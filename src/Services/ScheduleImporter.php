<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\MatchModel;

/**
 * Importiert Spielplan UND Ergebnisse aus verschiedenen Quellen und
 * normalisiert sie in ein einheitliches Format.
 *
 * Quellen (in Reihenfolge der Empfehlung):
 *   1. OpenFootball worldcup.json  (Public Domain, kein API-Key)  -> importOpenFootball()
 *   2. Generisches JSON                                            -> importJson()
 *   3. CSV                                                         -> importCsv()
 *
 * Alle Methoden liefern eine Statistik: ['inserted'=>n, 'updated'=>n, 'scored'=>n].
 *
 * Da dieselbe Datei Spielplan und Ergebnisse enthält, deckt der Importer
 * beide Aufgaben ab: neue Spiele anlegen UND Ergebnisse aktualisieren.
 */
final class ScheduleImporter
{
    /** ---------------------------------------------------------------
     *  Öffentliche Einstiegspunkte
     *  --------------------------------------------------------------- */

    public static function importOpenFootball(string $url): array
    {
        $raw = self::httpGet($url);
        if ($raw === null) {
            throw new \RuntimeException("Quelle nicht erreichbar: $url");
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Antwort ist kein gültiges JSON.');
        }
        return self::applyMatches(self::parseOpenFootball($data));
    }

