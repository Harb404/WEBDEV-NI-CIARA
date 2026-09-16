<?php
// TEMPORARY — delete after use.
require_once __DIR__ . '/database.php';
$database = getDatabase();
$statement = $database->query("SELECT id, email, password, status, role FROM users WHERE email = 'admin@commeettee.local'");
$row = $statement->fetch(PDO::FETCH_ASSOC);
echo '<pre>';
var_dump($row);
if ($row) {
  echo "password_verify('admin123', ...): ";
  var_dump(password_verify('admin123', $row['password']));
}
echo '</pre>';