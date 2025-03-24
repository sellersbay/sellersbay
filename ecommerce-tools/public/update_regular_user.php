<?php
// Script to fix the regular user account by removing admin roles

require_once dirname(__DIR__).'/vendor/autoload.php';

use App\Entity\User;
use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;

// Setup kernel
$_SERVER['APP_ENV'] = 'dev';
$_SERVER['APP_DEBUG'] = true;

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
    Debug::enable();
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

// Get database connection
$container = $kernel->getContainer();
$doctrine = $container->get('doctrine');
$entityManager = $doctrine->getManager();
$userRepository = $entityManager->getRepository(User::class);
$passwordHasher = $container->get('security.password_hasher');

// Find the user we want to update
$email = 'sellersbay@gmail.com';
$user = $userRepository->findOneBy(['email' => $email]);

if (!$user) {
    echo "Error: User with email $email not found!\n";
    exit(1);
}

echo "Found user: " . $user->getEmail() . "\n";
echo "Current roles: " . implode(', ', $user->getRoles()) . "\n";

// Make sure the password is properly hashed
$password = 'password123';
$hashedPassword = $passwordHasher->hashPassword($user, $password);
$user->setPassword($hashedPassword);

// Reset roles to only ROLE_USER
$user->setRoles(['ROLE_USER']);

// Save changes
$entityManager->flush();

echo "User updated successfully!\n";
echo "Email: " . $user->getEmail() . "\n";
echo "Password: $password\n";
echo "Updated roles: " . implode(', ', $user->getRoles()) . "\n";
echo "You can now log in at /login with these credentials.\n";