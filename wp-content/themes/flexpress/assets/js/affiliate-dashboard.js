/**
 * FlexPress Affiliate Dashboard Interactions
 * 
 * Provides interactive dashboard features including performance charts,
 * promotional link generation, and real-time commission tracking.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard components
    initializePerformanceCharts();
    initializeLinkGenerator();
    initializeCommissionTracking();
    initializeRealTimeUpdates();
});

/**
 * Initialize performance charts using Chart.js
 */
function initializePerformanceCharts() {
    const monthlyChartCtx = document.getElementById('monthly-performance-chart');
    
    if (monthlyChartCtx && typeof Chart !== 'undefined') {
        // Monthly Performance Chart
        const monthlyData = window.affiliateMonthlyStats || [];
        
        const monthlyChart = new Chart(monthlyChartCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(stat => {
                    const date = new Date(stat.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Commission Earned',
                    data: monthlyData.map(stat => parseFloat(stat.commission || 0)),
                    borderColor: 'var(--color-accent, #ff6b35)',
                    backgroundColor: 'rgba(255, 107, 53, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#ffffff'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#ffffff'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    y: {
                        ticks: {
                            color: '#ffffff',
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                }
            }
        });
    }
}

/**
 * Initialize promotional link generator
 */
function initializeLinkGenerator() {
    const linkForm = document.getElementById('link-generator-form');
    const generateLinkBtn = document.getElementById('generate-link-btn');
    
    if (linkForm) {
        linkForm.addEventListener('submit', function(e) {
            e.preventDefault();
            generateAffiliateLink();
        });
    }
    
    if (generateLinkBtn) {
        generateLinkBtn.addEventListener('click', function(e) {
            e.preventDefault();
            generateAffiliateLink();
        });
    }
}

/**
 * Generate affiliate link
 */
function generateAffiliateLink() {
    const baseUrlInput = document.getElementById('base_url');
    const campaignInput = document.getElementById('campaign_name');
    const affiliateCodeElement = document.getElementById('affiliate_code_display');
    const generatedLinkDiv = document.getElementById('generated-link');
    
    if (!baseUrlInput || !affiliateCodeElement) {
        return;
    }
    
    const baseUrl = baseUrlInput.value.trim();
    const affiliateCode = affiliateCodeElement.textContent.trim();
    const campaign = campaignInput ? campaignInput.value.trim() : '';
    
    if (!baseUrl) {
        showNotification('Please enter a URL to promote', 'error');
        return;
    }
    
    try {
        // Generate affiliate link
        const url = new URL(baseUrl);
        url.searchParams.set('ref', affiliateCode);
        
        if (campaign) {
            url.searchParams.set('campaign', campaign);
        }
        
        const affiliateLink = url.toString();
        
        // Display generated link
        if (generatedLinkDiv) {
            generatedLinkDiv.innerHTML = `
                <div class="card bg-dark border-secondary mt-3">
                    <div class="card-body">
                        <h6 class="card-title text-white">Your Affiliate Link:</h6>
                        <div class="input-group">
                            <input type="text" class="form-control" id="generated-affiliate-link" 
                                   value="${affiliateLink}" readonly>
                            <button class="btn btn-outline-light" type="button" id="copy-generated-link">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            Share this link to earn commissions on signups and purchases!
                        </small>
                    </div>
                </div>
            `;
            
            // Add copy functionality
            const copyBtn = document.getElementById('copy-generated-link');
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    copyToClipboard(affiliateLink);
                });
            }
            
            generatedLinkDiv.style.display = 'block';
        }
    } catch (error) {
        showNotification('Please enter a valid URL', 'error');
    }
}

/**
 * Initialize commission tracking updates
 */
function initializeCommissionTracking() {
    const refreshBtn = document.getElementById('refresh-commissions');
    
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            refreshCommissionData();
        });
    }
    
    // Auto-refresh every 5 minutes
    setInterval(function() {
        refreshCommissionData(true); // Silent refresh
    }, 5 * 60 * 1000);
}

/**
 * Initialize real-time updates
 */
function initializeRealTimeUpdates() {
    // Check for new commissions periodically
    setInterval(checkForNewCommissions, 2 * 60 * 1000); // Every 2 minutes
    
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (typeof bootstrap !== 'undefined' && tooltips.length > 0) {
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
    }
}

/**
 * Refresh commission data via AJAX
 */
async function refreshCommissionData(silent = false) {
    const refreshBtn = document.getElementById('refresh-commissions');
    
    if (!silent && refreshBtn) {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    }
    
    try {
        const response = await fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_affiliate_dashboard_data',
                nonce: affiliateNonce || ''
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateDashboardStats(data.data);
            
            if (!silent) {
                showNotification('Commission data updated!', 'success');
            }
        } else {
            throw new Error(data.data?.message || 'Failed to refresh data');
        }
        
    } catch (error) {
        console.error('Error refreshing commission data:', error);
        if (!silent) {
            showNotification('Error refreshing data', 'error');
        }
    } finally {
        if (!silent && refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
        }
    }
}

/**
 * Check for new commissions
 */
