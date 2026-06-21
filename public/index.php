<?php
declare(strict_types=1);

/**
 * Front-Controller. Apache-DocumentRoot zeigt auf dieses Verzeichnis (public/).
 * Alle Anfragen landen hier (siehe .htaccess) und werden vom Router verteilt.
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Router;
use App\Core\Session;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\BetController;
use App\Controllers\StandingsController;
use App\Controllers\TipsController;
use App\Controllers\AccountController;
use App\Controllers\LegalController;
use App\Controllers\Admin\AdminController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\MatchController;
use App\Controllers\Admin\BonusController;
use App\Controllers\Admin\SettingsController;

Session::start(config());

$router = new Router();

// --- Öffentlich --------------------------------------------------------
$router->get('/login',  [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout',[AuthController::class, 'logout']);

// Rechtstexte (ohne Login erreichbar)
$router->get('/impressum',   [LegalController::class, 'impressum']);
$router->get('/datenschutz', [LegalController::class, 'datenschutz']);

// --- Spieler -----------------------------------------------------------
$router->get('/',          [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

$router->get('/tippen',    [BetController::class, 'index']);
$router->post('/tippen',   [BetController::class, 'save']);

$router->get('/rangliste', [StandingsController::class, 'index']);
$router->get('/tipps',     [TipsController::class, 'index']);

$router->get('/konto',         [AccountController::class, 'index']);
$router->post('/konto/passwort',[AccountController::class, 'changePassword']);
$router->post('/konto/bonus',  [AccountController::class, 'saveBonus']);

// --- Admin -------------------------------------------------------------
$router->get('/admin',                 [AdminController::class, 'index']);

$router->get('/admin/benutzer',        [UserController::class, 'index']);
$router->post('/admin/benutzer',       [UserController::class, 'create']);
$router->post('/admin/benutzer/{id}',  [UserController::class, 'update']);
$router->post('/admin/benutzer/{id}/loeschen', [UserController::class, 'delete']);
$router->post('/admin/benutzer/{id}/passwort', [UserController::class, 'resetPassword']);

$router->get('/admin/spiele',          [MatchController::class, 'index']);
$router->post('/admin/spiele/import',  [MatchController::class, 'import']);
$router->post('/admin/spiele/{id}/ergebnis', [MatchController::class, 'saveResult']);
$router->post('/admin/spiele/neuberechnen',  [MatchController::class, 'recalculate']);

$router->get('/admin/bonus',           [BonusController::class, 'index']);
$router->post('/admin/bonus',          [BonusController::class, 'create']);
$router->post('/admin/bonus/{id}',     [BonusController::class, 'update']);
$router->post('/admin/bonus/{id}/aufloesen', [BonusController::class, 'resolve']);
$router->post('/admin/bonus/{id}/loeschen',  [BonusController::class, 'delete']);

$router->get('/admin/einstellungen',   [SettingsController::class, 'index']);
$router->post('/admin/einstellungen',  [SettingsController::class, 'save']);

$router->dispatch();
