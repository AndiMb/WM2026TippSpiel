<?php /** @var array $matches @var int $meId */ ?>

<h1 class="page-title"><?= e(t('tips.title')) ?></h1>
<p class="muted intro"><?= e(t('tips.intro')) ?></p>

<?php if (!$matches): ?>
    <div class="empty">
        <div class="empty-ico">🔒</div>
        <p><?= e(t('tips.empty_title')) ?></p>
        <p class="muted"><?= e(t('tips.empty_sub')) ?></p>
    </div>
<?php else: ?>
    <?php foreach ($matches as $m):
        $finished = $m['status'] === 'finished' && $m['score1'] !== null; ?>
        <section class="card tip-block">
            <div class="tip-head">
                <div class="tip-teams">
                    <?= flag($m['team1']) ?> <?= e(tname($m['team1'])) ?>
                    <span class="vs">–</span>
                    <?= flag($m['team2']) ?> <?= e(tname($m['team2'])) ?>
                </div>
                <div class="tip-res">
                    <?php if ($finished): ?>
                        <span class="score-final"><?= (int) $m['score1'] ?>:<?= (int) $m['score2'] ?><?= ko_decided_badge($m) ?></span>
                    <?php else: ?>
                        <span class="status status-live"><?= e(t('tips.live')) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="tip-meta muted"><?= e(fmt_datetime($m['kickoff'])) ?> <?= e(t('common.clock')) ?></div>

            <table class="table tip-table">
                <tbody>
                <?php foreach ($m['tips'] as $t): ?>
                    <tr class="<?= $t['user_id'] === $meId ? 'me-row' : '' ?>">
                        <td><?= e($t['name']) ?><?= $t['user_id'] === $meId ? ' <span class="you-tag">' . e(t('standings.you')) . '</span>' : '' ?></td>
                        <td class="num tip-pred">
                            <?php if ($t['has_bet']): ?>
                                <?= (int) $t['pred1'] ?>:<?= (int) $t['pred2'] ?>
                            <?php else: ?>
                                <span class="muted">–</span>
                            <?php endif; ?>
                        </td>
                        <td class="num">
                            <?php if ($t['points'] !== null): ?>
                                <span class="bet-chip pts-<?= (int) $t['points'] ?>"><?= (int) $t['points'] ?> <?= e(t('common.points_short')) ?></span>
                            <?php elseif ($t['has_bet'] && $finished): ?>
                                <span class="bet-chip pts-0">0 <?= e(t('common.points_short')) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
