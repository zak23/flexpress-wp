/**
 * FlexPress Plunk Admin JavaScript
 *
 * @package FlexPress
 */

jQuery(document).ready(function($) {
    // Test Plunk connection
    $('#test-plunk-connection').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $results = $('#plunk-test-results');
        
        $button.prop('disabled', true).text('Testing...');
        $results.html('<p>Testing Plunk connection...</p>');
        
        $.ajax({
            url: flexpressPlunkAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'test_plunk_connection',
                nonce: flexpressPlunkAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $results.html('<div class="notice notice-success inline"><p>✓ ' + response.data + '</p></div>');
                } else {
                    $results.html('<div class="notice notice-error inline"><p>✗ ' + response.data + '</p></div>');
                }
            },
            error: function() {
                $results.html('<div class="notice notice-error inline"><p>✗ Failed to test connection. Please check error logs.</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Test Plunk Connection');
            }
        });
    });

    // Sync users button
    $('#sync-users-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $results = $('#sync-users-results');
        var limit = $('#sync-limit').val() || 50;
        
        if (!confirm('This will sync existing WordPress users with Plunk. Continue?')) {
            return;
        }
        
        $button.prop('disabled', true).text('Syncing...');
        $results.html('<p>Syncing users with Plunk...</p>');
        
        $.ajax({
            url: flexpressPlunkAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'plunk_sync_users',
                limit: limit,
                nonce: flexpressPlunkAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $results.html('<div class="notice notice-success inline"><p>✓ ' + response.data.message + '</p></div>');
                    
                    // Show detailed results if available
                    if (response.data.results) {
                        var detailsHtml = '<div class="sync-details" style="margin-top: 10px;"><h4>Sync Details:</h4><ul>';
                        $.each(response.data.results, function(userId, result) {
                            var status = result.success ? '✓' : '✗';
                            var message = result.success ? 'Synced successfully' : result.error;
                            detailsHtml += '<li>User ' + userId + ': ' + status + ' ' + message + '</li>';
                        });
                        detailsHtml += '</ul></div>';
                        $results.append(detailsHtml);
                    }
                } else {
                    $results.html('<div class="notice notice-error inline"><p>✗ ' + response.data + '</p></div>');
                }
            },
            error: function() {
                $results.html('<div class="notice notice-error inline"><p>✗ Failed to sync users. Please check error logs.</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Sync Users');
            }
        });
    });

    // Auto-subscribe toggle effect
    $('#auto_subscribe_users').on('change', function() {
        var isChecked = $(this).is(':checked');
        var $modalSettings = $('#enable_newsletter_modal, #modal_delay').closest('tr');
        
        if (isChecked) {
            $modalSettings.show();
        } else {
            $modalSettings.hide();
        }
    });

    // Newsletter modal toggle effect
    $('#enable_newsletter_modal').on('change', function() {
        var isChecked = $(this).is(':checked');
        var $delaySetting = $('#modal_delay').closest('tr');
        
        if (isChecked) {
            $delaySetting.show();
        } else {
            $delaySetting.hide();
        }
    });

    // Initialize visibility on page load
    $('#auto_subscribe_users').trigger('change');
    $('#enable_newsletter_modal').trigger('change');
});
