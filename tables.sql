-- Musical World Database Setup
CREATE DATABASE IF NOT EXISTS musical_world;
USE musical_world;

-- Users table
CREATE TABLE IF NOT EXISTS `user` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `is_verified` TINYINT(1) DEFAULT 0,
  `verification_token` VARCHAR(255) DEFAULT NULL,
  `contributions` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin table
CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
);

-- Upload albums table
CREATE TABLE IF NOT EXISTS `upload_albums` (
  `album_id` INT AUTO_INCREMENT PRIMARY KEY,
  `singer_id` INT NOT NULL,
  `song_name` VARCHAR(255) NOT NULL,
  `song_format` VARCHAR(50) NOT NULL,
  `singer_name` VARCHAR(255) NOT NULL,
  `song_image` VARCHAR(255) NOT NULL,
  `audio_file` VARCHAR(255) NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`singer_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE
);

-- Favorites table
CREATE TABLE IF NOT EXISTS `favorites` (
  `fav_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `album_id` INT NOT NULL,
  UNIQUE KEY `unique_fav` (`user_id`, `album_id`),
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`album_id`) REFERENCES `upload_albums`(`album_id`) ON DELETE CASCADE
);

-- Trigger: increment contributions on song upload
DROP TRIGGER IF EXISTS `IncrementCount`;

CREATE TRIGGER `IncrementCount`
AFTER INSERT ON `upload_albums`
FOR EACH ROW
UPDATE user
SET user.contributions = user.contributions + 1
WHERE NEW.singer_id = user.user_id;

-- Stored Procedure: upload songs
DROP PROCEDURE IF EXISTS `uploadsongs`;

CREATE PROCEDURE `uploadsongs`(
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
END;

-- Playlists table
CREATE TABLE IF NOT EXISTS `playlists` (
  `playlist_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE
);

-- Playlist songs table
CREATE TABLE IF NOT EXISTS `playlist_songs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `playlist_id` INT NOT NULL,
  `album_id` INT NOT NULL,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_playlist_song` (`playlist_id`, `album_id`),
  FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`playlist_id`) ON DELETE CASCADE,
  FOREIGN KEY (`album_id`) REFERENCES `upload_albums`(`album_id`) ON DELETE CASCADE
);

-- Default admin (password: s123)
INSERT IGNORE INTO `admin` (`email`, `password`) VALUES
('admin@gmail.com', '$2y$10$wSCEBCMFCBGCHCICJCKCLuOeP1234567890abcdefghijklmnopqrs');
