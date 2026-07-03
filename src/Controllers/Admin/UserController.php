<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Models\User;

final class UserController
{
    public function index(): void
    {
        Auth::requireAdmin();
        View::render('admin/users', [
            '_active' => 'admin',
            'users'   => User::all(),
        ], 'Benutzer verwalten');
    }

    public function create(): void
    {
        Auth::requireAdmin();

        $username    = trim((string) ($_POST['username'] ?? ''));
        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        $password    = (string) ($_POST['password'] ?? '');
        $role        = ($_POST['role'] ?? 'player') === 'admin' ? 'admin' : 'player';

        // Validierung
        $errors = [];
        if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $username)) {
            $errors[] = 'Benutzername: 3–60 Zeichen, nur Buchstaben, Zahlen, . _ -';
        }
        if ($displayName === '' || mb_strlen($displayName) > 80) {
            $errors[] = 'Anzeigename ist erforderlich (max. 80 Zeichen).';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Passwort muss mindestens 6 Zeichen haben.';
        }
        if (!$errors && User::usernameExists($username)) {
            $errors[] = 'Benutzername ist bereits vergeben.';
        }

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            redirect('/admin/benutzer');
        }

        $locale = \App\Core\Lang::normalize((string) ($_POST['locale'] ?? 'de'));
        $theme  = theme_normalize((string) ($_POST['theme'] ?? 'standard'));
        User::create($username, $password, $displayName, $role, $locale, $theme);
        Session::flash('success', "Benutzer \"$displayName\" angelegt.");
        redirect('/admin/benutzer');
    }

    public function update(array $params): void
    {
        Auth::requireAdmin();
        $id = (int) $params['id'];

        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        $role        = ($_POST['role'] ?? 'player') === 'admin' ? 'admin' : 'player';
        $isActive    = isset($_POST['is_active']) ? 1 : 0;

        if ($displayName === '') {
            Session::flash('error', 'Anzeigename darf nicht leer sein.');
            redirect('/admin/benutzer');
        }

        // Sicherheitsnetz: man darf sich nicht selbst die Adminrechte/Aktivierung entziehen.
        if ($id === Auth::id() && ($role !== 'admin' || $isActive !== 1)) {
            Session::flash('error', 'Eigene Admin-Rechte können nicht entzogen werden.');
            redirect('/admin/benutzer');
        }

        $locale = \App\Core\Lang::normalize((string) ($_POST['locale'] ?? 'de'));
        $theme  = theme_normalize((string) ($_POST['theme'] ?? 'standard'));
        User::update($id, $displayName, $role, $isActive, $locale, $theme);
        Session::flash('success', 'Benutzer aktualisiert.');
        redirect('/admin/benutzer');
    }

    public function resetPassword(array $params): void
    {
        Auth::requireAdmin();
        $id = (int) $params['id'];
        $password = (string) ($_POST['password'] ?? '');

        if (strlen($password) < 6) {
            Session::flash('error', 'Passwort muss mindestens 6 Zeichen haben.');
            redirect('/admin/benutzer');
        }
        User::updatePassword($id, $password);
        Session::flash('success', 'Passwort zurückgesetzt.');
        redirect('/admin/benutzer');
    }

    public function delete(array $params): void
    {
        Auth::requireAdmin();
        $id = (int) $params['id'];

        if ($id === Auth::id()) {
            Session::flash('error', 'Der eigene Account kann nicht gelöscht werden.');
            redirect('/admin/benutzer');
        }

        User::delete($id);
        Session::flash('success', 'Benutzer gelöscht.');
        redirect('/admin/benutzer');
    }
}
