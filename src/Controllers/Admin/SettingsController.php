<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Models\Setting;

final class SettingsController
{
    public function index(): void
    {
        Auth::requireAdmin();
        View::render('admin/settings', [
            '_active'  => 'admin',
            'settings' => Setting::all(),
        ], 'Einstellungen');
    }

    public function save(): void
    {
        Auth::requireAdmin();

        // Punktesystem
        Setting::set('points_exact',    (string) max(0, (int) ($_POST['points_exact'] ?? 3)));
        Setting::set('points_diff',     (string) max(0, (int) ($_POST['points_diff'] ?? 2)));
        Setting::set('points_tendency', (string) max(0, (int) ($_POST['points_tendency'] ?? 1)));

        // Nachhol-Regel (Variante A / B)
        $mode = ($_POST['scoring_mode'] ?? 'zero_past') === 'since_join' ? 'since_join' : 'zero_past';
        Setting::set('scoring_mode', $mode);

        // Bonusfragen an/aus
        Setting::set('bonus_enabled', isset($_POST['bonus_enabled']) ? '1' : '0');

        // Turniername
        $name = trim((string) ($_POST['tournament_name'] ?? 'FIFA WM 2026'));
        Setting::set('tournament_name', $name !== '' ? mb_substr($name, 0, 80) : 'FIFA WM 2026');

        Session::flash('success', 'Einstellungen gespeichert. Tipp: Anschließend "Punkte neu berechnen".');
        redirect('/admin/einstellungen');
    }
}
