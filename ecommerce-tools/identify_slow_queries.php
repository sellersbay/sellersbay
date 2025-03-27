<?php

require dirname(__FILE__).'/vendor/autoload.php';
require dirname(__FILE__).'/config/bootstrap.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(dirname(__FILE__).'/.env');

$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Get the entity manager
$entityManager = $container->get('doctrine.orm.entity_manager');
$connection = $entityManager->getConnection();

// Setup a query logger
// In newer Doctrine versions, DebugStack moved from DBAL\Logging to DBAL\Logging\Middleware
try {
    // Try newer namespace first
    $logger = new \Doctrine\DBAL\Logging\Middleware\DebugStack();
} catch (\Error $e) {
    try {
        // Try older namespace
        $logger = new \Doctrine\DBAL\Logging\DebugStack();
    } catch (\Error $e) {
        // Try even older namespace
        $logger = new \Doctrine\DBAL\Logging\SQLLogger();
    }
}

$connection->getConfiguration()->setSQLLogger($logger);

echo "Analyzing potentially slow queries...\n\n";

// Test repository queries
$repositories = [
    'WooCommerceProduct' => $container->get('App\Repository\WooCommerceProductRepository'),
    'Transaction' => $container->get('App\Repository\TransactionRepository'),
    'User' => $container->get('App\Repository\UserRepository'),
];

$queriesRun = [];

// WooCommerceProductRepository
echo "Testing WooCommerceProductRepository queries...\n";
try {
    $repositories['WooCommerceProduct']->getProductCountsByCategory();
    $queriesRun[] = 'WooCommerceProductRepository::getProductCountsByCategory';
    
    $repositories['WooCommerceProduct']->getAIProcessedProductsByMonth();
    $queriesRun[] = 'WooCommerceProductRepository::getAIProcessedProductsByMonth';
    
    $repositories['WooCommerceProduct']->getContentGenerationStats();
    $queriesRun[] = 'WooCommerceProductRepository::getContentGenerationStats';
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// TransactionRepository
echo "Testing TransactionRepository queries...\n";
try {
    $repositories['Transaction']->getRevenueByMonth();
    $queriesRun[] = 'TransactionRepository::getRevenueByMonth';
    
    $repositories['Transaction']->getRevenueBreakdown();
    $queriesRun[] = 'TransactionRepository::getRevenueBreakdown';
    
    $repositories['Transaction']->getCreditUsageByMonth();
    $queriesRun[] = 'TransactionRepository::getCreditUsageByMonth';
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// UserRepository
echo "Testing UserRepository queries...\n";
try {
    $repositories['User']->getUserGrowthByMonth();
    $queriesRun[] = 'UserRepository::getUserGrowthByMonth';
    
    $repositories['User']->getUserDistribution();
    $queriesRun[] = 'UserRepository::getUserDistribution';
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Analyze results
$slowQueries = [];
$queryThreshold = 100; // milliseconds

foreach ($logger->queries as $i => $query) {
    if (isset($query['executionMS']) && $query['executionMS'] > $queryThreshold) {
        $slowQueries[] = [
            'sql' => $query['sql'],
            'params' => $query['params'],
            'time' => $query['executionMS'],
            'index' => $i
        ];
    }
}

// Sort by execution time (slowest first)
usort($slowQueries, function($a, $b) {
    return $b['time'] <=> $a['time'];
});

// Output results to console and file
$output = "=== SLOW QUERY ANALYSIS ===\n\n";
$output .= "Threshold: " . $queryThreshold . "ms\n";
$output .= "Total queries executed: " . count($logger->queries) . "\n";
$output .= "Slow queries found: " . count($slowQueries) . "\n\n";

if (count($slowQueries) > 0) {
    foreach ($slowQueries as $i => $query) {
        $output .= "SLOW QUERY #" . ($i + 1) . "\n";
        $output .= "Execution time: " . $query['time'] . "ms\n";
        $output .= "SQL: " . $query['sql'] . "\n";
        $output .= "Parameters: " . json_encode($query['params']) . "\n";
        
        // Try to identify which repository method ran this query
        $queryIndex = $query['index'];
        $possibleMethod = 'Unknown';
        foreach ($queriesRun as $methodName) {
            if ($queryIndex > 0 && $queryIndex <= count($logger->queries)) {
                // This is a very crude way to guess which method ran the query
                // In a real implementation, better correlation would be needed
                $possibleMethod = $methodName;
            }
        }
        $output .= "Likely from: " . $possibleMethod . "\n\n";
        
        // Run EXPLAIN on the query if possible
        try {
            if (strpos(strtoupper($query['sql']), 'SELECT') === 0) {
                $explainSql = "EXPLAIN " . $query['sql'];
                $stmt = $connection->executeQuery($explainSql, $query['params']);
                $explainResults = $stmt->fetchAllAssociative();
                
                $output .= "EXPLAIN results:\n";
                foreach ($explainResults as $explainRow) {
                    $output .= json_encode($explainRow) . "\n";
                }
            }
        } catch (\Exception $e) {
            $output .= "Could not run EXPLAIN: " . $e->getMessage() . "\n";
        }
        
        $output .= "-------------------------------------------\n\n";
    }
    
    // Add optimization recommendations
    $output .= "=== OPTIMIZATION RECOMMENDATIONS ===\n\n";
    $output .= "1. Add indexes for frequently filtered or sorted columns\n";
    $output .= "2. Consider caching results of aggregate queries that don't need real-time data\n";
    $output .= "3. Use pagination for large result sets\n";
    $output .= "4. Replace multiple individual queries with a single optimized query where possible\n";
    $output .= "5. Consider using query hints for complex operations\n";
    $output .= "6. Review entity associations and fetching strategies (lazy vs eager loading)\n\n";
} else {
    $output .= "No slow queries detected.\n";
}

echo $output;

// Save results to file
file_put_contents('var/log/slow_queries_report.txt', $output);
echo "Report saved to var/log/slow_queries_report.txt\n"; 