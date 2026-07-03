<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\MatchModel;
use App\Models\Setting;
use App\Core\Database as DB;

/**
 * Live-Zwischenstände von football-data.org (API v4, Wettbewerb "WC").
 *
 * Ergänzt die OpenFootball-Quelle (Spielplan + Endergebnisse, aber träge) um
 * zeitnahe Stände: laufende Spiele bekommen status='live' und den aktuellen
 * Spielstand; frisch beendete Spiele werden sofort abgeschlossen und die
 * Tipps gewertet – Minuten statt Stunden nach Abpfiff.
 *
 * Wichtige Zuordnung (KO-Spiele, siehe Migration 005):
 *   score1/2 = Stand nach 90 Minuten   (API: regularTime bzw. fullTime)
 *   et1/2    = Stand nach Verlängerung (API: regularTime + extraTime)
 *   pen1/2   = Elfmeterschießen        (API: penalties)
 *   Achtung: "fullTime" der API ist die GESAMTSUMME inkl. Verlängerungs- und
 *   Elfmetertoren.
 *
 * Kein API-Key konfiguriert -> alles bleibt aus (No-Op). Aufrufe sind über
 * eine Drossel (Setting 'live_last_fetch') auf ~1/Minute begrenzt, damit auch
 * der "faule" Abruf über den /live-Endpunkt das Limit des Gratis-Tarifs
 * (10 Anfragen/Minute) nie erreicht.
 */
final class LiveScoreService
{
    private const API_URL = 'https://api.football-data.org/v4/competitions/WC/matches';

    /** Mindestabstand zwischen zwei API-Abrufen in Sekunden. */
    private const THROTTLE_SECONDS = 55;

    /**
     * Namensunterschiede API -> OpenFootball (nur wo die normalisierten
     * Namen nicht ohnehin übereinstimmen).
     */
    private const ALIASES = [
        'bosnia and herzegovina' => 'Bosnia & Herzegovina',
        'cape verde islands'     => 'Cape Verde',
        'congo dr'               => 'DR Congo',
        'dr congo'               => 'DR Congo',
        'korea republic'         => 'South Korea',
        'ir iran'                => 'Iran',
        'united states'          => 'USA',
        'czechia'                => 'Czech Republic',
    ];

    /**
     * Holt aktuelle Stände und schreibt sie in die Datenbank.
     *
     * @param bool $force Drossel umgehen (für das Cron-Skript)
     * @return array{checked:int, live:int, finished:int, note?:string}
     */
    public static function update(bool $force = false): array
    {
        $none = ['checked' => 0, 'live' => 0, 'finished' => 0];

        $token = trim((string) config('import.footballdata_token'));
        if ($token === '') {
            return $none + ['note' => 'kein API-Key konfiguriert'];
        }

        // Drossel: höchstens ein Abruf pro Minute (Gratis-Limit schonen).
        $last = (int) Setting::get('live_last_fetch', '0');
        if (!$force && time() - $last < self::THROTTLE_SECONDS) {
            return $none + ['note' => 'gedrosselt'];
        }

        // Nur abrufen, wenn gerade plausibel ein Spiel läuft (Anstoß in den
        // letzten 4 Stunden oder in den nächsten 10 Minuten, noch nicht beendet).
        $window = (int) DB::scalar(
            "SELECT COUNT(*) FROM matches
             WHERE status != 'finished' AND kickoff BETWEEN ? AND ?",
            [gmdate('Y-m-d H:i:s', time() - 4 * 3600), gmdate('Y-m-d H:i:s', time() + 600)]
        );
        if ($window === 0 && !$force) {
            return $none + ['note' => 'kein Spiel im Zeitfenster'];
        }

        Setting::set('live_last_fetch', (string) time());

        $url = self::API_URL
            . '?dateFrom=' . gmdate('Y-m-d', time() - 86400)
            . '&dateTo='   . gmdate('Y-m-d', time() + 86400);
        $raw = self::httpGet($url, $token);
        if ($raw === null) {
            return $none + ['note' => 'API nicht erreichbar'];
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['matches']) || !is_array($data['matches'])) {
            return $none + ['note' => 'unerwartete API-Antwort'];
        }

