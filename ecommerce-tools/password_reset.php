<?php
/**
 * Direct Password Reset Script
 * This script creates a properly formatted bcrypt password hash and updates the user record
 * using Symfony's entity manager to bypass MySQL escaping issues.
 */

// This should be executed inside the Docker container
// Usage: docker exec -it roboseo2-php php password_reset.php

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/config/bootstrap.php';

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$entityManager = $container->get(EntityManagerInterface::class);
$passwordHasher = $container->get(UserPasswordHasherInterface::class);

// Get the user
$userRepository = $entityManager->getRepository(User::class);
$user = $userRepository->findOneBy(['email' => 'sellersbay@gmail.com']);

if (!$user) {
    echo "User not found!\n";
    exit(1);
}

// Simple test password
$plainPassword = 'test123';

// Use Symfony's password hasher to generate a proper hash
$hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);

echo "Original password: $plainPassword\n";
echo "Generated hash: $hashedPassword\n";

// Update user password
$user->setPassword($hashedPassword);

// Save to database
$entityManager->persist($user);
$entityManager->flush();

echo "Password updated successfully!\n";
echo "You can now login with:\n";
echo "Email: " . $user->getEmail() . "\n";
echo "Password: $plainPassword\n";

echo "\nPassword update completed successfully!\n";