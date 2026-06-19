<?php /** @var array $upcoming @var array $betMap @var int $openCount @var array $standings @var ?array $me @var array $recent */ ?>

<h1 class="page-title">Hallo, <?= e($user['display_name']) ?>! 👋</h1>

<!-- Punkteübersicht -->
<div class="stat-grid">
    <div class="stat-card stat-points">
        <div class="stat-num"><?= $me ? (int) $me['points'] : 0 ?></div>
        <div class="stat-lbl">Punkte</div>
    </div>
    <div class="stat-card stat-rank">
        <div class="stat-num"><?= $me ? '#' . (int) $me['rank'] : '–' ?></div>
        <div class="stat-lbl">Platz</div>
    </div>
    <div class="stat-card <?= $openCount > 0 ? 'stat-open' : '' ?>">
        <div class="stat-num"><?= (int) $openCount ?></div>
        <div class="stat-lbl">Offene Tipps</div>
    </div>
</div>

<?php if ($openCount > 0): ?>
    <a class="cta-banner" href="<?= e(url('/tippen')) ?>">
        ✏️ Du hast <strong><?= (int) $openCount ?></strong> Spiel(e) noch nicht getippt – jetzt tippen!
    </a>
<?php endif; ?>

<!-- Nächste Spiele -->
<section class="section">
    <h2 class="section-title">Nächste Spiele</h2>
    <?php if (!$upcoming): ?>
        <p class="muted">Aktuell sind keine kommenden Spiele eingetragen.</p>
    <?php else: ?>
        <div class="match-list">
            <?php foreach ($upcoming as $m):
                $bet = $betMap[(int) $m['id']] ?? null; ?>
                <div class="match-row">
                    <div class="match-info">
                        <div class="match-teams">
                            <span class="team"><?= e($m['team1']) ?></span>
                            <span class="vs">–</span>
                            <span class="team"><?= e($m['team2']) ?></span>
                        </div>
                        <div class="match-meta"><?= e(fmt_datetime($m['kickoff'])) ?> Uhr</div>
                    </div>
                    <div class="match-bet">
                        <?php if ($bet): ?>
                            <span class="bet-chip">Dein Tipp: <?= (int) $bet['pred1'] ?>:<?= (int) $bet['pred2'] ?></span>
                        <?php else: ?>
                            <a class="btn btn-small btn-primary" href="<?= e(url('/tippen')) ?>">Tippen</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Aktuelle Rangliste (Top 5) -->
<section class="section">
    <h2 class="section-title">Rangliste <span class="muted">(Top 5)</span></h2>
    <?php if (!$standings): ?>
        <p class="muted">Noch keine Wertung vorhanden.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>#</th><th>Spieler</th><th class="num">Punkte</th></tr></thead>
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
        <a class="link-more" href="<?= e(url('/rangliste')) ?>">Komplette Rangliste →</a>
    <?php endif; ?>
</section>

<?php if ($recent): ?>
<section class="section">
    <h2 class="section-title">Letzte Ergebnisse</h2>
    <div class="match-list">
        <?php foreach ($recent as $m):
            $bet = $betMap[(int) $m['id']] ?? null; ?>
            <div class="match-row">
                <div class="match-info">
                    <div class="match-teams">
                        <span class="team"><?= e($m['team1']) ?></span>
                        <span class="score-final"><?= (int) $m['score1'] ?>:<?= (int) $m['score2'] ?></span>
                        <span class="team"><?= e($m['team2']) ?></span>
                    </div>
                    <div class="match-meta"><?= e(fmt_datetime($m['kickoff'], 'd.m.Y')) ?></div>
                </div>
                <div class="match-bet">
                    <?php if ($bet): ?>
                        <span class="bet-chip pts-<?= $bet['points'] === null ? 'na' : (int) $bet['points'] ?>">
                            <?= (int) $bet['pred1'] ?>:<?= (int) $bet['pred2'] ?>
                            <?php if ($bet['points'] !== null): ?>· <?= (int) $bet['points'] ?> P<?php endif; ?>
                        </span>
                    <?php else: ?>
                        <span class="bet-chip pts-0">kein Tipp</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
