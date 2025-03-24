<?php
/**
 * Direct Password Reset Script
 * This script directly connects to the database and updates the password
 * Bypassing MySQL's handling of special characters in bcrypt hashes
 */

// Simple password to set
$password = 'password123';

// Generate a bcrypt hash for the password
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Generated hash: " . $hash . "\n";

// Connect to the database directly
try {
    $pdo = new PDO('mysql:host=roboseo2-mysql;dbname=roboseo2', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare statement with parameter binding to avoid special character issues
    $stmt = $pdo->prepare("UPDATE user SET password = :password WHERE email = :email");
    
    // Bind parameters - this handles escaping properly
    $stmt->bindParam(':password', $hash);
    $email = 'sellersbay@gmail.com';
    $stmt->bindParam(':email', $email);
    
    // Execute the statement
    $result = $stmt->execute();
    
    if ($result) {
        echo "Success! Password updated for $email\n";
        echo "You can now log in with:\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
    } else {
        echo "Failed to update password.\n";
    }
    
    // Verify the update
    $check = $pdo->query("SELECT id, email, password FROM user WHERE email = 'sellersbay@gmail.com'");
    $user = $check->fetch(PDO::FETCH_ASSOC);
    
    echo "\nVerification:\n";
    echo "User ID: {$user['id']}\n";
    echo "Email: {$user['email']}\n";
    echo "Password hash in DB: {$user['password']}\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}