<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Models\BonusQuestion;
use App\Models\Setting;
use App\Models\User;

final class AccountController
{
    public function index(): void
    {
        Auth::requireLogin();
        $uid = Auth::id();

        $bonusEnabled = Setting::bool('bonus_enabled');
        View::render('account', [
            '_active'       => 'konto',
            'me'            => Auth::user(),
            'bonusEnabled'  => $bonusEnabled,
            'bonusQuestions'=> $bonusEnabled ? BonusQuestion::active() : [],
            'bonusAnswers'  => $bonusEnabled ? BonusQuestion::answersForUser($uid) : [],
        ], 'Mein Konto');
    }

    public function changePassword(): void
    {
        Auth::requireLogin();
        $uid = Auth::id();
        $me  = Auth::user();

        $current = (string) ($_POST['current'] ?? '');
        $new     = (string) ($_POST['new'] ?? '');
        $confirm = (string) ($_POST['confirm'] ?? '');

        if (!password_verify($current, $me['password_hash'])) {
            Session::flash('error', 'Aktuelles Passwort ist falsch.');
            redirect('/konto');
        }
        if (strlen($new) < 6) {
            Session::flash('error', 'Neues Passwort muss mindestens 6 Zeichen haben.');
            redirect('/konto');
        }
        if ($new !== $confirm) {
            Session::flash('error', 'Die Passwörter stimmen nicht überein.');
            redirect('/konto');
        }

        User::updatePassword($uid, $new);
        Session::flash('success', 'Passwort geändert.');
        redirect('/konto');
    }

    public function saveBonus(): void
    {
        Auth::requireLogin();
        $uid = Auth::id();

        if (!Setting::bool('bonus_enabled')) {
            redirect('/konto');
        }

        $answers = $_POST['bonus'] ?? [];
        $saved = 0;
        if (is_array($answers)) {
            foreach ($answers as $qid => $answer) {
                $qid = (int) $qid;
                $answer = trim((string) $answer);
                if ($answer === '') {
                    continue;
                }
                $q = BonusQuestion::find($qid);
                if (!$q || (int) $q['is_active'] !== 1) {
                    continue;
                }
                // Nach Deadline keine Änderung mehr.
                if (!empty($q['deadline']) && is_past($q['deadline'])) {
                    continue;
                }
                BonusQuestion::saveAnswer($uid, $qid, mb_substr($answer, 0, 120));
                $saved++;
            }
        }

        Session::flash('success', $saved > 0 ? 'Bonus-Tipps gespeichert.' : 'Keine Änderungen.');
        redirect('/konto');
    }
}
