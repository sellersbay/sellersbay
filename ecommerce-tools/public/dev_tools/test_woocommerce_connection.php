<?php
/**
 * WooCommerce Connection Test Utility
 * 
 * This script tests the connection to a WooCommerce store's REST API
 * and diagnoses potential issues with the sync functionality.
 */

// Only run in web context
if (php_sapi_name() === 'cli') {
    die("This script must be run in a web browser.\n");
}

require_once dirname(__DIR__).'/vendor/autoload.php';

// Security - only allow from localhost
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die("For security reasons, this script can only be run from localhost.");
}

// Function to format responses for display
function formatResponse($data) {
    if (is_string($data)) {
        return htmlspecialchars($data);
    }
    return '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
}

// Function to parse URL
function parseUrl($url) {
    if (!str_starts_with($url, 'http')) {
        $url = 'https://' . $url;
    }
    return rtrim($url, '/');
}

// Initialize variables
$storeUrl = $_POST['store_url'] ?? '';
$consumerKey = $_POST['consumer_key'] ?? '';
$consumerSecret = $_POST['consumer_secret'] ?? '';
$productId = $_POST['product_id'] ?? '';
$results = [];
$hasConnection = false;

// Check if we should run tests
$runTests = isset($_POST['test_connection']) && $storeUrl && $consumerKey && $consumerSecret;

