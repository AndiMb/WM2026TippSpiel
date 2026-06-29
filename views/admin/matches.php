<?php /** @var array $matches */ ?>

<a class="back-link" href="<?= e(url('/admin')) ?>">← Adminbereich</a>
<h1 class="page-title">📅 Spiele &amp; Ergebnisse</h1>

<section class="section card">
    <h2 class="section-title">Spielplan importieren</h2>
    <p class="muted">Quelle: OpenFootball (kostenlos, kein API-Key). Importiert Spielplan und Ergebnisse.</p>

    <form method="post" action="<?= e(url('/admin/spiele/import')) ?>" class="form-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="source" value="online">
        <button class="btn btn-primary" type="submit">🔄 Jetzt online importieren</button>
    </form>

    <details class="mt">
        <summary>Alternativ: Datei hochladen (JSON / CSV)</summary>
        <form method="post" action="<?= e(url('/admin/spiele/import')) ?>" enctype="multipart/form-data" class="form mt">
            <?= csrf_field() ?>
            <label class="field">
                <span class="field-label">Format</span>
                <select class="input" name="source">
                    <option value="json">JSON</option>
                    <option value="csv">CSV (date,time,team1,team2,group,venue,score1,score2)</option>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Datei</span>
                <input class="input" type="file" name="file" accept=".json,.csv,text/csv,application/json" required>
            </label>
            <button class="btn" type="submit">Datei importieren</button>
        </form>
    </details>
</section>

<section class="section card">
    <h2 class="section-title">Punkte neu berechnen</h2>
    <p class="muted">Nach Änderungen am Punktesystem alle Tipps neu bewerten.</p>
    <form method="post" action="<?= e(url('/admin/spiele/neuberechnen')) ?>">
        <?= csrf_field() ?>
        <button class="btn" type="submit">🧮 Alle Punkte neu berechnen</button>
    </form>
</section>

<section class="section">
    <h2 class="section-title">Alle Spiele (<?= count($matches) ?>)</h2>
    <?php if (!$matches): ?>
        <p class="muted">Noch keine Spiele importiert.</p>
    <?php else: ?>
        <div class="match-admin-list">
        <?php foreach ($matches as $m): ?>
            <form method="post" action="<?= e(url('/admin/spiele/' . (int) $m['id'] . '/ergebnis')) ?>" class="match-admin-row">
                <?= csrf_field() ?>
                <div class="ma-info">
                    <div class="ma-teams"><?= flag($m['team1']) ?> <?= e(tname($m['team1'])) ?> – <?= flag($m['team2']) ?> <?= e(tname($m['team2'])) ?></div>
                    <div class="ma-meta">
                        <?= e(fmt_datetime($m['kickoff'])) ?>
                        <?php if (!empty($m['group_name'])): ?>· <?= e($m['group_name']) ?><?php endif; ?>
                        · <span class="status status-<?= e($m['status']) ?>"><?= e($m['status']) ?></span>
                    </div>
                </div>
                <div class="ma-result">
                    <input class="score-input score-input-sm" type="number" min="0" max="99"
                           name="score1" value="<?= $m['score1'] !== null ? (int) $m['score1'] : '' ?>" placeholder="–">
                    <span class="colon">:</span>
                    <input class="score-input score-input-sm" type="number" min="0" max="99"
                           name="score2" value="<?= $m['score2'] !== null ? (int) $m['score2'] : '' ?>" placeholder="–">
                    <button class="btn btn-small btn-primary" type="submit">OK</button>
                </div>
                <?php if (($m['stage'] ?? '') === 'knockout'): ?>
                <div class="ma-extra">
                    <span class="ma-extra-label" title="Nur ausfüllen, wenn es nach 90 Minuten unentschieden stand. Reguläres Ergebnis (oben) bleibt der 90-Minuten-Stand.">Verlängerung / Elfmeter:</span>
                    <span class="ma-extra-grp" title="Stand nach Verlängerung (n.V.)">
                        n.V.
                        <input class="score-input score-input-sm" type="number" min="0" max="99"
                               name="et1" value="<?= isset($m['et1']) && $m['et1'] !== null ? (int) $m['et1'] : '' ?>" placeholder="–">
                        <span class="colon">:</span>
                        <input class="score-input score-input-sm" type="number" min="0" max="99"
                               name="et2" value="<?= isset($m['et2']) && $m['et2'] !== null ? (int) $m['et2'] : '' ?>" placeholder="–">
                    </span>
                    <span class="ma-extra-grp" title="Ergebnis Elfmeterschießen (i.E.)">
                        i.E.
                        <input class="score-input score-input-sm" type="number" min="0" max="99"
                               name="pen1" value="<?= isset($m['pen1']) && $m['pen1'] !== null ? (int) $m['pen1'] : '' ?>" placeholder="–">
                        <span class="colon">:</span>
                        <input class="score-input score-input-sm" type="number" min="0" max="99"
                               name="pen2" value="<?= isset($m['pen2']) && $m['pen2'] !== null ? (int) $m['pen2'] : '' ?>" placeholder="–">
                    </span>
                </div>
                <?php endif; ?>
            </form>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
