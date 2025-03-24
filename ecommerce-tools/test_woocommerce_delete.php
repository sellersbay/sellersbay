<?php
// Test script for WooCommerce product deletion functionality

// Configuration
$baseUrl = 'http://localhost:8000';
$loginUrl = $baseUrl . '/login';
$dashboardUrl = $baseUrl . '/woocommerce/';
$deleteUrl = $baseUrl . '/woocommerce/delete';
$username = 'sellersbay@gmail.com';
$password = 'powder04';

// Initialize cURL session
$ch = curl_init();

// Configure cURL to store cookies in a file
$cookieFile = 'test_woocommerce_cookies.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

// Common cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

// Step 1: Load the login page to get the CSRF token
echo "Step 1: Loading login page...\n";
curl_setopt($ch, CURLOPT_URL, $loginUrl);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error accessing login page: ' . curl_error($ch) . "\n";
    curl_close($ch);
    exit;
}

// Extract CSRF token from the response
preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)"/', $response, $matches);
$csrfToken = $matches[1] ?? null;

if (!$csrfToken) {
    echo "Failed to extract CSRF token from login page.\n";
    curl_close($ch);
    exit;
}

echo "CSRF token extracted: " . substr($csrfToken, 0, 10) . "...\n";

// Step 2: Submit login form
echo "Step 2: Submitting login credentials...\n";
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_csrf_token' => $csrfToken,
    '_username' => $username,
    '_password' => $password
]));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error during login: ' . curl_error($ch) . "\n";
    curl_close($ch);
    exit;
}

// Check if login was successful
if (strpos($response, 'Invalid credentials') !== false) {
    echo "Login failed: Invalid credentials.\n";
    curl_close($ch);
    exit;
}

echo "Login successful!\n";

// Step 3: Access the WooCommerce dashboard
echo "Step 3: Accessing WooCommerce dashboard...\n";
curl_setopt($ch, CURLOPT_URL, $dashboardUrl);
curl_setopt($ch, CURLOPT_POST, false);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error accessing dashboard: ' . curl_error($ch) . "\n";
    curl_close($ch);
    exit;
}

// Verify we're on the dashboard page
if (strpos($response, 'WooCommerce Integration') === false) {
    echo "Failed to access WooCommerce dashboard.\n";
    file_put_contents('dashboard_response.html', $response);
    echo "Response saved to dashboard_response.html for debugging.\n";
    curl_close($ch);
    exit;
}

echo "Successfully accessed WooCommerce dashboard!\n";

// Extract product IDs from the dashboard page
preg_match_all('/<input[^>]*class="form-check-input exported-checkbox"[^>]*value="(\d+)"/', $response, $matches);
$productIds = $matches[1] ?? [];

if (empty($productIds)) {
    echo "No exported products found on the dashboard.\n";
    curl_close($ch);
    exit;
}

echo "Found " . count($productIds) . " exported products.\n";

// Step 4: Test deletion functionality with the first 2 products (if available)
$productsToDelete = array_slice($productIds, 0, min(2, count($productIds)));
echo "Step 4: Testing deletion of " . count($productsToDelete) . " products (IDs: " . implode(', ', $productsToDelete) . ")...\n";

curl_setopt($ch, CURLOPT_URL, $deleteUrl);
curl_setopt($ch, CURLOPT_POST, true);

$postData = [];
foreach ($productsToDelete as $productId) {
    $postData['product_ids'][] = $productId;
}

curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error during product deletion: ' . curl_error($ch) . "\n";
    curl_close($ch);
    exit;
}

// Check if deletion was successful by looking for success message
if (strpos($response, 'products deleted successfully') !== false) {
    echo "Success: Products were deleted successfully!\n";
} else {
    echo "Failed to delete products.\n";
    file_put_contents('delete_response.html', $response);
    echo "Response saved to delete_response.html for debugging.\n";
}

// Clean up
curl_close($ch);
echo "Test completed.\n";