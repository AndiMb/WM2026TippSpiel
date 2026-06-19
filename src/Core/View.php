<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimaler View-Renderer: bindet eine PHP-Vorlage in das gemeinsame
 * Layout ein. Keine Template-Sprache nötig – reines PHP mit e()-Escaping.
 */
final class View
{
    /**
     * Rendert eine View innerhalb des Layouts.
     *
     * @param string $template  Pfad relativ zu /views ohne .php (z.B. 'dashboard')
     * @param array  $data      Variablen für die View
     * @param string $title     Seitentitel
     */
    public static function render(string $template, array $data = [], string $title = ''): void
    {
        $viewsDir = \dirname(__DIR__, 2) . '/views';

        // Aktuellen Benutzer global für jede View bereitstellen (ohne explizit
        // gesetzte Variablen zu überschreiben).
        $data += ['user' => Auth::user(), 'isAdmin' => Auth::isAdmin()];

        // Inhalt der eigentlichen Seite in einen Puffer rendern.
        $content = self::capture($viewsDir . '/' . $template . '.php', $data);

        // Daten, die das Layout selbst benötigt.
        $layoutData = [
            'title'   => $title !== '' ? $title : (string) config('app.name'),
            'content' => $content,
            'flashes' => Session::takeFlashes(),
            'user'    => Auth::user(),
            'isAdmin' => Auth::isAdmin(),
            'active'  => $data['_active'] ?? '',
        ];

        echo self::capture($viewsDir . '/layout.php', $layoutData);
    }

    /** Rendert eine Vorlage ohne Layout (z.B. für JSON-freie Teilstücke). */
    public static function capture(string $file, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    /** JSON-Antwort senden. */
    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
