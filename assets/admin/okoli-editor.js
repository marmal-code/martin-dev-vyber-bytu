/* Marmal – Developerské projekty: editor mapy okolí (rozšíření editoru projektu, bez závislostí, jen wp.media).
 * editor.js zavolá window.martinDvOkoliEditor(api) a předá sdílený stav projektu (S), stav editoru (U) a pomocné funkce.
 * Mapy se ukládají do S.maps – stejné skryté pole a stejné uložení projektu (nonce + edit_post) jako pohledy a podlaží.
 */
(function () {
  'use strict';

  window.martinDvOkoliEditor = function (api) {
    var C = api.CFG.okoli;
    if (!C) return null;
    var T = C.i18n, S = api.S, U = api.U, esc = api.esc;
    var catKeys = Object.keys(C.cats);

    S.maps = Array.isArray(S.maps) ? S.maps : [];
    S.maps.forEach(function (m) {
      m.points = Array.isArray(m.points) ? m.points : [];
      if (['cisla', 'ikony', 'kombi'].indexOf(m.mode) < 0) m.mode = 'cisla';
    });

    var O = { sel: null, mode: 'select', drag: null, dragRow: null, moved: false };

    /* ---------- pomocné ---------- */
    function cur() {
      if (U.ctx.type !== 'map') return null;
      for (var i = 0; i < S.maps.length; i++) { if (S.maps[i].id === U.ctx.id) return S.maps[i]; }
      return null;
    }
    function point(m, id) {
      if (!m) return null;
      for (var i = 0; i < m.points.length; i++) { if (m.points[i].id === id) return m.points[i]; }
      return null;
    }
    function pad(n) { return n < 10 ? '0' + n : String(n); }
    function iconSvg(key) { return (C.icons[key] || C.icons.bod || { svg: '' }).svg; }
    function catColor(key) { return (C.cats[key] || {}).color || '#2271b1'; }
    function aspect(m) { return (m.image && m.image.w && m.image.h) ? (m.image.w / m.image.h) : 1.5; }
    function fmt(v) { return (Math.round(v * 10) / 10).toString().replace('.', ',') + ' %'; }

    function markInner(m, p, i) {
      if (m.mode === 'cisla') return pad(i + 1);
      if (m.mode === 'ikony') return iconSvg(p.icon);
      return iconSvg(p.icon) + '<b>' + pad(i + 1) + '</b>';
    }
    function markStyle(m, p) {
      return m.cat_colors ? ';--okc:' + esc(catColor(p.cat)) : '';
    }

    /* ---------- HTML ---------- */
    function navHTML() {
      var h = '<div class="mdv-h">' + esc(T.section) + '</div>';
      S.maps.forEach(function (m) {
        var on = U.ctx.type === 'map' && U.ctx.id === m.id;
        h += '<button type="button" class="mdv-navbtn' + (on ? ' on' : '') + '" data-ctx="map:' + esc(m.id) + '"><span>' + esc(m.name || T.unnamed) + '</span><small>' + m.points.length + '</small></button>';
      });
      h += '<button type="button" class="mdv-add" data-ok-act="addMap">+ ' + esc(T.addMap) + '</button>';
      return h;
    }

    function stageHTML(m) {
      if (!m.image || !m.image.url) {
        return '<div class="mdv-okstage mdv-empty" style="aspect-ratio:1.5"><p>' + esc(T.noImage) + '</p></div>';
      }
      var mk = m.points.map(function (p, i) {
        return '<span class="mdv-okmk' + (m.mode !== 'cisla' ? ' is-icon' : '') + (O.sel === p.id ? ' on' : '') + '" data-ok-pt="' + esc(p.id) + '" title="' + esc(pad(i + 1) + ' ' + (p.name || T.unnamed)) + '" style="left:' + p.x + '%;top:' + p.y + '%' + markStyle(m, p) + '">' + markInner(m, p, i) + '</span>';
      }).join('');
      if (m.proj) {
        mk += '<span class="mdv-okmk mdv-okproj' + (O.sel === 'proj' ? ' on' : '') + '" data-ok-pt="proj" title="' + esc(T.proj) + '" style="left:' + m.proj.x + '%;top:' + m.proj.y + '%"></span>';
      }
      return '<div class="mdv-okstage ok-' + O.mode + '" style="aspect-ratio:' + aspect(m) + '">' +
        '<img src="' + esc(m.image.url) + '" alt="" draggable="false">' + mk + '</div>';
    }

    function listHTML(m) {
      if (!m.points.length) return '<p class="mdv-help">' + esc(T.noPoints) + '</p>';
      return '<ol class="mdv-oklist">' + m.points.map(function (p, i) {
        var cat = C.cats[p.cat] ? C.cats[p.cat].name : '';
        return '<li class="mdv-okrow' + (O.sel === p.id ? ' on' : '') + '" draggable="true" data-ok-row="' + esc(p.id) + '">' +
          '<span class="mdv-okgrip" aria-hidden="true">⋮⋮</span>' +
          '<button type="button" class="mdv-okrowbtn" data-ok-sel="' + esc(p.id) + '">' +
          '<span class="mdv-oknum' + (m.mode !== 'cisla' ? ' is-icon' : '') + '" style="' + markStyle(m, p).slice(1) + '">' + markInner(m, p, i) + '</span>' +
          '<span class="mdv-okname">' + esc(p.name || T.unnamed) + (p.desc ? '<small>' + esc(p.desc) + '</small>' : '') + '</span>' +
          '<span class="mdv-okcat">' + esc(cat) + (p.dist ? ' · ' + esc(p.dist) : '') + '</span></button>' +
          '<span class="mdv-order"><button type="button" data-ok-act="up" data-ok-id="' + esc(p.id) + '" aria-label="' + esc(T.up) + '"' + (i === 0 ? ' disabled' : '') + '>↑</button>' +
          '<button type="button" data-ok-act="down" data-ok-id="' + esc(p.id) + '" aria-label="' + esc(T.down) + '"' + (i === m.points.length - 1 ? ' disabled' : '') + '>↓</button></span></li>';
      }).join('') + '</ol>';
    }

    function hint() {
      return O.mode === 'add' ? T.hintAdd : (O.mode === 'proj' ? T.hintProj : T.hintSelect);
    }

    function mainHTML() {
      var m = cur();
      if (!m) return '';
      var seg = function (mode, label) {
        return '<button type="button" data-ok-mode="' + mode + '" class="' + (O.mode === mode ? 'on' : '') + '"' + (m.image ? '' : ' disabled') + '>' + esc(label) + '</button>';
      };
      return '<div class="mdv-title"><span class="mdv-kicker">' + esc(T.map) + '</span><strong>' + esc(m.name || T.unnamed) + '</strong></div>' +
        '<p class="mdv-help">' + esc(T.mapHelp) + '</p>' +
        '<p class="mdv-help mdv-okwarn">' + esc(T.mapLicense) + '</p>' +
        '<div class="mdv-tb"><button type="button" class="button" data-ok-act="image">' + esc(m.image ? T.changeImage : T.chooseImage) + '</button>' +
        '<span class="mdv-seg">' + seg('select', T.modeSelect) + seg('add', T.modeAdd) + seg('proj', T.modeProj) + '</span></div>' +
        '<p class="mdv-hint mdv-okhint">' + esc(hint()) + '</p>' +
        '<div class="mdv-okwrap">' + stageHTML(m) + '</div>' +
        '<div class="mdv-group"><span class="mdv-kicker">' + esc(T.points) + '</span><p class="mdv-help">' + esc(T.dragHelp) + '</p>' +
        '<div class="mdv-oklist-wrap">' + listHTML(m) + '</div></div>';
    }

    function fld(label, input, help) {
      return '<label class="mdv-fld"><span>' + esc(label) + '</span>' + input + (help ? '<small>' + esc(help) + '</small>' : '') + '</label>';
    }
    function text(attr, key, value, ph, type) {
      return '<input type="' + (type || 'text') + '" ' + attr + '="' + key + '" value="' + esc(value || '') + '"' + (ph ? ' placeholder="' + esc(ph) + '"' : '') + '>';
    }

    function inspHTML() {
      var m = cur();
      if (!m) return '';
      var h = '';

      if (O.sel === 'proj' && m.proj) {
        h += '<span class="mdv-kicker">' + esc(T.proj) + '</span>';
        h += fld(T.projName, text('data-ok-proj', 'name', m.proj.name, T.projNamePh));
        h += fld(T.projDesc, text('data-ok-proj', 'desc', m.proj.desc, T.projDescPh));
        h += '<p class="mdv-help">' + esc(T.position.replace('%1$s', fmt(m.proj.x)).replace('%2$s', fmt(m.proj.y))) + '</p>';
        h += '<div class="mdv-group"><button type="button" class="button mdv-danger" data-ok-act="delProj">' + esc(T.projDel) + '</button></div>';
        return h;
      }

      var p = point(m, O.sel);
      if (p) {
        var idx = m.points.indexOf(p);
        h += '<span class="mdv-kicker">' + esc(pad(idx + 1)) + '</span>';
        h += fld(T.name, text('data-ok-pf', 'name', p.name, ''));
        h += fld(T.cat, '<select data-ok-pf="cat">' + catKeys.map(function (k) {
          return '<option value="' + esc(k) + '"' + (k === p.cat ? ' selected' : '') + '>' + esc(C.cats[k].name) + '</option>';
        }).join('') + '</select>');
        h += '<div class="mdv-fld"><span>' + esc(T.icon) + '</span><div class="mdv-okicons">' + Object.keys(C.icons).map(function (k) {
          return '<button type="button" class="mdv-okicon' + (k === p.icon ? ' on' : '') + '" data-ok-icon="' + esc(k) + '" title="' + esc(C.icons[k].name) + '" aria-label="' + esc(C.icons[k].name) + '">' + C.icons[k].svg + '</button>';
        }).join('') + '</div></div>';
        h += fld(T.desc, text('data-ok-pf', 'desc', p.desc, T.descPh));
        h += fld(T.dist, text('data-ok-pf', 'dist', p.dist, T.distPh));
        h += fld(T.url, text('data-ok-pf', 'url', p.url, 'https://', 'url'), T.urlHelp);
        h += '<p class="mdv-help">' + esc(T.position.replace('%1$s', fmt(p.x)).replace('%2$s', fmt(p.y))) + '</p>';
        h += '<div class="mdv-group"><button type="button" class="button mdv-danger" data-ok-act="delPoint">' + esc(T.delPoint) + '</button></div>';
        return h;
      }

      var idxMap = S.maps.indexOf(m) + 1;
      h += fld(T.mapName, text('data-ok-map', 'name', m.name, ''));
      h += fld(T.markers, '<select data-ok-map="mode">' +
        '<option value="cisla"' + (m.mode === 'cisla' ? ' selected' : '') + '>' + esc(T.mCisla) + '</option>' +
        '<option value="ikony"' + (m.mode === 'ikony' ? ' selected' : '') + '>' + esc(T.mIkony) + '</option>' +
        '<option value="kombi"' + (m.mode === 'kombi' ? ' selected' : '') + '>' + esc(T.mKombi) + '</option></select>');
      h += '<label class="mdv-check"><input type="checkbox" data-ok-map="cat_colors"' + (m.cat_colors ? ' checked' : '') + '> <span>' + esc(T.catColors) + '</span></label>';
      h += '<p class="mdv-help">' + esc(T.catColorsH) + '</p>';
      h += '<label class="mdv-check"><input type="checkbox" data-ok-map="filter"' + (m.filter ? ' checked' : '') + '> <span>' + esc(T.filter) + '</span></label>';
      if (!m.proj) h += '<p class="mdv-help">' + esc(T.projNone) + '</p>';
      h += '<div class="mdv-group"><span class="mdv-kicker">' + esc(T.embed) + '</span>' +
        '<p class="mdv-help">' + esc(T.order.replace('%d', idxMap)) + '</p>' +
        '<code class="mdv-okcode">[martin_mapa_okoli id="' + esc(String(api.CFG.projectId)) + '" mapa="' + idxMap + '"]</code></div>';
      h += '<div class="mdv-group"><button type="button" class="button mdv-danger" data-ok-act="delMap">' + esc(T.delMap) + '</button></div>';
      return h;
    }

    /* ---------- překreslení ---------- */
    function rMain() { api.part('.mdv-main', mainHTML()); api.sync(); }
    function rInsp() { api.part('.mdv-insp', inspHTML()); api.sync(); }
    function rStage() { var m = cur(); if (m) api.part('.mdv-okwrap', stageHTML(m)); }
    function rList() { var m = cur(); if (m) api.part('.mdv-oklist-wrap', listHTML(m)); }
    function rAll() { api.render(); }
    function select(id) { O.sel = id; rStage(); rList(); rInsp(); }
    // Zvýraznění vybrané značky bez překreslení mapy (při tažení musí značka zůstat stejný prvek).
    function markSel() {
      root.querySelectorAll('.mdv-okmk').forEach(function (el) { el.classList.toggle('on', el.getAttribute('data-ok-pt') === O.sel); });
    }
    function endDrag() {
      if (!O.drag) return;
      O.drag = null;
      if (O.moved) rInsp();
      api.sync();
    }

    /* ---------- akce ---------- */
    function chooseImage() {
      if (!window.wp || !wp.media) return;
      var frame = wp.media({ title: T.chooseImage, button: { text: T.useImage }, library: { type: 'image' }, multiple: false });
      frame.on('select', function () {
        var att = frame.state().get('selection').first().toJSON();
        var m = cur();
        if (!m) return;
        m.image = { id: att.id, url: att.url, w: att.width || 0, h: att.height || 0 };
        rAll();
      });
      frame.open();
    }

    function movePoint(m, from, to) {
      if (from < 0 || to < 0 || from >= m.points.length || to >= m.points.length || from === to) return;
      var p = m.points.splice(from, 1)[0];
      m.points.splice(to, 0, p);
    }

    function toPct(e, stage) {
      var r = stage.getBoundingClientRect();
      return [
        Math.round(Math.min(100, Math.max(0, (e.clientX - r.left) / r.width * 100)) * 100) / 100,
        Math.round(Math.min(100, Math.max(0, (e.clientY - r.top) / r.height * 100)) * 100) / 100
      ];
    }

    /* ---------- události ---------- */
    var root = api.root;

    root.addEventListener('click', function (e) {
      var el, m = cur();
      if ((el = e.target.closest('[data-ok-act]'))) {
        var act = el.getAttribute('data-ok-act');
        if (act === 'addMap') {
          var nm = { id: api.uid('m'), name: T.newMapName, image: null, mode: 'cisla', cat_colors: false, filter: false, proj: null, points: [] };
          S.maps.push(nm);
          O.sel = null; O.mode = 'select';
          api.setCtx('map', nm.id);
          return;
        }
        if (!m) return;
        switch (act) {
          case 'image': chooseImage(); break;
          case 'up':
          case 'down': {
            var i = m.points.findIndex(function (p) { return p.id === el.getAttribute('data-ok-id'); });
            movePoint(m, i, i + (act === 'up' ? -1 : 1));
            rStage(); rList(); rInsp();
            break;
          }
          case 'delPoint':
            if (window.confirm(T.confirmPt)) {
              m.points = m.points.filter(function (p) { return p.id !== O.sel; });
              O.sel = null; rAll();
            }
            break;
          case 'delProj': m.proj = null; O.sel = null; rAll(); break;
          case 'delMap':
            if (window.confirm(T.confirmMap)) {
              S.maps = S.maps.filter(function (x) { return x.id !== m.id; });
              O.sel = null;
              api.setCtx('view', S.start);
            }
            break;
        }
        return;
      }
      if ((el = e.target.closest('[data-ok-mode]'))) {
        O.mode = el.getAttribute('data-ok-mode');
        rMain();
        return;
      }
      if ((el = e.target.closest('[data-ok-sel]'))) { select(el.getAttribute('data-ok-sel')); return; }
      if ((el = e.target.closest('[data-ok-icon]')) && m) {
        var p = point(m, O.sel);
        if (p) { p.icon = el.getAttribute('data-ok-icon'); rStage(); rList(); rInsp(); }
      }
    });

    root.addEventListener('pointerdown', function (e) {
      var stage = e.target.closest('.mdv-okstage');
      var m = cur();
      if (!stage || !m || stage.classList.contains('mdv-empty')) return;
      e.preventDefault();
      var pos = toPct(e, stage);
      if (O.mode === 'add') {
        var cat = catKeys[0];
        var np = { id: api.uid('p'), name: T.newPoint, cat: cat, icon: C.cats[cat].icon, desc: '', dist: '', url: '', x: pos[0], y: pos[1] };
        m.points.push(np);
        O.sel = np.id;
        api.render();
        var inp = root.querySelector('[data-ok-pf="name"]');
        if (inp) { inp.focus(); inp.select(); }
        return;
      }
      if (O.mode === 'proj') {
        m.proj = { x: pos[0], y: pos[1], name: m.proj ? m.proj.name : '', desc: m.proj ? m.proj.desc : '' };
        O.mode = 'select'; O.sel = 'proj';
        api.render();
        return;
      }
      var mk = e.target.closest('[data-ok-pt]');
      if (mk) {
        O.sel = mk.getAttribute('data-ok-pt');
        O.drag = { id: O.sel };
        O.moved = false;
        markSel(); rList(); rInsp();
      } else if (O.sel) {
        select(null);
      }
    });

    window.addEventListener('pointermove', function (e) {
      if (!O.drag) return;
      if (e.buttons === 0) { endDrag(); return; } // tlačítko už není stisknuté (pointerup se ztratil)
      var m = cur(), stage = root.querySelector('.mdv-okstage');
      if (!m || !stage) return;
      var pos = toPct(e, stage);
      var target = O.drag.id === 'proj' ? m.proj : point(m, O.drag.id);
      if (!target) return;
      target.x = pos[0]; target.y = pos[1];
      O.moved = true;
      var el = stage.querySelector('[data-ok-pt="' + O.drag.id + '"]');
      if (el) { el.style.left = pos[0] + '%'; el.style.top = pos[1] + '%'; }
    });

    window.addEventListener('pointerup', endDrag);
    window.addEventListener('pointercancel', endDrag);
    window.addEventListener('blur', endDrag);

    /* přetažení řádků v seznamu bodů */
    root.addEventListener('dragstart', function (e) {
      var row = e.target.closest && e.target.closest('[data-ok-row]');
      if (!row) return;
      O.dragRow = row.getAttribute('data-ok-row');
      row.classList.add('is-drag');
      if (e.dataTransfer) { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', O.dragRow); }
    });
    root.addEventListener('dragover', function (e) {
      if (!O.dragRow) return;
      var row = e.target.closest('[data-ok-row]');
      if (!row) return;
      e.preventDefault();
      root.querySelectorAll('.mdv-okrow.is-over').forEach(function (r) { r.classList.remove('is-over'); });
      row.classList.add('is-over');
    });
    root.addEventListener('drop', function (e) {
      if (!O.dragRow) return;
      var row = e.target.closest('[data-ok-row]');
      var m = cur();
      e.preventDefault();
      if (row && m) {
        var ids = m.points.map(function (p) { return p.id; });
        movePoint(m, ids.indexOf(O.dragRow), ids.indexOf(row.getAttribute('data-ok-row')));
      }
      O.dragRow = null;
      rStage(); rList(); rInsp();
    });
    root.addEventListener('dragend', function () {
      if (!O.dragRow) return;
      O.dragRow = null;
      rList();
    });

    function onField(e) {
      var t = e.target, m = cur();
      if (!m) return;
      var k;
      if ((k = t.getAttribute('data-ok-map'))) {
        if (k === 'name') {
          m.name = t.value;
          api.part('.mdv-title strong', esc(m.name || T.unnamed));
          api.renderNav();
          return;
        }
        if (e.type !== 'change') return;
        m[k] = t.type === 'checkbox' ? t.checked : t.value;
        rStage(); rList(); api.sync();
        return;
      }
      if ((k = t.getAttribute('data-ok-proj')) && m.proj) {
        m.proj[k] = t.value;
        api.sync();
        return;
      }
      if ((k = t.getAttribute('data-ok-pf'))) {
        var p = point(m, O.sel);
        if (!p) return;
        if (k === 'cat') {
          if (e.type !== 'change') return;
          var old = C.cats[p.cat] ? C.cats[p.cat].icon : '';
          p.cat = t.value;
          if (!p.icon || p.icon === old) p.icon = C.cats[p.cat].icon;
          rStage(); rList(); rInsp();
          return;
        }
        p[k] = t.value;
        rList(); api.sync();
      }
    }
    root.addEventListener('input', onField);
    root.addEventListener('change', onField);

    return { navHTML: navHTML, mainHTML: mainHTML, inspHTML: inspHTML, reset: function () { O.sel = null; O.mode = 'select'; } };
  };
})();
