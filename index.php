<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
if (isset($_SESSION['admin_id'])) {
    header("Location: admin/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Musical World – Home</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="no-nav">
<div class="landing-hero">
  <div class="wave">🎵</div>
  <h1>Musical <span>World</span></h1>
  <p>Upload, discover, and enjoy music. Your personal music platform awaits.</p>
  <div class="hero-btns">
    <a href="login.php" class="btn btn-primary">🎧 Get Started</a>
    <a href="register.php" class="btn btn-outline">✨ Create Account</a>
    <a href="admin/login.php" class="btn btn-sm btn-warning">🔐 Admin</a>
  </div>
</div>
</body>
</html>
