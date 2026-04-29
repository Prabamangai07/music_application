<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once 'config/db.php';

$uid      = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle fav toggle FIRST — redirect before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_fav'])) {
    $aid  = (int)$_POST['album_id'];
    $tab  = in_array($_POST['tab'] ?? '', ['recent','artists','albums','songs']) ? $_POST['tab'] : 'recent';
    $art  = isset($_POST['artist']) ? '&artist='.urlencode($_POST['artist']) : '';
    $chk  = $conn->prepare("SELECT fav_id FROM favorites WHERE user_id=? AND album_id=?");
    $chk->bind_param("ii", $uid, $aid);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $st = $conn->prepare("DELETE FROM favorites WHERE user_id=? AND album_id=?");
    } else {
        $st = $conn->prepare("INSERT IGNORE INTO favorites (user_id,album_id) VALUES (?,?)");
    }
    $st->bind_param("ii", $uid, $aid);
    $st->execute();
    header("Location: library.php?tab=$tab$art"); exit;
}

$validTabs = ['recent','artists','albums','songs'];
$tab       = in_array($_GET['tab'] ?? '', $validTabs) ? $_GET['tab'] : 'recent';
$artist    = trim($_GET['artist'] ?? '');

$navActive = $sidebarActive = 'library';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Library – Musical World</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="dashboard-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-title">📚 My <span>Library</span></div>

    <div class="lib-tabs">
      <a href="library.php?tab=recent"  class="lib-tab <?= $tab==='recent'  ?'active':'' ?>">🕐 Recently Added</a>
      <a href="library.php?tab=artists" class="lib-tab <?= $tab==='artists' ?'active':'' ?>">🎤 Artists</a>
      <a href="library.php?tab=albums"  class="lib-tab <?= $tab==='albums'  ?'active':'' ?>">💿 Albums</a>
      <a href="library.php?tab=songs"   class="lib-tab <?= $tab==='songs'   ?'active':'' ?>">🎵 Songs</a>
    </div>

    <?php if ($tab === 'recent'):
      $rows = $conn->query("
        SELECT a.*, IF(f.fav_id IS NOT NULL,1,0) AS is_fav
        FROM upload_albums a
        LEFT JOIN favorites f ON f.album_id=a.album_id AND f.user_id=$uid
        ORDER BY a.uploaded_at DESC LIMIT 20");
    ?>
    <div class="card">
      <div class="card-header"><h3>🕐 Recently Added</h3></div>
      <?php if (!$rows || $rows->num_rows===0): ?>
        <div class="empty-state"><div class="empty-icon">🎵</div><p>No songs yet.</p></div>
      <?php else: ?>
        <div class="songs-grid">
          <?php while ($s = $rows->fetch_assoc()): ?>
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
                    <input type="hidden" name="tab" value="recent">
                    <button type="submit" name="toggle_fav" class="btn btn-sm <?= $s['is_fav']?'btn-danger':'btn-outline' ?>"><?= $s['is_fav']?'❤️':'🤍' ?></button>
                  </form>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php elseif ($tab === 'artists'):
      $rows = $conn->query("
        SELECT singer_name, COUNT(*) AS song_count, MAX(song_image) AS sample_image
        FROM upload_albums GROUP BY singer_name ORDER BY song_count DESC");
    ?>
    <div class="card">
      <div class="card-header"><h3>🎤 Artists</h3></div>
      <?php if (!$rows || $rows->num_rows===0): ?>
        <div class="empty-state"><div class="empty-icon">🎤</div><p>No artists yet.</p></div>
      <?php else: ?>
        <div class="artists-grid">
          <?php while ($a = $rows->fetch_assoc()): ?>
            <div class="artist-card">
              <div class="artist-avatar">
                <?php if (!empty($a['sample_image']) && file_exists("uploads/images/".$a['sample_image'])): ?>
                  <img src="uploads/images/<?= htmlspecialchars($a['sample_image']) ?>" alt="artist">
                <?php else: ?><span>🎤</span><?php endif; ?>
              </div>
              <div class="artist-name"><?= htmlspecialchars($a['singer_name']) ?></div>
              <div class="artist-meta"><?= (int)$a['song_count'] ?> song<?= $a['song_count']!=1?'s':'' ?></div>
              <a href="library.php?tab=songs&artist=<?= urlencode($a['singer_name']) ?>" class="btn btn-outline btn-sm" style="margin-top:0.5rem;">View Songs</a>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php elseif ($tab === 'albums'):
      $rows = $conn->query("
        SELECT a.*, IF(f.fav_id IS NOT NULL,1,0) AS is_fav
        FROM upload_albums a
        LEFT JOIN favorites f ON f.album_id=a.album_id AND f.user_id=$uid
        ORDER BY a.song_name ASC");
    ?>
    <div class="card">
      <div class="card-header"><h3>💿 Albums</h3></div>
      <?php if (!$rows || $rows->num_rows===0): ?>
        <div class="empty-state"><div class="empty-icon">💿</div><p>No albums yet.</p></div>
      <?php else: ?>
        <div class="songs-grid">
          <?php while ($s = $rows->fetch_assoc()): ?>
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
                    <input type="hidden" name="tab" value="albums">
                    <button type="submit" name="toggle_fav" class="btn btn-sm <?= $s['is_fav']?'btn-danger':'btn-outline' ?>"><?= $s['is_fav']?'❤️':'🤍' ?></button>
                  </form>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php elseif ($tab === 'songs'):
      if ($artist !== '') {
          $safe = $conn->real_escape_string($artist);
          $rows = $conn->query("
            SELECT a.*, IF(f.fav_id IS NOT NULL,1,0) AS is_fav
            FROM upload_albums a
            LEFT JOIN favorites f ON f.album_id=a.album_id AND f.user_id=$uid
            WHERE a.singer_name='$safe' ORDER BY a.song_name ASC");
      } else {
          $rows = $conn->query("
            SELECT a.*, IF(f.fav_id IS NOT NULL,1,0) AS is_fav
            FROM upload_albums a
            LEFT JOIN favorites f ON f.album_id=a.album_id AND f.user_id=$uid
            ORDER BY a.song_name ASC");
      }
    ?>
    <div class="card">
      <div class="card-header">
        <h3><?= $artist ? '🎵 Songs by '.htmlspecialchars($artist) : '🎵 All Songs' ?></h3>
        <?php if ($artist): ?><a href="library.php?tab=artists" class="btn btn-outline btn-sm">← Artists</a><?php endif; ?>
      </div>
      <?php if (!$rows || $rows->num_rows===0): ?>
        <div class="empty-state"><div class="empty-icon">🎵</div><p>No songs found.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Cover</th><th>Song</th><th>Artist</th><th>Format</th><th>Added</th><th>Actions</th></tr></thead>
            <tbody>
              <?php $i=1; while ($s = $rows->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td>
                  <?php if (!empty($s['song_image']) && file_exists("uploads/images/".$s['song_image'])): ?>
                    <img src="uploads/images/<?= htmlspecialchars($s['song_image']) ?>" style="width:38px;height:38px;object-fit:cover;border-radius:6px;">
                  <?php else: ?><span style="font-size:1.4rem;">🎵</span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($s['song_name']) ?></td>
                <td><?= htmlspecialchars($s['singer_name']) ?></td>
                <td><span class="badge badge-primary"><?= htmlspecialchars($s['song_format']) ?></span></td>
                <td style="color:var(--text-muted);font-size:0.8rem;"><?= date('d M Y', strtotime($s['uploaded_at'])) ?></td>
                <td>
                  <div style="display:flex;gap:0.4rem;align-items:center;">
                    <button class="btn btn-primary btn-sm" onclick="playSong('<?= htmlspecialchars(addslashes($s['audio_file'])) ?>','<?= htmlspecialchars(addslashes($s['song_name'])) ?>','<?= htmlspecialchars(addslashes($s['singer_name'])) ?>','<?= htmlspecialchars(addslashes($s['song_image'])) ?>')">▶</button>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="album_id" value="<?= $s['album_id'] ?>">
                      <input type="hidden" name="tab" value="songs">
                      <?php if ($artist): ?><input type="hidden" name="artist" value="<?= htmlspecialchars($artist) ?>"><?php endif; ?>
                      <button type="submit" name="toggle_fav" class="btn btn-sm <?= $s['is_fav']?'btn-danger':'btn-outline' ?>"><?= $s['is_fav']?'❤️':'🤍' ?></button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </main>
</div>
<?php include 'includes/player.php'; ?>
</body>
</html>
