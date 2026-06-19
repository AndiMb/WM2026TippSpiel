<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Aktualisiert Spielergebnisse aus der konfigurierten Online-Quelle.
 *
 * Da die OpenFootball-Datei Spielplan und Ergebnisse zusammen enthält, ist
 * das Aktualisieren der Ergebnisse identisch mit einem erneuten Import:
 * vorhandene Spiele werden aktualisiert, neue Ergebnisse gewertet.
 *
 * Diese Klasse existiert als eigener Einstiegspunkt für den Ergebnis-Cronjob.
 * Steht keine API zur Verfügung, trägt der Admin Ergebnisse manuell ein
 * (siehe Admin -> Ergebnisse); beide Wege rufen am Ende ScoringService auf.
 */
final class ResultUpdater
{
    /** Ergebnisse aus der primären Online-Quelle aktualisieren. */
    public static function run(): array
    {
        $url = (string) config('import.openfootball_url');
        if ($url === '') {
            throw new \RuntimeException('Keine Online-Quelle konfiguriert.');
        }
        return ScheduleImporter::importOpenFootball($url);
    }
}
