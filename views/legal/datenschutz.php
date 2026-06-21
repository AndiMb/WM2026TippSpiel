<?php /** @var ?string $operatorName @var ?string $operatorAddress @var ?string $operatorEmail */ ?>

<a class="back-link" href="<?= e(url('/login')) ?>">← Zurück</a>
<h1 class="page-title">Datenschutzerklärung</h1>

<section class="card legal-text">
    <h2 class="section-title">1. Verantwortlicher</h2>
    <p>
        <?php if (!empty($operatorName)): ?><strong><?= e($operatorName) ?></strong><br><?php endif; ?>
        <?php if (!empty($operatorAddress)): ?><?= nl2br(e($operatorAddress)) ?><br><?php endif; ?>
        <?php if (!empty($operatorEmail)): ?>E-Mail: <a href="mailto:<?= e($operatorEmail) ?>"><?= e($operatorEmail) ?></a><?php endif; ?>
        <?php if (empty($operatorName) && empty($operatorEmail)): ?>
            <span class="muted">(Betreiberangaben noch nicht hinterlegt – siehe Admin → Einstellungen.)</span>
        <?php endif; ?>
    </p>

    <h2 class="section-title">2. Welche Daten wir verarbeiten</h2>
    <ul>
        <li><strong>Kontodaten:</strong> Benutzername, Anzeigename und ein
            <em>verschlüsselt gespeichertes</em> (gehashtes) Passwort. Die Konten
            werden vom Administrator angelegt.</li>
        <li><strong>Spieldaten:</strong> die von dir abgegebenen Tipps, deine
            Punkte sowie Erstellungs-/Änderungszeitpunkte.</li>
        <li><strong>Technisch notwendiges Cookie:</strong> ein Sitzungs-Cookie
            („TIPP_SID"), um dich während der Nutzung angemeldet zu halten.</li>
    </ul>
    <p class="muted">
        Es findet <strong>kein Tracking</strong> statt. Es werden keine
        Analyse-Dienste, keine Werbe-Cookies und keine externen Schriftarten oder
        Skripte von Dritten geladen.
    </p>

    <h2 class="section-title">3. Zweck und Rechtsgrundlage</h2>
    <p>
        Die Daten werden ausschließlich zur Durchführung des privaten Tippspiels
        verarbeitet (Anmeldung, Abgabe und Auswertung von Tipps, Rangliste).
        Rechtsgrundlage ist – soweit die DSGVO Anwendung findet – Art. 6 Abs. 1
        lit. b und f DSGVO. Bei rein privater, familiärer Nutzung kann die
        Haushaltsausnahme (Art. 2 Abs. 2 lit. c DSGVO) greifen.
    </p>

    <h2 class="section-title">4. Cookies / Einwilligung</h2>
    <p>
        Es wird lediglich ein <strong>technisch erforderliches</strong>
        Sitzungs-Cookie gesetzt. Hierfür ist nach § 25 Abs. 2 TDDDG keine
        Einwilligung erforderlich; ein Cookie-Banner entfällt.
    </p>

    <h2 class="section-title">5. Empfänger / Hosting</h2>
    <p>
        Die Anwendung läuft auf dem Server des Betreibers bzw. dessen Hosting-
        Anbieters (Auftragsverarbeitung). Der Spielplan wird <strong>serverseitig</strong>
        aus einer öffentlichen Quelle (OpenFootball) geladen – dabei werden
        <strong>keine personenbezogenen Daten der Teilnehmer</strong> an Dritte
        übermittelt.
    </p>

    <h2 class="section-title">6. Speicherdauer</h2>
    <p>
        Die Daten werden gespeichert, solange das Konto besteht bzw. das
        Tippspiel läuft, und auf Wunsch oder nach Turnierende gelöscht.
    </p>

    <h2 class="section-title">7. Deine Rechte</h2>
    <p>
        Du hast das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der
        Verarbeitung sowie Datenübertragbarkeit und ein Beschwerderecht bei einer
        Aufsichtsbehörde. Für die Löschung deines Kontos wende dich an den
        Administrator<?php if (!empty($operatorEmail)): ?> (<a href="mailto:<?= e($operatorEmail) ?>"><?= e($operatorEmail) ?></a>)<?php endif; ?>.
    </p>

    <p class="muted legal-disclaimer">
        Diese Vorlage dient als Ausgangspunkt und ersetzt keine Rechtsberatung.
        Bitte an die konkrete Nutzung anpassen.
    </p>
</section>
