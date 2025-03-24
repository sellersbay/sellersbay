<?php
// Login verification test script
// This script verifies that our login system fix works correctly

$testEmail = 'sellersbay@gmail.com';
$testPassword = 'powder04';

echo "===== LOGIN VERIFICATION TEST =====\n\n";
echo "Testing login with credentials:\n";
echo "Email: $testEmail\n";
echo "Password: [hidden]\n\n";

echo "1. Initializing test...\n";

// Initialize cURL session
$ch = curl_init();

// First, get the login page to extract CSRF token
echo "2. Getting login page and CSRF token...\n";
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'test_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'test_cookies.txt');

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "ERROR: " . curl_error($ch) . "\n";
    exit(1);
}

// Extract CSRF token
$csrfToken = null;
if (preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)"/', $response, $matches)) {
    $csrfToken = $matches[1];
    echo "   CSRF token found: " . substr($csrfToken, 0, 10) . "...\n";
} else {
    echo "ERROR: Could not find CSRF token in login page.\n";
    exit(1);
}

// Now, submit the login form
echo "3. Submitting login form...\n";
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    '_username' => $testEmail,
    '_password' => $testPassword,
    '_csrf_token' => $csrfToken,
    '_remember_me' => 'on'
]);

$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

echo "   Status code: $statusCode\n";
echo "   Final URL: $finalUrl\n\n";

// Check if we were redirected to the dashboard
$loginSuccessful = (strpos($finalUrl, '/dashboard') !== false);

if ($loginSuccessful) {
    echo "SUCCESS: Login was successful! The system redirected to the dashboard.\n";
    echo "This confirms that our authenticator fix is working correctly.\n";
} else {
    echo "FAILURE: Login was not successful. We were not redirected to the dashboard.\n";
    echo "Debugging info:\n";
    echo "Response size: " . strlen($response) . " bytes\n";
    
    // Save response for debugging
    file_put_contents('login_test_response.html', $response);
    echo "Response saved to login_test_response.html for debugging.\n";
}

curl_close($ch);
echo "\nTest completed.\n";