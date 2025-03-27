<?php
// Simple API endpoint to provide chart data for dashboard
header('Content-Type: application/json');

// Get range parameter with default
$range = isset($_GET['range']) ? (int)$_GET['range'] : 6;

// Sample data for monthly activity
$months = [];
$counts = [];

// Generate sample monthly data for the past X months
$currentMonth = date('n');
$currentYear = date('Y');

for ($i = $range - 1; $i >= 0; $i--) {
    $month = $currentMonth - $i;
    $year = $currentYear;
    
    if ($month <= 0) {
        $month += 12;
        $year--;
    }
    
    // Get month name
    $monthName = date('M', mktime(0, 0, 0, $month, 1, $year));
    $months[] = $monthName;
    
    // Generate a random count between 10 and 100
    $counts[] = rand(10, 100);
}

// Sample data for product categories
$productCategories = [
    ['category_name' => 'Electronics', 'count' => 42],
    ['category_name' => 'Clothing', 'count' => 28],
    ['category_name' => 'Home & Garden', 'count' => 16],
    ['category_name' => 'Books', 'count' => 9]
];

// Create the monthly activity data
$monthlyActivity = [];
foreach ($months as $index => $month) {
    $monthlyActivity[] = [
        'month' => $month,
        'count' => $counts[$index]
    ];
}

// Return the data as JSON
echo json_encode([
    'product_categories' => $productCategories,
    'monthly_activity' => $monthlyActivity,
    'time_range' => $range
]); 