        return self::applyApiMatches($data['matches']);
    }

    /**
     * Überträgt API-Spiele auf die eigenen Spiele (öffentlich für Tests).
     *
     * @return array{checked:int, live:int, finished:int, unmatched:int}
     */
    public static function applyApiMatches(array $apiMatches): array
    {
        $index = self::buildIndex();
        $stats = ['checked' => 0, 'live' => 0, 'finished' => 0, 'unmatched' => 0];

        foreach ($apiMatches as $am) {
            $status = (string) ($am['status'] ?? '');
            if (!in_array($status, ['IN_PLAY', 'PAUSED', 'FINISHED'], true)) {
                continue;
            }
            $stats['checked']++;

            $hit = self::findMatch($am, $index);
            if ($hit === null) {
                $stats['unmatched']++;
                continue;
            }
            [$match, $swap] = $hit;
            $score = is_array($am['score'] ?? null) ? $am['score'] : [];

            if ($status !== 'FINISHED') {
                // Laufendes Spiel: aktuellen Stand übernehmen ("fullTime" trägt
                // während des Spiels den aktuellen Spielstand).
                [$s1, $s2] = self::pair($score['fullTime'] ?? null) ?? [0, 0];
                if ($swap) {
                    [$s1, $s2] = [$s2, $s1];
                }
                if ($match['status'] !== 'live'
                    || (int) $match['score1'] !== $s1 || (int) $match['score2'] !== $s2) {
                    MatchModel::setResult((int) $match['id'], $s1, $s2, 'live');
                    $stats['live']++;
                }
                continue;
            }

            // Beendetes Spiel: bereits abgeschlossene nicht erneut anfassen
            // (OpenFootball bzw. Admin bleiben die letzte Instanz).
            if ($match['status'] === 'finished') {
                continue;
            }
            $mapped = self::mapFinished($score);
            if ($mapped === null) {
                continue; // ohne verwertbares Ergebnis nichts erzwingen
            }
            [$s1, $s2, $et1, $et2, $p1, $p2] = $mapped;
            if ($swap) {
                [$s1, $s2, $et1, $et2, $p1, $p2] = [$s2, $s1, $et2, $et1, $p2, $p1];
            }
            MatchModel::setResult((int) $match['id'], $s1, $s2, 'finished', $et1, $et2, $p1, $p2);
            ScoringService::scoreMatch((int) $match['id']);
            $stats['finished']++;
        }

        return $stats;
    }

    /**
     * Endergebnis aus dem score-Objekt der API ableiten.
     * @return array{0:int,1:int,2:?int,3:?int,4:?int,5:?int}|null
     *         [score1, score2, et1, et2, pen1, pen2]
     */
    private static function mapFinished(array $score): ?array
    {
        $ft  = self::pair($score['fullTime'] ?? null);
        if ($ft === null) {
            return null;
        }
        $duration = (string) ($score['duration'] ?? 'REGULAR');

        if ($duration === 'REGULAR') {
            return [$ft[0], $ft[1], null, null, null, null];
        }

        $rt = self::pair($score['regularTime'] ?? null);
        if ($rt === null) {
            return null; // Verlängerung ohne 90-Minuten-Stand -> nicht zuordenbar
        }

        if ($duration === 'PENALTY_SHOOTOUT') {
            $ex  = self::pair($score['extraTime'] ?? null) ?? [0, 0];
            $pen = self::pair($score['penalties'] ?? null);
            return [$rt[0], $rt[1], $rt[0] + $ex[0], $rt[1] + $ex[1], $pen[0] ?? null, $pen[1] ?? null];
        }

        // EXTRA_TIME: fullTime = Stand nach Verlängerung (ohne Elfmeter).
        return [$rt[0], $rt[1], $ft[0], $ft[1], null, null];
    }

    /** [home, away] als int-Paar oder null, wenn unvollständig. */
    private static function pair($v): ?array
    {
        if (is_array($v) && isset($v['home'], $v['away'])) {
            return [(int) $v['home'], (int) $v['away']];
        }
        return null;
    }

    /** ---------------------------------------------------------------
     *  Zuordnung API-Spiel -> eigenes Spiel (Datum + Mannschaftsnamen)
     *  --------------------------------------------------------------- */

    /** @return array<string, array{m:array, swap:bool}> */
    private static function buildIndex(): array
    {
        $index = [];
        foreach (MatchModel::all() as $m) {
            $date = substr((string) $m['kickoff'], 0, 10);
            $a = self::normTeam((string) $m['team1']);
            $b = self::normTeam((string) $m['team2']);
            if ($a === '' || $b === '') {
                continue; // KO-Platzhalter (W##, 1A, ...) sind nicht zuordenbar
            }
            $index[$date . '|' . $a . '|' . $b] = ['m' => $m, 'swap' => false];
            $index[$date . '|' . $b . '|' . $a] = ['m' => $m, 'swap' => true];
        }
        return $index;
    }

    /** @return array{0:array,1:bool}|null [Spiel, Heim/Auswärts vertauscht?] */
    private static function findMatch(array $am, array $index): ?array
    {
        $home = self::apiTeam($am['homeTeam'] ?? null);
        $away = self::apiTeam($am['awayTeam'] ?? null);
        if ($home === '' || $away === '') {
            return null;
        }
        $utc = (string) ($am['utcDate'] ?? '');
        $ts  = strtotime($utc);
        if ($ts === false) {
            return null;
        }
        // Anstoß kann um Mitternacht (UTC) liegen -> beide Nachbartage prüfen.
        foreach ([0, -86400, 86400] as $shift) {
            $key = gmdate('Y-m-d', $ts + $shift) . '|' . $home . '|' . $away;
            if (isset($index[$key])) {
                return [$index[$key]['m'], $index[$key]['swap']];
            }
        }
        return null;
    }

    /** API-Teamnamen lesen und normalisieren (inkl. Alias-Auflösung). */
    private static function apiTeam($team): string
    {
        $name = is_array($team) ? trim((string) ($team['name'] ?? '')) : '';
        if ($name === '') {
            return '';
        }
        $alias = self::ALIASES[mb_strtolower($name)] ?? null;
        return self::normTeam($alias ?? $name);
    }

    /**
     * Mannschaftsnamen für den Vergleich normalisieren: Kleinbuchstaben,
     * Akzente entfernen, alles außer a-z verwerfen. KO-Platzhalter -> ''.
     */
    private static function normTeam(string $name): string
    {
        if (preg_match('/^[WL]\d+$/', $name) || preg_match('#^[123][A-L]#', $name)) {
            return '';
        }
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if ($t === false || $t === '') {
            $t = $name;
        }
        return (string) preg_replace('/[^a-z]/', '', strtolower($t));
    }

    /** HTTP-GET mit API-Key (kurzes Timeout, damit Seitenaufrufe nie hängen). */
    private static function httpGet(string $url, string $token): ?string
    {
        $ctx = stream_context_create(['http' => [
            'method'  => 'GET',
            'header'  => "X-Auth-Token: $token\r\nAccept: application/json\r\n",
            'timeout' => 6,
        ]]);
        $raw = @file_get_contents($url, false, $ctx);
        return $raw === false ? null : $raw;
    }
}
