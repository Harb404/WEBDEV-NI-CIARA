<?php
/**
 * Loads the data the public pages need: committee categories, open
 * postings (with the client's name and how many slots are already
 * filled), and — if someone is logged in as an aspirant — the set of
 * postings they've already applied to, keyed by posting_id so the
 * templates can just check isset($myApplications[$postingId]).
 */

$categories = $database->query('SELECT * FROM categories ORDER BY FIELD(id, "technicals", "decorations", "logistics", "documentation")')->fetchAll(PDO::FETCH_ASSOC);

$postings = $database->query("
  SELECT postings.*, users.name AS client_name, users.org_name AS client_org,
         categories.name AS category_name,
         (SELECT COUNT(*) FROM applications WHERE applications.posting_id = postings.id AND applications.status = 'accepted') AS filled_slots
  FROM postings
  JOIN users ON users.id = postings.client_id
  JOIN categories ON categories.id = postings.category_id
  WHERE postings.status = 'open'
  ORDER BY postings.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$myApplications = [];
if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'aspirant') {
  $statement = $database->prepare('SELECT posting_id, status FROM applications WHERE aspirant_id = ?');
  $statement->execute([(int) $_SESSION['user_id']]);
  foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $myApplications[(int) $row['posting_id']] = $row['status'];
  }
}