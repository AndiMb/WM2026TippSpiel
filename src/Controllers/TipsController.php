<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\TipsService;

final class TipsController
{
    public function index(): void
    {
        Auth::requireLogin();

        View::render('tips', [
            '_active' => 'rangliste', // gehört thematisch zur Rangliste
            'matches' => TipsService::recentMatchesWithTips(20),
            'meId'    => Auth::id(),
        ], 'Tipps der anderen');
    }
}
