<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/Validation.php';
requireAdmin();

$database = getDatabase();
$message = $_SESSION['admin_flash_message'] ?? '';
unset($_SESSION['admin_flash_message']);

$validPanels = ['overview', 'postings', 'applications', 'users', 'organizations', 'categories', 'reports', 'activity', 'messages', 'groups'];
$activePanel = $_GET['panel'] ?? 'overview';
if (!in_array($activePanel, $validPanels, true)) {
  $activePanel = 'overview';
}

/**
 * Records an admin/moderation action to activity_log so it shows up on the
 * Activity tab ("Monitor platform activity").
 */
function logActivity(PDO $database, string $action, string $details = ''): void
{
  $actorName = $_SESSION['user_name'] ?? 'Admin';
  $database->prepare('INSERT INTO activity_log (actor_name, action, details) VALUES (?, ?, ?)')
    ->execute([$actorName, $action, $details]);
}

/* ---------------- Admin actions ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $panel = $_POST['panel'] ?? 'overview';

  if ($action === 'toggle_posting_status') {
    $postingId = (int) ($_POST['posting_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? 'open';
    if (in_array($newStatus, ['open', 'closed'], true)) {
      $postingTitle = $database->prepare('SELECT title FROM postings WHERE id = ?');
      $postingTitle->execute([$postingId]);
      $database->prepare('UPDATE postings SET status = ? WHERE id = ?')->execute([$newStatus, $postingId]);
      logActivity($database, $newStatus === 'closed' ? 'Closed posting' : 'Reopened posting', $postingTitle->fetchColumn() ?: ('#' . $postingId));
      $_SESSION['admin_flash_message'] = 'Posting status updated.';
    }
  } elseif ($action === 'approve_posting') {
    $postingId = (int) ($_POST['posting_id'] ?? 0);
    $postingTitle = $database->prepare('SELECT title FROM postings WHERE id = ?');
    $postingTitle->execute([$postingId]);
    $title = $postingTitle->fetchColumn();
    if ($title !== false) {
      $database->prepare("UPDATE postings SET moderation_status = 'approved' WHERE id = ?")->execute([$postingId]);
      logActivity($database, 'Approved posting', $title);
      $_SESSION['admin_flash_message'] = 'Posting approved and now visible on the site.';
    }
  } elseif ($action === 'reject_posting') {
    $postingId = (int) ($_POST['posting_id'] ?? 0);
    $postingTitle = $database->prepare('SELECT title FROM postings WHERE id = ?');
    $postingTitle->execute([$postingId]);
    $title = $postingTitle->fetchColumn();
    if ($title !== false) {
      // Rejected postings are also closed so they can never accept
      // applications even if someone had the direct link.
      $database->prepare("UPDATE postings SET moderation_status = 'rejected', status = 'closed' WHERE id = ?")->execute([$postingId]);
      logActivity($database, 'Rejected posting', $title);
      $_SESSION['admin_flash_message'] = 'Posting rejected.';
    }
  } elseif ($action === 'delete_posting') {
    $postingId = (int) ($_POST['posting_id'] ?? 0);
    $postingTitle = $database->prepare('SELECT title FROM postings WHERE id = ?');
    $postingTitle->execute([$postingId]);
    $title = $postingTitle->fetchColumn();
    $database->prepare('DELETE FROM applications WHERE posting_id = ?')->execute([$postingId]);
    $database->prepare('DELETE FROM postings WHERE id = ?')->execute([$postingId]);
    logActivity($database, 'Removed posting', $title !== false ? $title : ('#' . $postingId));
    $_SESSION['admin_flash_message'] = 'Posting removed.';
  } elseif ($action === 'toggle_user_status') {
    $targetUserId = (int) ($_POST['user_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? 'active';
    if (in_array($newStatus, ['active', 'banned'], true)) {
      $targetUserName = $database->prepare("SELECT name FROM users WHERE id = ? AND role != 'admin'");
      $targetUserName->execute([$targetUserId]);
      $name = $targetUserName->fetchColumn();
      if ($name !== false) {
        $database->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'")->execute([$newStatus, $targetUserId]);
        logActivity($database, $newStatus === 'banned' ? 'Suspended account' : 'Reinstated account', $name);
      }
      $_SESSION['admin_flash_message'] = $newStatus === 'banned' ? 'Account suspended.' : 'Account reinstated.';
    }
  } elseif ($action === 'resolve_report') {
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $reportRow = $database->prepare("SELECT target_label FROM reports WHERE id = ? AND status = 'pending'");
    $reportRow->execute([$reportId]);
    $label = $reportRow->fetchColumn();
    if ($label !== false) {
      $database->prepare("UPDATE reports SET status = 'resolved', resolved_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$reportId]);
      logActivity($database, 'Resolved report', $label);
      $_SESSION['admin_flash_message'] = 'Report marked resolved.';
    }
  } elseif ($action === 'dismiss_report') {
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $reportRow = $database->prepare("SELECT target_label FROM reports WHERE id = ? AND status = 'pending'");
    $reportRow->execute([$reportId]);
    $label = $reportRow->fetchColumn();
    if ($label !== false) {
      $database->prepare("UPDATE reports SET status = 'dismissed', resolved_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$reportId]);
      logActivity($database, 'Dismissed report', $label);
      $_SESSION['admin_flash_message'] = 'Report dismissed.';
    }
  } elseif ($action === 'add_category') {
    $categoryName = trim($_POST['name'] ?? '');
    $categoryDescription = trim($_POST['description'] ?? '');
    if ($categoryName === '') {
      $_SESSION['admin_flash_message'] = 'Please enter a category name.';
    } else {
      $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $categoryName));
      $slug = trim($slug, '-') ?: 'category';
      $categoryId = $slug;
      $suffix = 2;
      $checkStatement = $database->prepare('SELECT COUNT(*) FROM categories WHERE id = ?');
      while (true) {
        $checkStatement->execute([$categoryId]);
        if (!$checkStatement->fetchColumn()) {
          break;
        }
        $categoryId = $slug . '-' . $suffix;
        $suffix++;
      }
      $database->prepare('INSERT INTO categories (id, name, description) VALUES (?, ?, ?)')->execute([$categoryId, $categoryName, $categoryDescription]);
      logActivity($database, 'Added category', $categoryName);
      $_SESSION['admin_flash_message'] = 'Category "' . $categoryName . '" added.';
    }
  } elseif ($action === 'update_category') {
    $categoryId = $_POST['category_id'] ?? '';
    $categoryName = trim($_POST['name'] ?? '');
    $categoryDescription = trim($_POST['description'] ?? '');
    if ($categoryId !== '' && $categoryName !== '') {
      $database->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?')->execute([$categoryName, $categoryDescription, $categoryId]);
      logActivity($database, 'Updated category', $categoryName);
      $_SESSION['admin_flash_message'] = 'Category updated.';
    }
  } elseif ($action === 'delete_category') {
    $categoryId = $_POST['category_id'] ?? '';
    $inUseStatement = $database->prepare('SELECT COUNT(*) FROM postings WHERE category_id = ?');
    $inUseStatement->execute([$categoryId]);
    if ((int) $inUseStatement->fetchColumn() > 0) {
      $_SESSION['admin_flash_message'] = 'That category still has postings under it — move or remove those first.';
    } else {
      $categoryNameStatement = $database->prepare('SELECT name FROM categories WHERE id = ?');
      $categoryNameStatement->execute([$categoryId]);
      $categoryName = $categoryNameStatement->fetchColumn();
      $database->prepare('DELETE FROM categories WHERE id = ?')->execute([$categoryId]);
      logActivity($database, 'Deleted category', $categoryName !== false ? $categoryName : $categoryId);
      $_SESSION['admin_flash_message'] = 'Category deleted.';
    }
  } elseif ($action === 'create_group') {
    $groupName = trim($_POST['name'] ?? '');
    $memberIds = array_filter(array_map('intval', $_POST['member_ids'] ?? []));
    if ($groupName === '') {
      $_SESSION['admin_flash_message'] = 'Please enter a group name.';
    } else {
      $database->prepare('INSERT INTO chat_groups (name, created_by) VALUES (?, ?)')->execute([$groupName, (int) $_SESSION['user_id']]);
      $newGroupId = (int) $database->lastInsertId();
      $memberStatement = $database->prepare('INSERT IGNORE INTO chat_group_members (group_id, user_id) VALUES (?, ?)');
      foreach ($memberIds as $memberId) {
        $memberStatement->execute([$newGroupId, $memberId]);
      }
      logActivity($database, 'Created group', $groupName);
      $_SESSION['admin_flash_message'] = 'Group "' . $groupName . '" created.';
      $_POST['group_id'] = $newGroupId;
    }
  } elseif ($action === 'delete_group') {
    $groupId = (int) ($_POST['group_id'] ?? 0);
    $groupNameStatement = $database->prepare('SELECT name FROM chat_groups WHERE id = ?');
    $groupNameStatement->execute([$groupId]);
    $groupName = $groupNameStatement->fetchColumn();
    if ($groupName !== false) {
      $database->prepare('DELETE FROM chat_group_messages WHERE group_id = ?')->execute([$groupId]);
      $database->prepare('DELETE FROM chat_group_members WHERE group_id = ?')->execute([$groupId]);
      $database->prepare('DELETE FROM chat_groups WHERE id = ?')->execute([$groupId]);
      logActivity($database, 'Deleted group', $groupName);
      $_SESSION['admin_flash_message'] = 'Group deleted.';
    }
    unset($_POST['group_id']);
  } elseif ($action === 'add_group_member') {
    $groupId = (int) ($_POST['group_id'] ?? 0);
    $userId = (int) ($_POST['user_id'] ?? 0);
    if ($groupId && $userId) {
      $database->prepare('INSERT IGNORE INTO chat_group_members (group_id, user_id) VALUES (?, ?)')->execute([$groupId, $userId]);
      $_SESSION['admin_flash_message'] = 'Member added.';
    }
  } elseif ($action === 'remove_group_member') {
    $groupId = (int) ($_POST['group_id'] ?? 0);
    $userId = (int) ($_POST['user_id'] ?? 0);
    if ($groupId && $userId) {
      $database->prepare('DELETE FROM chat_group_members WHERE group_id = ? AND user_id = ?')->execute([$groupId, $userId]);
      $_SESSION['admin_flash_message'] = 'Member removed.';
    }
  }

  $redirectUrl = 'admin.php?panel=' . urlencode($panel);
  if (!empty($_POST['group_id'])) {
    $redirectUrl .= '&group_id=' . (int) $_POST['group_id'];
  }
  header('Location: ' . $redirectUrl);
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
$statPendingPostingCount = (int) $database->query("SELECT COUNT(*) FROM postings WHERE moderation_status = 'pending'")->fetchColumn();
$statPendingReportCount = (int) $database->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

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
  $allUsers = $database->query("SELECT * FROM users WHERE role = 'aspirant' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}
if ($activePanel === 'organizations') {
  $allOrganizations = $database->query("
    SELECT users.*,
           (SELECT COUNT(*) FROM postings WHERE client_id = users.id) AS posting_count,
           (SELECT COUNT(*) FROM applications JOIN postings ON postings.id = applications.posting_id WHERE postings.client_id = users.id AND applications.status = 'accepted') AS accepted_count
    FROM users WHERE role = 'client' ORDER BY created_at DESC
  ")->fetchAll(PDO::FETCH_ASSOC);
}
if ($activePanel === 'categories') {
  $allCategories = $database->query("
    SELECT categories.*, (SELECT COUNT(*) FROM postings WHERE category_id = categories.id) AS posting_count
    FROM categories ORDER BY name
  ")->fetchAll(PDO::FETCH_ASSOC);
}
if ($activePanel === 'reports') {
  $allReports = $database->query("
    SELECT reports.*, users.name AS reporter_current_name
    FROM reports
    LEFT JOIN users ON users.id = reports.reporter_id
    ORDER BY (reports.status = 'pending') DESC, reports.created_at DESC
    LIMIT 200
  ")->fetchAll(PDO::FETCH_ASSOC);
}
if ($activePanel === 'activity') {
  $activityLog = $database->query('SELECT * FROM activity_log ORDER BY id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
}
if ($activePanel === 'groups') {
  $assignableUsers = $database->query("SELECT id, name, email, role FROM users WHERE role IN ('aspirant', 'client') ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

  $allGroups = $database->query("
    SELECT chat_groups.id, chat_groups.name,
           (SELECT COUNT(*) FROM chat_group_members WHERE group_id = chat_groups.id) AS member_count,
           (SELECT MAX(created_at) FROM chat_group_messages WHERE group_id = chat_groups.id) AS last_message_at
    FROM chat_groups
    ORDER BY last_message_at IS NULL, last_message_at DESC, chat_groups.created_at DESC
  ")->fetchAll(PDO::FETCH_ASSOC);

  $selectedGroupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
  $selectedGroup = null;
  $selectedGroupMembers = [];
  $nonMemberUsers = [];

  if ($selectedGroupId) {
    $selectedGroupStatement = $database->prepare('SELECT id, name FROM chat_groups WHERE id = ?');
    $selectedGroupStatement->execute([$selectedGroupId]);
    $selectedGroup = $selectedGroupStatement->fetch(PDO::FETCH_ASSOC) ?: null;
  }

  if ($selectedGroup) {
    $membersStatement = $database->prepare("
      SELECT users.id, users.name, users.email, users.role
      FROM chat_group_members
      JOIN users ON users.id = chat_group_members.user_id
      WHERE chat_group_members.group_id = ?
      ORDER BY users.name ASC
    ");
    $membersStatement->execute([$selectedGroupId]);
    $selectedGroupMembers = $membersStatement->fetchAll(PDO::FETCH_ASSOC);

    $memberIds = array_map(fn($m) => (int) $m['id'], $selectedGroupMembers);
    if ($memberIds) {
      $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
      $nonMemberStatement = $database->prepare("SELECT id, name, email, role FROM users WHERE role IN ('aspirant', 'client') AND id NOT IN ($placeholders) ORDER BY name ASC");
      $nonMemberStatement->execute($memberIds);
    } else {
      $nonMemberStatement = $database->query("SELECT id, name, email, role FROM users WHERE role IN ('aspirant', 'client') ORDER BY name ASC");
    }
    $nonMemberUsers = $nonMemberStatement->fetchAll(PDO::FETCH_ASSOC);
  }
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
      elseif ($activePanel === 'organizations'): include __DIR__ . '/admin/panel-organizations.php';
      elseif ($activePanel === 'categories'): include __DIR__ . '/admin/panel-categories.php';
      elseif ($activePanel === 'reports'): include __DIR__ . '/admin/panel-reports.php';
      elseif ($activePanel === 'activity'): include __DIR__ . '/admin/panel-activity.php';
      elseif ($activePanel === 'messages'): include __DIR__ . '/admin/panel-messages.php';
      elseif ($activePanel === 'groups'): include __DIR__ . '/admin/panel-groups.php';
      endif; ?>
    </div>
  </div>
</div>
<script src="admin/chat-widget.js?v=<?php echo file_exists(__DIR__ . '/admin/chat-widget.js') ? filemtime(__DIR__ . '/admin/chat-widget.js') : time(); ?>" defer></script>
<script src="group-chat-widget.js?v=<?php echo file_exists(__DIR__ . '/group-chat-widget.js') ? filemtime(__DIR__ . '/group-chat-widget.js') : time(); ?>" defer></script>
</body>
</html>