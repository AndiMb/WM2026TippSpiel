<?php
declare(strict_types=1);

/**
 * Bootstrap: lädt Konfiguration, registriert den Autoloader, startet die
 * Session und stellt die Datenbankverbindung her. Wird von public/index.php
 * und von den CLI-Skripten in /bin eingebunden.
 */

error_reporting(E_ALL);

// 1) Konfiguration laden ------------------------------------------------
$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    exit('Konfiguration fehlt. Bitte config/config.example.php nach config/config.php kopieren.');
}
$GLOBALS['app_config'] = require $configFile;

// 2) Einfacher PSR-4-Autoloader für den Namespace "App\" ----------------
spl_autoload_register(function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = __DIR__ . '/';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// 3) Hilfsfunktionen -----------------------------------------------------
require __DIR__ . '/helpers.php';

// 4) Fehleranzeige je nach Umgebung -------------------------------------
ini_set('display_errors', config('app.https_only') ? '0' : '1');

// 5) Zeitzone für interne Berechnungen auf UTC fixieren -----------------
date_default_timezone_set('UTC');

// 6) Datenbank initialisieren -------------------------------------------
App\Core\Database::init(config());
