<?php
session_start();
require_once __DIR__ . '/database.php';

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Must be logged in at all.
if (empty($_SESSION['user_id'])) {
  header('Location: login.php?next=account.php');
  exit;
}

// Admins have their own dashboard — send them there instead.
if (!empty($_SESSION['is_admin'])) {
  header('Location: admin.php');
  exit;
}

$database = getDatabase();

$flashMessage = $_SESSION['account_flash_message'] ?? '';
unset($_SESSION['account_flash_message']);

$userStatement = $database->prepare('SELECT id, name, email, role, status, org_name, avatar, created_at FROM users WHERE id = ? LIMIT 1');
$userStatement->execute([$_SESSION['user_id']]);
$user = $userStatement->fetch(PDO::FETCH_ASSOC);

// If the session refers to a user that no longer exists (or was deleted),
// clear the stale session and send them back to log in.
if (!$user) {
  session_unset();
  session_destroy();
  header('Location: login.php');
  exit;
}

// Being banned should also boot the user out, even mid-session.
if (($user['status'] ?? 'active') === 'banned') {
  session_unset();
  session_destroy();
  header('Location: login.php');
  exit;
}

$myPostings = [];
$myApplications = [];
$myGroupCount = 0;

$groupCountStatement = $database->prepare('SELECT COUNT(*) FROM chat_group_members WHERE user_id = ?');
$groupCountStatement->execute([$user['id']]);
$myGroupCount = (int) $groupCountStatement->fetchColumn();

