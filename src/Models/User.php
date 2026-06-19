<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class User
{
    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByUsername(string $username): ?array
    {
        return DB::one('SELECT * FROM users WHERE username = ?', [$username]);
    }

    /** Alle Spieler (für Rangliste relevant). */
    public static function allPlayers(): array
    {
        return DB::all("SELECT * FROM users WHERE is_active = 1 ORDER BY display_name");
    }

    public static function all(): array
    {
        return DB::all('SELECT * FROM users ORDER BY role DESC, display_name');
    }

    public static function create(
        string $username,
        string $password,
        string $displayName,
        string $role = 'player'
    ): int {
        return DB::insert(
            'INSERT INTO users (username, password_hash, display_name, role, joined_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $displayName,
                $role,
                DB::now(),   // Beitrittszeitpunkt = jetzt (relevant für Variante B)
                DB::now(),
            ]
        );
    }

    public static function updatePassword(int $id, string $password): void
    {
        DB::run('UPDATE users SET password_hash = ? WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function update(int $id, string $displayName, string $role, int $isActive): void
    {
        DB::run('UPDATE users SET display_name = ?, role = ?, is_active = ? WHERE id = ?',
            [$displayName, $role, $isActive, $id]);
    }

    public static function delete(int $id): void
    {
        DB::run('DELETE FROM users WHERE id = ?', [$id]);
    }

    public static function usernameExists(string $username): bool
    {
        return (bool) DB::scalar('SELECT 1 FROM users WHERE username = ?', [$username]);
    }
}
