<?php /** @var array $questions */ ?>

<a class="back-link" href="<?= e(url('/admin')) ?>">← Adminbereich</a>
<h1 class="page-title">⭐ Bonusfragen</h1>
<p class="muted intro">Bonusfragen werden nur angezeigt, wenn sie in den Einstellungen aktiviert sind.</p>

<section class="section card">
    <h2 class="section-title">Neue Bonusfrage</h2>
    <form method="post" action="<?= e(url('/admin/bonus')) ?>" class="form form-grid">
        <?= csrf_field() ?>
        <label class="field">
            <span class="field-label">Typ</span>
            <select class="input" name="qtype">
                <option value="champion">Weltmeister</option>
                <option value="finalist">Finalist</option>
                <option value="topscorer">Torschützenkönig</option>
                <option value="custom">Eigene Frage</option>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Punkte</span>
            <input class="input" type="number" name="points" value="5" min="0" max="100">
        </label>
        <label class="field field-full">
            <span class="field-label">Frage</span>
            <input class="input" type="text" name="question" required placeholder="z.B. Wer wird Weltmeister 2026?">
        </label>
        <label class="field">
            <span class="field-label">Einsendeschluss (optional)</span>
            <input class="input" type="datetime-local" name="deadline">
        </label>
        <div class="field field-full">
            <button class="btn btn-primary" type="submit">Frage anlegen</button>
        </div>
    </form>
</section>

<section class="section">
    <h2 class="section-title">Vorhandene Fragen</h2>
    <?php if (!$questions): ?>
        <p class="muted">Noch keine Bonusfragen angelegt.</p>
    <?php else: foreach ($questions as $q): ?>
        <div class="card">
            <form method="post" action="<?= e(url('/admin/bonus/' . (int) $q['id'])) ?>" class="form">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label class="field field-full">
                        <span class="field-label">Frage (<?= e($q['qtype']) ?>)</span>
                        <input class="input" type="text" name="question" value="<?= e($q['question']) ?>" required>
                    </label>
                    <label class="field">
                        <span class="field-label">Punkte</span>
                        <input class="input" type="number" name="points" value="<?= (int) $q['points'] ?>" min="0">
                    </label>
                    <label class="field">
                        <span class="field-label">Einsendeschluss</span>
                        <input class="input" type="datetime-local" name="deadline"
                               value="<?= e($q['deadline'] ? fmt_datetime($q['deadline'], 'Y-m-d\TH:i') : '') ?>">
                    </label>
                    <label class="check">
                        <input type="checkbox" name="is_active" <?= (int) $q['is_active'] === 1 ? 'checked' : '' ?>>
                        <span>aktiv</span>
                    </label>
                </div>
                <button class="btn btn-small" type="submit">Speichern</button>
            </form>

            <div class="user-actions">
                <form method="post" action="<?= e(url('/admin/bonus/' . (int) $q['id'] . '/aufloesen')) ?>" class="form-inline2">
                    <?= csrf_field() ?>
                    <input class="input input-sm" type="text" name="correct_answer"
                           value="<?= e($q['correct_answer'] ?? '') ?>" placeholder="Richtige Antwort">
                    <button class="btn btn-small btn-primary" type="submit">Auflösen &amp; werten</button>
                </form>
                <form method="post" action="<?= e(url('/admin/bonus/' . (int) $q['id'] . '/loeschen')) ?>"
                      onsubmit="return confirm('Bonusfrage löschen?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-small btn-danger" type="submit">Löschen</button>
                </form>
            </div>
        </div>
    <?php endforeach; endif; ?>
</section>
