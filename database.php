<?php
/**
 * ComMEETtee — database bootstrap.
 * Creates the schema on first run (mirrors the "auto-migrate" pattern from
 * the Masalihit Luxe build: every ALTER TABLE is guarded by an
 * information_schema check, so this file is safe to run on every request).
 */

function getDatabase(): PDO
{
  static $database;
  if ($database instanceof PDO) {
    return $database;
  }

  $host = getenv('DB_HOST') ?: '127.0.0.1';
  $username = getenv('DB_USER') ?: 'root';
  $password = getenv('DB_PASS') ?: '';
  $databaseName = 'commeettee';

  $server = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
  $server->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $server->exec("CREATE DATABASE IF NOT EXISTS `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  $database = new PDO("mysql:host=$host;dbname=$databaseName;charset=utf8mb4", $username, $password);
  $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  /* ---------------- users ----------------
     role: 'aspirant' | 'client' | 'admin'
     Aspirant-only fields (program, year, skills, bio, portfolio) sit on the
     same row rather than a second table — keeps every profile read to one
     query, and the fields are simply blank for client/admin accounts. */
  $database->exec("CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'aspirant',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    avatar VARCHAR(255) NOT NULL DEFAULT '',
    org_name VARCHAR(255) NOT NULL DEFAULT '',
    program VARCHAR(255) NOT NULL DEFAULT '',
    year_level VARCHAR(50) NOT NULL DEFAULT '',
    skills VARCHAR(500) NOT NULL DEFAULT '',
    availability VARCHAR(255) NOT NULL DEFAULT '',
    bio VARCHAR(1000) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB");

  $adminStatement = $database->prepare('SELECT id FROM users WHERE email = ?');
  $adminStatement->execute(['admin@commeettee.local']);
  if (!$adminStatement->fetchColumn()) {
    $adminStatement = $database->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
    $adminStatement->execute(['Administrator', 'admin@commeettee.local', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
  }

  /* ---------------- categories ----------------
     Seeded from the brand guide's committee types. Clients pick one of
     these when posting an opening. */
  $database->exec("CREATE TABLE IF NOT EXISTS categories (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    image VARCHAR(255) NOT NULL DEFAULT ''
  ) ENGINE=InnoDB");
  if ((int) $database->query('SELECT COUNT(*) FROM categories')->fetchColumn() === 0) {
    $seedCategories = [
      ['documentation', 'Documentation', 'Captures every milestone in photos, videos, and files, so nothing about the event goes unrecorded.', 'Pictures/committee-documentation.jpg'],
      ['technicals', 'Technicals', 'Handles the technical equipment, setup, and operations needed to ensure smooth event execution.', 'Pictures/committee-technicals.jpg'],
      ['decorations', 'Decorations', 'Shapes the look and feel of the venue, from overall layout down to the smallest visual detail.', 'Pictures/committee-decorations.jpg'],
      ['logistics', 'Logistics', 'Keeps people, supplies, and schedules moving so every event runs on time and on plan.', 'Pictures/committee-logistics.jpg'],
    ];
    $categoryStatement = $database->prepare('INSERT INTO categories (id, name, description, image) VALUES (?, ?, ?, ?)');
    foreach ($seedCategories as $category) {
      $categoryStatement->execute($category);
    }
  }

  /* ---------------- postings ----------------
     A committee opening posted by a client. */
  $database->exec("CREATE TABLE IF NOT EXISTS postings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    category_id VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description VARCHAR(2000) NOT NULL DEFAULT '',
    skills_needed VARCHAR(500) NOT NULL DEFAULT '',
    slots INT UNSIGNED NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    image VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
  ) ENGINE=InnoDB");

  // Admin moderation: new postings need approval before they're visible on
  // the public site. Existing postings are backfilled as 'approved' so
  // nothing that was already live suddenly disappears when this ships.
  $hasModerationStatus = (int) $database->query("
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'postings' AND column_name = 'moderation_status'
  ")->fetchColumn();
  if (!$hasModerationStatus) {
    $database->exec("ALTER TABLE postings ADD COLUMN moderation_status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER status");
    $database->exec("UPDATE postings SET moderation_status = 'approved'");
  }

  /* ---------------- applications ----------------
     status: pending | reviewed | accepted | declined | withdrawn */
  $database->exec("CREATE TABLE IF NOT EXISTS applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aspirant_id INT UNSIGNED NOT NULL,
    posting_id INT UNSIGNED NOT NULL,
    note VARCHAR(1000) NOT NULL DEFAULT '',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    status_seen TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    decided_at DATETIME NULL DEFAULT NULL,
    UNIQUE KEY unique_application (aspirant_id, posting_id),
    FOREIGN KEY (aspirant_id) REFERENCES users(id),
    FOREIGN KEY (posting_id) REFERENCES postings(id)
  ) ENGINE=InnoDB");

  $hasSeenByClient = (int) $database->query("
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'applications' AND column_name = 'seen_by_client'
  ")->fetchColumn();
  if (!$hasSeenByClient) {
    $database->exec("ALTER TABLE applications ADD COLUMN seen_by_client TINYINT(1) NOT NULL DEFAULT 0 AFTER status_seen");
    $database->exec("UPDATE applications SET seen_by_client = 1"); // don't flood clients with notifications for applications that already existed
  }

  /* ---------------- ratings ----------------
     Left by a client for an aspirant once a term/role has ended (mirrors
     the star ratings shown on the profile-preview mock-up). */
  $database->exec("CREATE TABLE IF NOT EXISTS ratings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aspirant_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    application_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment VARCHAR(500) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rating (application_id),
    FOREIGN KEY (aspirant_id) REFERENCES users(id),
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (application_id) REFERENCES applications(id)
  ) ENGINE=InnoDB");

  /* ---------------- chat ---------------- */
  $database->exec("CREATE TABLE IF NOT EXISTS chat_threads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    guest_token VARCHAR(64) NULL,
    guest_name VARCHAR(255) NULL,
    last_message_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id),
    UNIQUE KEY unique_guest_token (guest_token),
    FOREIGN KEY (user_id) REFERENCES users(id)
  ) ENGINE=InnoDB");

  $database->exec("CREATE TABLE IF NOT EXISTS chat_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id INT UNSIGNED NOT NULL,
    sender VARCHAR(10) NOT NULL,
    message VARCHAR(2000) NOT NULL,
    read_by_admin TINYINT(1) NOT NULL DEFAULT 0,
    read_by_client TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES chat_threads(id)
  ) ENGINE=InnoDB");

  /* ---------------- reports ----------------
     A user reporting a posting or an account as inappropriate. target_id
     points at postings.id or users.id depending on target_type; the label
     is a snapshot so the report still reads clearly even if the reported
     posting/account is later deleted. */
  $database->exec("CREATE TABLE IF NOT EXISTS reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED NULL,
    reporter_name VARCHAR(255) NOT NULL DEFAULT 'Guest',
    target_type VARCHAR(20) NOT NULL,
    target_id INT UNSIGNED NOT NULL,
    target_label VARCHAR(255) NOT NULL DEFAULT '',
    reason VARCHAR(1000) NOT NULL DEFAULT '',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL DEFAULT NULL,
    FOREIGN KEY (reporter_id) REFERENCES users(id)
  ) ENGINE=InnoDB");

  /* ---------------- activity_log ----------------
     A simple audit trail of moderation and admin actions, shown on the
     Activity tab so the admin can monitor what's happened on the platform. */
  $database->exec("CREATE TABLE IF NOT EXISTS activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_name VARCHAR(255) NOT NULL DEFAULT 'System',
    action VARCHAR(255) NOT NULL,
    details VARCHAR(500) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB");

  /* ---------------- group chat ----------------
     Admin creates a named group and adds any registered user (aspirant,
     client, or admin) to it; members see and post in the group from their
     own account (groups.php), admin manages it from the Groups panel. */
  $database->exec("CREATE TABLE IF NOT EXISTS chat_groups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
  ) ENGINE=InnoDB");

  $database->exec("CREATE TABLE IF NOT EXISTS chat_group_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    last_read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_group_member (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES chat_groups(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
  ) ENGINE=InnoDB");

  $database->exec("CREATE TABLE IF NOT EXISTS chat_group_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    message VARCHAR(2000) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES chat_groups(id),
    FOREIGN KEY (sender_id) REFERENCES users(id)
  ) ENGINE=InnoDB");

  return $database;
}