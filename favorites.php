<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once 'config/db.php';

$uid      = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_fav'])) {
    $aid = (int)$_POST['album_id'];
    $s = $conn->prepare("DELETE FROM favorites WHERE user_id=? AND album_id=?");
    $s->bind_param("ii", $uid, $aid);
    $s->execute();
    header("Location: favorites.php"); exit;
}

$songs = $conn->query("
    SELECT a.* FROM upload_albums a
    INNER JOIN favorites f ON f.album_id=a.album_id
    WHERE f.user_id=$uid ORDER BY f.fav_id DESC
");

$navActive = $sidebarActive = 'favorites';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Favorites – Musical World</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="dashboard-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-title">❤️ My <span>Favorites</span></div>
    <div class="card">
      <?php if (!$songs || $songs->num_rows === 0): ?>
        <div class="empty-state"><div class="empty-icon">🤍</div><p>No favorites yet. Go to <a href="dashboard.php">Dashboard</a> and add some!</p></div>
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
                    <button type="submit" name="remove_fav" class="btn btn-danger btn-sm">🗑 Remove</button>
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
