<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit; }

require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, password, is_verified FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($uid, $uname, $hashed, $verified);
        $stmt->fetch();

        if ($uid && password_verify($password, $hashed)) {
            if (!$verified) {
                $error = "Please verify your email before logging in.";
            } else {
                $_SESSION['user_id']   = $uid;
                $_SESSION['username']  = $uname;
                $_SESSION['user_email'] = $email;
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – Musical World</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="no-nav">
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">🎧</div>
    <h2>Welcome Back</h2>
    <p class="subtitle">Sign in to Musical World</p>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['verified'])): ?>
      <div class="alert alert-success">✅ Account created successfully! You can now log in.</div>
    <?php endif; ?>

    <?php if (isset($_GET['logout'])): ?>
      <div class="alert alert-info">👋 You have been logged out.</div>
    <?php endif; ?>

    <?php if (isset($_GET['token_invalid'])): ?>
      <div class="alert alert-danger">❌ Invalid or expired verification link.</div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Your password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Sign In</button>
    </form>

    <p style="text-align:center; margin-top:1.2rem; font-size:0.88rem; color:var(--text-muted);">
      Don't have an account? <a href="register.php">Register</a>
    </p>
  </div>
</div>
</body>
</html>
