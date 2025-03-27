<?php
// Standalone dashboard demo page using Mantis Bootstrap theme
// This allows for direct viewing of the dashboard implementation

// Get URL parameters with defaults
$products_count = isset($_GET['products']) ? (int)$_GET['products'] : 250;
$woocommerce_products_count = isset($_GET['woocommerce']) ? (int)$_GET['woocommerce'] : 180;
$available_credits = isset($_GET['credits']) ? (int)$_GET['credits'] : 100;
$username = isset($_GET['username']) ? $_GET['username'] : 'John Doe';
$subscription_tier = isset($_GET['tier']) ? $_GET['tier'] : 'Pro';
$billing_date = isset($_GET['billing']) ? $_GET['billing'] : date('m/d/Y', strtotime('+30 days'));

// Connected stores
$connected_stores = [
    'WooCommerce' => true,
    'Shopify' => true,
    'Magento' => false
];

// Sample recent activity for demonstration
$recent_activity = [
    [
        'date' => date('m/d/Y', strtotime('-1 day')),
        'action' => 'Imported 20 Products',
        'details' => 'WooCommerce → Pending Approval'
    ],
    [
        'date' => date('m/d/Y', strtotime('-3 day')),
        'action' => 'AI Generated 5 Descriptions',
        'details' => 'Shopify → Ready for Review'
    ],
    [
        'date' => date('m/d/Y', strtotime('-5 day')),
        'action' => 'Exported 15 Products',
        'details' => 'WooCommerce → Published'
    ],
    [
        'date' => date('m/d/Y', strtotime('-7 day')),
        'action' => 'Purchased Credits',
        'details' => 'Medium Package → 50 Credits Added'
    ],
    [
        'date' => date('m/d/Y', strtotime('-10 day')),
        'action' => 'Updated 8 Product Descriptions',
        'details' => 'Shopify → Published'
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="http://localhost/sellersbay/ecommerce-tools/public/" />
    <title>RoboSEO Dashboard - Mantis Bootstrap</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Dashboard CSS -->
    <link href="assets/styles/dashboard.css?t=<?= time() ?>" rel="stylesheet">
    
    <!-- Mantis Bootstrap CSS -->
    <link href="assets/mantis/css/mantis-bootstrap.css?t=<?= time() ?>" rel="stylesheet">
    <link href="assets/mantis/css/notifications.css?t=<?= time() ?>" rel="stylesheet">
    
    <!-- Custom navbar fixes -->
    <style>
    /* Simple reset for the navbar */
    .navbar {
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }
    .navbar-nav .nav-link {
        padding: 0.5rem 0;
    }
    @media (min-width: 992px) {
        .navbar-nav .nav-link {
            padding: 0.5rem 1rem;
        }
    }
    </style>
    
    <!-- Chart.js for dashboard charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.2.1/dist/chart.umd.min.js" integrity="sha384-LZ9+fR8NE1iR9u2mPCo8o46haoV86DEe3Kk0zQmKUXzA9Yz5j9ZbI6QrKn1ULlta" crossorigin="anonymous"></script>
    
    <!-- Debug script to detect 404 errors -->
    <script>
    (function() {
        console.log('Dashboard debug script loaded');
        
        // Monitor resource loading errors
        window.addEventListener('error', function(e) {
            if (e.target.tagName === 'IMG' || e.target.tagName === 'SCRIPT' || e.target.tagName === 'LINK') {
                console.error('Resource failed to load:', e.target.src || e.target.href);
            }
        }, true);
        
        // Fallback for Chart.js
        window.addEventListener('DOMContentLoaded', function() {
            if (typeof Chart === 'undefined') {
                console.error('Chart.js failed to load from CDN. Loading local fallback.');
                var fallbackScript = document.createElement('script');
                fallbackScript.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
                document.head.appendChild(fallbackScript);
            }
        });
    })();
    </script>
</head>
<body class="mantis-app-container">
    <!-- Hidden element for dashboard data -->
    <div id="dashboardData" 
         data-monthly-activity='<?php echo json_encode([
             ["month" => "Jan", "count" => 34],
             ["month" => "Feb", "count" => 42],
             ["month" => "Mar", "count" => 51],
             ["month" => "Apr", "count" => 48],
             ["month" => "May", "count" => 63],
             ["month" => "Jun", "count" => 58]
         ]); ?>'
         data-product-status='<?php echo json_encode([
             ["status" => "Published", "count" => 65],
             ["status" => "Draft", "count" => 42],
             ["status" => "Pending Review", "count" => 28],
             ["status" => "Scheduled", "count" => 15]
         ]); ?>'
         data-content-stats='<?php echo json_encode([
             "full_descriptions" => 65,
             "short_descriptions" => 42,
             "meta_descriptions" => 78,
             "image_alts" => 37
         ]); ?>'
    ></div>
    
    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="/sellersbay/ecommerce-tools/assets/images/sellersbay-logo.png" alt="Seller's Bay" height="40">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="connect.php">Connect</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="account.php">Account</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="support.php">Support</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="buy-credits.php" class="btn btn-primary me-2 d-none d-lg-inline-block">
                        <i class="fas fa-coins"></i> Buy Credits
                    </a>
                    <div class="me-3 d-none d-lg-block">
                        <i class="fas fa-coins text-warning"></i> <?= $available_credits ?> Credits
                    </div>
                    <div class="dropdown d-none d-lg-block">
                        <button class="btn dropdown-toggle p-0 border-0" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($username) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="mantis-content">
        <div class="container mt-4">
            <!-- Welcome & Subscription Section -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="mantis-welcome-card">
                        <h4>Welcome back, <span id="username"><?= htmlspecialchars($username) ?></span>!</h4>
                        <p>Subscription: <span id="plan"><?= htmlspecialchars($subscription_tier) ?></span>
                            <span class="badge bg-primary"><?= htmlspecialchars($subscription_tier) ?></span>
                        </p>
                        <p>AI Credits Left: <span id="credits"><?= $available_credits ?></span></p>
                        <p>Next Billing Date: <span id="billing_date"><?= $billing_date ?></span></p>
                        <div class="d-flex gap-2">
                            <a href="upgrade.php" class="btn btn-primary">Upgrade Plan</a>
                            <a href="buy-credits.php" class="btn btn-secondary">Buy More Credits</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- General Stats Overview -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="mantis-card mantis-stats-card">
                        <div class="card-content">
                            <div class="stats-icon primary">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stats-info">
                                <div class="stats-title">Total Products Processed</div>
                                <p class="stats-value" id="total_processed"><?= $products_count ?></p>
                            </div>
                        </div>
                        <div class="stats-footer">
                            <div class="stats-trend trend-positive">
                                <i class="fas fa-arrow-up"></i> 5.2%
                            </div>
                            <span>Since last month</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mantis-card mantis-stats-card">
                        <div class="card-content">
                            <div class="stats-icon success">
                                <i class="fas fa-file-export"></i>
                            </div>
                            <div class="stats-info">
                                <div class="stats-title">Total Products Exported</div>
                                <p class="stats-value" id="total_exported"><?= $woocommerce_products_count ?></p>
                            </div>
                        </div>
                        <div class="stats-footer">
                            <div class="stats-trend trend-positive">
                                <i class="fas fa-arrow-up"></i> 3.7%
                            </div>
                            <span>Since last month</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mantis-card mantis-stats-card">
                        <div class="card-content">
                            <div class="stats-icon info">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="stats-info">
                                <div class="stats-title">Connected Stores</div>
                                <div class="mt-2" id="connected_stores">
                                    <?php foreach($connected_stores as $store => $status): ?>
                                        <span class="mantis-badge <?= $status ? 'mantis-badge-success' : 'mantis-badge-danger' ?>">
                                            <?= htmlspecialchars($store) ?> <?= $status ? '✅' : '❌' ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="stats-footer">
                            <a href="integrations.php" class="text-info">
                                <i class="fas fa-cog me-1"></i> Manage Integrations
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Navigation Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="mantis-section-title mb-3">Quick Navigation</div>
                </div>
                <div class="col-md-3">
                    <a href="woo_dashboard.php" class="btn mantis-btn mantis-btn-primary w-100 mb-3">
                        <i class="fab fa-wordpress fa-fw me-2"></i> WooCommerce Dashboard
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="shopify_dashboard.php" class="btn mantis-btn mantis-btn-outline-primary w-100 mb-3">
                        <i class="fab fa-shopify fa-fw me-2"></i> Shopify Dashboard
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="magento_dashboard.php" class="btn mantis-btn mantis-btn-outline-primary w-100 mb-3">
                        <i class="fas fa-shopping-cart fa-fw me-2"></i> Magento Dashboard
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="integrations.php" class="btn mantis-btn mantis-btn-outline-primary w-100 mb-3">
                        <i class="fas fa-plug fa-fw me-2"></i> Manage Integrations
                    </a>
                </div>
            </div>
            
            <!-- Recent Activity Log -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="mantis-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mantis-card-title">Recent Activity</h6>
                            <button id="reloadActivity" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="mantis-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody id="activity_log">
                                        <?php foreach($recent_activity as $activity): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($activity['date']) ?></td>
                                                <td><?= htmlspecialchars($activity['action']) ?></td>
                                                <td><?= htmlspecialchars($activity['details']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3 text-center">
                                <a href="#" class="btn mantis-btn mantis-btn-primary">
                                    <i class="fas fa-history me-1"></i> View All Activity
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Analytics Section -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="mantis-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0">Monthly Activity</h5>
                            <div class="d-flex gap-2">
                                <div class="dropdown me-2">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="timeRangeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-calendar me-1"></i> Last 6 Months
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="timeRangeDropdown">
                                        <li><a class="dropdown-item" href="#" data-time-range="30">Last 30 Days</a></li>
                                        <li><a class="dropdown-item" href="#" data-time-range="90">Last 90 Days</a></li>
                                        <li><a class="dropdown-item" href="#" data-time-range="180">Last 180 Days</a></li>
                                    </ul>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="aiActivityOptions" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="aiActivityOptions">
                                        <li><a class="dropdown-item" href="#">Export as PDF</a></li>
                                        <li><a class="dropdown-item" href="#">Export as CSV</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Monthly Activity Chart -->
                            <div class="dashboard-chart-container">
                                <canvas id="aiActivityChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mantis-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0">Content Breakdown</h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="contentBreakdownOptions" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="contentBreakdownOptions">
                                    <li><a class="dropdown-item" href="#">Export as PDF</a></li>
                                    <li><a class="dropdown-item" href="#">Export as CSV</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Content Breakdown Chart -->
                            <div class="dashboard-chart-container">
                                <canvas id="productStatusChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- AI Credit Usage Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="mantis-card">
                        <div class="card-header">
                            <h6 class="mantis-card-title">AI Credit Usage</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="text-center">
                                        <div class="rounded-circle bg-primary text-white mx-auto" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-file-alt fa-2x"></i>
                                        </div>
                                        <h4 class="mt-3">65</h4>
                                        <p class="text-muted mb-0">Full Descriptions</p>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="text-center">
                                        <div class="rounded-circle bg-success text-white mx-auto" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-align-left fa-2x"></i>
                                        </div>
                                        <h4 class="mt-3">42</h4>
                                        <p class="text-muted mb-0">Short Descriptions</p>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="text-center">
                                        <div class="rounded-circle bg-info text-white mx-auto" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-search fa-2x"></i>
                                        </div>
                                        <h4 class="mt-3">78</h4>
                                        <p class="text-muted mb-0">Meta Descriptions</p>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="text-center">
                                        <div class="rounded-circle bg-warning text-white mx-auto" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image fa-2x"></i>
                                        </div>
                                        <h4 class="mt-3">37</h4>
                                        <p class="text-muted mb-0">Image Alt Texts</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Credit Usage Efficiency</span>
                                    <span>75%</span>
                                </div>
                                <div class="mantis-progress">
                                    <div class="mantis-progress-bar success" style="width: 75%;"></div>
                                </div>
                                <p class="text-muted mt-2 small">
                                    <i class="fas fa-info-circle me-1"></i> Higher efficiency means more content per credit. Improve by using bulk generation features.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Mantis Bootstrap JS -->
    <script src="assets/mantis/js/mantis-bootstrap.js?t=<?= time() ?>"></script>
    
    <!-- Dashboard Charts JS -->
    <script src="build/dashboard-charts.js?t=<?= time() ?>"></script>
    
    <!-- Other Dashboard Interactions -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle Refresh Activity Button
            document.getElementById('reloadActivity')?.addEventListener('click', function() {
                // In a real app, this would fetch from the server
                // For demo, we'll just show a loading indicator
                const activityTable = document.getElementById('activity_log');
                if (!activityTable) return;
                
                activityTable.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                `;
                
                // Simulate loading delay
                setTimeout(() => {
                    activityTable.innerHTML = `
                        <tr>
                            <td>${new Date().toLocaleDateString()}</td>
                            <td>Refreshed Activity Log</td>
                            <td>Manual Refresh</td>
                        </tr>
                        <?php foreach($recent_activity as $activity): ?>
                            <tr>
                                <td><?= htmlspecialchars($activity['date']) ?></td>
                                <td><?= htmlspecialchars($activity['action']) ?></td>
                                <td><?= htmlspecialchars($activity['details']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    `;
                }, 1000);
            });
            
            // Time Range Dropdown Functionality
            document.querySelectorAll('[data-time-range]').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const range = this.getAttribute('data-time-range');
                    const timeRangeDropdown = document.getElementById('timeRangeDropdown');
                    if (!timeRangeDropdown) return;
                    
                    timeRangeDropdown.innerHTML = `
                        <i class="fas fa-calendar me-1"></i> Last ${range} Days
                    `;
                    
                    // In a real app, this would update the chart data
                    if (typeof MantisUtils !== 'undefined') {
                        MantisUtils.showNotification('Time range updated to last ' + range + ' days', 'info');
                    }
                });
            });
        });
    </script>
</body>
</html>