<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once 'config/db.php';

$uid      = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];
$msg = $msgType = '';

// Auto-create tables if missing
$conn->query("CREATE TABLE IF NOT EXISTS `playlists` (
  `playlist_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `playlist_songs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `playlist_id` INT NOT NULL,
  `album_id` INT NOT NULL,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_pl_song` (`playlist_id`,`album_id`),
  FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`playlist_id`) ON DELETE CASCADE,
  FOREIGN KEY (`album_id`) REFERENCES `upload_albums`(`album_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Handle all POST actions — redirect immediately after each
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['create_playlist'])) {
        $name = trim($_POST['playlist_name'] ?? '');
        if ($name !== '') {
            $st = $conn->prepare("INSERT INTO playlists (user_id,name) VALUES (?,?)");
            $st->bind_param("is", $uid, $name);
            $st->execute();
        }
        header("Location: playlist.php"); exit;
    }

    if (isset($_POST['delete_playlist'])) {
        $pid = (int)$_POST['playlist_id'];
        $st  = $conn->prepare("DELETE FROM playlists WHERE playlist_id=? AND user_id=?");
        $st->bind_param("ii", $pid, $uid);
        $st->execute();
        header("Location: playlist.php"); exit;
    }

    if (isset($_POST['add_to_playlist'])) {
        $pid = (int)$_POST['playlist_id'];
        $aid = (int)$_POST['album_id'];
        if ($pid && $aid) {
            $st = $conn->prepare("INSERT IGNORE INTO playlist_songs (playlist_id,album_id) VALUES (?,?)");
            $st->bind_param("ii", $pid, $aid);
            $st->execute();
        }
        header("Location: playlist.php?view=$pid"); exit;
    }

    if (isset($_POST['remove_from_playlist'])) {
        $pid = (int)$_POST['playlist_id'];
        $aid = (int)$_POST['album_id'];
        $st  = $conn->prepare("DELETE FROM playlist_songs WHERE playlist_id=? AND album_id=?");
        $st->bind_param("ii", $pid, $aid);
        $st->execute();
        header("Location: playlist.php?view=$pid"); exit;
    }
}

$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;

