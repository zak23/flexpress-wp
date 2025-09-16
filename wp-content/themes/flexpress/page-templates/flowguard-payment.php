<?php
/**
 * Template Name: Flowguard Payment
 * 
 * Payment page template for Flowguard embedded payment forms.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

get_header();

$session_id = isset($_GET['session_id']) ? sanitize_text_field($_GET['session_id']) : '';

if (empty($session_id)) {
    wp_redirect(home_url('/join'));
    exit;
}

// Get Flowguard settings
$flowguard_settings = get_option('flexpress_flowguard_settings', []);
?>

<main id="primary" class="site-main flowguard-payment-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="payment-container">
                    <div class="payment-header text-center mb-4">
                        <h1 class="payment-title">Complete Your Payment</h1>
                        <p class="payment-subtitle">Secure payment processing powered by Flowguard</p>
                    </div>
                    
                    <!-- Payment Status Messages -->
                    <div id="payment-success" class="alert alert-success" style="display: none;"></div>
                    <div id="payment-error" class="alert alert-danger" style="display: none;"></div>
                    <div id="payment-pending" class="alert alert-warning" style="display: none;"></div>
                    
                    <!-- Payment Form Container -->
                    <div id="flowguard-payment-form" class="payment-form-container">
                        <div class="payment-loading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading payment form...</span>
                            </div>
                            <p class="mt-3">Loading secure payment form...</p>
                        </div>
                    </div>
                    
                    <!-- Payment Security Info -->
                    <div class="payment-security-info text-center mt-4">
                        <div class="security-badges">
                            <span class="badge bg-success me-2">
                                <i class="fas fa-shield-alt me-1"></i>
                                256-bit SSL Encryption
                            </span>
                            <span class="badge bg-info me-2">
                                <i class="fas fa-lock me-1"></i>
                                PCI DSS Compliant
                            </span>
                            <span class="badge bg-warning">
                                <i class="fas fa-credit-card me-1"></i>
                                3D Secure Enabled
                            </span>
                        </div>
                        <small class="text-muted d-block mt-2">
                            Your payment information is processed securely and never stored on our servers.
                        </small>
                    </div>
                    
                    <!-- Payment Help -->
                    <div class="payment-help mt-4">
                        <div class="accordion" id="paymentHelpAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="helpHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpCollapse" aria-expanded="false" aria-controls="helpCollapse">
                                        <i class="fas fa-question-circle me-2"></i>
                                        Need Help?
                                    </button>
                                </h2>
                                <div id="helpCollapse" class="accordion-collapse collapse" aria-labelledby="helpHeading" data-bs-parent="#paymentHelpAccordion">
                                    <div class="accordion-body">
                                        <h6>Payment Methods Accepted:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-credit-card me-2"></i>Visa, Mastercard, American Express</li>
                                            <li><i class="fas fa-university me-2"></i>Bank transfers</li>
                                            <li><i class="fas fa-mobile-alt me-2"></i>Mobile payments</li>
                                        </ul>
                                        
                                        <h6 class="mt-3">Security Features:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-shield-alt me-2"></i>256-bit SSL encryption</li>
                                            <li><i class="fas fa-lock me-2"></i>PCI DSS compliance</li>
                                            <li><i class="fas fa-user-shield me-2"></i>3D Secure authentication</li>
                                            <li><i class="fas fa-eye-slash me-2"></i>No card data storage</li>
                                        </ul>
                                        
                                        <div class="mt-3">
                                            <a href="<?php echo home_url('/contact'); ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-envelope me-1"></i>
                                                Contact Support
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.flowguard-payment-page {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.payment-container {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    padding: 2rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.payment-title {
    color: #ffffff;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.payment-subtitle {
    color: #b0b0b0;
    font-size: 1.1rem;
    margin-bottom: 0;
}

.payment-form-container {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
    padding: 2rem;
    margin: 1.5rem 0;
    border: 1px solid rgba(255, 255, 255, 0.08);
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.payment-loading {
    text-align: center;
    color: #b0b0b0;
}

.payment-loading .spinner-border {
    width: 3rem;
    height: 3rem;
    border-width: 0.3em;
}

.security-badges .badge {
    font-size: 0.8rem;
    padding: 0.5rem 0.75rem;
}

.payment-help .accordion-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
}

.payment-help .accordion-button {
    background: transparent;
    color: #ffffff;
    border: none;
    font-weight: 500;
}

.payment-help .accordion-button:not(.collapsed) {
    background: rgba(255, 255, 255, 0.05);
    color: #ffffff;
}

.payment-help .accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(255, 107, 107, 0.25);
}

.payment-help .accordion-body {
    background: rgba(255, 255, 255, 0.02);
    color: #b0b0b0;
}

.alert {
    border-radius: 8px;
    border: none;
    padding: 1rem 1.5rem;
    margin-bottom: 1rem;
}

.alert-success {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
    border-left: 4px solid #dc3545;
}

.alert-warning {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
    border-left: 4px solid #ffc107;
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

@media (max-width: 768px) {
    .payment-container {
        padding: 1.5rem;
        margin: 1rem;
    }
    
    .payment-title {
        font-size: 1.5rem;
    }
    
    .payment-form-container {
        padding: 1.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sessionId = '<?php echo esc_js($session_id); ?>';
    
    // Initialize Flowguard configuration
    window.flowguardConfig = {
        shopId: '<?php echo esc_js($flowguard_settings['shop_id'] ?? ''); ?>',
        environment: '<?php echo esc_js($flowguard_settings['environment'] ?? 'sandbox'); ?>',
        nonce: '<?php echo wp_create_nonce('flowguard_payment'); ?>'
    };
    
    // Initialize Flowguard payment form
    initFlowguardPayment(sessionId, {
        theme: 'dark',
        locale: 'en_US',
        customStyles: {
            primaryColor: '#ff6b6b',
            backgroundColor: '#1a1a1a',
            textColor: '#ffffff',
            borderColor: '#333333',
            borderRadius: '8px',
            fontFamily: 'Inter, sans-serif'
        }
    }).catch(error => {
        console.error('Error initializing payment form:', error);
        document.getElementById('payment-error').textContent = 'Error loading payment form. Please try again.';
        document.getElementById('payment-error').style.display = 'block';
    });
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // ESC to go back
        if (e.key === 'Escape') {
            window.history.back();
        }
    });
    
    // Add page visibility handling
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            console.log('Payment page hidden - pausing form updates');
        } else {
            console.log('Payment page visible - resuming form updates');
        }
    });
});
</script>

<?php get_footer(); ?>
