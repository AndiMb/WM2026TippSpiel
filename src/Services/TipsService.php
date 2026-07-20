<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database as DB;

/**
 * Stellt die Tipps aller Mitspieler zusammen – aber AUSSCHLIESSLICH für Spiele,
 * deren Anpfiff bereits vorbei ist. So kann niemand vor Tippschluss abschreiben.
 */
final class TipsService
{
    /**
     * Liefert die zuletzt angepfiffenen Spiele samt aller Spieler-Tipps.
     *
     * @return array<int, array> Liste von Spielen mit eingebetteten Tipps
     */
    public static function recentMatchesWithTips(int $limit = 20): array
    {
        // Nur Spiele, deren Anstoß in der Vergangenheit liegt (Tippschluss vorbei).
        $matches = DB::all(
            "SELECT * FROM matches
             WHERE kickoff <= ?
             ORDER BY kickoff DESC
             LIMIT $limit",
            [DB::now()]
        );
        if (!$matches) {
            return [];
        }

        $players = DB::all("SELECT id, display_name FROM users WHERE is_active = 1 ORDER BY display_name");

        $out = [];
        foreach ($matches as $m) {
            $mid = (int) $m['id'];

            // Tipps zu diesem Spiel einlesen (user_id -> Tipp).
            $betRows = DB::all('SELECT user_id, pred1, pred2, points FROM bets WHERE match_id = ?', [$mid]);
            $bets = [];
            foreach ($betRows as $b) {
                $bets[(int) $b['user_id']] = $b;
            }

            // Für jeden aktiven Spieler eine Zeile bauen (auch ohne Tipp).
            $rows = [];
            foreach ($players as $p) {
                $uid = (int) $p['id'];
                $bet = $bets[$uid] ?? null;
                $rows[] = [
                    'user_id' => $uid,
                    'name'    => $p['display_name'],
                    'pred1'   => $bet ? (int) $bet['pred1'] : null,
                    'pred2'   => $bet ? (int) $bet['pred2'] : null,
                    'points'  => ($bet && $bet['points'] !== null) ? (int) $bet['points'] : null,
                    'has_bet' => $bet !== null,
                ];
            }

            // Beste Tipps zuerst (nach Punkten), dann Name.
            usort($rows, function ($a, $b) {
                $pa = $a['points'] ?? -1;
                $pb = $b['points'] ?? -1;
                return [$pb, $a['name']] <=> [$pa, $b['name']];
            });

            $m['tips'] = $rows;
            $out[] = $m;
        }
        return $out;
    }

    /**
     * Liefert die Bonusfragen-Antworten aller Mitspieler – aber AUSSCHLIESSLICH
     * für Fragen, die entweder bereits aufgelöst sind oder deren Einsendeschluss
     * verstrichen ist. Fragen ohne Deadline bleiben so lange geheim, bis sie
     * aufgelöst werden (sonst könnte man vor Ablauf abschreiben).
     *
     * @return array<int, array> Liste sichtbarer Fragen mit eingebetteten Antworten
     */
    public static function bonusOverview(): array
    {
        $questions = DB::all(
            "SELECT * FROM bonus_questions
             WHERE is_active = 1
               AND (
                    (correct_answer IS NOT NULL AND correct_answer != '')
                 OR (deadline IS NOT NULL AND deadline <= ?)
               )
             ORDER BY id",
            [DB::now()]
        );
        if (!$questions) {
            return [];
        }

        $players = DB::all("SELECT id, display_name FROM users WHERE is_active = 1 ORDER BY display_name");

        $out = [];
        foreach ($questions as $q) {
            $qid = (int) $q['id'];

            $ansRows = DB::all('SELECT user_id, answer, points FROM bonus_answers WHERE bonus_question_id = ?', [$qid]);
            $answers = [];
            foreach ($ansRows as $a) {
                $answers[(int) $a['user_id']] = $a;
            }

            $resolved = $q['correct_answer'] !== null && $q['correct_answer'] !== '';

            $rows = [];
            foreach ($players as $p) {
                $uid = (int) $p['id'];
                $a = $answers[$uid] ?? null;
                $rows[] = [
                    'user_id'   => $uid,
                    'name'      => $p['display_name'],
                    'answer'    => $a['answer'] ?? null,
                    'points'    => ($a && $a['points'] !== null) ? (int) $a['points'] : null,
                    'has_answer'=> $a !== null,
                ];
            }

            // Beste Antworten zuerst (nach Punkten), dann Name.
            usort($rows, function ($a, $b) {
                $pa = $a['points'] ?? -1;
                $pb = $b['points'] ?? -1;
                return [$pb, $a['name']] <=> [$pa, $b['name']];
            });

            $q['resolved'] = $resolved;
            $q['answers']  = $rows;
            $out[] = $q;
        }
        return $out;
    }
}
