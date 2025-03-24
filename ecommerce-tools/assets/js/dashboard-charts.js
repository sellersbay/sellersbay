/**
 * Dashboard Charts
 * Handles initialization and configuration of dashboard charts
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts if elements exist
    if (document.getElementById('aiActivityChart')) {
        initAIActivityChart();
    }
    
    if (document.getElementById('productStatusChart')) {
        initProductStatusChart();
    }
    
    /**
     * Initialize AI Activity Chart - Shows AI processing over time
     */
    function initAIActivityChart() {
        // Fetch chart data from API
        fetch('/api/dashboard/charts-data')
            .then(response => response.json())
            .then(data => {
                const months = data.monthly_activity.map(item => item.month);
                const counts = data.monthly_activity.map(item => item.count);
                
                const ctx = document.getElementById('aiActivityChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [{
                            label: 'AI Processed Products',
                            data: counts,
                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                            borderColor: 'rgba(78, 115, 223, 1)',
                            pointRadius: 3,
                            pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                            pointBorderColor: 'rgba(78, 115, 223, 1)',
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                            pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                left: 10,
                                right: 25,
                                top: 25,
                                bottom: 0
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false
                                },
                                ticks: {
                                    maxTicksLimit: 7
                                }
                            },
                            y: {
                                ticks: {
                                    maxTicksLimit: 5,
                                    padding: 10,
                                    precision: 0 // Only show whole numbers
                                },
                                grid: {
                                    color: "rgb(234, 236, 244)",
                                    zeroLineColor: "rgb(234, 236, 244)",
                                    drawBorder: false,
                                    borderDash: [2],
                                    zeroLineBorderDash: [2]
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: "rgb(255, 255, 255)",
                                bodyColor: "#858796",
                                titleMarginBottom: 10,
                                titleColor: '#6e707e',
                                titleFontSize: 14,
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                xPadding: 15,
                                yPadding: 15,
                                displayColors: false,
                                intersect: false,
                                mode: 'index',
                                caretPadding: 10,
                                callbacks: {
                                    label: function(context) {
                                        var label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += context.parsed.y;
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error loading AI activity chart data:', error);
                const chartContainer = document.getElementById('aiActivityChart').parentNode;
                chartContainer.innerHTML = '<div class="alert alert-danger">Failed to load chart data. Please try again later.</div>';
            });
    }
    
    /**
     * Initialize Product Status Chart - Shows distribution of product statuses
     */
    function initProductStatusChart() {
        // Fetch chart data from API
        fetch('/api/dashboard/charts-data')
            .then(response => response.json())
            .then(data => {
                const labels = data.product_categories.map(item => item.category_name);
                const values = data.product_categories.map(item => item.count);
                
                const ctx = document.getElementById('productStatusChart').getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
                            hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'],
                            hoverBorderColor: "rgba(234, 236, 244, 1)",
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                backgroundColor: "rgb(255, 255, 255)",
                                bodyColor: "#858796",
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                xPadding: 15,
                                yPadding: 15,
                                displayColors: false,
                                caretPadding: 10,
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error loading product status chart data:', error);
                const chartContainer = document.getElementById('productStatusChart').parentNode;
                chartContainer.innerHTML = '<div class="alert alert-danger">Failed to load chart data. Please try again later.</div>';
            });
    }
    
    // Time range selector functionality for charts
    document.querySelectorAll('[data-time-range]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const range = this.getAttribute('data-time-range');
            
            // Update dropdown button text
            const dropdownButton = this.closest('.dropdown').querySelector('.dropdown-toggle');
            if (dropdownButton) {
                const rangeText = {
                    '30': 'Last 30 days',
                    '90': 'Last 90 days',
                    '365': 'Last year'
                };
                dropdownButton.innerHTML = `<i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>`;
            }
            
            // Fetch new data based on range
            fetch(`/api/dashboard/charts-data?range=${range}`)
                .then(response => response.json())
                .then(data => {
                    // Refresh charts with new data
                    if (document.getElementById('aiActivityChart')) {
                        // We'd update the chart here, but for simplicity we'll just reinitialize
                        // In a real app, you'd use chart.data.datasets[0].data = newData and chart.update()
                        document.getElementById('aiActivityChart').remove();
                        const chartContainer = document.querySelector('.dashboard-chart-container');
                        chartContainer.innerHTML = '<canvas id="aiActivityChart" height="300"></canvas>';
                        initAIActivityChart();
                    }
                })
                .catch(error => console.error('Error updating chart data:', error));
        });
    });
});