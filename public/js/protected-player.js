/**
 * MEMO STORE — protected player
 *
 * Client-side deterrents only slow a casual grab. The real enforcement is in
 * StreamController: signed URLs bound to session + IP + user agent, a key
 * endpoint that counts every handout, and grading that never leaves the server.
 * Everything here exists to make the easy paths (right-click, devtools copy,
 * URL sharing) unrewarding, and to stamp the frame so a screen recording
 * traces back to one account.
 */
class ProtectedPlayer {
  constructor(mount, videoId, csrf) {
    this.mount = typeof mount === 'string' ? document.querySelector(mount) : mount;
    this.videoId = videoId;
    this.csrf = csrf;
    this.hls = null;
    this.lastBeat = 0;
    this.eligible = false;
  }

  async start() {
    const res = await fetch(`/watch/${this.videoId}/open`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
    });

    if (!res.ok) {
      this.fail(res.status === 403
        ? 'Finish the previous chapter to open this one.'
        : 'Playback is unavailable right now. Try again in a moment.');
      return;
    }

    this.grant = await res.json();
    this.render();
    this.attachStream();
    this.attachWatermark();
    this.attachHeartbeat();
    this.attachDeterrents();
  }

  render() {
    this.mount.innerHTML = `
      <div class="mp-frame">
        <video id="mp-video"
               playsinline
               controls
               controlsList="nodownload noplaybackrate noremoteplayback"
               disablePictureInPicture
               disableRemotePlayback></video>
        <div class="mp-wm" id="mp-wm"></div>
        <div class="mp-seal">AES-128 · session-bound</div>
      </div>
      <div class="mp-check" id="mp-check" hidden></div>`;
    this.video = this.mount.querySelector('#mp-video');
  }

  attachStream() {
    const src = this.grant.manifest;

    // Safari plays encrypted HLS natively; everyone else needs hls.js.
    if (this.video.canPlayType('application/vnd.apple.mpegurl')) {
      this.video.src = src;
      return;
    }

    this.hls = new Hls({
      // Keep almost nothing buffered — a full download never exists in memory.
      maxBufferLength: 30,
      maxMaxBufferLength: 60,
      backBufferLength: 10,
      // Credentials ride along so the signed URL + session cookie match.
      xhrSetup: (xhr) => { xhr.withCredentials = true; },
    });

    this.hls.loadSource(src);
    this.hls.attachMedia(this.video);

    this.hls.on(Hls.Events.ERROR, (_, data) => {
      if (!data.fatal) return;
      // Signed URLs expire mid-session by design — re-open rather than die.
      if (data.response && data.response.code === 403) {
        this.hls.destroy();
        this.start();
        return;
      }
      this.fail('The stream dropped. Reload to continue where you left off.');
    });
  }

  /** Drifting forensic stamp — random path, so it can't be cropped out reliably. */
  attachWatermark() {
    const wm = this.mount.querySelector('#mp-wm');
    wm.textContent = this.grant.watermark;

    const move = () => {
      wm.style.left = (5 + Math.random() * 62) + '%';
      wm.style.top = (8 + Math.random() * 76) + '%';
      wm.style.opacity = (0.16 + Math.random() * 0.14).toFixed(2);
    };
    move();
    this.wmTimer = setInterval(move, 7000);
  }

  /** The server decides whether the chapter was watched. This just reports. */
  attachHeartbeat() {
    this.video.addEventListener('timeupdate', () => {
      const s = Math.floor(this.video.currentTime);
      if (s - this.lastBeat < 10) return;
      this.lastBeat = s;

      fetch(`/watch/${this.videoId}/heartbeat`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.csrf,
        },
        body: JSON.stringify({ second: s }),
      })
        .then(r => r.json())
        .then(d => { this.eligible = d.eligible; });
    });

    this.video.addEventListener('ended', () => this.openCheckpoint());
  }

  openCheckpoint() {
    const box = this.mount.querySelector('#mp-check');
    box.hidden = false;
    box.innerHTML = `
      <h3>Checkpoint — ${this.grant.questions.length} questions</h3>
      ${this.grant.questions.map((q, i) => `
        <fieldset data-q="${q.id}">
          <legend>${i + 1}. ${q.body}</legend>
          ${q.options.map(o => `
            <label><input type="radio" name="q${q.id}" value="${o.id}"> ${o.body}</label>
          `).join('')}
        </fieldset>`).join('')}
      <button id="mp-submit">Submit answers</button>
      <p id="mp-result"></p>`;

    box.querySelector('#mp-submit').addEventListener('click', () => this.submit(box));
  }

  async submit(box) {
    const answers = {};
    box.querySelectorAll('fieldset').forEach(fs => {
      const picked = fs.querySelector('input:checked');
      if (picked) answers[fs.dataset.q] = Number(picked.value);
    });

    if (Object.keys(answers).length !== this.grant.questions.length) {
      box.querySelector('#mp-result').textContent = 'Answer every question before submitting.';
      return;
    }

    const res = await fetch(`/watch/${this.videoId}/attempt`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
      body: JSON.stringify({ answers }),
    });

    const d = await res.json();
    const out = box.querySelector('#mp-result');

    if (d.passed) {
      out.textContent = `Passed with ${d.score}% — chapter ${d.next_chapter} is open.`;
      return;
    }

    if (d.rewatch) {
      out.textContent = 'No attempts left. Rewatch the chapter to reset.';
      return;
    }

    // Send them to the timestamps the missed answers came from, not to the answers.
    const marks = Object.values(d.review_timestamps || {});
    out.textContent = `${d.score}% — ${d.attempts_left} attempts left.`
      + (marks.length ? ` Rewatch from ${marks.map(this.fmt).join(', ')}.` : '');
  }

  attachDeterrents() {
    this.mount.addEventListener('contextmenu', e => e.preventDefault());
    this.mount.addEventListener('dragstart', e => e.preventDefault());

    // Pause when the tab is hidden — cuts background segment pulling.
    document.addEventListener('visibilitychange', () => {
      if (document.hidden && !this.video.paused) this.video.pause();
    });
  }

  fmt(s) {
    return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
  }

  fail(msg) {
    this.mount.innerHTML = `<div class="mp-error"><b>Playback stopped</b><p>${msg}</p></div>`;
  }

  destroy() {
    clearInterval(this.wmTimer);
    this.hls?.destroy();
  }
}

window.ProtectedPlayer = ProtectedPlayer;
