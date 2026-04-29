<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once 'config/db.php';

$uid      = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_song'])) {
    $aid = (int)$_POST['album_id'];
    $st  = $conn->prepare("SELECT song_image, audio_file FROM upload_albums WHERE album_id=? AND singer_id=?");
    $st->bind_param("ii", $aid, $uid);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    if ($row) {
        @unlink("uploads/images/" . $row['song_image']);
        @unlink("uploads/songs/"  . $row['audio_file']);
        $d = $conn->prepare("DELETE FROM upload_albums WHERE album_id=? AND singer_id=?");
        $d->bind_param("ii", $aid, $uid);
        $d->execute();
    }
    header("Location: my_songs.php"); exit;
}

$songs         = $conn->query("SELECT * FROM upload_albums WHERE singer_id=$uid ORDER BY uploaded_at DESC");
$contributions = (int)$conn->query("SELECT contributions FROM user WHERE user_id=$uid")->fetch_row()[0];

$navActive = $sidebarActive = 'my_songs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Songs – Musical World</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="dashboard-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-title">🎶 My <span>Songs</span></div>
    <div class="stats-grid" style="max-width:400px; margin-bottom:1.5rem;">
      <div class="stat-card"><div class="stat-icon">🎵</div><div class="stat-value"><?= $songs ? $songs->num_rows : 0 ?></div><div class="stat-label">Uploaded</div></div>
      <div class="stat-card"><div class="stat-icon">⭐</div><div class="stat-value"><?= $contributions ?></div><div class="stat-label">Contributions</div></div>
    </div>
    <div class="card">
      <div class="card-header">
        <h3>My Uploaded Songs</h3>
        <a href="upload.php" class="btn btn-primary btn-sm">+ Upload New</a>
      </div>
      <?php if (!$songs || $songs->num_rows === 0): ?>
        <div class="empty-state"><div class="empty-icon">🎵</div><p>You haven't uploaded any songs yet.</p><a href="upload.php" class="btn btn-primary" style="margin-top:1rem;">Upload Your First Song</a></div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Cover</th><th>Song Name</th><th>Artist</th><th>Format</th><th>Uploaded</th><th>Actions</th></tr></thead>
            <tbody>
              <?php $i=1; while ($s = $songs->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td>
                  <?php if (!empty($s['song_image']) && file_exists("uploads/images/".$s['song_image'])): ?>
                    <img src="uploads/images/<?= htmlspecialchars($s['song_image']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                  <?php else: ?><span style="font-size:1.5rem;">🎵</span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($s['song_name']) ?></td>
                <td><?= htmlspecialchars($s['singer_name']) ?></td>
                <td><span class="badge badge-primary"><?= htmlspecialchars($s['song_format']) ?></span></td>
                <td style="color:var(--text-muted);font-size:0.8rem;"><?= date('d M Y', strtotime($s['uploaded_at'])) ?></td>
                <td>
                  <button class="btn btn-primary btn-sm" onclick="playSong('<?= htmlspecialchars(addslashes($s['audio_file'])) ?>','<?= htmlspecialchars(addslashes($s['song_name'])) ?>','<?= htmlspecialchars(addslashes($s['singer_name'])) ?>','<?= htmlspecialchars(addslashes($s['song_image'])) ?>')">▶</button>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this song?')">
                    <input type="hidden" name="album_id" value="<?= $s['album_id'] ?>">
                    <button type="submit" name="delete_song" class="btn btn-danger btn-sm">🗑</button>
                  </form>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php include 'includes/player.php'; ?>
</body>
</html>
