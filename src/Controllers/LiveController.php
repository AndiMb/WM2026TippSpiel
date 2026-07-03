<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\MatchModel;
use App\Services\LiveScoreService;

/**
 * Kleiner JSON-Endpunkt für Live-Zwischenstände.
 *
 * Wird vom Frontend (app.js) etwa einmal pro Minute abgefragt, solange auf
 * der Seite ein laufendes Spiel angezeigt wird. Der Aufruf stößt nebenbei –
 * intern auf ~1x/Minute gedrosselt – die Aktualisierung über football-data.org
 * an ("fauler" Abruf). So funktionieren Live-Stände auch ganz ohne Cronjob;
 * ein Cronjob (bin/update_live.php) macht sie nur unabhängig von Seitenaufrufen.
 */
final class LiveController
{
    public function index(): void
    {
        Auth::requireLogin();

        try {
            LiveScoreService::update();
        } catch (\Throwable $e) {
            // Live-Quelle gestört -> trotzdem den letzten Datenbankstand liefern.
        }

        $out = [];
        foreach (MatchModel::liveOrRecent() as $m) {
            $decided = ko_decider($m);
            $out[] = [
                'id'      => (int) $m['id'],
                's1'      => $m['score1'] !== null ? (int) $m['score1'] : null,
                's2'      => $m['score2'] !== null ? (int) $m['score2'] : null,
                'status'  => $m['status'],
                // Zusatz "3:4 i.E." / "2:1 n.V." für erst nach 90 Minuten
                // entschiedene KO-Spiele (nur bei beendeten Spielen gesetzt).
                'decided' => $decided === null ? null : trim(strip_tags(ko_decided_badge($m))),
            ];
        }

        View::json([
            'matches'        => $out,
            'finished_label' => t('live.finished'),
            'ts'             => time(),
        ]);
    }
}
