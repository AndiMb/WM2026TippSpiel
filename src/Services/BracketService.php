<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\MatchModel;

/**
 * Baut den Turnierbaum der KO-Phase als echten Baum und löst die Platzhalter
 * der Quelle auf:
 *
 *   1A / 2B      -> 1./2. der Gruppe A bzw. B (aus dem AKTUELLEN Tabellenstand)
 *   3A/B/C/D/F   -> einer der besten Gruppendritten (nicht eindeutig -> Label)
 *   W73 / L101   -> Sieger/Verlierer von Spiel 73 bzw. 101 (echtes Team, sobald
 *                   das Spiel gespielt ist; sonst Label)
 *
 * Zusätzlich wird die Baumstruktur berechnet:
 *   - die Spiele jeder Runde werden so sortiert, dass zusammengehörende Paare
 *     untereinander stehen (für eine erkennbare Baumdarstellung)
 *   - je Spiel wird vermerkt, in welches Folgespiel der Sieger kommt
 *     ('advances_to'), damit man dem Pfad folgen kann.
 *
 * Die Beschriftungen werden NICHT hier übersetzt, sondern als strukturierte
 * Angaben zurückgegeben und in der View sprachabhängig formatiert.
 */
final class BracketService
{
    /** Runden in Reihenfolge (Quelle-Bezeichnung). Anzeige via t('round.<name>'). */
    private const ROUND_ORDER = [
        'Round of 32', 'Round of 16', 'Quarter-final',
        'Semi-final', 'Match for third place', 'Final',
    ];

    public static function build(): array
    {
        $matches = MatchModel::knockoutMatches();
        if (!$matches) {
            return [];
        }

        // Index nach Spielnummer.
        $byNum = [];
        foreach ($matches as $m) {
            if ($m['num'] !== null) {
                $byNum[(int) $m['num']] = $m;
            }
        }

        // Sieger-Folgespiel je Spielnummer ermitteln: wohin (Spielnummer) und in
        // welchen Slot (1 oder 2) der Sieger kommt – für Linien & Simulation.
        $advancesTo = [];
        foreach ($matches as $m) {
            if ($m['num'] === null) {
                continue;
            }
            $slotIdx = 1;
            foreach ([$m['team1'], $m['team2']] as $slot) {
                if (preg_match('/^W(\d+)$/', (string) $slot, $mm)) {
                    $advancesTo[(int) $mm[1]] = ['to' => (int) $m['num'], 'slot' => $slotIdx];
                }
                $slotIdx++;
            }
        }

        // Baum-Reihenfolge berechnen (DFS ab dem Finale).
        $order = [];
        $finalNum = self::roundFirstNum($matches, 'Final');
        if ($finalNum !== null) {
            $leaf = 0;
            self::placeInTree($finalNum, $byNum, $order, $leaf);
        }

        $standings = GroupsService::standings();

        // Runden aufbauen.
        $rounds = [];
        foreach (self::ROUND_ORDER as $src) {
            $rounds[$src] = ['name' => $src, 'matches' => []];
        }
        foreach ($matches as $m) {
            $round = $m['round_name'];
            if (!isset($rounds[$round])) {
                $rounds[$round] = ['name' => $round ?: 'KO', 'matches' => []];
            }
            $num = $m['num'] !== null ? (int) $m['num'] : null;

            // Welcher Slot wird aus welchem Vorspiel gespeist (W##)?
            $feed1 = preg_match('/^W(\d+)$/', (string) $m['team1'], $x1) ? (int) $x1[1] : null;
            $feed2 = preg_match('/^W(\d+)$/', (string) $m['team2'], $x2) ? (int) $x2[1] : null;

            $t1 = self::resolve($m['team1'], $standings, $byNum); $t1['feedFrom'] = $feed1;
            $t2 = self::resolve($m['team2'], $standings, $byNum); $t2['feedFrom'] = $feed2;

            $feeds = $num !== null ? ($advancesTo[$num] ?? null) : null;
            $rounds[$round]['matches'][] = [
                'num'         => $num,
                'team1'       => $t1,
                'team2'       => $t2,
                'score1'      => $m['score1'],
                'score2'      => $m['score2'],
                'status'      => $m['status'],
                'kickoff'     => $m['kickoff'],
                'feeds'       => $feeds,                              // ['to'=>num,'slot'=>1|2]
                'advances_to' => $feeds['to'] ?? null,
                '_order'      => $num !== null ? ($order[$num] ?? PHP_INT_MAX) : PHP_INT_MAX,
            ];
        }

        // Spiele je Runde in Baum-Reihenfolge sortieren.
        foreach ($rounds as &$r) {
            usort($r['matches'], fn($a, $b) => $a['_order'] <=> $b['_order']);
        }
        unset($r);

        return array_values(array_filter($rounds, fn($r) => !empty($r['matches'])));
    }

