<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/Validation.php';

if (!empty($_SESSION['user_id'])) {
  header('Location: account.php');
  exit;
}

function safeNextUrl(?string $next): ?string
{
  if (!$next) {
    return null;
  }
  if (preg_match('~^(?!//)(?!https?:)[A-Za-z0-9_\-]+\.php(\?[A-Za-z0-9_\-=&%.]*)?(#[A-Za-z0-9_\-]*)?$~', $next)) {
    return $next;
  }
  return null;
}

$nextUrl = safeNextUrl($_GET['next'] ?? $_POST['next'] ?? null);
$database = getDatabase();
$error = '';
$role = $_POST['role'] ?? ($_GET['role'] ?? 'aspirant');
if (!in_array($role, ['aspirant', 'client'], true)) {
  $role = 'aspirant';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = strtolower(trim($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirm_password'] ?? '';
  $orgName = trim($_POST['org_name'] ?? '');
  $program = trim($_POST['program'] ?? '');
  $yearLevel = trim($_POST['year_level'] ?? '');
  $skills = trim($_POST['skills'] ?? '');

  $error = validateRegistrationFields($name, $email, $password, $confirmPassword, $role);
  if ($error === '' && $role === 'client' && $orgName === '') {
    $error = 'Please enter your organization or business name.';
  }

  if ($error === '') {
    try {
      $statement = $database->prepare('INSERT INTO users (name, email, password, role, org_name, program, year_level, skills) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
      $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $orgName, $program, $yearLevel, $skills]);
      $_SESSION['user_id'] = (int) $database->lastInsertId();
      $_SESSION['user_name'] = $name;
      $_SESSION['role'] = $role;
      unset($_SESSION['is_admin']);
      header('Location: ' . ($nextUrl ?: 'account.php'));
      exit;
    } catch (PDOException $exception) {
      $error = 'That email is already registered.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account — ComMEETtee</title>
<link rel="stylesheet" href="style.css?v=<?php echo file_exists(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time(); ?>">
<script src="script.js?v=<?php echo file_exists(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : time(); ?>" defer></script>
</head>
<body>
<main class="auth-page register-page">
  <form class="auth-card" method="post">
    <div class="eyebrow">ComMEETtee</div>
    <h1 class="display">Join ComMEETtee</h1>
    <p class="form-intro">Tell us how you'd like to use ComMEETtee.</p>

    <div class="role-toggle" role="tablist" aria-label="Account type">
      <button type="button" class="role-toggle-btn <?php echo $role === 'aspirant' ? 'is-active' : ''; ?>" data-role-btn="aspirant">I'm an Aspirant</button>
      <button type="button" class="role-toggle-btn <?php echo $role === 'client' ? 'is-active' : ''; ?>" data-role-btn="client">I'm a Client</button>
    </div>
    <input type="hidden" name="role" id="register-role" value="<?php echo htmlspecialchars($role); ?>">

    <?php if ($error): ?><p class="form-error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <?php if ($nextUrl): ?><input type="hidden" name="next" value="<?php echo htmlspecialchars($nextUrl); ?>"><?php endif; ?>

    <label>Full Name<input type="text" name="name" required autocomplete="name"></label>
    <label>Email<input type="email" name="email" required autocomplete="email"></label>

    <div data-role-field="client" <?php echo $role !== 'client' ? 'hidden' : ''; ?>>
      <label>Organization / Business Name<input type="text" name="org_name" autocomplete="organization"></label>
    </div>
    <div data-role-field="aspirant" <?php echo $role !== 'aspirant' ? 'hidden' : ''; ?>>
      <label>Program
        <select name="program">
          <option value="">Select your program…</option>
          <?php foreach (norsuProgramOptions() as $programOption): ?>
            <option value="<?php echo htmlspecialchars($programOption); ?>"><?php echo htmlspecialchars($programOption); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Year Level
        <select name="year_level">
          <option value="">Select your year level…</option>
          <?php foreach (yearLevelOptions() as $yearOption): ?>
            <option value="<?php echo htmlspecialchars($yearOption); ?>"><?php echo htmlspecialchars($yearOption); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Skills &amp; Interests<input type="text" name="skills" placeholder="e.g. Graphic Design, Video Editing" autocomplete="off"></label>
    </div>

    <label>Password
      <div class="password-input-wrap">
        <input type="password" name="password" id="register-password" minlength="6" required autocomplete="new-password">
        <button type="button" class="password-toggle" data-toggle-for="register-password" aria-label="Show password" aria-pressed="false">
          <svg class="icon-eye" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/></svg>
          <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M3 3l18 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        </button>
      </div>
    </label>
    <label>Confirm Password
      <div class="password-input-wrap">
        <input type="password" name="confirm_password" id="register-confirm-password" minlength="6" required autocomplete="new-password">
        <button type="button" class="password-toggle" data-toggle-for="register-confirm-password" aria-label="Show password" aria-pressed="false">
          <svg class="icon-eye" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/></svg>
          <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M3 3l18 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        </button>
      </div>
      <span class="form-error password-match-error" id="confirm-password-error" hidden>Passwords do not match.</span>
    </label>

    <button class="cart-button" type="submit">Create Account</button>
    <a class="back-link" href="login.php">Already have an account? Log in</a>
    <a class="back-link" href="index.php">Back to home</a>
  </form>
</main>
</body>
</html>