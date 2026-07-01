<?php /** @var array $users @var ?array $selected @var array $matches @var array $betMap @var string $filter @var int $missingCount */ ?>
<?php
/** Ist ein Team schon eine echte Mannschaft (kein KO-Platzhalter)? */
$isRealTeam = function (?string $t): bool {
    $t = (string) $t;
    if ($t === '' || preg_match('/^[WL]\d+$/', $t) || preg_match('/^[123][A-L]/', $t)) {
        return false;
    }
    return true;
};
?>

<a class="back-link" href="<?= e(url('/admin')) ?>">← Adminbereich</a>
<h1 class="page-title">📝 Tipps nachtragen</h1>

<p class="muted">
    Hier kannst du für einen Spieler verspätet Tipps ergänzen oder korrigieren –
    auch für Spiele, deren Anpfiff schon vorbei ist. Bei bereits beendeten Spielen
    werden die Punkte sofort neu berechnet.
</p>

<section class="section card">
    <form method="get" action="<?= e(url('/admin/tipps')) ?>" class="form-inline">
        <label class="field">
            <span class="field-label">Spieler</span>
            <select class="input" name="user" onchange="this.form.submit()">
                <option value="">– bitte wählen –</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int) $u['id'] ?>" <?= $selected && (int) $selected['id'] === (int) $u['id'] ? 'selected' : '' ?>>
                        <?= e($u['display_name']) ?> (<?= e($u['username']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <noscript><button class="btn" type="submit">Anzeigen</button></noscript>
    </form>
</section>

<?php if ($selected): ?>
    <section class="section">
        <div class="tip-head">
            <h2 class="section-title">Tipps von <?= e($selected['display_name']) ?></h2>
            <div class="filter-toggle">
                <a class="btn btn-small <?= $filter === 'fehlend' ? 'btn-primary' : '' ?>"
                   href="<?= e(url('/admin/tipps?user=' . (int) $selected['id'] . '&filter=fehlend')) ?>">
                    Nur fehlende (<?= (int) $missingCount ?>)
                </a>
                <a class="btn btn-small <?= $filter === 'alle' ? 'btn-primary' : '' ?>"
                   href="<?= e(url('/admin/tipps?user=' . (int) $selected['id'] . '&filter=alle')) ?>">
                    Alle anzeigen
                </a>
            </div>
        </div>

        <form method="post" action="<?= e(url('/admin/tipps')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= (int) $selected['id'] ?>">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">

            <div class="match-admin-list">
            <?php
            $shown = 0;
            foreach ($matches as $m):
                $mid     = (int) $m['id'];
                $bet     = $betMap[$mid] ?? null;
                $missing = $bet === null;
                if ($filter === 'fehlend' && !$missing) {
                    continue;
                }
                $tippable = $isRealTeam($m['team1']) && $isRealTeam($m['team2']);
                if (!$tippable) {
                    continue; // KO-Spiele ohne feststehende Mannschaften überspringen
                }
                $shown++;
                $finished = $m['status'] === 'finished' && $m['score1'] !== null;
            ?>
                <div class="match-admin-row<?= $missing ? ' is-missing' : '' ?>">
                    <div class="ma-info">
                        <div class="ma-teams">
                            <?= flag($m['team1']) ?> <?= e(tname($m['team1'])) ?>
                            <span class="muted">–</span>
                            <?= flag($m['team2']) ?> <?= e(tname($m['team2'])) ?>
                        </div>
                        <div class="ma-meta">
                            <?= e(fmt_datetime($m['kickoff'])) ?>
                            <?php if (!empty($m['group_name'])): ?>· <?= e($m['group_name']) ?><?php endif; ?>
                            ·
                            <?php if ($finished): ?>
                                Ergebnis <strong><?= (int) $m['score1'] ?>:<?= (int) $m['score2'] ?></strong><?= ko_decided_badge($m) ?>
                            <?php else: ?>
                                <span class="status status-<?= e($m['status']) ?>"><?= e($m['status']) ?></span>
                            <?php endif; ?>
                            <?php if ($missing): ?><span class="badge badge-missing">kein Tipp</span><?php endif; ?>
                            <?php if ($bet && $bet['points'] !== null): ?>
                                <span class="bet-chip pts-<?= (int) $bet['points'] ?>"><?= (int) $bet['points'] ?> P</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ma-result">
                        <input class="score-input score-input-sm" type="number" min="0" max="99"
                               name="pred1[<?= $mid ?>]" value="<?= $bet ? (int) $bet['pred1'] : '' ?>" placeholder="–">
                        <span class="colon">:</span>
                        <input class="score-input score-input-sm" type="number" min="0" max="99"
                               name="pred2[<?= $mid ?>]" value="<?= $bet ? (int) $bet['pred2'] : '' ?>" placeholder="–">
                    </div>
                </div>
            <?php endforeach; ?>
            </div>

            <?php if ($shown === 0): ?>
                <p class="muted"><?= $filter === 'fehlend'
                    ? 'Für diesen Spieler fehlen keine Tipps. 🎉'
                    : 'Keine tippbaren Spiele vorhanden.' ?></p>
            <?php else: ?>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Tipps speichern</button>
                    <span class="muted">Leere Felder bleiben unverändert.</span>
                </div>
            <?php endif; ?>
        </form>
    </section>
<?php endif; ?>
