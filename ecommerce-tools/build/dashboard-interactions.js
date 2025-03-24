/**
 * Dashboard Interactions
 * Handles UI interactions and dynamic content loading for the dashboard
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize tab functionality
    initTabs();
    
    // Initialize activity reloading
    initActivityReload();
    
    // Auto-dismiss alerts after 5 seconds
    initAlertDismissal();
    
    /**
     * Initialize Bootstrap tooltips
     */
    function initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    /**
     * Initialize tab functionality
     */
    function initTabs() {
        const dashboardTabs = document.getElementById('dashboardTabs');
        if (!dashboardTabs) return;
        
        const tabLinks = dashboardTabs.querySelectorAll('.nav-link');
        const tabContents = document.querySelectorAll('.tab-pane');
        
        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs
                tabLinks.forEach(item => item.classList.remove('active'));
                tabContents.forEach(item => {
                    item.classList.remove('show', 'active');
                });
                
                // Add active class to current tab
                this.classList.add('active');
                const target = document.querySelector(this.getAttribute('data-bs-target'));
                if (target) {
                    target.classList.add('show', 'active');
                    
                    // If this tab needs to load data via AJAX
                    if (target.getAttribute('data-lazy-load') === 'true' && !target.getAttribute('data-loaded')) {
                        const spinner = target.querySelector('.spinner-border');
                        if (spinner) spinner.classList.remove('d-none');
                        
                        const endpoint = target.getAttribute('data-endpoint');
                        if (endpoint) {
                            fetch(endpoint)
                                .then(response => response.json())
                                .then(data => {
                                    target.innerHTML = renderTabContent(data, target.id);
                                    target.setAttribute('data-loaded', 'true');
                                })
                                .catch(error => {
                                    console.error('Error loading tab content:', error);
                                    target.innerHTML = createErrorMessage('Failed to load content. Please try again.');
                                });
                        }
                    }
                }
            });
        });
    }
    
    /**
     * Initialize activity reload functionality
     */
    function initActivityReload() {
        const reloadBtn = document.getElementById('reloadActivity');
        if (!reloadBtn) return;
        
        reloadBtn.addEventListener('click', function() {
            const container = document.getElementById('recentActivityContainer');
            if (!container) return;
            
            // Show loading spinner
            container.innerHTML = createLoadingSpinner();
            
            // In a real application, this would fetch from an API endpoint
            // For demo purposes, we'll simulate a delay and then update the content
            setTimeout(() => {
                // Sample activity data
                const activities = [
                    {
                        name: 'Ultra HD Monitor',
                        updated: '2025-03-16 15:30',
                        description: 'A stunning 32-inch 4K monitor with vivid colors and incredible detail.',
                        status: 'ai_processed',
                        id: 123
                    },
                    {
                        name: 'Wireless Gaming Mouse',
                        updated: '2025-03-16 14:45',
                        description: 'Ultra-responsive gaming mouse with RGB lighting and programmable buttons.',
                        status: 'imported',
                        id: 124
                    },
                    {
                        name: 'Mechanical Keyboard',
                        updated: '2025-03-16 12:15',
                        description: 'Tactile mechanical keyboard with customizable keycaps and RGB backlighting.',
                        status: 'ai_processed',
                        id: 125
                    }
                ];
                
                container.innerHTML = createActivityList(activities);
            }, 1000);
        });
    }
    
    /**
     * Initialize alert auto-dismissal
     */
    function initAlertDismissal() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            setTimeout(() => {
                const closeBtn = alert.querySelector('.btn-close');
                if (closeBtn) {
                    closeBtn.click();
                } else {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                }
            }, 5000);
        });
    }
    
    /**
     * Create a loading spinner element
     */
    function createLoadingSpinner() {
        return `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading latest activity...</p>
            </div>
        `;
    }
    
    /**
     * Create an error message element
     */
    function createErrorMessage(message) {
        return `
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                ${escapeHtml(message)}
            </div>
        `;
    }
    
    /**
     * Create an activity list from data
     */
    function createActivityList(activities) {
        if (!activities || activities.length === 0) {
            return '<p class="text-center text-muted my-4">No recent activity to display</p>';
        }
        
        let html = '<div class="list-group">';
        
        activities.forEach(activity => {
            html += `
                <div class="list-group-item activity-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${escapeHtml(activity.name)}</h6>
                        <small>${escapeHtml(activity.updated)}</small>
                    </div>
                    <p class="mb-1">${escapeHtml(activity.description || 'No description available')}</p>
                    <div class="mt-2">
                        <a href="/ai/generate/${activity.id}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        ${activity.status === 'ai_processed' ? `
                            <a href="/woocommerce/export/${activity.id}" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-upload"></i> Export
                            </a>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        html += `
            <div class="mt-3 text-center">
                <a href="/woocommerce/dashboard" class="btn btn-primary">
                    <i class="fas fa-list"></i> View All Products
                </a>
            </div>
        `;
        
        return html;
    }
    
    /**
     * Render content for a specific tab
     */
    function renderTabContent(data, tabId) {
        let html = '';
        
        switch (tabId) {
            case 'activity':
                html = createActivityList(data.activities);
                break;
            case 'statistics':
                html = createStatisticsContent(data.statistics);
                break;
            default:
                html = '<div class="p-4">Content loaded via AJAX</div>';
        }
        
        return html;
    }
    
    /**
     * Create statistics content
     */
    function createStatisticsContent(stats) {
        if (!stats) {
            return createErrorMessage('Statistics data is not available.');
        }
        
        // This would be implemented based on the stats data structure
        return '<div class="p-4">Statistics content would be rendered here.</div>';
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});

// Expose global functions
window.dashboardInteractions = {
    refreshActivity: function() {
        const reloadBtn = document.getElementById('reloadActivity');
        if (reloadBtn) {
            reloadBtn.click();
        }
    },
    
    showNotification: function(message, type = 'info') {
        const container = document.querySelector('.container-fluid');
        if (!container) return;
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show dashboard-alert`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => {
                alertDiv.remove();
            }, 500);
        }, 5000);
    }
};