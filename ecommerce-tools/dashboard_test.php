<?php
/**
 * Simplified Login Test
 *
 * This script attempts to login and capture the dashboard content
 */

// Configuration
$baseUrl = 'http://127.0.0.1:8000';
$loginUrl = $baseUrl . '/login';
$dashboardUrl = $baseUrl . '/dashboard';
$username = 'sellersbay@gmail.com';
$password = 'powder04';
$output_file = 'dashboard_output.html';

echo "Starting login test...\n";

// Initialize cURL session
$ch = curl_init();

// Set cURL options for getting the login form
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');

// Execute the request to get the login form
$response = curl_exec($ch);

if ($response === false) {
    die('Error: ' . curl_error($ch));
}

echo "Got login form, extracting CSRF token...\n";

// Extract CSRF token from the form
if (preg_match('/<input[^>]+name="_csrf_token"[^>]+value="([^"]+)"/', $response, $matches)) {
    $csrfToken = $matches[1];
    echo "Found CSRF token: " . substr($csrfToken, 0, 10) . "...\n";
} else {
    die("Could not find CSRF token in login form.");
}

// Prepare login data
$loginData = [
    '_username' => $username,
    '_password' => $password,
    '_csrf_token' => $csrfToken
];

// Set cURL options for submitting the login form
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($loginData));
curl_setopt($ch, CURLOPT_HEADER, true);

// Execute the login request
$response = curl_exec($ch);

if ($response === false) {
    die('Error: ' . curl_error($ch));
}

// Check if we were redirected after login
$info = curl_getinfo($ch);
echo "Login response code: " . $info['http_code'] . "\n";
echo "Login redirect URL: " . $info['redirect_url'] . "\n";

// Now access the dashboard
curl_setopt($ch, CURLOPT_URL, $dashboardUrl);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HEADER, false);

// Execute the dashboard request
$dashboardResponse = curl_exec($ch);

// Close cURL resource
curl_close($ch);

// Save dashboard output to file
file_put_contents($output_file, $dashboardResponse);

// Check if dashboard content is present in the response
$isDashboard = (strpos($dashboardResponse, 'Welcome to your dashboard') !== false);
echo "Dashboard access " . ($isDashboard ? "SUCCESSFUL" : "FAILED") . "\n";
echo "Dashboard content length: " . strlen($dashboardResponse) . " bytes\n";
echo "Dashboard content saved to $output_file\n";

// For visual verification, create an HTML file showing the dashboard or login page
$visualOutput = '<!DOCTYPE html>
<html>
<head>
    <title>Login Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .failure { color: red; }
        .container { border: 1px solid #ccc; padding: 20px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Login Test Results</h1>
    <p class="' . ($isDashboard ? 'success' : 'failure') . '">
        Dashboard access ' . ($isDashboard ? 'SUCCESSFUL' : 'FAILED') . '
    </p>
    <p>Content Length: ' . strlen($dashboardResponse) . ' bytes</p>
    <div class="container">
        ' . htmlspecialchars(substr($dashboardResponse, 0, 1000)) . '
        ' . (strlen($dashboardResponse) > 1000 ? '...(truncated)' : '') . '
    </div>
</body>
</html>';

file_put_contents('dashboard_visual.html', $visualOutput);
echo "Visual report created in dashboard_visual.html\n";