<?php
// Generate a password hash for 'password123' that can be copied and pasted directly
$hash = password_hash('password123', PASSWORD_DEFAULT);
echo "Generated hash: " . $hash . "\n";
echo "SQL-ready hash: '" . addslashes($hash) . "'\n";
?>