<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">⚽</div>
        <h1 class="login-title"><?= e(config('app.name')) ?></h1>
        <p class="login-sub">Bitte melde dich an</p>

        <form method="post" action="<?= e(url('/login')) ?>" class="form">
            <?= csrf_field() ?>
            <label class="field">
                <span class="field-label">Benutzername</span>
                <input class="input input-lg" type="text" name="username"
                       autocomplete="username" autofocus required>
            </label>
            <label class="field">
                <span class="field-label">Passwort</span>
                <input class="input input-lg" type="password" name="password"
                       autocomplete="current-password" required>
            </label>
            <button class="btn btn-primary btn-block btn-lg" type="submit">Anmelden</button>
        </form>

        <p class="login-legal">
            <a href="<?= e(url('/impressum')) ?>">Impressum</a>
            <span aria-hidden="true">·</span>
            <a href="<?= e(url('/datenschutz')) ?>">Datenschutz</a>
        </p>
    </div>
</div>
