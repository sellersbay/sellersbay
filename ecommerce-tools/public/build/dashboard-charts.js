/**
 * Dashboard Charts - Connects dashboard data to Chart.js visualizations
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard charts initializing...');
    
    // Get the dashboard data from the hidden element
    const dashboardData = document.getElementById('dashboardData');
    if (!dashboardData) {
        console.error('Dashboard data element not found');
        // Create fallback charts with sample data
        initActivityChartWithSampleData();
        initProductStatusChartWithSampleData();
        return;
    }
    
    // Parse the data from the data attributes
    let monthlyActivity;
    let productStatus;
    let contentStats;
    
    try {
        // Try to parse the data, using empty defaults if parsing fails
        try {
            monthlyActivity = JSON.parse(dashboardData.dataset.monthlyActivity || '[]');
            console.log('Monthly activity data:', monthlyActivity);
        } catch (e) {
            console.warn('Failed to parse monthly activity data:', e);
            monthlyActivity = [];
        }
        
        try {
            productStatus = JSON.parse(dashboardData.dataset.productStatus || '[]');
            console.log('Product status data:', productStatus);
        } catch (e) {
            console.warn('Failed to parse product status data:', e);
            productStatus = [];
        }
        
        try {
            contentStats = JSON.parse(dashboardData.dataset.contentStats || '{}');
            console.log('Content stats data:', contentStats);
        } catch (e) {
            console.warn('Failed to parse content stats data:', e);
            contentStats = {};
        }
        
        // Initialize the charts with the data
        if (monthlyActivity && monthlyActivity.length > 0) {
            initActivityChart(monthlyActivity);
        } else {
            initActivityChartWithSampleData();
        }
        
        if (productStatus && productStatus.length > 0) {
            initProductStatusChart(productStatus);
        } else {
            initProductStatusChartWithSampleData();
        }
    } catch (error) {
        console.error('Error initializing dashboard charts:', error);
        // Create fallback charts with sample data
        initActivityChartWithSampleData();
        initProductStatusChartWithSampleData();
    }
    // Move chart variables outside the event handler to make them globally accessible
    let fullActivityData = [];
    let activityChart = null;
    
    // Function to set up time range filtering buttons
    function setupTimeRangeFiltering() {
        const timeRangeSelectors = document.querySelectorAll('[data-time-range]');
        console.log('Setting up time range selectors:', timeRangeSelectors.length);
        
        timeRangeSelectors.forEach(selector => {
            selector.addEventListener('click', function(e) {
                e.preventDefault();
                const range = parseInt(this.dataset.timeRange, 10);
                console.log('Selected time range:', range);
                
                // Update the dropdown button text
                const dropdownToggle = this.closest('.dropdown').querySelector('.dropdown-toggle');
                if (dropdownToggle) {
                    let rangeText = '';
                    if (range === 30) rangeText = 'Last 30 days';
                    else if (range === 90) rangeText = 'Last 90 days';
                    else if (range === 365) rangeText = 'Last year';
                    
                    dropdownToggle.innerHTML = `<i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>`;
                    
                    // Add a small indicator that a filter is active
                    const filterIndicator = document.createElement('span');
                    filterIndicator.className = 'ms-1 badge bg-primary';
                    filterIndicator.textContent = rangeText;
                    filterIndicator.style.fontSize = '0.7rem';
                    dropdownToggle.appendChild(filterIndicator);
                }
                
                // Get parent dropdown menu and close it
                const dropdownMenu = this.closest('.dropdown-menu');
                if (dropdownMenu) {
                    // Find all items and remove active class
                    dropdownMenu.querySelectorAll('.dropdown-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    
                    // Add active class to the selected item
                    this.classList.add('active');
                }
                
                // Filter the chart by the selected time range
                updateChartByTimeRange(range);
            });
        });
    }
    
    /**
     * Initialize the AI Activity Chart
     * @param {Array} activityData - Monthly activity data from the server
     */
    function initActivityChart(activityData) {
        console.log('Initializing activity chart with data:', activityData);
        // Store the original data for filtering
        fullActivityData = activityData;
        
        const ctx = document.getElementById('aiActivityChart');
        if (!ctx) {
            console.error('AI Activity Chart canvas not found');
            return;
        }
        
        // COMPLETE REWRITE FOR PROPER CHART DISPLAY
        
        // Current date info for comparisons
        const today = new Date();
        const currentYear = today.getFullYear();
        const currentMonth = today.getMonth(); // 0-based (0 = January)
        const currentMonthDate = new Date(currentYear, currentMonth, 1);
        
        // Month name to index mapping
        const monthNames = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];
        
        console.log('*** DEBUG: Current month/year:', monthNames[currentMonth], currentYear);
        
        // Format the activity data for Chart.js with robust date handling
        try {
            console.log('*** DEBUG: Raw activity data:', JSON.stringify(activityData));
            
            // STEP 1: Transform the data with complete date information
            const dataWithDates = [];
            
            activityData.forEach(item => {
                if (!item.month) {
                    console.warn('Month data missing in item:', item);
                    return;
                }
                
                // Get month index from name
                const monthIndex = monthNames.indexOf(item.month);
                if (monthIndex === -1) {
                    console.warn('Invalid month name in item:', item);
                    return;
                }
                
                // Use the year provided in the data or assume a reasonable default
                let year;
                if (item.year) {
                    // Use year from data if available
                    year = parseInt(item.year, 10);
                } else {
                    // Handle legacy data format - infer year based on current date and month order
                    // If month index is greater than current month, it's likely from previous year
                    year = (monthIndex > currentMonth) ? currentYear - 1 : currentYear;
                }
                
                // Create a complete date object for this data point
                const itemDate = new Date(year, monthIndex, 1);
                
                // Only include dates up to the current month (no future dates)
                if (itemDate <= currentMonthDate) {
                    dataWithDates.push({
                        date: itemDate,
                        month: item.month,
                        year: year,
                        count: item.count,
                        label: `${item.month} ${year}`,
                        sortKey: (year * 100) + monthIndex // For reliable sorting
                    });
                }
            });
            
            console.log('*** DEBUG: Processed data with dates:', dataWithDates);
            
            // STEP 2: Ensure proper chronological order (oldest to newest)
            dataWithDates.sort((a, b) => a.sortKey - b.sortKey);
            
            console.log('*** DEBUG: Sorted data:', dataWithDates);
            
            // STEP 3: Extract the final chart data
            const months = dataWithDates.map(item => item.label);
            const counts = dataWithDates.map(item => item.count);
            
            // STEP 4: Update the chart
            // Create the chart and store the instance for future updates
            activityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Products Processed',
                        data: counts,
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        lineTension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
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
                            }
                        },
                        y: {
                            ticks: {
                                maxTicksLimit: 5,
                                padding: 10,
                                callback: function(value) {
                                    return value;
                                }
                            },
                            grid: {
                                color: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                borderDashOffset: [2]
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            displayColors: false,
                            backgroundColor: "rgb(255, 255, 255)",
                            bodyColor: "#858796",
                            titleColor: "#6e707e",
                            titleFontSize: 14,
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            caretPadding: 10,
                            callbacks: {
                                label: function(context) {
                                    return 'Products: ' + context.raw;
                                }
                            }
                        }
                    }
                }
            });
            
            // Add event listeners for time range selection
            setupTimeRangeFiltering();
            
            // Return to allow caller to use months and counts if needed
            return { months, counts };
        } catch (error) {
            console.error('Error processing activity data:', error);
        }
        
        // Add fallback data if no valid months were found - handled within the try/catch block
        // This redundant chart creation has been removed to prevent multiple instances
    }
    
    /**
     * Initialize the AI Activity Chart with sample data
     * Used as a fallback when real data is unavailable
     */
    function initActivityChartWithSampleData() {
        console.log('Initializing activity chart with sample data');
        const ctx = document.getElementById('aiActivityChart');
        if (!ctx) {
            console.error('AI Activity Chart canvas not found');
            return;
        }
        
        // Use the current month and 5 previous months for the sample data
        const today = new Date();
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                          'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        const months = [];
        const currentMonth = today.getMonth(); // 0-based (0 = January)
        
        // Add the past 6 months (or fewer if we're early in the year) with year information
        for (let i = 5; i >= 0; i--) {
            let monthIndex = currentMonth - i;
            let year = currentYear;
            
            // If month index goes negative, it's from the previous year
            if (monthIndex < 0) {
                monthIndex += 12; // Loop around for previous year
                year -= 1;
            }
            
            // Add month with year information
            months.unshift(`${monthNames[monthIndex]} ${year}`);
        }
        
        // Sample data that shows a growth trend
        const counts = [35, 42, 65, 89, 110, 135];
        
        // Create the chart
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Products Processed',
                    data: counts,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    lineTension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
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
                        }
                    },
                    y: {
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10,
                            callback: function(value) {
                                return value;
                            }
                        },
                        grid: {
                            color: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            borderDashOffset: [2]
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        displayColors: false,
                        backgroundColor: "rgb(255, 255, 255)",
                        bodyColor: "#858796",
                        titleColor: "#6e707e",
                        titleFontSize: 14,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        caretPadding: 10,
                        callbacks: {
                            label: function(context) {
                                return 'Products: ' + context.raw;
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Initialize the Product Status Chart
     * @param {Array} statusData - Product category and count data
     */
    function initProductStatusChart(statusData) {
        console.log('Initializing product status chart with data:', statusData);
        const ctx = document.getElementById('productStatusChart');
        if (!ctx) {
            console.error('Product Status Chart canvas not found');
            return;
        }
        
        // Group the data by status (using category as a proxy for status)
        const statusLabels = [];
        const statusCounts = [];
        const backgroundColors = [
            'rgba(78, 115, 223, 0.8)',   // Primary (Processed)
            'rgba(28, 200, 138, 0.8)',   // Success (Pending)
            'rgba(54, 185, 204, 0.8)',   // Info (Draft)
            'rgba(246, 194, 62, 0.8)'    // Warning (Published)
        ];
        
        try {
            // Create the dataset from the product status data
            if (statusData && statusData.length > 0) {
                statusData.forEach((item, index) => {
                    if (item && item.category_name) {
                        statusLabels.push(item.category_name);
                        statusCounts.push(item.count || 0);
                    }
                });
            }
        } catch (error) {
            console.error('Error processing product status data:', error);
        }
        
        // If no data, add placeholder categories
        if (statusLabels.length === 0) {
            console.warn('No product status data found, using fallback data');
            statusLabels.push('Imported Products', 'AI Processed', 'Exported');
            statusCounts.push(10, 2, 1);
        }
        
        // Create the chart
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: backgroundColors,
                    hoverBackgroundColor: backgroundColors.map(color => color.replace('0.8', '1')),
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw + ' products';
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Initialize the Product Status Chart with sample data
     * Used as a fallback when real data is unavailable
     */
    function initProductStatusChartWithSampleData() {
        console.log('Initializing product status chart with sample data');
        const ctx = document.getElementById('productStatusChart');
        if (!ctx) {
            console.error('Product Status Chart canvas not found');
            return;
        }
        
        // Sample data that resembles typical product statuses
        const statusLabels = ['Imported Products', 'AI Processed', 'Exported'];
        const statusCounts = [10, 2, 1];
        const backgroundColors = [
            'rgba(78, 115, 223, 0.8)',   // Primary (Processed)
            'rgba(28, 200, 138, 0.8)',   // Success (Pending)
            'rgba(54, 185, 204, 0.8)'    // Info (Draft)
        ];
        
        // Create the chart
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: backgroundColors,
                    hoverBackgroundColor: backgroundColors.map(color => color.replace('0.8', '1')),
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw + ' products';
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Filter activity data by time range
     * @param {Array} data - The original activity data
     * @param {number} days - Number of days to include (30, 90, or 365)
     * @returns {Array} Filtered data
     */
    function filterActivityDataByTimeRange(data, days) {
        if (!data || data.length === 0) {
            return [];
        }
        
        console.log(`Filtering activity data for the last ${days} days`);
        
        const today = new Date();
        const cutoffDate = new Date();
        cutoffDate.setDate(today.getDate() - days);
        
        const currentYear = today.getFullYear();
        const currentMonth = today.getMonth(); // 0-based (0 = January)
        
        // Format the activity data for Chart.js
        const months = [];
        const counts = [];
        const monthNames = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];
        
        // Use the data with year information
        const dataWithDates = data.map(item => {
            if (!item.month) return null;
            
            const monthIndex = monthNames.indexOf(item.month);
            if (monthIndex === -1) return null;
            
            // Use the year from the data if available, fall back to current year
            const year = item.year ? parseInt(item.year, 10) : currentYear;
            
            // Create a date for filtering
            const itemDate = new Date(year, monthIndex, 15); // Use middle of month
            
            return {
                date: itemDate,
                month: item.month,
                year: year,
                count: item.count,
                label: `${item.month} ${year}`
            };
        }).filter(item => item !== null);
        
        // Filter by the cutoff date and sort chronologically
        const filteredData = dataWithDates
            .filter(item => item.date >= cutoffDate && item.date <= today)
            .sort((a, b) => a.date - b.date);
        
        // Extract the sorted labels and counts
        filteredData.forEach(item => {
            months.push(item.label);
            counts.push(item.count);
        });
        
        return { months, counts };
    }
    
    /**
     * Update chart with the filtered data
     * @param {number} days - Number of days to filter by
     */
    function updateChartByTimeRange(days) {
        if (!activityChart) {
            console.error('Activity chart not initialized');
            return;
        }
        
        const { months, counts } = filterActivityDataByTimeRange(fullActivityData, days);
        
        if (months.length === 0) {
            console.warn('No valid activity data found for the selected time range');
            return;
        }
        
        // Update the chart data
        activityChart.data.labels = months;
        activityChart.data.datasets[0].data = counts;
        activityChart.update();
    }
    
    // Handle time range selection for the activity chart
    const timeRangeSelectors = document.querySelectorAll('[data-time-range]');
    timeRangeSelectors.forEach(selector => {
        selector.addEventListener('click', function(e) {
            e.preventDefault();
            const range = parseInt(this.dataset.timeRange, 10);
            console.log('Selected time range:', range);
            
            // Update the dropdown button text
            const dropdownToggle = this.closest('.dropdown').querySelector('.dropdown-toggle');
            if (dropdownToggle) {
                let rangeText = '';
                if (range === 30) rangeText = 'Last 30 days';
                else if (range === 90) rangeText = 'Last 90 days';
                else if (range === 365) rangeText = 'Last year';
                
                dropdownToggle.innerHTML = `<i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>`;
                
                // Add a small indicator that a filter is active
                const filterIndicator = document.createElement('span');
                filterIndicator.className = 'ms-1 badge bg-primary';
                filterIndicator.textContent = rangeText;
                filterIndicator.style.fontSize = '0.7rem';
                dropdownToggle.appendChild(filterIndicator);
            }
            
            // Get parent dropdown menu and close it
            const dropdownMenu = this.closest('.dropdown-menu');
            if (dropdownMenu) {
                // Find all items and remove active class
                dropdownMenu.querySelectorAll('.dropdown-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Add active class to the selected item
                this.classList.add('active');
            }
            
            // Filter the chart by the selected time range
            updateChartByTimeRange(range);
        });
    });
});