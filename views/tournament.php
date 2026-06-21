<?php /** @var array $groups @var array $bestThirds @var array $bracket */ ?>
<?php
/** Ein aufgelöstes Bracket-Team anzeigen (Flagge + Name oder übersetztes Label). */
$slot = function (array $tm): string {
    if (($tm['type'] ?? '') === 'team' && !empty($tm['en'])) {
        $html = flag($tm['en']) . ' ' . e(tname($tm['en']));
        if (!empty($tm['proj'])) {
            $html .= ' <span class="proj">' . e(t('tour.proj')) . '</span>';
        }
        return $html;
    }
    // Label je nach Art übersetzen
    switch ($tm['kind'] ?? 'raw') {
        case 'group_winner': $txt = t('tour.group_winner', ['grp' => $tm['grp']]); break;
        case 'group_second': $txt = t('tour.group_second', ['grp' => $tm['grp']]); break;
        case 'group_third':  $txt = t('tour.group_third', ['grps' => $tm['grps']]); break;
        case 'winner_of':    $txt = t('tour.winner_of', ['num' => $tm['num']]); break;
        case 'loser_of':     $txt = t('tour.loser_of', ['num' => $tm['num']]); break;
        default:             $txt = $tm['label'] ?? '?';
    }
    return '<span class="slot-label">' . e($txt) . '</span>';
};

// Spiel um Platz 3 aus dem Hauptbaum herauslösen (separat anzeigen).
$mainRounds = [];
$thirdPlace = null;
foreach ($bracket as $round) {
    if ($round['name'] === 'Match for third place') {
        $thirdPlace = $round['matches'][0] ?? null;
    } else {
        $mainRounds[] = $round;
    }
}

$renderMatch = function (array $bm) use ($slot): string {
    ob_start(); ?>
    <div class="ko-match">
        <?php if ($bm['num']): ?><div class="ko-num"><?= e(t('tour.match', ['num' => $bm['num']])) ?></div><?php endif; ?>
        <div class="ko-side">
            <span class="ko-team"><?= $slot($bm['team1']) ?></span>
            <span class="ko-score"><?= $bm['score1'] !== null ? (int) $bm['score1'] : '' ?></span>
        </div>
        <div class="ko-side">
            <span class="ko-team"><?= $slot($bm['team2']) ?></span>
            <span class="ko-score"><?= $bm['score2'] !== null ? (int) $bm['score2'] : '' ?></span>
        </div>
        <div class="ko-meta">
            <?= e(fmt_datetime($bm['kickoff'], 'd.m. H:i')) ?>
            <?php if (!empty($bm['advances_to'])): ?>
                <span class="ko-advance"><?= e(t('tour.advances', ['num' => $bm['advances_to']])) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php return (string) ob_get_clean();
};
?>

<h1 class="page-title"><?= e(t('tour.title')) ?></h1>

<!-- ===================== Gruppentabellen ===================== -->
<section class="section">
    <h2 class="section-title"><?= e(t('tour.groups')) ?></h2>
    <?php if (!$groups): ?>
        <p class="muted"><?= e(t('tour.no_groups')) ?></p>
    <?php else: ?>
    <div class="group-grid">
        <?php foreach ($groups as $g): ?>
            <div class="card group-card">
                <h3 class="group-title"><?= e($g['name']) ?></h3>
                <table class="table group-table">
                    <thead>
                        <tr>
                            <th>#</th><th><?= e(t('tour.col_team')) ?></th>
                            <th class="num"><?= e(t('tour.col_played')) ?></th>
                            <th class="num"><?= e(t('tour.col_diff')) ?></th>
                            <th class="num"><?= e(t('tour.col_points')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($g['rows'] as $r):
                        $cls = $r['pos'] <= 2 ? 'q-top' : ($r['pos'] === 3 ? 'q-third' : ''); ?>
                        <tr class="<?= $cls ?>">
                            <td class="pos"><?= (int) $r['pos'] ?></td>
                            <td class="t"><?= flag($r['team']) ?> <?= e(tname($r['team'])) ?></td>
                            <td class="num"><?= (int) $r['p'] ?></td>
                            <td class="num"><?= ($r['gd'] > 0 ? '+' : '') . (int) $r['gd'] ?></td>
                            <td class="num"><strong><?= (int) $r['pts'] ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="legend muted">
        <span class="dot q-top"></span> <?= e(t('tour.legend_top')) ?> ·
        <span class="dot q-third"></span> <?= e(t('tour.legend_third')) ?>
    </p>
    <?php endif; ?>
</section>

<!-- ===================== Beste Gruppendritte ===================== -->
<?php if ($bestThirds): ?>
<section class="section">
    <h2 class="section-title"><?= e(t('tour.best_thirds')) ?> <span class="muted"><?= e(t('tour.best_thirds_note')) ?></span></h2>
    <div class="card">
        <table class="table">
            <thead><tr><th>#</th><th><?= e(t('tour.col_group')) ?></th><th><?= e(t('tour.col_team')) ?></th><th class="num"><?= e(t('tour.col_diff')) ?></th><th class="num"><?= e(t('tour.col_points')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($bestThirds as $i => $tm): ?>
                <tr class="<?= $tm['qualified'] ? 'q-top' : '' ?>">
                    <td class="pos"><?= $i + 1 ?></td>
                    <td><?= e(str_replace('Group ', '', $tm['group'])) ?></td>
                    <td class="t"><?= flag($tm['team']) ?> <?= e(tname($tm['team'])) ?></td>
                    <td class="num"><?= ($tm['gd'] > 0 ? '+' : '') . (int) $tm['gd'] ?></td>
                    <td class="num"><strong><?= (int) $tm['pts'] ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<!-- ===================== Turnierbaum (KO) ===================== -->
<section class="section">
    <h2 class="section-title"><?= e(t('tour.bracket')) ?></h2>
    <?php if (!$mainRounds): ?>
        <p class="muted"><?= e(t('tour.bracket_empty')) ?></p>
    <?php else: ?>
        <p class="muted intro"><?= e(t('tour.bracket_intro')) ?></p>

        <!-- Runden-Tabs zum stufenweisen Durchblättern -->
        <div class="round-tabs" id="round-tabs" role="tablist">
            <?php foreach ($mainRounds as $i => $round): ?>
                <button type="button" class="round-tab<?= $i === 0 ? ' is-active' : '' ?>" data-idx="<?= $i ?>">
                    <?= e(t('round.' . $round['name'])) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="bracket-scroll" id="bracket">
            <div class="bracket">
                <?php foreach ($mainRounds as $i => $round): ?>
                    <div class="bracket-round" data-idx="<?= $i ?>">
                        <h3 class="round-title"><?= e(t('round.' . $round['name'])) ?></h3>
                        <?php foreach ($round['matches'] as $bm): ?>
                            <?= $renderMatch($bm) ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($thirdPlace): ?>
            <div class="third-place">
                <h3 class="round-title round-title-third"><?= e(t('round.Match for third place')) ?></h3>
                <?= $renderMatch($thirdPlace) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
