<?php
/**
 * Shared validation and access-control helpers.
 * Include this after session_start() and database.php in any page that needs it.
 */

function isValidPhone(string $phone): bool
{
  return (bool) preg_match('/^09\d{9}$/', $phone);
}

/**
 * NORSU's undergraduate program list (norsu.edu.ph/14), used to populate
 * the "Program" dropdown on registration and the aspirant profile form.
 */
function norsuProgramOptions(): array
{
  return [
    'Bachelor of Science in Biology',
    'Bachelor of Science in Chemistry',
    'Bachelor of Science in Computer Science',
    'Bachelor of Science in Geology',
    'Bachelor of Science in Information Technology',
    'Bachelor of Mass Communication',
    'Bachelor of Science in Mathematics',
    'Bachelor of Science in Psychology',
    'Bachelor of Science in Automotive Technology',
    'Bachelor of Science in Aviation Maintenance',
    'Bachelor of Science in Civil Technology',
    'Bachelor of Science in Computer and Electronics Technology',
    'Bachelor of Science in Electrical Technology',
    'Bachelor of Science in Food Technology',
    'Bachelor of Science in Industrial Technology',
    'Bachelor of Science in Mechanical Technology',
    'Bachelor of Science in Refrigeration and Air-Conditioning Technology',
    'Bachelor of Science in Elementary Education',
    'Bachelor of Science in Secondary Education',
    'Bachelor of Science in Accountancy',
    'Bachelor of Science in Business Administration',
    'Bachelor of Science in Office Systems Management',
    'Bachelor of Science in Hospitality Management',
    'Bachelor of Science in Tourism Management',
    'Bachelor of Science in Nursing',
    'Bachelor of Science in Pharmacy',
    'Bachelor of Science in Architecture',
    'Bachelor of Science in Civil Engineering',
    'Bachelor of Science in Computer Engineering',
    'Bachelor of Science in Electrical Engineering',
    'Bachelor of Science in Electronics and Communication Engineering',
    'Bachelor of Science in Geodetic Engineering',
    'Bachelor of Science in Geothermal Engineering',
    'Bachelor of Science in Mechanical Engineering',
    'Bachelor of Science in Criminology',
    'Bachelor of Science in Forestry',
    'Bachelor of Science in Agriculture',
    'Bachelor of Law',
  ];
}

/**
 * Year-level options for the aspirant Year Level dropdown. Five years
 * covers programs like Engineering, Architecture, and Law that run
 * longer than the standard four-year degree.
 */
function yearLevelOptions(): array
{
  return ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
}

/**
 * Validates the registration fields shared by register.php's Aspirant and
 * Client tabs. $role controls which extra field is required.
 */
function validateRegistrationFields(string $name, string $email, string $password, string $confirmPassword, string $role): string
{
  if ($name === '') {
    return 'Please enter your name.';
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return 'Please enter a valid email address.';
  }
  if (strlen($password) < 6) {
    return 'Password must be at least 6 characters.';
  }
  if ($password !== $confirmPassword) {
    return 'Passwords do not match.';
  }
  if (!in_array($role, ['aspirant', 'client'], true)) {
    return 'Please choose whether you are joining as an Aspirant or a Client.';
  }
  return '';
}

function isLoggedIn(): bool
{
  return !empty($_SESSION['user_id']);
}

function isAdmin(): bool
{
  return !empty($_SESSION['is_admin']);
}

function isClient(): bool
{
  return ($_SESSION['role'] ?? '') === 'client';
}

function isAspirant(): bool
{
  return ($_SESSION['role'] ?? '') === 'aspirant';
}

function requireAdmin(): void
{
  if (!isAdmin()) {
    header('Location: login.php');
    exit;
  }
}

function requireLogin(): void
{
  if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
  }
}