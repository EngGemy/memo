(() => {
  const cfg = window.MEMO_LANDING || {};
  const L = cfg.locale || 'ar';
  const S = cfg.strings || {};
  const $ = (s) => document.querySelector(s);
  const ar = (n) => L === 'ar'
    ? String(n).replace(/[0-9]/g, (d) => '٠١٢٣٤٥٦٧٨٩'[d])
    : String(n);
  const mm = (s) => String(Math.floor((s || 0) / 60)).padStart(2, '0') + ':' + String((s || 0) % 60).padStart(2, '0');
  const catName = (c) => {
    if (!c) return '';
    return (L === 'ar' && c.name_ar) ? c.name_ar : (c.name || '');
  };

  let VIDS = [], CATS = [], flt = 'all', hls = null, dt = null;

  Promise.all([
    fetch('/api/videos').then((r) => r.json()),
    fetch('/api/categories').then((r) => r.json()),
  ]).then(([videos, cats]) => {
    VIDS = videos;
    CATS = cats;
    draw();
  }).catch(() => {
    $('#empty').style.display = 'block';
    $('#empty').textContent = S.load_error || 'Error';
  });

  function draw() {
    const total = VIDS.length;
    let html = `<button class="tab ${flt === 'all' ? 'on' : ''}" data-f="all">${S.all || 'All'}<b>${ar(total)}</b></button>`;
    CATS.forEach((c) => {
      const n = VIDS.filter((v) => v.category && v.category.id === c.id).length;
      if (!n && !c.count) return;
      const count = n || c.count || 0;
      html += `<button class="tab ${flt == c.id ? 'on' : ''}" data-f="${c.id}">${catName(c)}<b>${ar(count)}</b></button>`;
    });
    $('#tabs').innerHTML = html;

    const list = VIDS.filter((v) => flt === 'all' || (v.category && String(v.category.id) === String(flt)));
    $('#empty').style.display = list.length ? 'none' : 'block';
    $('#empty').textContent = S.empty || '';

    $('#grid').innerHTML = list.map((v) => {
      const title = (L === 'ar' && v.title_ar) ? v.title_ar : v.title;
      const desc = (L === 'ar' && v.description_ar) ? v.description_ar : (v.description || '');
      return `<article class="card nc" data-slug="${v.slug}">
        <div class="th">${v.poster ? `<img src="${v.poster}" alt="" loading="lazy">` : ''}
          <span class="pl"><i></i></span><span class="d">${mm(v.duration)}</span></div>
        <div class="bd"><span class="ct">${catName(v.category)}</span>
          <h3>${title}</h3><p>${desc}</p>
          <div class="ft"><span>${ar(v.views || 0)} ${S.views || ''}</span>
            <a class="vc" href="/verify/${v.verify_code}">${v.verify_code}</a></div></div></article>`;
    }).join('');
  }

  $('#tabs').addEventListener('click', (e) => {
    const t = e.target.closest('.tab');
    if (t) { flt = t.dataset.f; draw(); }
  });

  $('#grid').addEventListener('click', (e) => {
    if (e.target.closest('.vc')) return;
    const c = e.target.closest('.card');
    if (c) play(c.dataset.slug);
  });

  async function play(slug) {
    try {
      const r = await fetch('/watch/' + slug + '/open', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
        },
      });
      if (!r.ok) throw 0;
      const d = await r.json();
      $('#mt').textContent = (L === 'ar' && d.video.title_ar) ? d.video.title_ar : d.video.title;
      $('#mv').textContent = d.video.verify_code;
      $('#trace').textContent = d.trace;
      $('#modal').classList.add('on');
      const v = $('#vid');
      if (hls) { hls.destroy(); hls = null; }
      if (v.canPlayType('application/vnd.apple.mpegurl')) {
        v.src = d.manifest;
      } else if (window.Hls && Hls.isSupported()) {
        hls = new Hls({ maxBufferLength: 30, backBufferLength: 10, xhrSetup: (x) => { x.withCredentials = true; } });
        hls.loadSource(d.manifest);
        hls.attachMedia(v);
      }
      v.play().catch(() => {});
      clearInterval(dt);
      const t = $('#trace');
      const mv = () => {
        t.style.left = (6 + Math.random() * 60) + '%';
        t.style.top = (8 + Math.random() * 74) + '%';
      };
      mv();
      dt = setInterval(mv, 7000);
    } catch {
      alert(S.play_error || 'Error');
    }
  }

  function shut() {
    $('#modal').classList.remove('on');
    const v = $('#vid');
    v.pause();
    v.removeAttribute('src');
    v.load();
    if (hls) { hls.destroy(); hls = null; }
    clearInterval(dt);
  }

  $('#mx').onclick = shut;
  $('#modal').addEventListener('click', (e) => { if (e.target.id === 'modal') shut(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') shut(); });
  document.addEventListener('contextmenu', (e) => { if (e.target.closest('.mbox')) e.preventDefault(); });

  const openSlug = new URLSearchParams(location.search).get('v');
  if (openSlug) {
    const wait = setInterval(() => {
      if (VIDS.length) { clearInterval(wait); play(openSlug); }
    }, 200);
  }
})();
