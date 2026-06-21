<?php
declare(strict_types=1);

use App\Core\Csrf;

/**
 * Globale Hilfsfunktionen, die in Controllern und Views genutzt werden.
 */

/** Zugriff auf die geladene Konfiguration. */
function config(?string $key = null)
{
    /** @var array $GLOBALS['app_config'] */
    $cfg = $GLOBALS['app_config'] ?? [];
    if ($key === null) {
        return $cfg;
    }
    // Punkt-Notation: 'app.name'
    $parts = explode('.', $key);
    $val = $cfg;
    foreach ($parts as $p) {
        if (!is_array($val) || !array_key_exists($p, $val)) {
            return null;
        }
        $val = $val[$p];
    }
    return $val;
}

/** Erzeugt eine vollständige URL unter Berücksichtigung des base_path. */
function url(string $path = '/'): string
{
    $base = rtrim((string) config('app.base_path'), '/');
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . $path;
    }
    return $base . $path;
}

/** HTML-Escaping (immer für Ausgabe von Benutzerdaten verwenden!). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** HTTP-Redirect auf einen internen Pfad. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Verstecktes CSRF-Feld für Formulare. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/**
 * Wandelt einen in UTC gespeicherten Zeitstempel in die Anzeige-Zeitzone um.
 */
function fmt_datetime(?string $utc, string $format = 'D, d.m.Y H:i'): string
{
    if (!$utc) {
        return '';
    }
    static $days = [
        'Mon' => 'Mo', 'Tue' => 'Di', 'Wed' => 'Mi', 'Thu' => 'Do',
        'Fri' => 'Fr', 'Sat' => 'Sa', 'Sun' => 'So',
    ];
    try {
        $dt = new DateTime($utc, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone((string) config('app.timezone')));
        return strtr($dt->format($format), $days);
    } catch (Exception $e) {
        return $utc;
    }
}

/** Ist der Anstoß bereits vorbei (Tippschluss erreicht)? */
function is_past(string $utcKickoff): bool
{
    return strtotime($utcKickoff . ' UTC') <= time();
}

/** Übersetzten Text holen (siehe src/Lang). */
function t(string $key, array $params = []): string
{
    return \App\Core\Lang::t($key, $params);
}

/** Anzeigename einer Mannschaft in der aktuellen Sprache (Fallback: Original). */
function tname(?string $en): string
{
    return $en === null ? '' : \App\Services\TeamService::name($en);
}

/**
 * Flaggen-Grafik einer Mannschaft als <img>-Tag (oder leer, wenn unbekannt).
 * Die Flagge ist rein dekorativ (der Name steht daneben) -> alt="" + aria-hidden.
 */
function flag(?string $en): string
{
    if ($en === null) {
        return '';
    }
    $iso = \App\Services\TeamService::iso2($en);
    if ($iso === null) {
        return '';
    }
    $src = url('/assets/img/flags/' . $iso . '.svg');
    return '<img class="flag" src="' . e($src) . '" alt="" aria-hidden="true" width="20" height="15" loading="lazy">';
}
