<?php /** @var array $groups @var array $bestThirds @var array $bracket */ ?>
<?php
/** Übersetzten Text eines Label-Slots erzeugen. */
$labelText = function (array $tm): string {
    switch ($tm['kind'] ?? 'raw') {
        case 'group_winner': return t('tour.group_winner', ['grp' => $tm['grp']]);
        case 'group_second': return t('tour.group_second', ['grp' => $tm['grp']]);
        case 'group_third':  return t('tour.group_third', ['grps' => $tm['grps']]);
        case 'winner_of':    return t('tour.winner_of', ['num' => $tm['num']]);
        case 'loser_of':     return t('tour.loser_of', ['num' => $tm['num']]);
        default:             return $tm['label'] ?? '?';
    }
};

/** HTML-Inhalt einer Mannschaftszeile (Server-Render = Ausgangszustand). */
$sideContent = function (array $tm) use ($labelText): string {
    if (($tm['type'] ?? '') === 'team' && !empty($tm['en'])) {
        $html = flag($tm['en']) . ' <span class="kt-name">' . e(tname($tm['en'])) . '</span>';
        if (!empty($tm['proj'])) {
            $html .= ' <span class="proj">' . e(t('tour.proj')) . '</span>';
        }
        return $html;
    }
    if (($tm['type'] ?? '') === 'open') {
        return '<span class="ko-open">' . e(t('tour.open')) . '</span>';
    }
    return '<span class="slot-label">' . e($labelText($tm)) . '</span>';
};

/** Basis-Objekt eines Slots für das JS-Modell (Simulation). */
$jsBase = function (array $tm) use ($labelText): array {
    if (($tm['type'] ?? '') === 'team' && !empty($tm['en'])) {
        $iso = \App\Services\TeamService::iso2($tm['en']);
        return ['t' => 'team', 'name' => tname($tm['en']),
                'flag' => $iso ? url('/assets/img/flags/' . $iso . '.svg') : '',
                'proj' => !empty($tm['proj'])];
    }
    if (($tm['type'] ?? '') === 'open') {
        return ['t' => 'open'];
    }
    return ['t' => 'label', 'text' => $labelText($tm)];
};

// Spiel um Platz 3 aus dem Hauptbaum lösen.
$mainRounds = [];
$thirdPlace = null;
foreach ($bracket as $round) {
    if ($round['name'] === 'Match for third place') {
        $thirdPlace = $round['matches'][0] ?? null;
    } else {
        $mainRounds[] = $round;
    }
}

// JS-Modell aufbauen (num -> feeds + zwei Slots).
$model = [];
foreach ($mainRounds as $round) {
    foreach ($round['matches'] as $bm) {
        if ($bm['num'] === null) { continue; }
        $model[(string) $bm['num']] = [
            'feeds' => $bm['feeds'],   // ['to'=>num,'slot'=>1|2] oder null
            'slots' => [
                ['feedFrom' => $bm['team1']['feedFrom'] ?? null, 'base' => $jsBase($bm['team1'])],
                ['feedFrom' => $bm['team2']['feedFrom'] ?? null, 'base' => $jsBase($bm['team2'])],
            ],
        ];
    }
}

/** Eine Mannschaftszeile (Button) rendern. */
$side = function (array $bm, int $slot) use ($sideContent): string {
    $tm = $bm['team' . $slot];
    $clickable = ($tm['type'] ?? '') === 'team';
    $score = $bm['score' . $slot];
    ob_start(); ?>
    <button type="button" class="ko-side<?= $clickable ? ' is-clickable' : '' ?>"
            data-num="<?= (int) $bm['num'] ?>" data-slot="<?= $slot ?>"<?= $clickable ? '' : ' disabled' ?>>
        <span class="ko-team"><?= $sideContent($tm) ?></span>
        <?php if ($score !== null): ?><span class="ko-score"><?= (int) $score ?></span><?php endif; ?>
    </button>
    <?php return (string) ob_get_clean();
};

$renderMatch = function (array $bm) use ($side): string {
    ob_start(); ?>
    <div class="ko-match" data-num="<?= (int) $bm['num'] ?>">
        <div class="ko-time"><?= e(fmt_datetime($bm['kickoff'], 'd.m. H:i')) ?><?= ko_decided_badge($bm) ?></div>
        <?= $side($bm, 1) ?>
        <?= $side($bm, 2) ?>
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

<!-- ===================== Turnierbaum (interaktiv) ===================== -->
<section class="section">
    <h2 class="section-title"><?= e(t('tour.bracket')) ?></h2>
    <?php if (!$mainRounds): ?>
        <p class="muted"><?= e(t('tour.bracket_empty')) ?></p>
    <?php else: ?>
        <div class="bracket-toolbar">
            <p class="muted sim-hint"><?= e(t('tour.sim_hint')) ?></p>
            <button type="button" class="btn btn-small" id="bracket-reset" hidden><?= e(t('tour.sim_reset')) ?></button>
        </div>

        <div class="bracket-wide">
        <!-- Runden-Buttons: direkt zur Runde springen -->
        <div class="round-tabs" id="round-tabs" role="tablist">
            <?php foreach ($mainRounds as $i => $round): ?>
                <button type="button" class="round-tab<?= $i === 0 ? ' is-active' : '' ?>" data-idx="<?= $i ?>">
                    <?= e(t('round.' . $round['name'])) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="bracket-scroll" id="bracket-scroll">
            <div class="bracket" id="bracket">
                <svg class="bracket-lines" id="bracket-lines" aria-hidden="true"></svg>
                <?php foreach ($mainRounds as $round): ?>
                    <div class="bracket-round">
                        <div class="round-head"><?= e(t('round.' . $round['name'])) ?></div>
                        <?php foreach ($round['matches'] as $bm): ?>
                            <div class="ko-slot"><?= $renderMatch($bm) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        </div><!-- /.bracket-wide -->

        <?php if ($thirdPlace): ?>
            <div class="third-place">
                <div class="round-head round-head-third"><?= e(t('round.Match for third place')) ?></div>
                <div class="ko-slot"><?= $renderMatch($thirdPlace) ?></div>
            </div>
        <?php endif; ?>

        <script type="application/json" id="bracket-model"><?= json_encode($model, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
        <script type="application/json" id="bracket-i18n"><?= json_encode(['open' => t('tour.open'), 'proj' => t('tour.proj')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <?php endif; ?>
</section>
