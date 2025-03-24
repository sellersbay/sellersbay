<?php
/**
 * Login test script with CSRF token support for Symfony
 * This script:
 * 1. Gets the login page to extract the CSRF token
 * 2. Submits login credentials with the CSRF token
 * 3. Follows redirects to the dashboard
 * 4. Saves the dashboard HTML for inspection
 */

// Configuration
$loginUrl = 'http://127.0.0.1:8000/login';
$dashboardUrl = 'http://127.0.0.1:8000/dashboard';
$email = 'sellersbay@gmail.com';
$password = 'powder04';
$cookieFile = 'csrf_test_cookies.txt';
$outputFile = 'dashboard_output.html';

echo "Starting login test with CSRF support...\n";

// Initialize CURL session
$ch = curl_init();

// Set cookie storage
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);

// Step 1: Get the login page to extract CSRF token
echo "Fetching login page to extract CSRF token...\n";
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, false);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch) . "\n";
    exit(1);
}

// Extract CSRF token
preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)"/', $response, $matches);
$csrfToken = isset($matches[1]) ? $matches[1] : '';

if (empty($csrfToken)) {
    echo "Failed to extract CSRF token from login page\n";
    exit(1);
}

echo "Extracted CSRF token: " . substr($csrfToken, 0, 10) . "...\n";

// Step 2: Submit login with CSRF token
echo "Submitting login credentials with CSRF token...\n";
$postData = [
    '_csrf_token' => $csrfToken,
    '_username' => $email,
    '_password' => $password,
    '_remember_me' => 'on'
];

curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

$response = curl_exec($ch);
$info = curl_getinfo($ch);

echo "Login submission status code: " . $info['http_code'] . "\n";
echo "Redirected to: " . $info['redirect_url'] . "\n";

// Step 3: Access the dashboard
echo "Attempting to access dashboard...\n";
curl_setopt($ch, CURLOPT_URL, $dashboardUrl);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HEADER, false); // Don't need headers for this request

$dashboardResponse = curl_exec($ch);
$info = curl_getinfo($ch);

echo "Dashboard access status code: " . $info['http_code'] . "\n";

// Step 4: Save dashboard output for inspection
file_put_contents($outputFile, $dashboardResponse);
echo "Dashboard response saved to $outputFile (" . strlen($dashboardResponse) . " bytes)\n";

// Check if dashboard content seems valid
if (strpos($dashboardResponse, 'Dashboard') !== false) {
    echo "SUCCESS: Dashboard content found!\n";
    if (strpos($dashboardResponse, 'Total Products') !== false) {
        echo "SUCCESS: Products section found!\n";
    }
    if (strpos($dashboardResponse, 'WooCommerce Products') !== false) {
        echo "SUCCESS: WooCommerce section found!\n";
    }
    if (strpos($dashboardResponse, 'Recent Activity') !== false) {
        echo "SUCCESS: Recent Activity section found!\n";
    }
} else {
    echo "WARNING: Could not verify dashboard content\n";
}

echo "Test completed.\n";
curl_close($ch);