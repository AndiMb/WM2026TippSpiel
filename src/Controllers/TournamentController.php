<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\BracketService;
use App\Services\GroupsService;

final class TournamentController
{
    public function index(): void
    {
        Auth::requireLogin();

        View::render('tournament', [
            '_active'    => 'turnier',
            'groups'     => GroupsService::standings(),
            'bestThirds' => GroupsService::bestThirds(),
            'bracket'    => BracketService::build(),
        ], 'Gruppen & Turnierbaum');
    }
}
