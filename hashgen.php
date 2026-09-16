<?php
// TEMPORARY FILE — delete this after use.
// Generates a bcrypt hash for "admin123" using this server's own PHP,
// so it's guaranteed to match when password_verify() checks it.
echo password_hash('admin123', PASSWORD_DEFAULT);