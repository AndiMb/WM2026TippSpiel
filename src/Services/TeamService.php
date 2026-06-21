<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database as DB;

/**
 * Mannschafts-Informationen:
 *   - deutscher Anzeigename + FIFA-Rang (aus Tabelle `teams`, Fallback: Datei)
 *   - letztes Spielergebnis je Team (abgeleitet aus den eigenen Spieldaten)
 *
 * Die Stammdaten stammen aus src/Data/teams.php und werden per syncFromData()
 * in die Tabelle `teams` übernommen.
 */
final class TeamService
{
    /** @var array<string,array{de:string,rank:?int,code:?string}>|null */
    private static ?array $map = null;

    /** Rohdaten aus der PHP-Datei. */
    public static function data(): array
    {
        return require \dirname(__DIR__) . '/Data/teams.php';
    }

    /** Gesamte Zuordnung (englisch -> Infos). DB bevorzugt, sonst Datei. */
    public static function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }
        self::$map = [];

        // Bevorzugt aus der DB (erlaubt spätere Anpassungen durch den Admin).
        try {
            foreach (DB::all('SELECT name_en, name_de, fifa_rank, code FROM teams') as $r) {
                self::$map[$r['name_en']] = [
                    'de'   => $r['name_de'],
                    'rank' => $r['fifa_rank'] !== null ? (int) $r['fifa_rank'] : null,
                    'code' => $r['code'],
                ];
            }
        } catch (\Throwable $e) {
            // Tabelle existiert evtl. noch nicht (vor der Migration) -> ignorieren.
        }

        // Fallback / Auffüllen aus der Datei (falls DB leer oder Team fehlt).
        if (!self::$map) {
            self::$map = self::data();
        }

        return self::$map;
    }

    /** Deutscher Anzeigename; unbekannte Teams bleiben im Original. */
    public static function nameDe(string $en): string
    {
        $m = self::map();
        return $m[$en]['de'] ?? $en;
    }

    /** FIFA-Weltranglistenplatz oder null. */
    public static function rank(string $en): ?int
    {
        $m = self::map();
        return $m[$en]['rank'] ?? null;
    }

    /**
     * ISO-3166-Alpha-2-Code für die Flaggen-Grafik (oder null).
     * Wird direkt aus der Datei gelesen, da die DB-Tabelle keinen ISO-Code führt.
     */
    public static function iso2(string $en): ?string
    {
        static $iso = null;
        if ($iso === null) {
            $iso = [];
            foreach (self::data() as $name => $info) {
                if (!empty($info['iso2'])) {
                    $iso[$name] = $info['iso2'];
                }
            }
        }
        return $iso[$en] ?? null;
    }

    /**
     * Letztes beendetes Spiel je Mannschaft, abgeleitet aus den eigenen Daten.
     * Eine einzige Abfrage, danach in PHP ausgewertet.
     *
     * @return array<string, array{gf:int,ga:int,opp:string,date:string,outcome:string}>
     */
    public static function lastResultsMap(): array
    {
        $rows = DB::all(
            "SELECT team1, team2, score1, score2, kickoff
             FROM matches
             WHERE status = 'finished' AND score1 IS NOT NULL AND score2 IS NOT NULL
             ORDER BY kickoff DESC"
        );

        $out = [];
        foreach ($rows as $r) {
            $s1 = (int) $r['score1'];
            $s2 = (int) $r['score2'];

            // Heimteam-Sicht
            if (!isset($out[$r['team1']])) {
                $out[$r['team1']] = self::result($s1, $s2, $r['team2'], $r['kickoff']);
            }
            // Auswärtsteam-Sicht
            if (!isset($out[$r['team2']])) {
                $out[$r['team2']] = self::result($s2, $s1, $r['team1'], $r['kickoff']);
            }
        }
        return $out;
    }

    private static function result(int $gf, int $ga, string $opp, string $date): array
    {
        $outcome = $gf > $ga ? 'win' : ($gf < $ga ? 'loss' : 'draw');
        return ['gf' => $gf, 'ga' => $ga, 'opp' => $opp, 'date' => $date, 'outcome' => $outcome];
    }

    /**
     * Übernimmt die Stammdaten aus der Datei in die Tabelle `teams` (Upsert).
     * Wird beim Seed und nach jedem Spielplan-Import aufgerufen.
     */
    public static function syncFromData(): int
    {
        $count = 0;
        foreach (self::data() as $en => $info) {
            $updated = DB::run(
                'UPDATE teams SET name_de = ?, fifa_rank = ?, code = ?, updated_at = ? WHERE name_en = ?',
                [$info['de'], $info['rank'] ?? null, $info['code'] ?? null, DB::now(), $en]
            );
            if ($updated === 0) {
                DB::run(
                    'INSERT INTO teams (name_en, name_de, fifa_rank, code, updated_at) VALUES (?, ?, ?, ?, ?)',
                    [$en, $info['de'], $info['rank'] ?? null, $info['code'] ?? null, DB::now()]
                );
            }
            $count++;
        }
        self::$map = null; // Cache invalidieren
        return $count;
    }
}
