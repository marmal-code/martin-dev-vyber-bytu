/* Martin – Výběr bytů: frontend. HTML vykresluje PHP, tady jen navigace, zvýraznění, bublina a filtry. */
(function () {
  'use strict';

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function q(key) { return window.CSS && CSS.escape ? CSS.escape(key) : key; }

  function init(root) {
    if (root.__martinDv) return;
    root.__martinDv = true;

    var dataEl = root.querySelector('script.martin-dv__data');
    if (!dataEl) return;
    var D;
    try { D = JSON.parse(dataEl.textContent); } catch (err) { return; }

    var panels = {};
    root.querySelectorAll('[data-dv-panel]').forEach(function (p) { panels[p.getAttribute('data-dv-panel')] = p; });
    root.querySelectorAll('[data-dv-card]').forEach(function (c) { c.__summary = c.innerHTML; });

    var tip = root.querySelector('.martin-dv__tip');
    var filter = { type: '', disp: '', floor: '', status: '' };
    var rows = Array.prototype.slice.call(root.querySelectorAll('tbody tr[data-dv-key]'));
    var emptyRow = root.querySelector('.martin-dv__empty-row');

    var current = null, parentView = null, stack = [];
    var hoverKey = null, selKey = null, tipKey = null, lastPointer = 'mouse';

    function parentOf(floorId) {
      for (var vid in D.views) {
        if (D.views[vid].floors.indexOf(floorId) !== -1) return vid;
      }
      return null;
    }

    /* ---------- navigace ---------- */
    function show(key, push) {
      if (!panels[key]) return;
      if (push && current && current !== key) stack.push({ key: current, parent: parentView });
      if (key.indexOf('view:') === 0) parentView = key.slice(5);
      else if (!parentView) parentView = parentOf(key.slice(6));
      current = key;
      selKey = null;
      Object.keys(panels).forEach(function (k) { panels[k].hidden = k !== key; });
      panels[key].querySelectorAll('[data-dv-back]').forEach(function (b) { b.hidden = !stack.length; });
      panels[key].querySelectorAll('.martin-dv__bar--view').forEach(function (b) { b.hidden = !stack.length; });
      renderPills(panels[key]);
      setHover(null);
      hideTip();
      setFloorFilter(key.indexOf('floor:') === 0 ? key.slice(6) : '');
      applyTable();
    }

    function back() {
      var prev = stack.pop();
      if (!prev) return;
      parentView = prev.parent;
      show(prev.key, false);
    }

    function renderPills(panel) {
      var box = panel.querySelector('[data-dv-pills]');
      if (!box) return;
      var floors = parentView && D.views[parentView] ? D.views[parentView].floors : [];
      if (floors.length < 2) { box.innerHTML = ''; return; }
      box.innerHTML = floors.map(function (id) {
        var key = 'floor:' + id;
        var on = key === current;
        return '<button type="button" class="martin-dv__pill' + (on ? ' is-active' : '') + '" data-dv-pill="' + esc(key) + '"' + (on ? ' aria-current="true"' : '') + '>' +
          esc(D.floors[id] ? D.floors[id].name : id) + '</button>';
      }).join('');
    }

    /* ---------- zvýraznění + karta ---------- */
    function setHover(key) {
      hoverKey = key;
      root.querySelectorAll('.is-hover').forEach(function (n) { n.classList.remove('is-hover'); });
      if (key) root.querySelectorAll('[data-dv-key="' + q(key) + '"]').forEach(function (n) { n.classList.add('is-hover'); });
      updateCard();
    }

    function markSelected() {
      root.querySelectorAll('.is-selected').forEach(function (n) { n.classList.remove('is-selected'); });
      if (selKey && panels[current]) {
        panels[current].querySelectorAll('[data-dv-key="' + q(selKey) + '"]').forEach(function (n) { n.classList.add('is-selected'); });
      }
    }

    function row(label, value) {
      return value ? '<dt>' + esc(label) + '</dt><dd>' + esc(value) + '</dd>' : '';
    }

    function cardHTML(u) {
      return '<p class="martin-dv__kicker">' + esc(u.typeLabel) + '</p>' +
        '<div class="martin-dv__card-head"><h4 class="martin-dv__card-title">' + esc(u.num) + '</h4>' +
        '<span class="martin-dv__status" data-status="' + esc(u.status) + '">' + esc(u.statusLabel) + '</span></div>' +
        '<dl class="martin-dv__dl">' +
        row(D.i18n.disp, u.disp) + row(D.i18n.floor, u.floorTxt) + row(D.i18n.area, u.areaTxt) +
        (u.outLabel ? row(u.outLabel, u.outAreaTxt || '✓') : '') + row(D.i18n.price, u.priceTxt) +
        '</dl>' +
        (u.acc && u.acc.length
          ? '<div class="martin-dv__acc"><p class="martin-dv__acc-title">' + esc(D.i18n.acc) + '</p><dl class="martin-dv__dl">' +
            u.acc.map(function (a) { return row(a.label, a.price); }).join('') + '</dl></div>'
          : '') +
        (u.clickable
          ? '<a class="martin-dv__btn" href="' + esc(u.url) + '">' + esc(D.settings.button_text) + ' →</a>'
          : '<p class="martin-dv__hint">' + esc(D.i18n.soldHint) + '</p>');
    }

    function updateCard() {
      var panel = panels[current];
      if (!panel) return;
      var card = panel.querySelector('[data-dv-card]');
      if (!card) return;
      var k = (hoverKey && hoverKey.indexOf('unit:') === 0) ? hoverKey : selKey;
      var u = k ? D.units[k.slice(5)] : null;
      card.innerHTML = u ? cardHTML(u) : card.__summary;
    }

    /* ---------- bublina ---------- */
    function tipHTML(key) {
      if (key.indexOf('unit:') === 0) {
        var u = D.units[key.slice(5)];
        if (!u) return '';
        var S = D.settings;
        var h = '<strong>' + esc(u.label) + (u.disp ? ' · ' + esc(u.disp) : '') + '</strong>';
        if (S.tip_area && u.areaTxt) h += '<span>' + esc(D.i18n.area) + ': ' + esc(u.areaTxt) + '</span>';
        if (S.tip_outdoor && u.outTxt) h += '<span>' + esc(u.outTxt) + '</span>';
        if (S.tip_price && u.priceTxt) h += '<span>' + esc(u.priceTxt) + '</span>';
        if (S.tip_status) h += '<span><span class="martin-dv__status" data-status="' + esc(u.status) + '">' + esc(u.statusLabel) + '</span></span>';
        if (u.clickable) h += '<em>' + esc(D.i18n.clickUnit) + '</em>';
        return h;
      }
      var k = D.keys[key];
      if (!k) return '';
      return '<strong>' + esc(k.title) + '</strong>' +
        k.lines.map(function (l) { return '<span>' + esc(l) + '</span>'; }).join('') +
        '<em>' + esc(D.i18n.clickArea) + '</em>';
    }

    function hideTip() { if (tip) tip.hidden = true; tipKey = null; }

    function placeTip(e, key, stage) {
      if (!tip) return;
      if (tipKey !== key) { tip.innerHTML = tipHTML(key); tipKey = key; }
      if (!tip.innerHTML) { tip.hidden = true; return; }
      tip.hidden = false;
      var r = root.getBoundingClientRect();
      var s = stage.getBoundingClientRect();
      var x = e.clientX - r.left, y = e.clientY - r.top;
      var minX = s.left - r.left + 8, maxX = s.right - r.left - 8;
      var minY = s.top - r.top + 8, maxY = s.bottom - r.top - 8;
      var tw = tip.offsetWidth, th = tip.offsetHeight;
      var left = x + 16; if (left + tw > maxX) left = x - tw - 16; if (left < minX) left = minX;
      var top = y + 16; if (top + th > maxY) top = y - th - 16; if (top < minY) top = minY;
      tip.style.left = left + 'px';
      tip.style.top = top + 'px';
    }

    function track(e) {
      var el = e.target.closest ? e.target.closest('[data-dv-key]') : null;
      if (el && !root.contains(el)) el = null;
      var key = el ? el.getAttribute('data-dv-key') : null;
      if (key !== hoverKey) setHover(key);
      var stage = el ? el.closest('.martin-dv__stage') : null;
      if (!stage || lastPointer === 'touch') { hideTip(); return; }
      placeTip(e, key, stage);
    }

    /* ---------- tabulka + filtr ---------- */
    function syncPills() {
      var any = false;
      root.querySelectorAll('[data-dv-fgroup]').forEach(function (b) {
        var g = b.getAttribute('data-dv-fgroup');
        var on = g === 'all' ? false : filter[g] === b.getAttribute('data-dv-fvalue');
        if (on) any = true;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      var all = root.querySelector('[data-dv-fgroup="all"]');
      if (all) { all.classList.toggle('is-active', !any); all.setAttribute('aria-pressed', any ? 'false' : 'true'); }
    }

    function applyTable() {
      if (!rows.length) return;
      var visible = 0;
      rows.forEach(function (r) {
        var ok = (!filter.floor || (' ' + r.getAttribute('data-floors') + ' ').indexOf(' ' + filter.floor + ' ') !== -1) &&
          (!filter.type || r.getAttribute('data-type') === filter.type) &&
          (!filter.disp || r.getAttribute('data-disp') === filter.disp) &&
          (!filter.status || r.getAttribute('data-status') === filter.status);
        r.hidden = !ok;
        if (ok) visible++;
      });
      if (emptyRow) emptyRow.hidden = visible > 0;
      syncPills();
    }

    function setFloorFilter(floorId) {
      // Při procházení podlaží se tabulka sama přepne na dané podlaží (jen když pro něj existuje tlačítko).
      var has = floorId && root.querySelector('[data-dv-fgroup="floor"][data-dv-fvalue="' + q(floorId) + '"]');
      filter.floor = has ? floorId : '';
    }

    /* ---------- události ---------- */
    root.addEventListener('pointerdown', function (e) { lastPointer = e.pointerType || 'mouse'; track(e); });
    root.addEventListener('pointermove', track);
    root.addEventListener('pointerleave', function () { setHover(null); hideTip(); });
    root.addEventListener('focusin', function (e) {
      var el = e.target.closest('[data-dv-key]');
      if (el) setHover(el.getAttribute('data-dv-key'));
    });
    root.addEventListener('focusout', function () { setHover(null); });

    root.addEventListener('click', function (e) {
      var t = e.target, el;
      if (t.closest('[data-dv-back]')) { back(); return; }
      if ((el = t.closest('[data-dv-fgroup]'))) {
        var g = el.getAttribute('data-dv-fgroup');
        if (g === 'all') { filter = { type: '', disp: '', floor: '', status: '' }; }
        else { var v = el.getAttribute('data-dv-fvalue'); filter[g] = filter[g] === v ? '' : v; }
        applyTable();
        return;
      }
      if ((el = t.closest('[data-dv-pill]'))) { show(el.getAttribute('data-dv-pill'), false); return; }
      if ((el = t.closest('[data-dv-go]'))) {
        e.preventDefault();
        show(el.getAttribute('data-dv-go'), true);
        var top = root.getBoundingClientRect().top;
        if (top < 0) root.scrollIntoView({ block: 'start', behavior: 'smooth' });
        return;
      }
      if ((el = t.closest('.martin-dv__area--unit'))) {
        var key = el.getAttribute('data-dv-key');
        // Dotyk: první klepnutí jen vybere a ukáže kartu, druhé otevře detail.
        if (lastPointer === 'touch' && selKey !== key) {
          e.preventDefault();
          selKey = key;
          markSelected();
          updateCard();
        }
        return;
      }
      if ((el = t.closest('tr[data-dv-key]')) && !t.closest('a')) {
        var link = el.querySelector('a[href]');
        if (link) window.location.href = link.href;
      }
    });


    /* ---------- start (podpora odkazu #id-floor-xyz) ---------- */
    var startKey = D.start;
    var hash = window.location.hash.slice(1);
    if (hash && hash.indexOf(root.id + '-') === 0) {
      var rest = hash.slice(root.id.length + 1);
      var cand = rest.replace(/^(view|floor)-/, '$1:');
      if (panels[cand]) startKey = cand;
    }
    if (startKey !== D.start && panels[D.start]) {
      parentView = D.start.slice(5);
      current = D.start;
      show(startKey, true);
    } else {
      show(startKey, false);
    }
  }

  function initAll(scope) {
    (scope || document).querySelectorAll('[data-martin-dv]').forEach(init);
  }
  window.martinDvInit = initAll;

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { initAll(); });
  else initAll();
})();
