<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/Validation.php';
header('Content-Type: application/json');

$database = getDatabase();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!isLoggedIn()) {
  echo json_encode(['success' => true, 'notifications' => [], 'unreadCount' => 0]);
  exit;
}

$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'aspirant';

/**
 * Aspirants are notified when a client decides on one of their applications
 * (accepted / declined / reviewed) — tracked via applications.status_seen.
 * Clients are notified when a new application comes in for one of their
 * postings — tracked via applications.seen_by_client.
 */
function buildNotifications(PDO $database, int $userId, string $role): array
{
  if ($role === 'client') {
    $statement = $database->prepare("
      SELECT applications.seen_by_client, applications.created_at,
             users.name AS aspirant_name, postings.title AS posting_title
      FROM applications
      JOIN postings ON postings.id = applications.posting_id
      JOIN users ON users.id = applications.aspirant_id
      WHERE postings.client_id = ?
      ORDER BY applications.created_at DESC
      LIMIT 20
    ");
    $statement->execute([$userId]);
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

    $notifications = array_map(fn($row) => [
      'text' => '<strong>' . htmlspecialchars($row['aspirant_name']) . '</strong> applied to "' . htmlspecialchars($row['posting_title']) . '"',
      'time' => date('M j, g:i A', strtotime($row['created_at'])),
      'unread' => !(bool) $row['seen_by_client'],
    ], $rows);

    $unreadStatement = $database->prepare("
      SELECT COUNT(*) FROM applications
      JOIN postings ON postings.id = applications.posting_id
      WHERE postings.client_id = ? AND applications.seen_by_client = 0
    ");
    $unreadStatement->execute([$userId]);
    return [$notifications, (int) $unreadStatement->fetchColumn()];
  }

  $statement = $database->prepare("
    SELECT status, status_seen, decided_at, created_at, postings.title AS posting_title
    FROM applications
    JOIN postings ON postings.id = applications.posting_id
    WHERE applications.aspirant_id = ? AND applications.status != 'pending'
    ORDER BY COALESCE(applications.decided_at, applications.created_at) DESC
    LIMIT 20
  ");
  $statement->execute([$userId]);
  $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

  $notifications = array_map(fn($row) => [
    'text' => 'Your application to "' . htmlspecialchars($row['posting_title']) . '" was ' . htmlspecialchars($row['status']) . '.',
    'time' => date('M j, g:i A', strtotime($row['decided_at'] ?? $row['created_at'])),
    'unread' => !(bool) $row['status_seen'],
  ], $rows);

  $unreadStatement = $database->prepare("SELECT COUNT(*) FROM applications WHERE aspirant_id = ? AND status != 'pending' AND status_seen = 0");
  $unreadStatement->execute([$userId]);
  return [$notifications, (int) $unreadStatement->fetchColumn()];
}

if ($action === 'poll') {
  [$notifications, $unreadCount] = buildNotifications($database, $userId, $role);
  echo json_encode(['success' => true, 'notifications' => $notifications, 'unreadCount' => $unreadCount]);
  exit;
}

if ($action === 'mark_read') {
  if ($role === 'client') {
    $database->prepare("
      UPDATE applications
      JOIN postings ON postings.id = applications.posting_id
      SET applications.seen_by_client = 1
      WHERE postings.client_id = ?
    ")->execute([$userId]);
  } else {
    $database->prepare('UPDATE applications SET status_seen = 1 WHERE aspirant_id = ?')->execute([$userId]);
  }
  [$notifications, $unreadCount] = buildNotifications($database, $userId, $role);
  echo json_encode(['success' => true, 'notifications' => $notifications, 'unreadCount' => $unreadCount]);
  exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
