<?php
/**
 * Database Connection Test
 * 
 * This script verifies the connection to the MySQL database
 * using the credentials from the .env.local file.
 */

echo "Database Connection Test\n";
echo "========================\n\n";

// Read the database connection string from .env.local
$envFile = file_get_contents('.env.local');
if (!$envFile) {
    die("Error: Could not read .env.local file\n");
}

// Extract the DATABASE_URL
if (!preg_match('/DATABASE_URL=([^\r\n]+)/', $envFile, $matches)) {
    die("Error: Could not find DATABASE_URL in .env.local file\n");
}

$dbUrl = $matches[1];
echo "Found DATABASE_URL: $dbUrl\n";

// Parse the database URL
if (!preg_match('/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/([^?]+)/', $dbUrl, $parts)) {
    die("Error: Could not parse DATABASE_URL\n");
}

$username = $parts[1];
$password = $parts[2];
$host = $parts[3];
$port = $parts[4];
$dbname = $parts[5];

echo "Parsed connection details:\n";
echo "- Host: $host\n";
echo "- Port: $port\n";
echo "- User: $username\n";
echo "- Password: " . str_repeat('*', strlen($password)) . "\n";
echo "- Database: $dbname\n\n";

// Test the connection
echo "Attempting to connect to database...\n";
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "SUCCESS: Connected to database!\n\n";
    
    // Check if the user table exists and has records
    echo "Checking for user table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'user'");
    if ($stmt->rowCount() > 0) {
        echo "User table found.\n";
        
        // Check for our test user
        $stmt = $pdo->prepare("SELECT id, email, username, roles FROM user WHERE email = ?");
        $stmt->execute(['sellersbay@gmail.com']);
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "User 'sellersbay@gmail.com' found!\n";
            echo "- ID: " . $row['id'] . "\n";
            echo "- Username: " . ($row['username'] ?? 'N/A') . "\n";
            echo "- Roles: " . ($row['roles'] ?? 'N/A') . "\n";
        } else {
            echo "ERROR: User 'sellersbay@gmail.com' NOT found in database!\n";
            echo "This explains why login is failing.\n";
        }
    } else {
        echo "ERROR: User table NOT found in database!\n";
        echo "This explains why login is failing.\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: Could not connect to database!\n";
    echo "Error message: " . $e->getMessage() . "\n";
    
    // Suggest a solution based on the error
    if (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "\nPossible solution:\n";
        echo "- Make sure the MySQL container is running\n";
        echo "- Check if port 3306 is properly mapped in docker-compose.yml\n";
        echo "- Try using 'localhost' instead of '127.0.0.1' in DATABASE_URL\n";
    }
}