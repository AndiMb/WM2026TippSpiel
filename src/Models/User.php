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
        string $role = 'player',
        string $locale = 'de',
        string $theme = 'standard'
    ): int {
        return DB::insert(
            'INSERT INTO users (username, password_hash, display_name, role, locale, theme, joined_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $displayName,
                $role,
                $locale,
                $theme,
                DB::now(),   // Beitrittszeitpunkt = jetzt (relevant für Variante B)
                DB::now(),
            ]
        );
    }

    /** Bevorzugte Sprache eines Benutzers setzen. */
    public static function updateLocale(int $id, string $locale): void
    {
        DB::run('UPDATE users SET locale = ? WHERE id = ?', [$locale, $id]);
    }

    /** Bevorzugte Ansicht (Design) eines Benutzers setzen. */
    public static function updateTheme(int $id, string $theme): void
    {
        DB::run('UPDATE users SET theme = ? WHERE id = ?', [$theme, $id]);
    }

    public static function updatePassword(int $id, string $password): void
    {
        DB::run('UPDATE users SET password_hash = ? WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function update(int $id, string $displayName, string $role, int $isActive, string $locale = 'de', string $theme = 'standard'): void
    {
        DB::run('UPDATE users SET display_name = ?, role = ?, is_active = ?, locale = ?, theme = ? WHERE id = ?',
            [$displayName, $role, $isActive, $locale, $theme, $id]);
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
