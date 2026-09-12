<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/Validation.php';
requireLogin();

$database = getDatabase();
$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'aspirant';

if (isAdmin()) {
  header('Location: admin.php');
  exit;
}

$message = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);
$error = '';

/* ============ CLIENT: create posting / manage applicants ============ */
if ($role === 'client' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'create_posting') {
    $title = trim($_POST['title'] ?? '');
    $categoryId = $_POST['category_id'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $skillsNeeded = trim($_POST['skills_needed'] ?? '');
    $slots = max(1, (int) ($_POST['slots'] ?? 1));

    if ($title === '') {
      $error = 'Please give the posting a title.';
    } else {
      $statement = $database->prepare('INSERT INTO postings (client_id, category_id, title, description, skills_needed, slots) VALUES (?, ?, ?, ?, ?, ?)');
      $statement->execute([$userId, $categoryId, $title, $description, $skillsNeeded, $slots]);
      $message = '"' . $title . '" is now live and open for applications.';
    }
  } elseif ($action === 'close_posting') {
    $postingId = (int) ($_POST['posting_id'] ?? 0);
    $database->prepare("UPDATE postings SET status = 'closed' WHERE id = ? AND client_id = ?")->execute([$postingId, $userId]);
    $message = 'Posting closed.';
  } elseif ($action === 'reopen_posting') {
    $postingId = (int) ($_POST['posting_id'] ?? 0);
    $database->prepare("UPDATE postings SET status = 'open' WHERE id = ? AND client_id = ?")->execute([$postingId, $userId]);
    $message = 'Posting reopened.';
  } elseif ($action === 'decide_application') {
    $applicationId = (int) ($_POST['application_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    if (in_array($decision, ['accepted', 'declined', 'reviewed'], true)) {
      $ownsPosting = $database->prepare('SELECT applications.id FROM applications JOIN postings ON postings.id = applications.posting_id WHERE applications.id = ? AND postings.client_id = ?');
      $ownsPosting->execute([$applicationId, $userId]);
      if ($ownsPosting->fetchColumn()) {
        $database->prepare('UPDATE applications SET status = ?, status_seen = 0, decided_at = NOW() WHERE id = ?')->execute([$decision, $applicationId]);
        $message = 'Application updated.';
      }
    }
  }
}

/* ============ ASPIRANT: update profile ============ */
if ($role === 'aspirant' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
  $program = trim($_POST['program'] ?? '');
  $yearLevel = trim($_POST['year_level'] ?? '');
  $skills = trim($_POST['skills'] ?? '');
  $availability = trim($_POST['availability'] ?? '');
  $bio = trim($_POST['bio'] ?? '');
  $database->prepare('UPDATE users SET program = ?, year_level = ?, skills = ?, availability = ?, bio = ? WHERE id = ?')
    ->execute([$program, $yearLevel, $skills, $availability, $bio, $userId]);
  $message = 'Profile updated.';
}

$statement = $database->prepare('SELECT * FROM users WHERE id = ?');
$statement->execute([$userId]);
$profile = $statement->fetch(PDO::FETCH_ASSOC);

if ($role === 'client') {
  $categories = $database->query('SELECT * FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
  $myPostings = $database->prepare('SELECT postings.*, categories.name AS category_name FROM postings JOIN categories ON categories.id = postings.category_id WHERE client_id = ? ORDER BY postings.created_at DESC');
  $myPostings->execute([$userId]);
  $myPostings = $myPostings->fetchAll(PDO::FETCH_ASSOC);

  $applicantsStatement = $database->prepare("
    SELECT applications.*, users.name AS aspirant_name, users.program, users.year_level, users.skills AS aspirant_skills, users.email AS aspirant_email,
           postings.title AS posting_title
    FROM applications
    JOIN users ON users.id = applications.aspirant_id
    JOIN postings ON postings.id = applications.posting_id
    WHERE postings.client_id = ?
    ORDER BY applications.created_at DESC
  ");
  $applicantsStatement->execute([$userId]);
  $applicants = $applicantsStatement->fetchAll(PDO::FETCH_ASSOC);
} else {
  $applicationsStatement = $database->prepare("
    SELECT applications.*, postings.title AS posting_title, postings.description AS posting_description,
           users.org_name AS client_org, users.name AS client_name, categories.name AS category_name
    FROM applications
    JOIN postings ON postings.id = applications.posting_id
    JOIN users ON users.id = postings.client_id
    JOIN categories ON categories.id = postings.category_id
    WHERE applications.aspirant_id = ?
    ORDER BY applications.created_at DESC
  ");
  $applicationsStatement->execute([$userId]);
  $myApplications = $applicationsStatement->fetchAll(PDO::FETCH_ASSOC);
  $database->prepare('UPDATE applications SET status_seen = 1 WHERE aspirant_id = ?')->execute([$userId]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $role === 'client' ? 'My Postings' : 'My Account'; ?> — ComMEETtee</title>
<link rel="stylesheet" href="style.css?v=<?php echo file_exists(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time(); ?>">
<script src="script.js?v=<?php echo file_exists(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : time(); ?>" defer></script>
</head>
<body>
<main class="account-page">
  <div class="account-header">
    <div><div class="eyebrow">ComMEETtee</div><h1 class="display"><?php echo $role === 'client' ? 'My Postings' : 'My Account'; ?></h1></div>
    <div class="store-actions">
      <div class="notif-wrap">
        <button class="nav-action notif-toggle" id="notif-toggle-btn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Application updates">
          <svg class="notif-bell" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 3a6 6 0 0 0-6 6v3.2c0 .5-.16.99-.46 1.4L4 15.5c-.6.8-.02 2 .98 2h14.04c1 0 1.58-1.2.98-2l-1.54-1.9c-.3-.41-.46-.9-.46-1.4V9a6 6 0 0 0-6-6Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M9.5 19a2.5 2.5 0 0 0 5 0" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
          <span class="notif-count" id="notif-count" style="display:none;">0</span>
        </button>
        <div class="notif-panel" id="notif-panel" aria-hidden="true">
          <div class="notif-panel-head">Updates</div>
          <div class="notif-list" id="notif-list"><p class="notif-empty">Nothing new yet.</p></div>
        </div>
      </div>
      <a class="nav-action" href="index.php">Back to ComMEETtee</a>
    </div>
  </div>

  <?php if ($message): ?><div class="store-message order-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="form-error account-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

  <?php if ($role === 'client'): ?>
    <?php include __DIR__ . '/account/client-dashboard.php'; ?>
  <?php else: ?>
    <?php include __DIR__ . '/account/aspirant-dashboard.php'; ?>
  <?php endif; ?>
</main>
</body>
</html>