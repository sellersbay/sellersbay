<?php
// Final login test with proper CSRF handling
include_once "../vendor/autoload.php";

// Symfony components
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;

// Initialize Symfony kernel to get services
$kernel = new \App\Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();
$container = $kernel->getContainer();

// Create a proper CSRF token
$tokenManager = new CsrfTokenManager();
$token = $tokenManager->getToken('authenticate')->getValue();

// Save token to session for validation
session_start();
$_SESSION['csrf_token'] = $token;

// Output debug header
?>
<!DOCTYPE html>
<html>
<head>
    <title>Final Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug { background: #f5f5f5; padding: 15px; border: 1px solid #ddd; margin: 15px 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"] { width: 300px; padding: 8px; }
        button { padding: 10px 15px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Final Login Test</h1>
    
    <div class="debug">
        <h3>Debug Information</h3>
        <pre><?php
        echo "CSRF Token generated: " . $token . "\n";
        echo "Using test credentials: sellersbay@gmail.com / powder04\n\n";
        
        // Check for authentication info
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo "POST data received:\n";
            echo "Username: " . ($_POST['_username'] ?? 'not provided') . "\n";
            echo "Password: " . (isset($_POST['_password']) ? '[HIDDEN]' : 'not provided') . "\n";
            echo "CSRF: " . ($_POST['_csrf_token'] ?? 'not provided') . "\n";
        }
        ?></pre>
    </div>
    
    <h2>Login Form</h2>
    <p>This form submits directly to the real login endpoint with proper CSRF token.</p>
    
    <form method="post" action="/login">
        <div class="form-group">
            <label for="username">Email:</label>
            <input type="text" id="username" name="_username" value="sellersbay@gmail.com" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="_password" value="powder04" required>
        </div>
        
        <div class="form-group">
            <label for="remember_me">
                <input type="checkbox" id="remember_me" name="_remember_me" checked> Remember me
            </label>
        </div>
        
        <input type="hidden" name="_csrf_token" value="<?php echo $token; ?>">
        
        <button type="submit">Log In</button>
    </form>
    
    <div style="margin-top: 30px;">
        <h3>Alternative Test Method</h3>
        <p>Click the button below to test login with a direct form submission:</p>
        <form id="autoSubmitForm" method="post" action="/login">
            <input type="hidden" name="_username" value="sellersbay@gmail.com">
            <input type="hidden" name="_password" value="powder04">
            <input type="hidden" name="_csrf_token" value="<?php echo $token; ?>">
            <input type="hidden" name="_remember_me" value="on">
            <button type="button" onclick="document.getElementById('autoSubmitForm').submit();">Auto-Submit Login</button>
        </form>
    </div>
</body>
</html>