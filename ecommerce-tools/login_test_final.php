<?php
// Simple login test script
$loginUrl = 'http://localhost:8000/login';
$email = 'sellersbay@gmail.com';
$password = 'test123';

// Initialize CURL session
$ch = curl_init();

// Get the login page first to grab the CSRF token
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'test_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'test_cookies.txt');

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
    exit;
}

// Extract CSRF token from the form
preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)"/', $response, $matches);
$csrfToken = isset($matches[1]) ? $matches[1] : '';

if (empty($csrfToken)) {
    echo "Could not find CSRF token\n";
    echo "Response: " . htmlspecialchars(substr($response, 0, 500)) . "...\n";
    exit;
}

echo "Found CSRF token: $csrfToken\n";

// Now submit the login form
$postData = [
    '_csrf_token' => $csrfToken,
    '_username' => $email,
    '_password' => $password,
    '_remember_me' => 'on'
];

curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

echo "HTTP Code: $httpCode\n";
echo "Final URL: $finalUrl\n";

// Check if we were redirected to dashboard (success) or stayed on login page (failure)
if (strpos($finalUrl, '/dashboard') !== false) {
    echo "SUCCESS: Login successful! Redirected to dashboard.\n";
} else {
    echo "FAILURE: Login failed. Still on login page or other error.\n";
    
    // Display a portion of the response for debugging
    echo "Response Headers and Body (first 1000 chars):\n";
    echo substr($response, 0, 1000) . "...\n";
}

curl_close($ch);
?>