<?php
/**
 * Advanced login test script with proper CSRF handling
 * This script attempts to log in to the Symfony application by:
 * 1. Fetching the login page to extract the CSRF token
 * 2. Submitting a POST request with proper credentials and CSRF token
 * 3. Following redirects to reach the dashboard
 * 4. Outputting the dashboard content
 */

// Configuration
$loginUrl = 'http://127.0.0.1:8000/login';
$username = 'sellersbay@gmail.com';
$password = 'powder04';
$dashboardUrl = 'http://127.0.0.1:8000/dashboard';
$cookieJar = __DIR__ . '/test_cookies.txt';

// Clear previous cookies
if (file_exists($cookieJar)) {
    unlink($cookieJar);
}

echo "Starting login test with CSRF support...\n";

// Step 1: Fetch login page to extract CSRF token
echo "Fetching login page to extract CSRF token...\n";
$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

$loginPage = curl_exec($ch);
curl_close($ch);

// Extract CSRF token
$csrfToken = '';
if (preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)"/', $loginPage, $matches)) {
    $csrfToken = $matches[1];
    echo "Extracted CSRF token: " . substr($csrfToken, 0, 10) . "...\n";
} else {
    echo "Failed to extract CSRF token. Continuing anyway...\n";
}

// Step 2: Submit login form with credentials and CSRF token
echo "Submitting login credentials with CSRF token...\n";
$postData = [
    '_username' => $username,
    '_password' => $password,
    '_csrf_token' => $csrfToken,
];

$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
curl_close($ch);

echo "Login submission status code: $httpCode\n";
echo "Redirected to: $redirectUrl\n";

// Step 3: Access dashboard page to verify login status
echo "Attempting to access dashboard...\n";
$ch = curl_init($dashboardUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

$dashboardContent = curl_exec($ch);
$dashboardHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Dashboard access status code: $dashboardHttpCode\n";

// Save dashboard content for inspection
$outputFile = __DIR__ . '/dashboard_output.html';
file_put_contents($outputFile, $dashboardContent);
echo "Dashboard response saved to $outputFile (" . strlen($dashboardContent) . " bytes)\n";

// Analyze the dashboard content to check if we're properly logged in
if (stripos($dashboardContent, 'Dashboard') !== false && 
    (stripos($dashboardContent, 'Products') !== false || 
     stripos($dashboardContent, 'Total Products') !== false)) {
    echo "SUCCESS: Dashboard content verified\n";
    
    // Look for product counts
    if (preg_match('/<p class="card-text display-4">(\d+)<\/p>.*?Total Products/is', $dashboardContent, $matches)) {
        echo "Found Total Products count: " . $matches[1] . "\n";
    }
    
    if (preg_match('/<p class="card-text display-4">(\d+)<\/p>.*?WooCommerce Products/is', $dashboardContent, $matches)) {
        echo "Found WooCommerce Products count: " . $matches[1] . "\n";
    }
    
    // Check for recent activity
    if (stripos($dashboardContent, 'Recent Activity') !== false) {
        echo "Found Recent Activity section\n";
    }
} else {
    echo "WARNING: Could not verify dashboard content\n";
}

echo "Test completed.\n";