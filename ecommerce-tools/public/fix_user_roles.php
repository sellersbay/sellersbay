<?php
// Minimal script to fix the regular user roles - removing admin privileges

// Bootstrap the application
require __DIR__.'/../vendor/autoload.php';

// Load .env file
$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->loadEnv(__DIR__.'/../.env');

// Create the kernel with explicit environment variables
$_SERVER['APP_ENV'] = 'dev';
$_SERVER['APP_DEBUG'] = true;

$kernel = new \App\Kernel($_SERVER['APP_ENV'], $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Get entity manager
$em = $container->get('doctrine.orm.entity_manager');
$userRepo = $em->getRepository(\App\Entity\User::class);

// Find the user we need to fix
$user = $userRepo->findOneBy(['email' => 'sellersbay@gmail.com']);

if ($user) {
    echo "Found user: " . $user->getEmail() . "\n";
    echo "Current roles: " . implode(', ', $user->getRoles()) . "\n";
    
    // Remove admin role, keep only ROLE_USER
    $user->setRoles(['ROLE_USER']);
    
    // Save changes
    $em->flush();
    
    echo "User roles updated successfully.\n";
    echo "New roles: " . implode(', ', $user->getRoles()) . "\n";
} else {
    echo "User not found: sellersbay@gmail.com\n";
}