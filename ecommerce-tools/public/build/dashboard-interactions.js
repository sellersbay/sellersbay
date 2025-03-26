/**
 * Dashboard Interactions - Handles tabs, refresh buttons, and other interactive elements
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard interactions initializing...');
    
    // Tab handling - ensure proper tab switching
    const dashboardTabs = document.getElementById('dashboardTabs');
    if (dashboardTabs) {
        const tabLinks = dashboardTabs.querySelectorAll('.nav-link');
        const tabContents = document.querySelectorAll('.tab-pane');
        
        tabLinks.forEach(tabLink => {
            tabLink.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs and contents
                tabLinks.forEach(link => link.classList.remove('active'));
                tabContents.forEach(content => {
                    content.classList.remove('show', 'active');
                });
                
                // Add active class to current tab and content
                this.classList.add('active');
                const target = document.querySelector(this.dataset.bsTarget);
                if (target) {
                    target.classList.add('show', 'active');
                }
            });
        });
    }
    
    // Handle refresh button for Recent Activity tab
    const reloadActivityBtn = document.getElementById('reloadActivity');
    if (reloadActivityBtn) {
        reloadActivityBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Refresh activity button clicked');
            
            const activityContainer = document.getElementById('recentActivityContainer');
            if (activityContainer) {
                // Show loading indicator
                activityContainer.innerHTML = `
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                
                // Make an AJAX request to get updated activity data
                fetchRecentActivity()
                    .then(html => {
                        activityContainer.innerHTML = html;
                        console.log('Activity refreshed successfully');
                    })
                    .catch(error => {
                        console.error('Error refreshing activity:', error);
                        // Use the createSampleActivityTable as a fallback
                        if (typeof error === 'string') {
                            activityContainer.innerHTML = error;
                        } else {
                            activityContainer.innerHTML = createSampleActivityTable();
                        }
                    });
            }
        });
    }
    
    /**
     * Fetches recent activity data via AJAX
     * @returns {Promise<string>} HTML content for the activity container
     */
    function fetchRecentActivity() {
        return new Promise((resolve, reject) => {
            console.log('Fetching recent activity data...');
            const xhr = new XMLHttpRequest();
            
            // Make sure the URL is correct by checking if we're in a subdirectory
            let url = '/dashboard/recent-activity';
            const currentPath = window.location.pathname;
            if (currentPath.includes('/ecommerce-tools/')) {
                // We're in a subdirectory, adjust the URL
                url = '/ecommerce-tools/public/dashboard/recent-activity';
                console.log('Using adjusted URL for subdirectory:', url);
            }
            
            xhr.open('GET', url, true);
            
            xhr.onload = function() {
                if (this.status >= 200 && this.status < 300) {
                    console.log('Activity data received successfully');
                    resolve(xhr.response);
                } else {
                    console.error('Error fetching recent activity:', this.status, xhr.statusText);
                    const errorHtml = `
                        <div class="alert alert-danger">
                            Failed to refresh activity. Status: ${this.status}
                            <button class="btn btn-sm btn-outline-danger ms-3" onclick="document.getElementById('reloadActivity').click()">
                                Retry
                            </button>
                        </div>
                    `;
                    reject(errorHtml);
                }
            };
            
            xhr.onerror = function() {
                console.error('Network error when fetching recent activity');
                const errorHtml = `
                    <div class="alert alert-danger">
                        Network error occurred. Please check your connection and try again.
                        <button class="btn btn-sm btn-outline-danger ms-3" onclick="document.getElementById('reloadActivity').click()">
                            Retry
                        </button>
                    </div>
                `;
                reject(errorHtml);
            };
            
            xhr.timeout = 10000; // 10 seconds timeout
            xhr.ontimeout = function() {
                console.error('Request timed out when fetching recent activity');
                const errorHtml = `
                    <div class="alert alert-danger">
                        Request timed out. Please try again.
                        <button class="btn btn-sm btn-outline-danger ms-3" onclick="document.getElementById('reloadActivity').click()">
                            Retry
                        </button>
                    </div>
                `;
                reject(errorHtml);
            };
            
            try {
                xhr.send();
                console.log('Request sent to:', url);
            } catch (e) {
                console.error('Error sending request:', e);
                reject('Error sending request: ' + e.message);
            }
        });
    }
    
    /**
     * Creates sample activity table HTML for fallback when AJAX fails
     * @returns {string} HTML for sample activity table
     */
    function createSampleActivityTable() {
        console.log('Creating sample activity table as fallback');
        const now = new Date();
        const formattedDate = now.toISOString().slice(0, 16).replace('T', ' ');
        
        return `
            <div class="alert alert-warning mb-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Showing sample data. Could not connect to server.
            </div>
            <div class="table-responsive">
                <table class="mantis-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Description</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>WDS WordPress Slide Show Pro</td>
                            <td>Elevate your website with WDS WordPress Slide Show Pro!</td>
                            <td>${formattedDate}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="#" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-upload"></i> Export
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>WordPress Any User Twitter Feed Pro</td>
                            <td>Any User Twitter Feed for WordPress. Put your twitter...</td>
                            <td>${formattedDate}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="#" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-upload"></i> Export
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Fully Managed Website Hosting</td>
                            <td>Fully managed website hosting (Includes 1 free website)</td>
                            <td>${formattedDate}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="#" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-center">
                <a href="/woocommerce" class="btn mantis-btn mantis-btn-primary">
                    <i class="fas fa-list"></i> View All Products
                </a>
            </div>
        `;
    }
    
    // Handle dropdown menu selections
    const dropdownItems = document.querySelectorAll('.dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update the dropdown button text (if applicable)
            const dropdownToggle = this.closest('.dropdown').querySelector('.dropdown-toggle');
            if (dropdownToggle) {
                dropdownToggle.textContent = this.textContent;
            }
            
            // Add any additional handling for dropdown selections here
            console.log('Selected dropdown item:', this.textContent);
        });
    });
});