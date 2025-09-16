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

// Get current user
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
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
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons text-center">
                        <a href="<?php echo home_url('/join'); ?>" class="btn btn-primary me-3">
                            <i class="fas fa-redo me-1"></i>
                            Try Again
                        </a>
                        <a href="<?php echo home_url('/contact'); ?>" class="btn btn-outline-primary me-3">
                            <i class="fas fa-envelope me-1"></i>
                            Contact Support
                        </a>
                        <a href="<?php echo home_url('/'); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-1"></i>
                            Go Home
                        </a>
                    </div>
                    
                    <!-- Support Information -->
                    <div class="support-info mt-4 text-center">
                        <div class="support-card">
                            <h6>
                                <i class="fas fa-headset me-2"></i>
                                Need Immediate Help?
                            </h6>
                            <p class="mb-3">
                                Our support team is available 24/7 to help you resolve payment issues.
                            </p>
                            <div class="support-methods">
                                <a href="mailto:support@example.com" class="support-method">
                                    <i class="fas fa-envelope"></i>
                                    Email Support
                                </a>
                                <a href="tel:+1234567890" class="support-method">
                                    <i class="fas fa-phone"></i>
                                    Call Support
                                </a>
                                <a href="<?php echo home_url('/contact'); ?>" class="support-method">
                                    <i class="fas fa-comments"></i>
                                    Live Chat
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.payment-declined-page {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.declined-container {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 2rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.declined-icon {
    font-size: 4rem;
    color: #dc3545;
    margin-bottom: 1rem;
}

.declined-title {
    color: #ffffff;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.declined-subtitle {
    color: #b0b0b0;
    font-size: 1.2rem;
    margin-bottom: 0;
}

.card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    margin-bottom: 1rem;
}

.card-header {
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    color: #ffffff;
    font-weight: 600;
}

.card-body {
    color: #b0b0b0;
}

.error-message, .error-code {
    margin-bottom: 0.75rem;
    padding: 0.75rem;
    background: rgba(220, 53, 69, 0.1);
    border-left: 4px solid #dc3545;
    border-radius: 4px;
}

.reasons-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.reason-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.02);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.reason-item i {
    color: #ff6b6b;
    font-size: 1.2rem;
    margin-top: 0.25rem;
    flex-shrink: 0;
}

.reason-content h6 {
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.reason-content p {
    margin-bottom: 0;
    color: #b0b0b0;
}

.steps-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.step-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.step-number {
    background: #ff6b6b;
    color: #ffffff;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
}

.step-content h6 {
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.step-content p {
    margin-bottom: 0;
    color: #b0b0b0;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
}

.btn-primary {
    background: #ff6b6b;
    border-color: #ff6b6b;
}

.btn-primary:hover {
    background: #ff5252;
    border-color: #ff5252;
}

.btn-outline-primary {
    border-color: #ff6b6b;
    color: #ff6b6b;
}

.btn-outline-primary:hover {
    background-color: #ff6b6b;
    border-color: #ff6b6b;
    color: #ffffff;
}

.btn-outline-secondary {
    border-color: #6c757d;
    color: #6c757d;
}

.btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    color: #ffffff;
}

.support-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 2rem;
}

.support-card h6 {
    color: #ffffff;
    margin-bottom: 1rem;
}

.support-methods {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.support-method {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    color: #b0b0b0;
    text-decoration: none;
    transition: all 0.3s ease;
}

.support-method:hover {
    background: rgba(255, 107, 107, 0.1);
    color: #ff6b6b;
    text-decoration: none;
}

@media (max-width: 768px) {
    .declined-container {
        padding: 1.5rem;
        margin: 1rem;
    }
    
    .declined-title {
        font-size: 2rem;
    }
    
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .action-buttons .btn {
        width: 100%;
    }
    
    .support-methods {
        flex-direction: column;
    }
    
    .support-method {
        justify-content: center;
    }
}
</style>

<script>
// Auto-redirect to join page after 60 seconds
setTimeout(function() {
    if (confirm('Would you like to try the payment again?')) {
        window.location.href = '<?php echo home_url('/join'); ?>';
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
