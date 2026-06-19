<?php
declare(strict_types=1);

namespace App\Core;

/**
 * CSRF-Schutz über ein Session-gebundenes Token.
 * Jedes Formular bindet das Token via csrf_field() ein; POST-Anfragen
 * werden in der Router-/Controller-Schicht mit verify() geprüft.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /** Konstant-zeitiger Vergleich gegen das Session-Token. */
    public static function verify(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }
}
