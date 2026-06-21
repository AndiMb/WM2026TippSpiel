<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Bet;
use App\Models\MatchModel;
use App\Services\StandingsService;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();
        $uid = Auth::id();

        $upcoming = MatchModel::upcoming(5);
        $betMap   = Bet::mapForUser($uid);

        // Eigene Punkte und Platz aus der Rangliste ziehen.
        $standings = StandingsService::build();
        $me = null;
        foreach ($standings as $row) {
            if ($row['user_id'] === $uid) {
                $me = $row;
                break;
            }
        }

        View::render('dashboard', [
            '_active'    => 'dashboard',
            'upcoming'   => $upcoming,
            'betMap'     => $betMap,
            'openCount'  => Bet::openCount($uid),
            'standings'  => array_slice($standings, 0, 5),
            'me'         => $me,
            'recent'     => MatchModel::finished(5),
        ], t('nav.start'));
    }
}
