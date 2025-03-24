<?php
// Test login script to verify authentication is working properly
// This helps us confirm users can access the dashboard

// Set up cURL session
$ch = curl_init();

// Login credentials
$email = 'sellersbay@gmail.com';
$password = 'powder04';

// First get the login page to extract CSRF token
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'test_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'test_cookies.txt');

echo "Fetching login page to extract CSRF token...\n";
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch) . "\n";
    exit;
}

// Extract CSRF token
$csrfToken = null;
if (preg_match('/<input type="hidden" name="_csrf_token" value="(.*?)">/', $response, $matches)) {
    $csrfToken = $matches[1];
    echo "Extracted CSRF token: " . substr($csrfToken, 0, 10) . "...\n";
} else {
    echo "Failed to extract CSRF token. Using simplified approach.\n";
    if (preg_match('/name="_csrf_token" value="([^"]+)"/', $response, $matches)) {
        $csrfToken = $matches[1];
        echo "Extracted CSRF token with alternate pattern: " . substr($csrfToken, 0, 10) . "...\n";
    }
}

// Submit login form
$postData = [
    '_username' => $email,
    '_password' => $password,
    '_csrf_token' => $csrfToken ?? '',
    '_remember_me' => 'on'
];

curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);

echo "Submitting login credentials...\n";
$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

echo "Login submission status code: $loginHttpCode\n";
echo "Redirected to: $effectiveUrl\n";

// Now try to access the dashboard
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/dashboard');
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

echo "Attempting to access dashboard...\n";
$dashboardResponse = curl_exec($ch);
$dashboardHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Dashboard access status code: $dashboardHttpCode\n";

// Save dashboard response for verification
file_put_contents('dashboard_response.html', $dashboardResponse);
echo "Dashboard response saved to dashboard_response.html (" . strlen($dashboardResponse) . " bytes)\n";

// Verify dashboard content
if (strlen($dashboardResponse) > 0) {
    // Check for dashboard elements
    $hasProducts = strpos($dashboardResponse, 'Total Products') !== false;
    $hasWooCommerce = strpos($dashboardResponse, 'WooCommerce Products') !== false;
    $hasRecentActivity = strpos($dashboardResponse, 'Recent Activity') !== false;
    
    if ($hasProducts && $hasWooCommerce && $hasRecentActivity) {
        echo "SUCCESS: Dashboard contains expected elements!\n";
    } else {
        echo "WARNING: Dashboard may be incomplete\n";
        echo "  - Total Products: " . ($hasProducts ? "Yes" : "No") . "\n";
        echo "  - WooCommerce Products: " . ($hasWooCommerce ? "Yes" : "No") . "\n";
        echo "  - Recent Activity: " . ($hasRecentActivity ? "Yes" : "No") . "\n";
    }
} else {
    echo "WARNING: Could not verify dashboard content\n";
}

curl_close($ch);
echo "Test completed.\n";