/**
 * FlexPress Earnings Dashboard JavaScript
 * 
 * @package FlexPress
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    let revenueChart, breakdownChart, comparisonChart;
    let currentPeriod = 'month';
    let currentData = window.flexpressInitialData || {};
    
    // Initialize on document ready
    $(document).ready(function() {
        initCharts();
        initEventHandlers();
    });
    
    /**
     * Initialize charts
     */
    function initCharts() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js not loaded');
            return;
        }
        
        // Revenue Over Time Chart
        const revenueCtx = document.getElementById('revenue-chart');
        if (revenueCtx) {
            const dailyData = currentData.daily_revenue || {};
            const labels = Object.keys(dailyData);
            const data = Object.values(dailyData);
            
            revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Daily Revenue',
                        data: data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '$' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toFixed(0);
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Transaction Breakdown Chart
        const breakdownCtx = document.getElementById('breakdown-chart');
        if (breakdownCtx) {
            const breakdown = currentData.breakdown || {};
            
            breakdownChart = new Chart(breakdownCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Subscriptions', 'Rebills', 'PPV Unlocks', 'Refunds', 'Chargebacks'],
                    datasets: [{
                        data: [
                            breakdown.subscriptions?.amount || 0,
                            breakdown.rebills?.amount || 0,
                            breakdown.unlocks?.amount || 0,
                            breakdown.refunds?.amount || 0,
                            breakdown.chargebacks?.amount || 0
                        ],
                        backgroundColor: [
                            '#667eea',
                            '#38ef7d',
                            '#f093fb',
                            '#ff6b6b',
                            '#f5576c'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return label + ': $' + value.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Comparison Chart
        const comparisonCtx = document.getElementById('comparison-chart');
        if (comparisonCtx) {
            comparisonChart = new Chart(comparisonCtx, {
                type: 'bar',
                data: {
                    labels: ['Revenue Comparison'],
                    datasets: [
                        {
                            label: 'Gross Revenue',
                            data: [currentData.gross_revenue || 0],
                            backgroundColor: '#667eea',
                            borderRadius: 8
                        },
                        {
                            label: 'Affiliate Commissions',
                            data: [currentData.affiliate_commissions || 0],
                            backgroundColor: '#f5576c',
                            borderRadius: 8
                        },
                        {
                            label: 'Net Revenue',
                            data: [currentData.net_revenue || 0],
                            backgroundColor: '#38ef7d',
                            borderRadius: 8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': $' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toFixed(0);
                                }
                            }
                        }
                    }
                }
            });
        }
    }
    
    /**
     * Update charts with new data
     */
    function updateCharts(data) {
        // Update revenue chart
        if (revenueChart) {
            const dailyData = data.daily_revenue || {};
            revenueChart.data.labels = Object.keys(dailyData);
            revenueChart.data.datasets[0].data = Object.values(dailyData);
            revenueChart.update();
        }
        
        // Update breakdown chart
        if (breakdownChart) {
            const breakdown = data.breakdown || {};
            breakdownChart.data.datasets[0].data = [
                breakdown.subscriptions?.amount || 0,
                breakdown.rebills?.amount || 0,
                breakdown.unlocks?.amount || 0,
                breakdown.refunds?.amount || 0,
                breakdown.chargebacks?.amount || 0
            ];
            breakdownChart.update();
        }
        
        // Update comparison chart
        if (comparisonChart) {
            comparisonChart.data.datasets[0].data = [data.gross_revenue || 0];
            comparisonChart.data.datasets[1].data = [data.affiliate_commissions || 0];
            comparisonChart.data.datasets[2].data = [data.net_revenue || 0];
            comparisonChart.update();
        }
    }
    
    /**
     * Initialize event handlers
     */
    function initEventHandlers() {
        // Period selector buttons
        $('.period-selector .button').on('click', function() {
            const period = $(this).data('period');
            
            // Update button states
            $('.period-selector .button').removeClass('button-primary').addClass('button');
            $(this).removeClass('button').addClass('button-primary');
            
            if (period === 'custom') {
                $('.custom-date-range').slideDown();
            } else {
                $('.custom-date-range').slideUp();
                loadEarningsData(period);
            }
        });
        
        // Custom date range apply button
        $('#apply-custom-range').on('click', function() {
            const startDate = $('#start-date').val();
            const endDate = $('#end-date').val();
            
            if (!startDate || !endDate) {
                alert('Please select both start and end dates');
                return;
            }
            
            loadEarningsData('custom', startDate, endDate);
        });
        
        // CSV Export button
        $('#export-csv').on('click', function() {
            const startDate = $('#start-date').val() || '';
            const endDate = $('#end-date').val() || '';
            
            const params = new URLSearchParams({
                action: 'flexpress_export_earnings_csv',
                nonce: flexpressEarnings.nonce,
                period: currentPeriod,
                start_date: startDate,
                end_date: endDate
            });
            
            window.location.href = flexpressEarnings.ajaxurl + '?' + params.toString();
        });
    }
    
    /**
     * Load earnings data via AJAX
     */
    function loadEarningsData(period, startDate, endDate) {
        currentPeriod = period;
        
        // Show loading state
        $('.flexpress-earnings-loading').show();
        $('.flexpress-earnings-cards, .flexpress-earnings-charts, .flexpress-earnings-breakdown, .flexpress-earnings-transactions').css('opacity', '0.5');
        
        $.ajax({
            url: flexpressEarnings.ajaxurl,
            type: 'POST',
            data: {
                action: 'flexpress_get_earnings_data',
                nonce: flexpressEarnings.nonce,
                period: period,
                start_date: startDate || '',
                end_date: endDate || ''
            },
            success: function(response) {
                if (response.success) {
                    currentData = response.data;
                    updateDashboard(response.data);
                } else {
                    alert('Error loading earnings data: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                alert('Error loading earnings data: ' + error);
            },
            complete: function() {
                $('.flexpress-earnings-loading').hide();
                $('.flexpress-earnings-cards, .flexpress-earnings-charts, .flexpress-earnings-breakdown, .flexpress-earnings-transactions').css('opacity', '1');
            }
        });
    }
    
    /**
     * Update dashboard with new data
     */
    function updateDashboard(data) {
        // Update summary cards
        $('[data-metric="gross_revenue"]').text('$' + formatNumber(data.gross_revenue));
        $('[data-metric="net_revenue"]').text('$' + formatNumber(data.net_revenue));
        $('[data-metric="total_transactions"]').text(formatNumber(data.total_transactions, 0));
        $('[data-metric="affiliate_commissions"]').text('$' + formatNumber(data.affiliate_commissions));
        $('[data-metric="average_transaction"]').text('$' + formatNumber(data.average_transaction));
        $('[data-metric="refunds_chargebacks"]').text('$' + formatNumber(data.refunds_total + data.chargebacks_total));
        
        // Update breakdown table
        const breakdown = data.breakdown;
        $('[data-breakdown="subscriptions-count"]').text(breakdown.subscriptions.count);
        $('[data-breakdown="subscriptions-amount"]').text('$' + formatNumber(breakdown.subscriptions.amount));
        $('[data-breakdown="rebills-count"]').text(breakdown.rebills.count);
        $('[data-breakdown="rebills-amount"]').text('$' + formatNumber(breakdown.rebills.amount));
        $('[data-breakdown="unlocks-count"]').text(breakdown.unlocks.count);
        $('[data-breakdown="unlocks-amount"]').text('$' + formatNumber(breakdown.unlocks.amount));
        $('[data-breakdown="refunds-count"]').text(breakdown.refunds.count);
        $('[data-breakdown="refunds-amount"]').text('-$' + formatNumber(breakdown.refunds.amount));
        $('[data-breakdown="chargebacks-count"]').text(breakdown.chargebacks.count);
        $('[data-breakdown="chargebacks-amount"]').text('-$' + formatNumber(breakdown.chargebacks.amount));
        
        // Update transactions table
        updateTransactionsTable(data.transactions);
        
        // Update charts
        updateCharts(data);
    }
    
    /**
     * Update transactions table
     */
    function updateTransactionsTable(transactions) {
        const tbody = $('#transactions-table-body');
        tbody.empty();
        
        const displayCount = Math.min(transactions.length, 50);
        
        for (let i = 0; i < displayCount; i++) {
            const t = transactions[i];
            const date = new Date(t.created_at).toLocaleString();
            const eventType = t.event_type || t.order_type;
            
            tbody.append(`
                <tr>
                    <td>${escapeHtml(date)}</td>
                    <td><code>${escapeHtml(t.transaction_id)}</code></td>
                    <td>${escapeHtml(t.display_name || 'N/A')}<br><small>${escapeHtml(t.user_email || '')}</small></td>
                    <td>${escapeHtml(eventType)}</td>
                    <td><strong>$${formatNumber(t.amount)}</strong></td>
                    <td><span class="status-badge status-${escapeHtml(t.status)}">${escapeHtml(t.status)}</span></td>
                </tr>
            `);
        }
        
        if (transactions.length > 50) {
            tbody.append(`
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">
                        <em>Showing first 50 transactions. Export CSV for full list.</em>
                    </td>
                </tr>
            `);
        }
    }
    
    /**
     * Format number with commas and decimals
     */
    function formatNumber(num, decimals = 2) {
        if (typeof num === 'undefined' || num === null) {
            return '0.00';
        }
        return parseFloat(num).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
})(jQuery);

