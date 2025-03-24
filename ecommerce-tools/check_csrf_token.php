<?php
// Simple script to check CSRF token generation on the login page
echo "Checking CSRF token generation on login page...\n";

// Initialize cURL session
$ch = curl_init('http://localhost:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";

// Extract the response body
list($header, $body) = explode("\r\n\r\n", $response, 2);

// Save the response for debugging
file_put_contents('csrf_check_response.html', $body);
echo "Response saved to csrf_check_response.html\n";

// Extract the CSRF token
if (preg_match('/<input type="hidden" name="_csrf_token" value="([^"]+)">/', $body, $matches)) {
    $csrfToken = $matches[1];
    echo "CSRF Token: $csrfToken\n";
    
    if ($csrfToken === 'csrf-token') {
        echo "ISSUE DETECTED: CSRF token is still showing as 'csrf-token' literal string\n";
    } else {
        echo "SUCCESS: CSRF token appears to be properly generated\n";
    }
} else {
    echo "ERROR: Could not find CSRF token in the response\n";
}

echo "Script completed\n";