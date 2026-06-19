<?php /** @var array $matches @var array $betMap */ ?>

<h1 class="page-title">Spiele tippen</h1>
<p class="muted intro">Tippe das Endergebnis. Du kannst deinen Tipp bis zum Anpfiff ändern.</p>

<?php if (!$matches): ?>
    <div class="empty">
        <div class="empty-ico">📭</div>
        <p>Aktuell gibt es keine Spiele zum Tippen.</p>
        <p class="muted">Sobald neue Spiele anstehen, erscheinen sie hier.</p>
    </div>
<?php else: ?>
<form method="post" action="<?= e(url('/tippen')) ?>" id="bet-form">
    <?= csrf_field() ?>

    <div class="bet-list">
    <?php
    $currentDay = null;
    foreach ($matches as $m):
        $id  = (int) $m['id'];
        $bet = $betMap[$id] ?? null;
        $day = fmt_datetime($m['kickoff'], 'l, d.m.Y');
        $dayShort = fmt_datetime($m['kickoff'], 'D, d.m.Y');
        if ($day !== $currentDay): $currentDay = $day; ?>
            <h2 class="day-head"><?= e($dayShort) ?></h2>
        <?php endif; ?>

        <div class="bet-card">
            <div class="bet-card-meta">
                <?= e(fmt_datetime($m['kickoff'], 'H:i')) ?> Uhr
                <?php if (!empty($m['group_name'])): ?>· <?= e($m['group_name']) ?><?php endif; ?>
                <?php if (!empty($m['venue'])): ?>· <?= e($m['venue']) ?><?php endif; ?>
            </div>
            <div class="bet-card-row">
                <div class="bet-team bet-team-home"><?= e($m['team1']) ?></div>

                <div class="bet-inputs">
                    <input class="score-input" type="number" inputmode="numeric"
                           min="0" max="99" name="pred1[<?= $id ?>]"
                           value="<?= $bet ? (int) $bet['pred1'] : '' ?>"
                           aria-label="Tore <?= e($m['team1']) ?>" placeholder="–">
                    <span class="colon">:</span>
                    <input class="score-input" type="number" inputmode="numeric"
                           min="0" max="99" name="pred2[<?= $id ?>]"
                           value="<?= $bet ? (int) $bet['pred2'] : '' ?>"
                           aria-label="Tore <?= e($m['team2']) ?>" placeholder="–">
                </div>

                <div class="bet-team bet-team-away"><?= e($m['team2']) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="sticky-save">
        <button class="btn btn-primary btn-lg btn-block" type="submit">✅ Tipps speichern</button>
    </div>
</form>
<?php endif; ?>
