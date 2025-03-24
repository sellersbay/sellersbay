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
    <title>RoboSEO Dashboard - Mantis Bootstrap</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Mantis Bootstrap CSS -->
    <link href="/assets/mantis/css/mantis-bootstrap.css" rel="stylesheet">
    
    <!-- Chart.js for dashboard charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.2.1/dist/chart.umd.min.js"></script>
</head>
<body class="mantis-app-container">
    
    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">RoboSEO</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="connect.php">Connect</a></li>
                    <li class="nav-item"><a class="nav-link" href="account.php">Account</a></li>
                    <li class="nav-item"><a class="nav-link" href="support.php">Support</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($username) ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-coins me-1"></i> <?= $available_credits ?> Credits
                        </a>
                    </li>
                </ul>
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
                <div class="col-lg-8">
                    <div class="mantis-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mantis-card-title">Monthly Performance</h6>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="timeRangeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-calendar me-1"></i> Last 6 Months
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="timeRangeDropdown">
                                    <li><a class="dropdown-item" href="#" data-time-range="30">Last 30 Days</a></li>
                                    <li><a class="dropdown-item" href="#" data-time-range="90">Last 3 Months</a></li>
                                    <li><a class="dropdown-item" href="#" data-time-range="180">Last 6 Months</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="performanceChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="mantis-card">
                        <div class="card-header">
                            <h6 class="mantis-card-title">Content Breakdown</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="contentBreakdownChart" height="300"></canvas>
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
    <script src="/assets/mantis/js/mantis-bootstrap.js"></script>
    
    <!-- Dashboard Charts Initialization -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Performance Chart
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            const performanceChart = new Chart(performanceCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Products Processed',
                        data: [35, 42, 67, 89, 112, 134],
                        borderColor: '#2196f3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        tension: 0.3,
                        fill: true
                    }, {
                        label: 'Products Exported',
                        data: [28, 35, 52, 64, 87, 103],
                        borderColor: '#4caf50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Content Breakdown Chart
            const breakdownCtx = document.getElementById('contentBreakdownChart').getContext('2d');
            const breakdownChart = new Chart(breakdownCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Full Descriptions', 'Short Descriptions', 'Meta Descriptions', 'Image Alt Texts'],
                    datasets: [{
                        data: [65, 42, 78, 37],
                        backgroundColor: [
                            '#2196f3',
                            '#4caf50',
                            '#03a9f4',
                            '#ff9800'
                        ],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
            
            // Handle Refresh Activity Button
            document.getElementById('reloadActivity').addEventListener('click', function() {
                // In a real app, this would fetch from the server
                // For demo, we'll just show a loading indicator
                const activityTable = document.getElementById('activity_log');
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
                    document.getElementById('timeRangeDropdown').innerHTML = `
                        <i class="fas fa-calendar me-1"></i> Last ${range} Days
                    `;
                    
                    // In a real app, this would update the chart data
                    // For demo, we'll just show a loading indicator and simulated data change
                    MantisUtils.showNotification('Time range updated to last ' + range + ' days', 'info');
                });
            });
        });
    </script>
</body>
</html>