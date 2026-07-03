<?php /** @var array $me @var bool $bonusEnabled @var array $bonusQuestions @var array $bonusAnswers @var array $languages */ ?>

<h1 class="page-title"><?= e(t('account.title')) ?></h1>

<section class="section card">
    <h2 class="section-title"><?= e(t('account.profile')) ?></h2>
    <dl class="profile">
        <dt><?= e(t('account.display_name')) ?></dt><dd><?= e($me['display_name']) ?></dd>
        <dt><?= e(t('account.username')) ?></dt><dd><?= e($me['username']) ?></dd>
        <dt><?= e(t('account.role')) ?></dt><dd><?= $me['role'] === 'admin' ? e(t('account.role_admin')) : e(t('account.role_player')) ?></dd>
    </dl>
</section>

<section class="section card">
    <h2 class="section-title"><?= e(t('account.language')) ?></h2>
    <form method="post" action="<?= e(url('/konto/sprache')) ?>" class="form">
        <?= csrf_field() ?>
        <label class="field">
            <span class="field-label"><?= e(t('account.language_label')) ?></span>
            <select class="input" name="locale">
                <?php foreach ($languages as $lc): ?>
                    <option value="<?= e($lc) ?>" <?= ($me['locale'] ?? 'de') === $lc ? 'selected' : '' ?>>
                        <?= e(t('lang.' . $lc)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-primary" type="submit"><?= e(t('account.language_save')) ?></button>
    </form>
</section>

<section class="section card">
    <h2 class="section-title"><?= e(t('account.theme')) ?></h2>
    <form method="post" action="<?= e(url('/konto/ansicht')) ?>" class="form">
        <?= csrf_field() ?>
        <?php foreach (app_themes() as $th): ?>
            <label class="radio-card">
                <input type="radio" name="theme" value="<?= e($th) ?>"
                       <?= theme_normalize($me['theme'] ?? 'standard') === $th ? 'checked' : '' ?>>
                <span>
                    <strong><?= e(t('theme.' . $th)) ?></strong>
                    <small class="hint"><?= e(t('theme.' . $th . '_desc')) ?></small>
                </span>
            </label>
        <?php endforeach; ?>
        <button class="btn btn-primary" type="submit"><?= e(t('account.theme_save')) ?></button>
    </form>
</section>

<?php if ($bonusEnabled && $bonusQuestions): ?>
<section class="section card">
    <h2 class="section-title"><?= e(t('account.bonus')) ?></h2>
    <form method="post" action="<?= e(url('/konto/bonus')) ?>" class="form">
        <?= csrf_field() ?>
        <?php foreach ($bonusQuestions as $q):
            $a = $bonusAnswers[(int) $q['id']] ?? null;
            $locked = !empty($q['deadline']) && is_past($q['deadline']);
            $resolved = $q['correct_answer'] !== null && $q['correct_answer'] !== ''; ?>
            <div class="field">
                <span class="field-label">
                    <?= e($q['question']) ?> <span class="muted">(<?= (int) $q['points'] ?> <?= e(t('common.points_short')) ?>)</span>
                </span>
                <input class="input" type="text" name="bonus[<?= (int) $q['id'] ?>]"
                       value="<?= e($a['answer'] ?? '') ?>"
                       <?= $locked || $resolved ? 'disabled' : '' ?>
                       maxlength="120" placeholder="<?= e(t('account.bonus_answer')) ?>">
                <?php if ($resolved): ?>
                    <small class="hint"><?= e(t('account.bonus_correct')) ?> <strong><?= e($q['correct_answer']) ?></strong>
                        <?php if ($a && $a['points'] !== null): ?>· <?= (int) $a['points'] ?> <?= e(t('common.points_short')) ?><?php endif; ?>
                    </small>
                <?php elseif ($locked): ?>
                    <small class="hint"><?= e(t('account.bonus_deadline_over')) ?></small>
                <?php elseif (!empty($q['deadline'])): ?>
                    <small class="hint"><?= e(t('account.bonus_deadline', ['date' => fmt_datetime($q['deadline']), 'clock' => t('common.clock')])) ?></small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button class="btn btn-primary" type="submit"><?= e(t('account.bonus_save')) ?></button>
    </form>
</section>
<?php endif; ?>

<section class="section card">
    <h2 class="section-title"><?= e(t('account.pw_title')) ?></h2>
    <form method="post" action="<?= e(url('/konto/passwort')) ?>" class="form">
        <?= csrf_field() ?>
        <label class="field">
            <span class="field-label"><?= e(t('account.pw_current')) ?></span>
            <input class="input" type="password" name="current" autocomplete="current-password" required>
        </label>
        <label class="field">
            <span class="field-label"><?= e(t('account.pw_new')) ?></span>
            <input class="input" type="password" name="new" autocomplete="new-password" minlength="6" required>
        </label>
        <label class="field">
            <span class="field-label"><?= e(t('account.pw_repeat')) ?></span>
            <input class="input" type="password" name="confirm" autocomplete="new-password" minlength="6" required>
        </label>
        <button class="btn btn-primary" type="submit"><?= e(t('account.pw_submit')) ?></button>
    </form>
</section>
