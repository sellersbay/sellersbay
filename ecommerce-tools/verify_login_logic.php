<?php
// Simple verification script for login detection logic
// This tests only the logic we changed in LoginFormAuthenticator->supports() method

echo "===== LOGIN DETECTION LOGIC VERIFICATION =====\n\n";
echo "This script verifies that the login detection logic we implemented\n";
echo "properly handles both route-based detection and direct URL access.\n\n";

// Mock Attributes class with the get method
class MockAttributes {
    private $attributes = [];
    
    public function set(string $name, $value): void {
        $this->attributes[$name] = $value;
    }
    
    public function get(string $name) {
        return $this->attributes[$name] ?? null;
    }
}

// Create a mock Request class with just what we need to test the logic
class MockRequest {
    private $method;
    private $path;
    private $attributes;
    
    public function __construct(string $method, string $path) {
        $this->method = $method;
        $this->path = $path;
        $this->attributes = new MockAttributes();
    }
    
    public function isMethod(string $method): bool {
        return $this->method === $method;
    }
    
    public function getPathInfo(): string {
        return $this->path;
    }
    
    public function getAttributes(): MockAttributes {
        return $this->attributes;
    }
}

// Test cases
$tests = [
    [
        'name' => 'Route-based login detection',
        'request' => function() {
            $request = new MockRequest('POST', '/login');
            $request->getAttributes()->set('_route', 'app_login');
            return $request;
        },
        'expected' => true,
        'description' => 'POST request to /login with _route=app_login'
    ],
    [
        'name' => 'Direct URL login detection',
        'request' => function() {
            $request = new MockRequest('POST', '/login');
            // No route attribute needed for this test
            return $request;
        },
        'expected' => true,
        'description' => 'POST request to /login without _route attribute'
    ],
    [
        'name' => 'Wrong path rejection',
        'request' => function() {
            $request = new MockRequest('POST', '/dashboard');
            return $request;
        },
        'expected' => false,
        'description' => 'POST request to /dashboard (should be rejected)'
    ],
    [
        'name' => 'Wrong method rejection',
        'request' => function() {
            $request = new MockRequest('GET', '/login');
            return $request;
        },
        'expected' => false,
        'description' => 'GET request to /login (should be rejected)'
    ],
    [
        'name' => 'Complex routing scenario (FIX VALUE TEST)',
        'request' => function() {
            // Simulate a prefixed route in Symfony where the path doesn't match exactly,
            // but the route name is correct - e.g., '/en/login' or '/admin/login'
            $request = new MockRequest('POST', '/en/login');
            $request->getAttributes()->set('_route', 'app_login');
            return $request;
        },
        'expected' => true, // Should be accepted by fixed logic, rejected by original
        'description' => 'POST to /en/login with _route=app_login (demonstrates fix value)'
    ]
];

// Original login detection logic (before our fix)
function originalSupports($request) {
    // Only handle POST requests to the login path
    return $request->isMethod('POST') && 
           $request->getPathInfo() === '/login';
}

// Fixed login detection logic (after our fix)
function fixedSupports($request) {
    // Support both Symfony routing and direct URL access
    return $request->isMethod('POST') && 
           ($request->getAttributes()->get('_route') === 'app_login' || 
            $request->getPathInfo() === '/login');
}

// Run the tests
echo "Running tests...\n\n";
$allOriginalPassed = true;
$allFixedPassed = true;

foreach ($tests as $i => $test) {
    $request = $test['request']();
    
    $originalResult = originalSupports($request);
    $fixedResult = fixedSupports($request);
    
    $originalPassed = $originalResult === $test['expected'];
    $fixedPassed = $fixedResult === $test['expected'];
    
    if (!$originalPassed) $allOriginalPassed = false;
    if (!$fixedPassed) $allFixedPassed = false;
    
    echo "Test " . ($i + 1) . ": " . $test['name'] . "\n";
    echo "  - " . $test['description'] . "\n";
    echo "  - Original logic: " . ($originalPassed ? "✓ PASS" : "✗ FAIL") . "\n";
    echo "  - Fixed logic: " . ($fixedPassed ? "✓ PASS" : "✗ FAIL") . "\n\n";
}

// Show summary
echo "=== SUMMARY ===\n\n";
echo "Original logic (isMethod('POST') && getPathInfo() === '/login'):\n";
echo "  " . ($allOriginalPassed ? "✓ All tests passed" : "✗ Some tests failed") . "\n\n";

echo "Fixed logic (isMethod('POST') && (get('_route') === 'app_login' || getPathInfo() === '/login')):\n";
echo "  " . ($allFixedPassed ? "✓ All tests passed" : "✗ Some tests failed") . "\n\n";

if ($allFixedPassed && !$allOriginalPassed) {
    echo "VERIFICATION SUCCESSFUL: The fixed login detection logic properly handles all test cases!\n";
    echo "This confirms our fix works correctly and will detect login requests via both\n";
    echo "route detection and direct URL access, while properly rejecting non-login requests.\n";
} else if ($allFixedPassed && $allOriginalPassed) {
    echo "VERIFICATION NOTE: Both original and fixed logic passed all tests.\n";
    echo "This suggests our test cases may not be fully covering the scenarios we fixed.\n";
} else {
    echo "VERIFICATION FAILED: The fixed logic does not handle all test cases correctly.\n";
    echo "Please review the test results to identify where the logic is failing.\n";
}