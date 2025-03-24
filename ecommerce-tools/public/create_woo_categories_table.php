<?php
// Simple script to create woo_categories table directly

// Get database parameters from Symfony configuration
require dirname(__DIR__).'/vendor/autoload.php';
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$params = $container->get('doctrine.dbal.default_connection')->getParams();

// Connect to database
$dbHost = $params['host'];
$dbName = $params['dbname'];
$dbUser = $params['user'];
$dbPassword = $params['password'];

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // SQL to create the woo_categories table
    $sql = "CREATE TABLE IF NOT EXISTS woo_categories (
        id INT AUTO_INCREMENT NOT NULL,
        name VARCHAR(255) NOT NULL,
        woocommerce_id INT DEFAULT NULL,
        owner_id INT NOT NULL,
        store_url VARCHAR(255) DEFAULT NULL,
        original_data JSON DEFAULT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        slug VARCHAR(255) DEFAULT NULL,
        count INT DEFAULT NULL,
        PRIMARY KEY(id),
        INDEX idx_owner_store (owner_id, store_url)
    )";
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "Table 'woo_categories' created successfully!";
} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}