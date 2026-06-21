<?php /** @var ?string $operatorName @var ?string $operatorAddress @var ?string $operatorEmail */ ?>

<a class="back-link" href="<?= e(url('/login')) ?>">← Zurück</a>
<h1 class="page-title">Impressum</h1>

<section class="card legal-text">
    <?php if (empty($operatorName) && empty($operatorAddress) && empty($operatorEmail)): ?>
        <div class="flash flash-error">
            Es wurden noch keine Betreiberangaben hinterlegt. Der Administrator kann
            diese unter <strong>Admin → Einstellungen → Rechtliches</strong> eintragen.
        </div>
    <?php endif; ?>

    <h2 class="section-title">Angaben gemäß § 5 DDG</h2>
    <p>
        <?php if (!empty($operatorName)): ?>
            <strong><?= e($operatorName) ?></strong><br>
        <?php endif; ?>
        <?php if (!empty($operatorAddress)): ?>
            <?= nl2br(e($operatorAddress)) ?>
        <?php endif; ?>
    </p>

    <?php if (!empty($operatorEmail)): ?>
        <h2 class="section-title">Kontakt</h2>
        <p>E-Mail: <a href="mailto:<?= e($operatorEmail) ?>"><?= e($operatorEmail) ?></a></p>
    <?php endif; ?>

    <h2 class="section-title">Hinweise</h2>
    <p class="muted">
        Dieses Tippspiel wird <strong>privat und ohne Gewinnerzielungsabsicht</strong>
        im Familien-/Bekanntenkreis betrieben. Es werden <strong>keine Geldeinsätze</strong>
        erhoben; es handelt sich daher nicht um Glücksspiel im Sinne des
        Glücksspielstaatsvertrags.
    </p>
    <p class="muted">
        Dieses Angebot steht in <strong>keiner Verbindung zur FIFA</strong> oder zu
        offiziellen Veranstaltern der Fußball-Weltmeisterschaft. Verwendete
        Mannschafts- und Wettbewerbsbezeichnungen dienen ausschließlich der
        Beschreibung. Spieldaten stammen aus der gemeinfreien Quelle OpenFootball.
    </p>
</section>
