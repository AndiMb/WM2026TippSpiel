<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\MatchModel;

/**
 * Berechnet die Gruppentabellen der Gruppenphase aus den eigenen Spieldaten.
 *
 * Punkte: Sieg 3, Unentschieden 1, Niederlage 0.
 * Sortierung (vereinfacht, kindgerecht): Punkte > Tordifferenz > erzielte Tore
 * > Name. (Die offizielle FIFA-Tie-Break-Reihenfolge mit direktem Vergleich
 * usw. wird hier bewusst nicht nachgebildet.)
 */
final class GroupsService
{
    /**
     * @return array<string, array{name:string, rows:array}> indexiert nach group_name
     */
    public static function standings(): array
    {
        $groups = [];

        foreach (MatchModel::groupMatches() as $m) {
            $g = $m['group_name'];
            if (!isset($groups[$g])) {
                $groups[$g] = [];
            }
            // Beide Mannschaften als Tabellenzeile anlegen (auch ohne Ergebnis).
            foreach ([$m['team1'], $m['team2']] as $t) {
                if (!isset($groups[$g][$t])) {
                    $groups[$g][$t] = self::emptyRow($t);
                }
            }

            // Nur beendete Spiele zählen.
            if ($m['status'] !== 'finished' || $m['score1'] === null || $m['score2'] === null) {
                continue;
            }
            $s1 = (int) $m['score1'];
            $s2 = (int) $m['score2'];
            self::applyResult($groups[$g][$m['team1']], $s1, $s2);
            self::applyResult($groups[$g][$m['team2']], $s2, $s1);
        }

        // Sortieren und in Listen umwandeln.
        $out = [];
        foreach ($groups as $name => $rows) {
            $list = array_values($rows);
            usort($list, function ($a, $b) {
                return [$b['pts'], $b['gd'], $b['gf'], $a['team']]
                   <=> [$a['pts'], $a['gd'], $a['gf'], $b['team']];
            });
            // Platz vergeben
            foreach ($list as $i => &$r) {
                $r['pos'] = $i + 1;
            }
            unset($r);
            $out[$name] = ['name' => $name, 'rows' => $list];
        }

        // Gruppen alphabetisch (Group A, Group B, …)
        ksort($out);
        return $out;
    }

    /**
     * Aktuelle Rangliste der Gruppendritten (für die 8 Qualifikationsplätze
     * im Sechzehntelfinale). Liefert die Drittplatzierten aller Gruppen,
     * absteigend sortiert; die besten 8 sind über 'qualified' markiert.
     */
    public static function bestThirds(): array
    {
        $thirds = [];
        foreach (self::standings() as $g) {
            if (isset($g['rows'][2])) {           // Index 2 = 3. Platz
                $row = $g['rows'][2];
                $row['group'] = $g['name'];
                $thirds[] = $row;
            }
        }
        usort($thirds, function ($a, $b) {
            return [$b['pts'], $b['gd'], $b['gf'], $a['team']]
               <=> [$a['pts'], $a['gd'], $a['gf'], $b['team']];
        });
        foreach ($thirds as $i => &$t) {
            $t['qualified'] = $i < 8;
        }
        unset($t);
        return $thirds;
    }

    /** Liefert das Team an einer bestimmten Position einer Gruppe (oder null). */
    public static function teamAt(array $standings, string $groupLetter, int $position): ?string
    {
        $name = 'Group ' . $groupLetter;
        if (!isset($standings[$name]['rows'][$position - 1])) {
            return null;
        }
        return $standings[$name]['rows'][$position - 1]['team'];
    }

    private static function emptyRow(string $team): array
    {
        return ['team' => $team, 'p' => 0, 'w' => 0, 'd' => 0, 'l' => 0,
                'gf' => 0, 'ga' => 0, 'gd' => 0, 'pts' => 0];
    }

    private static function applyResult(array &$row, int $for, int $against): void
    {
        $row['p']++;
        $row['gf'] += $for;
        $row['ga'] += $against;
        $row['gd']  = $row['gf'] - $row['ga'];
        if ($for > $against) {
            $row['w']++; $row['pts'] += 3;
        } elseif ($for === $against) {
            $row['d']++; $row['pts'] += 1;
        } else {
            $row['l']++;
        }
    }
}
