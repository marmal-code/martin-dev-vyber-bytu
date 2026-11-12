/* Marmal – Developerské projekty: fotky, vizualizace, video a dokumenty jednotky (od 0.16.0).
 * Výběr přes wp.media; data jako JSON do #martin-dv-media-data, PHP je při uložení ověří (save_unit_media).
 * HTML se skládá jen z hodnot prošlých esc(). */
(function () {
  'use strict';

  var CFG = window.martinDvMediaEditor;
  var root = document.getElementById('martin-dv-media-editor');
  var input = document.getElementById('martin-dv-media-data');
  if (!CFG || !root || !input) return;

  var T = CFG.i18n, FILES = CFG.files || {};
  var S;
  try { S = JSON.parse(input.value || '{}') || {}; } catch (e) { S = {}; }
  ['galerie', 'vizualizace', 'dokumenty'].forEach(function (k) { if (!Array.isArray(S[k])) S[k] = []; });
  S.video = S.video || '';

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function sync() { input.value = JSON.stringify(S); }

  function imagesHTML(list, title, help) {
    var L = S[list], n = L.length;
    var items = L.map(function (id, i) {
      var f = FILES[id] || {};
      return '<li class="mdm-img"><img src="' + esc(f.thumb) + '" alt="" title="' + esc(f.file) + '">' +
        '<span class="mdm-tools">' +
        '<button type="button" class="button-link" data-act="up" data-list="' + list + '" data-i="' + i + '" aria-label="' + esc(T.left) + '"' + (i === 0 ? ' disabled' : '') + '>←</button>' +
        '<button type="button" class="button-link" data-act="down" data-list="' + list + '" data-i="' + i + '" aria-label="' + esc(T.right) + '"' + (i === n - 1 ? ' disabled' : '') + '>→</button>' +
        '<button type="button" class="button-link mdu-del" data-act="del" data-list="' + list + '" data-i="' + i + '" aria-label="' + esc(T.remove) + '">×</button></span></li>';
    }).join('');
    return '<div class="mdu-sec"><h4>' + esc(title) + ' <span class="mdm-count">(' + n + ')</span></h4>' + (help ? '<p class="description">' + esc(help) + '</p>' : '') +
      (items ? '<ul class="mdm-grid">' + items + '</ul>' : '<p class="mdu-empty">' + esc(T.empty) + '</p>') +
      '<p><button type="button" class="button" data-act="addimg" data-list="' + list + '">+ ' + esc(T.addImages) + '</button></p></div>';
  }

  function docsHTML() {
    var D = S.dokumenty, n = D.length;
    var rows = D.map(function (d, i) {
      var f = FILES[d.soubor] || {};
      return '<tr><td><input type="text" data-doc="' + i + '" value="' + esc(d.nazev) + '" placeholder="' + esc(T.docNamePh) + '">' +
        '<input type="text" class="mdi-desc" data-doc-desc="' + i + '" value="' + esc(d.popis || '') + '" placeholder="' + esc(T.docDescPh) + '"></td>' +
        '<td class="mdi-file"><span>' + esc(f.file || '') + '</span> <button type="button" class="button button-small" data-act="file" data-list="dokumenty" data-i="' + i + '">' + esc(T.changeFile) + '</button></td>' +
        '<td class="mdu-tools">' +
        '<button type="button" class="button-link" data-act="up" data-list="dokumenty" data-i="' + i + '" aria-label="' + esc(T.up) + '"' + (i === 0 ? ' disabled' : '') + '>↑</button>' +
        '<button type="button" class="button-link" data-act="down" data-list="dokumenty" data-i="' + i + '" aria-label="' + esc(T.down) + '"' + (i === n - 1 ? ' disabled' : '') + '>↓</button>' +
        '<button type="button" class="button-link mdu-del" data-act="del" data-list="dokumenty" data-i="' + i + '" aria-label="' + esc(T.remove) + '">×</button></td></tr>';
    }).join('');
    return '<div class="mdu-sec"><h4>' + esc(T.docs) + '</h4><p class="description">' + esc(T.docsHelp) + '</p>' +
      (rows ? '<table class="widefat mdu-table"><tbody>' + rows + '</tbody></table>' : '<p class="mdu-empty">' + esc(T.empty) + '</p>') +
      '<p><button type="button" class="button" data-act="adddoc">+ ' + esc(T.addDoc) + '</button></p></div>';
  }

  function render() {
    root.innerHTML = '<p class="mdm-note">' + esc(T.mainPhoto) + '</p>' +
      imagesHTML('galerie', T.gallery, T.galleryHelp) +
      imagesHTML('vizualizace', T.viz, '') +
      '<div class="mdu-sec"><h4>' + esc(T.video) + '</h4><input type="url" class="large-text" data-video value="' + esc(S.video) + '" placeholder="' + esc(T.videoPh) + '"><p class="description">' + esc(T.videoHelp) + '</p></div>' +
      docsHTML();
    sync();
  }

  function remember(a) {
    var thumb = (a.sizes && a.sizes.thumbnail && a.sizes.thumbnail.url) || a.icon || a.url;
    FILES[a.id] = { file: a.filename || a.title || '', thumb: thumb };
  }
  function pick(opts, done) {
    if (!window.wp || !wp.media) return;
    var frame = wp.media({ title: opts.title, button: { text: opts.title }, multiple: !!opts.multiple, library: opts.image ? { type: 'image' } : {} });
    frame.on('select', function () {
      var list = frame.state().get('selection').toJSON();
      list.forEach(remember);
      done(list);
    });
    frame.open();
  }

  root.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-act]');
    if (!b) return;
    e.preventDefault();
    var act = b.getAttribute('data-act'), list = b.getAttribute('data-list'), i = +b.getAttribute('data-i'), L = S[list];
    if (act === 'addimg') {
      pick({ title: T.addImages, image: true, multiple: true }, function (sel) {
        sel.forEach(function (a) { if (L.indexOf(a.id) === -1) L.push(a.id); });
        render();
      });
      return;
    }
    if (act === 'adddoc') {
      pick({ title: T.chooseFile }, function (sel) { if (sel[0]) { S.dokumenty.push({ nazev: sel[0].title || '', popis: '', soubor: sel[0].id }); render(); } });
      return;
    }
    if (act === 'file') {
      pick({ title: T.chooseFile }, function (sel) { if (sel[0]) { L[i].soubor = sel[0].id; render(); } });
      return;
    }
    if (!L) return;
    if (act === 'del') L.splice(i, 1);
    if (act === 'up' && i > 0) L.splice(i - 1, 0, L.splice(i, 1)[0]);
    if (act === 'down' && i < L.length - 1) L.splice(i + 1, 0, L.splice(i, 1)[0]);
    render();
  });

  function onField(e) {
    var t = e.target;
    if (t.hasAttribute('data-video')) { S.video = t.value.trim(); sync(); return; }
    if (t.hasAttribute('data-doc')) { var d = S.dokumenty[+t.getAttribute('data-doc')]; if (d) d.nazev = t.value; sync(); return; }
    if (t.hasAttribute('data-doc-desc')) { var dd = S.dokumenty[+t.getAttribute('data-doc-desc')]; if (dd) dd.popis = t.value; sync(); }
  }
  root.addEventListener('input', onField);
  root.addEventListener('change', onField);
  root.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.matches('input')) e.preventDefault();
  });

  render();
})();
