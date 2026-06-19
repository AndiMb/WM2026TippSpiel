<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\View;
use App\Models\MatchModel;
use App\Models\User;
use App\Models\Setting;
use App\Core\Database as DB;

final class AdminController
{
    public function index(): void
    {
        Auth::requireAdmin();

        View::render('admin/index', [
            '_active'   => 'admin',
            'userCount' => count(User::all()),
            'matchCount'=> MatchModel::count(),
            'finished'  => (int) DB::scalar("SELECT COUNT(*) FROM matches WHERE status = 'finished'"),
            'betCount'  => (int) DB::scalar('SELECT COUNT(*) FROM bets'),
            'settings'  => Setting::all(),
        ], 'Adminbereich');
    }
}
