<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Lang;
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
            'languages'     => Lang::SUPPORTED,
        ], t('account.title'));
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
            Session::flash('error', t('flash.pw_wrong'));
            redirect('/konto');
        }
        if (strlen($new) < 6) {
            Session::flash('error', t('flash.pw_short'));
            redirect('/konto');
        }
        if ($new !== $confirm) {
            Session::flash('error', t('flash.pw_mismatch'));
            redirect('/konto');
        }

        User::updatePassword($uid, $new);
        Session::flash('success', t('flash.pw_changed'));
        redirect('/konto');
    }

    /** Ansicht (Design) des Benutzers ändern. */
    public function saveTheme(): void
    {
        Auth::requireLogin();
        $uid = Auth::id();

        $theme = theme_normalize((string) ($_POST['theme'] ?? 'standard'));
        User::updateTheme($uid, $theme);

        Session::flash('success', t('flash.theme_saved'));
        redirect('/konto');
    }

    /** Anzeigesprache des Benutzers ändern. */
    public function saveLanguage(): void
    {
        Auth::requireLogin();
        $uid = Auth::id();

        $locale = Lang::normalize((string) ($_POST['locale'] ?? Lang::DEFAULT));
        User::updateLocale($uid, $locale);
        $_SESSION['locale'] = $locale;          // sofort wirksam
        Lang::init($locale);                    // Flash gleich in neuer Sprache

        Session::flash('success', t('flash.lang_saved'));
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

        Session::flash('success', $saved > 0 ? t('flash.bonus_saved') : t('flash.no_change'));
        redirect('/konto');
    }
}
