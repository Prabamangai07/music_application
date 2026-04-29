<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $uid  = (int)$_POST['user_id'];
        $rows = $conn->query("SELECT song_image, audio_file FROM upload_albums WHERE singer_id=$uid");
        if ($rows) { while ($s = $rows->fetch_assoc()) { @unlink("../uploads/images/".$s['song_image']); @unlink("../uploads/songs/".$s['audio_file']); } }
        $conn->query("DELETE FROM user WHERE user_id=$uid");
        header("Location: users.php?msg=deleted"); exit;
    }
    if (isset($_POST['toggle_verify'])) {
        $uid  = (int)$_POST['user_id'];
        $cur  = (int)$_POST['current_status'];
        $conn->query("UPDATE user SET is_verified=".($cur?0:1)." WHERE user_id=$uid");
        header("Location: users.php?msg=updated"); exit;
    }
}

$users = $conn->query("
    SELECT u.*, (SELECT COUNT(*) FROM upload_albums WHERE singer_id=u.user_id) AS song_count
    FROM user u ORDER BY u.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users – Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-header">🔐 Admin Panel – Musical World</div>
<nav class="navbar" style="position:relative;top:auto;">
  <div class="brand">🛡️ Admin <span>Panel</span></div>
  <div class="nav-links">
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="users.php" class="active">👥 Users</a>
    <a href="songs.php">🎵 Songs</a>
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
      <a href="users.php" class="active">👥 Manage Users</a>
      <a href="songs.php">🎵 Manage Songs</a>
      <a href="logout.php">🚪 Logout</a>
    </nav>
  </aside>
  <main class="main-content">
    <div class="page-title">👥 Manage <span>Users</span></div>
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success">✅ <?= $_GET['msg']==='deleted'?'User deleted.':'Status updated.' ?></div>
    <?php endif; ?>
    <div class="card">
      <div class="card-header"><h3>All Users (<?= $users ? $users->num_rows : 0 ?>)</h3></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Username</th><th>Email</th><th>Status</th><th>Songs</th><th>Contributions</th><th>Joined</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if (!$users || $users->num_rows===0): ?>
              <tr><td colspan="8" style="text-align:center;color:var(--text-muted);">No users yet.</td></tr>
            <?php else: $i=1; while ($u = $users->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td style="font-size:0.82rem;"><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge <?= $u['is_verified']?'badge-success':'badge-danger' ?>"><?= $u['is_verified']?'✅ Verified':'⏳ Pending' ?></span></td>
                <td><?= (int)$u['song_count'] ?></td>
                <td><?= (int)$u['contributions'] ?></td>
                <td style="font-size:0.78rem;color:var(--text-muted);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                      <input type="hidden" name="current_status" value="<?= $u['is_verified'] ?>">
                      <button type="submit" name="toggle_verify" class="btn btn-sm <?= $u['is_verified']?'btn-warning':'btn-success' ?>"><?= $u['is_verified']?'🔒 Unverify':'✅ Verify' ?></button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user and all their songs?')">
                      <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                      <button type="submit" name="delete_user" class="btn btn-danger btn-sm">🗑 Delete</button>
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
</body>
</html>
