<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }
require_once '../config/db.php';

$totalUsers    = (int)$conn->query("SELECT COUNT(*) FROM user")->fetch_row()[0];
$verifiedUsers = (int)$conn->query("SELECT COUNT(*) FROM user WHERE is_verified=1")->fetch_row()[0];
$totalSongs    = (int)$conn->query("SELECT COUNT(*) FROM upload_albums")->fetch_row()[0];
$totalFavs     = (int)$conn->query("SELECT COUNT(*) FROM favorites")->fetch_row()[0];

$recentSongs = $conn->query("
    SELECT a.song_name, a.singer_name, a.song_format, a.uploaded_at, u.username
    FROM upload_albums a JOIN user u ON u.user_id=a.singer_id
    ORDER BY a.uploaded_at DESC LIMIT 5
");
$recentUsers = $conn->query("
    SELECT username, email, is_verified, contributions, created_at
    FROM user ORDER BY created_at DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard – Musical World</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-header">🔐 Admin Panel – Musical World</div>
<nav class="navbar" style="position:relative;top:auto;">
  <div class="brand">🛡️ Admin <span>Panel</span></div>
  <div class="nav-links">
    <a href="dashboard.php" class="active">📊 Dashboard</a>
    <a href="users.php">👥 Users</a>
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
      <a href="dashboard.php" class="active">📊 Dashboard</a>
      <a href="users.php">👥 Manage Users</a>
      <a href="songs.php">🎵 Manage Songs</a>
      <a href="logout.php">🚪 Logout</a>
    </nav>
  </aside>
  <main class="main-content">
    <div class="page-title">📊 Admin <span>Dashboard</span></div>
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-value"><?= $totalUsers ?></div><div class="stat-label">Total Users</div></div>
      <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-value"><?= $verifiedUsers ?></div><div class="stat-label">Verified</div></div>
      <div class="stat-card"><div class="stat-icon">🎵</div><div class="stat-value"><?= $totalSongs ?></div><div class="stat-label">Total Songs</div></div>
      <div class="stat-card"><div class="stat-icon">❤️</div><div class="stat-value"><?= $totalFavs ?></div><div class="stat-label">Favorites</div></div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem;">
      <div class="card">
        <div class="card-header"><h3>🎵 Recent Songs</h3><a href="songs.php" class="btn btn-outline btn-sm">View All</a></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Song</th><th>Artist</th><th>By</th><th>Date</th></tr></thead>
            <tbody>
              <?php if (!$recentSongs || $recentSongs->num_rows===0): ?>
                <tr><td colspan="4" style="text-align:center;color:var(--text-muted);">No songs yet</td></tr>
              <?php else: while ($s = $recentSongs->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($s['song_name']) ?></td>
                  <td><?= htmlspecialchars($s['singer_name']) ?></td>
                  <td><?= htmlspecialchars($s['username']) ?></td>
                  <td style="font-size:0.78rem;color:var(--text-muted);"><?= date('d M Y', strtotime($s['uploaded_at'])) ?></td>
                </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3>👥 Recent Users</h3><a href="users.php" class="btn btn-outline btn-sm">View All</a></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Name</th><th>Email</th><th>Status</th></tr></thead>
            <tbody>
              <?php if (!$recentUsers || $recentUsers->num_rows===0): ?>
                <tr><td colspan="3" style="text-align:center;color:var(--text-muted);">No users yet</td></tr>
              <?php else: while ($u = $recentUsers->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($u['username']) ?></td>
                  <td style="font-size:0.8rem;"><?= htmlspecialchars($u['email']) ?></td>
                  <td><span class="badge <?= $u['is_verified']?'badge-success':'badge-danger' ?>"><?= $u['is_verified']?'Verified':'Pending' ?></span></td>
                </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
</body>
</html>
