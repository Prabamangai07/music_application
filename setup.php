<?php
// This file auto-inserts the correct admin password hash into the database.
// Run it ONCE at: http://localhost/musical-world-project/setup.php
// Then DELETE this file.

require_once 'config/db.php';

$messages = [];

// 1. Create tables
$tables = [
"CREATE TABLE IF NOT EXISTS `user` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `is_verified` TINYINT(1) DEFAULT 0,
  `verification_token` VARCHAR(255) DEFAULT NULL,
  `contributions` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
)",
"CREATE TABLE IF NOT EXISTS `upload_albums` (
  `album_id` INT AUTO_INCREMENT PRIMARY KEY,
  `singer_id` INT NOT NULL,
  `song_name` VARCHAR(255) NOT NULL,
  `song_format` VARCHAR(50) NOT NULL,
  `singer_name` VARCHAR(255) NOT NULL,
  `song_image` VARCHAR(255) NOT NULL,
  `audio_file` VARCHAR(255) NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`singer_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS `favorites` (
  `fav_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `album_id` INT NOT NULL,
  UNIQUE KEY `unique_fav` (`user_id`, `album_id`),
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`album_id`) REFERENCES `upload_albums`(`album_id`) ON DELETE CASCADE
)"
];

foreach ($tables as $sql) {
    if ($conn->query($sql)) {
        $messages[] = ['ok', 'Table created/verified ✅'];
    } else {
        $messages[] = ['err', 'Table error: ' . $conn->error];
    }
}

// 2. Trigger
$conn->query("DROP TRIGGER IF EXISTS `IncrementCount`");
$trigger = "CREATE TRIGGER `IncrementCount`
AFTER INSERT ON `upload_albums`
FOR EACH ROW
UPDATE user SET user.contributions = user.contributions + 1
WHERE NEW.singer_id = user.user_id";
if ($conn->query($trigger)) {
    $messages[] = ['ok', 'Trigger IncrementCount created ✅'];
} else {
    $messages[] = ['err', 'Trigger error: ' . $conn->error];
}

// 3. Stored Procedure
$conn->query("DROP PROCEDURE IF EXISTS `uploadsongs`");
$proc = "CREATE PROCEDURE `uploadsongs`(
    IN p_singer_id INT,
    IN p_song_name VARCHAR(255),
    IN p_song_format VARCHAR(255),
    IN p_singer_name VARCHAR(255),
    IN p_song_image VARCHAR(255),
    IN p_audio_file VARCHAR(255)
)
BEGIN
    INSERT INTO upload_albums(singer_id, song_name, song_format, singer_name, song_image, audio_file)
    VALUES(p_singer_id, p_song_name, p_song_format, p_singer_name, p_song_image, p_audio_file);
END";
if ($conn->query($proc)) {
    $messages[] = ['ok', 'Stored Procedure uploadsongs created ✅'];
} else {
    $messages[] = ['err', 'Procedure error: ' . $conn->error];
}

// 4. Admin account
$hash = password_hash('s123', PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT IGNORE INTO admin (email, password) VALUES (?, ?)");
$email = 'admin@gmail.com';
$stmt->bind_param("ss", $email, $hash);
if ($stmt->execute()) {
    $messages[] = ['ok', 'Admin account ready (admin@gmail.com / s123) ✅'];
} else {
    $messages[] = ['err', 'Admin insert error: ' . $conn->error];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Setup – Musical World</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card" style="max-width:520px;">
    <div class="auth-logo">⚙️</div>
    <h2>Database Setup</h2>
    <p class="subtitle">Musical World – One-time setup</p>
    <div style="margin-top:1rem;">
      <?php foreach ($messages as [$type, $msg]): ?>
        <div class="alert <?= $type === 'ok' ? 'alert-success' : 'alert-danger' ?>">
          <?= htmlspecialchars($msg) ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php $hasError = in_array('err', array_column($messages, 0)); ?>
    <?php if (!$hasError): ?>
      <div class="alert alert-info" style="margin-top:1rem;">
        🎉 Setup complete! <strong>Delete this file (setup.php)</strong> then proceed.
      </div>
      <a href="login.php" class="btn btn-primary btn-block" style="margin-top:1rem;">Go to Login →</a>
    <?php else: ?>
      <div class="alert alert-warning" style="margin-top:1rem;">
        ⚠️ Some errors occurred. Check your <code>config/db.php</code> credentials.
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
