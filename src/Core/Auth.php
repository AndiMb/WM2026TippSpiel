<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Authentifizierung & Autorisierung.
 * Hält den eingeloggten Benutzer in der Session.
 */
final class Auth
{
    /** Login-Versuch. Gibt true bei Erfolg. */
    public static function attempt(string $username, string $password): bool
    {
        $user = User::findByUsername($username);
        if (!$user || (int) $user['is_active'] !== 1) {
            // Dummy-Verify gegen Timing-Angriffe (Antwortzeit angleichen).
            password_verify($password, '$2y$10$usesomesillystringforsalt0000000000000000000000000000');
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Rehash, falls sich der Standard-Algorithmus geändert hat.
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword((int) $user['id'], $password);
        }

        session_regenerate_id(true);
        Session::set('uid', (int) $user['id']);
        return true;
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function check(): bool
    {
        return Session::get('uid') !== null;
    }

    /** Aktuell eingeloggter Benutzer als Array (oder null). */
    public static function user(): ?array
    {
        $uid = Session::get('uid');
        if ($uid === null) {
            return null;
        }
        static $cache = null;
        if ($cache === null || (int) $cache['id'] !== (int) $uid) {
            $cache = User::find((int) $uid);
        }
        return $cache;
    }

    public static function id(): ?int
    {
        $uid = Session::get('uid');
        return $uid === null ? null : (int) $uid;
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && $u['role'] === 'admin';
    }

    /** Zugriff nur für eingeloggte Benutzer. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Bitte zuerst anmelden.');
            redirect('/login');
        }
        // Falls der Benutzer in der Zwischenzeit gelöscht/gesperrt wurde:
        $u = self::user();
        if ($u === null || (int) $u['is_active'] !== 1) {
            self::logout();
            redirect('/login');
        }
    }

    /** Zugriff nur für Admins. */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            exit('Kein Zugriff.');
        }
    }
}
