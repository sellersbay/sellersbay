/**
 * Dashboard Interactions
 * Handles UI interactions and dynamic content loading for the dashboard
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Handle dashboard tabs
    initDashboardTabs();
    
    // Recent activity reload
    initRecentActivityReload();
    
    // Alert auto-dismiss
    initAlertDismissal();
    
    /**
     * Initialize Bootstrap tooltips
     */
    function initTooltips() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        if (tooltipTriggerList.length) {
            [...tooltipTriggerList].map(tooltipTriggerEl => {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }
    
    /**
     * Initialize dashboard tabs functionality
     */
    function initDashboardTabs() {
        const dashboardTabs = document.getElementById('dashboardTabs');
        if (!dashboardTabs) return;
        
        // Get all tab links and content panels
        const tabLinks = dashboardTabs.querySelectorAll('.nav-link');
        const tabContents = document.querySelectorAll('.tab-pane');
        
        // Add click event listeners to each tab
        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs
                tabLinks.forEach(link => link.classList.remove('active'));
                tabContents.forEach(content => {
                    content.classList.remove('show', 'active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding content
                const targetId = this.getAttribute('data-bs-target');
                if (targetId) {
                    const targetContent = document.querySelector(targetId);
                    if (targetContent) {
                        targetContent.classList.add('show', 'active');
                        
                        // If this tab needs to load data via AJAX
                        if (targetContent.getAttribute('data-lazy-load') === 'true' && 
                            !targetContent.getAttribute('data-loaded')) {
                            
                            const loadingSpinner = targetContent.querySelector('.spinner-border');
                            if (loadingSpinner) loadingSpinner.classList.remove('d-none');
                            
                            // Get endpoint from data attribute
                            const endpoint = targetContent.getAttribute('data-endpoint');
                            if (endpoint) {
                                fetch(endpoint)
                                    .then(response => response.json())
                                    .then(data => {
                                        // Render content based on tab ID
                                        targetContent.innerHTML = renderTabContent(data, targetContent.id);
                                        targetContent.setAttribute('data-loaded', 'true');
                                    })
                                    .catch(error => {
                                        console.error('Error loading tab content:', error);
                                        targetContent.innerHTML = `
                                            <div class="alert alert-danger">
                                                Failed to load content. Please try again.
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                        `;
                                    });
                            }
                        }
                    }
                }
            });
        });
    }
    
    /**
     * Initialize recent activity reload functionality
     */
    function initRecentActivityReload() {
        const reloadBtn = document.getElementById('reloadActivity');
        if (!reloadBtn) return;
        
        reloadBtn.addEventListener('click', function() {
            const activityContainer = document.getElementById('recentActivityContainer');
            if (!activityContainer) return;
            
            // Show loading indicator
            activityContainer.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            // Fetch updated activity data
            fetch('/api/dashboard/recent-activity')
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    
                    if (!data.products || data.products.length === 0) {
                        html = '<p class="text-center text-muted my-4">No recent activity to display</p>';
                    } else {
                        html = '<div class="list-group">';
                        
                        data.products.forEach(product => {
                            const shortDesc = product.shortDescription || 'No description available';
                            const truncatedDesc = shortDesc.length > 100 
                                ? shortDesc.substring(0, 97) + '...' 
                                : shortDesc;
                            
                            html += `
                                <div class="list-group-item activity-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">${escapeHtml(product.name)}</h6>
                                        <small>${product.updatedAt}</small>
                                    </div>
                                    <p class="mb-1">${escapeHtml(truncatedDesc)}</p>
                                    <div class="mt-2">
                                        <a href="/ai/generate/${product.id}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        ${product.status === 'ai_processed' ? `
                                            <a href="/woocommerce/export/${product.id}" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-upload"></i> Export
                                            </a>
                                        ` : ''}
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += `
                            </div>
                            <div class="mt-3 text-center">
                                <a href="/woocommerce/dashboard" class="btn btn-primary">
                                    <i class="fas fa-list"></i> View All Products
                                </a>
                            </div>
                        `;
                    }
                    
                    activityContainer.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading recent activity:', error);
                    activityContainer.innerHTML = `
                        <div class="alert alert-danger">
                            Failed to load recent activity. Please try again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                });
        });
    }
    
    /**
     * Initialize automatic alert dismissal
     */
    function initAlertDismissal() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        
        alerts.forEach(alert => {
            setTimeout(() => {
                const closeButton = alert.querySelector('.btn-close');
                if (closeButton) {
                    closeButton.click();
                } else {
                    alert.classList.add('fade');
                    setTimeout(() => {
                        alert.remove();
                    }, 150);
                }
            }, 5000); // Auto-dismiss after 5 seconds
        });
    }
    
    /**
     * Render tab content based on tab ID and data
     * @param {Object} data - The data from API
     * @param {string} tabId - The ID of the tab
     * @return {string} HTML content
     */
    function renderTabContent(data, tabId) {
        let html = '';
        
        switch (tabId) {
            case 'activity':
                html = renderActivityTab(data);
                break;
            case 'stats':
                html = renderStatsTab(data);
                break;
            default:
                html = '<div class="p-4">Content loaded successfully.</div>';
        }
        
        return html;
    }
    
    /**
     * Render activity tab content
     * @param {Object} data - The activity data
     * @return {string} HTML content
     */
    function renderActivityTab(data) {
        let html = '';
        
        if (!data.products || data.products.length === 0) {
            html = '<p class="text-center text-muted my-4">No recent activity to display</p>';
        } else {
            html = '<div class="list-group">';
            
            data.products.forEach(product => {
                const shortDesc = product.shortDescription || 'No description available';
                const truncatedDesc = shortDesc.length > 100 
                    ? shortDesc.substring(0, 97) + '...' 
                    : shortDesc;
                
                html += `
                    <div class="list-group-item activity-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${escapeHtml(product.name)}</h6>
                            <small>${product.updatedAt}</small>
                        </div>
                        <p class="mb-1">${escapeHtml(truncatedDesc)}</p>
                        <div class="mt-2">
                            <a href="/ai/generate/${product.id}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            ${product.status === 'ai_processed' ? `
                                <a href="/woocommerce/export/${product.id}" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-upload"></i> Export
                                </a>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
        }
        
        return html;
    }
    
    /**
     * Render stats tab content
     * @param {Object} data - The stats data
     * @return {string} HTML content
     */
    function renderStatsTab(data) {
        // This would render a complex stats view
        // For simplicity, we'll return a placeholder
        return `
            <div class="alert alert-info">
                Stats data loaded successfully. This section would display detailed statistics.
            </div>
        `;
    }
    
    /**
     * HTML escape utility function
     * @param {string} str - The string to escape
     * @return {string} Escaped string
     */
    function escapeHtml(str) {
        if (!str) return '';
        
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});