<?php
// Simple database connection test

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 4px; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 4px; }
        .info { background: #e2f0fb; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Database Connection Test</h1>";

// Get database details from .env file
$envFile = file_get_contents(__DIR__ . '/../.env');
$dbUrl = '';

// Extract DATABASE_URL from .env
if (preg_match('/DATABASE_URL="([^"]+)"/', $envFile, $matches)) {
    $dbUrl = $matches[1];
    echo "<div class='info'>Found DATABASE_URL in .env file: <code>$dbUrl</code></div>";
} else {
    echo "<div class='error'>Could not find DATABASE_URL in .env file.</div>";
    exit;
}

// Parse the DATABASE_URL
try {
    // Parse URL parts
    if (strpos($dbUrl, 'mysql://') === 0) {
        $dbUrl = str_replace('mysql://', '', $dbUrl);
        $parts = parse_url('mysql://' . $dbUrl);

        // Extract credentials and connection details
        $host = $parts['host'] ?? '127.0.0.1';
        $port = $parts['port'] ?? 3306;
        $username = $parts['user'] ?? 'root';
        $password = $parts['pass'] ?? '';
        $dbname = ltrim($parts['path'] ?? '', '/');

        echo "<h2>Connection Details</h2>
        <ul>
            <li>Host: $host</li>
            <li>Port: $port</li>
            <li>Username: $username</li>
            <li>Password: " . (empty($password) ? "(empty)" : "(hidden)") . "</li>
            <li>Database: $dbname</li>
        </ul>";

        // Attempt MySQL connection
        echo "<h2>Connection Test</h2>";
        
        try {
            $conn = new mysqli($host, $username, $password, $dbname, $port);
            
            // Check connection
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            echo "<div class='success'>Successfully connected to the database!</div>";
            
            // Test executing a simple query
            echo "<h3>Database Tables</h3>";
            $result = $conn->query("SHOW TABLES");
            
            if ($result) {
                echo "<ul>";
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_array()) {
                        echo "<li>{$row[0]}</li>";
                    }
                } else {
                    echo "<li>(No tables found)</li>";
                }
                echo "</ul>";
            } else {
                echo "<div class='error'>Error showing tables: " . $conn->error . "</div>";
            }
            
            // Close connection
            $conn->close();
            
        } catch (Exception $e) {
            echo "<div class='error'>Database connection failed: " . $e->getMessage() . "</div>";
            echo "<h3>Troubleshooting</h3>
            <ul>
                <li>Check if MySQL server is running</li>
                <li>Verify username and password</li>
                <li>Make sure database '$dbname' exists</li>
                <li>Check firewall settings</li>
            </ul>";
        }
    } else {
        echo "<div class='error'>Unsupported database type in DATABASE_URL. Only MySQL is supported by this test script.</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>Error parsing DATABASE_URL: " . $e->getMessage() . "</div>";
}

echo "</body></html>";