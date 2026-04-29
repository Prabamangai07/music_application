<?php
session_start();
require_once 'config/db.php';

$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("SELECT user_id FROM user WHERE verification_token = ? AND is_verified = 0");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->bind_result($uid);
$stmt->fetch();
$stmt->close();

if ($uid) {
    $upd = $conn->prepare("UPDATE user SET is_verified = 1, verification_token = NULL WHERE user_id = ?");
    $upd->bind_param("i", $uid);
    $upd->execute();

    // Clear pending session
    unset($_SESSION['pending_email'], $_SESSION['pending_token'], $_SESSION['pending_name']);

    header("Location: login.php?verified=1");
    exit;
} else {
    header("Location: login.php?token_invalid=1");
    exit;
}
