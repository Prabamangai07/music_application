<?php
session_start();
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
if (!isset($_SESSION['user_id']) && !$isLocal) { header("Location: login.php"); exit; }

require_once 'config/db.php';

$results = [];
$queries = [
    "playlists" => "CREATE TABLE IF NOT EXISTS `playlists` (
        `playlist_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "playlist_songs" => "CREATE TABLE IF NOT EXISTS `playlist_songs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `playlist_id` INT NOT NULL,
        `album_id` INT NOT NULL,
        `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_pl_song` (`playlist_id`,`album_id`),
        FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`playlist_id`) ON DELETE CASCADE,
        FOREIGN KEY (`album_id`) REFERENCES `upload_albums`(`album_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($queries as $name => $sql) {
    $results[$name] = $conn->query($sql) ? "✅ Table `$name` ready." : "❌ `$name` failed: ".$conn->error;
}
$allOk = !array_filter($results, fn($r) => str_starts_with($r, '❌'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup – Musical World</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">🛠️</div>
    <h2>Database Setup</h2>
    <p class="subtitle">Musical World – Run once to create tables</p>
    <div style="margin-top:1.25rem;">
      <?php foreach ($results as $r): ?>
        <div class="alert <?= str_starts_with($r,'✅')?'alert-success':'alert-danger' ?>" style="margin-bottom:0.6rem;">
          <?= htmlspecialchars($r) ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if ($allOk): ?>
      <div class="alert alert-info" style="margin-top:0.75rem;">All tables ready! You can now use all features.</div>
    <?php endif; ?>
    <a href="dashboard.php" class="btn btn-primary btn-block" style="margin-top:1.25rem;">🎵 Go to Dashboard</a>
  </div>
</div>
</body>
</html>
