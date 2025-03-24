<?php
// Simple dashboard access helper to view our redesigned dashboard
session_start();

// Set session variables to simulate logged in user
$_SESSION['_security_main'] = serialize([
    'email' => 'sellersbay@gmail.com',
    'password' => 'powder04',
    'roles' => ['ROLE_USER'],
    'username' => 'Test User',
    'authenticated' => true
]);

// Redirect to dashboard
header('Location: /dashboard');
exit;