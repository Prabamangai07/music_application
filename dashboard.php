<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once 'config/db.php';

$uid      = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_fav'])) {
    $aid = (int)$_POST['album_id'];
    $chk = $conn->prepare("SELECT fav_id FROM favorites WHERE user_id=? AND album_id=?");
    $chk->bind_param("ii", $uid, $aid);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $st = $conn->prepare("DELETE FROM favorites WHERE user_id=? AND album_id=?");
    } else {
        $st = $conn->prepare("INSERT IGNORE INTO favorites (user_id,album_id) VALUES (?,?)");
    }
    $chk->close();
    $st->bind_param("ii", $uid, $aid);
    $st->execute();
    header("Location: dashboard.php"); exit;
}

$totalSongs = (int)$conn->query("SELECT COUNT(*) FROM upload_albums")->fetch_row()[0];
$myUploads  = (int)$conn->query("SELECT COUNT(*) FROM upload_albums WHERE singer_id=$uid")->fetch_row()[0];
$myFavs     = (int)$conn->query("SELECT COUNT(*) FROM favorites WHERE user_id=$uid")->fetch_row()[0];
$myContrib  = (int)$conn->query("SELECT contributions FROM user WHERE user_id=$uid")->fetch_row()[0];

$songs = $conn->query("
    SELECT a.*, IF(f.fav_id IS NOT NULL,1,0) AS is_fav
    FROM upload_albums a
    LEFT JOIN favorites f ON f.album_id=a.album_id AND f.user_id=$uid
    ORDER BY a.uploaded_at DESC
");

$navActive = $sidebarActive = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – Musical World</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="dashboard-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-title">Welcome back, <span><?= htmlspecialchars($username) ?></span> 👋</div>
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon">🎵</div><div class="stat-value"><?= $totalSongs ?></div><div class="stat-label">Total Songs</div></div>
      <div class="stat-card"><div class="stat-icon">⬆️</div><div class="stat-value"><?= $myUploads ?></div><div class="stat-label">My Uploads</div></div>
      <div class="stat-card"><div class="stat-icon">❤️</div><div class="stat-value"><?= $myFavs ?></div><div class="stat-label">Favorites</div></div>
      <div class="stat-card"><div class="stat-icon">⭐</div><div class="stat-value"><?= $myContrib ?></div><div class="stat-label">Contributions</div></div>
    </div>
    <div class="card">
      <div class="card-header">
        <h3>🎶 All Songs</h3>
        <a href="upload.php" class="btn btn-primary btn-sm">+ Upload</a>
      </div>
      <?php if (!$songs || $songs->num_rows === 0): ?>
        <div class="empty-state">
          <div class="empty-icon">🎵</div>
          <p>No songs yet. Be the first to upload!</p>
          <a href="upload.php" class="btn btn-primary" style="margin-top:1rem;">Upload Song</a>
        </div>
      <?php else: ?>
        <div class="songs-grid">
          <?php while ($s = $songs->fetch_assoc()): ?>
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
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="album_id" value="<?= $s['album_id'] ?>">
                    <button type="submit" name="toggle_fav" class="btn btn-sm <?= $s['is_fav'] ? 'btn-danger' : 'btn-outline' ?>"><?= $s['is_fav'] ? '❤️' : '🤍' ?></button>
                  </form>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include 'includes/player.php'; ?>
</body>
</html>
