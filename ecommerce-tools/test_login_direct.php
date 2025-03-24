<?php
// Test login script for validating authentication functionality
// Uses cURL to simulate a direct browser login

// Test credentials
$email = 'sellersbay@gmail.com';
$password = 'powder04';

// Function to extract CSRF token from login page
function getCsrfToken($html) {
    $pattern = '/<input type="hidden" name="_csrf_token" value="([^"]+)"/';
    if (preg_match($pattern, $html, $matches)) {
        return $matches[1];
    }
    return null;
}

// Initialize cURL session
$ch = curl_init();

// Configure cURL options for cookie handling
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);

// Step 1: Fetch login page to get CSRF token
echo "Step 1: Requesting login page...\n";
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
$response = curl_exec($ch);
$csrfToken = getCsrfToken($response);

if (!$csrfToken) {
    echo "Error: Could not find CSRF token on login page\n";
    exit(1);
}

echo "Found CSRF token: $csrfToken\n";

// Step 2: Submit login form
echo "\nStep 2: Submitting login credentials...\n";
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_username' => $email,
    '_password' => $password,
    '_csrf_token' => $csrfToken,
    '_remember_me' => 'on'
]));

$loginResponse = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

echo "Login response status code: $statusCode\n";
echo "Final URL after login: $finalUrl\n";

// Step 3: Check if we can access the dashboard
echo "\nStep 3: Checking dashboard access...\n";
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/dashboard');
curl_setopt($ch, CURLOPT_POST, false);
$dashboardResponse = curl_exec($ch);
$dashboardStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$dashboardUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

echo "Dashboard status code: $dashboardStatusCode\n";
echo "Dashboard URL: $dashboardUrl\n";

// Verify authentication
if (strpos($dashboardUrl, '/login') !== false) {
    echo "\nFAILED: Login was unsuccessful - redirected to login page\n";
} else if (strpos($dashboardResponse, 'Welcome') !== false) {
    echo "\nSUCCESS: Login was successful - dashboard shows Welcome message\n";
} else {
    echo "\nUNSURE: Reached dashboard but couldn't verify success indicators\n";
}

// Clean up
curl_close($ch);
?>