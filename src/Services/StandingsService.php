<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database as DB;
use App\Models\Setting;

/**
 * Erstellt die Rangliste.
 *
 * Sortierung laut Vorgabe:
 *   1. Punkte (absteigend)
 *   2. Anzahl exakter Ergebnisse (absteigend)
 *   3. Anzahl richtiger Tendenzen (absteigend)
 *
 * Nachhol-Regel über die Einstellung 'scoring_mode':
 *   - 'zero_past'  (Variante A): alle beendeten Spiele zählen. Fehlt ein Tipp
 *                  (z.B. weil das Spiel vor dem Beitritt lag), gibt es 0 Punkte.
 *   - 'since_join' (Variante B): pro Spieler zählen nur Spiele, deren Anstoß
 *                  am oder nach dem Beitrittszeitpunkt liegt.
 */
final class StandingsService
{
    public static function build(): array
    {
        $mode         = Setting::get('scoring_mode');
        $bonusEnabled = Setting::bool('bonus_enabled');
        $pointsExact  = Setting::int('points_exact');
        $pointsDiff   = Setting::int('points_diff');

        $players = DB::all("SELECT id, display_name, joined_at FROM users WHERE is_active = 1");

        $rows = [];
        foreach ($players as $p) {
            $uid = (int) $p['id'];

            // Bedingung für Variante B: nur Spiele seit Beitritt.
            $joinFilter = '';
            $params = [$uid];
            if ($mode === 'since_join') {
                $joinFilter = ' AND m.kickoff >= ?';
                $params[] = $p['joined_at'];
            }

            // Aggregierte Tipp-Statistik über beendete Spiele.
            $stat = DB::one(
                "SELECT
                    COALESCE(SUM(b.points), 0)                              AS pts,
                    COALESCE(SUM(CASE WHEN b.points = ? THEN 1 ELSE 0 END), 0) AS exact_cnt,
                    COALESCE(SUM(CASE WHEN b.points = ? THEN 1 ELSE 0 END), 0) AS diff_cnt,
                    COALESCE(SUM(CASE WHEN b.points > 0 THEN 1 ELSE 0 END), 0) AS hit_cnt,
                    COUNT(b.id)                                            AS bet_cnt
                 FROM bets b
                 JOIN matches m ON m.id = b.match_id
                 WHERE b.user_id = ? AND m.status = 'finished'
                   AND b.points IS NOT NULL $joinFilter",
                array_merge([$pointsExact, $pointsDiff], $params)
            );

            $points  = (int) ($stat['pts'] ?? 0);
            $exact   = (int) ($stat['exact_cnt'] ?? 0);
            $diff    = (int) ($stat['diff_cnt'] ?? 0);
            // "Richtige Tendenzen" = Tipps mit Punkten, die weder exakt noch
            // richtige Tordifferenz waren.
            $tendency = (int) ($stat['hit_cnt'] ?? 0) - $exact - $diff;
            $betCount = (int) ($stat['bet_cnt'] ?? 0);

            // Bonuspunkte addieren (wenn aktiviert).
            $bonusPts = 0;
            if ($bonusEnabled) {
                $bonusPts = (int) DB::scalar(
                    'SELECT COALESCE(SUM(points), 0) FROM bonus_answers
                     WHERE user_id = ? AND points IS NOT NULL', [$uid]
                );
            }

            $rows[] = [
                'user_id'   => $uid,
                'name'      => $p['display_name'],
                'points'    => $points + $bonusPts,
                'match_pts' => $points,
                'bonus_pts' => $bonusPts,
                'exact'     => $exact,
                'diff'      => $diff,
                'tendency'  => $tendency,
                'bets'      => $betCount,
            ];
        }

        // Sortierung: Punkte > exakte > Tordifferenz > Tendenzen > Name.
        usort($rows, function ($a, $b) {
            return [$b['points'], $b['exact'], $b['diff'], $b['tendency'], $a['name']]
               <=> [$a['points'], $a['exact'], $a['diff'], $a['tendency'], $b['name']];
        });

        // Plätze vergeben (gleiche Werte = gleicher Platz).
        $rank = 0; $shown = 0; $prev = null;
        foreach ($rows as &$r) {
            $shown++;
            $key = [$r['points'], $r['exact'], $r['diff'], $r['tendency']];
            if ($key !== $prev) {
                $rank = $shown;
                $prev = $key;
            }
            $r['rank'] = $rank;
        }
        unset($r);

        return $rows;
    }
}
