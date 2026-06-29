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

/**
 * Ermittelt, ob ein KO-Spiel erst in der Verlängerung oder im Elfmeterschießen
 * entschieden wurde – für den Hinweis „n.V." / „i.E." neben dem Ergebnis.
 *
 * Erwartet eine Spiel-Zeile mit score1/score2 (90 Min.) sowie optional
 * et1/et2 (Verlängerung) und pen1/pen2 (Elfmeter).
 *
 * @return 'et'|'pen'|null  'et' = nach Verlängerung, 'pen' = im Elfmeterschießen
 */
function ko_decider(array $m): ?string
{
    $s1 = $m['score1'] ?? null;
    $s2 = $m['score2'] ?? null;
    if ($s1 === null || $s2 === null || (int) $s1 !== (int) $s2) {
        return null; // kein Ergebnis oder bereits nach 90 Minuten entschieden
    }
    $et1 = $m['et1'] ?? null;
    $et2 = $m['et2'] ?? null;
    if ($et1 !== null && $et2 !== null && (int) $et1 !== (int) $et2) {
        return 'et';
    }
    $p1 = $m['pen1'] ?? null;
    $p2 = $m['pen2'] ?? null;
    if ($p1 !== null && $p2 !== null && (int) $p1 !== (int) $p2) {
        return 'pen';
    }
    return null;
}

/**
 * Kleines Badge „n.V." / „i.E." (mit Tooltip) für ein KO-Spiel; leer, wenn das
 * Spiel regulär entschieden wurde.
 */
function ko_decided_badge(array $m): string
{
    $dec = ko_decider($m);
    if ($dec === null) {
        return '';
    }
    $title = t('match.' . $dec . '_full');
    if ($dec === 'pen' && isset($m['pen1'], $m['pen2'])) {
        $title .= ' ' . (int) $m['pen1'] . ':' . (int) $m['pen2'];
    }
    return ' <span class="decided decided-' . $dec . '" title="' . e($title) . '">'
        . e(t('match.' . $dec)) . '</span>';
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
