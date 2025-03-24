<?php
require_once '../vendor/autoload.php';

// Core Symfony components
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

// Get the Kernel
$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Get the authenticator and user provider services
$authenticator = $container->get('App\Security\LoginFormAuthenticator');
$userProvider = $container->get('App\Service\FileUserProvider');

echo "<h1>Auth Debug Info</h1>";
echo "<pre>";

// Debug info
echo "Testing authenticator support...\n";
$request = Request::createFromGlobals();
$supportsResult = $authenticator->supports($request);
echo "supports() result: " . var_export($supportsResult, true) . "\n\n";

// Test authenticate method with hardcoded credentials
echo "Testing authenticate method for test user...\n";
$testEmail = 'sellersbay@gmail.com';
$testPassword = 'powder04';

// Try manual authentication
try {
    // Hardcoded values for testing
    $_POST['_username'] = $testEmail;
    $_POST['_password'] = $testPassword;
    $_POST['_csrf_token'] = 'debug-token';
    
    $newRequest = Request::createFromGlobals();
    echo "Request params: " . print_r($newRequest->request->all(), true) . "\n";
    
    echo "Check if authenticator supports this request: ";
    $supportsNewRequest = $authenticator->supports($newRequest);
    echo var_export($supportsNewRequest, true) . "\n";
    
    if ($supportsNewRequest) {
        echo "Authenticator supports the request, attempting authentication...\n";
        
        try {
            $passport = $authenticator->authenticate($newRequest);
            echo "Authentication succeeded!\n";
            
            // Try to load the user
            $userBadge = $passport->getBadge(UserBadge::class);
            $userIdentifier = $userBadge->getUserIdentifier();
            echo "User identifier: $userIdentifier\n";
            
            try {
                $user = $userProvider->loadUserByIdentifier($userIdentifier);
                echo "User loaded successfully: \n";
                echo "  Email: " . $user->getEmail() . "\n";
                echo "  Roles: " . implode(', ', $user->getRoles()) . "\n";
            } catch (\Exception $e) {
                echo "Error loading user: " . $e->getMessage() . "\n";
            }
        } catch (\Exception $e) {
            echo "Authentication failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Authenticator does not support the request.\n";
    }
} catch (\Exception $e) {
    echo "Error during authentication test: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";