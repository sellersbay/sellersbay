<?php
// Place this file in the project root
// Run with: php login_test.php

// Test credentials
$email = 'sellersbay@gmail.com';
$password = 'powder04';

echo "Starting login test...\n";

// Initialize cURL session
$ch = curl_init();

// First get the login page to obtain the CSRF token
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_HEADER, false);

$response = curl_exec($ch);

// Extract CSRF token from the response using a more robust pattern
preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)"/', $response, $matches);
if (empty($matches[1])) {
    echo "Failed to get CSRF token. Response length: " . strlen($response) . "\n";
    file_put_contents('login_page.html', $response);
    echo "Login page saved to login_page.html for debugging\n";
    die();
}
$csrfToken = $matches[1];

echo "Got CSRF token: " . substr($csrfToken, 0, 10) . "...\n";

// Now submit the login form
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_csrf_token' => $csrfToken,
    '_username' => $email,
    '_password' => $password,
    '_remember_me' => 'on'
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);  // Include headers in output

$response = curl_exec($ch);

// Check for redirect header to see if login was successful
$redirectUrl = '';
if (preg_match('/Location: ([^\r\n]+)/i', $response, $matches)) {
    $redirectUrl = trim($matches[1]);
    echo "Redirect URL: $redirectUrl\n";
}

// Split headers and body
list($headers, $body) = explode("\r\n\r\n", $response, 2);

// Check HTTP status code
preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers, $matches);
$statusCode = $matches[1] ?? 'unknown';
echo "HTTP Status: $statusCode\n";

// Check for login error messages
if (strpos($body, 'Invalid credentials') !== false) {
    echo "ERROR: Invalid credentials\n";
}
if (strpos($body, 'Invalid CSRF token') !== false) {
    echo "ERROR: Invalid CSRF token\n";
}

// If redirected, try to fetch the dashboard
if ($redirectUrl && strpos($redirectUrl, 'dashboard') !== false) {
    echo "Following redirect to dashboard...\n";
    
    curl_setopt($ch, CURLOPT_URL, $redirectUrl);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $dashboardResponse = curl_exec($ch);
    
    if (strpos($dashboardResponse, 'Dashboard') !== false) {
        echo "SUCCESS: Dashboard loaded successfully!\n";
        echo "Dashboard content length: " . strlen($dashboardResponse) . " characters\n";
        file_put_contents('dashboard_content.html', $dashboardResponse);
        echo "Dashboard content saved to dashboard_content.html\n";
    } else {
        echo "FAILED: Dashboard content not found in response\n";
        echo "Response length: " . strlen($dashboardResponse) . " characters\n";
        file_put_contents('dashboard_response.html', $dashboardResponse);
        echo "Response saved to dashboard_response.html\n";
    }
} else {
    // Try to access dashboard directly with cookies
    echo "Trying to access dashboard directly...\n";
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/dashboard');
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $dashboardResponse = curl_exec($ch);
    
    if (strpos($dashboardResponse, 'Dashboard') !== false) {
        echo "SUCCESS: Dashboard loaded successfully!\n";
        echo "Dashboard content length: " . strlen($dashboardResponse) . " characters\n";
        file_put_contents('dashboard_content.html', $dashboardResponse);
        echo "Dashboard content saved to dashboard_content.html\n";
    } else {
        echo "FAILED: Dashboard content not found in response\n";
        echo "Response length: " . strlen($dashboardResponse) . " characters\n";
        file_put_contents('dashboard_response.html', $dashboardResponse);
        echo "Response saved to dashboard_response.html\n";
    }
}

curl_close($ch);
// Clean up
if (file_exists('cookies.txt')) {
    unlink('cookies.txt');
}