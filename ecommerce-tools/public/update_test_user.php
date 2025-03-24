<?php
// Simple script to update test user password

require_once dirname(__DIR__).'/vendor/autoload.php';

use App\Entity\User;
use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\ErrorHandler\Debug;

// Environment setup similar to index.php
if ($_SERVER['APP_DEBUG']) {
    umask(0000);
    Debug::enable();
}

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? false));
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();
$passwordHasher = $container->get('security.user_password_hasher');

// Test user email to find
$testUserEmail = 'sellersbay@gmail.com'; // The test user account being used

// Find the test user
$userRepository = $entityManager->getRepository(User::class);
$testUser = $userRepository->findOneBy(['email' => $testUserEmail]);

$updatedUser = false;

if ($testUser) {
    echo "Updating existing test user: " . $testUserEmail . "\n";
    
    // Update password for existing user
    $hashedPassword = $passwordHasher->hashPassword($testUser, 'password123');
    $testUser->setPassword($hashedPassword);
    
    // Ensure user has proper roles
    $roles = $testUser->getRoles();
    if (!in_array('ROLE_USER', $roles)) {
        $roles[] = 'ROLE_USER';
        $testUser->setRoles(array_unique($roles));
    }
    
    $updatedUser = true;
} else {
    echo "Creating new test user: " . $testUserEmail . "\n";
    
    // Create new test user
    $testUser = new User();
    $testUser->setEmail($testUserEmail);
    $testUser->setFirstName('Test');
    $testUser->setLastName('User');
    $testUser->setRoles(['ROLE_USER']);
    $testUser->setIsVerified(true);
    $testUser->setCreatedAt(new \DateTimeImmutable());
    $testUser->setUpdatedAt(new \DateTimeImmutable());
    $testUser->setCredits(50);
    
    // Set hashed password
    $hashedPassword = $passwordHasher->hashPassword($testUser, 'password123');
    $testUser->setPassword($hashedPassword);
    
    $updatedUser = true;
}

if ($updatedUser) {
    // Save changes
    $entityManager->persist($testUser);
    $entityManager->flush();
    
    echo "Test user updated/created successfully!\n";
    echo "Email: " . $testUserEmail . "\n";
    echo "Password: password123\n";
    echo "Roles: " . implode(', ', $testUser->getRoles()) . "\n";
    echo "You can now log in at /login with these credentials.\n";
} else {
    echo "Error: Could not update/create test user.\n";
}