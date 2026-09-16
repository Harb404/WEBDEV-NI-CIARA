-- ComMEETtee database
-- Generated to match the schema auto-created by database.php
-- Import this after dropping/creating a fresh `commeettee` database.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `commeettee` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `commeettee`;

-- ---------------- users ----------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` TEXT NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'aspirant',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `avatar` VARCHAR(255) NOT NULL DEFAULT '',
  `org_name` VARCHAR(255) NOT NULL DEFAULT '',
  `program` VARCHAR(255) NOT NULL DEFAULT '',
  `year_level` VARCHAR(50) NOT NULL DEFAULT '',
  `skills` VARCHAR(500) NOT NULL DEFAULT '',
  `availability` VARCHAR(255) NOT NULL DEFAULT '',
  `bio` VARCHAR(1000) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin account: admin@commeettee.local / admin123
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Administrator', 'admin@commeettee.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ---------------- categories ----------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` VARCHAR(50) PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(500) NOT NULL DEFAULT '',
  `image` VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

INSERT INTO `categories` (`id`, `name`, `description`, `image`) VALUES
('documentation', 'Documentation', 'Captures every milestone in photos, videos, and files, so nothing about the event goes unrecorded.', 'Pictures/committee-documentation.jpg'),
('technicals', 'Technicals', 'Handles the technical equipment, setup, and operations needed to ensure smooth event execution.', 'Pictures/committee-technicals.jpg'),
('decorations', 'Decorations', 'Shapes the look and feel of the venue, from overall layout down to the smallest visual detail.', 'Pictures/committee-decorations.jpg'),
('logistics', 'Logistics', 'Keeps people, supplies, and schedules moving so every event runs on time and on plan.', 'Pictures/committee-logistics.jpg');

-- ---------------- postings ----------------
DROP TABLE IF EXISTS `postings`;
CREATE TABLE `postings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `category_id` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` VARCHAR(2000) NOT NULL DEFAULT '',
  `skills_needed` VARCHAR(500) NOT NULL DEFAULT '',
  `slots` INT UNSIGNED NOT NULL DEFAULT 1,
  `status` VARCHAR(20) NOT NULL DEFAULT 'open',
  `moderation_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `image` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB;

-- ---------------- applications ----------------
DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `aspirant_id` INT UNSIGNED NOT NULL,
  `posting_id` INT UNSIGNED NOT NULL,
  `note` VARCHAR(1000) NOT NULL DEFAULT '',
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `status_seen` TINYINT(1) NOT NULL DEFAULT 1,
  `seen_by_client` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `decided_at` DATETIME NULL DEFAULT NULL,
  UNIQUE KEY `unique_application` (`aspirant_id`, `posting_id`),
  FOREIGN KEY (`aspirant_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`posting_id`) REFERENCES `postings`(`id`)
) ENGINE=InnoDB;

-- ---------------- ratings ----------------
DROP TABLE IF EXISTS `ratings`;
CREATE TABLE `ratings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `aspirant_id` INT UNSIGNED NOT NULL,
  `client_id` INT UNSIGNED NOT NULL,
  `application_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `comment` VARCHAR(500) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_rating` (`application_id`),
  FOREIGN KEY (`aspirant_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`client_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`application_id`) REFERENCES `applications`(`id`)
) ENGINE=InnoDB;

-- ---------------- chat ----------------
DROP TABLE IF EXISTS `chat_threads`;
CREATE TABLE `chat_threads` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `guest_token` VARCHAR(64) NULL,
  `guest_name` VARCHAR(255) NULL,
  `last_message_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user` (`user_id`),
  UNIQUE KEY `unique_guest_token` (`guest_token`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE `chat_messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `thread_id` INT UNSIGNED NOT NULL,
  `sender` VARCHAR(10) NOT NULL,
  `message` VARCHAR(2000) NOT NULL,
  `read_by_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `read_by_client` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`thread_id`) REFERENCES `chat_threads`(`id`)
) ENGINE=InnoDB;

-- ---------------- reports ----------------
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reporter_id` INT UNSIGNED NULL,
  `reporter_name` VARCHAR(255) NOT NULL DEFAULT 'Guest',
  `target_type` VARCHAR(20) NOT NULL,
  `target_id` INT UNSIGNED NOT NULL,
  `target_label` VARCHAR(255) NOT NULL DEFAULT '',
  `reason` VARCHAR(1000) NOT NULL DEFAULT '',
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` DATETIME NULL DEFAULT NULL,
  FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- ---------------- activity_log ----------------
DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `actor_name` VARCHAR(255) NOT NULL DEFAULT 'System',
  `action` VARCHAR(255) NOT NULL,
  `details` VARCHAR(500) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;