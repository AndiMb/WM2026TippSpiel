<?php
/** @var string $title @var string $content @var array $flashes @var ?array $user @var bool $isAdmin @var string $active */
$lang = \App\Core\Lang::locale();
?><!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1b8a5a">
    <title><?= e($title) ?> · <?= e(config('app.name')) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/style.css')) ?>?v=6">
    <link rel="icon" href="<?= e(url('/assets/img/favicon.svg')) ?>" type="image/svg+xml">
</head>
<body>

<?php if ($user): ?>
<header class="topbar">
    <a class="brand" href="<?= e(url('/dashboard')) ?>">
        <span class="brand-ball" aria-hidden="true">⚽</span>
        <span class="brand-text"><?= e(config('app.name')) ?></span>
    </a>
    <form class="logout-form" method="post" action="<?= e(url('/logout')) ?>">
        <?= csrf_field() ?>
        <button class="btn-logout" type="submit" title="<?= e(t('app.logout')) ?>"><?= e(t('app.logout')) ?></button>
    </form>
</header>
<?php endif; ?>

<main class="container">
    <?php foreach ($flashes as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>

    <?= $content /* bereits in den Views via e() escaped */ ?>

    <?php if ($user): ?>
    <footer class="page-footer">
        <a href="<?= e(url('/impressum')) ?>"><?= e(t('footer.imprint')) ?></a>
        <span aria-hidden="true">·</span>
        <a href="<?= e(url('/datenschutz')) ?>"><?= e(t('footer.privacy')) ?></a>
    </footer>
    <?php endif; ?>
</main>

<?php if ($user): ?>
<nav class="bottomnav" aria-label="Navigation">
    <a href="<?= e(url('/dashboard')) ?>" class="<?= $active === 'dashboard' ? 'is-active' : '' ?>">
        <span class="nav-ico">🏠</span><span class="nav-lbl"><?= e(t('nav.start')) ?></span>
    </a>
    <a href="<?= e(url('/tippen')) ?>" class="<?= $active === 'tippen' ? 'is-active' : '' ?>">
        <span class="nav-ico">✏️</span><span class="nav-lbl"><?= e(t('nav.bet')) ?></span>
    </a>
    <a href="<?= e(url('/turnier')) ?>" class="<?= $active === 'turnier' ? 'is-active' : '' ?>">
        <span class="nav-ico">🏟️</span><span class="nav-lbl"><?= e(t('nav.tournament')) ?></span>
    </a>
    <a href="<?= e(url('/rangliste')) ?>" class="<?= $active === 'rangliste' ? 'is-active' : '' ?>">
        <span class="nav-ico">🏆</span><span class="nav-lbl"><?= e(t('nav.standings')) ?></span>
    </a>
    <a href="<?= e(url('/konto')) ?>" class="<?= $active === 'konto' ? 'is-active' : '' ?>">
        <span class="nav-ico">👤</span><span class="nav-lbl"><?= e(t('nav.account')) ?></span>
    </a>
    <?php if ($isAdmin): ?>
    <a href="<?= e(url('/admin')) ?>" class="<?= $active === 'admin' ? 'is-active' : '' ?>">
        <span class="nav-ico">⚙️</span><span class="nav-lbl"><?= e(t('nav.admin')) ?></span>
    </a>
    <?php endif; ?>
</nav>
<?php endif; ?>

<script src="<?= e(url('/assets/js/app.js')) ?>?v=5" defer></script>
</body>
</html>
