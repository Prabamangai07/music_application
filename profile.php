<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once 'config/db.php';

$uid = (int)$_SESSION['user_id'];
$msg = $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $newName = trim($_POST['username']);
        if (strlen($newName) < 2) {
            $msg = "Username must be at least 2 characters."; $msgType = 'danger';
        } else {
            $st = $conn->prepare("UPDATE user SET username=? WHERE user_id=?");
            $st->bind_param("si", $newName, $uid);
            if ($st->execute()) { $_SESSION['username'] = $newName; $msg = "Profile updated!"; $msgType = 'success'; }
            else { $msg = "Update failed."; $msgType = 'danger'; }
        }
    } elseif (isset($_POST['change_password'])) {
        $cur  = $_POST['current_password'] ?? '';
        $new  = $_POST['new_password']     ?? '';
        $conf = $_POST['confirm_password'] ?? '';
        $row  = $conn->query("SELECT password FROM user WHERE user_id=$uid")->fetch_assoc();
        if (!$row || !password_verify($cur, $row['password'])) {
            $msg = "Current password is incorrect."; $msgType = 'danger';
        } elseif (strlen($new) < 6) {
            $msg = "New password must be at least 6 characters."; $msgType = 'danger';
        } elseif ($new !== $conf) {
            $msg = "Passwords do not match."; $msgType = 'danger';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $st   = $conn->prepare("UPDATE user SET password=? WHERE user_id=?");
            $st->bind_param("si", $hash, $uid);
            $st->execute();
            $msg = "Password changed successfully!"; $msgType = 'success';
        }
    }
}

$user = $conn->query("SELECT * FROM user WHERE user_id=$uid")->fetch_assoc();
if (!$user) { session_destroy(); header("Location: login.php"); exit; }
$username = $user['username'];

$totalUploads = (int)$conn->query("SELECT COUNT(*) FROM upload_albums WHERE singer_id=$uid")->fetch_row()[0];
$totalFavs    = (int)$conn->query("SELECT COUNT(*) FROM favorites WHERE user_id=$uid")->fetch_row()[0];
$plCheck      = $conn->query("SHOW TABLES LIKE 'playlists'");
$totalPlaylists = ($plCheck && $plCheck->num_rows > 0)
    ? (int)$conn->query("SELECT COUNT(*) FROM playlists WHERE user_id=$uid")->fetch_row()[0] : 0;

$navActive = $sidebarActive = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile – Musical World</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="dashboard-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-title">👤 My <span>Profile</span></div>

    <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType ?>"><?= $msgType==='success'?'✅':'⚠️' ?> <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="profile-header-card">
      <div class="profile-avatar-lg"><?= strtoupper(substr($user['username'],0,1)) ?></div>
      <div class="profile-header-info">
        <h2><?= htmlspecialchars($user['username']) ?></h2>
        <p><?= htmlspecialchars($user['email']) ?></p>
        <p class="profile-joined">📅 Joined <?= date('d M Y', strtotime($user['created_at'])) ?></p>
        <span class="badge badge-<?= $user['is_verified'] ? 'success':'danger' ?>">
          <?= $user['is_verified'] ? '✅ Verified':'❌ Not Verified' ?>
        </span>
      </div>
    </div>

    <div class="stats-grid" style="margin-bottom:2rem;">
      <div class="stat-card"><div class="stat-icon">⬆️</div><div class="stat-value"><?= $totalUploads ?></div><div class="stat-label">Uploads</div></div>
      <div class="stat-card"><div class="stat-icon">❤️</div><div class="stat-value"><?= $totalFavs ?></div><div class="stat-label">Favorites</div></div>
      <div class="stat-card"><div class="stat-icon">📋</div><div class="stat-value"><?= $totalPlaylists ?></div><div class="stat-label">Playlists</div></div>
      <div class="stat-card"><div class="stat-icon">⭐</div><div class="stat-value"><?= (int)$user['contributions'] ?></div><div class="stat-label">Contributions</div></div>
    </div>

    <div class="profile-forms-grid">
      <div class="card">
        <div class="card-header"><h3>✏️ Edit Profile</h3></div>
        <form method="POST">
          <div class="form-group"><label>Username</label><input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required minlength="2"></div>
          <div class="form-group"><label>Email (read-only)</label><input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled></div>
          <button type="submit" name="update_profile" class="btn btn-primary">💾 Save Changes</button>
        </form>
      </div>
      <div class="card">
        <div class="card-header"><h3>🔒 Change Password</h3></div>
        <form method="POST">
          <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
          <div class="form-group"><label>New Password (min 6 chars)</label><input type="password" name="new_password" required minlength="6"></div>
          <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" required minlength="6"></div>
          <button type="submit" name="change_password" class="btn btn-primary">🔑 Update Password</button>
        </form>
      </div>
    </div>
  </main>
</div>
<?php include 'includes/player.php'; ?>
</body>
</html>
