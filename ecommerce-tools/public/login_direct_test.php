<?php
// Simple direct login test script
session_start();

$loginUrl = 'http://127.0.0.1:8000/login';
$testEmail = 'sellersbay@gmail.com';
$testPassword = 'powder04';

// Display form if not submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Get CSRF token from login page
    $loginPage = file_get_contents($loginUrl);
    preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)"/', $loginPage, $matches);
    $csrfToken = $matches[1] ?? '';
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Direct Login Test</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; }
            input[type="text"], input[type="password"] { width: 100%; padding: 8px; box-sizing: border-box; }
            button { background: #0d6efd; color: white; border: none; padding: 10px 15px; cursor: pointer; }
        </style>
    </head>
    <body>
        <h1>Direct Login Test</h1>
        <p>This form submits directly to the Symfony login endpoint with proper field names.</p>
        
        <form method="POST" action="login_direct_test.php">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="text" id="email" name="_username" value="'.$testEmail.'" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="_password" value="'.$testPassword.'" required>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="_remember_me" checked> Remember me
                </label>
            </div>
            
            <input type="hidden" name="_csrf_token" value="'.$csrfToken.'">
            
            <button type="submit">Login</button>
        </form>
        
        <p>Once you submit, this script will send credentials directly to the login endpoint.</p>
    </body>
    </html>';
    
    exit;
}

// Process form submission
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    '_username' => $_POST['_username'],
    '_password' => $_POST['_password'],
    '_csrf_token' => $_POST['_csrf_token'],
    '_remember_me' => isset($_POST['_remember_me']) ? 'on' : ''
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'login_test_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'login_test_cookies.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$info = curl_getinfo($ch);

echo '<!DOCTYPE html>
<html>
<head>
    <title>Login Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .result { margin: 20px 0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Login Test Results</h1>
    
    <div class="result">
        <h2>Login Status</h2>
        <p>HTTP Status: ' . $info['http_code'] . '</p>
        <p>Final URL: ' . $info['url'] . '</p>';

if (strpos($info['url'], 'login') !== false) {
    echo '<p class="error">Login FAILED - Redirected back to login page</p>';
} else if (strpos($info['url'], 'dashboard') !== false) {
    echo '<p class="success">Login SUCCESS - Redirected to dashboard</p>';
} else {
    echo '<p>Login result unclear - check results below</p>';
}

echo '</div>
    
    <div class="result">
        <h2>Dashboard Test</h2>';

// Try to access dashboard
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/dashboard');
curl_setopt($ch, CURLOPT_POST, false);
$dashboardResponse = curl_exec($ch);
$dashboardInfo = curl_getinfo($ch);

echo '<p>Dashboard HTTP Status: ' . $dashboardInfo['http_code'] . '</p>
        <p>Dashboard URL: ' . $dashboardInfo['url'] . '</p>';

if (strpos($dashboardInfo['url'], 'login') !== false) {
    echo '<p class="error">Cannot access dashboard - redirected to login</p>';
} else if (strpos($dashboardInfo['url'], 'dashboard') !== false) {
    echo '<p class="success">Successfully accessed dashboard</p>';
}

curl_close($ch);

echo '</div>
</body>
</html>';