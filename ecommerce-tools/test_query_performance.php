<?php
/**
 * Script to test and compare performance of original vs optimized queries
 */
require dirname(__FILE__).'/vendor/autoload.php';
require dirname(__FILE__).'/config/bootstrap.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(dirname(__FILE__).'/.env');

$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Get repositories
$transactionRepo = $container->get('App\Repository\TransactionRepository');
$userRepo = $container->get('App\Repository\UserRepository');
$wooProductRepo = $container->get('App\Repository\WooCommerceProductRepository');

echo "=== QUERY PERFORMANCE COMPARISON ===\n\n";

// =================== TRANSACTION REPOSITORY TESTS ===================
echo "TESTING: TransactionRepository\n";

// Test getRevenueByMonth (original method vs optimized method)
echo "Testing getRevenueByMonth...\n";
$originalTimings = [];
$optimizedTimings = [];

// Get reflection methods to access both original and optimized methods
$reflectionClass = new ReflectionClass($transactionRepo);

// Try to get the original method if it exists
$originalMethod = null;
try {
    $originalMethod = $reflectionClass->getMethod('getRevenueByMonthOriginal');
} catch (\ReflectionException $e) {
    echo "Original method not found - we'll just test the optimized version\n";
}

// Run tests 5 times to get average timing
for ($i = 0; $i < 5; $i++) {
    // Time original method if available
    if ($originalMethod) {
        $start = microtime(true);
        $originalMethod->invoke($transactionRepo);
        $end = microtime(true);
        $originalTimings[] = ($end - $start) * 1000; // Convert to milliseconds
    }
    
    // Time optimized method
    $start = microtime(true);
    $transactionRepo->getRevenueByMonth();
    $end = microtime(true);
    $optimizedTimings[] = ($end - $start) * 1000; // Convert to milliseconds
}

// Calculate averages
$originalAvg = $originalMethod ? array_sum($originalTimings) / count($originalTimings) : 0;
$optimizedAvg = array_sum($optimizedTimings) / count($optimizedTimings);

echo "  Original method avg time: " . ($originalMethod ? round($originalAvg, 2) . "ms" : "N/A") . "\n";
echo "  Optimized method avg time: " . round($optimizedAvg, 2) . "ms\n";

if ($originalMethod) {
    $improvement = (($originalAvg - $optimizedAvg) / $originalAvg) * 100;
    echo "  Improvement: " . round($improvement, 2) . "%\n";
}

echo "\n";

// =================== USER REPOSITORY TESTS ===================
echo "TESTING: UserRepository\n";

// Test getUserGrowthByMonth
echo "Testing getUserGrowthByMonth...\n";
$originalTimings = [];
$optimizedTimings = [];

// Get reflection methods
$reflectionClass = new ReflectionClass($userRepo);

// Try to get the original method if it exists
$originalMethod = null;
try {
    $originalMethod = $reflectionClass->getMethod('getUserGrowthByMonthOriginal');
} catch (\ReflectionException $e) {
    echo "Original method not found - we'll just test the optimized version\n";
}

// Run tests 5 times to get average timing
for ($i = 0; $i < 5; $i++) {
    // Time original method if available
    if ($originalMethod) {
        $start = microtime(true);
        $originalMethod->invoke($userRepo);
        $end = microtime(true);
        $originalTimings[] = ($end - $start) * 1000; // Convert to milliseconds
    }
    
    // Time optimized method
    $start = microtime(true);
    $userRepo->getUserGrowthByMonth();
    $end = microtime(true);
    $optimizedTimings[] = ($end - $start) * 1000; // Convert to milliseconds
}

// Calculate averages
$originalAvg = $originalMethod ? array_sum($originalTimings) / count($originalTimings) : 0;
$optimizedAvg = array_sum($optimizedTimings) / count($optimizedTimings);

echo "  Original method avg time: " . ($originalMethod ? round($originalAvg, 2) . "ms" : "N/A") . "\n";
echo "  Optimized method avg time: " . round($optimizedAvg, 2) . "ms\n";

if ($originalMethod) {
    $improvement = (($originalAvg - $optimizedAvg) / $originalAvg) * 100;
    echo "  Improvement: " . round($improvement, 2) . "%\n";
}

echo "\n";

// =================== WOOCOMMERCE PRODUCT REPOSITORY TESTS ===================
echo "TESTING: WooCommerceProductRepository\n";

// Test getProductCountsByCategory
echo "Testing getProductCountsByCategory...\n";
$originalTimings = [];
$optimizedTimings = [];

// Get reflection methods
$reflectionClass = new ReflectionClass($wooProductRepo);

