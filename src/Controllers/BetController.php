<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Models\Bet;
use App\Models\MatchModel;
use App\Services\TeamService;

final class BetController
{
    public function index(): void
    {
        Auth::requireLogin();
        $uid = Auth::id();

        // Nur zukünftige, noch nicht angepfiffene Spiele sind tippbar.
        $matches = MatchModel::openForBets();
        $betMap  = Bet::mapForUser($uid);

        View::render('bets', [
            '_active' => 'tippen',
            'matches' => $matches,
            'betMap'  => $betMap,
            'forms'   => TeamService::lastResultsMap(), // letztes Ergebnis je Team
        ], 'Spiele tippen');
    }

    public function save(): void
    {
        Auth::requireLogin();
        $uid = Auth::id();

        // Erwartet Arrays: pred1[matchId], pred2[matchId]
        $pred1 = $_POST['pred1'] ?? [];
        $pred2 = $_POST['pred2'] ?? [];

        if (!is_array($pred1) || !is_array($pred2)) {
            Session::flash('error', 'Ungültige Eingabe.');
            redirect('/tippen');
        }

        $saved = 0;
        foreach ($pred1 as $matchId => $h) {
            $matchId = (int) $matchId;
            $a = $pred2[$matchId] ?? '';

            // Leere Felder = kein Tipp für dieses Spiel, überspringen.
            if ($h === '' || $a === '') {
                continue;
            }

            // Eingabe-Validierung: ganze Zahlen 0..99.
            if (!ctype_digit((string) $h) || !ctype_digit((string) $a)) {
                continue;
            }
            $h = (int) $h; $a = (int) $a;
            if ($h > 99 || $a > 99) {
                continue;
            }

            // Spiel prüfen: existiert und noch nicht angepfiffen?
            $match = MatchModel::find($matchId);
            if (!$match || $match['status'] !== 'scheduled' || is_past($match['kickoff'])) {
                continue; // Tippschluss bereits erreicht -> ignorieren
            }

            Bet::save($uid, $matchId, $h, $a);
            $saved++;
        }

        Session::flash('success', $saved > 0
            ? "$saved Tipp(s) gespeichert. Viel Glück!"
            : 'Keine Tipps gespeichert.');
        redirect('/tippen');
    }
}
