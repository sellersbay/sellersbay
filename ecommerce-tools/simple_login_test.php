<?php
// Simplified test script with hardcoded fallback token
echo "Starting simple Docker-aware login test with fallback token...\n";

// Test credentials
$email = 'sellersbay@gmail.com';
$password = 'powder04';

// Initialize cURL session
$ch = curl_init();

// Submit login with the hardcoded fallback token
curl_setopt($ch, CURLOPT_URL, 'http://nginx/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_csrf_token' => 'test_csrf_token',  // Use the fallback token defined in LoginFormAuthenticator
    '_username' => $email,
    '_password' => $password,
    '_remember_me' => 'on'
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, 'simple_test_cookies.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
echo "Submitted login form with fallback token\n";

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
file_put_contents('simple_login_response.html', $body);
echo "Login response saved to simple_login_response.html\n";

// Check for login error messages
if (strpos($body, 'Invalid credentials') !== false) {
    echo "ERROR: Invalid credentials\n";
}
if (strpos($body, 'Invalid CSRF token') !== false) {
    echo "ERROR: Invalid CSRF token\n";
}

// Try to access dashboard with authenticated session
echo "\nAttempting to access dashboard...\n";
curl_setopt($ch, CURLOPT_URL, 'http://nginx/dashboard');
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_COOKIEFILE, 'simple_test_cookies.txt');

$dashboardResponse = curl_exec($ch);
$dashboardHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Dashboard HTTP Status Code: $dashboardHttpCode\n";

// Check if we got dashboard content
if (strpos($dashboardResponse, 'Dashboard') !== false) {
    echo "SUCCESS: Dashboard loaded successfully!\n";
} else {
    echo "FAILED: Dashboard content not found\n";
    // Save dashboard response for debugging
    file_put_contents('simple_dashboard_response.html', $dashboardResponse);
    echo "Dashboard response saved to simple_dashboard_response.html\n";
}

curl_close($ch);

// Uncomment to clean up cookie file
// if (file_exists('simple_test_cookies.txt')) {
//     unlink('simple_test_cookies.txt');
// }