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

    // ===================================================================
    //  Tippformular: AUTOMATISCHES SPEICHERN.
    //  Sobald beide Felder eines Spiels ausgefüllt sind, wird der Tipp nach
    //  kurzer Pause per fetch() gespeichert – ohne den Button zu drücken.
    // ===================================================================
    var betForm = document.getElementById('bet-form');
    if (betForm && window.fetch) {
        var asI18n = {};
        try { asI18n = JSON.parse((document.getElementById('autosave-i18n') || {}).textContent || '{}'); } catch (e) {}
        var note = document.getElementById('autosave-note');
        if (note) note.hidden = false;          // Hinweis nur mit aktivem JS zeigen

        var csrf = (betForm.querySelector('input[name="_csrf"]') || {}).value || '';
        var timers = {};                        // matchId -> debounce-Timer
        var pending = 0;                         // laufende/ausstehende Speicherungen

        function setStatus(card, text, cls) {
            var el = card.querySelector('[data-status]');
            if (!el) return;
            el.textContent = text || '';
            el.className = 'bet-status' + (cls ? ' is-' + cls : '');
        }

        function saveCard(card) {
            var id = card.getAttribute('data-match');
            var h = card.querySelector('input[name="pred1[' + id + ']"]');
            var a = card.querySelector('input[name="pred2[' + id + ']"]');
            if (!h || !a) return;
            // Nur speichern, wenn BEIDE Tore eingetragen sind.
            if (h.value === '' || a.value === '') { setStatus(card, '', ''); return; }

            setStatus(card, asI18n.saving || '…', 'saving');
            pending++;
            var body = '_csrf=' + encodeURIComponent(csrf) + '&ajax=1'
                     + '&pred1[' + id + ']=' + encodeURIComponent(h.value)
                     + '&pred2[' + id + ']=' + encodeURIComponent(a.value);
            fetch(betForm.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'fetch' },
                credentials: 'same-origin',
                body: body
            }).then(function (r) {
                return r.ok ? r.json() : Promise.reject();
            }).then(function (res) {
                if (res && res.ok && res.saved > 0) {
                    setStatus(card, asI18n.saved || 'OK', 'saved');
                } else {
                    setStatus(card, asI18n.error || '!', 'error');
                }
            }).catch(function () {
                setStatus(card, asI18n.error || '!', 'error');
            }).then(function () { pending--; });
        }

        // Eingaben überwachen (debounced pro Spiel-Karte).
        betForm.addEventListener('input', function (ev) {
            var card = ev.target.closest && ev.target.closest('.bet-card');
            if (!card) return;
            var id = card.getAttribute('data-match');
            clearTimeout(timers[id]);
            timers[id] = setTimeout(function () { saveCard(card); }, 700);
        });

        // Beim Verlassen der Seite nur warnen, wenn gerade noch gespeichert wird.
        window.addEventListener('beforeunload', function (e) {
            if (pending > 0) { e.preventDefault(); e.returnValue = ''; }
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

    // ===================================================================
    //  Interaktiver Turnierbaum: Sieger anklicken -> Pfad wird simuliert.
    // ===================================================================
    var bracket = document.getElementById('bracket');
    var modelEl = document.getElementById('bracket-model');
    if (bracket && modelEl) {
        var M = {};
        try { M = JSON.parse(modelEl.textContent || '{}'); } catch (e) { M = {}; }
        var i18n = {};
        try { i18n = JSON.parse((document.getElementById('bracket-i18n') || {}).textContent || '{}'); } catch (e) {}
        var picked = {};                       // matchNum -> 0|1 (gewählter Slot)
        var svg = document.getElementById('bracket-lines');
        var SVGNS = 'http://www.w3.org/2000/svg';

        // Liefert die Mannschaft in einem Slot (rekursiv über die Vorspiele).
        function teamInSlot(num, idx) {
            var m = M[num]; if (!m) return { t: 'open' };
            var s = m.slots[idx];
            if (s.feedFrom != null && picked[s.feedFrom] != null) {
                return teamInSlot(s.feedFrom, picked[s.feedFrom]);
            }
            if (s.base && s.base.t === 'team') return s.base;   // echtes/projiziertes Team
            if (s.feedFrom != null) return { t: 'open' };
            return s.base || { t: 'open' };
        }

        // Inhalt einer Slot-Zeile neu zeichnen.
        function renderSide(btn) {
            var num = btn.dataset.num, idx = parseInt(btn.dataset.slot, 10) - 1;
            var tm = teamInSlot(num, idx);
            var teamSpan = btn.querySelector('.ko-team');
            var simmed = false;
            if (tm.t === 'team') {
                var flag = tm.flag ? '<img class="flag" src="' + tm.flag + '" alt="" aria-hidden="true" width="20" height="15">' : '';
                var proj = tm.proj ? ' <span class="proj">' + (i18n.proj || '') + '</span>' : '';
                teamSpan.innerHTML = flag + ' <span class="kt-name">' + escapeHtml(tm.name) + '</span>' + proj;
                // "simuliert", wenn der Slot aus einem Vorspiel mit Pick stammt
                var s = M[num].slots[idx];
                simmed = (s.feedFrom != null && picked[s.feedFrom] != null);
                btn.classList.toggle('is-clickable', true);
                btn.disabled = false;
            } else if (tm.t === 'label') {
                teamSpan.innerHTML = '<span class="slot-label">' + escapeHtml(tm.text) + '</span>';
                btn.classList.remove('is-clickable'); btn.disabled = true;
            } else {
                teamSpan.innerHTML = '<span class="ko-open">' + escapeHtml(i18n.open || '') + '</span>';
                btn.classList.remove('is-clickable'); btn.disabled = true;
            }
            teamSpan.classList.toggle('is-sim', simmed);
            btn.classList.toggle('is-winner', picked[num] === idx);
        }

        function renderAll() {
            // Ungültige Picks (Slot nicht mehr besetzt) entfernen.
            Object.keys(picked).forEach(function (num) {
                if (teamInSlot(num, picked[num]).t !== 'team') delete picked[num];
            });
            bracket.querySelectorAll('.ko-side').forEach(renderSide);
            var reset = document.getElementById('bracket-reset');
            if (reset) reset.hidden = Object.keys(picked).length === 0;
        }

        // Klick auf eine Mannschaft = Sieger dieses Spiels wählen (Toggle).
        bracket.addEventListener('click', function (ev) {
            var btn = ev.target.closest('.ko-side');
            if (!btn || btn.disabled) return;
            var num = btn.dataset.num, idx = parseInt(btn.dataset.slot, 10) - 1;
            if (teamInSlot(num, idx).t !== 'team') return;
            picked[num] = (picked[num] === idx) ? undefined : idx;
            if (picked[num] === undefined) delete picked[num];
            renderAll();
        });

        var resetBtn = document.getElementById('bracket-reset');
        if (resetBtn) resetBtn.addEventListener('click', function () { picked = {}; renderAll(); });

        // --- Verbindungslinien (SVG) ---
        function drawLines() {
            while (svg.firstChild) svg.removeChild(svg.firstChild);
            var base = bracket.getBoundingClientRect();
            var w = bracket.scrollWidth, h = bracket.scrollHeight;
            svg.setAttribute('width', w); svg.setAttribute('height', h);
            svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);

            bracket.querySelectorAll('.ko-match').forEach(function (el) {
                var num = el.dataset.num, m = M[num];
                if (!m || !m.feeds || m.feeds.to == null) return;
                var parent = bracket.querySelector('.ko-match[data-num="' + m.feeds.to + '"]');
                if (!parent) return;
                var c = el.getBoundingClientRect(), p = parent.getBoundingClientRect();
                var x1 = c.right - base.left, y1 = c.top - base.top + c.height / 2;
                var x2 = p.left - base.left,  y2 = p.top - base.top + p.height / 2;
                var xm = (x1 + x2) / 2;
                var path = document.createElementNS(SVGNS, 'path');
                path.setAttribute('d', 'M' + x1 + ',' + y1 + ' H' + xm + ' V' + y2 + ' H' + x2);
                svg.appendChild(path);   // Aussehen kommt aus dem CSS (.bracket-lines path)
            });
        }

        renderAll();
        // Linien zeichnen, sobald das Layout steht (auch nach Flag-Laden).
        requestAnimationFrame(drawLines);
        window.addEventListener('load', drawLines);
        var rt;
        window.addEventListener('resize', function () {
            clearTimeout(rt); rt = setTimeout(drawLines, 150);
        });
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
})();
