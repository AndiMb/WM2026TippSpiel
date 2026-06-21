<?php /** @var int $code @var string $message */ ?>
<div class="empty">
    <div class="empty-ico"><?= (int) $code === 404 ? '🔍' : '⚠️' ?></div>
    <h1 class="page-title"><?= (int) $code ?></h1>
    <p><?= e($message) ?></p>
    <a class="btn btn-primary" href="<?= e(url('/dashboard')) ?>"><?= e(t('error.home')) ?></a>
</div>
