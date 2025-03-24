<?php
// Standalone dashboard demo page that bypasses Symfony's authentication
// This allows for direct viewing of the dashboard implementation

// Get URL parameters with defaults
$products_count = isset($_GET['products']) ? (int)$_GET['products'] : 5;
$woocommerce_products_count = isset($_GET['woocommerce']) ? (int)$_GET['woocommerce'] : 3;
$available_credits = isset($_GET['credits']) ? (int)$_GET['credits'] : 100;
$showMoreProducts = isset($_GET['more_products']) && $_GET['more_products'] === 'true';

// Sample recent products for demonstration
$recent_products = [
    [
        'name' => 'WooCommerce Product Description Bundle',
        'description' => 'Ready-to-use product descriptions for WooCommerce stores with keyword optimization.',
        'type' => 'woocommerce',
        'updated_at' => date('Y-m-d H:i', strtotime('-1 day'))
    ],
    [
        'name' => 'SEO Optimized Content Pack',
        'description' => 'A collection of professionally written, SEO-optimized content for e-commerce stores.',
        'type' => 'product',
        'updated_at' => date('Y-m-d H:i', strtotime('-2 day'))
    ],
    [
        'name' => 'Category Page Templates',
        'description' => 'Structured category page templates with integrated meta descriptions and headers.',
        'type' => 'product',
        'updated_at' => date('Y-m-d H:i', strtotime('-5 day'))
    ],
];

// Add more products if requested
if ($showMoreProducts) {
    $additional_products = [
        [
            'name' => 'Premium SEO Content',
            'description' => 'Advanced SEO content generated with AI tools.',
            'type' => 'product',
            'updated_at' => date('Y-m-d H:i', strtotime('-3 day'))
        ],
        [
            'name' => 'WooCommerce Pro Bundle',
            'description' => 'A collection of premium WooCommerce products with enhanced descriptions.',
            'type' => 'woocommerce',
            'updated_at' => date('Y-m-d H:i', strtotime('-4 day'))
        ],
    ];
    $recent_products = array_merge($recent_products, $additional_products);
}

// Sort recent products by date (newest first)
usort($recent_products, function($a, $b) {
    return strtotime($b['updated_at']) - strtotime($a['updated_at']);
});

// Create a simple HTML login page link
$login_url = '/login';
$dashboard_url = '/dashboard';

// Add a warning banner if trying to access the real dashboard might not work
$auth_warning = '';
if (isset($_GET['auth_failed']) && $_GET['auth_failed'] === 'true') {
    $auth_warning = '
    <div class="alert alert-warning" style="background-color: #fff3cd; color: #856404; padding: 12px; margin-bottom: 20px; border: 1px solid #ffeeba; border-radius: 4px;">
        <strong>Authentication Note:</strong> The actual dashboard requires login. This is a demonstration version showing how the dashboard looks and functions.
    </div>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoboSEO Dashboard</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.5;
            color: #212529;
            background-color: #f8f9fa;
            padding: 0;
            margin: 0;
        }
        .navbar {
            background-color: #212529;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        h1 {
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .welcome-message {
            margin-bottom: 1.5rem;
            color: #6c757d;
        }
        .card {
            background-color: white;
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .card-stats {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
        .card-stats h3 {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: #6c757d;
        }
        .card-stats .number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }
        .card-stats-products {
            border-left: 5px solid #28a745;
        }
        .card-stats-woocommerce {
            border-left: 5px solid #007bff;
        }
        .card-stats-credits {
            border-left: 5px solid #6f42c1;
        }
        .card-recent {
            padding: 1.5rem;
        }
        .card-recent h2 {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            color: #343a40;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-item h3 {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .activity-item p {
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .activity-item .date {
            font-size: 0.875rem;
            color: #adb5bd;
        }
        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .badge-product {
            background-color: #28a745;
        }
        .badge-woocommerce {
            background-color: #007bff;
        }
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: all 0.15s ease-in-out;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-group {
            margin-top: 1rem;
            text-align: center;
        }
        .btn-group .btn {
            margin: 0 0.5rem;
        }
        .note {
            background-color: #e3f2fd;
            border: 1px solid #bee5eb;
            border-radius: 0.25rem;
            padding: 1rem;
            margin: 2rem 0;
            color: #0c5460;
        }
        .footer {
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
            color: #6c757d;
            font-size: 0.875rem;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }
        .col-md-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
            padding-right: 15px;
            padding-left: 15px;
            box-sizing: border-box;
        }
        .col-md-12 {
            flex: 0 0 100%;
            max-width: 100%;
            padding-right: 15px;
            padding-left: 15px;
            box-sizing: border-box;
        }
        
        /* Make it responsive */
        @media (max-width: 768px) {
            .col-md-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            .card-stats .number {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="/" class="navbar-brand">RoboSEO</a>
        <div>
            <a href="<?= $login_url ?>" class="btn btn-outline-light" style="color: white; border: 1px solid white; background: transparent;">Sign in</a>
        </div>
    </div>

    <div class="container">
        <?= $auth_warning ?>
        
        <h1>Dashboard</h1>
        <div class="welcome-message">Welcome, RoboSEO User!</div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card card-stats card-stats-products">
                    <h3>Total Products</h3>
                    <div class="number"><?= $products_count ?></div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card card-stats card-stats-woocommerce">
                    <h3>WooCommerce Products</h3>
                    <div class="number"><?= $woocommerce_products_count ?></div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card card-stats card-stats-credits">
                    <h3>Available Credits</h3>
                    <div class="number"><?= $available_credits ?></div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card card-recent">
                    <h2>Recent Activity</h2>
                    
                    <?php foreach ($recent_products as $product): ?>
                        <div class="activity-item">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['description']) ?></p>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="date">Updated: <?= $product['updated_at'] ?></span>
                                <?php if ($product['type'] === 'woocommerce'): ?>
                                    <span class="badge badge-woocommerce">WooCommerce</span>
                                <?php else: ?>
                                    <span class="badge badge-product">Product</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="btn-group">
            <a href="#" class="btn btn-primary" onclick="window.location.href='<?= $dashboard_url ?>'; return false;">Manage Products</a>
            <a href="#" class="btn btn-secondary" onclick="window.location.href='<?= $dashboard_url ?>?view=more'; return false;">View with More Data</a>
        </div>
        
        <div class="note">
            <strong>Note:</strong> This is a demonstration of the dashboard functionality. The actual dashboard will display real data from the database when accessed through the Symfony application.
        </div>
    </div>
    
    <div class="footer">
        RoboSEO Dashboard Demo
    </div>
</body>
</html>