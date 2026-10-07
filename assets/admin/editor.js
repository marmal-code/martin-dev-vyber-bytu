/* Martin – Výběr bytů: editor projektu (administrace). Bez závislostí, jen wp.media. */
(function () {
  'use strict';

  var CFG = window.martinDvEditor;
  var root = document.getElementById('martin-dv-editor');
  var input = document.getElementById('martin-dv-data');
  if (!CFG || !root || !input) return;

  var T = CFG.i18n;
  var units = CFG.units || [];
  var S = CFG.data || {};

  /* ---------- pomocné ---------- */
  function uid(p) { return p + Math.random().toString(36).slice(2, 9); }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function pts(poly) { return poly.map(function (p) { return p[0] + ',' + p[1]; }).join(' '); }
  function centroid(poly) {
    var x = 0, y = 0;
    poly.forEach(function (p) { x += p[0]; y += p[1]; });
    return [x / poly.length, y / poly.length];
  }
  function r2(v) { return Math.round(v * 100) / 100; }
  function unitById(id) { for (var i = 0; i < units.length; i++) { if (units[i].id === id) return units[i]; } return null; }

  /* ---------- normalizace ---------- */
  S.views = Array.isArray(S.views) ? S.views : [];
  S.floors = Array.isArray(S.floors) ? S.floors : [];
  if (!S.views.length) S.views.push({ id: uid('v'), name: T.defaultView, image: null, areas: [] });
  S.views.concat(S.floors).forEach(function (o) { o.areas = Array.isArray(o.areas) ? o.areas : []; });
  if (!S.start) S.start = S.views[0].id;
  S.settings = Object.assign({}, CFG.defaults, S.settings || {});

  var U = { ctx: { type: 'view', id: S.start }, sel: null, mode: 'select', draft: [], drag: null };

  function sync() { input.value = JSON.stringify(S); }
  function list(type) { return type === 'view' ? S.views : S.floors; }
  function ctxObj() {
    if (U.ctx.type === 'settings') return null;
    var l = list(U.ctx.type);
    for (var i = 0; i < l.length; i++) { if (l[i].id === U.ctx.id) return l[i]; }
    return null;
  }
  function selArea() {
    var o = ctxObj();
    if (!o || !U.sel) return null;
    for (var i = 0; i < o.areas.length; i++) { if (o.areas[i].id === U.sel) return o.areas[i]; }
    return null;
  }
  function areaLabel(a) {
    if (U.ctx.type === 'view') {
      var t = a.target;
      if (!t) return T.noTarget;
      var l = list(t.type);
      for (var i = 0; i < l.length; i++) { if (l[i].id === t.id) return l[i].name || T.unnamed; }
      return T.noTarget;
    }
    var u = unitById(a.unit);
    return u ? u.short : T.noUnit;
  }
  function aspect(o) { return (o.image && o.image.w && o.image.h) ? (o.image.w / o.image.h) : 1.5; }

  /* ---------- HTML ---------- */
  function navHTML() {
    var h = '<div class="mdv-h">' + esc(T.views) + '</div>';
    S.views.forEach(function (v) {
      var on = U.ctx.type === 'view' && U.ctx.id === v.id;
      h += '<button type="button" class="mdv-navbtn' + (on ? ' on' : '') + '" data-ctx="view:' + esc(v.id) + '"><span>' + esc(v.name || T.unnamed) + '</span>' +
        (S.start === v.id ? '<small>' + esc(T.start) + '</small>' : '<small>' + v.areas.length + '</small>') + '</button>';
    });
    h += '<button type="button" class="mdv-add" data-act="addView">+ ' + esc(T.addView) + '</button>';
    h += '<div class="mdv-h">' + esc(T.floors) + '</div>';
    S.floors.forEach(function (f, i) {
      var on = U.ctx.type === 'floor' && U.ctx.id === f.id;
      h += '<div class="mdv-navrow"><button type="button" class="mdv-navbtn' + (on ? ' on' : '') + '" data-ctx="floor:' + esc(f.id) + '"><span>' + esc(f.name || T.unnamed) + '</span><small>' + f.areas.length + '</small></button>' +
        '<span class="mdv-order"><button type="button" data-act="up" data-id="' + esc(f.id) + '" title="' + esc(T.up) + '" aria-label="' + esc(T.up) + '"' + (i === 0 ? ' disabled' : '') + '>↑</button>' +
        '<button type="button" data-act="down" data-id="' + esc(f.id) + '" title="' + esc(T.down) + '" aria-label="' + esc(T.down) + '"' + (i === S.floors.length - 1 ? ' disabled' : '') + '>↓</button></span></div>';
    });
    h += '<button type="button" class="mdv-add" data-act="addFloor">+ ' + esc(T.addFloor) + '</button>';
    h += '<div class="mdv-h">' + esc(T.project) + '</div>';
    h += '<button type="button" class="mdv-navbtn' + (U.ctx.type === 'settings' ? ' on' : '') + '" data-ctx="settings:x"><span>' + esc(T.settings) + '</span></button>';
    return h;
  }

  function stageHTML() {
    var o = ctxObj();
    if (!o) return '';
    if (!o.image || !o.image.url) {
      return '<div class="mdv-stage mdv-empty" style="aspect-ratio:1.5"><p>' + esc(T.noImage) + '</p></div>';
    }
    var polys = '', labels = '', handles = '', draft = '';
    o.areas.forEach(function (a) {
      polys += '<polygon class="mdv-ap' + (a.id === U.sel ? ' on' : '') + '" data-id="' + esc(a.id) + '" points="' + pts(a.poly) + '"></polygon>';
      var c = centroid(a.poly);
      labels += '<span class="mdv-label" style="left:' + c[0] + '%;top:' + c[1] + '%">' + esc(areaLabel(a)) + '</span>';
    });
    if (U.mode === 'draw') {
      if (U.draft.length) {
        draft = '<polygon class="mdv-draft" points="' + pts(U.draft) + '"></polygon>';
        handles = U.draft.map(function (p, i) {
          return '<span class="mdv-handle mdv-handle-d' + (i === 0 ? ' first' : '') + '" data-i="' + i + '" style="left:' + p[0] + '%;top:' + p[1] + '%"></span>';
        }).join('');
      }
    } else {
      var a = selArea();
      if (a) {
        handles = a.poly.map(function (p, i) {
          return '<span class="mdv-handle" data-i="' + i + '" style="left:' + p[0] + '%;top:' + p[1] + '%"></span>';
        }).join('');
      }
    }
    return '<div class="mdv-stage ' + U.mode + '" style="aspect-ratio:' + aspect(o) + '">' +
      '<img src="' + esc(o.image.url) + '" alt="" draggable="false">' +
      '<svg viewBox="0 0 100 100" preserveAspectRatio="none">' + polys + draft + '</svg>' + labels + handles + '</div>';
  }

  function mainHTML() {
    if (U.ctx.type === 'settings') return settingsHTML();
    var o = ctxObj();
    if (!o) return '';
    var isView = U.ctx.type === 'view';
    var hint;
    if (U.mode === 'draw') hint = T.hintDraw.replace('%d', U.draft.length);
    else hint = selArea() ? T.hintDrag : T.hintSelect;

    var chips = o.areas.map(function (a) {
      return '<button type="button" class="mdv-chip' + (a.id === U.sel ? ' on' : '') + ((isView ? a.target : a.unit) ? '' : ' warn') + '" data-sel="' + esc(a.id) + '">' + esc(areaLabel(a)) + '</button>';
    }).join('');

    return '<div class="mdv-title"><span class="mdv-kicker">' + esc(isView ? T.view : T.floor) + '</span><strong>' + esc(o.name || T.unnamed) + '</strong></div>' +
      '<p class="mdv-help">' + esc(isView ? T.viewHelp : T.floorHelp) + '</p>' +
      '<div class="mdv-tb">' +
      '<button type="button" class="button" data-act="image">' + esc(o.image ? T.changeImage : T.chooseImage) + '</button>' +
      '<span class="mdv-seg"><button type="button" data-mode="select" class="' + (U.mode === 'select' ? 'on' : '') + '">' + esc(T.modeSelect) + '</button>' +
      '<button type="button" data-mode="draw" class="' + (U.mode === 'draw' ? 'on' : '') + '"' + (o.image ? '' : ' disabled') + '>' + esc(T.modeDraw) + '</button></span>' +
      (U.mode === 'draw' ? '<button type="button" class="button button-primary" data-act="finish"' + (U.draft.length < 3 ? ' disabled' : '') + '>' + esc(T.finish) + '</button>' +
        '<button type="button" class="button" data-act="cancel">' + esc(T.cancel) + '</button>' : '') +
      '</div>' +
      '<p class="mdv-hint">' + esc(hint) + '</p>' +
      '<div class="mdv-stage-wrap">' + stageHTML() + '</div>' +
      '<div class="mdv-chips">' + chips + '<button type="button" class="mdv-chip mdv-chip-add" data-act="newArea"' + (o.image ? '' : ' disabled') + '>+ ' + esc(T.newArea) + '</button></div>';
  }

  function check(key, label) {
    return '<label class="mdv-check"><input type="checkbox" data-set="' + key + '"' + (S.settings[key] ? ' checked' : '') + '> <span>' + esc(label) + '</span></label>';
  }

  function settingsHTML() {
    var s = S.settings;
    return '<div class="mdv-title"><span class="mdv-kicker">' + esc(T.project) + '</span><strong>' + esc(T.settings) + '</strong></div>' +
      '<div class="mdv-settings">' +
      check('show_table', T.setTable) + check('sold_clickable', T.setSold) + check('show_outlines', T.setOutlines) +
      '<div class="mdv-group"><span class="mdv-kicker">' + esc(T.setTip) + '</span>' +
      check('tip_area', T.tipArea) + check('tip_outdoor', T.tipOutdoor) + check('tip_price', T.tipPrice) + check('tip_status', T.tipStatus) + '</div>' +
      '<label class="mdv-fld"><span>' + esc(T.buttonText) + '</span><input type="text" data-set="button_text" value="' + esc(s.button_text) + '" placeholder="' + esc(T.buttonPh) + '"></label>' +
      '<div class="mdv-group"><span class="mdv-kicker">' + esc(T.colors) + '</span>' +
      '<label class="mdv-fld"><span>' + esc(T.accent) + '</span><input type="text" data-set="color_accent" value="' + esc(s.color_accent) + '" placeholder="var(--bde-brand-primary-color)"><small>' + esc(T.accentHelp) + '</small></label>' +
      '<div class="mdv-row"><label class="mdv-fld"><span>' + esc(T.reserved) + '</span><input type="color" data-set="color_reserved" value="' + esc(s.color_reserved) + '"></label>' +
      '<label class="mdv-fld"><span>' + esc(T.sold) + '</span><input type="color" data-set="color_sold" value="' + esc(s.color_sold) + '"></label></div>' +
      '</div></div>';
  }

  function inspHTML() {
    if (U.ctx.type === 'settings') {
      return '<p class="mdv-help">' + esc(T.accentHelp) + '</p>';
    }
    var o = ctxObj();
    if (!o) return '';
    var a = selArea();
    var isView = U.ctx.type === 'view';
    var h = '';

    if (a) {
      if (isView) {
        var val = a.target ? a.target.type + ':' + a.target.id : '';
        h += '<label class="mdv-fld"><span>' + esc(T.target) + '</span><select data-area="target"><option value="">' + esc(T.chooseTarget) + '</option>';
        h += '<optgroup label="' + esc(T.floors) + '">' + S.floors.map(function (f) {
          var v = 'floor:' + f.id;
          return '<option value="' + esc(v) + '"' + (v === val ? ' selected' : '') + '>' + esc(f.name || T.unnamed) + '</option>';
        }).join('') + '</optgroup>';
        h += '<optgroup label="' + esc(T.views) + '">' + S.views.filter(function (v) { return v.id !== o.id; }).map(function (v) {
          var k = 'view:' + v.id;
          return '<option value="' + esc(k) + '"' + (k === val ? ' selected' : '') + '>' + esc(v.name || T.unnamed) + '</option>';
        }).join('') + '</optgroup></select></label>';
      } else {
        h += '<label class="mdv-fld"><span>' + esc(T.unit) + '</span><select data-area="unit"><option value="0">' + esc(T.chooseUnit) + '</option>' +
          units.map(function (u) { return '<option value="' + u.id + '"' + (u.id === a.unit ? ' selected' : '') + '>' + esc(u.label) + '</option>'; }).join('') +
          '</select></label>';
        if (!units.length) h += '<p class="mdv-help">' + esc(T.noUnits) + '</p>';
        var cu = unitById(a.unit);
        if (cu && cu.edit) h += '<p><a href="' + esc(cu.edit) + '" target="_blank" rel="noopener">' + esc(T.editUnit) + ' ↗</a></p>';
        h += '<p><a href="' + esc(CFG.newUnitUrl) + '" target="_blank" rel="noopener">+ ' + esc(T.newUnit) + ' ↗</a></p>';
        h += '<p class="mdv-help">' + esc(T.reloadNote) + ' ' + esc(T.mezonet) + '</p>';
      }
      h += '<div class="mdv-group"><span class="mdv-kicker">' + esc(T.points.replace('%d', a.poly.length)) + '</span>' +
        '<div class="mdv-tb"><button type="button" class="button" data-act="redraw">' + esc(T.redraw) + '</button>' +
        '<button type="button" class="button mdv-danger" data-act="delArea">' + esc(T.delArea) + '</button></div></div>';
      return h;
    }

    h += '<label class="mdv-fld"><span>' + esc(T.name) + '</span><input type="text" data-obj="name" value="' + esc(o.name) + '"></label>';
    if (isView) {
      h += '<label class="mdv-check"><input type="checkbox" data-obj="start"' + (S.start === o.id ? ' checked disabled' : '') + '> <span>' + esc(T.isStart) + '</span></label>';
    }
    h += '<div class="mdv-group"><button type="button" class="button mdv-danger" data-act="delObj">' + esc(isView ? T.delView : T.delFloor) + '</button></div>';
    return h;
  }

  /* ---------- render ---------- */
  function render() {
    root.innerHTML = '<div class="mdv"><div class="mdv-nav">' + navHTML() + '</div><div class="mdv-main">' + mainHTML() + '</div><div class="mdv-insp">' + inspHTML() + '</div></div>';
    sync();
  }
  function part(sel, html) { var el = root.querySelector(sel); if (el) el.innerHTML = html; }
  function renderNav() { part('.mdv-nav', navHTML()); sync(); }
  function renderMain() { part('.mdv-main', mainHTML()); sync(); }
  function renderStage() { part('.mdv-stage-wrap', stageHTML()); }

  /* ---------- akce ---------- */
  function setCtx(type, id) {
    U.ctx = { type: type, id: id }; U.sel = null; U.mode = 'select'; U.draft = [];
    render();
  }

  function finishDraw() {
    var o = ctxObj();
    if (!o || U.draft.length < 3) return;
    var poly = U.draft.map(function (p) { return [r2(p[0]), r2(p[1])]; });
    var a = selArea();
    if (a) {
      a.poly = poly;
    } else {
      a = { id: uid('a'), poly: poly };
      if (U.ctx.type === 'view') a.target = null; else a.unit = 0;
      o.areas.push(a);
      U.sel = a.id;
    }
    U.draft = []; U.mode = 'select';
    render();
  }

  function chooseImage() {
    if (!window.wp || !wp.media) return;
    var frame = wp.media({ title: T.chooseImage, button: { text: T.useImage }, library: { type: 'image' }, multiple: false });
    frame.on('select', function () {
      var att = frame.state().get('selection').first().toJSON();
      var o = ctxObj();
      if (!o) return;
      o.image = { id: att.id, url: att.url, w: att.width || 0, h: att.height || 0 };
      render();
    });
    frame.open();
  }

  function moveFloor(id, dir) {
    var i = S.floors.findIndex(function (f) { return f.id === id; });
    var j = i + dir;
    if (i < 0 || j < 0 || j >= S.floors.length) return;
    var tmp = S.floors[i]; S.floors[i] = S.floors[j]; S.floors[j] = tmp;
    renderNav();
  }

  function deleteObj() {
    var o = ctxObj();
    if (!o) return;
    if (U.ctx.type === 'view' && S.views.length < 2) { window.alert(T.lastView); return; }
    if (!window.confirm(T.confirmObj)) return;
    var type = U.ctx.type;
    if (type === 'view') S.views = S.views.filter(function (v) { return v.id !== o.id; });
    else S.floors = S.floors.filter(function (f) { return f.id !== o.id; });
    // Oblasti, které na smazané místo vedly, zůstanou bez cíle.
    S.views.forEach(function (v) {
      v.areas.forEach(function (a) { if (a.target && a.target.type === type && a.target.id === o.id) a.target = null; });
    });
    if (S.start === o.id) S.start = S.views[0].id;
    setCtx('view', S.start);
  }

  function toPct(e, stage) {
    var r = stage.getBoundingClientRect();
    return [
      Math.min(100, Math.max(0, (e.clientX - r.left) / r.width * 100)),
      Math.min(100, Math.max(0, (e.clientY - r.top) / r.height * 100))
    ];
  }

  /* ---------- události ---------- */
  root.addEventListener('click', function (e) {
    var t = e.target;
    var el;
    if ((el = t.closest('[data-ctx]'))) {
      var parts = el.getAttribute('data-ctx').split(':');
      setCtx(parts[0], parts[1]);
      return;
    }
    if ((el = t.closest('[data-mode]'))) {
      U.mode = el.getAttribute('data-mode'); U.draft = [];
      renderMain();
      return;
    }
    if ((el = t.closest('[data-sel]'))) {
      U.sel = el.getAttribute('data-sel'); U.mode = 'select'; U.draft = [];
      render();
      return;
    }
    if (!(el = t.closest('[data-act]'))) return;
    var act = el.getAttribute('data-act');
    var o = ctxObj();
    switch (act) {
      case 'addView': {
        var v = { id: uid('v'), name: T.newViewName, image: null, areas: [] };
        S.views.push(v); setCtx('view', v.id); break;
      }
      case 'addFloor': {
        var f = { id: uid('f'), name: T.newFloorName, image: null, areas: [] };
        S.floors.push(f); setCtx('floor', f.id); break;
      }
      case 'up': moveFloor(el.getAttribute('data-id'), -1); break;
      case 'down': moveFloor(el.getAttribute('data-id'), 1); break;
      case 'image': chooseImage(); break;
      case 'newArea': U.sel = null; U.mode = 'draw'; U.draft = []; render(); break;
      case 'finish': finishDraw(); break;
      case 'cancel': U.mode = 'select'; U.draft = []; renderMain(); break;
      case 'redraw': U.mode = 'draw'; U.draft = []; renderMain(); break;
      case 'delArea':
        if (o && window.confirm(T.confirmArea)) {
          o.areas = o.areas.filter(function (a) { return a.id !== U.sel; });
          U.sel = null; render();
        }
        break;
      case 'delObj': deleteObj(); break;
    }
  });

  root.addEventListener('pointerdown', function (e) {
    var stage = e.target.closest('.mdv-stage');
    if (!stage || stage.classList.contains('mdv-empty')) return;
    if (U.mode === 'draw') {
      e.preventDefault();
      if (e.target.classList.contains('first') && U.draft.length >= 3) { finishDraw(); return; }
      U.draft.push(toPct(e, stage));
      renderMain();
      return;
    }
    var h = e.target.closest('.mdv-handle');
    if (h) { U.drag = { i: +h.getAttribute('data-i') }; e.preventDefault(); return; }
    var poly = e.target.closest('polygon[data-id]');
    if (poly) { U.sel = poly.getAttribute('data-id'); render(); }
  });

  window.addEventListener('pointermove', function (e) {
    if (!U.drag) return;
    var stage = root.querySelector('.mdv-stage');
    var a = selArea();
    if (!stage || !a) return;
    var p = toPct(e, stage);
    a.poly[U.drag.i] = [r2(p[0]), r2(p[1])];
    renderStage();
  });

  window.addEventListener('pointerup', function () {
    if (U.drag) { U.drag = null; sync(); }
  });

  function onField(e) {
    var t = e.target;
    var o = ctxObj();
    if (t.hasAttribute('data-set')) {
      var k = t.getAttribute('data-set');
      S.settings[k] = t.type === 'checkbox' ? t.checked : t.value;
      sync();
      return;
    }
    if (t.hasAttribute('data-obj') && o) {
      if (t.getAttribute('data-obj') === 'name') {
        o.name = t.value; renderNav(); part('.mdv-title strong', esc(o.name || T.unnamed)); return;
      }
      if (t.getAttribute('data-obj') === 'start' && t.checked) { S.start = o.id; render(); return; }
    }
    if (t.hasAttribute('data-area') && e.type === 'change') {
      var a = selArea();
      if (!a) return;
      if (t.getAttribute('data-area') === 'target') {
        var v = t.value.split(':');
        a.target = t.value ? { type: v[0], id: v[1] } : null;
      } else {
        a.unit = parseInt(t.value, 10) || 0;
      }
      render();
    }
  }
  root.addEventListener('input', onField);
  root.addEventListener('change', onField);

  // Enter v polích editoru nesmí odeslat formulář příspěvku.
  root.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.matches('input')) e.preventDefault();
  });

  document.addEventListener('keydown', function (e) {
    if (U.mode !== 'draw') return;
    if (e.target.matches && e.target.matches('input, select, textarea, [contenteditable]')) return;
    if (e.key === 'Enter') { e.preventDefault(); finishDraw(); }
    else if (e.key === 'Escape') { U.mode = 'select'; U.draft = []; renderMain(); }
    else if (e.key === 'Backspace') { e.preventDefault(); U.draft.pop(); renderMain(); }
  });

  render();
})();
