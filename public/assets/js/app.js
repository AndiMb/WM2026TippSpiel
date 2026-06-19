/* =====================================================================
   WM 2026 Tippspiel – kleines Vanilla-JS für mehr Bedienkomfort.
   Die App funktioniert auch komplett ohne JavaScript (Progressive Enhancement).
   ===================================================================== */
(function () {
    'use strict';

    // Eingaben auf 0..99 begrenzen (zusätzlich zur HTML-Validierung).
    document.addEventListener('input', function (ev) {
        var el = ev.target;
        if (!el.classList || !el.classList.contains('score-input')) return;
        var v = el.value.replace(/[^0-9]/g, '');
        if (v.length > 2) v = v.slice(0, 2);
        if (v !== '' && parseInt(v, 10) > 99) v = '99';
        if (v !== el.value) el.value = v;
    });

    // Tippformular: Hinweis bei ungespeicherten Änderungen.
    var betForm = document.getElementById('bet-form');
    if (betForm) {
        var dirty = false;
        betForm.addEventListener('input', function () { dirty = true; });
        betForm.addEventListener('submit', function () { dirty = false; });
        window.addEventListener('beforeunload', function (e) {
            if (dirty) { e.preventDefault(); e.returnValue = ''; }
        });
    }

    // Flash-Meldungen nach einigen Sekunden sanft ausblenden.
    document.querySelectorAll('.flash-success').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 4000);
    });
})();
