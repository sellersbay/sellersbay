<?php
// Simple script to create woo_categories table directly with XAMPP settings

// XAMPP default connection settings
$dbHost = 'localhost';
$dbName = 'roboseo2';
$dbUser = 'root';
$dbPassword = ''; // Default XAMPP has empty password

try {
    // Create connection
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPassword);
    // Set error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Creating woo_categories table</h1>";
    
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
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB";
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "<div style='color: green; font-weight: bold;'>Table 'woo_categories' created successfully!</div>";
    
    // Check if any existing table exists and display information
    $checkStatement = $pdo->query("SHOW TABLES LIKE 'woo_categories'");
    $tableExists = $checkStatement->rowCount() > 0;
    
    if ($tableExists) {
        echo "<div>Table verification: woo_categories exists in the database.</div>";
        
        // Get column information
        $columnsStatement = $pdo->query("DESCRIBE woo_categories");
        $columns = $columnsStatement->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Table Structure:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch(PDOException $e) {
    echo "<div style='color: red; font-weight: bold;'>Error creating table: " . $e->getMessage() . "</div>";
}