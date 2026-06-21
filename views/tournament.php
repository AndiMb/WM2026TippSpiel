<?php /** @var array $groups @var array $bestThirds @var array $bracket */ ?>
<?php
/** Kleiner Helfer: ein aufgelöstes Bracket-Team anzeigen (Flagge + Name oder Label). */
$slot = function (array $t): string {
    if (($t['type'] ?? '') === 'team' && !empty($t['en'])) {
        $html = flag($t['en']) . ' ' . e(tname($t['en']));
        if (!empty($t['proj'])) {
            $html .= ' <span class="proj" title="laut aktuellem Tabellenstand">(vorauss.)</span>';
        }
        return $html;
    }
    return '<span class="slot-label">' . e($t['label'] ?? '?') . '</span>';
};
?>

<h1 class="page-title">🏟️ Gruppen &amp; Turnierbaum</h1>

<!-- ===================== Gruppentabellen ===================== -->
<section class="section">
    <h2 class="section-title">Gruppentabellen</h2>
    <?php if (!$groups): ?>
        <p class="muted">Noch keine Gruppenspiele vorhanden.</p>
    <?php else: ?>
    <div class="group-grid">
        <?php foreach ($groups as $g): ?>
            <div class="card group-card">
                <h3 class="group-title"><?= e($g['name']) ?></h3>
                <table class="table group-table">
                    <thead>
                        <tr>
                            <th>#</th><th>Team</th>
                            <th class="num" title="Spiele">Sp</th>
                            <th class="num" title="Tordifferenz">Diff</th>
                            <th class="num" title="Punkte">Pkt</th>
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
        <span class="dot q-top"></span> Platz 1–2 (weiter) ·
        <span class="dot q-third"></span> Platz 3 (evtl. weiter als bester Dritter)
    </p>
    <?php endif; ?>
</section>

<!-- ===================== Beste Gruppendritte ===================== -->
<?php if ($bestThirds): ?>
<section class="section">
    <h2 class="section-title">Beste Gruppendritte <span class="muted">(8 kommen weiter)</span></h2>
    <div class="card">
        <table class="table">
            <thead><tr><th>#</th><th>Gruppe</th><th>Team</th><th class="num">Diff</th><th class="num">Pkt</th></tr></thead>
            <tbody>
            <?php foreach ($bestThirds as $i => $t): ?>
                <tr class="<?= $t['qualified'] ? 'q-top' : '' ?>">
                    <td class="pos"><?= $i + 1 ?></td>
                    <td><?= e(str_replace('Group ', '', $t['group'])) ?></td>
                    <td class="t"><?= flag($t['team']) ?> <?= e(tname($t['team'])) ?></td>
                    <td class="num"><?= ($t['gd'] > 0 ? '+' : '') . (int) $t['gd'] ?></td>
                    <td class="num"><strong><?= (int) $t['pts'] ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<!-- ===================== Turnierbaum (KO) ===================== -->
<section class="section">
    <h2 class="section-title">Turnierbaum (KO-Phase)</h2>
    <?php if (!$bracket): ?>
        <p class="muted">Der Turnierbaum erscheint, sobald die KO-Spiele importiert sind.</p>
    <?php else: ?>
        <p class="muted intro">
            Im Sechzehntelfinale werden die Gruppenplätze laut aktuellem
            Tabellenstand angezeigt <span class="proj">(vorauss.)</span>.
        </p>
        <div class="bracket-scroll">
            <div class="bracket">
                <?php foreach ($bracket as $round): ?>
                    <div class="bracket-round">
                        <h3 class="round-title"><?= e($round['title']) ?></h3>
                        <?php foreach ($round['matches'] as $bm): ?>
                            <div class="ko-match">
                                <?php if ($bm['num']): ?><div class="ko-num">Spiel <?= (int) $bm['num'] ?></div><?php endif; ?>
                                <div class="ko-side">
                                    <span class="ko-team"><?= $slot($bm['team1']) ?></span>
                                    <span class="ko-score"><?= $bm['score1'] !== null ? (int) $bm['score1'] : '' ?></span>
                                </div>
                                <div class="ko-side">
                                    <span class="ko-team"><?= $slot($bm['team2']) ?></span>
                                    <span class="ko-score"><?= $bm['score2'] !== null ? (int) $bm['score2'] : '' ?></span>
                                </div>
                                <div class="ko-meta"><?= e(fmt_datetime($bm['kickoff'], 'd.m. H:i')) ?> Uhr</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
