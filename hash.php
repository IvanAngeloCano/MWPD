<?php
// Change the password below to whatever you want to hash.
$password = 'test123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo 'Password: ' . htmlspecialchars($password) . '<br>';
echo 'New hash for database: <br><b>' . htmlspecialchars($hash) . '</b>';
?>
