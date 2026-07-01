<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Models\Bet;
use App\Models\MatchModel;
use App\Models\User;
use App\Services\ScoringService;

/**
 * Admin: Tipps einzelner Spieler nachtragen.
 *
 * Erlaubt es, für einen ausgewählten Spieler verspätet Tipps zu ergänzen oder
 * zu korrigieren – auch für Spiele, deren Anpfiff (Tippschluss) bereits vorbei
 * ist. Für bereits beendete Spiele werden die Punkte sofort neu berechnet.
 */
final class BetAdminController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $users    = User::allPlayers();
        $userId   = (int) ($_GET['user'] ?? 0);
        $selected = $userId > 0 ? User::find($userId) : null;

        // Standardansicht: nur Spiele ohne Tipp ("ergänzen"). Umschaltbar auf alle.
        $filter = ($_GET['filter'] ?? 'fehlend') === 'alle' ? 'alle' : 'fehlend';

        $matches = [];
        $betMap  = [];
        $missingCount = 0;
        if ($selected) {
            $betMap = Bet::mapForUser((int) $selected['id']);
            foreach (MatchModel::all() as $m) {
                if (!isset($betMap[(int) $m['id']])) {
                    $missingCount++;
                }
            }
            $matches = MatchModel::all();
        }

        View::render('admin/bets', [
            '_active'      => 'admin',
            'users'        => $users,
            'selected'     => $selected,
            'matches'      => $matches,
            'betMap'       => $betMap,
            'filter'       => $filter,
            'missingCount' => $missingCount,
        ], 'Tipps nachtragen');
    }

    public function save(): void
    {
        Auth::requireAdmin();

        $userId = (int) ($_POST['user_id'] ?? 0);
        $user   = $userId > 0 ? User::find($userId) : null;
        if (!$user) {
            Session::flash('error', 'Bitte zuerst einen Spieler auswählen.');
            redirect('/admin/tipps');
        }

        $filter = ($_POST['filter'] ?? 'fehlend') === 'alle' ? 'alle' : 'fehlend';

        $pred1 = $_POST['pred1'] ?? [];
        $pred2 = $_POST['pred2'] ?? [];
        if (!is_array($pred1) || !is_array($pred2)) {
            Session::flash('error', 'Ungültige Eingabe.');
            redirect('/admin/tipps?user=' . $userId . '&filter=' . $filter);
        }

        $saved   = 0;
        $toScore = [];
        foreach ($pred1 as $matchId => $h) {
            $matchId = (int) $matchId;
            $a = $pred2[$matchId] ?? '';

            // Beide leer -> unverändert lassen (kein Tipp / bestehenden behalten).
            if ($h === '' || $a === '') {
                continue;
            }
            if (!ctype_digit((string) $h) || !ctype_digit((string) $a)) {
                continue;
            }
            $h = (int) $h; $a = (int) $a;
            if ($h > 99 || $a > 99) {
                continue;
            }

            $match = MatchModel::find($matchId);
            if (!$match) {
                continue;
            }

            // Unverändert -> nichts tun (keine unnötige Punkte-Neuberechnung).
            $existing = Bet::forUserAndMatch($userId, $matchId);
            if ($existing && (int) $existing['pred1'] === $h && (int) $existing['pred2'] === $a) {
                continue;
            }

            Bet::save($userId, $matchId, $h, $a);
            $saved++;

            // Beendete Spiele sofort werten.
            if ($match['status'] === 'finished') {
                $toScore[$matchId] = true;
            }
        }

        foreach (array_keys($toScore) as $mid) {
            ScoringService::scoreMatch($mid);
        }

        Session::flash('success', $saved > 0
            ? sprintf('%d Tipp(s) für "%s" gespeichert.', $saved, $user['display_name'])
            : 'Keine Änderungen gespeichert.');
        redirect('/admin/tipps?user=' . $userId . '&filter=' . $filter);
    }
}
