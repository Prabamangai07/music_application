<?php
session_start();
if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit;
}

$email    = $_SESSION['pending_email'];
$token    = $_SESSION['pending_token'];
$username = $_SESSION['pending_name'];

$verifyLink = "http://" . $_SERVER['HTTP_HOST'] . "/musical-world-project/activate_email.php?token=" . urlencode($token);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Email – Musical World</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card" style="max-width:480px; text-align:center;">
    <div class="auth-logo">📧</div>
    <h2>Verify Your Email</h2>
    <p class="subtitle">Account created for <strong><?= htmlspecialchars($email) ?></strong></p>

    <div class="alert alert-success">
      ✅ Registration successful! Click the button below to verify your account.
    </div>

    <a href="<?= htmlspecialchars($verifyLink) ?>" class="btn btn-primary btn-block" style="margin-top:1rem; font-size:1rem;">
      ✅ Verify My Account
    </a>

    <p style="margin-top:1.5rem; font-size:0.82rem; color:var(--text-muted);">
      Or copy this link manually:
    </p>
    <div style="background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:8px; padding:0.75rem; margin-top:0.5rem; word-break:break-all; font-size:0.78rem; color:var(--text-muted); text-align:left;">
      <?= htmlspecialchars($verifyLink) ?>
    </div>

    <p style="margin-top:1.5rem; font-size:0.85rem; color:var(--text-muted);">
      <a href="login.php">← Back to Login</a>
    </p>
  </div>
</div>
</body>
</html>
