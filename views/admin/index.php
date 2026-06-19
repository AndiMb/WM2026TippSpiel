<?php /** @var int $userCount @var int $matchCount @var int $finished @var int $betCount @var array $settings */ ?>

<h1 class="page-title">⚙️ Adminbereich</h1>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-num"><?= (int) $userCount ?></div><div class="stat-lbl">Benutzer</div></div>
    <div class="stat-card"><div class="stat-num"><?= (int) $matchCount ?></div><div class="stat-lbl">Spiele</div></div>
    <div class="stat-card"><div class="stat-num"><?= (int) $finished ?></div><div class="stat-lbl">Beendet</div></div>
    <div class="stat-card"><div class="stat-num"><?= (int) $betCount ?></div><div class="stat-lbl">Tipps</div></div>
</div>

<div class="admin-menu">
    <a class="admin-tile" href="<?= e(url('/admin/benutzer')) ?>"><span class="tile-ico">👥</span> Benutzer verwalten</a>
    <a class="admin-tile" href="<?= e(url('/admin/spiele')) ?>"><span class="tile-ico">📅</span> Spiele &amp; Ergebnisse</a>
    <a class="admin-tile" href="<?= e(url('/admin/bonus')) ?>"><span class="tile-ico">⭐</span> Bonusfragen</a>
    <a class="admin-tile" href="<?= e(url('/admin/einstellungen')) ?>"><span class="tile-ico">🛠️</span> Einstellungen</a>
</div>

<section class="section card">
    <h2 class="section-title">Aktuelle Konfiguration</h2>
    <dl class="profile">
        <dt>Punkte (exakt/Diff/Tendenz)</dt>
        <dd><?= (int) $settings['points_exact'] ?> / <?= (int) $settings['points_diff'] ?> / <?= (int) $settings['points_tendency'] ?></dd>
        <dt>Nachhol-Regel</dt>
        <dd><?= $settings['scoring_mode'] === 'since_join' ? 'Variante B – nur Spiele seit Beitritt' : 'Variante A – Vergangenes zählt mit 0 Punkten' ?></dd>
        <dt>Bonusfragen</dt>
        <dd><?= ($settings['bonus_enabled'] ?? '0') === '1' ? 'aktiviert' : 'deaktiviert' ?></dd>
    </dl>
</section>
