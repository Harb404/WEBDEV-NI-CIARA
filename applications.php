<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/Validation.php';
header('Content-Type: application/json');

$database = getDatabase();
$action = $_POST['action'] ?? '';
$postingId = (int) ($_POST['posting_id'] ?? 0);

if (!isLoggedIn()) {
  http_response_code(401);
  echo json_encode(['success' => false, 'requiresLogin' => true, 'message' => 'Please log in as an Aspirant to apply.']);
  exit;
}

if (!isAspirant()) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Only Aspirant accounts can apply to postings.']);
  exit;
}

$aspirantId = (int) $_SESSION['user_id'];

function postingStatusHtml(PDO $database, int $postingId, int $aspirantId): array
{
  $statement = $database->prepare("
    SELECT postings.slots, postings.status AS posting_status,
           (SELECT COUNT(*) FROM applications WHERE posting_id = postings.id AND status = 'accepted') AS filled,
           (SELECT status FROM applications WHERE posting_id = postings.id AND aspirant_id = ?) AS my_status
    FROM postings WHERE postings.id = ?
  ");
  $statement->execute([$aspirantId, $postingId]);
  $row = $statement->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    return ['html' => '<p class="posting-gone">This posting no longer exists.</p>', 'status' => null];
  }

  $remaining = max(0, (int) $row['slots'] - (int) $row['filled']);
  $myStatus = $row['my_status'];

  ob_start();
  if ($myStatus) {
    echo '<div class="application-status application-status--' . htmlspecialchars($myStatus) . '">' . htmlspecialchars(ucfirst($myStatus)) . '</div>';
    if (in_array($myStatus, ['pending', 'reviewed'], true)) {
      echo '<form method="post" data-application-form><input type="hidden" name="action" value="withdraw"><input type="hidden" name="posting_id" value="' . $postingId . '"><button class="cart-button posting-withdraw-button" type="submit">Withdraw</button></form>';
    }
  } else {
    $isFull = $remaining < 1 || $row['posting_status'] !== 'open';
    echo '<form method="post" data-application-form><input type="hidden" name="action" value="apply"><input type="hidden" name="posting_id" value="' . $postingId . '"><button class="cart-button ' . ($isFull ? 'out-of-stock-button' : '') . '" type="submit" ' . ($isFull ? 'disabled' : '') . '>' . ($isFull ? 'Slots Full' : 'Apply Now') . '</button></form>';
  }
  return ['html' => ob_get_clean(), 'status' => $myStatus];
}

if ($action === 'apply' && $postingId) {
  $statement = $database->prepare("SELECT slots, status, (SELECT COUNT(*) FROM applications WHERE posting_id = postings.id AND status = 'accepted') AS filled FROM postings WHERE id = ?");
  $statement->execute([$postingId]);
  $posting = $statement->fetch(PDO::FETCH_ASSOC);

  if (!$posting || $posting['status'] !== 'open') {
    echo json_encode(['success' => false, 'message' => 'That posting is no longer available.']);
    exit;
  }
  if ((int) $posting['filled'] >= (int) $posting['slots']) {
    echo json_encode(['success' => false, 'message' => 'That posting is already full.']);
    exit;
  }

  $database->prepare('INSERT IGNORE INTO applications (aspirant_id, posting_id) VALUES (?, ?)')->execute([$aspirantId, $postingId]);
  $result = postingStatusHtml($database, $postingId, $aspirantId);
  echo json_encode(['success' => true, 'message' => 'Application sent — track it from My Applications.', 'postingHtml' => $result['html']]);
  exit;
}

if ($action === 'withdraw' && $postingId) {
  $database->prepare("DELETE FROM applications WHERE aspirant_id = ? AND posting_id = ? AND status IN ('pending', 'reviewed')")->execute([$aspirantId, $postingId]);
  $result = postingStatusHtml($database, $postingId, $aspirantId);
  echo json_encode(['success' => true, 'message' => 'Application withdrawn.', 'postingHtml' => $result['html']]);
  exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request.']);