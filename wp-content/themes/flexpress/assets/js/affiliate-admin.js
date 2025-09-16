/**
 * FlexPress Affiliate Admin Dashboard
 */
jQuery(document).ready(function($) {
    'use strict';

    // Create button click handler
    $('#create-promo-code').on('click', function() {
        $('#promo-code-modal').fadeIn(300);
        $('#new-promo-code').focus();
    });

    // Close modal
    $('.modal-close').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        closeModal();
    });

    // Close modal on outside click
    $(document).on('click', '.affiliate-modal', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Helper function to close modal
    function closeModal() {
        $('.affiliate-modal').fadeOut(300, function() {
            $('#promo-code-form').trigger('reset');
            clearErrors();
        });
    }

    // Helper function to show form errors
    function showFormError(field, message) {
        const $field = $(field);
        const $error = $('<div class="form-error" style="color: #dc3232; font-size: 12px; margin-top: 5px;"></div>');
        $error.text(message);
        $field.closest('.form-field').append($error);
        $field.addClass('error');
    }

    // Helper function to clear form errors
    function clearErrors() {
        $('.form-error').remove();
        $('.error').removeClass('error');
    }

    // Helper function to validate promo code format
    function validatePromoCode(code) {
        // Only allow letters, numbers, and hyphens, 3-20 characters
        return /^[a-zA-Z0-9-]{3,20}$/.test(code);
    }

    // Helper function to show notices
    function showNotice(message, type = 'error') {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        const $dismiss = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
        
        $notice.append($dismiss);
        $('.wrap > h1').after($notice);
        
        $dismiss.on('click', function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Form submission
    $('#promo-code-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $targetPlans = $('#target-plans');
        const $promoCode = $('#new-promo-code');
        const $affiliateName = $('#affiliate-name');
        const $commissionRate = $('#commission-rate');
        
        // Clear previous errors
        clearErrors();
        
        // Validate promo code format
        if (!validatePromoCode($promoCode.val().trim())) {
            showFormError($promoCode, 'Promo code must be 3-20 characters and contain only letters, numbers, and hyphens.');
            return;
        }
        
        // Validate affiliate name
        if ($affiliateName.val().trim().length < 2) {
            showFormError($affiliateName, 'Affiliate name must be at least 2 characters.');
            return;
        }
        
        // Validate target plans
        if (!$targetPlans.val() || $targetPlans.val().length === 0) {
            showFormError($targetPlans, 'Please select at least one target plan.');
            return;
        }
        
        // Validate commission rate
        const commissionRate = parseFloat($commissionRate.val());
        if (isNaN(commissionRate) || commissionRate < 0 || commissionRate > 100) {
            showFormError($commissionRate, 'Commission rate must be between 0 and 100.');
            return;
        }
        
        // Disable form while submitting
        $form.find('input, select, button').prop('disabled', true);
        
        const formData = {
            action: 'create_affiliate_code',
            nonce: flexpressAffiliate.nonce,
            code: $promoCode.val().trim(),
            affiliate_name: $affiliateName.val().trim(),
            target_plans: $targetPlans.val(),
            commission_rate: commissionRate
        };
        
        // Show loading state
        $submitButton.html('<span class="spinner is-active"></span> Creating...');
        
        $.ajax({
            url: flexpressAffiliate.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message || flexpressAffiliate.i18n.success, 'success');
                    closeModal();
                    // Reload after a short delay to allow the notice to be seen
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showFormError($promoCode, response.data.message || flexpressAffiliate.i18n.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showNotice(flexpressAffiliate.i18n.error + (error ? ': ' + error : ''));
            },
            complete: function() {
                // Re-enable form
                $form.find('input, select, button').prop('disabled', false);
                $submitButton.html('Create Promo Code');
            }
        });
    });

    // Delete code
    $(document).on('click', '.delete-code', function() {
        const code = $(this).data('code');
        const $row = $(this).closest('tr');
        
        if (!confirm(flexpressAffiliate.i18n.confirmDelete)) {
            return;
        }
        
        const $button = $(this);
        $button.prop('disabled', true).html('<span class="spinner is-active"></span>');
        
        $.ajax({
            url: flexpressAffiliate.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_affiliate_code',
                nonce: flexpressAffiliate.nonce,
                code: code
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message || flexpressAffiliate.i18n.success, 'success');
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        // If no more rows, show empty message
                        if ($('#promo-codes-list tr').length === 0) {
                            $('#promo-codes-list').html('<tr><td colspan="7">' + flexpressAffiliate.i18n.noPromoCodesFound + '</td></tr>');
                        }
                    });
                } else {
                    showNotice(response.data.message || flexpressAffiliate.i18n.error, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showNotice(flexpressAffiliate.i18n.error + (error ? ': ' + error : ''));
            },
            complete: function() {
                $button.prop('disabled', false).html('Delete');
            }
        });
    });

    // View code details
    $(document).on('click', '.view-details', function() {
        const code = $(this).data('code');
        const $button = $(this);
        
        $button.prop('disabled', true).html('<span class="spinner is-active"></span>');
        
        $.ajax({
            url: flexpressAffiliate.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_affiliate_stats',
                nonce: flexpressAffiliate.nonce,
                code: code
            },
            success: function(response) {
                if (response.success) {
                    showStatsModal(response.data);
                } else {
                    showNotice(response.data.message || flexpressAffiliate.i18n.error, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showNotice(flexpressAffiliate.i18n.error + (error ? ': ' + error : ''));
            },
            complete: function() {
                $button.prop('disabled', false).html('Details');
            }
        });
    });

    // Helper function to show stats modal
    function showStatsModal(data) {
        const modalHtml = `
            <div id="code-details-modal" class="affiliate-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Code Details: ${data.code}</h2>
                        <span class="modal-close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <h3>Total Uses</h3>
                                <div class="stat-number">${data.total_uses}</div>
                            </div>
                            <div class="stat-card">
                                <h3>Revenue Generated</h3>
                                <div class="stat-number">$${data.revenue}</div>
                            </div>
                            <div class="stat-card">
                                <h3>Conversion Rate</h3>
                                <div class="stat-number">${data.conversion_rate}%</div>
                            </div>
                            <div class="stat-card">
                                <h3>Last 30 Days</h3>
                                <div class="stat-number">${data.recent_uses}</div>
                            </div>
                        </div>
                        <div class="usage-timeline">
                            <h3>Recent Usage</h3>
                            <canvas id="usage-timeline-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove any existing modals
        $('#code-details-modal').remove();
        
        // Add new modal
        const $modal = $(modalHtml).appendTo('body');
        $modal.fadeIn(300);
        
        // Initialize chart if we have timeline data
        if (data.timeline && data.timeline.length) {
            const ctx = document.getElementById('usage-timeline-chart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.timeline.map(item => item.date),
                    datasets: [{
                        label: 'Uses',
                        data: data.timeline.map(item => item.count),
                        borderColor: '#007cba',
                        backgroundColor: 'rgba(0, 124, 186, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    }
}); 