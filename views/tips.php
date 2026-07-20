<?php /** @var array $matches @var bool $bonusEnabled @var array $bonusOverview @var int $meId */ ?>

<h1 class="page-title"><?= e(t('tips.title')) ?></h1>
<p class="muted intro"><?= e(t('tips.intro')) ?></p>

<?php if ($bonusEnabled && $bonusOverview): ?>
    <section class="section">
        <h2 class="section-title"><?= e(t('tips.bonus_title')) ?></h2>
        <p class="muted intro"><?= e(t('tips.bonus_intro')) ?></p>
        <?php foreach ($bonusOverview as $q): ?>
            <section class="card tip-block">
                <div class="tip-head">
                    <div class="tip-teams">
                        <?= e($q['question']) ?>
                        <span class="muted">(<?= (int) $q['points'] ?> <?= e(t('common.points_short')) ?>)</span>
                    </div>
                    <?php if ($q['resolved']): ?>
                        <span class="score-final"><?= e($q['correct_answer']) ?></span>
                    <?php else: ?>
                        <span class="status status-live"><?= e(t('tips.bonus_open')) ?></span>
                    <?php endif; ?>
                </div>

                <table class="table tip-table">
                    <tbody>
                    <?php foreach ($q['answers'] as $a): ?>
                        <tr class="<?= $a['user_id'] === $meId ? 'me-row' : '' ?>">
                            <td><?= e($a['name']) ?><?= $a['user_id'] === $meId ? ' <span class="you-tag">' . e(t('standings.you')) . '</span>' : '' ?></td>
                            <td class="tip-pred">
                                <?php if ($a['has_answer']): ?>
                                    <?= e($a['answer']) ?>
                                <?php else: ?>
                                    <span class="muted">–</span>
                                <?php endif; ?>
                            </td>
                            <td class="num">
                                <?php if ($a['points'] !== null): ?>
                                    <span class="bet-chip pts-<?= (int) $a['points'] ?>"><?= (int) $a['points'] ?> <?= e(t('common.points_short')) ?></span>
                                <?php elseif ($a['has_answer'] && $q['resolved']): ?>
                                    <span class="bet-chip pts-0">0 <?= e(t('common.points_short')) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

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
                    <?php elseif ($m['status'] === 'live' && $m['score1'] !== null): ?>
                        <span class="score-final" data-live-score="<?= (int) $m['id'] ?>"><?= (int) $m['score1'] ?>:<?= (int) $m['score2'] ?></span>
                        <span class="live-badge" data-live-badge="<?= (int) $m['id'] ?>"><?= e(t('live.badge')) ?></span>
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
