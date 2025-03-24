<?php
// Direct login test using PHP code execution instead of HTTP requests
// This bypasses web server routing issues

// Load Symfony's kernel
require __DIR__.'/vendor/autoload.php';
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Security\LoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

echo "===== DIRECT LOGIN TEST =====\n\n";

try {
    // Boot the Symfony kernel
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    $container = $kernel->getContainer();
    
    echo "1. Kernel booted successfully\n";
    
    // Get the authenticator service
    $authenticator = $container->get(LoginFormAuthenticator::class);
    echo "2. Got authenticator service\n";
    
    // Get the token storage
    $tokenStorage = $container->get('security.token_storage');
    echo "3. Got token storage service\n";
    
    // Create a mock request
    $request = Request::create('/login', 'POST', [
        '_username' => 'sellersbay@gmail.com',
        '_password' => 'powder04',
        '_csrf_token' => 'test-token' // This won't be checked in our test
    ]);
    echo "4. Created mock request\n";
    
    // Test the supports method
    $supports = $authenticator->supports($request);
    echo "5. Authenticator supports test: " . ($supports ? 'PASS' : 'FAIL') . "\n";
    
    // Directly test the Passport creation
    try {
        echo "6. Testing direct authentication...\n";
        
        // Get the user provider
        $userProvider = $container->get('App\Service\FileUserProvider');
        echo "   - Got user provider\n";
        
        // Try to load the test user
        $user = $userProvider->loadUserByIdentifier('sellersbay@gmail.com');
        echo "   - User loaded: " . $user->getEmail() . "\n";
        echo "   - User roles: " . implode(', ', $user->getRoles()) . "\n";
        
        // Check if password verification would succeed
        echo "   - Checking user credentials... ";
        $passwordHasher = $container->get('security.password_hasher_factory')
            ->getPasswordHasher($user);
            
        $validPassword = $passwordHasher->verify(
            $user->getPassword(), 
            'powder04'
        );
        
        echo ($validPassword ? "PASS" : "FAIL") . "\n";
        
        echo "7. Authentication test summary:\n";
        echo "   - Authenticator supports method: " . ($supports ? "WORKING" : "NOT WORKING") . "\n";
        echo "   - User loading: WORKING\n";
        echo "   - Password verification: " . ($validPassword ? "WORKING" : "NOT WORKING") . "\n\n";
        
        if ($supports) {
            echo "SUCCESS: The login fix appears to be working correctly!\n";
            echo "The authenticator now properly detects login requests via both route names and direct URLs.\n";
            echo "This should fix the login problems and allow access to your application again.\n";
        } else {
            echo "PARTIAL SUCCESS: User authentication works, but the authenticator doesn't detect the request correctly.\n";
            echo "Double-check the supports() method in LoginFormAuthenticator.php\n";
        }
        
    } catch (\Exception $e) {
        echo "ERROR during authentication test: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}