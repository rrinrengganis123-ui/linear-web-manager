CREATE DATABASE IF NOT EXISTS `linear_web_manager`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `linear_web_manager`;

-- STACK
CREATE TABLE IF NOT EXISTS `stack_sessions` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL DEFAULT 'Session Baru',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `stack_items` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT UNSIGNED NOT NULL,
  `value`      VARCHAR(255) NOT NULL,
  `position`   INT UNSIGNED NOT NULL,
  `pushed_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `stack_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `stack_log` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT UNSIGNED NOT NULL,
  `action`     ENUM('PUSH','POP','PEEK','CLEAR') NOT NULL,
  `value`      VARCHAR(255) NULL,
  `acted_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `stack_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- QUEUE
CREATE TABLE IF NOT EXISTS `queue_sessions` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL DEFAULT 'Queue Baru',
  `max_size`   INT UNSIGNED NOT NULL DEFAULT 10,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `queue_items` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id`  INT UNSIGNED NOT NULL,
  `value`       VARCHAR(255) NOT NULL,
  `priority`    TINYINT NOT NULL DEFAULT 0,
  `status`      ENUM('waiting','processing','done') NOT NULL DEFAULT 'waiting',
  `enqueued_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dequeued_at` TIMESTAMP NULL,
  FOREIGN KEY (`session_id`) REFERENCES `queue_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `queue_log` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT UNSIGNED NOT NULL,
  `action`     ENUM('ENQUEUE','DEQUEUE','PEEK','CLEAR') NOT NULL,
  `value`      VARCHAR(255) NULL,
  `acted_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `queue_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- LINKED LIST (PLAYLIST)
CREATE TABLE IF NOT EXISTS `playlist_sessions` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL DEFAULT 'Playlist Baru',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `playlist_nodes` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT UNSIGNED NOT NULL,
  `title`      VARCHAR(150) NOT NULL,
  `artist`     VARCHAR(100) NOT NULL DEFAULT 'Unknown',
  `duration`   VARCHAR(10)  NOT NULL DEFAULT '0:00',
  `position`   INT UNSIGNED NOT NULL,
  `prev_id`    INT UNSIGNED NULL,
  `next_id`    INT UNSIGNED NULL,
  `added_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `playlist_sessions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`prev_id`)    REFERENCES `playlist_nodes`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`next_id`)    REFERENCES `playlist_nodes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `playlist_log` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT UNSIGNED NOT NULL,
  `action`     ENUM('INSERT_HEAD','INSERT_TAIL','INSERT_AT','DELETE_HEAD','DELETE_TAIL','DELETE_AT','TRAVERSE') NOT NULL,
  `value`      VARCHAR(255) NULL,
  `acted_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`session_id`) REFERENCES `playlist_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- SEED DATA
INSERT INTO `stack_sessions` (`name`) VALUES ('Demo Undo/Redo');
INSERT INTO `stack_items` (`session_id`,`value`,`position`) VALUES
  (1,'Buka Dokumen',0),(1,'Tulis Paragraf 1',1),(1,'Bold Teks',2),(1,'Ubah Font',3);
INSERT INTO `stack_log` (`session_id`,`action`,`value`) VALUES
  (1,'PUSH','Buka Dokumen'),(1,'PUSH','Tulis Paragraf 1'),(1,'PUSH','Bold Teks'),(1,'PUSH','Ubah Font');

INSERT INTO `queue_sessions` (`name`,`max_size`) VALUES ('Loket Tiket A',5);
INSERT INTO `queue_items` (`session_id`,`value`,`priority`,`status`) VALUES
  (1,'Tiket #001 - Budi Santoso',0,'processing'),
  (1,'Tiket #002 - Siti Rahma',0,'waiting'),
  (1,'Tiket #003 - Ahmad Fauzi',1,'waiting');
INSERT INTO `queue_log` (`session_id`,`action`,`value`) VALUES
  (1,'ENQUEUE','Tiket #001'),(1,'ENQUEUE','Tiket #002'),(1,'ENQUEUE','Tiket #003');

INSERT INTO `playlist_sessions` (`name`) VALUES ('My Playlist');
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO `playlist_nodes` (`session_id`,`title`,`artist`,`duration`,`position`,`prev_id`,`next_id`) VALUES
  (1,'Bohemian Rhapsody','Queen','5:55',0,NULL,2),
  (1,'Blinding Lights','The Weeknd','3:20',1,1,3),
  (1,'Shape of You','Ed Sheeran','3:53',2,2,NULL);

SET FOREIGN_KEY_CHECKS = 1;
INSERT INTO `playlist_log` (`session_id`,`action`,`value`) VALUES
  (1,'INSERT_TAIL','Bohemian Rhapsody'),(1,'INSERT_TAIL','Blinding Lights'),(1,'INSERT_TAIL','Shape of You');