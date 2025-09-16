<?php
/**
 * Template Name: Payment
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

<main id="primary" class="site-main payment-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="payment-container">
                    <div class="payment-header text-center mb-4">
                        <h1 class="payment-title">COMPLETE YOUR PAYMENT</h1>
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
.payment-page {
    background: #000;
    min-height: 100vh;
}

.payment-container {
    background: #1a1a1a;
    border-radius: 8px;
    padding: 2rem;
    border: 1px solid #333;
}

.payment-title {
    color: #ffffff;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.payment-subtitle {
    color: #888;
    font-size: 1rem;
    margin-bottom: 0;
}

.payment-form-container {
    background: #222;
    border-radius: 8px;
    padding: 2rem;
    margin: 1.5rem 0;
    border: 1px solid #333;
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
    background: #222;
    border: 1px solid #333;
    border-radius: 4px;
}

.payment-help .accordion-button {
    background: transparent;
    color: #ffffff;
    border: none;
    font-weight: 500;
}

.payment-help .accordion-button:not(.collapsed) {
    background: #333;
    color: #ffffff;
}

.payment-help .accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(255, 107, 107, 0.25);
}

.payment-help .accordion-body {
    background: #222;
    color: #888;
}

.alert {
    border-radius: 4px;
    border: none;
    padding: 1rem 1.5rem;
    margin-bottom: 1rem;
}

.alert-success {
    background: #1e3a1e;
    color: #28a745;
    border: 1px solid #28a745;
}

.alert-danger {
    background: #3a1e1e;
    color: #dc3545;
    border: 1px solid #dc3545;
}

.alert-warning {
    background: #3a3a1e;
    color: #ffc107;
    border: 1px solid #ffc107;
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
    
    .flowguard-form-fields {
        padding: 1rem;
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
        nonce: '<?php echo wp_create_nonce('flowguard_nonce'); ?>'
    };
    
    // Check if session ID is valid
    if (!sessionId || sessionId === '') {
        console.error('No session ID provided');
        document.getElementById('payment-error').textContent = 'Invalid payment session. Please try again.';
        document.getElementById('payment-error').style.display = 'block';
        return;
    }
    
    // Show loading message
    document.getElementById('payment-pending').textContent = 'Loading secure payment form...';
    document.getElementById('payment-pending').style.display = 'block';
    
    // Load Flowguard SDK and initialize payment form
    loadFlowguardSDK().then(() => {
        initializePaymentForm(sessionId);
    }).catch(error => {
        console.error('Failed to load Flowguard SDK:', error);
        document.getElementById('payment-error').textContent = 'Error loading payment form. Please try again.';
        document.getElementById('payment-error').style.display = 'block';
        document.getElementById('payment-pending').style.display = 'none';
    });
    
    // Load Flowguard SDK
    function loadFlowguardSDK() {
        return new Promise((resolve, reject) => {
            // Check if SDK is already loaded
            if (window.Flowguard) {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = 'https://flowguard.yoursafe.com/js/flowguard.js';
            script.onload = () => {
                console.log('Flowguard SDK loaded successfully');
                resolve();
            };
            script.onerror = (error) => {
                console.error('Failed to load Flowguard SDK:', error);
                reject(error);
            };
            document.head.appendChild(script);
        });
    }
    
    // Initialize payment form
    function initializePaymentForm(sessionId) {
        try {
            const container = document.getElementById('flowguard-payment-form');
            if (!container) {
                throw new Error('Payment container not found');
            }
            
            // Debug: Check what Flowguard object contains
            console.log('Flowguard object:', window.Flowguard);
            console.log('Flowguard type:', typeof window.Flowguard);
            
            // Flowguard is the constructor itself, not an object with methods
            if (typeof window.Flowguard !== 'function') {
                throw new Error('Flowguard is not a constructor function');
            }
            
            // Create target elements with loading indicators
            container.innerHTML = `
                <div class="flowguard-form-fields">
                    <div class="field-group">
                        <label>Card Number</label>
                        <div id="card-number-element" class="flowguard-field-container">
                            <div class="field-loading">
                                <div class="loading-spinner"></div>
                                <span>Loading secure field...</span>
                            </div>
                        </div>
                    </div>
                    <div class="field-group">
                        <label>Expiry Date</label>
                        <div id="exp-date-element" class="flowguard-field-container">
                            <div class="field-loading">
                                <div class="loading-spinner"></div>
                                <span>Loading secure field...</span>
                            </div>
                        </div>
                    </div>
                    <div class="field-group">
                        <label>Cardholder Name</label>
                        <div id="cardholder-element" class="flowguard-field-container">
                            <div class="field-loading">
                                <div class="loading-spinner"></div>
                                <span>Loading secure field...</span>
                            </div>
                        </div>
                    </div>
                    <div class="field-group">
                        <label>CVV</label>
                        <div id="cvv-element" class="flowguard-field-container">
                            <div class="field-loading">
                                <div class="loading-spinner"></div>
                                <span>Loading secure field...</span>
                            </div>
                        </div>
                    </div>
                    <div class="price-display-group">
                        <div id="price-element" class="price-display-container">
                            <div class="price-loading">
                                <div class="loading-spinner"></div>
                                <span>Loading price information...</span>
                            </div>
                        </div>
                    </div>
                    <button id="submit-payment" class="btn btn-primary btn-lg w-100 mt-3" disabled>
                        <i class="fas fa-spinner fa-spin me-2"></i>
                        Loading Payment Form...
                    </button>
                </div>
                <style>
                    .flowguard-form-fields {
                        max-width: 500px;
                        margin: 0 auto;
                        padding: 2rem;
                    }
                    .field-group {
                        margin-bottom: 1rem;
                    }
                    .field-group label {
                        color: #ffffff;
                        font-weight: 500;
                        font-size: 0.9rem;
                        display: block;
                        margin-bottom: 0.5rem;
                    }
                    .flowguard-field-container {
                        position: relative;
                        min-height: 40px;
                        border: 1px solid #444;
                        border-radius: 4px;
                        background: #333;
                        overflow: hidden;
                    }
                    
                    .field-loading {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 0.5rem;
                        height: 40px;
                        color: #888;
                        font-size: 0.8rem;
                        transition: opacity 0.3s ease;
                    }
                    
                    .loading-spinner {
                        width: 16px;
                        height: 16px;
                        border: 2px solid rgba(255, 107, 107, 0.3);
                        border-top: 2px solid #ff6b6b;
                        border-radius: 50%;
                        animation: spin 1s linear infinite;
                    }
                    
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                    
                    .flowguard-field-container.loaded .field-loading {
                        display: none;
                    }
                    
                    .flowguard-field-container iframe {
                        width: 100%;
                        height: 40px;
                        border: none;
                        border-radius: 8px;
                    }
                    
                    /* Special styling for price field */
                    #price-element iframe {
                        height: 60px !important;
                        width: 100% !important;
                        min-width: 200px;
                        border: none !important;
                        border-radius: 8px !important;
                    }
                    
                    /* Ensure price field container maintains proper dimensions */
                    #price-element.flowguard-field-container {
                        min-height: 60px;
                        height: 60px;
                    }
                    
                    /* Override Flowguard SDK styling for price element */
                    #price-element.loaded {
                        background: transparent !important;
                        border: none !important;
                        padding: 0.5rem 0 !important;
                        min-height: auto !important;
                        display: block !important;
                        text-align: center !important;
                    }
                    
                    /* Override Flowguard SDK price styling */
                    .title.price {
                        color: #ffffff !important;
                        font-size: 1.1rem !important;
                        font-weight: 600 !important;
                        text-align: center !important;
                        padding: 0 !important;
                        background: transparent !important;
                        border: none !important;
                        margin: 0.5rem 0 !important;
                        white-space: normal !important;
                    }
                    
                    .title.price.svelte-1cjut88 {
                        color: #ffffff !important;
                        font-size: 1.1rem !important;
                        font-weight: 600 !important;
                        text-align: center !important;
                        padding: 0 !important;
                        background: transparent !important;
                        border: none !important;
                        margin: 0.5rem 0 !important;
                        white-space: normal !important;
                    }
                    
                    /* Fix HTML entities in price display */
                    .title.price * {
                        color: #ffffff !important;
                    }
                    
                    /* Additional styling for any price-related elements */
                    [class*="price"] {
                        color: #ffffff !important;
                        background: transparent !important;
                        border: none !important;
                        padding: 0 !important;
                        margin: 0.5rem 0 !important;
                        text-align: center !important;
                        font-weight: 600 !important;
                        font-size: 1.1rem !important;
                    }
                    
                    /* Price display container styling */
                    .price-display-group {
                        margin: 1.5rem 0;
                    }
                    
                    .price-display-label {
                        color: #ffffff;
                        font-weight: 600;
                        font-size: 1rem;
                        margin-bottom: 0.5rem;
                        text-transform: uppercase;
                        letter-spacing: 1px;
                    }
                    
                    .price-display-container {
                        background: transparent;
                        border: none;
                        padding: 0.5rem 0;
                        text-align: center;
                        min-height: auto;
                        display: block;
                    }
                    
                    .price-loading {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 0.5rem;
                        color: #888;
                        font-size: 0.9rem;
                    }
                    
                    .price-display-container.loaded .price-loading {
                        display: none;
                    }
                    
                    @keyframes pulse {
                        0% { transform: scale(1); }
                        50% { transform: scale(1.02); }
                        100% { transform: scale(1); }
                    }
                    
                    .btn-primary {
                        background: #ff6b6b;
                        border: none;
                        border-radius: 4px;
                        font-weight: 600;
                        padding: 1rem 2rem;
                        font-size: 1.1rem;
                        transition: all 0.3s ease;
                        text-transform: uppercase;
                        letter-spacing: 1px;
                    }
                    
                    .btn-primary:hover:not(:disabled) {
                        background: #ff5252;
                        transform: translateY(-1px);
                    }
                    
                    .btn-primary:disabled {
                        opacity: 0.7;
                        cursor: not-allowed;
                    }
                </style>
            `;
            
            // Initialize Flowguard exactly as per documentation
            const flowguard = new Flowguard({
                sessionId: sessionId,
                cardNumber: {
                    target: '#card-number-element'
                },
                cvv: {
                    target: '#cvv-element'
                },
                cardholder: {
                    target: '#cardholder-element'
                },
                expDate: {
                    target: '#exp-date-element'
                },
                price: {
                    target: '#price-element'
                }
            });
            
            console.log('Flowguard initialized with field elements');
            
            // Wait for elements to be ready before enabling submit
            const submitButton = document.getElementById('submit-payment');
            if (submitButton) {
                // Initially disable the button
                submitButton.disabled = true;
                submitButton.textContent = 'Loading payment form...';
                
                // Track which elements have loaded
                const loadedElements = new Set();
                const allElements = ['cardNumber', 'expDate', 'cardholder', 'cvv', 'price'];
                
                // Check if elements are ready periodically
                const checkElementsReady = setInterval(() => {
                    try {
                        // Try to get mounted elements to see if they're ready
                        const notMountedElements = flowguard.getNotMountedElements ? flowguard.getNotMountedElements() : [];
                        
                        // Check which elements are now loaded
                        allElements.forEach(elementName => {
                            if (!notMountedElements.includes(elementName) && !loadedElements.has(elementName)) {
                                // Element just loaded, hide its loading spinner
                                loadedElements.add(elementName);
                                const elementId = elementName === 'cardNumber' ? 'card-number-element' :
                                                elementName === 'expDate' ? 'exp-date-element' :
                                                elementName === 'cardholder' ? 'cardholder-element' :
                                                elementName === 'cvv' ? 'cvv-element' : 'price-element';
                                
                                const fieldContainer = document.getElementById(elementId);
                                if (fieldContainer) {
                                    if (elementName === 'price') {
                                        // Special handling for price element - it's a display container, not a form field
                                        fieldContainer.classList.add('loaded');
                                        // Hide the loading message
                                        const loadingElement = fieldContainer.querySelector('.price-loading');
                                        if (loadingElement) {
                                            loadingElement.style.display = 'none';
                                        }
                                    } else {
                                        fieldContainer.classList.add('loaded');
                                    }
                                    console.log('Field loaded:', elementName);
                                }
                            }
                        });
                        
                        if (notMountedElements.length === 0) {
                            // All elements are mounted
                            clearInterval(checkElementsReady);
                            submitButton.disabled = false;
                            submitButton.innerHTML = '<i class="fas fa-credit-card me-2"></i>Complete Payment';
                            console.log('All Flowguard elements are ready');
                            
                            // Add a subtle success animation
                            submitButton.style.animation = 'pulse 0.5s ease-in-out';
                            setTimeout(() => {
                                submitButton.style.animation = '';
                            }, 500);
                        } else {
                            console.log('Still loading elements:', notMountedElements);
                        }
                    } catch (error) {
                        console.log('Error checking element readiness:', error);
                        // Fallback: enable after 5 seconds
                        setTimeout(() => {
                            clearInterval(checkElementsReady);
                            submitButton.disabled = false;
                            submitButton.innerHTML = '<i class="fas fa-credit-card me-2"></i>Complete Payment';
                            console.log('Enabled submit button after timeout');
                            
                            // Hide all loading spinners
                            document.querySelectorAll('.flowguard-field-container').forEach(container => {
                                container.classList.add('loaded');
                            });
                        }, 5000);
                    }
                }, 200); // Check more frequently for smoother UX
                
                submitButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Submit button clicked');
                    
                    if (this.disabled) {
                        console.log('Submit button is disabled, payment form not ready');
                        return;
                    }
                    
                    if (typeof flowguard.submit === 'function') {
                        console.log('Calling flowguard.submit()');
                        this.disabled = true;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                        
                        try {
                            flowguard.submit();
                        } catch (error) {
                            console.error('Error submitting payment:', error);
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-credit-card me-2"></i>Complete Payment';
                        }
                    } else {
                        console.error('flowguard.submit is not a function');
                    }
                });
            }
            
            // Debug: Check what methods are available on the payment form
            console.log('Payment form methods:', Object.getOwnPropertyNames(flowguard));
            console.log('Payment form prototype methods:', Object.getOwnPropertyNames(Object.getPrototypeOf(flowguard)));
            
            setupEventHandlers(flowguard);
            
            // Hide loading message
            document.getElementById('payment-pending').style.display = 'none';
            console.log('Flowguard payment form initialized successfully');
            
        } catch (error) {
            console.error('Error initializing payment form:', error);
            document.getElementById('payment-error').textContent = 'Error initializing payment form. Please try again.';
            document.getElementById('payment-error').style.display = 'block';
            document.getElementById('payment-pending').style.display = 'none';
        }
    }
    
    // Setup event handlers for payment form
    function setupEventHandlers(paymentForm) {
        // Check if the payment form has event handling methods
        if (typeof paymentForm.on === 'function') {
            // Setup event handlers using .on() method
            paymentForm.on('payment.success', (event) => {
                console.log('Payment successful:', event);
                document.getElementById('payment-pending').style.display = 'none';
                document.getElementById('payment-success').textContent = 'Payment successful! Redirecting...';
                document.getElementById('payment-success').style.display = 'block';
                
                // Redirect to success page
                setTimeout(() => {
                    const successUrl = new URL(window.location.origin + '/payment-success');
                    if (event.transactionId) {
                        successUrl.searchParams.set('transaction_id', event.transactionId);
                    }
                    if (event.saleId) {
                        successUrl.searchParams.set('sale_id', event.saleId);
                    }
                    window.location.href = successUrl.toString();
                }, 2000);
            });
            
            paymentForm.on('payment.error', (event) => {
                console.error('Payment error:', event);
                document.getElementById('payment-pending').style.display = 'none';
                document.getElementById('payment-error').textContent = event.message || 'Payment failed. Please try again.';
                document.getElementById('payment-error').style.display = 'block';
            });
            
            paymentForm.on('payment.pending', (event) => {
                console.log('Payment pending:', event);
                document.getElementById('payment-pending').textContent = event.message || 'Payment is being processed...';
            });
        } else if (typeof paymentForm.addEventListener === 'function') {
            // Try addEventListener if available
            paymentForm.addEventListener('payment.success', (event) => {
                console.log('Payment successful:', event);
                document.getElementById('payment-pending').style.display = 'none';
                document.getElementById('payment-success').textContent = 'Payment successful! Redirecting...';
                document.getElementById('payment-success').style.display = 'block';
                
                setTimeout(() => {
                    const successUrl = new URL(window.location.origin + '/payment-success');
                    if (event.transactionId) {
                        successUrl.searchParams.set('transaction_id', event.transactionId);
                    }
                    if (event.saleId) {
                        successUrl.searchParams.set('sale_id', event.saleId);
                    }
                    window.location.href = successUrl.toString();
                }, 2000);
            });
            
            paymentForm.addEventListener('payment.error', (event) => {
                console.error('Payment error:', event);
                document.getElementById('payment-pending').style.display = 'none';
                document.getElementById('payment-error').textContent = event.message || 'Payment failed. Please try again.';
                document.getElementById('payment-error').style.display = 'block';
            });
        } else {
            console.log('No event handling methods found on payment form');
            console.log('Payment form will handle events internally');
        }
    }
    
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
