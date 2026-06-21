<?php $lang = \App\Core\Lang::locale(); ?>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">⚽</div>
        <h1 class="login-title"><?= e(config('app.name')) ?></h1>
        <p class="login-sub"><?= e(t('login.subtitle')) ?></p>

        <form method="post" action="<?= e(url('/login')) ?>" class="form">
            <?= csrf_field() ?>
            <label class="field">
                <span class="field-label"><?= e(t('login.username')) ?></span>
                <input class="input input-lg" type="text" name="username"
                       autocomplete="username" autofocus required>
            </label>
            <label class="field">
                <span class="field-label"><?= e(t('login.password')) ?></span>
                <input class="input input-lg" type="password" name="password"
                       autocomplete="current-password" required>
            </label>
            <button class="btn btn-primary btn-block btn-lg" type="submit"><?= e(t('login.submit')) ?></button>
        </form>

        <div class="lang-switch" aria-label="<?= e(t('login.language')) ?>">
            <a href="<?= e(url('/login?lang=de')) ?>" class="<?= $lang === 'de' ? 'is-active' : '' ?>">🇩🇪 Deutsch</a>
            <a href="<?= e(url('/login?lang=pt')) ?>" class="<?= $lang === 'pt' ? 'is-active' : '' ?>">🇵🇹 Português</a>
        </div>

        <p class="login-legal">
            <a href="<?= e(url('/impressum')) ?>"><?= e(t('footer.imprint')) ?></a>
            <span aria-hidden="true">·</span>
            <a href="<?= e(url('/datenschutz')) ?>"><?= e(t('footer.privacy')) ?></a>
        </p>
    </div>
</div>
