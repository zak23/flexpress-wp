/**
 * FlexPress Turnstile Admin JavaScript
 *
 * @package FlexPress
 */

jQuery(document).ready(function($) {
    // Test Turnstile connection
    $('#test-turnstile-connection').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $results = $('#turnstile-test-results');
        
        $button.prop('disabled', true).text('Testing...');
        $results.html('<p>Testing Turnstile connection...</p>');
        
        $.ajax({
            url: flexpressTurnstileAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'test_turnstile_connection',
                nonce: flexpressTurnstileAdmin.nonce
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
                $button.prop('disabled', false).text('Test Turnstile Connection');
            }
        });
    });
});
