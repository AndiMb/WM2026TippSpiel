<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

/**
 * Key/Value-Einstellungen. Werden gecacht, damit jede Anfrage nur einmal lädt.
 */
final class Setting
{
    private static ?array $cache = null;

    /** Standardwerte – greifen, solange nichts in der DB steht. */
    public const DEFAULTS = [
        'points_exact'    => '3',     // exaktes Ergebnis
        'points_diff'     => '2',     // richtige Tordifferenz (kein Remis)
        'points_tendency' => '1',     // richtige Tendenz (Sieger/Remis)
        'scoring_mode'    => 'zero_past', // 'zero_past' (Variante A) | 'since_join' (Variante B)
        'bonus_enabled'   => '0',     // Bonusfragen aktiv?
        'tournament_name' => 'FIFA WM 2026',
    ];

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }
        self::$cache = self::DEFAULTS;
        foreach (DB::all('SELECT skey, sval FROM settings') as $row) {
            self::$cache[$row['skey']] = $row['sval'];
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        self::load();
        return self::$cache[$key] ?? $default ?? self::DEFAULTS[$key] ?? null;
    }

    public static function int(string $key): int
    {
        return (int) self::get($key);
    }

    public static function bool(string $key): bool
    {
        return self::get($key) === '1';
    }

    public static function set(string $key, string $value): void
    {
        // Portables Upsert: erst versuchen zu aktualisieren, sonst einfügen.
        $updated = DB::run('UPDATE settings SET sval = ? WHERE skey = ?', [$value, $key]);
        if ($updated === 0) {
            DB::run('INSERT INTO settings (skey, sval) VALUES (?, ?)', [$key, $value]);
        }
        self::$cache = null; // Cache invalidieren
    }

    public static function all(): array
    {
        self::load();
        return self::$cache;
    }
}
