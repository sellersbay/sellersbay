<?php
// Test script for login with CSRF token handling
echo "Starting login test with CSRF token handling...\n";

// Test credentials
$email = 'sellersbay@gmail.com';
$password = 'powder04';

// Initialize cURL session
$ch = curl_init();

// First get the login page to obtain the CSRF token
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'csrf_test_cookies.txt');
curl_setopt($ch, CURLOPT_HEADER, false);

$response = curl_exec($ch);
echo "Fetched login page. Response length: " . strlen($response) . "\n";

// Extract CSRF token from the response
preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)"/', $response, $matches);
if (empty($matches[1])) {
    echo "Failed to get CSRF token\n";
    file_put_contents('csrf_login_page.html', $response);
    echo "Login page saved to csrf_login_page.html for debugging\n";
    die();
}
$csrfToken = $matches[1];

echo "Got CSRF token: " . substr($csrfToken, 0, 10) . "...\n";

// Now submit the login form with CSRF token
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_csrf_token' => $csrfToken,
    '_username' => $email,
    '_password' => $password,
    '_remember_me' => 'on'
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, 'csrf_test_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEJAR, 'csrf_test_cookies.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
echo "Submitted login form\n";

// Check HTTP status code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Status Code: $httpCode\n";

// Get redirect URL if any
$redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
echo "Redirect URL: " . ($redirectUrl ?: "None") . "\n";

// Split headers and body
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

// Save response for debugging
file_put_contents('csrf_login_response.html', $body);
echo "Login response saved to csrf_login_response.html\n";

// Check for login error messages
if (strpos($body, 'Invalid credentials') !== false) {
    echo "ERROR: Invalid credentials\n";
}
if (strpos($body, 'Invalid CSRF token') !== false) {
    echo "ERROR: Invalid CSRF token\n";
}

// Try to access dashboard with authenticated session
echo "\nAttempting to access dashboard...\n";
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/dashboard');
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_COOKIEFILE, 'csrf_test_cookies.txt');

$dashboardResponse = curl_exec($ch);
$dashboardHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Dashboard HTTP Status Code: $dashboardHttpCode\n";

// Check if we got dashboard content
if (strpos($dashboardResponse, 'Dashboard') !== false) {
    echo "SUCCESS: Dashboard loaded successfully!\n";
} else {
    echo "FAILED: Dashboard content not found\n";
    // Save dashboard response for debugging
    file_put_contents('csrf_dashboard_response.html', $dashboardResponse);
    echo "Dashboard response saved to csrf_dashboard_response.html\n";
}

curl_close($ch);

// Clean up
if (file_exists('csrf_test_cookies.txt')) {
    unlink('csrf_test_cookies.txt');
}