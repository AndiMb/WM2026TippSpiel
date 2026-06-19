<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Sicheres Session-Management.
 * Setzt restriktive Cookie-Flags und regeneriert die ID beim Login.
 */
final class Session
{
    public static function start(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('TIPP_SID');
        session_set_cookie_params([
            'lifetime' => 0,                       // bis Browser geschlossen wird
            'path'     => ($config['app']['base_path'] ?: '/'),
            'httponly' => true,                    // kein JS-Zugriff auf Cookie
            'secure'   => (bool) $config['app']['https_only'],
            'samesite' => 'Lax',                   // CSRF-Grundschutz für Top-Level-Navigation
        ]);
        session_start();

        // Session-Fixation vorbeugen: ID periodisch erneuern.
        if (!isset($_SESSION['_started'])) {
            session_regenerate_id(true);
            $_SESSION['_started'] = time();
        }
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Komplett ausloggen. */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Einmal-Meldung (Flash) setzen. */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /** Flash-Meldungen abholen und leeren. */
    public static function takeFlashes(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $f;
    }
}
