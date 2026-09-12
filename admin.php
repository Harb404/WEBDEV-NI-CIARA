<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/Validation.php';
requireAdmin();

$database = getDatabase();
$message = $_SESSION['admin_flash_message'] ?? '';
unset($_SESSION['admin_flash_message']);

$validPanels = ['overview', 'postings', 'applications', 'users', 'messages'];
$activePanel = $_GET['panel'] ?? 'overview';
if (!in_array($activePanel, $validPanels, true)) {
  $activePanel = 'overview';
}

/* ---------------- Admin actions ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $panel = $_POST['panel'] ?? 'overview';

  if ($action === 'toggle_posting_status') {
    $postingId = (int) ($_POST['posting_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? 'open';
    if (in_array($newStatus, ['open', 'closed'], true)) {
      $database->prepare('UPDATE postings SET status = ? WHERE id = ?')->execute([$newStatus, $postingId]);
      $_SESSION['admin_flash_message'] = 'Posting status updated.';
    }
  } elseif ($action === 'delete_posting') {
    $postingId = (int) ($_POST['posting_id'] ?? 0);
    $database->prepare('DELETE FROM applications WHERE posting_id = ?')->execute([$postingId]);
    $database->prepare('DELETE FROM postings WHERE id = ?')->execute([$postingId]);
    $_SESSION['admin_flash_message'] = 'Posting removed.';
  } elseif ($action === 'toggle_user_status') {
    $targetUserId = (int) ($_POST['user_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? 'active';
    if (in_array($newStatus, ['active', 'banned'], true)) {
      $database->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'")->execute([$newStatus, $targetUserId]);
      $_SESSION['admin_flash_message'] = $newStatus === 'banned' ? 'Account suspended.' : 'Account reinstated.';
    }
  }

  header('Location: admin.php?panel=' . urlencode($panel));
  exit;
}

/* ---------------- Dashboard stats ---------------- */
$statUserCount = (int) $database->query("SELECT COUNT(*) FROM users WHERE role = 'aspirant'")->fetchColumn();
$statClientCount = (int) $database->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$statPostingCount = (int) $database->query('SELECT COUNT(*) FROM postings')->fetchColumn();
$statOpenPostingCount = (int) $database->query("SELECT COUNT(*) FROM postings WHERE status = 'open'")->fetchColumn();
$statApplicationCount = (int) $database->query('SELECT COUNT(*) FROM applications')->fetchColumn();
$statAcceptedCount = (int) $database->query("SELECT COUNT(*) FROM applications WHERE status = 'accepted'")->fetchColumn();
$statPendingCount = (int) $database->query("SELECT COUNT(*) FROM applications WHERE status IN ('pending','reviewed')")->fetchColumn();

$monthlyApplications = array_fill(1, 12, 0);
$monthlyStatement = $database->prepare('SELECT MONTH(created_at) AS m, COUNT(*) AS total FROM applications WHERE YEAR(created_at) = ? GROUP BY MONTH(created_at)');
$monthlyStatement->execute([date('Y')]);
foreach ($monthlyStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
  $monthlyApplications[(int) $row['m']] = (int) $row['total'];
}
$monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$maxMonthlyApplications = max(1, max($monthlyApplications));

$topCategories = $database->query("
  SELECT categories.name, COUNT(applications.id) AS total
  FROM applications
  JOIN postings ON postings.id = applications.posting_id
  JOIN categories ON categories.id = postings.category_id
  GROUP BY categories.id ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

if ($activePanel === 'postings') {
  $allPostings = $database->query("
    SELECT postings.*, users.name AS client_name, users.org_name AS client_org, categories.name AS category_name,
           (SELECT COUNT(*) FROM applications WHERE posting_id = postings.id) AS application_count
    FROM postings JOIN users ON users.id = postings.client_id JOIN categories ON categories.id = postings.category_id
    ORDER BY postings.created_at DESC
  ")->fetchAll(PDO::FETCH_ASSOC);
}
if ($activePanel === 'applications') {
  $allApplications = $database->query("
    SELECT applications.*, users.name AS aspirant_name, postings.title AS posting_title, clients.name AS client_name
    FROM applications
    JOIN users ON users.id = applications.aspirant_id
    JOIN postings ON postings.id = applications.posting_id
    JOIN users AS clients ON clients.id = postings.client_id
    ORDER BY applications.created_at DESC LIMIT 100
  ")->fetchAll(PDO::FETCH_ASSOC);
}
if ($activePanel === 'users') {
  $allUsers = $database->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — ComMEETtee</title>
<link rel="stylesheet" href="admin-style.css?v=<?php echo file_exists(__DIR__ . '/admin-style.css') ? filemtime(__DIR__ . '/admin-style.css') : time(); ?>">
</head>
<body class="dash-body">
<div class="dash-shell">
<?php include __DIR__ . '/admin/sidebar.php'; ?>
  <div class="dash-main">
<?php include __DIR__ . '/admin/topbar.php'; ?>
    <div class="dash-content">
      <?php if ($message): ?><div class="dash-message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
      <?php if ($activePanel === 'overview'): include __DIR__ . '/admin/panel-overview.php';
      elseif ($activePanel === 'postings'): include __DIR__ . '/admin/panel-postings.php';
      elseif ($activePanel === 'applications'): include __DIR__ . '/admin/panel-applications.php';
      elseif ($activePanel === 'users'): include __DIR__ . '/admin/panel-users.php';
      elseif ($activePanel === 'messages'): include __DIR__ . '/admin/panel-messages.php';
      endif; ?>
    </div>
  </div>
</div>
<script src="admin/chat-widget.js?v=<?php echo file_exists(__DIR__ . '/admin/chat-widget.js') ? filemtime(__DIR__ . '/admin/chat-widget.js') : time(); ?>" defer></script>
</body>
</html>