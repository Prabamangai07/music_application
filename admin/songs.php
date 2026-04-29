<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }
require_once '../config/db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_song'])) {
    $aid = (int)$_POST['album_id'];
    $row = $conn->query("SELECT song_image, audio_file FROM upload_albums WHERE album_id=$aid")->fetch_assoc();
    if ($row) {
        @unlink("../uploads/images/" . $row['song_image']);
        @unlink("../uploads/songs/"  . $row['audio_file']);
        $conn->query("DELETE FROM upload_albums WHERE album_id=$aid");
        $msg = "Song deleted successfully.";
    }
    header("Location: songs.php?msg=deleted"); exit;
}

$songs = $conn->query("
    SELECT a.*, u.username
    FROM upload_albums a
    JOIN user u ON u.user_id = a.singer_id
    ORDER BY a.uploaded_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Songs – Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-header">🔐 Admin Panel – Musical World</div>
<nav class="navbar" style="position:relative;top:auto;">
  <div class="brand">🛡️ Admin <span>Panel</span></div>
  <div class="nav-links">
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="users.php">👥 Users</a>
    <a href="songs.php" class="active">🎵 Songs</a>
    <a href="logout.php">🚪 Logout</a>
  </div>
</nav>
<div class="dashboard-layout" style="padding-top:0;">
  <aside class="sidebar" style="top:0;height:100vh;">
    <div class="user-info">
      <div class="avatar">A</div>
      <div class="name">Administrator</div>
      <div class="email"><?= htmlspecialchars($_SESSION['admin_email']) ?></div>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php">📊 Dashboard</a>
      <a href="users.php">👥 Manage Users</a>
      <a href="songs.php" class="active">🎵 Manage Songs</a>
      <a href="logout.php">🚪 Logout</a>
    </nav>
  </aside>
  <main class="main-content">
    <div class="page-title">🎵 Manage <span>Songs</span></div>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
      <div class="alert alert-success">✅ Song deleted successfully.</div>
    <?php endif; ?>
    <div class="card">
      <div class="card-header"><h3>All Songs (<?= $songs ? $songs->num_rows : 0 ?>)</h3></div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Cover</th><th>Song Name</th><th>Artist</th><th>Format</th><th>Uploaded By</th><th>Date</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if (!$songs || $songs->num_rows === 0): ?>
              <tr><td colspan="8" style="text-align:center;color:var(--text-muted);">No songs yet.</td></tr>
            <?php else: $i=1; while ($s = $songs->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td>
                  <?php if (!empty($s['song_image']) && file_exists("../uploads/images/".$s['song_image'])): ?>
                    <img src="../uploads/images/<?= htmlspecialchars($s['song_image']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                  <?php else: ?><span style="font-size:1.5rem;">🎵</span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($s['song_name']) ?></td>
                <td><?= htmlspecialchars($s['singer_name']) ?></td>
                <td><span class="badge badge-primary"><?= htmlspecialchars($s['song_format']) ?></span></td>
                <td><?= htmlspecialchars($s['username']) ?></td>
                <td style="font-size:0.78rem;color:var(--text-muted);"><?= date('d M Y', strtotime($s['uploaded_at'])) ?></td>
                <td>
                  <div style="display:flex;gap:0.4rem;">
                    <button class="btn btn-primary btn-sm" onclick="adminPlay('../uploads/songs/<?= htmlspecialchars(addslashes($s['audio_file'])) ?>','<?= htmlspecialchars(addslashes($s['song_name'])) ?>','<?= htmlspecialchars(addslashes($s['singer_name'])) ?>')">▶ Play</button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this song permanently?')">
                      <input type="hidden" name="album_id" value="<?= $s['album_id'] ?>">
                      <button type="submit" name="delete_song" class="btn btn-danger btn-sm">🗑 Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Admin Mini Player -->
<div class="player-bar" id="adminPlayerBar">
  <div class="player-info">
    <div class="player-text">
      <div class="player-title"  id="adminTitle">–</div>
      <div class="player-artist" id="adminArtist">Admin Preview</div>
    </div>
  </div>
  <div class="player-controls">
    <div class="controls-btns">
      <button onclick="adminSeek(-10)">⏪</button>
      <button class="play-btn" id="adminPPBtn" onclick="adminToggle()">▶</button>
      <button onclick="adminSeek(10)">⏩</button>
    </div>
    <div class="progress-wrap">
      <span id="adminCurrent">0:00</span>
      <input type="range" id="adminProg" value="0" min="0" step="0.1" oninput="adminAudio.currentTime=this.value">
      <span id="adminDur">0:00</span>
    </div>
  </div>
  <div class="player-right">
    <div class="volume-wrap">🔊 <input type="range" min="0" max="1" step="0.05" value="1" oninput="adminAudio.volume=this.value"></div>
  </div>
</div>
<audio id="adminAudioEl"></audio>

<script>
var adminAudio = document.getElementById('adminAudioEl');
var adminBar   = document.getElementById('adminPlayerBar');
adminBar.style.display = 'none';

function adminPlay(file, name, artist) {
  adminAudio.src = file;
  adminAudio.play().catch(function(){});
  adminBar.style.display = 'flex';
  document.body.style.paddingBottom = 'var(--player-h)';
  document.getElementById('adminTitle').textContent  = name;
  document.getElementById('adminArtist').textContent = artist;
  document.getElementById('adminPPBtn').textContent  = '⏸';
}
function adminToggle() {
  if (adminAudio.paused) { adminAudio.play(); document.getElementById('adminPPBtn').textContent='⏸'; }
  else { adminAudio.pause(); document.getElementById('adminPPBtn').textContent='▶'; }
}
function adminSeek(s) { adminAudio.currentTime = Math.max(0, adminAudio.currentTime + s); }
adminAudio.addEventListener('play',  function(){ document.getElementById('adminPPBtn').textContent='⏸'; });
adminAudio.addEventListener('pause', function(){ document.getElementById('adminPPBtn').textContent='▶'; });
adminAudio.addEventListener('ended', function(){ document.getElementById('adminPPBtn').textContent='▶'; });
adminAudio.addEventListener('timeupdate', function(){
  var fmt = function(s){ var m=Math.floor(s/60),sec=Math.floor(s%60); return m+':'+(sec<10?'0':'')+sec; };
  document.getElementById('adminCurrent').textContent = fmt(adminAudio.currentTime);
  document.getElementById('adminProg').value = adminAudio.currentTime;
});
adminAudio.addEventListener('loadedmetadata', function(){
  var fmt = function(s){ var m=Math.floor(s/60),sec=Math.floor(s%60); return m+':'+(sec<10?'0':'')+sec; };
  document.getElementById('adminProg').max = adminAudio.duration;
  document.getElementById('adminDur').textContent = fmt(adminAudio.duration);
});
</script>
</body>
</html>
