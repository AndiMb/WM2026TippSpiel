<?php /** @var array $standings @var bool $bonusEnabled @var string $scoringMode @var int $meId */ ?>

<h1 class="page-title">🏆 Rangliste</h1>

<a class="btn btn-small" href="<?= e(url('/tipps')) ?>">👀 Tipps der anderen ansehen</a>

<p class="muted intro">
    <?php if ($scoringMode === 'since_join'): ?>
        Gewertet werden nur Spiele ab deinem Beitritt.
    <?php else: ?>
        Gewertet werden alle gespielten Spiele (verpasste Spiele = 0 Punkte).
    <?php endif; ?>
</p>

<?php if (!$standings): ?>
    <p class="muted">Noch keine Wertung vorhanden.</p>
<?php else: ?>
<div class="table-scroll">
<table class="table table-standings">
    <thead>
        <tr>
            <th>#</th>
            <th>Spieler</th>
            <th class="num">Punkte</th>
            <?php if ($bonusEnabled): ?><th class="num hide-sm">Bonus</th><?php endif; ?>
            <th class="num">Exakt</th>
            <th class="num">Tendenz</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($standings as $row):
        $medal = ['1' => '🥇', '2' => '🥈', '3' => '🥉'][(string) $row['rank']] ?? ''; ?>
        <tr class="<?= $row['user_id'] === $meId ? 'me-row' : '' ?>">
            <td class="rank-cell"><?= $medal ?: (int) $row['rank'] ?></td>
            <td><?= e($row['name']) ?><?= $row['user_id'] === $meId ? ' <span class="you-tag">(du)</span>' : '' ?></td>
            <td class="num"><strong><?= (int) $row['points'] ?></strong></td>
            <?php if ($bonusEnabled): ?><td class="num hide-sm"><?= (int) $row['bonus_pts'] ?></td><?php endif; ?>
            <td class="num"><?= (int) $row['exact'] ?></td>
            <td class="num"><?= (int) $row['tendency'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<p class="legend muted">
    <strong>Exakt</strong> = genaues Ergebnis getroffen ·
    <strong>Tendenz</strong> = richtige Tordifferenz/Sieger
</p>
<?php endif; ?>
