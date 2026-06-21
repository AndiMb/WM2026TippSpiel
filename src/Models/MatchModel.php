<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

/**
 * Datenzugriff für Spiele. ("Match" ist in PHP ein reserviertes Schlüsselwort,
 * daher heißt die Klasse MatchModel.)
 */
final class MatchModel
{
    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM matches WHERE id = ?', [$id]);
    }

    public static function all(): array
    {
        return DB::all('SELECT * FROM matches ORDER BY kickoff, id');
    }

    /** Kommende Spiele (Anstoß in der Zukunft), aufsteigend. */
    public static function upcoming(int $limit = 5): array
    {
        return DB::all(
            "SELECT * FROM matches
             WHERE kickoff > ? AND status = 'scheduled'
             ORDER BY kickoff ASC LIMIT $limit",
            [DB::now()]
        );
    }

    /** Bereits beendete Spiele, absteigend (neueste zuerst). */
    public static function finished(int $limit = 10): array
    {
        return DB::all(
            "SELECT * FROM matches
             WHERE status = 'finished'
             ORDER BY kickoff DESC LIMIT $limit"
        );
    }

    /** Spiele, die noch tippbar sind (Anstoß in der Zukunft). */
    public static function openForBets(): array
    {
        return DB::all(
            "SELECT * FROM matches
             WHERE kickoff > ? AND status = 'scheduled'
             ORDER BY kickoff ASC",
            [DB::now()]
        );
    }

    public static function findByExtKey(string $extKey): ?array
    {
        return DB::one('SELECT * FROM matches WHERE ext_key = ?', [$extKey]);
    }

    /** KO-Spiele werden über die offizielle Spielnummer eindeutig erkannt. */
    public static function findByNum(int $num): ?array
    {
        return DB::one('SELECT * FROM matches WHERE num = ?', [$num]);
    }

    /** Alle Gruppenspiele (für die Gruppentabellen). */
    public static function groupMatches(): array
    {
        return DB::all(
            "SELECT * FROM matches WHERE group_name IS NOT NULL ORDER BY group_name, kickoff, id"
        );
    }

    /** Alle KO-Spiele (für den Turnierbaum), nach Spielnummer/Anstoß sortiert. */
    public static function knockoutMatches(): array
    {
        return DB::all(
            "SELECT * FROM matches
             WHERE group_name IS NULL AND stage = 'knockout'
             ORDER BY COALESCE(num, 9999), kickoff, id"
        );
    }

    public static function insertMatch(array $m): int
    {
        return DB::insert(
            'INSERT INTO matches
                (ext_key, num, stage, round_name, group_name, team1, team2, kickoff, venue, score1, score2, status, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $m['ext_key'] ?? null, $m['num'] ?? null, $m['stage'] ?? 'group', $m['round_name'] ?? null,
                $m['group_name'] ?? null, $m['team1'], $m['team2'], $m['kickoff'],
                $m['venue'] ?? null, $m['score1'] ?? null, $m['score2'] ?? null,
                $m['status'] ?? 'scheduled', DB::now(),
            ]
        );
    }

    public static function updateFromImport(int $id, array $m): void
    {
        DB::run(
            'UPDATE matches SET
                num = ?, stage = ?, round_name = ?, group_name = ?, team1 = ?, team2 = ?,
                kickoff = ?, venue = ?, score1 = ?, score2 = ?, status = ?, updated_at = ?
             WHERE id = ?',
            [
                $m['num'] ?? null, $m['stage'] ?? 'group', $m['round_name'] ?? null, $m['group_name'] ?? null,
                $m['team1'], $m['team2'], $m['kickoff'], $m['venue'] ?? null,
                $m['score1'] ?? null, $m['score2'] ?? null, $m['status'] ?? 'scheduled',
                DB::now(), $id,
            ]
        );
    }

    /** Nur das Ergebnis/den Status setzen (manuell oder per API). */
    public static function setResult(int $id, ?int $score1, ?int $score2, string $status): void
    {
        DB::run('UPDATE matches SET score1 = ?, score2 = ?, status = ?, updated_at = ? WHERE id = ?',
            [$score1, $score2, $status, DB::now(), $id]);
    }

    public static function count(): int
    {
        return (int) DB::scalar('SELECT COUNT(*) FROM matches');
    }
}
