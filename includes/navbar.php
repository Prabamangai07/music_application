<?php $navActive = $navActive ?? ''; ?>
<nav class="navbar">
  <div class="brand">🎵 Musical <span>World</span></div>
  <div class="nav-links">
    <a href="dashboard.php" <?= $navActive==='dashboard' ? 'class="active"':'' ?>>🏠 Home</a>
    <a href="upload.php"    <?= $navActive==='upload'    ? 'class="active"':'' ?>>⬆️ Upload</a>
    <a href="library.php"   <?= $navActive==='library'   ? 'class="active"':'' ?>>📚 Library</a>
    <a href="playlist.php"  <?= $navActive==='playlist'  ? 'class="active"':'' ?>>📋 Playlists</a>
    <a href="profile.php"   <?= $navActive==='profile'   ? 'class="active"':'' ?>>👤 Profile</a>
    <a href="logout.php">🚪 Logout</a>
  </div>
  <div class="nav-now-playing">
    <span id="navNowPlaying"></span>
  </div>
</nav>
