<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Setting;
use App\Services\TipsService;

final class TipsController
{
    public function index(): void
    {
        Auth::requireLogin();

        $bonusEnabled = Setting::bool('bonus_enabled');

        View::render('tips', [
            '_active'      => 'rangliste', // gehört thematisch zur Rangliste
            'matches'      => TipsService::recentMatchesWithTips(20),
            'bonusEnabled' => $bonusEnabled,
            'bonusOverview'=> $bonusEnabled ? TipsService::bonusOverview() : [],
            'meId'         => Auth::id(),
        ], t('tips.title'));
    }
}
