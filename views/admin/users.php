<?php /** @var array $users */ ?>

<a class="back-link" href="<?= e(url('/admin')) ?>">← Adminbereich</a>
<h1 class="page-title">👥 Benutzer verwalten</h1>

<section class="section card">
    <h2 class="section-title">Neuen Benutzer anlegen</h2>
    <form method="post" action="<?= e(url('/admin/benutzer')) ?>" class="form form-grid">
        <?= csrf_field() ?>
        <label class="field">
            <span class="field-label">Benutzername (Login)</span>
            <input class="input" type="text" name="username" required
                   pattern="[A-Za-z0-9_.\-]{3,60}" placeholder="z.B. emma">
        </label>
        <label class="field">
            <span class="field-label">Anzeigename</span>
            <input class="input" type="text" name="display_name" required placeholder="z.B. Emma">
        </label>
        <label class="field">
            <span class="field-label">Passwort</span>
            <input class="input" type="text" name="password" required minlength="6" placeholder="min. 6 Zeichen">
        </label>
        <label class="field">
            <span class="field-label">Rolle</span>
            <select class="input" name="role">
                <option value="player">Spieler</option>
                <option value="admin">Administrator</option>
            </select>
        </label>
        <div class="field field-full">
            <button class="btn btn-primary" type="submit">Benutzer anlegen</button>
        </div>
    </form>
</section>

<section class="section">
    <h2 class="section-title">Alle Benutzer</h2>
    <?php foreach ($users as $u): ?>
        <div class="card user-card">
            <form method="post" action="<?= e(url('/admin/benutzer/' . (int) $u['id'])) ?>" class="form-inline">
                <?= csrf_field() ?>
                <div class="user-head">
                    <strong><?= e($u['username']) ?></strong>
                    <span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] === 'admin' ? 'Admin' : 'Spieler' ?></span>
                </div>
                <div class="form-grid">
                    <label class="field">
                        <span class="field-label">Anzeigename</span>
                        <input class="input" type="text" name="display_name" value="<?= e($u['display_name']) ?>" required>
                    </label>
                    <label class="field">
                        <span class="field-label">Rolle</span>
                        <select class="input" name="role">
                            <option value="player" <?= $u['role'] === 'player' ? 'selected' : '' ?>>Spieler</option>
                            <option value="admin"  <?= $u['role'] === 'admin'  ? 'selected' : '' ?>>Administrator</option>
                        </select>
                    </label>
                    <label class="check">
                        <input type="checkbox" name="is_active" <?= (int) $u['is_active'] === 1 ? 'checked' : '' ?>>
                        <span>aktiv</span>
                    </label>
                </div>
                <button class="btn btn-small" type="submit">Speichern</button>
            </form>

            <div class="user-actions">
                <form method="post" action="<?= e(url('/admin/benutzer/' . (int) $u['id'] . '/passwort')) ?>" class="form-inline2">
                    <?= csrf_field() ?>
                    <input class="input input-sm" type="text" name="password" placeholder="Neues Passwort" minlength="6">
                    <button class="btn btn-small" type="submit">Passwort setzen</button>
                </form>
                <form method="post" action="<?= e(url('/admin/benutzer/' . (int) $u['id'] . '/loeschen')) ?>"
                      onsubmit="return confirm('Benutzer wirklich löschen? Alle Tipps gehen verloren.');">
                    <?= csrf_field() ?>
                    <button class="btn btn-small btn-danger" type="submit">Löschen</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</section>
