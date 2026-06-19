<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        View::render('auth/login', [], 'Anmelden');
    }

    public function login(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            Session::flash('error', 'Bitte Benutzername und Passwort eingeben.');
            redirect('/login');
        }

        if (Auth::attempt($username, $password)) {
            Session::flash('success', 'Willkommen zurück!');
            redirect('/dashboard');
        }

        Session::flash('error', 'Benutzername oder Passwort ist falsch.');
        redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
