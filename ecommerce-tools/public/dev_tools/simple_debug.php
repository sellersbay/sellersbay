<?php
// Simple debug script for login issues

// Test environment
echo "<h1>Login System Debug</h1>";
echo "<pre>";

// Check if we're in debug mode
echo "APP_ENV: " . ($_SERVER['APP_ENV'] ?? 'unknown') . "\n";
echo "APP_DEBUG: " . ($_SERVER['APP_DEBUG'] ?? 'unknown') . "\n\n";

// Check current request
echo "Current request details:\n";
echo "Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n";
echo "URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n\n";

// Check for security.yaml
$securityYamlPath = __DIR__ . '/../config/packages/security.yaml';
echo "Security config exists: " . (file_exists($securityYamlPath) ? 'Yes' : 'No') . "\n";

if (file_exists($securityYamlPath)) {
    $securityContent = file_get_contents($securityYamlPath);
    echo "Contains FileUserProvider: " . (strpos($securityContent, 'FileUserProvider') !== false ? 'Yes' : 'No') . "\n\n";
}

// Check for login authenticator
$authenticatorPath = __DIR__ . '/../src/Security/LoginFormAuthenticator.php';
echo "LoginFormAuthenticator exists: " . (file_exists($authenticatorPath) ? 'Yes' : 'No') . "\n";

if (file_exists($authenticatorPath)) {
    $authenticatorContent = file_get_contents($authenticatorPath);
    echo "Contains supports method: " . (strpos($authenticatorContent, 'public function supports') !== false ? 'Yes' : 'No') . "\n";
    
    // Extract the supports method to see its implementation
    if (preg_match('/public function supports.*?\{(.*?)\}/s', $authenticatorContent, $matches)) {
        echo "Supports method implementation:\n" . trim($matches[1]) . "\n\n";
    }
}

// Check for templates errors
$dashboardPath = __DIR__ . '/../templates/dashboard/index.html.twig';
echo "Dashboard template exists: " . (file_exists($dashboardPath) ? 'Yes' : 'No') . "\n";

if (file_exists($dashboardPath)) {
    $dashboardContent = file_get_contents($dashboardPath);
    echo "Contains instanceof: " . (strpos($dashboardContent, 'instanceof') !== false ? 'Yes' : 'No') . "\n";
    echo "Contains instance of: " . (strpos($dashboardContent, 'instance of') !== false ? 'Yes' : 'No') . "\n\n";
}

// Test form submission directly
echo "Form submission test:\n";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "Test login post data received:\n";
    echo "Username: " . ($_POST['_username'] ?? 'not provided') . "\n";
    echo "Password: " . (isset($_POST['_password']) ? '********' : 'not provided') . "\n";
    echo "CSRF Token: " . ($_POST['_csrf_token'] ?? 'not provided') . "\n";
}

// Show a test form
echo "</pre>";
echo "<hr><h2>Test Login Form</h2>";
echo "<form method='post' action=''>";
echo "<div>Username: <input type='text' name='_username' value='sellersbay@gmail.com'></div>";
echo "<div>Password: <input type='password' name='_password' value='powder04'></div>";
echo "<div>CSRF Token: <input type='text' name='_csrf_token' value='debug-token'></div>";
echo "<div><button type='submit'>Test Login</button></div>";
echo "</form>";