    /** Erste Spielnummer einer Runde. */
    private static function roundFirstNum(array $matches, string $round): ?int
    {
        foreach ($matches as $m) {
            if ($m['round_name'] === $round && $m['num'] !== null) {
                return (int) $m['num'];
            }
        }
        return null;
    }

    /**
     * Ordnet die Spiele rekursiv im Baum an. Blätter (Sechzehntelfinale)
     * bekommen fortlaufende Werte, innere Knoten den Mittelwert ihrer Kinder –
     * so stehen zusammengehörende Paare zentriert untereinander.
     */
    private static function placeInTree(int $num, array $byNum, array &$order, int &$leaf): float
    {
        $m = $byNum[$num] ?? null;
        if (!$m) {
            return $order[$num] = $leaf++;
        }
        // Kinder = Spiele, auf die sich W##-Platzhalter beziehen.
        $children = [];
        foreach ([$m['team1'], $m['team2']] as $slot) {
            if (preg_match('/^W(\d+)$/', (string) $slot, $mm) && isset($byNum[(int) $mm[1]])) {
                $children[] = (int) $mm[1];
            }
        }
        if (!$children) {
            return $order[$num] = $leaf++;
        }
        $vals = [];
        foreach ($children as $c) {
            $vals[] = self::placeInTree($c, $byNum, $order, $leaf);
        }
        return $order[$num] = (min($vals) + max($vals)) / 2;
    }

    /**
     * Löst einen Mannschafts-Platzhalter auf.
     *
     * @return array{type:string, en?:string, proj?:bool, kind?:string, grp?:string, grps?:string, num?:int, label?:string}
     */
    private static function resolve(string $token, array $standings, array $byNum): array
    {
        $token = trim($token);

        // 1A / 2B  -> Tabellenstand der Gruppe (projiziert)
        if (preg_match('/^([12])([A-L])$/', $token, $mm)) {
            $pos = (int) $mm[1];
            $team = GroupsService::teamAt($standings, $mm[2], $pos);
            if ($team !== null && self::isRealTeam($team)) {
                return ['type' => 'team', 'en' => $team, 'proj' => true];
            }
            return ['type' => 'label', 'kind' => $pos === 1 ? 'group_winner' : 'group_second', 'grp' => $mm[2]];
        }

        // 3A/B/C/D/F -> einer der besten Gruppendritten (nicht eindeutig)
        if (preg_match('#^3([A-L])(?:/[A-L])+$#', $token)) {
            return ['type' => 'label', 'kind' => 'group_third', 'grps' => substr($token, 1)];
        }

        // W73 / L101 -> Sieger/Verlierer eines Spiels
        if (preg_match('/^([WL])(\d+)$/', $token, $mm)) {
            $isWinner = $mm[1] === 'W';
            $ref = (int) $mm[2];
            $team = self::winnerOrLoser($ref, $isWinner, $byNum);
            if ($team !== null) {
                return ['type' => 'team', 'en' => $team];
            }
            // Noch nicht entschieden -> "offener" Slot (wird per Simulation gefüllt).
            return ['type' => 'open', 'kind' => $isWinner ? 'winner_of' : 'loser_of', 'num' => $ref];
        }

        // Sonst: echtes Team (bereits aufgelöst durch die Quelle).
        if (self::isRealTeam($token)) {
            return ['type' => 'team', 'en' => $token];
        }
        return ['type' => 'label', 'kind' => 'raw', 'label' => $token];
    }

    /** Sieger/Verlierer eines (beendeten) Spiels als echtes Team, sonst null. */
    private static function winnerOrLoser(int $num, bool $winner, array $byNum): ?string
    {
        $m = $byNum[$num] ?? null;
        if (!$m || $m['status'] !== 'finished' || $m['score1'] === null || $m['score2'] === null) {
            return null;
        }
        $s1 = (int) $m['score1'];
        $s2 = (int) $m['score2'];
        if ($s1 === $s2) {
            return null; // Unentschieden -> Sieger steht hier nicht fest (Elfmeter)
        }
        if (!self::isRealTeam($m['team1']) || !self::isRealTeam($m['team2'])) {
            return null;
        }
        $homeWon = $s1 > $s2;
        return ($winner === $homeWon) ? $m['team1'] : $m['team2'];
    }

    /** Heuristik: ein echter Teamname ist KEIN Platzhalter-Code. */
    private static function isRealTeam(string $token): bool
    {
        if (preg_match('#^[123][A-L]#', $token) || preg_match('/^[WL]\d+$/', $token)) {
            return false;
        }
        return $token !== '';
    }
}
