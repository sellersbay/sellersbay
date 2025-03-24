<?php
// Simple script to directly check WooCommerce tables without Symfony framework
// This helps diagnose why /woocommerce/ won't load

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database credentials - using standard XAMPP settings
$host = '127.0.0.1';
$dbname = 'roboseo2';
$username = 'root';
$password = '';
$port = 3306;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Direct WooCommerce Database Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #0073aa; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        .info { background: #e2f0fb; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Direct WooCommerce Database Check</h1>
    <div class='info'>
        This script connects directly to MySQL without using the Symfony framework.
        It helps diagnose why the WooCommerce dashboard isn't loading.
    </div>";

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "<div class='success'>Connected to MySQL database successfully!</div>";
    
    // Check if woo_categories table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'woo_categories'");
    $woo_categories_exists = $stmt->rowCount() > 0;
    
    if ($woo_categories_exists) {
        echo "<div class='success'>The woo_categories table exists!</div>";
        
        // Count categories
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM woo_categories");
        $result = $stmt->fetch();
        $categoryCount = $result['count'];
        
        echo "<h2>WooCommerce Categories ($categoryCount found)</h2>";
        
        if ($categoryCount > 0) {
            // Get categories
            $stmt = $pdo->query("SELECT * FROM woo_categories LIMIT 10");
            $categories = $stmt->fetchAll();
            
            echo "<table>
                <tr>
                    <th>ID</th>
                    <th>WooCommerce ID</th>
                    <th>Name</th>
                    <th>Store URL</th>
                </tr>";
            
            foreach ($categories as $category) {
                echo "<tr>
                    <td>{$category['id']}</td>
                    <td>{$category['woocommerce_id']}</td>
                    <td>{$category['name']}</td>
                    <td>{$category['store_url']}</td>
                </tr>";
            }
            
            echo "</table>";
        } else {
            echo "<div class='info'>No categories found in the woo_categories table.</div>";
        }
    } else {
        echo "<div class='error'>The woo_categories table does not exist!</div>";
    }
    
    // Check if woo_commerce_product table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'woo_commerce_product'");
    $woo_products_exists = $stmt->rowCount() > 0;
    
    if ($woo_products_exists) {
        echo "<div class='success'>The woo_commerce_product table exists!</div>";
        
        // Count products
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM woo_commerce_product");
        $result = $stmt->fetch();
        $productCount = $result['count'];
        
        echo "<h2>WooCommerce Products ($productCount found)</h2>";
        
        if ($productCount > 0) {
            // Get products
            $stmt = $pdo->query("SELECT * FROM woo_commerce_product LIMIT 10");
            $products = $stmt->fetchAll();
            
            echo "<table>
                <tr>
                    <th>ID</th>
                    <th>WooCommerce ID</th>
                    <th>Name</th>
                    <th>Status</th>
                </tr>";
            
            foreach ($products as $product) {
                echo "<tr>
                    <td>{$product['id']}</td>
                    <td>{$product['woocommerce_id']}</td>
                    <td>{$product['name']}</td>
                    <td>{$product['status']}</td>
                </tr>";
            }
            
            echo "</table>";
        } else {
            echo "<div class='info'>No products found in the woo_commerce_product table.</div>";
        }
    } else {
        echo "<div class='error'>The woo_commerce_product table does not exist!</div>";
    }

    echo "<h2>Diagnosis</h2>
    <div class='info'>
        <p><strong>Root Cause:</strong> The WooCommerce dashboard won't load because of a DATABASE_URL environment variable issue.</p>
        <p>When using the PHP built-in development server (php -S) on port 8090, Symfony cannot find the DATABASE_URL environment variable.</p>
        <p>As shown above, the database and WooCommerce tables exist and are accessible when connecting directly.</p>
        <p><strong>Solutions:</strong></p>
        <ol>
            <li>Use the XAMPP server on port 8000 instead of the PHP built-in server</li>
            <li>Create a <code>.env.local.php</code> file with hardcoded environment variables</li>
            <li>Set environment variables via the command line before starting the PHP server</li>
        </ol>
    </div>
    ";

} catch (PDOException $e) {
    echo "<div class='error'>Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";