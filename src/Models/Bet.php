<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class Bet
{
    /** Tipp eines Benutzers zu einem Spiel. */
    public static function forUserAndMatch(int $userId, int $matchId): ?array
    {
        return DB::one('SELECT * FROM bets WHERE user_id = ? AND match_id = ?', [$userId, $matchId]);
    }

    /** Alle Tipps eines Benutzers, indexiert nach match_id. */
    public static function mapForUser(int $userId): array
    {
        $rows = DB::all('SELECT * FROM bets WHERE user_id = ?', [$userId]);
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['match_id']] = $r;
        }
        return $map;
    }

    /** Tipp anlegen oder aktualisieren (Upsert). Punkte werden zurückgesetzt. */
    public static function save(int $userId, int $matchId, int $pred1, int $pred2): void
    {
        $existing = self::forUserAndMatch($userId, $matchId);
        if ($existing) {
            DB::run(
                'UPDATE bets SET pred1 = ?, pred2 = ?, points = NULL, updated_at = ? WHERE id = ?',
                [$pred1, $pred2, DB::now(), $existing['id']]
            );
        } else {
            DB::insert(
                'INSERT INTO bets (user_id, match_id, pred1, pred2, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$userId, $matchId, $pred1, $pred2, DB::now(), DB::now()]
            );
        }
    }

    public static function setPoints(int $betId, int $points): void
    {
        DB::run('UPDATE bets SET points = ? WHERE id = ?', [$points, $betId]);
    }

    /** Anzahl offener Tipps (tippbare Spiele ohne Tipp) für einen Benutzer. */
    public static function openCount(int $userId): int
    {
        return (int) DB::scalar(
            "SELECT COUNT(*) FROM matches m
             WHERE m.kickoff > ? AND m.status = 'scheduled'
               AND NOT EXISTS (SELECT 1 FROM bets b WHERE b.match_id = m.id AND b.user_id = ?)",
            [DB::now(), $userId]
        );
    }

    /** Alle Tipps zu einem Spiel (für die Wertung). */
    public static function forMatch(int $matchId): array
    {
        return DB::all('SELECT * FROM bets WHERE match_id = ?', [$matchId]);
    }
}
