<?php
declare(strict_types=1);

/**
 * Seed-Skript: legt Standard-Einstellungen, einen Admin-Account und
 * (optional) Beispiel-Bonusfragen an.
 *
 * Aufruf:
 *     php database/seed.php
 *
 * Optional mit eigenem Admin-Passwort:
 *     php database/seed.php meinPasswort123
 *
 * Das Skript ist idempotent: vorhandene Einträge werden nicht doppelt angelegt.
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database as DB;
use App\Models\Setting;
use App\Models\User;

$adminUser = 'admin';
$adminPass = $argv[1] ?? 'admin123';     // Bitte nach dem ersten Login ändern!
$adminName = 'Administrator';

echo "Seeding ...\n";

// 1) Standard-Einstellungen setzen (nur falls noch nicht vorhanden).
foreach (Setting::DEFAULTS as $key => $val) {
    if (DB::scalar('SELECT 1 FROM settings WHERE skey = ?', [$key]) === false) {
        Setting::set($key, $val);
        echo "  + Einstellung: $key = $val\n";
    }
}

// 2) Admin-Benutzer anlegen.
if (!User::usernameExists($adminUser)) {
    User::create($adminUser, $adminPass, $adminName, 'admin');
    echo "  + Admin angelegt:  Benutzer '$adminUser'  Passwort '$adminPass'\n";
    echo "    >>> BITTE DAS PASSWORT NACH DEM ERSTEN LOGIN ÄNDERN! <<<\n";
} else {
    echo "  = Admin '$adminUser' existiert bereits.\n";
}

// 3) Beispiel-Bonusfragen (deaktiviert, bis Admin Bonus aktiviert).
$haveBonus = (int) DB::scalar('SELECT COUNT(*) FROM bonus_questions');
if ($haveBonus === 0) {
    DB::insert(
        "INSERT INTO bonus_questions (qtype, question, points, is_active, created_at)
         VALUES ('champion', 'Wer wird Weltmeister 2026?', 10, 1, ?)", [DB::now()]);
    DB::insert(
        "INSERT INTO bonus_questions (qtype, question, points, is_active, created_at)
         VALUES ('topscorer', 'Wer wird Torschützenkönig?', 5, 1, ?)", [DB::now()]);
    echo "  + 2 Beispiel-Bonusfragen angelegt (Bonus ist standardmäßig deaktiviert).\n";
}

echo "✓ Seed abgeschlossen.\n";
echo "Nächster Schritt: Spielplan importieren (Adminbereich) oder\n";
echo "   php bin/import_schedule.php\n";