    public static function importJson(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Datei ist kein gültiges JSON.');
        }
        // Unterstützt sowohl das OpenFootball-Format als auch eine flache Liste.
        $matches = isset($data['matches']) || isset($data['rounds'])
            ? self::parseOpenFootball($data)
            : self::parseGenericList($data);
        return self::applyMatches($matches);
    }

    public static function importCsv(string $csvText): array
    {
        $matches = self::parseCsv($csvText);
        return self::applyMatches($matches);
    }

    /** ---------------------------------------------------------------
     *  Parser: Quelle -> normalisierte Match-Arrays
     *  --------------------------------------------------------------- */

    /**
     * OpenFootball-Format. Unterstützt beide Varianten:
     *   - { "matches": [ ... ] }
     *   - { "rounds": [ { "matches": [ ... ] } ] }
     */
    private static function parseOpenFootball(array $data): array
    {
        $rawMatches = [];
        if (isset($data['matches']) && is_array($data['matches'])) {
            $rawMatches = $data['matches'];
        } elseif (isset($data['rounds']) && is_array($data['rounds'])) {
            foreach ($data['rounds'] as $round) {
                foreach ($round['matches'] ?? [] as $m) {
                    $m['round'] = $m['round'] ?? ($round['name'] ?? null);
                    $rawMatches[] = $m;
                }
            }
        }

        $out = [];
        foreach ($rawMatches as $m) {
            $team1 = self::teamName($m['team1'] ?? null);
            $team2 = self::teamName($m['team2'] ?? null);
            if ($team1 === '' || $team2 === '') {
                continue; // Platzhalter (z.B. "Winner Group A") überspringen wir vorerst
            }

            $kickoff = self::toUtc($m['date'] ?? '', $m['time'] ?? null);
            $sc = self::extractScore($m['score'] ?? null);

            $group = $m['group'] ?? null;
            $num   = isset($m['num']) ? (int) $m['num'] : null;  // Spielnummer (KO-Phase)
            $out[] = [
                'ext_key'    => self::extKey($kickoff, $team1, $team2),
                'num'        => $num,
                'stage'      => $group ? 'group' : 'knockout',
                'round_name' => $m['round'] ?? null,
                'group_name' => $group,
                'team1'      => $team1,
                'team2'      => $team2,
                'kickoff'    => $kickoff,
                'venue'      => $m['ground'] ?? ($m['city'] ?? null),
                'score1'     => $sc['s1'],
                'score2'     => $sc['s2'],
                'status'     => $sc['status'],
                'et1'        => $sc['et1'],
                'et2'        => $sc['et2'],
                'pen1'       => $sc['pen1'],
                'pen2'       => $sc['pen2'],
            ];
        }
        return $out;
    }

    /** Flache JSON-Liste: [{date,time,team1,team2,group,venue,score1,score2}, ...] */
    private static function parseGenericList(array $data): array
    {
        $list = $data['fixtures'] ?? $data; // erlaube Wrapper oder direkte Liste
        $out = [];
        foreach ($list as $m) {
            if (!is_array($m) || empty($m['team1']) || empty($m['team2'])) {
                continue;
            }
            $kickoff = self::toUtc($m['date'] ?? '', $m['time'] ?? null);
            $s1 = isset($m['score1']) && $m['score1'] !== '' ? (int) $m['score1'] : null;
            $s2 = isset($m['score2']) && $m['score2'] !== '' ? (int) $m['score2'] : null;
            $status = ($s1 !== null && $s2 !== null) ? 'finished' : 'scheduled';
            $out[] = [
                'ext_key'    => self::extKey($kickoff, (string) $m['team1'], (string) $m['team2']),
                'stage'      => $m['stage'] ?? (!empty($m['group']) ? 'group' : 'knockout'),
                'round_name' => $m['round'] ?? null,
                'group_name' => $m['group'] ?? null,
                'team1'      => (string) $m['team1'],
                'team2'      => (string) $m['team2'],
                'kickoff'    => $kickoff,
                'venue'      => $m['venue'] ?? null,
                'score1'     => $s1,
                'score2'     => $s2,
                'status'     => $status,
            ];
        }
        return $out;
    }

    /**
     * CSV-Format mit Kopfzeile. Erwartete Spalten (Reihenfolge egal):
     *   date,time,team1,team2,group,venue,score1,score2
     * Trennzeichen ',' oder ';'.
     */
    private static function parseCsv(string $csvText): array
    {
        $csvText = preg_replace('/^\xEF\xBB\xBF/', '', $csvText); // BOM entfernen
        $lines = preg_split('/\r\n|\r|\n/', trim($csvText));
        if (!$lines || count($lines) < 2) {
            return [];
        }
        $delim = (substr_count($lines[0], ';') > substr_count($lines[0], ',')) ? ';' : ',';
        $header = array_map(fn($h) => strtolower(trim($h)), str_getcsv($lines[0], $delim));

        $out = [];
        for ($i = 1; $i < count($lines); $i++) {
            if (trim($lines[$i]) === '') {
                continue;
            }
            $cols = str_getcsv($lines[$i], $delim);
            $row = array_combine($header, array_pad($cols, count($header), '')) ?: [];
            if (empty($row['team1']) || empty($row['team2'])) {
                continue;
            }
            $kickoff = self::toUtc($row['date'] ?? '', $row['time'] ?? null);
            $s1 = isset($row['score1']) && $row['score1'] !== '' ? (int) $row['score1'] : null;
            $s2 = isset($row['score2']) && $row['score2'] !== '' ? (int) $row['score2'] : null;
            $status = ($s1 !== null && $s2 !== null) ? 'finished' : 'scheduled';
            $out[] = [
                'ext_key'    => self::extKey($kickoff, $row['team1'], $row['team2']),
                'stage'      => !empty($row['group']) ? 'group' : 'knockout',
                'round_name' => $row['round'] ?? null,
                'group_name' => $row['group'] ?? null,
                'team1'      => trim($row['team1']),
                'team2'      => trim($row['team2']),
                'kickoff'    => $kickoff,
                'venue'      => $row['venue'] ?? null,
                'score1'     => $s1,
                'score2'     => $s2,
                'status'     => $status,
            ];
        }
        return $out;
    }

    /** ---------------------------------------------------------------
     *  Upsert: normalisierte Matches in die DB schreiben
     *  --------------------------------------------------------------- */
    private static function applyMatches(array $matches): array
    {
        $inserted = $updated = $scored = 0;

        foreach ($matches as $m) {
            // KO-Spiele werden über die stabile Spielnummer erkannt – so wird
            // dasselbe Spiel auch dann wiedergefunden, wenn die Mannschaften
            // erst im Turnierverlauf feststehen (Platzhalter -> echtes Team).
            // Gruppenspiele (ohne Nummer) werden über den ext_key erkannt.
            $existing = (!empty($m['num']) ? MatchModel::findByNum((int) $m['num']) : null)
                ?? MatchModel::findByExtKey($m['ext_key']);

            if ($existing === null) {
                $newId = MatchModel::insertMatch($m);
                $inserted++;
                if ($m['status'] === 'finished') {
                    ScoringService::scoreMatch($newId);
                    $scored++;
                }
                continue;
            }

            // Vorhandenes Spiel aktualisieren – aber bereits manuell vom Admin
            // gesetzte Ergebnisse nicht ohne neuen Wert überschreiben.
            $becameFinished = $existing['status'] !== 'finished' && $m['status'] === 'finished';

            // Live-Zwischenstand (football-data.org) nicht durch die träge
            // OpenFootball-Quelle zurücksetzen, solange diese das Spiel noch
            // als offen führt.
            if ($m['status'] !== 'finished' && $existing['status'] === 'live') {
                $m['score1'] = $existing['score1'];
                $m['score2'] = $existing['score2'];
                $m['status'] = 'live';
            }

            // Falls die Quelle (noch) kein Ergebnis hat, vorhandenes behalten.
            if ($m['status'] !== 'finished' && $existing['status'] === 'finished') {
                $m['score1'] = $existing['score1'];
                $m['score2'] = $existing['score2'];
                $m['status']  = 'finished';
                $m['et1']  = $existing['et1']  ?? null;
                $m['et2']  = $existing['et2']  ?? null;
                $m['pen1'] = $existing['pen1'] ?? null;
                $m['pen2'] = $existing['pen2'] ?? null;
            }

            // Ergebnis-Korrektur erkennen: Spiel war schon beendet, die Quelle
            // liefert jetzt aber einen anderen Endstand -> Tipps NEU werten.
            // (Ohne das blieben nach einer Korrektur die alten Punkte stehen.)
            $resultChanged = $existing['status'] === 'finished'
                && $m['status'] === 'finished'
                && self::resultKey($existing) !== self::resultKey($m);

            MatchModel::updateFromImport((int) $existing['id'], $m);
            $updated++;

            if ($becameFinished || $resultChanged) {
                ScoringService::scoreMatch((int) $existing['id']);
                $scored++;
            }
        }

        // Mannschafts-Stammdaten (deutsche Namen, FIFA-Rang) aktuell halten.
        try {
            TeamService::syncFromData();
        } catch (\Throwable $e) {
            // Tabelle teams evtl. noch nicht vorhanden -> Import nicht abbrechen.
        }

        return ['inserted' => $inserted, 'updated' => $updated, 'scored' => $scored];
    }

    /** ---------------------------------------------------------------
     *  Hilfsfunktionen
     *  --------------------------------------------------------------- */

    /** Teamname extrahieren (kann String oder Objekt sein). */
    private static function teamName($team): string
    {
        if (is_array($team)) {
            return trim((string) ($team['name'] ?? $team['code'] ?? ''));
        }
        return trim((string) $team);
    }

    /**
     * Liest das Ergebnis aus dem OpenFootball-score-Objekt.
     *
     * Format: { "ht":[..], "ft":[..], "et":[..], "p":[..] }
     *   ft = Stand nach 90 Minuten (regulär)  -> score1/score2 (Tipp-Wertung)
     *   et = Stand nach Verlängerung          -> et1/et2 (nur bei Verlängerung)
     *   p  = Elfmeterschießen                 -> pen1/pen2 (nur bei Elfmeter)
     *
     * @return array{s1:?int,s2:?int,status:string,et1:?int,et2:?int,pen1:?int,pen2:?int}
     */
    private static function extractScore($score): array
    {
        $empty = ['s1' => null, 's2' => null, 'status' => 'scheduled',
                  'et1' => null, 'et2' => null, 'pen1' => null, 'pen2' => null];
        if (!is_array($score)) {
            return $empty;
        }
        $ft = $score['ft'] ?? null;
        if (!is_array($ft) || count($ft) !== 2 || $ft[0] === null || $ft[1] === null) {
            return $empty;
        }
        // Ein Wertepaar lesen (oder [null, null], wenn nicht vorhanden).
        $pair = static function ($v): array {
            return (is_array($v) && count($v) === 2 && $v[0] !== null && $v[1] !== null)
                ? [(int) $v[0], (int) $v[1]]
                : [null, null];
        };
        [$et1, $et2]   = $pair($score['et'] ?? null);
        [$pen1, $pen2] = $pair($score['p'] ?? null);

        return ['s1' => (int) $ft[0], 's2' => (int) $ft[1], 'status' => 'finished',
                'et1' => $et1, 'et2' => $et2, 'pen1' => $pen1, 'pen2' => $pen2];
    }

    /** Stabiler Schlüssel zur Duplikaterkennung. */
    private static function extKey(string $kickoff, string $team1, string $team2): string
    {
        return substr($kickoff, 0, 10) . '|' . $team1 . '|' . $team2;
    }

    /** Vergleichsschlüssel eines Endergebnisses (inkl. Verlängerung/Elfmeter). */
    private static function resultKey(array $m): string
    {
        $v = static fn($x) => $x === null ? '-' : (string) (int) $x;
        return $v($m['score1'] ?? null) . ':' . $v($m['score2'] ?? null)
            . '|' . $v($m['et1'] ?? null) . ':' . $v($m['et2'] ?? null)
            . '|' . $v($m['pen1'] ?? null) . ':' . $v($m['pen2'] ?? null);
    }

    /**
     * Wandelt Datum + Zeit der Quelle in einen UTC-Zeitstempel.
     * Unterstützt OpenFootball-Zeiten wie "13:00 UTC-6" sowie reine Zeiten.
     */
    private static function toUtc(string $date, ?string $time): string
    {
        $date = trim($date);
        if ($date === '') {
            // Ohne Datum nicht sinnvoll – weit in die Vergangenheit legen.
            return gmdate('Y-m-d H:i:s', 0);
        }
        $time = trim((string) $time);

        // Offset wie "UTC-6", "UTC+2" extrahieren.
        $tz = 'UTC';
        if (preg_match('/UTC\s*([+-]\d{1,2})(?::?(\d{2}))?/i', $time, $mm)) {
            $hours = (int) $mm[1];
            $mins  = isset($mm[2]) ? (int) $mm[2] : 0;
            $tz = sprintf('%+03d:%02d', $hours, $mins);
            $time = trim(preg_replace('/UTC.*$/i', '', $time));
        }
        if ($time === '') {
            $time = '00:00';
        }

        try {
            $dt = new \DateTime("$date $time", new \DateTimeZone($tz));
            $dt->setTimezone(new \DateTimeZone('UTC'));
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return $date . ' 00:00:00';
        }
    }

    /** Robustes HTTP-GET via cURL (Fallback: file_get_contents). */
    private static function httpGet(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_USERAGENT      => 'WM2026-Tippspiel/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ($body !== false && $code >= 200 && $code < 300) ? (string) $body : null;
        }

        $ctx = stream_context_create(['http' => ['timeout' => 20, 'user_agent' => 'WM2026-Tippspiel/1.0']]);
        $body = @file_get_contents($url, false, $ctx);
        return $body === false ? null : $body;
    }
}
