<?php
/**
 * Lets a logged-in user report a posting or another account as
 * inappropriate. Feeds the admin Reports panel (admin/panel-reports.php).
 * Call with POST: action=report_posting|report_user, target_id, reason.
 */
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/Validation.php';
header('Content-Type: application/json');

$database = getDatabase();

if (!isLoggedIn()) {
  http_response_code(401);
  echo json_encode(['success' => false, 'requiresLogin' => true, 'message' => 'Please log in to submit a report.']);
  exit;
}

$action = $_POST['action'] ?? '';
$reason = trim($_POST['reason'] ?? '');
$targetId = (int) ($_POST['target_id'] ?? 0);
$reporterId = (int) $_SESSION['user_id'];
$reporterName = $_SESSION['user_name'] ?? 'User';

if ($reason === '') {
  echo json_encode(['success' => false, 'message' => 'Please tell us what\'s wrong before submitting.']);
  exit;
}

if ($action === 'report_posting' && $targetId) {
  $statement = $database->prepare('SELECT title FROM postings WHERE id = ?');
  $statement->execute([$targetId]);
  $title = $statement->fetchColumn();
  if ($title === false) {
    echo json_encode(['success' => false, 'message' => 'That posting no longer exists.']);
    exit;
  }
  $database->prepare('INSERT INTO reports (reporter_id, reporter_name, target_type, target_id, target_label, reason) VALUES (?, ?, ?, ?, ?, ?)')
    ->execute([$reporterId, $reporterName, 'posting', $targetId, $title, substr($reason, 0, 1000)]);
  echo json_encode(['success' => true, 'message' => 'Thanks — we\'ll take a look at this posting.']);
  exit;
}

if ($action === 'report_user' && $targetId) {
  $statement = $database->prepare("SELECT name FROM users WHERE id = ? AND role != 'admin'");
  $statement->execute([$targetId]);
  $name = $statement->fetchColumn();
  if ($name === false) {
    echo json_encode(['success' => false, 'message' => 'That account no longer exists.']);
    exit;
  }
  $database->prepare('INSERT INTO reports (reporter_id, reporter_name, target_type, target_id, target_label, reason) VALUES (?, ?, ?, ?, ?, ?)')
    ->execute([$reporterId, $reporterName, 'user', $targetId, $name, substr($reason, 0, 1000)]);
  echo json_encode(['success' => true, 'message' => 'Thanks — we\'ll review this account.']);
  exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request.']);