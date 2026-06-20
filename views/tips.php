<?php /** @var array $matches @var int $meId */ ?>

<h1 class="page-title">👀 Tipps der anderen</h1>
<p class="muted intro">
    Sichtbar sind nur Spiele, die schon angepfiffen wurden – vorher bleibt jeder
    Tipp geheim.
</p>

<?php if (!$matches): ?>
    <div class="empty">
        <div class="empty-ico">🔒</div>
        <p>Noch keine angepfiffenen Spiele.</p>
        <p class="muted">Sobald das erste Spiel läuft, erscheinen hier die Tipps aller Mitspieler.</p>
    </div>
<?php else: ?>
    <?php foreach ($matches as $m):
        $finished = $m['status'] === 'finished' && $m['score1'] !== null; ?>
        <section class="card tip-block">
            <div class="tip-head">
                <div class="tip-teams">
                    <?= e(tname($m['team1'])) ?> – <?= e(tname($m['team2'])) ?>
                </div>
                <div class="tip-res">
                    <?php if ($finished): ?>
                        <span class="score-final"><?= (int) $m['score1'] ?>:<?= (int) $m['score2'] ?></span>
                    <?php else: ?>
                        <span class="status status-live">läuft</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="tip-meta muted"><?= e(fmt_datetime($m['kickoff'])) ?> Uhr</div>

            <table class="table tip-table">
                <tbody>
                <?php foreach ($m['tips'] as $t): ?>
                    <tr class="<?= $t['user_id'] === $meId ? 'me-row' : '' ?>">
                        <td><?= e($t['name']) ?><?= $t['user_id'] === $meId ? ' <span class="you-tag">(du)</span>' : '' ?></td>
                        <td class="num tip-pred">
                            <?php if ($t['has_bet']): ?>
                                <?= (int) $t['pred1'] ?>:<?= (int) $t['pred2'] ?>
                            <?php else: ?>
                                <span class="muted">–</span>
                            <?php endif; ?>
                        </td>
                        <td class="num">
                            <?php if ($t['points'] !== null): ?>
                                <span class="bet-chip pts-<?= (int) $t['points'] ?>"><?= (int) $t['points'] ?> P</span>
                            <?php elseif ($t['has_bet'] && $finished): ?>
                                <span class="bet-chip pts-0">0 P</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
