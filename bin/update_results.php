<?php
declare(strict_types=1);

/**
 * Cron-Skript: Spielergebnisse aktualisieren und Tipps werten.
 *
 * Ruft die konfigurierte Online-Quelle ab, schreibt neue Endergebnisse und
 * berechnet die Punkte der betroffenen Tipps. Steht keine API zur Verfügung,
 * kann dieser Cronjob entfallen – der Admin trägt Ergebnisse dann manuell ein.
 *
 * Beispiel-Cronjob (während der WM alle 30 Minuten):
 *     siehe INSTALL.md, Abschnitt "Cronjobs" (Minuten-Intervall mit Slash-30).
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Services\ResultUpdater;

$started = date('c');
try {
    $stats = ResultUpdater::run();
    printf("[%s] Ergebnis-Update: %d neu, %d aktualisiert, %d gewertet.\n",
        $started, $stats['inserted'], $stats['updated'], $stats['scored']);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "[$started] FEHLER beim Ergebnis-Update: " . $e->getMessage() . "\n");
    exit(1);
}