async function checkForNewCommissions() {
    const lastCheckTime = localStorage.getItem('lastCommissionCheck');
    const currentTime = new Date().toISOString();
    
    try {
        const response = await fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'check_new_affiliate_commissions',
                since: lastCheckTime || currentTime,
                nonce: affiliateNonce || ''
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.data.new_commissions > 0) {
            showNotification(
                `🎉 You earned ${data.data.new_commissions} new commission${data.data.new_commissions > 1 ? 's' : ''}!`,
                'success',
                5000
            );
            
            // Refresh dashboard data
            refreshCommissionData(true);
        }
        
        localStorage.setItem('lastCommissionCheck', currentTime);
        
    } catch (error) {
        console.error('Error checking for new commissions:', error);
    }
}

/**
 * Update dashboard statistics
 */
function updateDashboardStats(data) {
    const statsElements = {
        totalCommissions: document.getElementById('total-commissions'),
        pendingCommissions: document.getElementById('pending-commissions'),
        totalSignups: document.getElementById('total-signups'),
        totalRebills: document.getElementById('total-rebills'),
        conversionRate: document.getElementById('conversion-rate'),
        thisMonthCommissions: document.getElementById('this-month-commissions')
    };
    
    if (data.affiliate) {
        const affiliate = data.affiliate;
        
        if (statsElements.totalCommissions) {
            statsElements.totalCommissions.textContent = '$' + parseFloat(affiliate.total_commission || 0).toFixed(2);
        }
        
        if (statsElements.pendingCommissions) {
            statsElements.pendingCommissions.textContent = '$' + parseFloat(affiliate.pending_commission || 0).toFixed(2);
        }
        
        if (statsElements.totalSignups) {
            statsElements.totalSignups.textContent = affiliate.total_signups || 0;
        }
        
        if (statsElements.totalRebills) {
            statsElements.totalRebills.textContent = affiliate.total_rebills || 0;
        }
        
        // Calculate conversion rate (signups / total clicks - would need click tracking)
        if (statsElements.conversionRate) {
            const totalClicks = affiliate.total_clicks || affiliate.total_signups || 1;
            const conversionRate = ((affiliate.total_signups || 0) / totalClicks * 100).toFixed(1);
            statsElements.conversionRate.textContent = conversionRate + '%';
        }
    }
    
    // Update recent commissions table
    if (data.recent_commissions) {
        updateCommissionsTable(data.recent_commissions);
    }
}

/**
 * Update recent commissions table
 */
function updateCommissionsTable(commissions) {
    const tableBody = document.getElementById('recent-commissions-tbody');
    
    if (!tableBody) return;
    
    tableBody.innerHTML = commissions.map(commission => {
        const date = new Date(commission.created_at).toLocaleDateString();
        const amount = parseFloat(commission.commission_amount || 0).toFixed(2);
        const statusBadge = getStatusBadge(commission.status);
        const typeBadge = getTypeBadge(commission.transaction_type);
        
        return `
            <tr>
                <td>${date}</td>
                <td>${typeBadge}</td>
                <td>$${amount}</td>
                <td>${statusBadge}</td>
            </tr>
        `;
    }).join('');
}

/**
 * Get status badge HTML
 */
function getStatusBadge(status) {
    const statusConfig = {
        pending: { class: 'warning', text: 'Pending' },
        approved: { class: 'info', text: 'Approved' },
        paid: { class: 'success', text: 'Paid' },
        cancelled: { class: 'danger', text: 'Cancelled' }
    };
    
    const config = statusConfig[status] || statusConfig.pending;
    return `<span class="badge bg-${config.class}">${config.text}</span>`;
}

/**
 * Get transaction type badge HTML
 */
function getTypeBadge(type) {
    const typeConfig = {
        signup: { class: 'primary', text: 'Signup' },
        rebill: { class: 'success', text: 'Rebill' },
        ppv: { class: 'info', text: 'PPV' }
    };
    
    const config = typeConfig[type] || typeConfig.signup;
    return `<span class="badge bg-${config.class}">${config.text}</span>`;
}

/**
 * Copy text to clipboard
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showNotification('Link copied to clipboard!', 'success');
    } catch (error) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showNotification('Link copied to clipboard!', 'success');
        } catch (err) {
            showNotification('Unable to copy link', 'error');
        }
        
        document.body.removeChild(textArea);
    }
}

/**
 * Show notification toast
 */
function showNotification(message, type = 'info', duration = 3000) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after duration
    setTimeout(() => {
        if (notification && notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

/**
 * Export dashboard data
 */
function exportDashboardData() {
    const exportBtn = document.getElementById('export-data-btn');
    
    if (exportBtn) {
        exportBtn.addEventListener('click', async function() {
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'export_affiliate_data',
                        format: 'csv',
                        nonce: affiliateNonce || ''
                    })
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `affiliate-data-${new Date().toISOString().split('T')[0]}.csv`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                } else {
                    throw new Error('Export failed');
                }
                
            } catch (error) {
                console.error('Export error:', error);
                showNotification('Export failed', 'error');
            }
        });
    }
} 