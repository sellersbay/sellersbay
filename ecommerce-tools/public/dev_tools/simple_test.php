<?php
// Simple test file that bypasses Symfony framework
// This will help determine if basic PHP serving is working

// Output basic information
echo '<h1>PHP Test Page</h1>';
echo '<p>PHP is working if you can see this page.</p>';
echo '<hr>';

// Show PHP version
echo '<h2>PHP Info</h2>';
echo '<p>PHP Version: ' . phpversion() . '</p>';

// Test if we can access environment variables
echo '<h2>Environment Tests</h2>';
echo '<p>APP_ENV: ' . (getenv('APP_ENV') ?: 'Not found') . '</p>';

// Show request information
echo '<h2>Request Info</h2>';
echo '<p>Request Method: ' . $_SERVER['REQUEST_METHOD'] . '</p>';
echo '<p>Request URI: ' . $_SERVER['REQUEST_URI'] . '</p>';

// Show server info
echo '<h2>Server Info</h2>';
echo '<p>Server Software: ' . $_SERVER['SERVER_SOFTWARE'] . '</p>';
echo '<p>Server Name: ' . $_SERVER['SERVER_NAME'] . '</p>';
echo '<p>Server Port: ' . $_SERVER['SERVER_PORT'] . '</p>';

// Don't try to connect to the database
echo '<h2>Database</h2>';
echo '<p>Not attempting database connection in this test.</p>';

// Provide a link to test Symfony routes
echo '<h2>Links</h2>';
echo '<p><a href="/login">Test Symfony Login Route</a></p>';