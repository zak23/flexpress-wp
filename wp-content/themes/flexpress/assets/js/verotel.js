/**
 * Verotel integration JavaScript
 */
(function($) {
    'use strict';

    /**
     * Handles the cancel subscription process
     */
    function handleCancelSubscription() {
        // Check if the cancel button exists
        if ($('#cancel-subscription').length === 0) {
            console.log('Cancel subscription button not found');
            return;
        }
        
        // Check if flexpress_verotel is available
        if (typeof flexpress_verotel === 'undefined') {
            console.error('flexpress_verotel object not found - script not properly localized');
            return;
        }
        
        console.log('Setting up cancel subscription handler...');
        
        $('#cancel-subscription').on('click', function(e) {
            e.preventDefault();
            
            // Confirm cancellation with the user
            if (!confirm('Are you sure you want to cancel your subscription? This action cannot be undone.')) {
                return;
            }
            
            var $button = $('#cancel-subscription');
            var originalText = $button.text();
            
            $.ajax({
                url: flexpress_verotel.ajax_url,
                type: 'POST',
                data: {
                    action: 'cancel_subscription',
                    nonce: flexpress_verotel.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text('Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        // Check if we need to redirect to Verotel
                        if (response.data.redirect_url) {
                            alert(response.data.message);
                            // Redirect to Verotel cancellation page
                            window.location.href = response.data.redirect_url;
                        } else {
                            // Local cancellation completed
                            var message = response.data.message;
                            if (response.data.warning) {
                                message += '\n\nNote: ' + response.data.warning;
                            }
                            alert(message);
                            // Reload the page to show updated subscription status
                            window.location.reload();
                        }
                    } else {
                        alert('Error: ' + response.data);
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while cancelling your subscription. Please try again or contact support.');
                    $button.prop('disabled', false).text(originalText);
                    console.error('Cancel subscription error:', error);
                }
            });
        });
    }

    /**
     * Handles the update payment method process
     */
    function handleUpdatePaymentMethod() {
        $('#update-payment-method').on('click', function(e) {
            e.preventDefault();
            
            // This will open a modal or redirect to Verotel payment update page
            // For now, just show a placeholder message
            alert(FlexPress.i18n.update_payment_method_pending);
        });
    }

    /**
     * Handles the membership renewal process
     */
    function handleRenewMembership() {
        // Check if the renew button exists
        if ($('#renew-membership').length === 0) {
            console.log('Renew membership button not found');
            return;
        }
        
        // Check if flexpress_verotel is available
        if (typeof flexpress_verotel === 'undefined') {
            console.error('flexpress_verotel object not found - script not properly localized');
            return;
        }
        
        console.log('Setting up renew membership handler...');
        
        $('#renew-membership').on('click', function(e) {
            e.preventDefault();
            
            var $button = $('#renew-membership');
            var originalText = $button.html();
            var planId = $button.data('plan-id');
            
            // Confirm renewal with the user
            if (!confirm('Are you sure you want to renew your membership? You will be redirected to complete the payment.')) {
                return;
            }
            
            $.ajax({
                url: flexpress_verotel.ajax_url,
                type: 'POST',
                data: {
                    action: 'renew_membership',
                    plan_id: planId,
                    nonce: flexpress_verotel.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message and redirect to Verotel
                        var renewalData = response.data;
                        alert(renewalData.message);
                        
                        // Redirect to Verotel payment page
                        if (renewalData.renewal_url) {
                            window.location.href = renewalData.renewal_url;
                        } else {
                            // Fallback to membership page if no URL
                            window.location.href = '/membership';
                        }
                    } else {
                        alert('Error: ' + response.data);
                        $button.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred while processing your renewal. Please try again or contact support.');
                    $button.prop('disabled', false).html(originalText);
                    console.error('Renew membership error:', error);
                }
            });
        });
    }

    /**
     * Initializes all Verotel related functionality
     */
    function init() {
        // Debug: Check if we're on the right page
        console.log('Verotel.js loaded, checking for billing tab...');
        console.log('Billing tab found:', $('.tab-pane#billing').length > 0);
        console.log('Cancel button found:', $('#cancel-subscription').length > 0);
        console.log('Renew button found:', $('#renew-membership').length > 0);
        
        // Only initialize if we find either the billing tab, cancel button, or renew button
        if ($('.tab-pane#billing').length === 0 && $('#cancel-subscription').length === 0 && $('#renew-membership').length === 0) {
            console.log('No billing tab, cancel button, or renew button found, skipping Verotel initialization');
            return;
        }
        
        console.log('Initializing Verotel handlers...');
        
        // Initialize handlers
        handleCancelSubscription();
        handleUpdatePaymentMethod();
        handleRenewMembership();
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery); 