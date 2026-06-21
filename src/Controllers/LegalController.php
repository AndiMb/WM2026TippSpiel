<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Setting;

/**
 * Öffentliche Rechtstexte: Impressum und Datenschutzerklärung.
 * Diese Seiten sind bewusst OHNE Login erreichbar (z. B. von der Anmeldeseite),
 * da sie auch für nicht eingeloggte Besucher verfügbar sein müssen.
 */
final class LegalController
{
    public function impressum(): void
    {
        View::render('legal/impressum', [
            'operatorName'    => Setting::get('operator_name'),
            'operatorAddress' => Setting::get('operator_address'),
            'operatorEmail'   => Setting::get('operator_email'),
        ], 'Impressum');
    }

    public function datenschutz(): void
    {
        View::render('legal/datenschutz', [
            'operatorName'    => Setting::get('operator_name'),
            'operatorAddress' => Setting::get('operator_address'),
            'operatorEmail'   => Setting::get('operator_email'),
        ], 'Datenschutz');
    }
}
