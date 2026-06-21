<?php /** @var array $upcoming @var array $betMap @var int $openCount @var array $standings @var ?array $me @var array $recent */ ?>

<h1 class="page-title"><?= e(t('dash.hello', ['name' => $user['display_name']])) ?></h1>

<!-- Punkteübersicht -->
<div class="stat-grid">
    <div class="stat-card stat-points">
        <div class="stat-num"><?= $me ? (int) $me['points'] : 0 ?></div>
        <div class="stat-lbl"><?= e(t('dash.points')) ?></div>
    </div>
    <div class="stat-card stat-rank">
        <div class="stat-num"><?= $me ? '#' . (int) $me['rank'] : '–' ?></div>
        <div class="stat-lbl"><?= e(t('dash.rank')) ?></div>
    </div>
    <div class="stat-card <?= $openCount > 0 ? 'stat-open' : '' ?>">
        <div class="stat-num"><?= (int) $openCount ?></div>
        <div class="stat-lbl"><?= e(t('dash.open_bets')) ?></div>
    </div>
</div>

<?php if ($openCount > 0): ?>
    <a class="cta-banner" href="<?= e(url('/tippen')) ?>">
        ✏️ <?= t('dash.cta_open', ['count' => '<strong>' . (int) $openCount . '</strong>']) ?>
    </a>
<?php endif; ?>

<a class="cta-banner cta-secondary" href="<?= e(url('/turnier')) ?>">
    <?= e(t('dash.cta_tournament')) ?>
</a>

<!-- Nächste Spiele -->
<section class="section">
    <h2 class="section-title"><?= e(t('dash.next_matches')) ?></h2>
    <?php if (!$upcoming): ?>
        <p class="muted"><?= e(t('dash.no_upcoming')) ?></p>
    <?php else: ?>
        <div class="match-list">
            <?php foreach ($upcoming as $m):
                $bet = $betMap[(int) $m['id']] ?? null; ?>
                <div class="match-row">
                    <div class="match-info">
                        <div class="match-teams">
                            <span class="team"><?= flag($m['team1']) ?> <?= e(tname($m['team1'])) ?></span>
                            <span class="vs">–</span>
                            <span class="team"><?= flag($m['team2']) ?> <?= e(tname($m['team2'])) ?></span>
                        </div>
                        <div class="match-meta"><?= e(fmt_datetime($m['kickoff'])) ?> <?= e(t('common.clock')) ?></div>
                    </div>
                    <div class="match-bet">
                        <?php if ($bet): ?>
                            <span class="bet-chip"><?= e(t('dash.your_bet', ['bet' => (int) $bet['pred1'] . ':' . (int) $bet['pred2']])) ?></span>
                        <?php else: ?>
                            <a class="btn btn-small btn-primary" href="<?= e(url('/tippen')) ?>"><?= e(t('dash.bet_now')) ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Aktuelle Rangliste (Top 5) -->
<section class="section">
    <h2 class="section-title"><?= e(t('dash.standings_top')) ?> <span class="muted"><?= e(t('dash.standings_top5')) ?></span></h2>
    <?php if (!$standings): ?>
        <p class="muted"><?= e(t('standings.none')) ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th><?= e(t('table.rank')) ?></th><th><?= e(t('table.player')) ?></th><th class="num"><?= e(t('table.points')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($standings as $row): ?>
                <tr class="<?= $row['user_id'] === $user['id'] ? 'me-row' : '' ?>">
                    <td><?= (int) $row['rank'] ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td class="num"><strong><?= (int) $row['points'] ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <a class="link-more" href="<?= e(url('/rangliste')) ?>"><?= e(t('dash.full_standings')) ?></a>
    <?php endif; ?>
</section>

<?php if ($recent): ?>
<section class="section">
    <h2 class="section-title"><?= e(t('dash.recent')) ?></h2>
    <a class="link-more" href="<?= e(url('/tipps')) ?>"><?= e(t('dash.see_others')) ?></a>
    <div class="match-list">
        <?php foreach ($recent as $m):
            $bet = $betMap[(int) $m['id']] ?? null; ?>
            <div class="match-row">
                <div class="match-info">
                    <div class="match-teams">
                        <span class="team"><?= flag($m['team1']) ?> <?= e(tname($m['team1'])) ?></span>
                        <span class="score-final"><?= (int) $m['score1'] ?>:<?= (int) $m['score2'] ?></span>
                        <span class="team"><?= flag($m['team2']) ?> <?= e(tname($m['team2'])) ?></span>
                    </div>
                    <div class="match-meta"><?= e(fmt_datetime($m['kickoff'], 'd.m.Y')) ?></div>
                </div>
                <div class="match-bet">
                    <?php if ($bet): ?>
                        <span class="bet-chip pts-<?= $bet['points'] === null ? 'na' : (int) $bet['points'] ?>">
                            <?= (int) $bet['pred1'] ?>:<?= (int) $bet['pred2'] ?>
                            <?php if ($bet['points'] !== null): ?>· <?= (int) $bet['points'] ?> <?= e(t('common.points_short')) ?><?php endif; ?>
                        </span>
                    <?php else: ?>
                        <span class="bet-chip pts-0"><?= e(t('dash.no_bet')) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
