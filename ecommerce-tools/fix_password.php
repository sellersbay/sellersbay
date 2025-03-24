<?php
// Script to fix password hash for the user sellersbay@gmail.com

// Generate proper bcrypt hash for 'password123'
$password = 'password123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Password: $password\n";
echo "Hash: $hash\n";
echo "SQL-safe hash: " . addslashes($hash) . "\n";

// Output SQL command to update user password
echo "\nSQL Command to run:\n";
echo "UPDATE user SET password='". addslashes($hash) ."' WHERE email='sellersbay@gmail.com';\n";
?>