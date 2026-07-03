<?php
declare(strict_types=1);

/**
 * Cron-Skript: Live-Zwischenstände von football-data.org holen.
 *
 * Setzt bei laufenden Spielen status='live' + aktuellen Spielstand und
 * schließt frisch beendete Spiele sofort ab (inkl. Tipp-Wertung) – Minuten
 * statt Stunden nach Abpfiff. Ohne konfigurierten API-Key
 * (config: import.footballdata_token) ist das Skript ein No-Op.
 *
 * Beispiel-Cronjob (jede Minute; außerhalb von Spielzeiten macht das Skript
 * dank Zeitfenster-Prüfung keinen API-Aufruf):
 *     * * * * *  php /var/www/tippspiel/bin/update_live.php >> /var/log/tippspiel_live.log 2>&1
 *
 * Hinweis: Auch ohne Cronjob funktionieren Live-Stände – der /live-Endpunkt
 * stößt die Aktualisierung (gedrosselt) bei Seitenaufrufen an. Der Cronjob
 * macht sie nur unabhängig davon, ob gerade jemand die Seite offen hat.
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Services\LiveScoreService;

$started = date('c');
try {
    $stats = LiveScoreService::update(true);
    printf("[%s] Live-Update: %d geprüft, %d live, %d abgeschlossen%s\n",
        $started, $stats['checked'], $stats['live'], $stats['finished'],
        isset($stats['note']) ? ' (' . $stats['note'] . ')' : '');
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "[$started] FEHLER beim Live-Update: " . $e->getMessage() . "\n");
    exit(1);
}
