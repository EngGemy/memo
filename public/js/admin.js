/* MEMO STORE — admin dashboard client.
 * Server renders one page per section (window.MEMO.panel tells us which),
 * so there is no client-side router or i18n switch here — those are plain
 * page links now. Every panel binds only if its markup is on the page. */
(function () {
  'use strict';

  var MEMO = window.MEMO || {};
  if (!MEMO.routes) return;

  var L = MEMO.locale === 'en' ? 'en' : 'ar';
  var T = MEMO.T || {};
  var PANEL = MEMO.panel || 'videos';
  var K = T.k || {};
  var ST = T.st || {};
  var ACT = T.act || {};
  var MSG = T.msg || {};

  var DATA = { stats: {}, videos: [], leaks: [], activity: [], top: [] };
  var CATEGORIES = [];

  /* ══════ core helpers ══════ */
  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function csrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? (m.content || '') : '';
  }

  function esc(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function num(n) {
    return L === 'ar' ? String(n).replace(/[0-9]/g, function (d) { return '٠١٢٣٤٥٦٧٨٩'[d]; }) : String(n);
  }

  function mmss(s) {
    s = s || 0;
    return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
  }

  function catLabel(v) {
    var c = v && v.category;
    if (c && typeof c === 'object') {
      var n = L === 'ar' ? (c.name_ar || c.name) : (c.name || c.name_ar);
      return n || '—';
    }
    if (typeof c === 'string' && c) return c;
    return '—';
  }

  function catName(c) {
    if (!c) return '';
    return L === 'ar' ? (c.name_ar || c.name || '') : (c.name || c.name_ar || '');
  }

  var toastTimer = null;
  function toast(msg, bad) {
    var el = $('#toast');
    if (!el) return;
    el.textContent = msg;
    el.classList.toggle('bad', !!bad);
    el.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { el.classList.remove('show'); }, 2200);
  }

  async function api(url, opt) {
    opt = opt || {};
    var headers = Object.assign({ Accept: 'application/json', 'X-CSRF-TOKEN': csrf() }, opt.headers || {});
    var init = Object.assign({}, opt, { headers: headers });
    delete init.json;
    if (opt.json !== undefined) {
      headers['Content-Type'] = 'application/json';
      init.body = JSON.stringify(opt.json);
    }
    var r = await fetch(url, init);
    if (!r.ok) {
      var msg = r.status;
      try { var j = await r.json(); msg = j.message || msg; } catch (_e) {}
      throw new Error(msg);
    }
    return r.status === 204 ? null : r.json();
  }

  function setLive(state) {
    var el = $('#liveTag');
    if (!el) return;
    if (state === 'run') {
      el.innerHTML = '<span class="spin"></span>';
      el.className = 'tag run notch';
    } else if (state === 'ok') {
      el.textContent = T.live || '';
      el.className = 'tag ok notch';
    } else {
      el.textContent = MSG.err || '';
      el.className = 'tag bad notch';
    }
  }

  /* ══════ generic drag-to-reorder ══════
   * Works on <tr> rows or plain divs alike. A drag only starts when the
   * gesture began on an element carrying the .drag handle, so the rest of
   * the row stays clickable/selectable. */
  function enableDragReorder(container, rowSelector, onReorder) {
    if (!container) return;
    var dragEl = null;

    function clearOver() {
      $$(rowSelector, container).forEach(function (r) { r.classList.remove('drag-over'); r.style.borderTop = ''; });
    }

    container.addEventListener('dragstart', function (e) {
      var row = e.target.closest ? e.target.closest(rowSelector) : null;
      if (!row || !container.contains(row) || !e.target.closest('.drag')) { e.preventDefault(); return; }
      dragEl = row;
      row.classList.add('dragging');
      row.style.opacity = '.45';
      e.dataTransfer.effectAllowed = 'move';
      try { e.dataTransfer.setData('text/plain', row.dataset.id || ''); } catch (_e) {}
    });

    container.addEventListener('dragend', function () {
      if (dragEl) { dragEl.classList.remove('dragging'); dragEl.style.opacity = ''; }
      clearOver();
      dragEl = null;
    });

    container.addEventListener('dragover', function (e) {
      if (!dragEl) return;
      e.preventDefault();
      var row = e.target.closest ? e.target.closest(rowSelector) : null;
      if (!row || row === dragEl || !container.contains(row)) return;
      clearOver();
      row.classList.add('drag-over');
      row.style.borderTop = '2px solid var(--volt)';
    });

    container.addEventListener('drop', function (e) {
      if (!dragEl) return;
      e.preventDefault();
      var row = e.target.closest ? e.target.closest(rowSelector) : null;
      clearOver();
      if (!row || row === dragEl || !container.contains(row)) return;
      var rows = $$(rowSelector, container);
      var from = rows.indexOf(dragEl), to = rows.indexOf(row);
      if (from < 0 || to < 0) return;
      if (from < to) row.after(dragEl); else row.before(dragEl);
      var order = $$(rowSelector, container).map(function (r) { return r.dataset.id; });
      onReorder(order);
    });
  }

  /* ══════ KPIs ══════ */
  function renderKpis() {
    var el = $('#kpis');
    if (!el) return;
    var s = DATA.stats || {};
    var cards = [
      [K.total, num(s.total || 0), ''],
      [K.public, num(s.public || 0), '', s.public ? '' : 'w'],
      [K.proc, num(s.processing || 0), '', s.processing ? 'w' : ''],
      [K.wm, num(s.watermarked || 0) + ' / ' + num(s.total || 0), ''],
      [K.views, num(s.views_total || 0), ''],
      [K.today, num(s.views_today || 0), ''],
      [K.storage, num(s.storage_gb || 0) + ' ' + (T.gb || 'GB'), ''],
      [K.leaks, num(s.leaks_open || 0), '', s.leaks_open ? 'b' : ''],
    ];
    el.innerHTML = cards.map(function (c) {
      return '<div class="kpi notch"><span>' + esc(c[0] || '') + '</span><b>' + c[1] + '</b><i class="' + (c[3] || '') + '">' + (c[2] || '') + '</i></div>';
    }).join('');
  }

  /* ══════ videos panel ══════ */
  function videoRowHtml(v) {
    var stCls = (v.status === 'published' && v.is_public) ? 'ok'
      : v.status === 'failed' ? 'bad'
      : ['queued', 'transcoding', 'uploading'].indexOf(v.status) !== -1 ? 'run' : 'wait';
    var label = (v.status === 'published' && !v.is_public)
      ? (T.ready_hidden || '')
      : (ST[v.status] || v.status) + (v.status === 'transcoding' ? ' ' + num(v.progress || 0) + '%' : '');
    var title = (L === 'ar' && v.title_ar) ? v.title_ar : v.title;
    var poster = v.poster || '/assets/memo-mark.png';

    var actions = '<button class="btn ghost sm notch" data-act="edit" type="button">' + esc(ACT.edit || '') + '</button>';
    if (v.status === 'published') {
      actions += v.is_public
        ? '<button class="btn ghost sm notch" data-act="unpublish" type="button">' + esc(ACT.unpublish || '') + '</button>'
        : '<button class="btn ok sm notch" data-act="publish" type="button">' + esc(ACT.publish || '') + '</button>';
    }
    if (v.status !== 'uploading') {
      actions += '<button class="btn ghost sm notch" data-act="retry" type="button">' + esc(ACT.retry || '') + '</button>';
    }
    actions += '<button class="btn bad sm notch" data-act="del" type="button">' + esc(ACT.del || '') + '</button>';

    return '<tr draggable="true" data-id="' + v.id + '">'
      + '<td><span class="drag">⠿</span></td>'
      + '<td><div class="vtitle"><img class="vthumb notch" src="' + esc(poster) + '" alt=""><b>' + esc(title || '') + '</b></div></td>'
      + '<td>' + esc(catLabel(v)) + '</td>'
      + '<td><span class="mono">' + mmss(v.duration) + '</span></td>'
      + '<td>' + num(v.views || 0) + '</td>'
      + '<td>' + (v.watermark_burned ? '<span class="tag ok notch">✓</span>' : '<span class="tag wait notch">—</span>') + '</td>'
      + '<td><span class="mono">' + esc(v.verify_code || '—') + '</span></td>'
      + '<td><span class="tag ' + stCls + ' notch">' + esc(label) + '</span></td>'
      + '<td><div class="acts">' + actions + '</div></td>'
      + '</tr>';
  }

  function renderVideos() {
    var table = $('#vTable');
    if (!table) return;
    var rows = DATA.videos || [];
    var cnt = $('#vCount');
    if (cnt) cnt.textContent = num(rows.length) + ' ' + (T.videos_unit || '');
    var emptyEl = $('#vEmpty');
    if (emptyEl) emptyEl.classList.toggle('hide', rows.length > 0);
    table.classList.toggle('hide', rows.length === 0);
    var tbody = $('tbody', table);
    if (tbody) tbody.innerHTML = rows.map(videoRowHtml).join('');
  }

  var vTable = $('#vTable');
  if (vTable) {
    var vBody = $('tbody', vTable) || vTable;

    vBody.addEventListener('click', async function (e) {
      var editBtn = e.target.closest('[data-act="edit"]');
      var tr = e.target.closest('tr');
      if (!tr) return;
      var id = tr.dataset.id;

      if (editBtn) { openEditModal(id); return; }

      var b = e.target.closest('[data-act]');
      if (!b) return;
      var act = b.dataset.act;
      b.disabled = true;
      try {
        if (act === 'del') {
          if (!confirm(T.confirm_del || '')) { b.disabled = false; return; }
          await api('/admin/api/videos/' + id, { method: 'DELETE' });
          toast(MSG.del || '');
        } else {
          await api('/admin/api/videos/' + id + '/' + act, { method: 'POST' });
          toast(act === 'publish' ? (MSG.pub || '') : act === 'unpublish' ? (MSG.unpub || '') : (MSG.queued || ''));
        }
        load();
      } catch (err) {
        toast((MSG.err || '') + ' — ' + err.message, true);
        b.disabled = false;
      }
    });

    enableDragReorder(vBody, 'tr', async function (ids) {
      try {
        await api(MEMO.routes.videosReorder, { method: 'POST', json: { order: ids.map(Number) } });
      } catch (err) {
        toast((MSG.err || '') + ' — ' + err.message, true);
      }
    });
  }

  /* ══════ edit modal ══════ */
  function fillCategorySelect(sel, selectedId) {
    if (!sel) return;
    var opts = ['<option value="">—</option>'].concat(
      (CATEGORIES || []).map(function (c) { return '<option value="' + c.id + '">' + esc(catName(c)) + '</option>'; })
    );
    sel.innerHTML = opts.join('');
    sel.value = selectedId == null ? '' : String(selectedId);
  }

  function openEditModal(id) {
    var modal = $('#editModal');
    if (!modal) return;
    var v = (DATA.videos || []).find(function (x) { return String(x.id) === String(id); });
    if (!v) return;

    if ($('#eId')) $('#eId').value = v.id;
    if ($('#eTitle')) $('#eTitle').value = v.title || '';
    if ($('#eTitleAr')) $('#eTitleAr').value = v.title_ar || '';
    if ($('#eDesc')) $('#eDesc').value = v.description || '';
    if ($('#eDescAr')) $('#eDescAr').value = v.description_ar || '';
    fillCategorySelect($('#eCat'), v.category_id);

    var verifyPath = v.verify_code ? ('/verify/' + v.verify_code) : '#';
    var eVerify = $('#eVerify');
    if (eVerify) { eVerify.textContent = verifyPath; eVerify.href = verifyPath; }

    var eP = $('#ePoster');
    if (eP) eP.src = v.poster || '/assets/memo-mark.png';

    modal.classList.add('on');
  }

  function closeEditModal() {
    var m = $('#editModal');
    if (m) m.classList.remove('on');
  }

  var editModal = $('#editModal');
  if (editModal) {
    if ($('#editClose')) $('#editClose').onclick = closeEditModal;
    if ($('#eCancel')) $('#eCancel').onclick = closeEditModal;
    editModal.addEventListener('click', function (e) { if (e.target === editModal) closeEditModal(); });

    var eSave = $('#eSave');
    if (eSave) {
      eSave.onclick = async function () {
        var id = $('#eId') ? $('#eId').value : '';
        if (!id) return;
        var payload = {
          title: $('#eTitle') ? $('#eTitle').value : undefined,
          title_ar: $('#eTitleAr') ? ($('#eTitleAr').value || null) : null,
          description: $('#eDesc') ? ($('#eDesc').value || null) : null,
          description_ar: $('#eDescAr') ? ($('#eDescAr').value || null) : null,
          category_id: $('#eCat') ? ($('#eCat').value || null) : null,
        };
        try {
          await api('/admin/api/videos/' + id, { method: 'PATCH', json: payload });
          toast(MSG.saved || '');
          closeEditModal();
          load();
        } catch (e2) {
          toast((MSG.err || '') + ' — ' + e2.message, true);
        }
      };
    }

    var ePosterBtn = $('#ePosterBtn'), ePosterFile = $('#ePosterFile'), ePosterReset = $('#ePosterReset');
    if (ePosterBtn && ePosterFile) ePosterBtn.onclick = function () { ePosterFile.click(); };
    if (ePosterFile) {
      ePosterFile.onchange = async function () {
        var id = $('#eId') ? $('#eId').value : '';
        var f = ePosterFile.files[0];
        if (!id || !f) return;
        var fd = new FormData();
        fd.append('poster', f);
        try {
          var res = await api('/admin/api/videos/' + id + '/poster', { method: 'POST', body: fd });
          var eP = $('#ePoster');
          if (eP && res && res.poster) eP.src = res.poster;
          toast(MSG.saved || '');
          load();
        } catch (e3) {
          toast((MSG.err || '') + ' — ' + e3.message, true);
        }
        ePosterFile.value = '';
      };
    }
    if (ePosterReset) {
      ePosterReset.onclick = async function () {
        var id = $('#eId') ? $('#eId').value : '';
        if (!id) return;
        try {
          var v = await api('/admin/api/videos/' + id + '/poster/reset', { method: 'POST' });
          var eP = $('#ePoster');
          if (eP) eP.src = (v && v.poster) || '/assets/memo-mark.png';
          toast(MSG.saved || '');
          load();
        } catch (e4) {
          toast((MSG.err || '') + ' — ' + e4.message, true);
        }
      };
    }
  }

  /* ══════ categories panel + selects ══════ */
  async function loadCategories() {
    try {
      CATEGORIES = await api(MEMO.routes.categories);
    } catch (_e) {
      CATEGORIES = CATEGORIES || [];
    }
    var catSel = $('#cat');
    if (catSel) fillCategorySelect(catSel, catSel.value || null);
    var eCatSel = $('#eCat');
    if (eCatSel) fillCategorySelect(eCatSel, eCatSel.value || null);
    renderCategoriesList();
  }

  function renderCategoriesList() {
    var list = $('#cList');
    if (!list) return;
    var rows = CATEGORIES || [];
    var emptyEl = $('#cEmpty');
    if (emptyEl) emptyEl.classList.toggle('hide', rows.length > 0);
    list.classList.toggle('hide', rows.length === 0);
    var cnt = $('#cCount');
    if (cnt) cnt.textContent = num(rows.length);

    list.innerHTML = rows.map(function (c) {
      return '<div class="cat-row" draggable="true" data-id="' + c.id + '">'
        + '<span class="drag">⠿</span>'
        + '<div class="nm">'
        + '<input type="text" class="notch" data-field="name" value="' + esc(c.name || '') + '" style="margin-bottom:6px">'
        + '<input type="text" class="notch" data-field="name_ar" dir="rtl" value="' + esc(c.name_ar || '') + '">'
        + '</div>'
        + '<span class="mono">' + num(c.videos || 0) + '</span>'
        + '<button class="tag ' + (c.is_active ? 'ok' : 'wait') + ' notch" data-toggle type="button" style="border:0">' + (c.is_active ? '✓' : '—') + '</button>'
        + '<button class="btn bad sm notch" data-del type="button">' + esc(ACT.del || '') + '</button>'
        + '</div>';
    }).join('');
  }

  var cList = $('#cList');
  if (cList) {
    cList.addEventListener('focusout', async function (e) {
      var inp = e.target.closest ? e.target.closest('[data-field]') : null;
      if (!inp) return;
      var row = inp.closest('.cat-row');
      if (!row) return;
      var id = row.dataset.id;
      var field = inp.dataset.field;
      var cat = (CATEGORIES || []).find(function (c) { return String(c.id) === String(id); });
      if (cat && (cat[field] || '') === inp.value) return;
      try {
        var updated = await api('/admin/api/categories/' + id, { method: 'PATCH', json: (function () { var o = {}; o[field] = inp.value; return o; })() });
        if (cat) cat[field] = updated[field];
        toast(MSG.saved || '');
      } catch (err) {
        toast((MSG.err || '') + ' — ' + err.message, true);
      }
    });

    cList.addEventListener('click', async function (e) {
      var row = e.target.closest('.cat-row');
      if (!row) return;
      var id = row.dataset.id;

      if (e.target.closest('[data-toggle]')) {
        var cat = (CATEGORIES || []).find(function (c) { return String(c.id) === String(id); });
        var next = !(cat && cat.is_active);
        try {
          var updated = await api('/admin/api/categories/' + id, { method: 'PATCH', json: { is_active: next } });
          if (cat) cat.is_active = updated.is_active;
          renderCategoriesList();
        } catch (err) {
          toast((MSG.err || '') + ' — ' + err.message, true);
        }
      }

      if (e.target.closest('[data-del]')) {
        if (!confirm(T.confirm_cat_del || '')) return;
        try {
          await api('/admin/api/categories/' + id, { method: 'DELETE' });
          toast(MSG.del || '');
          await loadCategories();
        } catch (err) {
          toast((MSG.err || '') + ' — ' + err.message, true);
        }
      }
    });

    var cAdd = $('#cAdd');
    if (cAdd) {
      cAdd.onclick = async function () {
        var nameEl = $('#cName'), nameArEl = $('#cNameAr');
        var name = nameEl ? nameEl.value.trim() : '';
        if (!name) { toast(MSG.need || '', true); return; }
        try {
          await api('/admin/api/categories', { method: 'POST', json: { name: name, name_ar: (nameArEl && nameArEl.value) || null } });
          if (nameEl) nameEl.value = '';
          if (nameArEl) nameArEl.value = '';
          toast(MSG.saved || '');
          await loadCategories();
        } catch (e5) {
          toast((MSG.err || '') + ' — ' + e5.message, true);
        }
      };
    }

    enableDragReorder(cList, '.cat-row', async function (ids) {
      try {
        await api(MEMO.routes.categoriesReorder, { method: 'POST', json: { order: ids.map(Number) } });
        await loadCategories();
      } catch (err) {
        toast((MSG.err || '') + ' — ' + err.message, true);
      }
    });
  }

  /* ══════ upload panel ══════ */
  var drop = $('#drop');
  if (drop) {
    var CHUNK = 8 * 1024 * 1024;
    var picked = null, aborted = false;
    var browseBtn = $('#browse'), fileInput = $('#file'), goBtn = $('#go'), abortBtn = $('#abort'), hint = $('#hint'), queue = $('#queue');

    if (browseBtn && fileInput) browseBtn.onclick = function () { fileInput.click(); };
    ['dragenter', 'dragover'].forEach(function (ev) {
      drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('hot'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('hot'); });
    });
    drop.addEventListener('drop', function (e) { pick(e.dataTransfer.files[0]); });
    if (fileInput) fileInput.onchange = function () { pick(fileInput.files[0]); };

    function pick(f) {
      if (!f) return;
      picked = f;
      if (goBtn) goBtn.disabled = false;
      if (hint) hint.textContent = f.name + ' · ' + (f.size / 1073741824).toFixed(2) + ' GB · ' + Math.ceil(f.size / CHUNK) + ' chunks';
      var tEn = $('#tEn');
      if (tEn && !tEn.value) tEn.value = f.name.replace(/\.[^.]+$/, '').replace(/[-_]/g, ' ');
    }

    if (abortBtn) {
      abortBtn.onclick = function () {
        aborted = true;
        abortBtn.classList.add('hide');
        if (goBtn) goBtn.disabled = false;
      };
    }

    function pollUpload(id, st, pb) {
      var timer = setInterval(async function () {
        try {
          var v = await api('/admin/api/videos/' + id);
          if (pb) pb.style.insetInlineEnd = (100 - (v.progress || 0)) + '%';
          if (v.status === 'published') {
            st.textContent = (T.ready || '') + (v.watermark_burned ? (' · ' + (T.ready_wm || '')) : '');
            clearInterval(timer);
            load();
          } else if (v.status === 'failed') {
            st.textContent = T.failed || '';
            clearInterval(timer);
            load();
          } else {
            st.textContent = (T.transcoding || '') + ' ' + num(v.progress || 0) + '%';
          }
        } catch (_e) {
          clearInterval(timer);
        }
      }, 3000);
    }

    if (goBtn) {
      goBtn.onclick = async function () {
        if (!picked) return;
        var titleEl = $('#tEn');
        var title = titleEl ? titleEl.value.trim() : '';
        if (!title) { toast(MSG.need || '', true); return; }
        aborted = false;
        goBtn.disabled = true;
        if (abortBtn) abortBtn.classList.remove('hide');

        var card = document.createElement('div');
        card.className = 'job notch';
        card.innerHTML = '<div class="r"><b>' + esc(title) + '</b><span data-s>…</span></div><div class="pbar"><i data-p></i></div>';
        if (queue) queue.prepend(card);
        var st = card.querySelector('[data-s]'), pb = card.querySelector('[data-p]');

        try {
          var catEl = $('#cat'), expEl = $('#exp'), tArEl = $('#tAr'), descEl = $('#desc');
          var open = await api(MEMO.routes.uploads, {
            method: 'POST',
            json: {
              filename: picked.name,
              size_bytes: picked.size,
              title: title,
              title_ar: (tArEl && tArEl.value) || null,
              description: (descEl && descEl.value) || null,
              category_id: (catEl && catEl.value) || null,
              expert_id: (expEl && expEl.value) || null,
            },
          });

          var missing = Array.from({ length: open.total_chunks }, function (_v, i) { return i; });
          var sent = 0;
          for (var pass = 0; pass < 3 && missing.length; pass++) {
            for (var mi = 0; mi < missing.length; mi++) {
              var i = missing[mi];
              if (aborted) {
                st.textContent = abortBtn ? abortBtn.textContent : '';
                return;
              }
              await fetch('/admin/uploads/' + open.uuid + '/' + i + '?video_id=' + open.video_id, {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/octet-stream' },
                body: picked.slice(i * CHUNK, Math.min(picked.size, (i + 1) * CHUNK)),
              });
              sent++;
              var p = Math.round(sent / open.total_chunks * 100);
              if (pb) pb.style.insetInlineEnd = (100 - p) + '%';
              st.textContent = (T.uploading || '') + ' ' + num(p) + '%';
            }
            missing = (await api('/admin/uploads/' + open.uuid)).missing;
          }

          st.textContent = T.assembling || '';
          await api('/admin/uploads/' + open.uuid + '/complete?video_id=' + open.video_id, {
            method: 'POST',
            json: { video_id: open.video_id },
          });
          st.textContent = T.queued_tx || '';
          pollUpload(open.video_id, st, pb);
          load();
        } catch (e6) {
          st.textContent = (T.failed || MSG.err || '') + ' — ' + e6.message;
          card.style.borderColor = 'rgba(255,77,109,.5)';
        } finally {
          goBtn.disabled = false;
          if (abortBtn) abortBtn.classList.add('hide');
        }
      };
    }
  }

  /* ══════ brand + watermark composer ══════
   * Drawn on canvas, exported as a PNG, uploaded, and burned in by ffmpeg —
   * what the admin previews here is byte-identical to what ends up baked
   * into every transcoded frame. */
  var cv = $('#wmCanvas');
  if (cv) {
    var ctx = cv.getContext('2d');
    var logoImg = new Image();
    logoImg.crossOrigin = 'anonymous';
    logoImg.onload = drawWm;
    logoImg.src = MEMO.logo || '/assets/memo-logo.png';

    function drawWm() {
      var W = cv.width, H = cv.height;
      ctx.clearRect(0, 0, W, H);
      ctx.fillStyle = '#0b1020'; ctx.fillRect(0, 0, W, H);
      ctx.fillStyle = 'rgba(91,140,255,.10)'; ctx.fillRect(0, 0, W, H);

      var rSize = $('#rSize'), rOp = $('#rOp'), wmPos = $('#wmPos'), wmPhone = $('#wmPhone');
      var scale = (rSize ? +rSize.value : 18) / 100;
      var op = (rOp ? +rOp.value : 72) / 100;
      var pos = wmPos ? wmPos.value : 'br';
      var markW = W * scale;
      var ratio = logoImg.width ? logoImg.height / logoImg.width : 0.5;
      var markH = markW * ratio;
      var phone = wmPhone ? wmPhone.value.trim() : '';
      var fs = Math.max(9, markW * 0.17);
      var blockH = markH + fs * 1.5, pad = W * 0.035;

      var x = /r$/.test(pos) ? W - markW - pad : pad;
      var y = /^t/.test(pos) ? pad : H - blockH - pad;

      ctx.globalAlpha = op;
      if (logoImg.complete && logoImg.width) ctx.drawImage(logoImg, x, y, markW, markH);
      if (phone) {
        ctx.font = "600 " + fs + "px 'JetBrains Mono',monospace";
        ctx.textAlign = /r$/.test(pos) ? 'right' : 'left';
        ctx.fillStyle = '#fff';
        ctx.shadowColor = 'rgba(0,0,0,.85)'; ctx.shadowBlur = 6;
        ctx.fillText(phone, /r$/.test(pos) ? x + markW : x, y + markH + fs * 1.15);
        ctx.shadowBlur = 0;
      }
      ctx.globalAlpha = 1;

      var oSize = $('#oSize'), oOp = $('#oOp');
      if (oSize && rSize) oSize.textContent = rSize.value + '%';
      if (oOp && rOp) oOp.textContent = rOp.value + '%';
    }

    function exportWm() {
      return new Promise(function (res) {
        var rSize = $('#rSize'), rOp = $('#rOp'), wmPhone = $('#wmPhone');
        var scale = (rSize ? +rSize.value : 18) / 100;
        var op = (rOp ? +rOp.value : 72) / 100;
        var W = 800, markW = W * scale / 0.4;
        var ratio = logoImg.width ? logoImg.height / logoImg.width : 0.5;
        var fs = Math.max(12, markW * 0.17), markH = markW * ratio;
        var phone = wmPhone ? wmPhone.value.trim() : '';

        var c = document.createElement('canvas');
        c.width = Math.ceil(markW);
        c.height = Math.ceil(markH + (phone ? fs * 1.5 : 0));
        var g = c.getContext('2d');
        g.globalAlpha = op;
        if (logoImg.complete && logoImg.width) g.drawImage(logoImg, 0, 0, markW, markH);
        if (phone) {
          g.font = '600 ' + fs + 'px monospace';
          g.textAlign = 'right';
          g.fillStyle = '#fff';
          g.shadowColor = 'rgba(0,0,0,.9)'; g.shadowBlur = 8;
          g.fillText(phone, markW, markH + fs * 1.15);
        }
        c.toBlob(res, 'image/png');
      });
    }

    ['rSize', 'rOp', 'wmPos', 'wmPhone'].forEach(function (id) {
      var el = $('#' + id);
      if (!el) return;
      el.addEventListener('input', drawWm);
      el.addEventListener('change', drawWm);
    });

    var SZ = [['rNav', 'oNav', '--logo-nav'], ['rHero', 'oHero', '--logo-hero'], ['rFoot', 'oFoot', '--logo-foot']];
    function syncSizes() {
      SZ.forEach(function (pair) {
        var rEl = $('#' + pair[0]), oEl = $('#' + pair[1]);
        if (!rEl) return;
        var px = rEl.value;
        document.documentElement.style.setProperty(pair[2], px + 'px');
        if (oEl) oEl.textContent = px + ' ' + (T.px || 'px');
      });
    }
    SZ.forEach(function (pair) {
      var el = $('#' + pair[0]);
      if (el) el.addEventListener('input', syncSizes);
    });

    var newLogo = null;
    var bLogo = $('#bLogo'), logoFile = $('#logoFile');
    if (bLogo && logoFile) bLogo.onclick = function () { logoFile.click(); };
    if (logoFile) {
      logoFile.onchange = function () {
        var f = logoFile.files[0];
        if (!f) return;
        newLogo = f;
        var u = URL.createObjectURL(f);
        var side = $('#logoSide');
        if (side) side.src = u;
        logoImg.src = u;
      };
    }

    var bSave = $('#bSave');
    if (bSave) {
      bSave.onclick = async function () {
        var fd = new FormData();
        fd.append('watermark', await exportWm(), 'watermark.png');
        fd.append('watermark_phone', $('#wmPhone') ? $('#wmPhone').value.trim() : '');
        fd.append('logo_nav', $('#rNav') ? $('#rNav').value : 32);
        fd.append('logo_hero', $('#rHero') ? $('#rHero').value : 76);
        fd.append('logo_foot', $('#rFoot') ? $('#rFoot').value : 40);
        if (newLogo) fd.append('logo', newLogo);
        try {
          await api(MEMO.routes.brandSave, { method: 'POST', body: fd });
          toast(MSG.saved || '');
          newLogo = null;
        } catch (e7) {
          toast((MSG.err || '') + ' — ' + e7.message, true);
        }
      };
    }

    syncSizes();
    drawWm();

    (async function () {
      try {
        var b = await api(MEMO.routes.brand);
        if (!b) return;
        if (b.watermark_phone && $('#wmPhone')) $('#wmPhone').value = b.watermark_phone;
        if (b.logo_nav && $('#rNav')) $('#rNav').value = b.logo_nav;
        if (b.logo_hero && $('#rHero')) $('#rHero').value = b.logo_hero;
        if (b.logo_foot && $('#rFoot')) $('#rFoot').value = b.logo_foot;
        syncSizes();
        drawWm();
      } catch (_e) {}
    })();
  }

  /* ══════ leaks panel ══════ */
  function fillLeakVideoSelect() {
    var sel = $('#lkVid');
    if (!sel) return;
    var prev = sel.value;
    sel.innerHTML = '<option value="">—</option>' + (DATA.videos || []).map(function (v) {
      var t = (L === 'ar' && v.title_ar) ? v.title_ar : v.title;
      return '<option value="' + v.id + '">' + esc(t || '') + '</option>';
    }).join('');
    sel.value = prev || '';
  }

  function renderLeaks() {
    var table = $('#lkTable');
    var rows = DATA.leaks || [];
    var cnt = $('#lkCount');
    if (cnt) cnt.textContent = num(rows.length);
    var emptyEl = $('#lkEmpty');
    if (emptyEl) emptyEl.classList.toggle('hide', rows.length > 0);

    if (table) {
      table.classList.toggle('hide', rows.length === 0);
      var S = { open: 'bad', reported: 'warn', removed: 'ok', ignored: 'wait' };
      var tbody = $('tbody', table);
      if (tbody) {
        tbody.innerHTML = rows.map(function (r) {
          return '<tr data-id="' + r.id + '">'
            + '<td><a href="' + esc(r.url) + '" target="_blank" rel="noopener" class="mono">' + esc((r.url || '').slice(0, 52)) + '</a></td>'
            + '<td>' + esc(r.platform || '—') + '</td>'
            + '<td>' + esc(r.video ? r.video.title : '—') + '</td>'
            + '<td><span class="tag ' + (S[r.status] || 'wait') + ' notch">' + esc(r.status || '') + '</span></td>'
            + '<td><div class="acts">'
            + '<button class="btn ghost sm notch" data-evi type="button">' + esc(ACT.evi || '') + '</button>'
            + '<button class="btn ok sm notch" data-done type="button">✓</button>'
            + '</div></td></tr>';
        }).join('');
      }
    }
    fillLeakVideoSelect();
  }

  var lkTable = $('#lkTable');
  if (lkTable) {
    lkTable.addEventListener('click', async function (e) {
      var row = e.target.closest('tr');
      if (!row) return;
      var id = row.dataset.id;
      if (e.target.closest('[data-evi]')) {
        try {
          var ev = await api('/admin/api/leaks/' + id + '/evidence');
          await navigator.clipboard.writeText(ev.statement + '\n\n' + JSON.stringify(ev, null, 2));
          toast(MSG.copied || '');
        } catch (_e) {
          toast(MSG.err || '', true);
        }
      }
      if (e.target.closest('[data-done]')) {
        try {
          await api('/admin/api/leaks/' + id, { method: 'PATCH', json: { status: 'removed' } });
          load();
        } catch (_e) {
          toast(MSG.err || '', true);
        }
      }
    });
  }

  var lkAdd = $('#lkAdd');
  if (lkAdd) {
    lkAdd.onclick = async function () {
      var urlEl = $('#lkUrl');
      var url = urlEl ? urlEl.value.trim() : '';
      if (!url) return;
      try {
        var platEl = $('#lkPlat'), whoEl = $('#lkWho'), vidEl = $('#lkVid');
        await api(MEMO.routes.leaks, {
          method: 'POST',
          json: {
            url: url,
            platform: (platEl && platEl.value) || null,
            impersonator: (whoEl && whoEl.value) || null,
            video_id: (vidEl && vidEl.value) || null,
          },
        });
        if (urlEl) urlEl.value = '';
        if (platEl) platEl.value = '';
        if (whoEl) whoEl.value = '';
        toast(MSG.lk || '');
        load();
      } catch (e8) {
        toast((MSG.err || '') + ' — ' + e8.message, true);
      }
    };
  }

  /* ══════ activity panel ══════ */
  function renderActivity() {
    var topEl = $('#topList');
    if (topEl) {
      var top = DATA.top || [];
      topEl.innerHTML = top.length ? top.map(function (r) {
        return '<div style="display:flex;gap:10px;padding:9px 0;border-bottom:1px solid rgba(91,140,255,.07)">'
          + '<span>' + esc(r.title) + '</span><span class="mono" style="margin-inline-start:auto">' + num(r.hits) + '</span></div>';
      }).join('') : '<div class="empty">—</div>';
    }
    var actEl = $('#actList');
    if (actEl) {
      var rows = DATA.activity || [];
      actEl.innerHTML = rows.length ? rows.map(function (a) {
        return '<div style="display:flex;gap:10px;padding:9px 0;border-bottom:1px solid rgba(91,140,255,.07);font-size:12.5px">'
          + '<span class="tag ' + (a.type === 'flagged' ? 'bad' : 'run') + ' notch">' + esc(a.type || '') + '</span>'
          + '<span class="mono">' + esc(a.ip || '') + '</span>'
          + '<span class="mono" style="margin-inline-start:auto;color:#4A5570">' + esc((a.created_at || '').slice(5, 16)) + '</span>'
          + '</div>';
      }).join('') : '<div class="empty">—</div>';
    }
  }

  /* ══════ nav / chrome ══════ */
  var mbtn = $('#mbtn');
  if (mbtn) {
    mbtn.onclick = function () {
      var side = $('#side');
      if (side) side.classList.toggle('open');
    };
  }
  var refreshBtn = $('#refresh');
  if (refreshBtn) refreshBtn.onclick = function () { load(); };

  /* ══════ boot ══════ */
  async function load() {
    setLive('run');
    try {
      var ov = await api(MEMO.routes.overview);
      await loadCategories();
      DATA = ov || DATA;
      renderKpis();
      renderVideos();
      renderLeaks();
      renderActivity();
      setLive('ok');
    } catch (e) {
      setLive('bad');
      toast((MSG.err || '') + ' — ' + e.message, true);
    }
  }

  load();
  setInterval(function () { if (!document.hidden) load(); }, 20000);
})();
