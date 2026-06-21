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

    // Turnierbaum: Runden-Tabs zum stufenweisen Springen + aktive Runde markieren.
    var bracket = document.getElementById('bracket');
    var tabs = document.getElementById('round-tabs');
    if (bracket && tabs) {
        var rounds = bracket.querySelectorAll('.bracket-round');
        var tabBtns = tabs.querySelectorAll('.round-tab');

        function setActive(idx) {
            tabBtns.forEach(function (b) {
                b.classList.toggle('is-active', parseInt(b.dataset.idx, 10) === idx);
            });
        }

        // Klick auf einen Tab: passende Runde einrasten lassen.
        // getBoundingClientRect ist robust unabhängig vom offsetParent.
        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt(btn.dataset.idx, 10);
                var target = rounds[idx];
                if (target) {
                    // Direktes Setzen (kein 'smooth') – verträgt sich mit
                    // scroll-snap: mandatory und rastet sauber auf die Runde.
                    var delta = target.getBoundingClientRect().left - bracket.getBoundingClientRect().left;
                    bracket.scrollLeft = bracket.scrollLeft + delta;
                    setActive(idx);
                }
            });
        });

        // Beim Scrollen die aktuell sichtbare Runde als aktiv markieren.
        var ticking = false;
        bracket.addEventListener('scroll', function () {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(function () {
                var bRect = bracket.getBoundingClientRect();
                var center = bRect.left + bracket.clientWidth / 2;
                var best = 0, bestDist = Infinity;
                rounds.forEach(function (r, i) {
                    var rRect = r.getBoundingClientRect();
                    var d = Math.abs((rRect.left + rRect.width / 2) - center);
                    if (d < bestDist) { bestDist = d; best = i; }
                });
                setActive(best);
                ticking = false;
            });
        }, { passive: true });
    }
})();
