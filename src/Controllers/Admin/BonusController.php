<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Models\BonusQuestion;
use App\Services\ScoringService;

final class BonusController
{
    public function index(): void
    {
        Auth::requireAdmin();
        View::render('admin/bonus', [
            '_active'   => 'admin',
            'questions' => BonusQuestion::all(),
        ], 'Bonusfragen');
    }

    public function create(): void
    {
        Auth::requireAdmin();
        $question = trim((string) ($_POST['question'] ?? ''));
        $qtype    = (string) ($_POST['qtype'] ?? 'custom');
        $points   = max(0, (int) ($_POST['points'] ?? 5));
        $deadline = self::parseDeadline($_POST['deadline'] ?? '');

        if ($question === '') {
            Session::flash('error', 'Bitte eine Frage eingeben.');
            redirect('/admin/bonus');
        }
        $allowed = ['champion', 'finalist', 'topscorer', 'custom'];
        if (!in_array($qtype, $allowed, true)) {
            $qtype = 'custom';
        }

        BonusQuestion::create($qtype, $question, $points, $deadline);
        Session::flash('success', 'Bonusfrage angelegt.');
        redirect('/admin/bonus');
    }

    public function update(array $params): void
    {
        Auth::requireAdmin();
        $id = (int) $params['id'];
        $question = trim((string) ($_POST['question'] ?? ''));
        $points   = max(0, (int) ($_POST['points'] ?? 5));
        $deadline = self::parseDeadline($_POST['deadline'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($question === '') {
            Session::flash('error', 'Frage darf nicht leer sein.');
            redirect('/admin/bonus');
        }
        BonusQuestion::update($id, $question, $points, $deadline, $isActive);
        Session::flash('success', 'Bonusfrage aktualisiert.');
        redirect('/admin/bonus');
    }

    /** Richtige Antwort setzen und Punkte vergeben. */
    public function resolve(array $params): void
    {
        Auth::requireAdmin();
        $id = (int) $params['id'];
        $answer = trim((string) ($_POST['correct_answer'] ?? ''));

        if ($answer === '') {
            Session::flash('error', 'Bitte die richtige Antwort eingeben.');
            redirect('/admin/bonus');
        }
        BonusQuestion::resolve($id, $answer);
        ScoringService::scoreBonus($id);
        Session::flash('success', 'Bonusfrage aufgelöst und gewertet.');
        redirect('/admin/bonus');
    }

    public function delete(array $params): void
    {
        Auth::requireAdmin();
        BonusQuestion::delete((int) $params['id']);
        Session::flash('success', 'Bonusfrage gelöscht.');
        redirect('/admin/bonus');
    }

    /** "YYYY-MM-DDTHH:MM" (lokale Zeit) -> UTC-Zeitstempel. */
    private static function parseDeadline(string $local): ?string
    {
        $local = trim($local);
        if ($local === '') {
            return null;
        }
        try {
            $dt = new \DateTime($local, new \DateTimeZone((string) config('app.timezone')));
            $dt->setTimezone(new \DateTimeZone('UTC'));
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
