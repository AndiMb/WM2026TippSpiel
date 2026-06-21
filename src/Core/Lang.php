<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Sehr einfache Mehrsprachigkeit.
 *
 * Die aktuelle Sprache wird einmal pro Anfrage gesetzt (aus der Benutzer-
 * einstellung oder der Session). Übersetzungen liegen als PHP-Arrays in
 * src/Lang/<locale>.php. Fehlt ein Schlüssel in der gewählten Sprache, wird
 * auf Deutsch und zuletzt auf den Schlüssel selbst zurückgegriffen.
 */
final class Lang
{
    public const SUPPORTED = ['de', 'pt'];
    public const DEFAULT   = 'de';

    private static string $locale = self::DEFAULT;
    private static array $messages = [];
    private static array $fallback = [];

    public static function init(string $locale): void
    {
        $locale = self::normalize($locale);
        self::$locale = $locale;

        $dir = \dirname(__DIR__) . '/Lang';
        self::$messages = is_file("$dir/$locale.php") ? (require "$dir/$locale.php") : [];
        self::$fallback = $locale === self::DEFAULT
            ? self::$messages
            : (is_file("$dir/" . self::DEFAULT . '.php') ? (require "$dir/" . self::DEFAULT . '.php') : []);
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    /** Gültige Sprache erzwingen. */
    public static function normalize(string $locale): string
    {
        $locale = strtolower(substr(trim($locale), 0, 5));
        return in_array($locale, self::SUPPORTED, true) ? $locale : self::DEFAULT;
    }

    /**
     * Übersetzten Text holen. Platzhalter werden als :name ersetzt.
     * Beispiel: t('bets.saved', ['count' => 3])
     */
    public static function t(string $key, array $params = []): string
    {
        $text = self::$messages[$key] ?? self::$fallback[$key] ?? $key;
        if ($params) {
            foreach ($params as $k => $v) {
                $text = str_replace(':' . $k, (string) $v, $text);
            }
        }
        return $text;
    }
}
