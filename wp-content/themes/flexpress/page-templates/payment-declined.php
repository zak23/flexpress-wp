<?php
/**
 * Template Name: Payment Declined
 * 
 * Payment declined page template for failed transactions.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

get_header();

$error_code = isset($_GET['error_code']) ? sanitize_text_field($_GET['error_code']) : '';
$error_message = isset($_GET['error_message']) ? sanitize_text_field($_GET['error_message']) : '';

// Get URL parameters for determining return URL
$episode_id = isset($_GET['episode_id']) ? intval($_GET['episode_id']) : 0;
$plan = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : '';
$ref = isset($_GET['ref']) ? sanitize_text_field($_GET['ref']) : '';
$return_url = isset($_GET['return_url']) ? esc_url_raw($_GET['return_url']) : '';

// Get current user
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Determine the appropriate return URL
$try_again_url = '/join'; // Default fallback
if ($episode_id > 0) {
    // PPV episode purchase - return to specific episode page
    $episode = get_post($episode_id);
    if ($episode && $episode->post_type === 'episode') {
        $try_again_url = get_permalink($episode_id);
    } else {
        $try_again_url = '/episodes'; // Fallback to episodes page
    }
} elseif ($return_url) {
    // Custom return URL provided
    $try_again_url = $return_url;
} elseif ($plan) {
    // Membership signup with plan - return to membership page
    $try_again_url = '/membership';
} else {
    // General membership signup - return to membership page
    $try_again_url = '/membership';
}
?>

<main id="primary" class="site-main payment-declined-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="declined-container">
                    <!-- Declined Header -->
                    <div class="declined-header text-center mb-5">
                        <div class="declined-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <h1 class="declined-title">Payment Declined</h1>
                        <p class="declined-subtitle">We're sorry, but your payment could not be processed at this time.</p>
                    </div>
                    
                    <!-- Error Details -->
                    <?php if ($error_message || $error_code): ?>
                    <div class="error-details mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Error Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($error_message): ?>
                                <div class="error-message">
                                    <strong>Message:</strong> <?php echo esc_html($error_message); ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($error_code): ?>
                                <div class="error-code">
                                    <strong>Error Code:</strong> <?php echo esc_html($error_code); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Common Reasons -->
                    <div class="common-reasons mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Common Reasons for Payment Declines
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="reasons-list">
                                    <div class="reason-item">
                                        <i class="fas fa-credit-card"></i>
                                        <div class="reason-content">
                                            <h6>Insufficient Funds</h6>
                                            <p>Your card may not have enough available balance.</p>
                                        </div>
                                    </div>
                                    <div class="reason-item">
                                        <i class="fas fa-lock"></i>
                                        <div class="reason-content">
                                            <h6>Card Security</h6>
                                            <p>Your bank may have blocked the transaction for security reasons.</p>
                                        </div>
                                    </div>
                                    <div class="reason-item">
                                        <i class="fas fa-globe"></i>
                                        <div class="reason-content">
                                            <h6>International Restrictions</h6>
                                            <p>Your card may not allow international or online transactions.</p>
                                        </div>
                                    </div>
                                    <div class="reason-item">
                                        <i class="fas fa-user-shield"></i>
                                        <div class="reason-content">
                                            <h6>3D Secure Authentication</h6>
                                            <p>Additional verification may be required by your bank.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- What to Do Next -->
                    <div class="next-steps mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    What to Do Next
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="steps-list">
                                    <div class="step-item">
                                        <div class="step-number">1</div>
                                        <div class="step-content">
                                            <h6>Check Your Card Details</h6>
                                            <p>Verify that your card number, expiry date, and CVV are correct.</p>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-number">2</div>
                                        <div class="step-content">
                                            <h6>Contact Your Bank</h6>
                                            <p>Call your bank to ensure the card is active and allows online transactions.</p>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-number">3</div>
                                        <div class="step-content">
                                            <h6>Try a Different Payment Method</h6>
                                            <p>Use a different card or payment method if available.</p>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-number">4</div>
                                        <div class="step-content">
                                            <h6>Contact Support</h6>
                                            <p>If the problem persists, our support team can help you.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Next Steps -->
                    <div class="next-steps mt-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-redo me-2"></i>
                                    Try Again
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="steps-list">
                                    <div class="step-item">
                                        <div class="step-number">1</div>
                                        <div class="step-content">
                                            <h6>Retry Payment</h6>
                                            <p>Double-check your card details and try the payment again.</p>
                                            <a href="<?php echo home_url($try_again_url); ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-redo me-1"></i>
                                                Try Payment Again
                                            </a>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-number">2</div>
                                        <div class="step-content">
                                            <h6>Contact Support</h6>
                                            <p>Get help resolving payment issues and explore alternative payment methods.</p>
                                            <a href="<?php echo home_url('/contact'); ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-envelope me-1"></i>
                                                Contact Support
                                            </a>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-number">3</div>
                                        <div class="step-content">
                                            <h6>Browse Free Content</h6>
                                            <p>Explore our free content while you resolve payment issues.</p>
                                            <a href="<?php echo home_url('/'); ?>" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-home me-1"></i>
                                                Go Home
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Support Information -->
                    <div class="support-info mt-4 text-center">
                        <p class="text-muted">
                            Need help or have questions about payment issues?
                        </p>
                        <a href="<?php echo home_url('/contact'); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-1"></i>
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>


<script>
// Auto-redirect after 60 seconds
setTimeout(function() {
    if (confirm('Would you like to try the payment again?')) {
        window.location.href = '<?php echo home_url($try_again_url); ?>';
    }
}, 60000);

// Track payment decline for analytics
if (typeof gtag !== 'undefined') {
    gtag('event', 'payment_declined', {
        'event_category': 'ecommerce',
        'event_label': '<?php echo esc_js($error_code ?: 'unknown'); ?>',
        'value': 0
    });
}
</script>

<?php get_footer(); ?>
