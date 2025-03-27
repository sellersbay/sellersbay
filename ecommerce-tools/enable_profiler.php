<?php
/**
 * Script to enable the Symfony Web Profiler for database query analysis
 */

$webProfilerFile = __DIR__ . '/config/packages/web_profiler.yaml';

if (!file_exists($webProfilerFile)) {
    echo "Error: Web profiler configuration file not found at: $webProfilerFile\n";
    exit(1);
}

// Read the current config
$content = file_get_contents($webProfilerFile);

// Check if the profiler is already enabled
if (strpos($content, 'toolbar: true') !== false) {
    echo "Web Profiler toolbar is already enabled.\n";
    echo "Access the profiler by visiting: /_profiler after loading a page.\n";
    exit(0);
}

// Enable the toolbar
$content = str_replace('toolbar: false', 'toolbar: true', $content);

// Write the updated config
file_put_contents($webProfilerFile, $content);

echo "Web Profiler toolbar has been enabled.\n";
echo "To use it:\n";
echo "1. Clear your Symfony cache: php bin/console cache:clear\n";
echo "2. Visit any page on your website\n";
echo "3. Look for the toolbar at the bottom of the page\n";
echo "4. Click on the database icon to see query details\n";
echo "5. Or visit /_profiler to see full profiling information\n";

// Optionally clear the cache automatically
$clearCache = readline("Would you like to clear the cache now? (y/n): ");
if (strtolower($clearCache) === 'y') {
    echo "Clearing cache...\n";
    system('php bin/console cache:clear');
    echo "Cache cleared.\n";
}

echo "\nTo check for slow queries:\n";
echo "1. Look for queries with high execution time in the profiler\n";
echo "2. Run EXPLAIN on these queries to identify optimization opportunities\n";
echo "3. Check for N+1 query issues (multiple similar queries being executed in loops)\n";
echo "4. Refer to slow_queries.md for optimization recommendations\n"; 