if ($user['role'] === 'client') {
  $myPostings = $database->prepare("
    SELECT postings.*, categories.name AS category_name,
           (SELECT COUNT(*) FROM applications WHERE posting_id = postings.id) AS application_count
    FROM postings
    JOIN categories ON categories.id = postings.category_id
    WHERE postings.client_id = ?
    ORDER BY postings.created_at DESC
  ");
  $myPostings->execute([$user['id']]);
  $myPostings = $myPostings->fetchAll(PDO::FETCH_ASSOC);
} elseif ($user['role'] === 'aspirant') {
  $myApplications = $database->prepare("
    SELECT applications.*, postings.title AS posting_title, clients.name AS client_name, clients.org_name AS client_org
    FROM applications
    JOIN postings ON postings.id = applications.posting_id
    JOIN users AS clients ON clients.id = postings.client_id
    WHERE applications.aspirant_id = ?
    ORDER BY applications.created_at DESC
  ");
  $myApplications->execute([$user['id']]);
  $myApplications = $myApplications->fetchAll(PDO::FETCH_ASSOC);
}

$initials = '';
foreach (preg_split('/\s+/', trim($user['name'])) as $part) {
  if ($part !== '') {
    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
  }
  if (mb_strlen($initials) >= 2) {
    break;
  }
}

$roleBadgeClass = [
  'aspirant' => 'profile-role-badge--aspirant',
  'client' => 'profile-role-badge--client',
  'admin' => 'profile-role-badge--admin',
][$user['role']] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account — ComMEETtee</title>
<link rel="stylesheet" href="style.css?v=<?php echo file_exists(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time(); ?>">
<script src="script.js?v=<?php echo file_exists(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : time(); ?>" defer></script>
</head>
<body>
<main class="account-page wrap" style="padding-top: 40px; padding-bottom: 60px;">

  <?php if ($flashMessage): ?>
    <div class="dash-message" style="margin-bottom: 20px;"><?php echo htmlspecialchars($flashMessage); ?></div>
  <?php endif; ?>

  <section class="profile-card" style="max-width: 600px; margin: 0 auto 30px auto;">
    <div class="profile-cover"></div>
    <div class="profile-card-body" style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 0 28px 28px;">
      
      <form class="profile-avatar-upload" method="post" action="avatar-upload.php" enctype="multipart/form-data" style="margin-top: -46px; margin-bottom: 16px;">
        <label class="profile-avatar-edit" title="Change profile photo" style="position: relative; display: block; width: 96px; height: 96px; border-radius: 50%; border: 4px solid var(--white); box-shadow: var(--shadow); cursor: pointer; overflow: hidden; background: var(--navy);">
          <?php if (!empty($user['avatar']) && file_exists(__DIR__ . '/' . $user['avatar'])): ?>
            <img src="<?php echo htmlspecialchars($user['avatar']); ?>?v=<?php echo filemtime(__DIR__ . '/' . $user['avatar']); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>" class="profile-avatar-photo" style="width: 100%; height: 100%; object-fit: cover;">
          <?php else: ?>
            <span class="profile-avatar-placeholder profile-avatar-placeholder--large" style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; font-size: 32px; font-weight: 700; color: var(--white);"><?php echo htmlspecialchars($initials ?: '?'); ?></span>
          <?php endif; ?>
          <span class="profile-avatar-edit-icon" aria-hidden="true" style="position: absolute; bottom: 0; right: 0; background: var(--orange); color: var(--navy); width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid var(--white);">
            <svg viewBox="0 0 24 24" fill="none" style="width: 14px; height: 14px;"><path d="M4 20h4.2L18.4 9.8a2 2 0 0 0 0-2.8l-1.4-1.4a2 2 0 0 0-2.8 0L4 15.8V20Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
          </span>
          <input type="file" name="avatar" accept="image/png, image/jpeg, image/webp, image/gif" onchange="this.form.submit()" hidden>
        </label>
      </form>

      <div class="profile-card-info" style="width: 100%; margin-bottom: 24px;">
        <div class="profile-name-row" style="display: flex; align-items: center; justify-content: center; gap: 10px; flex-wrap: wrap; margin-bottom: 8px;">
          <h1 class="display" style="font-size: 24px; margin: 0;"><?php echo htmlspecialchars($user['name']); ?></h1>
          <span class="profile-role-badge <?php echo $roleBadgeClass; ?>" style="background: var(--gray-100); padding: 2px 10px; border-radius: 99px; font-size: 12px; font-weight: 700;"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
        </div>
        <p class="account-meta" style="color: var(--gray-700); font-size: 14px;">
          <?php if (!empty($user['org_name'])): ?><?php echo htmlspecialchars($user['org_name']); ?> &middot; <?php endif; ?>
          <?php echo htmlspecialchars($user['email']); ?>
          &middot; Joined <?php echo htmlspecialchars(date('M Y', strtotime($user['created_at']))); ?>
        </p>
      </div>

      <div class="profile-card-actions" style="display: flex; gap: 12px; width: 100%; justify-content: center;">
        <a class="btn btn--outline" href="groups.php">Group Chats<?php if ($myGroupCount > 0): ?> <span class="dash-nav-badge" style="background: var(--orange); color: var(--navy); padding: 1px 6px; border-radius: 10px; font-size: 11px;"><?php echo $myGroupCount; ?></span><?php endif; ?></a>
        <a class="btn btn--dark" href="logout.php">Log out</a>
      </div>
    </div>
  </section>

  <?php if ($user['role'] === 'client'): ?>
    <section class="account-section" style="max-width: 900px; margin: 0 auto;">
      <h2 style="margin-bottom: 16px;">Your postings</h2>
      <?php if (!$myPostings): ?>
        <p style="color: var(--gray-700);">You haven't created any postings yet.</p>
      <?php else: ?>
        <div style="overflow-x: auto;">
          <table class="account-table" style="width: 100%; border-collapse: collapse; background: var(--white); border-radius: var(--radius-sm); overflow: hidden; box-shadow: var(--shadow);">
            <thead>
              <tr style="background: var(--gray-100); text-align: left;">
                <th style="padding: 12px 16px;">Title</th>
                <th style="padding: 12px 16px;">Category</th>
                <th style="padding: 12px 16px;">Status</th>
                <th style="padding: 12px 16px;">Moderation</th>
                <th style="padding: 12px 16px;">Applications</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($myPostings as $posting): ?>
                <tr style="border-top: 1px solid var(--gray-100);">
                  <td style="padding: 12px 16px;"><?php echo htmlspecialchars($posting['title']); ?></td>
                  <td style="padding: 12px 16px;"><?php echo htmlspecialchars($posting['category_name']); ?></td>
                  <td style="padding: 12px 16px;"><?php echo htmlspecialchars(ucfirst($posting['status'])); ?></td>
                  <td style="padding: 12px 16px;"><?php echo htmlspecialchars(ucfirst($posting['moderation_status'] ?? 'pending')); ?></td>
                  <td style="padding: 12px 16px;"><?php echo (int) $posting['application_count']; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  <?php elseif ($user['role'] === 'aspirant'): ?>
    <section class="account-section" style="max-width: 900px; margin: 0 auto;">
      <h2 style="margin-bottom: 16px;">Your applications</h2>
      <?php if (!$myApplications): ?>
        <p style="color: var(--gray-700);">You haven't applied to anything yet.</p>
      <?php else: ?>
        <div style="overflow-x: auto;">
          <table class="account-table" style="width: 100%; border-collapse: collapse; background: var(--white); border-radius: var(--radius-sm); overflow: hidden; box-shadow: var(--shadow);">
            <thead>
              <tr style="background: var(--gray-100); text-align: left;">
                <th style="padding: 12px 16px;">Posting</th>
                <th style="padding: 12px 16px;">Client</th>
                <th style="padding: 12px 16px;">Status</th>
                <th style="padding: 12px 16px;">Applied</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($myApplications as $application): ?>
                <tr style="border-top: 1px solid var(--gray-100);">
                  <td style="padding: 12px 16px;"><?php echo htmlspecialchars($application['posting_title']); ?></td>
                  <td style="padding: 12px 16px;"><?php echo htmlspecialchars($application['client_org'] ?: $application['client_name']); ?></td>
                  <td style="padding: 12px 16px;"><?php echo htmlspecialchars(ucfirst($application['status'])); ?></td>
                  <td style="padding: 12px 16px;"><?php echo htmlspecialchars($application['created_at']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</main>
</body>
</html>