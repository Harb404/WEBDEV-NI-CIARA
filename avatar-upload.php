<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/Validation.php';
requireLogin();

$database = getDatabase();
$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['avatar'])) {
  header('Location: account.php');
  exit;
}

$file = $_FILES['avatar'];

if ($file['error'] !== UPLOAD_ERR_OK) {
  $_SESSION['account_flash_message'] = 'That upload didn\'t go through — please try again.';
  header('Location: account.php');
  exit;
}

// 3MB limit, images only.
$maxBytes = 3 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
  $_SESSION['account_flash_message'] = 'Please choose an image under 3MB.';
  header('Location: account.php');
  exit;
}

$imageInfo = @getimagesize($file['tmp_name']);
$allowedTypes = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
if (!$imageInfo || !isset($allowedTypes[$imageInfo[2]])) {
  $_SESSION['account_flash_message'] = 'Please upload a JPG, PNG, WEBP, or GIF image.';
  header('Location: account.php');
  exit;
}

$extension = $allowedTypes[$imageInfo[2]];
$avatarDir = __DIR__ . '/Pictures/avatars';
if (!is_dir($avatarDir)) {
  mkdir($avatarDir, 0755, true);
}

// Remove any previous avatar file for this user before saving the new one.
foreach (glob($avatarDir . '/user-' . $userId . '.*') as $oldFile) {
  @unlink($oldFile);
}

$filename = 'user-' . $userId . '-' . time() . '.' . $extension;
$destination = $avatarDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
  $_SESSION['account_flash_message'] = 'Could not save the image — please try again.';
  header('Location: account.php');
  exit;
}

$relativePath = 'Pictures/avatars/' . $filename;
$database->prepare('UPDATE users SET avatar = ? WHERE id = ?')->execute([$relativePath, $userId]);

$_SESSION['account_flash_message'] = 'Profile photo updated.';
header('Location: account.php');
exit;