if ($runTests) {
    // Start output buffering to capture any errors
    ob_start();
    
    try {
        // Test 1: Basic server connectivity using cURL directly
        $results['test_server'] = [
            'name' => 'Basic Server Connectivity',
            'status' => 'Running...'
        ];
        
        $parsedUrl = parseUrl($storeUrl);
        $ch = curl_init($parsedUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response !== false) {
            $results['test_server']['status'] = 'Passed';
            $results['test_server']['details'] = "HTTP code: $httpCode";
        } else {
            $results['test_server']['status'] = 'Failed';
            $results['test_server']['details'] = "cURL error: $curlError";
        }
        curl_close($ch);
        
        // Test 2: WooCommerce REST API connectivity
        $results['test_api'] = [
            'name' => 'WooCommerce REST API',
            'status' => 'Running...'
        ];
        
        $apiUrl = $parsedUrl . '/wp-json/wc/v3/products';
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        // Add authentication
        $params = [
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret
        ];
        $apiUrl .= '?' . http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response !== false && $httpCode == 200) {
            $results['test_api']['status'] = 'Passed';
            $results['test_api']['details'] = "API responded with HTTP $httpCode";
            $hasConnection = true;
            
            // Parse the response to get product count
            $responseData = json_decode($response, true);
            if (is_array($responseData)) {
                $productCount = count($responseData);
                $results['test_api']['products'] = "Found $productCount products";
            }
        } else {
            $results['test_api']['status'] = 'Failed';
            $results['test_api']['details'] = "HTTP code: $httpCode, cURL error: $curlError";
            
            // Try to get more details from response
            if ($response) {
                $responseData = json_decode($response, true);
                if ($responseData && isset($responseData['message'])) {
                    $results['test_api']['error_message'] = $responseData['message'];
                } else {
                    $results['test_api']['response'] = substr($response, 0, 255) . '...'; // First 255 chars
                }
            }
        }
        curl_close($ch);
        
        // Test 3: Specific product fetching (if product ID provided)
        if ($productId && $hasConnection) {
            $results['test_product'] = [
                'name' => 'Specific Product Test',
                'status' => 'Running...'
            ];
            
            $apiUrl = $parsedUrl . '/wp-json/wc/v3/products/' . $productId;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl . '?' . http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
            
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($response !== false && $httpCode == 200) {
                $results['test_product']['status'] = 'Passed';
                $productData = json_decode($response, true);
                $results['test_product']['details'] = "Successfully retrieved product: " . 
                    ($productData['name'] ?? 'Unknown product');
            } else {
                $results['test_product']['status'] = 'Failed';
                $results['test_product']['details'] = "HTTP code: $httpCode, cURL error: $curlError";
                
                if ($response) {
                    $responseData = json_decode($response, true);
                    if ($responseData && isset($responseData['message'])) {
                        $results['test_product']['error_message'] = $responseData['message'];
                    }
                }
            }
            curl_close($ch);
        }
        
        // Test 4: Check network configuration
        $results['test_network'] = [
            'name' => 'Network Configuration',
            'status' => 'Running...'
        ];
        
        $networkInfo = [];
        
        // Check if allow_url_fopen is enabled
        $networkInfo[] = "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'Enabled' : 'Disabled');
        
        // Check max_execution_time
        $networkInfo[] = "max_execution_time: " . ini_get('max_execution_time') . " seconds";
        
        // Check if cURL extension is loaded
        $networkInfo[] = "cURL extension: " . (function_exists('curl_version') ? 'Loaded' : 'Not loaded');
        
        // Get cURL version info
        if (function_exists('curl_version')) {
            $curlInfo = curl_version();
            $networkInfo[] = "cURL version: " . $curlInfo['version'];
            $networkInfo[] = "SSL version: " . $curlInfo['ssl_version'];
        }
        
        // Check DNS resolution
        $parsedUrlParts = parse_url($parsedUrl);
        $host = $parsedUrlParts['host'] ?? '';
        if ($host) {
            $dnsCheck = dns_get_record($host, DNS_A);
            $networkInfo[] = "DNS resolution for $host: " . 
                (empty($dnsCheck) ? 'Failed to resolve' : 'Resolved to ' . ($dnsCheck[0]['ip'] ?? 'unknown IP'));
        }
        
        $results['test_network']['status'] = 'Completed';
        $results['test_network']['details'] = implode("<br>", $networkInfo);
        
    } catch (Exception $e) {
        $results['error'] = [
            'name' => 'Exception Caught',
            'status' => 'Error',
            'details' => $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()
        ];
    }
    
    // Capture any PHP errors or notices
    $errorOutput = ob_get_clean();
    if ($errorOutput) {
        $results['php_errors'] = [
            'name' => 'PHP Errors/Notices',
            'status' => 'Warning',
            'details' => $errorOutput
        ];
    }
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WooCommerce Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2 {
            color: #0073aa;
        }
        form {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background: #005177;
        }
        .test-results {
            margin-top: 20px;
        }
        .test-item {
            background: #f9f9f9;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 4px;
        }
        .test-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .test-status {
            margin-bottom: 10px;
        }
        .test-status.passed {
            color: #46b450;
        }
        .test-status.failed {
            color: #dc3232;
        }
        .test-status.warning {
            color: #ffb900;
        }
        .test-details {
            background: #fff;
            border: 1px solid #eee;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .note {
            background: #e5f5fa;
            padding: 10px;
            border-left: 4px solid #00a0d2;
            margin: 20px 0;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <h1>WooCommerce Connection Test</h1>
    
    <div class="note">
        <p>This utility helps diagnose WooCommerce API connection issues that may cause "Sync" functionality problems.</p>
        <p>Enter your WooCommerce store details below to test the connection.</p>
    </div>
    
    <form method="post" action="">
        <div>
            <label for="store_url">Store URL:</label>
            <input type="text" id="store_url" name="store_url" placeholder="https://your-store.com" value="<?= htmlspecialchars($storeUrl) ?>" required>
        </div>
        
        <div>
            <label for="consumer_key">Consumer Key:</label>
            <input type="text" id="consumer_key" name="consumer_key" placeholder="ck_xxxxxxxxxxxx" value="<?= htmlspecialchars($consumerKey) ?>" required>
        </div>
        
        <div>
            <label for="consumer_secret">Consumer Secret:</label>
            <input type="text" id="consumer_secret" name="consumer_secret" placeholder="cs_xxxxxxxxxxxx" value="<?= htmlspecialchars($consumerSecret) ?>" required>
        </div>
        
        <div>
            <label for="product_id">Product ID (optional):</label>
            <input type="text" id="product_id" name="product_id" placeholder="e.g., 123" value="<?= htmlspecialchars($productId) ?>">
        </div>
        
        <div>
            <input type="submit" name="test_connection" value="Run Connection Tests">
        </div>
    </form>
    
    <?php if ($runTests): ?>
    <h2>Test Results</h2>
    <div class="test-results">
        <?php foreach ($results as $key => $test): ?>
            <div class="test-item">
                <div class="test-name"><?= htmlspecialchars($test['name']) ?></div>
                <div class="test-status <?= strtolower($test['status']) === 'passed' ? 'passed' : (strtolower($test['status']) === 'warning' ? 'warning' : 'failed') ?>">
                    Status: <?= htmlspecialchars($test['status']) ?>
                </div>
                <?php if (isset($test['details'])): ?>
                    <div class="test-details">
                        <?= $test['details'] ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($test['error_message'])): ?>
                    <div class="test-details">
                        Error: <?= htmlspecialchars($test['error_message']) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($test['response'])): ?>
                    <div class="test-details">
                        Response: <?= htmlspecialchars($test['response']) ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($test['products'])): ?>
                    <div class="test-details">
                        <?= htmlspecialchars($test['products']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($hasConnection): ?>
    <div class="note">
        <p><strong>Connection Successful!</strong> Your WooCommerce store's API is accessible.</p>
        <p>If you're still having issues with the "Sync" button in the application, please check:</p>
        <ul>
            <li>That you're using the same credentials in the application as you tested here</li>
            <li>That the product ID exists in your WooCommerce store</li>
            <li>Your server's network configuration allows outbound connections to your WooCommerce store</li>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</body>
</html>