<?php
// Final test script to submit login credentials via POST
// This script will handle CSRF token and properly submit to the login endpoint

// Set test credentials
$email = 'sellersbay@gmail.com';
$password = 'powder04';

// Create cURL handle
$ch = curl_init();

// First get the login page to get a CSRF token
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'test_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'test_cookies.txt');

$response = curl_exec($ch);

// Extract CSRF token from the response
preg_match('/<input type="hidden" name="_csrf_token" value="(.*?)">/', $response, $matches);
$csrfToken = $matches[1] ?? 'test_token';

echo "Got CSRF token: $csrfToken<br>";

// Now submit the login form with credentials
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    '_username' => $email,
    '_password' => $password,
    '_csrf_token' => $csrfToken,
    '_remember_me' => 'on'
]);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$info = curl_getinfo($ch);

echo "Login POST status code: " . $info['http_code'] . "<br>";
echo "Final URL after login: " . $info['url'] . "<br>";

// Check if we can access the dashboard
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/dashboard');
curl_setopt($ch, CURLOPT_POST, false);
$dashboardResponse = curl_exec($ch);
$dashboardInfo = curl_getinfo($ch);

echo "Dashboard status code: " . $dashboardInfo['http_code'] . "<br>";
echo "Dashboard URL: " . $dashboardInfo['url'] . "<br>";

// Determine if login was successful
if (strpos($dashboardInfo['url'], 'login') !== false) {
    echo "<strong style='color:red'>FAILED: Login was unsuccessful - redirected to login page</strong>";
} else {
    echo "<strong style='color:green'>SUCCESS: Login was successful - accessed dashboard</strong>";
}

curl_close($ch);
?>