<?php
// This file bypasses Symfony framework entirely and tests direct connection to OpenAI API
// using native PHP cURL, which should work in any XAMPP environment

// Start output buffering to capture all errors
ob_start();

// Set execution time limit high to avoid timeout issues
ini_set('max_execution_time', 120);
set_time_limit(120);

try {
    echo "<h1>OpenAI API Direct Connection Test</h1>";

    // Load API key from different sources to debug environment variable issues
    echo "<h2>API Key Detection</h2>";
    
    // Method 1: Try to load it from .env.local file directly
    $envLocalPath = dirname(__DIR__) . '/.env.local';
    $envPath = dirname(__DIR__) . '/.env';
    
    $apiKey = null;
    
    if (file_exists($envLocalPath)) {
        echo "<p>Found .env.local file</p>";
        $envContent = file_get_contents($envLocalPath);
        if (preg_match('/OPENAI_API_KEY=["\'](.*?)["\']/i', $envContent, $matches)) {
            $apiKey = $matches[1];
            echo "<p>API key found in .env.local (length: " . strlen($apiKey) . 
                 ", first 5 chars: " . substr($apiKey, 0, 5) . "...)</p>";
        } else {
            echo "<p style='color:red'>Could not find OPENAI_API_KEY in .env.local</p>";
        }
    } else {
        echo "<p style='color:red'>.env.local file not found</p>";
    }
    
    // Method 2: Try to load from regular .env as fallback
    if ($apiKey === null && file_exists($envPath)) {
        echo "<p>Found .env file</p>";
        $envContent = file_get_contents($envPath);
        if (preg_match('/OPENAI_API_KEY=["\'](.*?)["\']/i', $envContent, $matches)) {
            $apiKey = $matches[1];
            echo "<p>API key found in .env (length: " . strlen($apiKey) . 
                 ", first 5 chars: " . substr($apiKey, 0, 5) . "...)</p>";
        } else {
            echo "<p style='color:red'>Could not find OPENAI_API_KEY in .env</p>";
        }
    } elseif (!file_exists($envPath)) {
        echo "<p style='color:red'>.env file not found</p>";
    }
    
    // If still no API key, use a hardcoded one from .env.local 
    if ($apiKey === null) {
        echo "<p style='color:orange'>Using hardcoded API key from .env.local</p>";
        $apiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');

    }
    
    if (empty($apiKey) || $apiKey === 'sk_test_placeholder_replace_with_actual_key') {
        throw new Exception("No valid API key found in any location");
    }

    // Check that cURL extension is installed
    echo "<h2>cURL Check</h2>";
    if (!extension_loaded('curl')) {
        throw new Exception("cURL extension is not installed");
    }
    echo "<p style='color:green'>cURL extension is available</p>";

    // Test PHP memory limits
    echo "<h2>PHP Environment</h2>";
    echo "<p>PHP Version: " . phpversion() . "</p>";
    echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>";
    echo "<p>Max Execution Time: " . ini_get('max_execution_time') . "</p>";
    
    // Show OpenSSL info
    if (extension_loaded('openssl')) {
        echo "<p>OpenSSL version: " . OPENSSL_VERSION_TEXT . "</p>";
    } else {
        echo "<p style='color:red'>OpenSSL extension not loaded!</p>";
    }
    
    // Direct OpenAI API test using native cURL (no Symfony components)
    echo "<h2>Direct OpenAI API Test (native cURL)</h2>";
    
    // Create a simple prompt for testing
    $prompt = "Generate a short product description for a coffee mug.";
    
    // Initialize cURL session
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    
    $data = json_encode([
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant that generates product descriptions.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 150
    ]);
    
    // Set cURL options
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,  // Disable SSL verification
        CURLOPT_SSL_VERIFYHOST => 0,      // Disable host verification
        CURLOPT_VERBOSE => true
    ]);
    
    // Log if the API request is going through a proxy
    $proxy = getenv('HTTP_PROXY') ?: getenv('http_proxy');
    if ($proxy) {
        echo "<p>Proxy detected: $proxy</p>";
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
    } else {
        echo "<p>No proxy detected</p>";
    }
    
    echo "<p>Sending request to OpenAI API...</p>";
    
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    // Execute cURL request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    
    // Get verbose info for debugging
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    
    echo "<h3>Results</h3>";
    
    if ($errno) {
        echo "<p style='color:red'>cURL Error ($errno): $error</p>";
        echo "<h3>Verbose Log</h3>";
        echo "<pre>" . htmlspecialchars($verboseLog) . "</pre>";
    } else {
        echo "<p>HTTP Status Code: $httpCode</p>";
        
        if ($httpCode === 200) {
            echo "<p style='color:green'>Success! Here's the response:</p>";
            $responseData = json_decode($response, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $content = $responseData['choices'][0]['message']['content'] ?? 'No content returned';
                echo "<div style='background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin: 15px 0;'>";
                echo "<p><strong>Generated Content:</strong></p>";
                echo "<p>" . nl2br(htmlspecialchars($content)) . "</p>";
                echo "</div>";
                
                echo "<p>Full response data:</p>";
                echo "<pre>" . htmlspecialchars(json_encode($responseData, JSON_PRETTY_PRINT)) . "</pre>";
            } else {
                echo "<p style='color:red'>JSON decoding error: " . json_last_error_msg() . "</p>";
                echo "<p>Raw response:</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        } else {
            echo "<p style='color:red'>API request failed with status code: $httpCode</p>";
            echo "<p>Response:</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
            echo "<h3>Verbose Log</h3>";
            echo "<pre>" . htmlspecialchars($verboseLog) . "</pre>";
        }
    }
    
    curl_close($ch);

    // Add reference implementation for AIService.php
    echo "<h2>Recommended Fix for AIService.php</h2>";
    echo "<p>Based on the test results, here's how to modify the AIService.php callOpenAI method:</p>";
    echo "<pre style='background-color: #f0f0f0; padding: 15px; overflow: auto;'>";
    echo htmlspecialchars('
private function callOpenAI(string $prompt, bool $isPremium = false, array $options = []): string
{
    // Set model and parameters based on premium status
    $model = $isPremium ? \'gpt-4-turbo-preview\' : \'gpt-3.5-turbo\'; 
    $temperature = $isPremium ? 0.6 : 0.7;
    $maxTokens = $isPremium ? 1500 : 1000;
    
    // Enhanced system prompt
    $systemPrompt = $isPremium 
        ? \'You are an elite e-commerce copywriter and SEO specialist...\'
        : \'You are an expert e-commerce copywriter and SEO specialist...\';
    
    try {
        // OPTION 1: Use direct cURL instead of HttpClient for XAMPP compatibility
        $ch = curl_init(\'https://api.openai.com/v1/chat/completions\');
        
        $data = json_encode([
            \'model\' => $model,
            \'messages\' => [
                [
                    \'role\' => \'system\',
                    \'content\' => $systemPrompt
                ],
                [
                    \'role\' => \'user\',
                    \'content\' => $prompt
                ]
            ],
            \'temperature\' => $temperature,
            \'max_tokens\' => $maxTokens,
        ]);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                \'Content-Type: application/json\',
                \'Authorization: Bearer \' . $this->openaiApiKey
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new \Exception(\'cURL error: \' . curl_error($ch));
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode !== 200) {
            throw new \Exception(\'OpenAI API returned status code: \' . $httpCode);
        }
        
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(\'JSON decoding error: \' . json_last_error_msg());
        }
        
        return $data[\'choices\'][0][\'message\'][\'content\'] ?? \'\';
    } catch (\Exception $e) {
        error_log(\'OpenAI API Error: \' . $e->getMessage());
        throw $e;
    }
}');
    echo "</pre>";

} catch (Exception $e) {
    echo "<div style='color: red; padding: 15px; background: #ffeeee; border: 1px solid #ffaaaa; margin: 15px;'>";
    echo "<h2>Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// Display any errors or output
$output = ob_get_clean();
echo $output;