<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database as DB;
use App\Models\Bet;
use App\Models\BonusQuestion;
use App\Models\MatchModel;
use App\Models\Setting;

/**
 * Berechnet Punkte für Tipps und Bonusfragen.
 *
 * Punktelogik (konfigurierbar in den Einstellungen):
 *   - exaktes Ergebnis          -> points_exact   (Standard 3)
 *   - richtige Tordifferenz     -> points_diff    (Standard 2, nur bei Nicht-Remis)
 *   - richtige Tendenz          -> points_tendency(Standard 1, Sieger oder Remis)
 *   - sonst                     -> 0
 */
final class ScoringService
{
    public static function pointsFor(int $pred1, int $pred2, int $score1, int $score2): int
    {
        $exact    = Setting::int('points_exact');
        $diff     = Setting::int('points_diff');
        $tendency = Setting::int('points_tendency');

        // 1) Exaktes Ergebnis
        if ($pred1 === $score1 && $pred2 === $score2) {
            return $exact;
        }

        $predSign  = $pred1  <=> $pred2;   // -1, 0, +1
        $scoreSign = $score1 <=> $score2;

        // Tendenz muss überhaupt stimmen, sonst 0 Punkte.
        if ($predSign !== $scoreSign) {
            return 0;
        }

        // 2) Richtige Tordifferenz (nur bei echtem Sieg, nicht bei Remis)
        if ($predSign !== 0 && ($pred1 - $pred2) === ($score1 - $score2)) {
            return $diff;
        }

        // 3) Richtige Tendenz (Sieger korrekt oder beides Remis)
        return $tendency;
    }

    /**
     * Wertet alle Tipps eines beendeten Spiels aus und schreibt die Punkte.
     * Wird beim Setzen eines Ergebnisses aufgerufen.
     */
    public static function scoreMatch(int $matchId): void
    {
        $match = MatchModel::find($matchId);
        if (!$match || $match['status'] !== 'finished'
            || $match['score1'] === null || $match['score2'] === null) {
            return;
        }

        foreach (Bet::forMatch($matchId) as $bet) {
            $pts = self::pointsFor(
                (int) $bet['pred1'], (int) $bet['pred2'],
                (int) $match['score1'], (int) $match['score2']
            );
            Bet::setPoints((int) $bet['id'], $pts);
        }
    }

    /**
     * Bewertet die Antworten einer aufgelösten Bonusfrage.
     * Vergleich erfolgt case-insensitiv und trimmt Leerzeichen.
     */
    public static function scoreBonus(int $questionId): void
    {
        $q = BonusQuestion::find($questionId);
        if (!$q || $q['correct_answer'] === null || $q['correct_answer'] === '') {
            return;
        }
        $correct = self::normalize($q['correct_answer']);
        $points  = (int) $q['points'];

        foreach (BonusQuestion::answersForQuestion($questionId) as $ans) {
            $pts = self::normalize($ans['answer']) === $correct ? $points : 0;
            DB::run('UPDATE bonus_answers SET points = ? WHERE id = ?', [$pts, $ans['id']]);
        }
    }

    /** Alle beendeten Spiele und aufgelösten Bonusfragen komplett neu berechnen. */
    public static function recalculateAll(): array
    {
        $matches = 0;
        foreach (MatchModel::all() as $m) {
            if ($m['status'] === 'finished' && $m['score1'] !== null && $m['score2'] !== null) {
                self::scoreMatch((int) $m['id']);
                $matches++;
            }
        }
        $bonus = 0;
        foreach (BonusQuestion::all() as $q) {
            if ($q['correct_answer'] !== null && $q['correct_answer'] !== '') {
                self::scoreBonus((int) $q['id']);
                $bonus++;
            }
        }
        return ['matches' => $matches, 'bonus' => $bonus];
    }

    private static function normalize(string $s): string
    {
        return mb_strtolower(trim($s));
    }
}
