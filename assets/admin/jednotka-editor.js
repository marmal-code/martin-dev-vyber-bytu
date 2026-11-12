/* Marmal – Developerské projekty: editor místností, venkovních ploch a parametrů u jednotky (od 0.14.0).
 * Bez závislostí. Data jdou jako JSON do skrytého pole #martin-dv-unit-data, PHP je při uložení sanitizuje.
 * Veškerý text se vkládá přes esc() – žádné neošetřené innerHTML z dat. */
(function () {
  'use strict';

  var CFG = window.martinDvUnitEditor;
  var root = document.getElementById('martin-dv-unit-editor');
  var input = document.getElementById('martin-dv-unit-data');
  if (!CFG || !root || !input) return;

  var T = CFG.i18n;
  var S;
  try { S = JSON.parse(input.value || '{}') || {}; } catch (e) { S = {}; }
  ['rooms', 'outdoors', 'params', 'accessories'].forEach(function (k) { if (!Array.isArray(S[k])) S[k] = []; });

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function toNum(v) {
    var n = parseFloat(String(v == null ? '' : v).replace(/\s| /g, '').replace(',', '.'));
    return isFinite(n) && n > 0 ? Math.round(n * 100) / 100 : 0;
  }
  function showNum(n) { return n ? String(n).replace('.', ',') : ''; }
  function fmtArea(n) {
    return (Math.round(n * 100) / 100).toLocaleString('cs-CZ', { maximumFractionDigits: 2 }) + ' m²';
  }
  function sync() { input.value = JSON.stringify(S); updateSums(); }

  /* ---------- řádky ---------- */
  function tools(list, i) {
    var n = S[list].length;
    return '<td class="mdu-tools">' +
      '<button type="button" class="button-link" data-act="up" data-list="' + list + '" data-i="' + i + '" aria-label="' + esc(T.up) + '"' + (i === 0 ? ' disabled' : '') + '>↑</button>' +
      '<button type="button" class="button-link" data-act="down" data-list="' + list + '" data-i="' + i + '" aria-label="' + esc(T.down) + '"' + (i === n - 1 ? ' disabled' : '') + '>↓</button>' +
      '<button type="button" class="button-link mdu-del" data-act="del" data-list="' + list + '" data-i="' + i + '" aria-label="' + esc(T.remove) + '">×</button></td>';
  }
  function txt(list, i, key, val, ph, extra) {
    return '<input type="text" data-list="' + list + '" data-i="' + i + '" data-k="' + key + '" value="' + esc(val) + '"' +
      (ph ? ' placeholder="' + esc(ph) + '"' : '') + (extra || '') + '>';
  }
  function num(list, i, val) {
    return '<input type="text" inputmode="decimal" class="mdu-num" data-list="' + list + '" data-i="' + i + '" data-k="plocha" value="' + esc(showNum(val)) + '">';
  }
  function select(list, i, key, options, val) {
    var h = '<select data-list="' + list + '" data-i="' + i + '" data-k="' + key + '">';
    Object.keys(options).forEach(function (k) {
      h += '<option value="' + esc(k) + '"' + (k === val ? ' selected' : '') + '>' + esc(options[k]) + '</option>';
    });
    return h + '</select>';
  }

  function roomsHTML() {
    var rows = S.rooms.map(function (r, i) {
      return '<tr>' +
        '<td>' + txt('rooms', i, 'nazev', r.nazev, T.roomPh) + '</td>' +
        '<td>' + num('rooms', i, r.plocha) + '</td>' +
        '<td>' + select('rooms', i, 'typ', CFG.roomTypes, r.typ || 'obytna') + '</td>' +
        '<td class="mdu-c"><input type="checkbox" data-list="rooms" data-i="' + i + '" data-k="zapocitat" title="' + esc(T.countTitle.replace('%s', CFG.areaLabel)) + '"' + (r.zapocitat !== false ? ' checked' : '') + '></td>' +
        '<td>' + txt('rooms', i, 'poznamka', r.poznamka, T.notePh) + '</td>' +
        tools('rooms', i) + '</tr>';
    }).join('');
    return section(T.rooms, T.roomsHelp,
      '<th>' + esc(T.name) + '</th><th class="mdu-w-num">' + esc(T.area) + '</th><th class="mdu-w-type">' + esc(T.type) + '</th>' +
      '<th class="mdu-c" title="' + esc(T.countTitle.replace('%s', CFG.areaLabel)) + '">' + esc(T.count) + '</th><th>' + esc(T.note) + '</th><th></th>',
      rows, 'rooms', T.addRoom) +
      '<div class="mdu-sums" aria-live="polite"></div>';
  }

  function outdoorsHTML() {
    var rows = S.outdoors.map(function (o, i) {
      var dirs = Array.isArray(o.orientace) ? o.orientace : [];
      var chips = CFG.orientations.map(function (d) {
        return '<label class="mdu-dir" title="' + esc(d.long) + '"><input type="checkbox" data-list="outdoors" data-i="' + i + '" data-dir="' + esc(d.key) + '"' +
          (dirs.indexOf(d.key) !== -1 ? ' checked' : '') + '><span>' + esc(d.short) + '</span></label>';
      }).join('');
      return '<tr>' +
        '<td>' + select('outdoors', i, 'typ', CFG.outdoorTypes, o.typ || Object.keys(CFG.outdoorTypes)[0]) + '</td>' +
        '<td>' + num('outdoors', i, o.plocha) + '</td>' +
        '<td><div class="mdu-dirs">' + chips + '</div></td>' +
        tools('outdoors', i) + '</tr>';
    }).join('');
    return section(T.outdoors, T.outdoorsHelp,
      '<th class="mdu-w-type">' + esc(T.type) + '</th><th class="mdu-w-num">' + esc(T.area) + '</th><th>' + esc(T.orientation) + '</th><th></th>',
      rows, 'outdoors', T.addOutdoor);
  }

  function paramsHTML() {
    var rows = S.params.map(function (p, i) {
      return '<tr>' +
        '<td>' + txt('params', i, 'nazev', p.nazev, T.paramPh, ' list="mdu-suggest"') + '</td>' +
        '<td>' + txt('params', i, 'hodnota', p.hodnota, T.valuePh) + '</td>' +
        tools('params', i) + '</tr>';
    }).join('');
    var dl = '<datalist id="mdu-suggest">' + CFG.suggestions.map(function (s) { return '<option value="' + esc(s) + '">'; }).join('') + '</datalist>';
    return section(T.params, T.paramsHelp,
      '<th>' + esc(T.name) + '</th><th>' + esc(T.value) + '</th><th></th>', rows, 'params', T.addParam) + dl;
  }

  /* Od 0.17.0: příslušenství (stání, garáž, sklep, jiné) s volitelnou vazbou na samostatnou jednotku. */
  function accessoriesHTML() {
    var units = CFG.accUnits || [];
    var rows = S.accessories.map(function (a, i) {
      var opts = '<option value="0">' + esc(T.accNoUnit) + '</option>' + units.map(function (u) {
        return '<option value="' + u.id + '"' + (+a.jednotka === u.id ? ' selected' : '') + '>' + esc(u.label) + (u.parent ? ' ' + esc(T.accTaken.replace('%s', u.parent)) : '') + '</option>';
      }).join('');
      return '<tr>' +
        '<td>' + select('accessories', i, 'typ', CFG.accTypes, a.typ || 'stani') + '</td>' +
        '<td>' + txt('accessories', i, 'nazev', a.nazev, T.accNamePh) + '</td>' +
        '<td>' + txt('accessories', i, 'oznaceni', a.oznaceni, T.accCodePh) + '</td>' +
        '<td>' + num('accessories', i, a.plocha) + '</td>' +
        '<td><input type="text" inputmode="numeric" class="mdu-num" data-list="accessories" data-i="' + i + '" data-k="cena" value="' + esc(a.cena ? String(a.cena) : '') + '"></td>' +
        '<td class="mdu-c"><input type="checkbox" data-list="accessories" data-i="' + i + '" data-k="v_cene"' + (a.v_cene ? ' checked' : '') + '></td>' +
        '<td><select data-list="accessories" data-i="' + i + '" data-k="jednotka">' + opts + '</select></td>' +
        tools('accessories', i) + '</tr>';
    }).join('');
    return section(T.acc, T.accHelp,
      '<th class="mdu-w-type">' + esc(T.type) + '</th><th>' + esc(T.accName) + '</th><th class="mdu-w-code">' + esc(T.accCode) + '</th><th class="mdu-w-num">' + esc(T.area) + '</th>' +
      '<th class="mdu-w-num">' + esc(T.accPrice) + '</th><th class="mdu-c">' + esc(T.accIncluded) + '</th><th>' + esc(T.accUnit) + '</th><th></th>',
      rows, 'accessories', T.addAcc);
  }

  function section(title, help, head, rows, list, add) {
    return '<div class="mdu-sec"><h4>' + esc(title) + '</h4><p class="description">' + esc(help) + '</p>' +
      (rows ? '<table class="widefat mdu-table"><thead><tr>' + head + '</tr></thead><tbody>' + rows + '</tbody></table>' : '<p class="mdu-empty">' + esc(T.empty) + '</p>') +
      '<p><button type="button" class="button" data-act="add" data-list="' + list + '">+ ' + esc(add) + '</button></p></div>';
  }

  /* ---------- součty a kontrola plochy ---------- */
  function totalField() {
    return document.querySelector('.acf-field[data-name="dv_plocha"] input');
  }
  function updateSums() {
    var box = root.querySelector('.mdu-sums');
    if (!box) return;
    var counted = 0, other = 0, outdoor = 0;
    S.rooms.forEach(function (r) { if (r.zapocitat !== false) counted += r.plocha || 0; else other += r.plocha || 0; });
    S.outdoors.forEach(function (o) { outdoor += o.plocha || 0; });
    var h = '';
    if (counted || other) {
      h += '<span><strong>' + esc(T.sumRooms.replace('%s', CFG.areaLabel)) + ':</strong> ' + esc(fmtArea(counted)) + '</span>';
      if (other) h += '<span>' + esc(T.sumOther) + ': ' + esc(fmtArea(other)) + '</span>';
    }
    if (outdoor) h += '<span>' + esc(T.sumOutdoor) + ': ' + esc(fmtArea(outdoor)) + '</span>';
    var f = totalField(), total = f ? toNum(f.value) : 0, msg = '';
    if (counted && total) {
      // Celková plocha zahrnuje příčky a zdi → součet místností smí být stejný nebo menší.
      // Tolerance 0,1 m² kvůli zaokrouhlení ploch v kartách bytů.
      var diff = Math.round((counted - total) * 100) / 100, tl = CFG.totalLabel.toLowerCase();
      if (diff > 0.1) {
        msg = '<p class="mdu-warn">⚠ ' + esc(T.over.replace('%1$s', fmtArea(counted)).replace('%2$s', tl).replace('%3$s', fmtArea(total)).replace('%4$s', fmtArea(diff))) + '</p>';
      } else if (diff < -0.1) {
        msg = '<p class="mdu-ok">✓ ' + esc(T.under.replace('%1$s', fmtArea(-diff)).replace('%2$s', tl)) + '</p>';
      } else {
        msg = '<p class="mdu-ok">✓ ' + esc(T.match.replace('%s', tl)) + '</p>';
      }
    }
    box.innerHTML = (h ? '<div class="mdu-sumline">' + h + '</div>' : '') + msg;
  }

  /* ---------- render a události ---------- */
  function render() {
    root.innerHTML = roomsHTML() + outdoorsHTML() + paramsHTML() + accessoriesHTML();
    sync();
  }

  function blank(list) {
    if (list === 'rooms') return { nazev: '', plocha: 0, typ: 'obytna', zapocitat: true, poznamka: '' };
    if (list === 'outdoors') return { typ: Object.keys(CFG.outdoorTypes)[0], plocha: 0, orientace: [] };
    if (list === 'accessories') return { typ: 'stani', nazev: '', oznaceni: '', plocha: 0, cena: 0, v_cene: false, jednotka: 0 };
    return { nazev: '', hodnota: '' };
  }

  root.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-act]');
    if (!b) return;
    e.preventDefault();
    var list = b.getAttribute('data-list'), i = +b.getAttribute('data-i'), L = S[list], act = b.getAttribute('data-act');
    if (!L) return;
    if (act === 'add') {
      L.push(blank(list));
      render();
      var inputs = root.querySelectorAll('[data-list="' + list + '"][data-i="' + (L.length - 1) + '"]');
      if (inputs[0]) inputs[0].focus();
      return;
    }
    if (act === 'del') L.splice(i, 1);
    if (act === 'up' && i > 0) L.splice(i - 1, 0, L.splice(i, 1)[0]);
    if (act === 'down' && i < L.length - 1) L.splice(i + 1, 0, L.splice(i, 1)[0]);
    render();
  });

  function onField(e) {
    var t = e.target, list = t.getAttribute('data-list');
    if (!list || !S[list]) return;
    var row = S[list][+t.getAttribute('data-i')];
    if (!row) return;
    if (t.hasAttribute('data-dir')) {
      var d = t.getAttribute('data-dir');
      row.orientace = (row.orientace || []).filter(function (x) { return x !== d; });
      if (t.checked) row.orientace.push(d);
    } else {
      var k = t.getAttribute('data-k');
      if (k === 'plocha') row.plocha = toNum(t.value);
      else if (k === 'cena') row.cena = parseInt(String(t.value).replace(/\D/g, ''), 10) || 0;
      else if (k === 'jednotka') row.jednotka = parseInt(t.value, 10) || 0;
      else if (t.type === 'checkbox') row[k] = t.checked;
      else row[k] = t.value;
    }
    sync();
  }
  root.addEventListener('input', onField);
  root.addEventListener('change', onField);
  root.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.matches('input')) e.preventDefault(); // neodesílat formulář
  });

  // Kontrola se přepočítá i při změně celkové plochy v ACF poli výše.
  document.addEventListener('input', function (e) {
    if (e.target.closest && e.target.closest('.acf-field[data-name="dv_plocha"]')) updateSums();
  });

  render();
})();
