<?php
/**
 * Enhanced Login Test Script
 * 
 * This script will:
 * 1. Get the login page and extract the CSRF token
 * 2. Submit a proper login request with credentials
 * 3. Follow redirects to the dashboard
 * 4. Save the dashboard HTML content
 */

// Configuration
$baseUrl = 'http://localhost:8000';
$username = 'sellersbay@gmail.com';
$password = 'powder04';
$outputFile = 'dashboard_content.html';

echo "Starting enhanced login test...\n";

// Initialize cURL session
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

// Step 1: Get login page and extract CSRF token
echo "Fetching login page...\n";
curl_setopt($ch, CURLOPT_URL, "$baseUrl/login");
$response = curl_exec($ch);

if ($response === false) {
    die('Error fetching login page: ' . curl_error($ch));
}

// Extract CSRF token
$csrfToken = null;
if (preg_match('/<input[^>]*name="_csrf_token"[^>]*value="([^"]*)"/', $response, $matches)) {
    $csrfToken = $matches[1];
    echo "Found CSRF token: " . substr($csrfToken, 0, 10) . "...\n";
} else {
    die("Could not find CSRF token in login page");
}

// Step 2: Submit login form
echo "Submitting login credentials...\n";
curl_setopt($ch, CURLOPT_URL, "$baseUrl/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_csrf_token' => $csrfToken,
    '_username' => $username,
    '_password' => $password,
    '_remember_me' => 'on'
]));

// Get HTTP headers to check for redirects
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);

if ($response === false) {
    die('Error submitting login form: ' . curl_error($ch));
}

// Parse response headers and body
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);

// Check for login success by looking for redirect
$location = null;
if (preg_match('/Location: ([^\r\n]*)/', $header, $matches)) {
    $location = $matches[1];
    echo "Login redirect to: $location\n";
} else {
    // Check if we're still on login page (error)
    if (strpos($body, 'Invalid credentials') !== false) {
        die("Login failed: Invalid credentials");
    } else if (strpos($body, 'Invalid CSRF token') !== false) {
        die("Login failed: Invalid CSRF token");
    }
}

// Step 3: Follow redirect to dashboard
echo "Accessing dashboard...\n";
curl_setopt($ch, CURLOPT_URL, "$baseUrl/dashboard");
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$dashboardContent = curl_exec($ch);

if ($dashboardContent === false) {
    die('Error fetching dashboard: ' . curl_error($ch));
}

// Analyze the dashboard content
$isLoggedIn = strpos($dashboardContent, 'Sign in') === false && 
              strpos($dashboardContent, 'Login') === false;
$isDashboard = strpos($dashboardContent, 'Dashboard') !== false ||
               strpos($dashboardContent, 'Recent Products') !== false ||
               strpos($dashboardContent, 'Credits') !== false;

echo "Dashboard length: " . strlen($dashboardContent) . " bytes\n";
echo "Is logged in: " . ($isLoggedIn ? "Yes" : "No") . "\n";
echo "Is dashboard: " . ($isDashboard ? "Yes" : "No") . "\n";

// Save dashboard content for analysis
file_put_contents($outputFile, $dashboardContent);
echo "Dashboard content saved to $outputFile\n";

// Create a visual representation file
$htmlOutput = '<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Test Result</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        h1 { color: #333; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background-color: #f8f9fa; padding: 15px; border-radius: 4px; overflow: auto; }
        .content { margin-top: 20px; }
        h2 { margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dashboard Login Test Results</h1>
        
        <div class="results">
            <p><strong>Login Status:</strong> ' . 
            ($isLoggedIn ? '<span class="success">Logged In</span>' : '<span class="error">Not Logged In</span>') . 
            '</p>
            <p><strong>Dashboard Content:</strong> ' . 
            ($isDashboard ? '<span class="success">Dashboard Content Found</span>' : '<span class="error">No Dashboard Content</span>') . 
            '</p>
            <p><strong>Content Length:</strong> ' . strlen($dashboardContent) . ' bytes</p>
        </div>
        
        <h2>Dashboard Content Preview</h2>
        <div class="content">
            <pre>' . htmlspecialchars(substr($dashboardContent, 0, 1000)) . '...</pre>
        </div>
        
        <p><em>Note: This is not a screenshot but rather the raw HTML content of the dashboard page after login.</em></p>
    </div>
</body>
</html>';

file_put_contents('dashboard_results.html', $htmlOutput);
echo "Visual report created in dashboard_results.html\n";

// Clean up
curl_close($ch);
echo "Test completed.\n";