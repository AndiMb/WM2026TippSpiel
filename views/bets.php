<?php /** @var array $matches @var array $betMap */ ?>

<?php
/**
 * Kleiner Helfer: zeigt FIFA-Rang und das letzte Spielergebnis einer
 * Mannschaft als kurzen Hinweis (Anhaltspunkt für den Tipp).
 */
$forms = $forms ?? [];
$teamHint = function (string $en) use ($forms): string {
    $parts = [];
    $rank = \App\Services\TeamService::rank($en);
    if ($rank) {
        $parts[] = 'FIFA #' . $rank;
    }
    if (isset($forms[$en])) {
        $f = $forms[$en];
        $icon = $f['outcome'] === 'win' ? '✅' : ($f['outcome'] === 'loss' ? '❌' : '➖');
        $parts[] = t('bets.hint_last') . ' ' . $icon . ' ' . (int) $f['gf'] . ':' . (int) $f['ga']
                 . ' ' . t('bets.hint_vs') . ' ' . e(tname($f['opp']));
    }
    return implode(' · ', $parts);
};
?>

<h1 class="page-title"><?= e(t('bets.title')) ?></h1>
<p class="muted intro"><?= e(t('bets.intro')) ?></p>
<p class="autosave-note" id="autosave-note" hidden><?= e(t('bets.autosave')) ?></p>

<?php if (!$matches): ?>
    <div class="empty">
        <div class="empty-ico">📭</div>
        <p><?= e(t('bets.empty_title')) ?></p>
        <p class="muted"><?= e(t('bets.empty_sub')) ?></p>
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

        <div class="bet-card" data-match="<?= $id ?>">
            <div class="bet-card-meta">
                <?= e(fmt_datetime($m['kickoff'], 'H:i')) ?> <?= e(t('common.clock')) ?>
                <?php if (!empty($m['group_name'])): ?>· <?= e($m['group_name']) ?><?php endif; ?>
                <?php if (!empty($m['venue'])): ?>· <?= e($m['venue']) ?><?php endif; ?>
                <span class="bet-status" data-status aria-live="polite"></span>
            </div>
            <div class="bet-card-row">
                <div class="bet-team bet-team-home">
                    <div class="bet-flag"><?= flag($m['team1']) ?></div>
                    <div class="bet-team-name"><?= e(tname($m['team1'])) ?></div>
                    <div class="team-hint"><?= $teamHint($m['team1']) ?></div>
                </div>

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

                <div class="bet-team bet-team-away">
                    <div class="bet-flag"><?= flag($m['team2']) ?></div>
                    <div class="bet-team-name"><?= e(tname($m['team2'])) ?></div>
                    <div class="team-hint"><?= $teamHint($m['team2']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <?php /* Tipps werden automatisch gespeichert (siehe app.js). Der Button
             bleibt nur als Fallback für Browser ohne JavaScript erhalten. */ ?>
    <noscript>
        <div class="sticky-save">
            <button class="btn btn-primary btn-lg btn-block" type="submit"><?= e(t('bets.save')) ?></button>
        </div>
    </noscript>
</form>
<script type="application/json" id="autosave-i18n"><?= json_encode([
    'saving' => t('bets.saving'),
    'saved'  => t('bets.saved'),
    'error'  => t('bets.save_error'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php endif; ?>
