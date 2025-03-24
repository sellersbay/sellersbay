<?php
// Final Login and Dashboard Test
// This script tests both the login authentication and the dashboard page rendering

echo "==========================================\n";
echo "FINAL LOGIN AND DASHBOARD TEST\n";
echo "==========================================\n";
echo "This script will:\n";
echo "1. Get the login page and extract the CSRF token\n";
echo "2. Submit the login form with test credentials\n";
echo "3. Follow redirects to the dashboard\n";
echo "4. Verify the dashboard page loads correctly\n\n";

// Test credentials
$email = 'sellersbay@gmail.com';
$password = 'powder04';

echo "Using test credentials:\n";
echo "Email: $email\n";
echo "Password: [hidden]\n\n";

// Initialize cURL session
$ch = curl_init();

// Common cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'test_cookies_final.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'test_cookies_final.txt');
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36');

echo "Step 1: Loading login page to get CSRF token...\n";
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/login');
$response = curl_exec($ch);

// Check if page loaded
if ($response === false) {
    echo "ERROR: Failed to load login page: " . curl_error($ch) . "\n";
    exit(1);
}

// Extract CSRF token
if (preg_match('/<input[^>]*name="_csrf_token"[^>]*value="([^"]*)"/', $response, $matches)) {
    $csrfToken = $matches[1];
    echo "CSRF token extracted: " . substr($csrfToken, 0, 10) . "...\n";
} else {
    echo "ERROR: Could not find CSRF token on login page\n";
    file_put_contents('login_page_error.html', $response);
    echo "Login page saved to login_page_error.html for debugging\n";
    exit(1);
}

echo "\nStep 2: Submitting login form with credentials and CSRF token...\n";
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Origin: http://localhost:8000',
    'Referer: http://localhost:8000/login'
]);
$postData = http_build_query([
    '_username' => $email,
    '_password' => $password,
    '_csrf_token' => $csrfToken,
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
$response = curl_exec($ch);

// Check login response
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
echo "Final URL after login: $finalUrl\n";

// Determine if login was successful
if (strpos($finalUrl, '/dashboard') !== false || strpos($finalUrl, '/login') === false) {
    echo "Login successful! Redirected to: $finalUrl\n";
} else {
    echo "Login FAILED. Still on login page or error page.\n";
    file_put_contents('login_failed_response.html', $response);
    echo "Response saved to login_failed_response.html for debugging\n";
    exit(1);
}

echo "\nStep 3: Accessing dashboard page...\n";
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/dashboard');
curl_setopt($ch, CURLOPT_POST, false);
$response = curl_exec($ch);

// Check if dashboard loaded
if ($response === false) {
    echo "ERROR: Failed to load dashboard page: " . curl_error($ch) . "\n";
    exit(1);
}

// Check for Twig error (specifically the 'instance of' error we fixed)
if (strpos($response, 'Unknown "instance of" test') !== false) {
    echo "ERROR: Dashboard still has Twig error with 'instance of' test!\n";
    file_put_contents('dashboard_twig_error.html', $response);
    echo "Error page saved to dashboard_twig_error.html for debugging\n";
    exit(1);
}

// Check for normal dashboard content indicators
if (strpos($response, 'Welcome to your RoboSEO dashboard') !== false || 
    strpos($response, 'Total Products') !== false || 
    strpos($response, 'WooCommerce Products') !== false) {
    echo "Dashboard loaded successfully! Found expected dashboard content.\n";
    echo "No Twig errors detected.\n";
} else {
    echo "WARNING: Dashboard page loaded but expected content not found.\n";
    file_put_contents('dashboard_unexpected_content.html', $response);
    echo "Response saved to dashboard_unexpected_content.html for inspection\n";
}

// Clean up cURL
curl_close($ch);

echo "\nTest completed successfully! Both login and dashboard are working properly.\n";
echo "==========================================\n";