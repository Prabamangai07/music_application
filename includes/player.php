<!-- MUSIC PLAYER BAR — hidden by default, shown only when a song plays -->
<div class="player-bar" id="playerBar">
  <div class="player-info">
    <img id="playerThumb" class="player-thumb" src="" alt="" style="display:none;">
    <div class="player-text">
      <div class="player-title"  id="playerTitle">–</div>
      <div class="player-artist" id="playerArtist">–</div>
    </div>
  </div>
  <div class="player-controls">
    <div class="controls-btns">
      <button onclick="seekBy(-10)" title="Rewind 10s">⏪</button>
      <button class="play-btn" id="playPauseBtn" onclick="togglePlay()">▶</button>
      <button onclick="seekBy(10)"  title="Forward 10s">⏩</button>
    </div>
    <div class="progress-wrap">
      <span id="currentTime">0:00</span>
      <input type="range" id="progressBar" value="0" min="0" step="0.1" oninput="seekTo(this.value)">
      <span id="duration">0:00</span>
    </div>
  </div>
  <div class="player-right">
    <div class="volume-wrap">
      🔊 <input type="range" id="volumeBar" min="0" max="1" step="0.05" value="1" oninput="setVolume(this.value)">
    </div>
  </div>
</div>
<audio id="audioPlayer"></audio>

<script>
(function () {
  var audio       = document.getElementById('audioPlayer');
  var bar         = document.getElementById('playerBar');
  var ppBtn       = document.getElementById('playPauseBtn');
  var progBar     = document.getElementById('progressBar');
  var saveTimer   = null;
  var barVisible  = false;

  /* ======================================================
     On page load — restore from sessionStorage if exists
  ====================================================== */
  window.addEventListener('DOMContentLoaded', function () {
    var raw = sessionStorage.getItem('mw_song');
    if (!raw) return;
    var d;
    try { d = JSON.parse(raw); } catch (e) { sessionStorage.removeItem('mw_song'); return; }
    if (!d || !d.file || !d.name) return;

    _showBar();
    _applyUI(d.name, d.artist || '–', d.image || '');
    ppBtn.textContent = '▶';

    audio.src = 'uploads/songs/' + d.file;
    audio.addEventListener('loadedmetadata', function onMeta() {
      audio.removeEventListener('loadedmetadata', onMeta);
      audio.currentTime = d.time || 0;
      if (d.playing) audio.play().catch(function () { ppBtn.textContent = '▶'; });
    });

    _setNavBadge(d.name);
  });

  /* ======================================================
     Public API — called by every ▶ Play button
  ====================================================== */
  window.playSong = function (file, name, artist, image) {
    audio.src = 'uploads/songs/' + file;
    audio.play().catch(function () {});
    _showBar();
    _applyUI(name, artist, image);
    _setNavBadge(name);
    _scheduleSave(true);
  };

  window.togglePlay = function () {
    if (!audio.src) return;
    if (audio.paused) { audio.play().catch(function () {}); }
    else              { audio.pause(); }
  };

  window.seekBy    = function (s) { if (audio.src) audio.currentTime = Math.max(0, audio.currentTime + s); };
  window.seekTo    = function (v) { if (audio.src) audio.currentTime = parseFloat(v); };
  window.setVolume = function (v) { audio.volume = parseFloat(v); };

  /* ======================================================
     Audio events
  ====================================================== */
  audio.addEventListener('play',  function () { ppBtn.textContent = '⏸'; });
  audio.addEventListener('pause', function () { ppBtn.textContent = '▶'; });
  audio.addEventListener('ended', function () { ppBtn.textContent = '▶'; _scheduleSave(false); });

  audio.addEventListener('timeupdate', function () {
    document.getElementById('currentTime').textContent = _fmt(audio.currentTime);
    progBar.value = audio.currentTime;
    _scheduleSave(!audio.paused);
  });

  audio.addEventListener('loadedmetadata', function () {
    progBar.max = isFinite(audio.duration) ? audio.duration : 0;
    document.getElementById('duration').textContent = _fmt(audio.duration);
  });

  /* ======================================================
     Helpers
  ====================================================== */
  function _showBar() {
    if (barVisible) return;
    barVisible = true;
    bar.style.display = 'flex';
    /* Push page content up so nothing hides behind player */
    document.body.style.paddingBottom = 'var(--player-h)';
  }

  function _applyUI(name, artist, image) {
    document.getElementById('playerTitle').textContent  = name   || '–';
    document.getElementById('playerArtist').textContent = artist || '–';
    var thumb = document.getElementById('playerThumb');
    if (image && image !== 'undefined' && image !== '') {
      thumb.src = 'uploads/images/' + image;
      thumb.style.display = 'block';
    } else {
      thumb.src = '';
      thumb.style.display = 'none';
    }
    ppBtn.textContent = '⏸';
  }

  function _setNavBadge(name) {
    var np = document.getElementById('navNowPlaying');
    if (!np) return;
    if (name && name !== '–') {
      np.textContent = '🎵 ' + name;
      np.style.display = 'inline-flex';
    } else {
      np.style.display = 'none';
    }
  }

  function _scheduleSave(playing) {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () { _doSave(playing); }, 800);
  }

  function _doSave(playing) {
    if (!audio.src) return;
    var file  = audio.src.split('/').pop();
    var title = document.getElementById('playerTitle').textContent;
    if (!file || !title || title === '–') return;
    var thumb = document.getElementById('playerThumb');
    var img   = (thumb.style.display !== 'none' && thumb.src) ? thumb.src.split('/').pop() : '';
    sessionStorage.setItem('mw_song', JSON.stringify({
      file:    file,
      name:    title,
      artist:  document.getElementById('playerArtist').textContent,
      image:   img,
      time:    isFinite(audio.currentTime) ? audio.currentTime : 0,
      playing: playing
    }));
  }

  function _fmt(s) {
    if (!isFinite(s) || s < 0) return '0:00';
    var m = Math.floor(s / 60), sec = Math.floor(s % 60);
    return m + ':' + (sec < 10 ? '0' : '') + sec;
  }
})();
</script>
