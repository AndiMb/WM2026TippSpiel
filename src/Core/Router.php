<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Sehr schlanker Router.
 *  - Routen werden mit Methode + Pfadmuster registriert.
 *  - Dynamische Segmente in geschweiften Klammern: /admin/users/{id}/delete
 *  - Bei POST-Routen wird automatisch das CSRF-Token geprüft.
 */
final class Router
{
    /** @var array<int, array{method:string, regex:string, vars:array, handler:callable|array}> */
    private array $routes = [];

    public function get(string $path, $handler): void  { $this->add('GET', $path, $handler); }
    public function post(string $path, $handler): void { $this->add('POST', $path, $handler); }

    private function add(string $method, string $path, $handler): void
    {
        // {name} -> benannte Capture-Gruppe
        $vars = [];
        $regex = preg_replace_callback('#\{(\w+)\}#', function ($m) use (&$vars) {
            $vars[] = $m[1];
            return '([^/]+)';
        }, $path);

        $this->routes[] = [
            'method'  => $method,
            'regex'   => '#^' . $regex . '$#',
            'vars'    => $vars,
            'handler' => $handler,
        ];
    }

    /** Aktuellen Pfad ermitteln (ohne base_path und Query-String). */
    private function currentPath(): string
    {
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $base = rtrim((string) config('app.base_path'), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        return $path === '' ? '/' : (rtrim($path, '/') ?: '/');
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path   = $this->currentPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            // CSRF-Prüfung für alle ändernden Anfragen.
            if ($method === 'POST' && !Csrf::verify($_POST['_csrf'] ?? null)) {
                http_response_code(419);
                exit('Sicherheitstoken ungültig oder abgelaufen. Bitte Seite neu laden.');
            }

            array_shift($matches); // vollständiger Treffer entfernen
            $params = array_combine($route['vars'], $matches) ?: [];

            $this->call($route['handler'], $params);
            return;
        }

        http_response_code(404);
        View::render('error', ['code' => 404, 'message' => 'Seite nicht gefunden.'], 'Nicht gefunden');
    }

    private function call($handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $methodName] = $handler;
            $instance = new $class();
            $instance->$methodName($params);
            return;
        }
        $handler($params);
    }
}
