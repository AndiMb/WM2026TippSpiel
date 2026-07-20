<?php /** @var array $standings @var int $meId */ ?>
<?php
$medalOf = function (int $rank): string {
    return ['1' => '🥇', '2' => '🥈', '3' => '🥉'][(string) $rank] ?? '';
};
$total = count($standings);

// Für die Enthüllung vom letzten zum ersten Platz umdrehen; jede Zeile
// bekommt hier schon Medaille + "das bist du" fertig aufbereitet.
$reveal = [];
foreach (array_reverse($standings) as $row) {
    $reveal[] = [
        'rank'   => (int) $row['rank'],
        'medal'  => $medalOf((int) $row['rank']),
        'name'   => $row['name'],
        'points' => (int) $row['points'],
        'isMe'   => $row['user_id'] === $meId,
    ];
}
?>

<a class="back-link" href="<?= e(url('/rangliste')) ?>"><?= e(t('ceremony.back')) ?></a>
<h1 class="page-title"><?= e(t('ceremony.title')) ?></h1>

<?php if (!$standings): ?>
    <p class="muted"><?= e(t('standings.none')) ?></p>
<?php else: ?>

<p class="muted intro" id="ceremony-subtitle">
    <?= e(t('ceremony.subtitle', ['count' => $total])) ?>
</p>

<div class="card ceremony-card" id="ceremony-card">
    <div class="ceremony-progress" id="ceremony-progress" aria-live="polite"></div>

    <div class="ceremony-stage" id="ceremony-stage" aria-live="polite">
        <p class="muted"><?= e(t('ceremony.intro_hint')) ?></p>
    </div>

    <div class="ceremony-controls">
        <button type="button" class="btn btn-primary btn-lg" id="ceremony-next"><?= e(t('ceremony.start')) ?></button>
        <button type="button" class="btn btn-small" id="ceremony-skip"><?= e(t('ceremony.skip')) ?></button>
    </div>

    <ul class="ceremony-list" id="ceremony-list"></ul>
</div>

<div class="ceremony-finale" id="ceremony-finale" hidden>
    <h2 class="section-title"><?= e(t('ceremony.finished_title')) ?></h2>
    <p class="muted"><?= e(t('ceremony.finished_sub', ['count' => $total])) ?></p>
    <button type="button" class="btn" id="ceremony-restart"><?= e(t('ceremony.restart')) ?></button>
</div>

<canvas id="ceremony-confetti" class="ceremony-confetti" aria-hidden="true"></canvas>

<script type="application/json" id="ceremony-data"><?= json_encode($reveal, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script type="application/json" id="ceremony-i18n"><?= json_encode([
    'rankOf'  => t('ceremony.rank_of'),
    'next'    => t('ceremony.next'),
    'you'     => t('standings.you'),
    'points'  => t('common.points_short'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<?php endif; ?>
