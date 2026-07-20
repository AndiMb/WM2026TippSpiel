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

    // ===================================================================
    //  Live-Zwischenstände: solange auf der Seite ein laufendes Spiel
    //  angezeigt wird, einmal pro Minute /live abfragen und die Stände
    //  ohne Neuladen aktualisieren. Endet das letzte Spiel, stoppt das
    //  Polling von selbst.
    // ===================================================================
    var liveUrl = document.body.getAttribute('data-live-url');
    if (liveUrl && window.fetch && document.querySelector('[data-live-badge]')) {
        var liveTimer = setInterval(pollLive, 60000);

        function pollLive() {
            fetch(liveUrl, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'fetch' }
            }).then(function (r) {
                return r.ok ? r.json() : Promise.reject();
            }).then(function (res) {
                (res.matches || []).forEach(function (m) {
                    var score = document.querySelector('[data-live-score="' + m.id + '"]');
                    if (score && m.s1 !== null && m.s2 !== null) {
                        var txt = m.s1 + ':' + m.s2 + (m.decided ? ' ' + m.decided : '');
                        if (score.textContent !== txt) {
                            score.textContent = txt;
                            score.classList.remove('score-bump');
                            void score.offsetWidth;          // Animation neu starten
                            score.classList.add('score-bump');
                        }
                    }
                    // Spiel beendet -> Badge ummelden und nicht weiter beobachten.
                    var badge = document.querySelector('[data-live-badge="' + m.id + '"]');
                    if (badge && m.status === 'finished') {
                        badge.textContent = res.finished_label || 'FT';
                        badge.classList.add('is-done');
                        badge.removeAttribute('data-live-badge');
                    }
                });
                if (!document.querySelector('[data-live-badge]')) {
                    clearInterval(liveTimer);
                }
            }).catch(function () { /* nächster Versuch in einer Minute */ });
        }
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

        // --- Runden-Buttons: horizontal zur gewählten Stufe springen ---
        var scroller = document.getElementById('bracket-scroll');
        var tabsEl = document.getElementById('round-tabs');
        if (scroller && tabsEl) {
            var rounds = bracket.querySelectorAll('.bracket-round');
            var tabBtns = tabsEl.querySelectorAll('.round-tab');
            var setActiveTab = function (idx) {
                tabBtns.forEach(function (b) {
                    b.classList.toggle('is-active', parseInt(b.dataset.idx, 10) === idx);
                });
            };
            tabBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var idx = parseInt(btn.dataset.idx, 10);
                    var target = rounds[idx];
                    if (!target) return;
                    var delta = target.getBoundingClientRect().left - scroller.getBoundingClientRect().left;
                    scroller.scrollLeft = scroller.scrollLeft + delta;   // direkt = zuverlässig
                    setActiveTab(idx);
                });
            });
            // Beim horizontalen Scrollen die sichtbare Runde als aktiv markieren.
            var st = false;
            scroller.addEventListener('scroll', function () {
                if (st) return; st = true;
                window.requestAnimationFrame(function () {
                    var left = scroller.getBoundingClientRect().left + 40;
                    var best = 0, bestD = Infinity;
                    rounds.forEach(function (r, i) {
                        var d = Math.abs(r.getBoundingClientRect().left - left);
                        if (d < bestD) { bestD = d; best = i; }
                    });
                    setActiveTab(best);
                    st = false;
                });
            }, { passive: true });
        }
    }

    // ===================================================================
    //  Siegerehrung: Rangliste vom letzten zum ersten Platz enthüllen,
    //  mit etwas Konfetti als kleines Extra fürs Saisonende.
    // ===================================================================
    var ceremonyStage = document.getElementById('ceremony-stage');
    if (ceremonyStage) {
        var ceremonyData = [];
        try { ceremonyData = JSON.parse((document.getElementById('ceremony-data') || {}).textContent || '[]'); } catch (e) {}
        var ceremonyI18n = {};
        try { ceremonyI18n = JSON.parse((document.getElementById('ceremony-i18n') || {}).textContent || '{}'); } catch (e) {}

        var cList     = document.getElementById('ceremony-list');
        var cProgress = document.getElementById('ceremony-progress');
        var cNextBtn  = document.getElementById('ceremony-next');
        var cSkipBtn  = document.getElementById('ceremony-skip');
        var cCard     = document.getElementById('ceremony-card');
        var cFinale   = document.getElementById('ceremony-finale');
        var cRestart  = document.getElementById('ceremony-restart');
        var cCanvas   = document.getElementById('ceremony-confetti');
        var total     = ceremonyData.length;
        var idx       = 0;                     // Index in ceremonyData (0 = letzter Platz)
        var reduced   = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function itemHtml(item) {
            var youTag = item.isMe ? ' <span class="you-tag">' + escapeHtml(ceremonyI18n.you || '') + '</span>' : '';
            return '<span class="rank-cell">' + (item.medal || item.rank) + '</span>'
                 + '<span class="ceremony-item-name">' + escapeHtml(item.name) + youTag + '</span>'
                 + '<span class="ceremony-item-pts">' + item.points + ' ' + escapeHtml(ceremonyI18n.points || '') + '</span>';
        }

        // Neu enthüllten Platz oben in die wachsende Liste einfügen – da jeder
        // weitere Platz einen besseren Rang hat, bleibt die Liste dabei immer
        // in der richtigen Reihenfolge (Platz 1 landet zum Schluss ganz oben).
        function prependToList(item) {
            var li = document.createElement('li');
            li.className = 'ceremony-item' + (item.isMe ? ' is-me' : '') + (item.rank <= 3 ? ' is-podium' : '');
            li.innerHTML = itemHtml(item);
            cList.insertBefore(li, cList.firstChild);
        }

        function showStage(item) {
            cProgress.textContent = (ceremonyI18n.rankOf || '')
                .replace(':rank', String(item.rank)).replace(':total', String(total));
            cCard.classList.remove('is-me-highlight');
            var big = item.rank === 1 ? ' ceremony-stage-first' : (item.rank <= 3 ? ' ceremony-stage-podium' : '');
            cCard.classList.toggle('is-me-highlight', !!item.isMe);
            cStageInnerUpdate(item, big);
        }

        function cStageInnerUpdate(item, extraClass) {
            ceremonyStage.className = 'ceremony-stage ceremony-pop' + extraClass;
            // Reflow erzwingen, damit die Animation bei jedem Platz neu startet.
            void ceremonyStage.offsetWidth;
            ceremonyStage.classList.add('ceremony-pop-play');
            var youTag = item.isMe ? ' <span class="you-tag">' + escapeHtml(ceremonyI18n.you || '') + '</span>' : '';
            ceremonyStage.innerHTML =
                '<div class="ceremony-medal">' + (item.medal || ('#' + item.rank)) + '</div>' +
                '<div class="ceremony-name">' + escapeHtml(item.name) + youTag + '</div>' +
                '<div class="ceremony-points">' + item.points + ' ' + escapeHtml(ceremonyI18n.points || '') + '</div>';
        }

        function revealStep() {
            if (idx >= total) return;
            var item = ceremonyData[idx];
            showStage(item);
            prependToList(item);
            if (!reduced) {
                var size = item.rank === 1 ? 'huge' : (item.rank <= 3 ? 'big' : 'small');
                confettiBurst(size);
            }
            idx++;
            if (idx >= total) {
                cNextBtn.hidden = true;
                cSkipBtn.hidden = true;
                cFinale.hidden = false;
                if (!reduced) { setTimeout(function () { confettiBurst('huge'); }, 250); }
            } else {
                cNextBtn.textContent = ceremonyI18n.next || cNextBtn.textContent;
            }
        }

        function revealAllInstant() {
            while (idx < total) {
                var item = ceremonyData[idx];
                prependToList(item);
                idx++;
            }
            ceremonyStage.hidden = true;
            cNextBtn.hidden = true;
            cSkipBtn.hidden = true;
            cFinale.hidden = false;
        }

        function restart() {
            idx = 0;
            cList.innerHTML = '';
            cFinale.hidden = true;
            ceremonyStage.hidden = false;
            ceremonyStage.className = 'ceremony-stage';
            ceremonyStage.innerHTML = '';
            cNextBtn.hidden = false;
            cNextBtn.textContent = cNextBtn.getAttribute('data-start-label') || cNextBtn.textContent;
            cSkipBtn.hidden = false;
            // Bewegungsreduzierung bleibt auch nach "Nochmal von vorne" respektiert.
            if (reduced) { revealAllInstant(); }
        }

        cNextBtn.setAttribute('data-start-label', cNextBtn.textContent);
        cNextBtn.addEventListener('click', revealStep);
        cSkipBtn.addEventListener('click', revealAllInstant);
        if (cRestart) cRestart.addEventListener('click', restart);

        // Wer keine Bewegung wünscht, bekommt sofort die fertige Liste ohne
        // Konfetti und ohne Schritt-für-Schritt-Enthüllung.
        if (reduced) {
            revealAllInstant();
        }

        // --- Minimales, abhängigkeitsfreies Konfetti (Canvas) ---------------
        var confettiColors = null;
        function getConfettiColors() {
            if (confettiColors) return confettiColors;
            var cs = getComputedStyle(document.documentElement);
            confettiColors = [
                cs.getPropertyValue('--green').trim()  || '#1b8a5a',
                cs.getPropertyValue('--yellow').trim() || '#f6c343',
                cs.getPropertyValue('--blue').trim()   || '#2563eb',
                cs.getPropertyValue('--red').trim()    || '#d64545',
                '#ffffff'
            ];
            return confettiColors;
        }

        var particles = [];
        var confettiRunning = false;
        function confettiBurst(size) {
            if (!cCanvas || !cCanvas.getContext) return;
            var counts = { small: 18, big: 60, huge: 130 };
            var count = counts[size] || counts.small;
            var colors = getConfettiColors();
            var w = window.innerWidth, h = window.innerHeight;
            cCanvas.width = w; cCanvas.height = h;
            for (var i = 0; i < count; i++) {
                particles.push({
                    x: w / 2 + (Math.random() - 0.5) * w * (size === 'small' ? 0.3 : 0.8),
                    y: -20 - Math.random() * 60,
                    vx: (Math.random() - 0.5) * 5,
                    vy: 2 + Math.random() * 3.5,
                    rot: Math.random() * 360,
                    vr: (Math.random() - 0.5) * 14,
                    size: 5 + Math.random() * 6,
                    color: colors[(Math.random() * colors.length) | 0],
                    life: 0,
                    maxLife: 110 + Math.random() * 60
                });
            }
            if (!confettiRunning) { confettiRunning = true; requestAnimationFrame(confettiTick); }
        }

        function confettiTick() {
            var ctx = cCanvas.getContext('2d');
            ctx.clearRect(0, 0, cCanvas.width, cCanvas.height);
            var alive = [];
            for (var i = 0; i < particles.length; i++) {
                var p = particles[i];
                p.x += p.vx; p.y += p.vy; p.vy += 0.06; p.rot += p.vr; p.life++;
                if (p.life < p.maxLife && p.y < cCanvas.height + 30) {
                    ctx.save();
                    ctx.translate(p.x, p.y);
                    ctx.rotate(p.rot * Math.PI / 180);
                    ctx.fillStyle = p.color;
                    ctx.globalAlpha = Math.max(0, 1 - p.life / p.maxLife);
                    ctx.fillRect(-p.size / 2, -p.size / 3, p.size, p.size * 0.6);
                    ctx.restore();
                    alive.push(p);
                }
            }
            particles = alive;
            if (particles.length > 0) {
                requestAnimationFrame(confettiTick);
            } else {
                confettiRunning = false;
                ctx.clearRect(0, 0, cCanvas.width, cCanvas.height);
            }
        }

        window.addEventListener('resize', function () {
            if (cCanvas) { cCanvas.width = window.innerWidth; cCanvas.height = window.innerHeight; }
        });
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
})();
