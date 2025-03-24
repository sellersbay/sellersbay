/**
 * Dashboard Charts
 * Handles initialization and configuration of dashboard charts
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard charts if the elements exist
    if (document.getElementById('aiActivityChart')) {
        initAIActivityChart();
    }
    
    if (document.getElementById('productStatusChart')) {
        initProductStatusChart();
    }
    
    // Function to load chart data and initialize activity chart
    function initAIActivityChart() {
        // Normally we would fetch this data from an API
        // For demo purposes, we'll use sample data
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const processedCounts = [65, 78, 52, 115, 98, 87];
        
        const ctx = document.getElementById('aiActivityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'AI Processed Products',
                    data: processedCounts,
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
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgb(255, 255, 255)',
                        bodyColor: '#858796',
                        titleMarginBottom: 10,
                        titleColor: '#6e707e',
                        titleFontSize: 14,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        padding: 15,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Handle time range dropdown
        const timeRangeLinks = document.querySelectorAll('[data-time-range]');
        timeRangeLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const range = this.getAttribute('data-time-range');
                updateTimeRange(range);
            });
        });
        
        // Function to update chart with new time range
        function updateTimeRange(days) {
            // In a real implementation, this would fetch new data based on the time range
            // For demo purposes, we'll just show an alert
            console.log(`Time range updated to ${days} days`);
            
            // Sample data update logic
            let newMonths = [];
            let newData = [];
            
            // Generate some random sample data based on the selected range
            if (days === '30') {
                newMonths = ['Mar 1', 'Mar 8', 'Mar 15', 'Mar 22', 'Mar 29'];
                newData = [22, 31, 29, 36, 42];
            } else if (days === '90') {
                newMonths = ['Jan', 'Feb', 'Mar'];
                newData = [75, 95, 140];
            } else if (days === '365') {
                newMonths = ['Apr', 'Jul', 'Oct', 'Jan', 'Apr'];
                newData = [240, 312, 365, 398, 452];
            }
            
            // Update chart data
            const chart = Chart.getChart('aiActivityChart');
            if (chart) {
                chart.data.labels = newMonths;
                chart.data.datasets[0].data = newData;
                chart.update();
            }
        }
    }
    
    // Function to initialize product status chart
    function initProductStatusChart() {
        // Sample data for the product status chart
        const labels = ['Imported', 'AI Processed', 'Exported', 'Draft'];
        const values = [35, 25, 20, 10];
        const backgroundColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'];
        const hoverBackgroundColors = ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'];
        
        const ctx = document.getElementById('productStatusChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: backgroundColors,
                    hoverBackgroundColor: hoverBackgroundColors,
                    hoverBorderColor: 'rgba(234, 236, 244, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 10
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgb(255, 255, 255)',
                        bodyColor: '#858796',
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        padding: 15,
                        displayColors: false
                    }
                },
                cutout: '70%'
            }
        });
    }
    
    // Handle resize events to properly size charts
    window.addEventListener('resize', function() {
        const aiActivityChart = Chart.getChart('aiActivityChart');
        const productStatusChart = Chart.getChart('productStatusChart');
        
        if (aiActivityChart) {
            aiActivityChart.resize();
        }
        
        if (productStatusChart) {
            productStatusChart.resize();
        }
    });
});

// Error handling for charts
function handleChartError(chartId, message) {
    const container = document.getElementById(chartId).parentNode;
    if (container) {
        container.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
        `;
    }
}

// Expose chart functionality globally
window.dashboardCharts = {
    refreshCharts: function() {
        const aiActivityChart = Chart.getChart('aiActivityChart');
        const productStatusChart = Chart.getChart('productStatusChart');
        
        if (aiActivityChart) {
            // In a real-world scenario, this would fetch fresh data from the server
            aiActivityChart.update();
        }
        
        if (productStatusChart) {
            productStatusChart.update();
        }
    }
};