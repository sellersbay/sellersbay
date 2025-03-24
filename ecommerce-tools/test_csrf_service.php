<?php
// Script to test CSRF token generation service directly

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\DependencyInjection\ContainerBuilder;

echo "Testing CSRF token generation service...\n";

try {
    // Create a Symfony kernel
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    
    // Get the CSRF token manager service
    $container = $kernel->getContainer();
    $csrfTokenManager = $container->get('security.csrf.token_manager');
    
    // Generate a token
    $token = $csrfTokenManager->getToken('authenticate');
    echo "Generated CSRF token: " . $token->getValue() . "\n";
    
    // Test token validation
    $isValid = $csrfTokenManager->isTokenValid($token);
    echo "Token is valid: " . ($isValid ? 'Yes' : 'No') . "\n";
    
    // Test with a fake token
    $fakeToken = new CsrfToken('authenticate', 'csrf-token');
    $isFakeValid = $csrfTokenManager->isTokenValid($fakeToken);
    echo "Fake token 'csrf-token' is valid: " . ($isFakeValid ? 'Yes' : 'No') . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "Test complete\n";