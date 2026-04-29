<?php
$sidebarActive = $sidebarActive ?? '';
$sidebarUser   = $username ?? ($_SESSION['username'] ?? '');
$sidebarEmail  = $_SESSION['user_email'] ?? '';
?>
<aside class="sidebar">
  <div class="user-info">
    <div class="avatar"><?= strtoupper(substr($sidebarUser, 0, 1)) ?></div>
    <div class="name"><?= htmlspecialchars($sidebarUser) ?></div>
    <div class="email"><?= htmlspecialchars($sidebarEmail) ?></div>
  </div>
  <nav class="sidebar-nav">
    <a href="dashboard.php" <?= $sidebarActive==='dashboard' ? 'class="active"':'' ?>>🏠 Dashboard</a>
    <a href="upload.php"    <?= $sidebarActive==='upload'    ? 'class="active"':'' ?>>⬆️ Upload Song</a>
    <a href="favorites.php" <?= $sidebarActive==='favorites' ? 'class="active"':'' ?>>❤️ Favorites</a>
    <a href="my_songs.php"  <?= $sidebarActive==='my_songs'  ? 'class="active"':'' ?>>🎶 My Songs</a>
    <a href="playlist.php"  <?= $sidebarActive==='playlist'  ? 'class="active"':'' ?>>📋 Playlists</a>
    <a href="library.php"   <?= $sidebarActive==='library'   ? 'class="active"':'' ?>>📚 Library</a>
    <a href="profile.php"   <?= $sidebarActive==='profile'   ? 'class="active"':'' ?>>👤 Profile</a>
    <a href="logout.php">🚪 Logout</a>
  </nav>
</aside>
