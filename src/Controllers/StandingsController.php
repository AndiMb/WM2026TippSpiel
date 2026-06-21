<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Setting;
use App\Services\StandingsService;

final class StandingsController
{
    public function index(): void
    {
        Auth::requireLogin();

        View::render('standings', [
            '_active'      => 'rangliste',
            'standings'    => StandingsService::build(),
            'bonusEnabled' => Setting::bool('bonus_enabled'),
            'scoringMode'  => Setting::get('scoring_mode'),
            'meId'         => Auth::id(),
        ], t('nav.standings'));
    }
}