// Fetch playlists
$playlists = $conn->query("
    SELECT p.*, COUNT(ps.album_id) AS song_count
    FROM playlists p
    LEFT JOIN playlist_songs ps ON ps.playlist_id=p.playlist_id
    WHERE p.user_id=$uid
    GROUP BY p.playlist_id
    ORDER BY p.created_at DESC
");

// Fetch current playlist detail
$currentPlaylist = null;
$playlistSongs   = null;
if ($viewId) {
    $st = $conn->prepare("SELECT * FROM playlists WHERE playlist_id=? AND user_id=?");
    $st->bind_param("ii", $viewId, $uid);
    $st->execute();
    $currentPlaylist = $st->get_result()->fetch_assoc();
    if ($currentPlaylist) {
        $playlistSongs = $conn->query("
            SELECT a.* FROM upload_albums a
            INNER JOIN playlist_songs ps ON ps.album_id=a.album_id
            WHERE ps.playlist_id=$viewId
            ORDER BY ps.added_at DESC
        ");
    }
}

$navActive = $sidebarActive = 'playlist';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Playlists – Musical World</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="dashboard-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-title">📋 My <span>Playlists</span></div>

    <!-- Create Playlist -->
    <div class="card" style="max-width:480px;margin-bottom:2rem;">
      <div class="card-header"><h3>➕ New Playlist</h3></div>
      <form method="POST" style="display:flex;gap:0.75rem;">
        <input type="text" name="playlist_name" placeholder="Playlist name..." class="form-input-inline" required maxlength="100">
        <button type="submit" name="create_playlist" class="btn btn-primary btn-sm">Create</button>
      </form>
    </div>

    <?php if ($viewId && $currentPlaylist): ?>
      <div style="margin-bottom:1rem;">
        <a href="playlist.php" class="btn btn-outline btn-sm">← Back to Playlists</a>
      </div>
      <div class="card">
        <div class="card-header">
          <h3>🎵 <?= htmlspecialchars($currentPlaylist['name']) ?></h3>
          <button class="btn btn-primary btn-sm" onclick="document.getElementById('addSongModal').classList.add('active')">+ Add Song</button>
        </div>
        <?php if (!$playlistSongs || $playlistSongs->num_rows === 0): ?>
          <div class="empty-state"><div class="empty-icon">🎵</div><p>No songs yet. Click "+ Add Song" to add some!</p></div>
        <?php else: ?>
          <div class="songs-grid">
            <?php while ($s = $playlistSongs->fetch_assoc()): ?>
              <div class="song-card">
                <div class="song-cover">
                  <?php if (!empty($s['song_image']) && file_exists("uploads/images/".$s['song_image'])): ?>
                    <img src="uploads/images/<?= htmlspecialchars($s['song_image']) ?>" alt="cover">
                  <?php else: ?><div class="cover-placeholder">🎵</div><?php endif; ?>
                </div>
                <div class="song-info">
                  <div class="song-name" title="<?= htmlspecialchars($s['song_name']) ?>"><?= htmlspecialchars($s['song_name']) ?></div>
                  <div class="song-artist">🎤 <?= htmlspecialchars($s['singer_name']) ?></div>
                  <div class="song-actions">
                    <button class="btn btn-primary btn-sm" onclick="playSong('<?= htmlspecialchars(addslashes($s['audio_file'])) ?>','<?= htmlspecialchars(addslashes($s['song_name'])) ?>','<?= htmlspecialchars(addslashes($s['singer_name'])) ?>','<?= htmlspecialchars(addslashes($s['song_image'])) ?>')">▶ Play</button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Remove from playlist?')">
                      <input type="hidden" name="playlist_id" value="<?= $viewId ?>">
                      <input type="hidden" name="album_id" value="<?= $s['album_id'] ?>">
                      <button type="submit" name="remove_from_playlist" class="btn btn-danger btn-sm">🗑</button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Add Song Modal -->
      <div class="modal-overlay" id="addSongModal">
        <div class="modal">
          <div class="modal-header">
            <h3>Add Song to Playlist</h3>
            <button class="modal-close" onclick="document.getElementById('addSongModal').classList.remove('active')">✕</button>
          </div>
          <form method="POST">
            <input type="hidden" name="playlist_id" value="<?= $viewId ?>">
            <div class="form-group">
              <label>Select Song</label>
              <select name="album_id" required>
                <option value="">-- Choose a song --</option>
                <?php
                $allSongs = $conn->query("SELECT album_id, song_name, singer_name FROM upload_albums ORDER BY song_name ASC");
                if ($allSongs): while ($ms = $allSongs->fetch_assoc()): ?>
                  <option value="<?= $ms['album_id'] ?>"><?= htmlspecialchars($ms['song_name']) ?> – <?= htmlspecialchars($ms['singer_name']) ?></option>
                <?php endwhile; endif; ?>
              </select>
            </div>
            <button type="submit" name="add_to_playlist" class="btn btn-primary btn-block">Add to Playlist</button>
          </form>
        </div>
      </div>

    <?php else: ?>
      <?php if (!$playlists || $playlists->num_rows === 0): ?>
        <div class="empty-state"><div class="empty-icon">📋</div><p>No playlists yet. Create your first one above!</p></div>
      <?php else: ?>
        <div class="playlists-grid">
          <?php while ($pl = $playlists->fetch_assoc()): ?>
            <div class="playlist-card">
              <div class="playlist-icon">📋</div>
              <div class="playlist-info">
                <div class="playlist-name"><?= htmlspecialchars($pl['name']) ?></div>
                <div class="playlist-meta"><?= (int)$pl['song_count'] ?> song<?= $pl['song_count'] != 1 ? 's':'' ?></div>
                <div class="playlist-date"><?= date('d M Y', strtotime($pl['created_at'])) ?></div>
              </div>
              <div class="playlist-actions">
                <a href="playlist.php?view=<?= $pl['playlist_id'] ?>" class="btn btn-primary btn-sm">▶ Open</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this playlist?')">
                  <input type="hidden" name="playlist_id" value="<?= $pl['playlist_id'] ?>">
                  <button type="submit" name="delete_playlist" class="btn btn-danger btn-sm">🗑</button>
                </form>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</div>
<?php include 'includes/player.php'; ?>
</body>
</html>
