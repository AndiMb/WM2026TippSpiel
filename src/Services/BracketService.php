<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\MatchModel;

/**
 * Baut den Turnierbaum der KO-Phase und löst die Platzhalter der Quelle auf:
 *
 *   1A / 2B      -> 1./2. der Gruppe A bzw. B (aus dem AKTUELLEN Tabellenstand)
 *   3A/B/C/D/F   -> einer der besten Gruppendritten (nicht eindeutig -> Label)
 *   W73 / L101   -> Sieger/Verlierer von Spiel 73 bzw. 101 (echtes Team, sobald
 *                   das Spiel gespielt ist; sonst Label "Sieger Spiel 73")
 *
 * Für das Sechzehntelfinale wird – wie gewünscht – der aktuelle Gruppen-
 * Tabellenstand verwendet, um 1./2.-Platzierte projiziert anzuzeigen.
 */
final class BracketService
{
    /** Runden in Reihenfolge: Quelle-Name => deutscher Titel. */
    private const ROUNDS = [
        'Round of 32'           => 'Sechzehntelfinale',
        'Round of 16'           => 'Achtelfinale',
        'Quarter-final'         => 'Viertelfinale',
        'Semi-final'            => 'Halbfinale',
        'Match for third place' => 'Spiel um Platz 3',
        'Final'                 => 'Finale',
    ];

    public static function build(): array
    {
        $matches = MatchModel::knockoutMatches();
        if (!$matches) {
            return [];
        }

        // Index nach Spielnummer (für W##/L##-Auflösung).
        $byNum = [];
        foreach ($matches as $m) {
            if ($m['num'] !== null) {
                $byNum[(int) $m['num']] = $m;
            }
        }

        $standings = GroupsService::standings();

        // Runden in definierter Reihenfolge füllen.
        $rounds = [];
        foreach (self::ROUNDS as $src => $titleDe) {
            $rounds[$src] = ['title' => $titleDe, 'matches' => []];
        }
        foreach ($matches as $m) {
            $round = $m['round_name'];
            if (!isset($rounds[$round])) {
                // Unbekannte Rundenbezeichnung ans Ende hängen.
                $rounds[$round] = ['title' => $round ?: 'KO-Runde', 'matches' => []];
            }
            $rounds[$round]['matches'][] = [
                'num'     => $m['num'] !== null ? (int) $m['num'] : null,
                'team1'   => self::resolve($m['team1'], $standings, $byNum),
                'team2'   => self::resolve($m['team2'], $standings, $byNum),
                'score1'  => $m['score1'],
                'score2'  => $m['score2'],
                'status'  => $m['status'],
                'kickoff' => $m['kickoff'],
                'venue'   => $m['venue'],
            ];
        }

        // Leere Runden entfernen.
        return array_values(array_filter($rounds, fn($r) => !empty($r['matches'])));
    }

    /**
     * Löst einen Mannschafts-Platzhalter auf.
     *
     * @return array{type:string, en?:string, label?:string, proj?:bool}
     *   type 'team'  -> echtes/projiziertes Team (Feld 'en')
     *   type 'label' -> beschreibender Text (Feld 'label')
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
            $label = ($pos === 1 ? 'Sieger Gruppe ' : '2. Gruppe ') . $mm[2];
            return ['type' => 'label', 'label' => $label];
        }

        // 3A/B/C/D/F -> einer der besten Gruppendritten (nicht eindeutig)
        if (preg_match('#^3([A-L])(?:/[A-L])+$#', $token)) {
            $letters = substr($token, 1);
            return ['type' => 'label', 'label' => '3. aus Gruppe ' . $letters];
        }

        // W73 / L101 -> Sieger/Verlierer eines Spiels
        if (preg_match('/^([WL])(\d+)$/', $token, $mm)) {
            $isWinner = $mm[1] === 'W';
            $ref = (int) $mm[2];
            $team = self::winnerOrLoser($ref, $isWinner, $byNum);
            if ($team !== null) {
                return ['type' => 'team', 'en' => $team];
            }
            return ['type' => 'label',
                    'label' => ($isWinner ? 'Sieger Spiel ' : 'Verlierer Spiel ') . $ref];
        }

        // Sonst: echtes Team (bereits aufgelöst durch die Quelle).
        if (self::isRealTeam($token)) {
            return ['type' => 'team', 'en' => $token];
        }
        return ['type' => 'label', 'label' => $token];
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
            return null; // Unentschieden in KO -> Sieger steht hier nicht fest (Elfmeter)
        }
        $home = self::isRealTeam($m['team1']);
        $away = self::isRealTeam($m['team2']);
        if (!$home || !$away) {
            return null;
        }
        $homeWon = $s1 > $s2;
        return ($winner === $homeWon) ? $m['team1'] : $m['team2'];
    }

    /** Heuristik: ein echter Teamname ist KEIN Platzhalter-Code. */
    private static function isRealTeam(string $token): bool
    {
        // Platzhalter: 1A, 2B, 3A/B/.., W73, L101
        if (preg_match('#^[123][A-L]#', $token) || preg_match('/^[WL]\d+$/', $token)) {
            return false;
        }
        return $token !== '';
    }
}
