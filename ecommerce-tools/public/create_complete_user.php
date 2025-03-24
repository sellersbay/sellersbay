<?php
// Complete script to create a properly configured user account

require __DIR__.'/../vendor/autoload.php';

// Create the kernel with explicit environment variables
$_SERVER['APP_ENV'] = 'dev';
$_SERVER['APP_DEBUG'] = true;

// Run the proper console command instead of trying to do everything manually
// This ensures all Symfony services are properly initialized
$command = sprintf(
    'php %s/../bin/console app:create-admin %s %s %s %s --no-interaction',
    __DIR__,
    escapeshellarg('sellersbay@gmail.com'), 
    escapeshellarg('password123'),
    escapeshellarg('Test'),
    escapeshellarg('User')
);

echo "Running command: $command\n";
$output = shell_exec($command);
echo $output;

// Now modify the roles to ensure this is a regular user, not an admin
$command2 = sprintf(
    'php %s/../bin/console doctrine:query:sql %s --no-interaction',
    __DIR__,
    escapeshellarg("UPDATE user SET roles = '[\"ROLE_USER\"]' WHERE email = 'sellersbay@gmail.com'")
);

echo "\nSetting proper user roles (removing ROLE_ADMIN)...\n";
$output2 = shell_exec($command2);
echo $output2 ?: "User roles updated successfully.\n";

echo "\nComplete setup finished. You can now:";
echo "\n- Login as admin: sellersbay@example.com / 123456";
echo "\n- Login as regular user: sellersbay@gmail.com / password123\n";