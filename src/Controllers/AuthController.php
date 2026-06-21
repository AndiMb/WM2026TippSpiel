<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Lang;
use App\Core\Session;
use App\Core\View;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        View::render('auth/login', [], t('login.submit'));
    }

    public function login(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            Session::flash('error', t('flash.login_empty'));
            redirect('/login');
        }

        if (Auth::attempt($username, $password)) {
            // Begrüßung gleich in der Sprache des Benutzers anzeigen.
            $u = Auth::user();
            if (!empty($u['locale'])) {
                $_SESSION['locale'] = $u['locale'];
                Lang::init($u['locale']);
            }
            Session::flash('success', t('flash.login_ok'));
            redirect('/dashboard');
        }

        Session::flash('error', t('flash.login_fail'));
        redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
