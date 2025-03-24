<?php
// Simple authenticator test that only tests the supports() method directly

// Include the LoginFormAuthenticator class
require_once __DIR__ . '/src/Security/LoginFormAuthenticator.php';

// Include necessary Symfony components 
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

echo "===== SIMPLE AUTHENTICATOR TEST =====\n\n";

// Create a minimal mock of the UrlGenerator
class MockUrlGenerator implements UrlGeneratorInterface {
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string {
        if ($name === 'app_login') {
            return '/login';
        }
        return '/' . $name;
    }
    
    public function setContext(\Symfony\Component\Routing\RequestContext $context): void {}
    public function getContext(): \Symfony\Component\Routing\RequestContext 
    {
        return new \Symfony\Component\Routing\RequestContext();
    }
    public function getPathFromRoute(string $route, array $parameters = []): string 
    {
        return '/';
    }
}

try {
    // Create instance of the authenticator with mock dependencies
    $authenticator = new App\Security\LoginFormAuthenticator(new MockUrlGenerator());
    echo "1. Created authenticator instance\n";
    
    // Test route-based detection (simulates Symfony routing)
    $routeRequest = Request::create('/login', 'POST');
    $routeRequest->attributes->set('_route', 'app_login');
    
    $supportsRoute = $authenticator->supports($routeRequest);
    echo "2. Route-based detection test (app_login): " . ($supportsRoute ? "PASS" : "FAIL") . "\n";
    
    // Test direct URL detection
    $pathRequest = Request::create('/login', 'POST');
    $supportsPath = $authenticator->supports($pathRequest);
    echo "3. Direct URL detection test (/login): " . ($supportsPath ? "PASS" : "FAIL") . "\n";
    
    // Test non-login path
    $wrongPathRequest = Request::create('/dashboard', 'POST');
    $supportsWrongPath = $authenticator->supports($wrongPathRequest);
    echo "4. Non-login path test (/dashboard): " . (!$supportsWrongPath ? "PASS" : "FAIL") . "\n";
    
    // Test non-POST method
    $getRequest = Request::create('/login', 'GET');
    $supportsGet = $authenticator->supports($getRequest);
    echo "5. Non-POST method test: " . (!$supportsGet ? "PASS" : "FAIL") . "\n\n";
    
    // Determine overall test result
    if ($supportsRoute && $supportsPath && !$supportsWrongPath && !$supportsGet) {
        echo "SUCCESS: All authenticator support tests passed!\n";
        echo "The fix to LoginFormAuthenticator is working correctly.\n";
        echo "It now properly detects login requests via both route detection and direct URL access.\n";
    } else {
        echo "FAIL: Some authenticator support tests failed.\n";
        if (!$supportsRoute) echo "- Route-based detection not working\n";
        if (!$supportsPath) echo "- Direct path detection not working\n";
        if ($supportsWrongPath) echo "- Wrong path incorrectly supported\n";
        if ($supportsGet) echo "- GET method incorrectly supported\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}