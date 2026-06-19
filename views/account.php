<?php /** @var array $me @var bool $bonusEnabled @var array $bonusQuestions @var array $bonusAnswers */ ?>

<h1 class="page-title">👤 Mein Konto</h1>

<section class="section card">
    <h2 class="section-title">Profil</h2>
    <dl class="profile">
        <dt>Anzeigename</dt><dd><?= e($me['display_name']) ?></dd>
        <dt>Benutzername</dt><dd><?= e($me['username']) ?></dd>
        <dt>Rolle</dt><dd><?= $me['role'] === 'admin' ? 'Administrator' : 'Spieler' ?></dd>
    </dl>
</section>

<?php if ($bonusEnabled && $bonusQuestions): ?>
<section class="section card">
    <h2 class="section-title">⭐ Bonusfragen</h2>
    <form method="post" action="<?= e(url('/konto/bonus')) ?>" class="form">
        <?= csrf_field() ?>
        <?php foreach ($bonusQuestions as $q):
            $a = $bonusAnswers[(int) $q['id']] ?? null;
            $locked = !empty($q['deadline']) && is_past($q['deadline']);
            $resolved = $q['correct_answer'] !== null && $q['correct_answer'] !== ''; ?>
            <div class="field">
                <span class="field-label">
                    <?= e($q['question']) ?> <span class="muted">(<?= (int) $q['points'] ?> P)</span>
                </span>
                <input class="input" type="text" name="bonus[<?= (int) $q['id'] ?>]"
                       value="<?= e($a['answer'] ?? '') ?>"
                       <?= $locked || $resolved ? 'disabled' : '' ?>
                       maxlength="120" placeholder="Deine Antwort">
                <?php if ($resolved): ?>
                    <small class="hint">Richtig: <strong><?= e($q['correct_answer']) ?></strong>
                        <?php if ($a && $a['points'] !== null): ?>· du: <?= (int) $a['points'] ?> P<?php endif; ?>
                    </small>
                <?php elseif ($locked): ?>
                    <small class="hint">Einsendeschluss erreicht.</small>
                <?php elseif (!empty($q['deadline'])): ?>
                    <small class="hint">Bis <?= e(fmt_datetime($q['deadline'])) ?> Uhr änderbar.</small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button class="btn btn-primary" type="submit">Bonus-Tipps speichern</button>
    </form>
</section>
<?php endif; ?>

<section class="section card">
    <h2 class="section-title">🔒 Passwort ändern</h2>
    <form method="post" action="<?= e(url('/konto/passwort')) ?>" class="form">
        <?= csrf_field() ?>
        <label class="field">
            <span class="field-label">Aktuelles Passwort</span>
            <input class="input" type="password" name="current" autocomplete="current-password" required>
        </label>
        <label class="field">
            <span class="field-label">Neues Passwort</span>
            <input class="input" type="password" name="new" autocomplete="new-password" minlength="6" required>
        </label>
        <label class="field">
            <span class="field-label">Neues Passwort wiederholen</span>
            <input class="input" type="password" name="confirm" autocomplete="new-password" minlength="6" required>
        </label>
        <button class="btn btn-primary" type="submit">Passwort ändern</button>
    </form>
</section>
