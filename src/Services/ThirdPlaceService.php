<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Ordnet die acht besten Gruppendritten ihren Sechzehntelfinal-Spielen zu.
 *
 * Welcher Gruppendritte in welches der acht Spiele kommt, ist NICHT frei
 * waehlbar, sondern haengt davon ab, welche acht der zwoelf Gruppen ihren
 * Dritten qualifizieren. FIFA legt dafuer eine feste Tabelle fest (Annex C der
 * Turnierordnung, 495 Kombinationen) – siehe src/Data/third_place_allocation.php.
 *
 * Solange die Gruppenphase laeuft, wird der aktuelle Tabellenstand verwendet
 * (Projektion, in der Anzeige mit „(voraussichtlich)" markiert) – analog zu den
 * Platzhaltern 1A/2B.
 */
final class ThirdPlaceService
{
    /** @var array<string, array<int,string>>|null */
    private static ?array $table = null;

    /**
     * Liefert die Zuordnung Spielnummer => Gruppenbuchstabe des Dritten.
     * Leeres Array, wenn (noch) keine acht Dritten feststehen.
     *
     * @param array $standings Ergebnis von GroupsService::standings()
     * @return array<int,string>
     */
    public static function mapping(array $standings): array
    {
        // Drittplatzierte aller Gruppen einsammeln.
        $thirds = [];
        foreach ($standings as $g) {
            if (!isset($g['rows'][2])) {
                continue;                        // Gruppe hat (noch) keinen 3. Platz
            }
            $row = $g['rows'][2];
            $row['letter'] = trim(str_replace('Group', '', $g['name']));
            $thirds[] = $row;
        }
        if (count($thirds) < 8) {
            return [];
        }

        // Beste acht Dritte bestimmen (gleiche Sortierung wie die Rangliste der
        // Gruppendritten: Punkte > Tordifferenz > Tore > Name).
        usort($thirds, function ($a, $b) {
            return [$b['pts'], $b['gd'], $b['gf'], $a['team']]
               <=> [$a['pts'], $a['gd'], $a['gf'], $b['team']];
        });
        $letters = array_map(fn($t) => $t['letter'], array_slice($thirds, 0, 8));
        sort($letters);
        $key = implode('', $letters);

        return self::table()[$key] ?? [];
    }

    /** @return array<string, array<int,string>> */
    private static function table(): array
    {
        if (self::$table === null) {
            self::$table = require __DIR__ . '/../Data/third_place_allocation.php';
        }
        return self::$table;
    }
}
