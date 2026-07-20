<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\StandingsService;

/**
 * Kleine "Siegerehrung": zeigt die Rangliste als schrittweise Enthüllung vom
 * letzten zum ersten Platz, mit Konfetti – als nettes Extra fürs Saisonende.
 * Rein clientseitig (JS) auf Basis der bereits berechneten Rangliste; keine
 * eigene Serverlogik nötig.
 */
final class AwardController
{
    public function index(): void
    {
        Auth::requireLogin();

        View::render('award', [
            '_active'   => 'rangliste',
            'standings' => StandingsService::build(),
            'meId'      => Auth::id(),
        ], t('ceremony.title'));
    }
}
