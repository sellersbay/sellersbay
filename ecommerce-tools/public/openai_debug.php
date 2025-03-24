<?php
// Simple debug script to check OpenAI API key

// Load Symfony's environment
require_once dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/config/bootstrap.php';

echo "<h1>OpenAI API Debug</h1>";

// Check if OpenAI API key is defined
$openaiApiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?? null;

if ($openaiApiKey) {
    $keyLength = strlen($openaiApiKey);
    $maskedKey = substr($openaiApiKey, 0, 10) . '...' . substr($openaiApiKey, -4);
    echo "<p>API Key (masked): " . htmlspecialchars($maskedKey) . " (Length: $keyLength)</p>";
    
    // Check if using placeholder
    if ($openaiApiKey === 'sk_test_placeholder_replace_with_actual_key') {
        echo "<p style='color:red'>Error: Using placeholder API key from .env file.</p>";
    } else {
        echo "<p style='color:green'>Using a non-placeholder API key.</p>";
    }
    
    // Make simple API request to test
    $ch = curl_init('https://api.openai.com/v1/models');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $openaiApiKey,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>API Response Code: $httpCode</p>";
    
    if ($httpCode === 200) {
        echo "<p style='color:green'>Success! API connection is working.</p>";
    } else {
        echo "<p style='color:red'>Error: API connection failed.</p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 300)) . "...</pre>";
    }
} else {
    echo "<p style='color:red'>Error: OpenAI API key not found in environment variables.</p>";
}

// Show env files that exist
echo "<h2>Environment Files</h2>";
$files = [
    '.env' => file_exists(dirname(__DIR__) . '/.env'),
    '.env.local' => file_exists(dirname(__DIR__) . '/.env.local'),
    '.env.dev' => file_exists(dirname(__DIR__) . '/.env.dev'),
    '.env.dev.local' => file_exists(dirname(__DIR__) . '/.env.dev.local'),
];

foreach ($files as $file => $exists) {
    echo "<li>$file: " . ($exists ? "Exists" : "Not found") . "</li>";
}
?>