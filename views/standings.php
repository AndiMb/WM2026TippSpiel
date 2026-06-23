<?php /** @var array $standings @var bool $bonusEnabled @var string $scoringMode @var int $meId */ ?>

<h1 class="page-title"><?= e(t('standings.title')) ?></h1>

<a class="btn btn-small" href="<?= e(url('/tipps')) ?>"><?= e(t('standings.see_others')) ?></a>

<p class="muted intro">
    <?php if ($scoringMode === 'since_join'): ?>
        <?= e(t('standings.intro_join')) ?>
    <?php else: ?>
        <?= e(t('standings.intro_all')) ?>
    <?php endif; ?>
</p>

<?php if (!$standings): ?>
    <p class="muted"><?= e(t('standings.none')) ?></p>
<?php else: ?>
<div class="table-scroll">
<table class="table table-standings">
    <thead>
        <tr>
            <th><?= e(t('table.rank')) ?></th>
            <th><?= e(t('table.player')) ?></th>
            <th class="num"><?= e(t('table.points')) ?></th>
            <?php if ($bonusEnabled): ?><th class="num hide-sm"><?= e(t('standings.col_bonus')) ?></th><?php endif; ?>
            <th class="num"><?= e(t('standings.col_exact')) ?></th>
            <th class="num"><?= e(t('standings.col_diff')) ?></th>
            <th class="num"><?= e(t('standings.col_tendency')) ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($standings as $row):
        $medal = ['1' => '🥇', '2' => '🥈', '3' => '🥉'][(string) $row['rank']] ?? ''; ?>
        <tr class="<?= $row['user_id'] === $meId ? 'me-row' : '' ?>">
            <td class="rank-cell"><?= $medal ?: (int) $row['rank'] ?></td>
            <td><?= e($row['name']) ?><?= $row['user_id'] === $meId ? ' <span class="you-tag">' . e(t('standings.you')) . '</span>' : '' ?></td>
            <td class="num"><strong><?= (int) $row['points'] ?></strong></td>
            <?php if ($bonusEnabled): ?><td class="num hide-sm"><?= (int) $row['bonus_pts'] ?></td><?php endif; ?>
            <td class="num"><?= (int) $row['exact'] ?></td>
            <td class="num"><?= (int) $row['diff'] ?></td>
            <td class="num"><?= (int) $row['tendency'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<p class="legend muted"><?= t('standings.legend') ?></p>
<?php endif; ?>
