<?php
// Simple script to check MySQL connection and database existence

echo "<!DOCTYPE html>
<html>
<head>
    <title>MySQL Connection Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 4px; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 4px; }
        .info { background: #e2f0fb; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>MySQL Connection Check</h1>";

// Step 1: Check if MySQL is running on port 3306
echo "<h2>1. MySQL Server Status</h2>";
$connection = @fsockopen('127.0.0.1', 3306, $errno, $errstr, 5);
if (is_resource($connection)) {
    echo "<div class='success'>MySQL server is running on port 3306!</div>";
    fclose($connection);
} else {
    echo "<div class='error'>MySQL server is not reachable on port 3306: $errstr ($errno)</div>";
    echo "<p>Possible reasons:</p>
    <ul>
        <li>XAMPP MySQL service is not running</li>
        <li>MySQL is running on a different port</li>
        <li>Firewall is blocking connections</li>
    </ul>";
    
    echo "</body></html>";
    exit;
}

// Step 2: Try to connect to MySQL
echo "<h2>2. MySQL Connection Test</h2>";
try {
    $host = '127.0.0.1';
    $username = 'root';
    $password = ''; // Default XAMPP password is empty
    
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<div class='success'>Successfully connected to MySQL server!</div>";
    
    // Step 3: Check if 'roboseo2' database exists
    echo "<h2>3. Database Check</h2>";
    
    $result = $conn->query("SHOW DATABASES LIKE 'roboseo2'");
    if ($result->num_rows > 0) {
        echo "<div class='success'>Database 'roboseo2' exists!</div>";
        
        // Connect to the roboseo2 database specifically
        $conn->select_db('roboseo2');
        
        // Check tables
        echo "<h3>Tables in 'roboseo2' database:</h3>";
        $tables = $conn->query("SHOW TABLES");
        
        if ($tables->num_rows > 0) {
            echo "<ul>";
            while ($table = $tables->fetch_array()) {
                echo "<li>" . $table[0] . "</li>";
            }
            echo "</ul>";
            
            // Look specifically for WooCommerce related tables
            $wooTables = $conn->query("SHOW TABLES LIKE 'woo%'");
            if ($wooTables->num_rows > 0) {
                echo "<div class='success'>Found " . $wooTables->num_rows . " WooCommerce related tables!</div>";
            } else {
                echo "<div class='error'>No WooCommerce related tables found. This could explain why the WooCommerce dashboard won't load.</div>";
            }
        } else {
            echo "<div class='error'>No tables found in the 'roboseo2' database.</div>";
        }
        
    } else {
        echo "<div class='error'>Database 'roboseo2' does not exist!</div>";
        echo "<p>This is likely why the WooCommerce dashboard won't load. You need to create the database.</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='error'>MySQL connection error: " . $e->getMessage() . "</div>";
}

echo "<h2>Recommendation</h2>";
echo "<div class='info'>
<p>Based on the checks above, here are some troubleshooting steps:</p>
<ol>
    <li>Ensure XAMPP is running (Apache and MySQL services)</li>
    <li>Check if the 'roboseo2' database exists in MySQL</li>
    <li>Verify that your .env file contains the correct DATABASE_URL setting:
        <pre>DATABASE_URL=\"mysql://root@127.0.0.1:3306/roboseo2\"</pre>
    </li>
    <li>Make sure there are no other configuration files overriding the database settings</li>
    <li>Run Symfony migrations if the database exists but has no tables:
        <pre>php bin/console doctrine:migrations:migrate</pre>
    </li>
</ol>
</div>";

echo "</body></html>";