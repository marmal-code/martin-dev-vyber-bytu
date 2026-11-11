/* Marmal – Developerské projekty: Mapa okolí (frontend). Bez závislostí.
 * Propojení bod ↔ řádek (najetí myší, klávesnice, ťuknutí), bublina s názvem, filtr kategorií,
 * automatická barva čísla/ikony podle barvy značky (tmavá na světlé, bílá na tmavé).
 */
(function () {
  'use strict';

  /* ---------- automatická barva textu značky ---------- */
  function parseColor(str) {
    var m = String(str).match(/[\d.]+/g);
    if (!m || m.length < 3) return null;
    var v = m.slice(0, 3).map(Number);
    if (/^color\(/.test(str)) v = v.map(function (x) { return x * 255; }); // color(srgb 0.5 0.5 0.5)
    if (m.length > 3 && Number(m[3]) === 0) return null; // průhledné
    return v;
  }
  function luminance(rgb) {
    var c = rgb.map(function (x) {
      x = x / 255;
      return x <= 0.03928 ? x / 12.92 : Math.pow((x + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
  }
  function autoInk(el) {
    var rgb = parseColor(window.getComputedStyle(el).backgroundColor);
    if (!rgb) return;
    // Vybere barvu s vyšším kontrastem (WCAG): tmavou #1f2a33, nebo bílou.
    var L = luminance(rgb), dark = luminance([31, 42, 51]);
    var onDark = (L + 0.05) / (dark + 0.05), onWhite = 1.05 / (L + 0.05);
    el.style.setProperty('--mdo-ink-auto', onDark > onWhite ? '#1f2a33' : '#ffffff');
  }

  function init(root) {
    if (root.__martinDvOkoli) return;
    root.__martinDvOkoli = true;

    var map = root.querySelector('.martin-dv-okoli__map');
    var tip = root.querySelector('.martin-dv-okoli__tip');
    if (!map || !tip) return;

    var marks = {}, rows = {};
    root.querySelectorAll('.martin-dv-okoli__mk').forEach(function (m) { marks[m.getAttribute('data-mdo')] = m; });
    root.querySelectorAll('.martin-dv-okoli__row').forEach(function (r) { rows[r.getAttribute('data-mdo')] = r; });

    function paintInk() {
      Object.keys(marks).forEach(function (k) { if (k !== 'proj') autoInk(marks[k]); });
      root.querySelectorAll('.martin-dv-okoli__num').forEach(autoInk);
    }
    paintInk();

    var hovered = null, pinned = null;

    function update() {
      var a = hovered !== null ? hovered : pinned;
      Object.keys(marks).forEach(function (k) { marks[k].classList.toggle('is-active', k === a); });
      Object.keys(rows).forEach(function (k) { rows[k].classList.toggle('is-active', k === a); });
      map.classList.toggle('has-active', a !== null && a !== 'proj');

      var mk = a !== null ? marks[a] : null;
      if (!mk || mk.classList.contains('is-hidden')) { tip.hidden = true; return; }

      tip.textContent = '';
      tip.appendChild(document.createTextNode(mk.getAttribute('data-name') || ''));
      var sub = mk.getAttribute('data-sub');
      if (sub) {
        var s = document.createElement('small');
        s.textContent = sub;
        tip.appendChild(s);
      }
      var x = parseFloat(mk.style.left) || 0, y = parseFloat(mk.style.top) || 0;
      tip.style.left = x + '%';
      tip.style.top = y + '%';
      tip.style.setProperty('--tx', x < 14 ? '-12%' : (x > 86 ? '-88%' : '-50%'));
      tip.classList.toggle('is-below', y < 18);
      tip.hidden = false;
    }

    function keyOf(el) {
      var t = el && el.closest('[data-mdo]');
      return t && root.contains(t) ? t.getAttribute('data-mdo') : null;
    }

    root.addEventListener('pointerover', function (e) {
      if (e.pointerType !== 'mouse') return;
      var k = keyOf(e.target);
      if (k !== hovered) { hovered = k; update(); }
    });
    root.addEventListener('pointerleave', function () {
      if (hovered !== null) { hovered = null; update(); }
    });
    root.addEventListener('focusin', function (e) {
      var t = e.target;
      if (t.matches && t.matches(':focus-visible')) { hovered = keyOf(t); update(); }
    });
    root.addEventListener('focusout', function () {
      if (hovered !== null) { hovered = null; update(); }
    });

    root.addEventListener('click', function (e) {
      var chip = e.target.closest('.martin-dv-okoli__chip');
      if (chip) { filter(chip); return; }
      var t = e.target.closest('[data-mdo]');
      if (!t) return;
      if (t.tagName === 'A') return; // řádek s odkazem – nech prohlížeč otevřít odkaz
      var k = t.getAttribute('data-mdo');
      pinned = pinned === k ? null : k;
      if (e.pointerType && e.pointerType !== 'mouse') hovered = null;
      if (pinned !== null && t.classList.contains('martin-dv-okoli__row')) {
        var r = map.getBoundingClientRect();
        if (r.bottom < 0 || r.top > window.innerHeight) map.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      }
      update();
    });

    document.addEventListener('click', function (e) {
      if (pinned !== null && !root.contains(e.target)) { pinned = null; update(); }
    });

    function filter(chip) {
      var cat = chip.getAttribute('data-cat') || '';
      root.querySelectorAll('.martin-dv-okoli__chip').forEach(function (c) { c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
      Object.keys(marks).forEach(function (k) {
        if (k === 'proj') return;
        var hide = cat !== '' && marks[k].getAttribute('data-cat') !== cat;
        marks[k].classList.toggle('is-hidden', hide);
        marks[k].tabIndex = hide ? -1 : 0;
        if (hide) marks[k].setAttribute('aria-hidden', 'true'); else marks[k].removeAttribute('aria-hidden');
        if (hide && pinned === k) pinned = null;
      });
      root.querySelectorAll('.martin-dv-okoli__li').forEach(function (li) {
        li.classList.toggle('is-hidden', cat !== '' && li.getAttribute('data-cat') !== cat);
      });
      update();
    }

    // Barvy se mohou změnit po načtení CSS z Breakdance (Global Settings) – přepočítat po načtení stránky.
    window.addEventListener('load', paintInk, { once: true });
  }

  function initAll(scope) {
    (scope || document).querySelectorAll('[data-martin-dv-okoli]').forEach(init);
  }
  window.martinDvOkoliInit = initAll;

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { initAll(); });
  else initAll();
})();
