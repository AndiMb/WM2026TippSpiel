<?php
declare(strict_types=1);

/**
 * Cron-Skript: Spielplan importieren / aktualisieren.
 *
 * Holt den kompletten Spielplan (inkl. bereits gespielter Ergebnisse) aus der
 * konfigurierten Online-Quelle (OpenFootball) und legt neue Spiele an bzw.
 * aktualisiert bestehende.
 *
 * Beispiel-Cronjob (einmal täglich um 03:00 Uhr):
 *     0 3 * * *  php /var/www/tippspiel/bin/import_schedule.php >> /var/log/tippspiel_import.log 2>&1
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Services\ScheduleImporter;

$started = date('c');
try {
    $url   = (string) config('import.openfootball_url');
    $stats = ScheduleImporter::importOpenFootball($url);
    printf("[%s] Spielplan-Import: %d neu, %d aktualisiert, %d gewertet.\n",
        $started, $stats['inserted'], $stats['updated'], $stats['scored']);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "[$started] FEHLER beim Spielplan-Import: " . $e->getMessage() . "\n");
    exit(1);
}
