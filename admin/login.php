<?php
session_start();
if (isset($_SESSION['admin_id'])) { header("Location: dashboard.php"); exit; }

require_once '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT admin_id, password FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($aid, $hashed);
        $stmt->fetch();

        if ($aid && password_verify($password, $hashed)) {
            $_SESSION['admin_id']    = $aid;
            $_SESSION['admin_email'] = $email;
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid admin credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login – Musical World</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="no-nav">
<div class="admin-header">🔐 Admin Panel – Musical World</div>
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">🛡️</div>
    <h2>Admin Login</h2>
    <p class="subtitle">Restricted access only</p>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['logout'])): ?>
      <div class="alert alert-info">👋 Logged out successfully.</div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Admin Email</label>
        <input type="email" name="email" placeholder="admin@gmail.com" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••" required>
      </div>
      <button type="submit" class="btn btn-warning btn-block">🔐 Login as Admin</button>
    </form>

    <p style="text-align:center; margin-top:1rem; font-size:0.85rem; color:var(--text-muted);">
      <a href="../index.php">← Back to Home</a>
    </p>
  </div>
</div>
</body>
</html>
