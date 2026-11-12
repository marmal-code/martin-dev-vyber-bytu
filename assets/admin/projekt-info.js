/* Marmal – Developerské projekty: box „Údaje projektu“ (od 0.15.0).
 * Bez závislostí kromě wp.media (výběr fotky a souborů). Data jdou jako JSON do #martin-dv-info-data,
 * PHP je při uložení sanitizuje (sanitize_info). HTML se skládá jen z hodnot prošlých esc(). */
(function () {
  'use strict';

  var CFG = window.martinDvInfoEditor;
  var root = document.getElementById('martin-dv-info-editor');
  var input = document.getElementById('martin-dv-info-data');
  if (!CFG || !root || !input) return;

  var T = CFG.i18n, FILES = CFG.files || {};
  var S;
  try { S = JSON.parse(input.value || '{}') || {}; } catch (e) { S = {}; }
  S.prodejce = S.prodejce || {};
  S.texty = S.texty || {};
  if (!Array.isArray(S.dokumenty)) S.dokumenty = [];

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function sync() { input.value = JSON.stringify(S); }
  function get(path) {
    var p = path.split('.'), o = S;
    for (var i = 0; i < p.length; i++) { if (o == null) return ''; o = o[p[i]]; }
    return o == null ? '' : o;
  }
  function set(path, v) {
    var p = path.split('.'), o = S;
    for (var i = 0; i < p.length - 1; i++) { o = o[p[i]] = o[p[i]] || {}; }
    o[p[p.length - 1]] = v;
  }

  function fld(path, label, opts) {
    opts = opts || {};
    var v = get(path), inp;
    if (opts.area) {
      inp = '<textarea rows="3" data-path="' + path + '" placeholder="' + esc(opts.ph || '') + '">' + esc(v) + '</textarea>';
    } else {
      inp = '<input type="' + (opts.type || 'text') + '" data-path="' + path + '" value="' + esc(v) + '"' + (opts.ph ? ' placeholder="' + esc(opts.ph) + '"' : '') + '>';
    }
    return '<label class="mdi-fld' + (opts.wide ? ' mdi-wide' : '') + '"><span>' + esc(label) + '</span>' + inp +
      (opts.help ? '<small>' + esc(opts.help) + '</small>' : '') + '</label>';
  }

  function photoHTML() {
    var id = +S.prodejce.foto || 0, f = FILES[id];
    return '<div class="mdi-fld"><span>' + esc(T.foto) + '</span><div class="mdi-photo">' +
      (id && f ? '<img src="' + esc(f.thumb) + '" alt="">' : '') +
      '<button type="button" class="button" data-act="photo">' + esc(T.choosePhoto) + '</button>' +
      (id ? '<button type="button" class="button-link mdu-del" data-act="nophoto">' + esc(T.removePhoto) + '</button>' : '') +
      '</div></div>';
  }

  function docsHTML() {
    var n = S.dokumenty.length;
    var rows = S.dokumenty.map(function (d, i) {
      var f = FILES[d.soubor];
      return '<tr><td><input type="text" data-doc="' + i + '" value="' + esc(d.nazev) + '" placeholder="' + esc(T.docNamePh) + '"></td>' +
        '<td class="mdi-file">' + (f ? '<span title="' + esc(f.file) + '">' + esc(f.file) + '</span> ' : '<em>' + esc(T.noFile) + '</em> ') +
        '<button type="button" class="button button-small" data-act="file" data-i="' + i + '">' + esc(f ? T.changeFile : T.chooseFile) + '</button></td>' +
        '<td class="mdu-tools">' +
        '<button type="button" class="button-link" data-act="up" data-i="' + i + '" aria-label="' + esc(T.up) + '"' + (i === 0 ? ' disabled' : '') + '>↑</button>' +
        '<button type="button" class="button-link" data-act="down" data-i="' + i + '" aria-label="' + esc(T.down) + '"' + (i === n - 1 ? ' disabled' : '') + '>↓</button>' +
        '<button type="button" class="button-link mdu-del" data-act="del" data-i="' + i + '" aria-label="' + esc(T.remove) + '">×</button></td></tr>';
    }).join('');
    return (rows ? '<table class="widefat mdu-table"><thead><tr><th>' + esc(T.docName) + '</th><th>' + esc(T.docFile) + '</th><th></th></tr></thead><tbody>' + rows + '</tbody></table>'
      : '<p class="mdu-empty">' + esc(T.empty) + '</p>') +
      '<p><button type="button" class="button" data-act="adddoc">+ ' + esc(T.addDoc) + '</button></p>';
  }

  function render() {
    root.innerHTML =
      '<div class="mdu-sec"><h4>' + esc(T.project) + '</h4><p class="description">' + esc(T.projectHelp) + '</p><div class="mdi-grid">' +
        fld('developer', T.developer) + fld('dodavatel', T.dodavatel) + fld('lokalita', T.lokalita, { ph: T.lokalitaPh }) +
        fld('termin', T.termin, { ph: T.terminPh }) + fld('predprodej_od', T.predOd, { type: 'date' }) + fld('predprodej_do', T.predDo, { type: 'date' }) +
      '</div></div>' +
      '<div class="mdu-sec"><h4>' + esc(T.seller) + '</h4><p class="description">' + esc(T.sellerHelp) + '</p><div class="mdi-grid">' +
        fld('prodejce.jmeno', T.jmeno) + fld('prodejce.firma', T.firma) + fld('prodejce.telefon', T.telefon, { type: 'tel' }) +
        fld('prodejce.email', T.email, { type: 'email' }) + photoHTML() +
      '</div></div>' +
      '<div class="mdu-sec"><h4>' + esc(T.texts) + '</h4><div class="mdi-grid">' +
        fld('texty.cena', T.cena, { ph: T.cenaPh, help: T.cenaHelp, wide: true }) +
        fld('texty.plochy', T.plochy, { ph: T.plochyPh, area: true, wide: true }) +
      '</div></div>' +
      '<div class="mdu-sec"><h4>' + esc(T.docs) + '</h4><p class="description">' + esc(T.docsHelp) + '</p>' + docsHTML() + '</div>';
    sync();
  }

  /* ---------- výběr z knihovny médií ---------- */
  function pick(opts, done) {
    if (!window.wp || !wp.media) return;
    var frame = wp.media({ title: opts.title, button: { text: opts.title }, multiple: false, library: opts.image ? { type: 'image' } : {} });
    frame.on('select', function () {
      var a = frame.state().get('selection').first().toJSON();
      var thumb = (a.sizes && a.sizes.thumbnail && a.sizes.thumbnail.url) || a.icon || a.url;
      FILES[a.id] = { title: a.title || '', file: a.filename || a.title || '', thumb: thumb };
      done(a);
    });
    frame.open();
  }

  root.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-act]');
    if (!b) return;
    e.preventDefault();
    var act = b.getAttribute('data-act'), i = +b.getAttribute('data-i'), D = S.dokumenty;
    if (act === 'photo') { pick({ title: T.choosePhoto, image: true }, function (a) { S.prodejce.foto = a.id; render(); }); return; }
    if (act === 'nophoto') { S.prodejce.foto = 0; render(); return; }
    if (act === 'adddoc') {
      pick({ title: T.chooseFile }, function (a) { D.push({ nazev: a.title || '', soubor: a.id }); render(); });
      return;
    }
    if (act === 'file') { pick({ title: T.chooseFile }, function (a) { D[i].soubor = a.id; if (!D[i].nazev) D[i].nazev = a.title || ''; render(); }); return; }
    if (act === 'del') D.splice(i, 1);
    if (act === 'up' && i > 0) D.splice(i - 1, 0, D.splice(i, 1)[0]);
    if (act === 'down' && i < D.length - 1) D.splice(i + 1, 0, D.splice(i, 1)[0]);
    render();
  });

  function onField(e) {
    var t = e.target;
    if (t.hasAttribute('data-path')) { set(t.getAttribute('data-path'), t.value); sync(); return; }
    if (t.hasAttribute('data-doc')) { var d = S.dokumenty[+t.getAttribute('data-doc')]; if (d) d.nazev = t.value; sync(); }
  }
  root.addEventListener('input', onField);
  root.addEventListener('change', onField);
  root.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.matches('input')) e.preventDefault();
  });

  render();
})();
