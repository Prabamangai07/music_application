<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require_once 'config/db.php';

$uid      = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];
$error    = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $song_name   = trim($_POST['song_name']   ?? '');
    $song_format = trim($_POST['song_format'] ?? '');
    $singer_name = trim($_POST['singer_name'] ?? '');

    if (empty($song_name) || empty($singer_name) || empty($song_format)) {
        $error = "All fields are required.";
    } elseif (empty($_FILES['audio_file']['name']) || empty($_FILES['song_image']['name'])) {
        $error = "Both audio file and cover image are required.";
    } else {
        $audioExt   = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
        $imageExt   = strtolower(pathinfo($_FILES['song_image']['name'], PATHINFO_EXTENSION));
        $allowAudio = ['mp3','wav','ogg','flac','m4a'];
        $allowImage = ['jpg','jpeg','png','webp','gif'];

        if (!in_array($audioExt, $allowAudio)) {
            $error = "Invalid audio format. Allowed: " . implode(', ', $allowAudio);
        } elseif (!in_array($imageExt, $allowImage)) {
            $error = "Invalid image format. Allowed: " . implode(', ', $allowImage);
        } elseif ($_FILES['audio_file']['size'] > 20 * 1024 * 1024) {
            $error = "Audio file too large. Max 20MB.";
        } elseif ($_FILES['song_image']['size'] > 5 * 1024 * 1024) {
            $error = "Image too large. Max 5MB.";
        } else {
            $audioName = uniqid('audio_') . '.' . $audioExt;
            $imageName = uniqid('img_')   . '.' . $imageExt;
            $audioPath = "uploads/songs/$audioName";
            $imagePath = "uploads/images/$imageName";

            if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $audioPath) &&
                move_uploaded_file($_FILES['song_image']['tmp_name'], $imagePath)) {

                $stmt = $conn->prepare("CALL uploadsongs(?,?,?,?,?,?)");
                $stmt->bind_param("isssss", $uid, $song_name, $song_format, $singer_name, $imageName, $audioName);

                if ($stmt->execute()) {
                    // Free stored procedure result to prevent "Commands out of sync"
                    $stmt->close();
                    while ($conn->more_results()) { $conn->next_result(); }
                    $success = "Song \"" . htmlspecialchars($song_name) . "\" uploaded successfully! 🎵";
                } else {
                    $stmt->close();
                    @unlink($audioPath);
                    @unlink($imagePath);
                    $error = "Upload failed. Please try again.";
                }
            } else {
                $error = "File upload failed. Check folder permissions.";
            }
        }
    }
}

$navActive = $sidebarActive = 'upload';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload Song – Musical World</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="dashboard-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="page-title">⬆️ Upload <span>Song</span></div>
    <div class="card" style="max-width:560px;">
      <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= $success ?></div>
      <?php endif; ?>
      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label>Song Name *</label>
          <input type="text" name="song_name" placeholder="e.g. Blinding Lights" value="<?= htmlspecialchars($_POST['song_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Artist / Singer Name *</label>
          <input type="text" name="singer_name" placeholder="e.g. The Weeknd" value="<?= htmlspecialchars($_POST['singer_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Song Format *</label>
          <select name="song_format" required>
            <option value="">-- Select Format --</option>
            <?php foreach (['MP3','WAV','OGG','FLAC','M4A'] as $fmt): ?>
              <option value="<?= $fmt ?>" <?= (($_POST['song_format'] ?? '') === $fmt) ? 'selected' : '' ?>><?= $fmt ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Cover Image * (JPG, PNG, WEBP – max 5MB)</label>
          <input type="file" name="song_image" accept="image/*" required>
        </div>
        <div class="form-group">
          <label>Audio File * (MP3, WAV, OGG, FLAC, M4A – max 20MB)</label>
          <input type="file" name="audio_file" accept="audio/*" required>
        </div>
        <div id="previewWrap" style="display:none;margin-bottom:1rem;">
          <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:0.4rem;">Audio Preview:</p>
          <audio id="audioPreview" controls style="width:100%;"></audio>
        </div>
        <button type="submit" class="btn btn-primary btn-block">🎵 Upload Song</button>
      </form>
    </div>
  </main>
</div>
<?php include 'includes/player.php'; ?>
<script>
document.querySelector('input[name="audio_file"]').addEventListener('change', function () {
  if (this.files[0]) {
    document.getElementById('audioPreview').src = URL.createObjectURL(this.files[0]);
    document.getElementById('previewWrap').style.display = 'block';
  }
});
</script>
</body>
</html>
