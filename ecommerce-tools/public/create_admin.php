<?php

// This is a one-time script to create an admin user account
// Run this script once and then remove it for security reasons

require_once dirname(__DIR__).'/vendor/autoload.php';

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$entityManager = $container->get('doctrine')->getManager();
$userRepository = $container->get('doctrine')->getRepository(User::class);
$passwordHasher = $container->get(UserPasswordHasherInterface::class);

// Configuration for the admin user
$username = 'sellersbay';
$plainPassword = '123456';
$roles = ['ROLE_ADMIN'];

// Check if the user already exists
$existingUser = $userRepository->findOneBy(['email' => $username . '@example.com']);

if ($existingUser) {
    // Update the existing user
    $user = $existingUser;
    echo "Updating existing user: {$username}...\n";
} else {
    // Create a new user
    $user = new User();
    $user->setEmail($username . '@example.com');
    $user->setFirstName('Admin');
    $user->setLastName('User');
    echo "Creating new admin user: {$username}...\n";
}

// Set the password
$hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
$user->setPassword($hashedPassword);

// Set roles
$user->setRoles($roles);

// Set is verified
$user->setIsVerified(true);

// Add some initial credits
$user->setCredits(100);

// Persist to database
$entityManager->persist($user);
$entityManager->flush();

// Output success message
echo "Admin user '{$username}' created/updated successfully with ROLE_ADMIN privileges.\n";
echo "Email: {$username}@example.com\n";
echo "Password: {$plainPassword} (please change this as soon as possible)\n";
echo "You can now log in at: /login\n";
echo "And access the admin area at: /admin\n";
echo "\nIMPORTANT: Delete this script after use for security reasons!\n";

// Return HTTP response
$response = new Response(
    '<html><body>
    <h1>Admin User Created</h1>
    <p>Admin user "<strong>' . $username . '</strong>" created/updated successfully with ROLE_ADMIN privileges.</p>
    <p><strong>Email:</strong> ' . $username . '@example.com</p>
    <p><strong>Password:</strong> ' . $plainPassword . ' (please change this as soon as possible)</p>
    <p>You can now <a href="/login">log in</a> and access the <a href="/admin">admin area</a>.</p>
    <p><strong>IMPORTANT: Delete this script after use for security reasons!</strong></p>
    </body></html>'
);
$response->send();