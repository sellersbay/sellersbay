<?php
// Simple login test for dashboard access
$loginUrl = 'http://127.0.0.1:8000/login';
$dashboardUrl = 'http://127.0.0.1:8000/dashboard';
$email = 'sellersbay@gmail.com';
$password = 'powder04';

// Initialize CURL session
$ch = curl_init();

// Set cookie storage
curl_setopt($ch, CURLOPT_COOKIEJAR, 'dashboard_test_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'dashboard_test_cookies.txt');

// Get the login page first to grab the CSRF token
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);

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

$loginResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

echo "Login HTTP Code: $httpCode\n";
echo "Final URL after login: $finalUrl\n";

// Now try to access the dashboard
curl_setopt($ch, CURLOPT_URL, $dashboardUrl);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_HEADER, false);

$dashboardResponse = curl_exec($ch);

// Save dashboard page for inspection
file_put_contents('dashboard_response.html', $dashboardResponse);

echo "Dashboard response saved to dashboard_response.html\n";
echo "Dashboard response size: " . strlen($dashboardResponse) . " bytes\n";

// Check if dashboard content is present
if (strpos($dashboardResponse, 'Welcome') !== false &&
    strpos($dashboardResponse, 'Total Products') !== false &&
    strpos($dashboardResponse, 'WooCommerce Products') !== false) {
    echo "SUCCESS: Dashboard content is displayed correctly!\n";
} else {
    echo "WARNING: Could not verify all dashboard elements\n";
}

curl_close($ch);

echo "Test completed.\n";
?>