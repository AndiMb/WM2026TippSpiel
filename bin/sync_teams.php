<?php
declare(strict_types=1);

/**
 * Aktualisiert die Mannschafts-Stammdaten (deutsche Namen + FIFA-Rang) aus
 * src/Data/teams.php in die Tabelle `teams`.
 *
 * Nützlich nach dem Bearbeiten der Datei (z. B. neue FIFA-Rangliste):
 *     php bin/sync_teams.php
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Services\TeamService;

try {
    $n = TeamService::syncFromData();
    echo "✓ $n Mannschaften synchronisiert.\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Fehler: ' . $e->getMessage() . "\n");
    exit(1);
}
