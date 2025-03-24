<?php
// Basic PHP test file to verify functionality
require_once __DIR__ . '/vendor/autoload.php';

echo "PHP Version: " . phpversion() . "\n\n";

echo "=== PHP Extensions ===\n";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    echo $ext . "\n";
}

echo "\n=== MySQL Connection Test ===\n";
try {
    $conn = new PDO('mysql:host=roboseo2-mysql;dbname=roboseo2', 'root', 'root');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!\n";
    
    $stmt = $conn->query("SELECT COUNT(*) FROM user");
    $userCount = $stmt->fetchColumn();
    echo "User count: $userCount\n";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== Stripe Test ===\n";
try {
    if (class_exists('\\Stripe\\Stripe')) {
        echo "Stripe class exists!\n";
    } else {
        echo "Stripe class does not exist.\n";
    }
} catch (Exception $e) {
    echo "Error checking Stripe: " . $e->getMessage() . "\n";
}