// Try to get the original method if it exists
$originalMethod = null;
try {
    $originalMethod = $reflectionClass->getMethod('getProductCountsByCategoryOriginal');
} catch (\ReflectionException $e) {
    echo "Original method not found - we'll just test the optimized version\n";
}

// Run tests 5 times to get average timing
for ($i = 0; $i < 5; $i++) {
    // Time original method if available
    if ($originalMethod) {
        $start = microtime(true);
        $originalMethod->invoke($wooProductRepo);
        $end = microtime(true);
        $originalTimings[] = ($end - $start) * 1000; // Convert to milliseconds
    }
    
    // Time optimized method
    $start = microtime(true);
    $wooProductRepo->getProductCountsByCategory();
    $end = microtime(true);
    $optimizedTimings[] = ($end - $start) * 1000; // Convert to milliseconds
}

// Calculate averages
$originalAvg = $originalMethod ? array_sum($originalTimings) / count($originalTimings) : 0;
$optimizedAvg = array_sum($optimizedTimings) / count($optimizedTimings);

echo "  Original method avg time: " . ($originalMethod ? round($originalAvg, 2) . "ms" : "N/A") . "\n";
echo "  Optimized method avg time: " . round($optimizedAvg, 2) . "ms\n";

if ($originalMethod) {
    $improvement = (($originalAvg - $optimizedAvg) / $originalAvg) * 100;
    echo "  Improvement: " . round($improvement, 2) . "%\n";
}

echo "\n";

// Test getAIProcessedProductsByMonth
echo "Testing getAIProcessedProductsByMonth...\n";
$originalTimings = [];
$optimizedTimings = [];

// Try to get the original method if it exists
$originalMethod = null;
try {
    $originalMethod = $reflectionClass->getMethod('getAIProcessedProductsByMonthOriginal');
} catch (\ReflectionException $e) {
    echo "Original method not found - we'll just test the optimized version\n";
}

// Run tests 5 times to get average timing
for ($i = 0; $i < 5; $i++) {
    // Time original method if available
    if ($originalMethod) {
        $start = microtime(true);
        $originalMethod->invoke($wooProductRepo);
        $end = microtime(true);
        $originalTimings[] = ($end - $start) * 1000; // Convert to milliseconds
    }
    
    // Time optimized method
    $start = microtime(true);
    $wooProductRepo->getAIProcessedProductsByMonth();
    $end = microtime(true);
    $optimizedTimings[] = ($end - $start) * 1000; // Convert to milliseconds
}

// Calculate averages
$originalAvg = $originalMethod ? array_sum($originalTimings) / count($originalTimings) : 0;
$optimizedAvg = array_sum($optimizedTimings) / count($optimizedTimings);

echo "  Original method avg time: " . ($originalMethod ? round($originalAvg, 2) . "ms" : "N/A") . "\n";
echo "  Optimized method avg time: " . round($optimizedAvg, 2) . "ms\n";

if ($originalMethod) {
    $improvement = (($originalAvg - $optimizedAvg) / $originalAvg) * 100;
    echo "  Improvement: " . round($improvement, 2) . "%\n";
}

echo "\n";

// =================== SUMMARY ===================
echo "=== SUMMARY ===\n";
echo "1. Added indexes to frequently queried columns\n";
echo "2. Optimized TransactionRepository methods:\n";
echo "   - getRevenueByMonth (multiple queries → single query with DATE_FORMAT)\n";
echo "   - getRevenueBreakdown (3 separate queries → single query with CASE)\n";
echo "   - getCreditUsageByMonth (multiple queries → single query with DATE_FORMAT)\n";
echo "3. Optimized UserRepository methods:\n";
echo "   - getUserGrowthByMonth (24 separate queries → single query with recursive CTE)\n";
echo "   - getUserDistribution (multiple role count queries → single query with JSON_CONTAINS)\n";
echo "4. Optimized WooCommerceProductRepository methods:\n";
echo "   - getProductCountsByCategory (added direct SQL and caching)\n";
echo "   - getAIProcessedProductsByMonth (multiple queries → single query with recursive CTE)\n";
echo "   - getContentGenerationStats (multiple queries → single query with CASE expressions)\n";
echo "\n";
echo "Additional optimizations:\n";
echo "1. Added caching placeholders (commented out) - need to inject cache service\n";
echo "2. Used direct SQL for better performance where appropriate\n";
echo "3. Updated indexes for optimal query performance\n";
echo "\n";

echo "To view SQL queries in detail, enable the Symfony Profiler and visit /_profiler\n";
echo "Done!\n"; 