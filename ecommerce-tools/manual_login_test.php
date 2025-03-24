<?php
// Simple script to test our manual authentication implementation

echo "Testing manual login with cURL...\n";

// Initialize cURL session
$ch = curl_init();

// Set options for submitting login credentials
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_username' => 'sellersbay@gmail.com',
    '_password' => 'powder04'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'test_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'test_cookies.txt');
curl_setopt($ch, CURLOPT_HEADER, true);

// Execute the request
$response = curl_exec($ch);

// Split headers and body
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

// Print request info
$info = curl_getinfo($ch);
echo "Response code: " . $info['http_code'] . "\n";
echo "Redirect URL: " . $info['redirect_url'] . "\n";
echo "Effective URL: " . $info['url'] . "\n\n";

// Check if we've been redirected to the dashboard
$is_dashboard = strpos($info['url'], 'dashboard') !== false;
echo "Redirected to dashboard: " . ($is_dashboard ? "YES" : "NO") . "\n";

// Look for dashboard content markers
$has_dashboard_content = strpos($body, 'Welcome') !== false || strpos($body, 'Dashboard') !== false;
echo "Contains dashboard content: " . ($has_dashboard_content ? "YES" : "NO") . "\n\n";

// Save response to file for inspection
file_put_contents('manual_login_response.html', $body);
echo "Response saved to manual_login_response.html\n";

// Now try to access dashboard directly with cookies
echo "\nAttempting to access dashboard directly...\n";
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/dashboard');
curl_setopt($ch, CURLOPT_POST, false);
$dashboard_response = curl_exec($ch);

// Split headers and body
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$dashboard_body = substr($dashboard_response, $header_size);

// Check dashboard access
$info = curl_getinfo($ch);
echo "Response code: " . $info['http_code'] . "\n";
echo "Final URL: " . $info['url'] . "\n";

// Check if we're on the dashboard or redirected to login
$is_dashboard = strpos($info['url'], 'dashboard') !== false;
echo "On dashboard page: " . ($is_dashboard ? "YES" : "NO") . "\n";

// Look for dashboard content markers
$has_dashboard_content = strpos($dashboard_body, 'Welcome') !== false || strpos($dashboard_body, 'Dashboard') !== false;
echo "Contains dashboard content: " . ($has_dashboard_content ? "YES" : "NO") . "\n";

// Save dashboard response
file_put_contents('dashboard_direct_access.html', $dashboard_body);
echo "Dashboard response saved to dashboard_direct_access.html\n";

// Clean up
curl_close($ch);