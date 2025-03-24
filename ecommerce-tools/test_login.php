<?php
// A simple script to test login functionality and dashboard access

// Install cURL if not already installed
// docker exec roboseo2-php apt-get update && docker exec roboseo2-php apt-get install -y curl

// Parse login form and get CSRF token
$ch = curl_init('http://localhost:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
$response = curl_exec($ch);

// Extract CSRF token
preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)">/', $response, $matches);
$csrf_token = $matches[1] ?? '';

if (empty($csrf_token)) {
    echo "Failed to get CSRF token\n";
    exit(1);
}

echo "Got CSRF token: " . $csrf_token . "\n";

// Attempt login
$login_data = [
    '_username' => 'sellersbay@gmail.com',
    '_password' => 'password123', // This is just a guess, can be changed
    '_csrf_token' => $csrf_token,
    '_remember_me' => 'on'
];

curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($login_data));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);

// Check if we're redirected to dashboard
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/dashboard');
curl_setopt($ch, CURLOPT_POST, false);
$dashboard_response = curl_exec($ch);

echo "Dashboard page length: " . strlen($dashboard_response) . " bytes\n";

// Check if dashboard content is present (look for common elements)
if (strpos($dashboard_response, 'dashboard') !== false || 
    strpos($dashboard_response, 'Welcome') !== false ||
    strpos($dashboard_response, 'user') !== false) {
    echo "SUCCESS: Dashboard content detected!\n";
} else {
    echo "FAILURE: Could not detect dashboard content\n";
    echo "Response snippet: " . substr($dashboard_response, 0, 500) . "...\n";
}

curl_close($ch);