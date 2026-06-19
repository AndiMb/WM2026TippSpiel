<?php /** @var array $settings */ ?>

<a class="back-link" href="<?= e(url('/admin')) ?>">← Adminbereich</a>
<h1 class="page-title">🛠️ Einstellungen</h1>

<form method="post" action="<?= e(url('/admin/einstellungen')) ?>" class="form">
    <?= csrf_field() ?>

    <section class="section card">
        <h2 class="section-title">Punktesystem</h2>
        <div class="form-grid">
            <label class="field">
                <span class="field-label">Exaktes Ergebnis</span>
                <input class="input" type="number" name="points_exact" min="0" max="100"
                       value="<?= (int) $settings['points_exact'] ?>">
            </label>
            <label class="field">
                <span class="field-label">Richtige Tordifferenz</span>
                <input class="input" type="number" name="points_diff" min="0" max="100"
                       value="<?= (int) $settings['points_diff'] ?>">
            </label>
            <label class="field">
                <span class="field-label">Richtige Tendenz</span>
                <input class="input" type="number" name="points_tendency" min="0" max="100"
                       value="<?= (int) $settings['points_tendency'] ?>">
            </label>
        </div>
    </section>

    <section class="section card">
        <h2 class="section-title">Nachhol-Regel (bereits gespielte Spiele)</h2>
        <label class="radio-card">
            <input type="radio" name="scoring_mode" value="zero_past"
                   <?= ($settings['scoring_mode'] ?? 'zero_past') !== 'since_join' ? 'checked' : '' ?>>
            <span>
                <strong>Variante A:</strong> Alle Spiele zählen.
                Vergangene/verpasste Spiele bringen 0 Punkte. <em>(empfohlen, alle gleich)</em>
            </span>
        </label>
        <label class="radio-card">
            <input type="radio" name="scoring_mode" value="since_join"
                   <?= ($settings['scoring_mode'] ?? '') === 'since_join' ? 'checked' : '' ?>>
            <span>
                <strong>Variante B:</strong> Rangliste zählt pro Spieler nur Spiele
                <em>seit dem Beitritt</em>.
            </span>
        </label>
    </section>

    <section class="section card">
        <h2 class="section-title">Bonusfragen</h2>
        <label class="check check-lg">
            <input type="checkbox" name="bonus_enabled" <?= ($settings['bonus_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
            <span>Bonusfragen aktivieren (Weltmeister, Finalist, Torschützenkönig …)</span>
        </label>
    </section>

    <section class="section card">
        <h2 class="section-title">Allgemein</h2>
        <label class="field">
            <span class="field-label">Turniername</span>
            <input class="input" type="text" name="tournament_name"
                   value="<?= e($settings['tournament_name'] ?? 'FIFA WM 2026') ?>" maxlength="80">
        </label>
    </section>

    <button class="btn btn-primary btn-lg" type="submit">Einstellungen speichern</button>
</form>
