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
$plan_id = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : '';
$promo_code = isset($_GET['promo']) ? sanitize_text_field($_GET['promo']) : '';

// If no session_id but we have a plan, handle based on login status
if (empty($session_id) && !empty($plan_id)) {
    if (is_user_logged_in()) {
        // Logged in user with plan - create Flowguard session
        $user_id = get_current_user_id();
        
        // Get the pricing plan details (include promo code for pricing)
        $pricing_plans = flexpress_get_pricing_plans(true, $promo_code);
        if (!isset($pricing_plans[$plan_id])) {
            wp_redirect(home_url('/membership?error=invalid_plan'));
            exit;
        }
        
        $plan = $pricing_plans[$plan_id];
        
        // Create Flowguard payment session using API
        if (function_exists('flexpress_get_flowguard_api')) {
            $api = flexpress_get_flowguard_api();
            
            if (!$api) {
                wp_redirect(home_url('/membership?error=flowguard_not_available'));
                exit;
            }
            
        // Get payment data
        $payment_data_result = flexpress_create_flowguard_payment_data($plan_id, $plan, $user_id);
        
        if (!$payment_data_result['success']) {
            wp_redirect(home_url('/membership?error=session_creation_failed'));
            exit;
        }
        
        $payment_data = $payment_data_result['data'];
        
        // Apply promo code discount if provided
        if (!empty($promo_code)) {
            // Validate and apply promo code
            if (class_exists('FlexPress_Promo_Codes')) {
                $promo_codes = new FlexPress_Promo_Codes();
                $original_amount = $plan['price'];
                $validation = $promo_codes->validate_promo_code_logic($promo_code, $user_id, $plan_id, $original_amount);
                
                if ($validation['valid']) {
                    // Use the calculated discount and final amount from validation
                    $final_amount = $validation['final_amount'];
                    
                    // Update payment data with discounted amount
                    $payment_data['priceAmount'] = number_format($final_amount, 2, '.', '');
                    
                    // Apply discount to trial amount if plan has trial enabled
                    if (!empty($plan['trial_enabled']) && isset($payment_data['trialAmount'])) {
                        $original_trial_amount = $plan['trial_price'];
                        
                        // Validate promo code for trial amount
                        $trial_validation = $promo_codes->validate_promo_code_logic($promo_code, $user_id, $plan_id, $original_trial_amount);
                        
                        if ($trial_validation['valid']) {
                            $discounted_trial_amount = $trial_validation['final_amount'];
                            $payment_data['trialAmount'] = number_format($discounted_trial_amount, 2, '.', '');
                        }
                    }
                }
            }
        }
            
            // Create session using API
            if ($plan['plan_type'] === 'one_time' || $plan['plan_type'] === 'lifetime') {
                $result = $api->start_purchase($payment_data);
            } else {
                $result = $api->start_subscription($payment_data);
            }
            
            if ($result['success'] && !empty($result['session_id'])) {
                // Redirect to payment page with session ID
                wp_redirect(home_url('/payment?session_id=' . urlencode($result['session_id'])));
                exit;
            } else {
                // Failed to create session
                wp_redirect(home_url('/membership?error=session_creation_failed'));
                exit;
            }
        } else {
            // Fallback - redirect with error
            wp_redirect(home_url('/membership?error=flowguard_not_available'));
            exit;
        }
    } else {
        // Not logged in - redirect to membership page to register/login
        wp_redirect(home_url('/membership?plan=' . urlencode($plan_id)));
        exit;
    }
}

// If no session_id and no plan, redirect to membership page
if (empty($session_id)) {
    wp_redirect(home_url('/membership'));
    exit;
}

// Get Flowguard settings
$flowguard_settings = get_option('flexpress_flowguard_settings', []);
?>

<div class="payment-page">
    <div class="container py-5">
        <div class="row justify-content-center mb-5">
            <div class="col-md-10 text-center">
                <h1 class="display-4 mb-4"><?php esc_html_e('Complete Your Payment', 'flexpress'); ?></h1>
                <p class="lead mb-4"><?php esc_html_e('Secure payment processing powered by Flowguard', 'flexpress'); ?></p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="payment-container">
                    
                    <!-- Payment Status Messages -->
                    <div id="payment-success" class="alert alert-success" style="display: none;" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <span id="success-message"></span>
                    </div>
                    <div id="payment-error" class="alert alert-danger" style="display: none;" role="alert">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-exclamation-circle me-2"></i><span id="error-message"></span></span>
                            <button id="refresh-session" class="btn btn-outline-light btn-sm" style="display: none;">
                                <i class="fas fa-refresh me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                    <div id="payment-pending" class="alert alert-warning" style="display: none;" role="alert">
                        <i class="fas fa-spinner fa-spin me-2"></i>
                        <span id="pending-message"></span>
                    </div>
                    
                    
                    <!-- Payment Form Container -->
                    <div class="payment-form-section">
                        <h3 class="text-center mb-4"><?php esc_html_e('Payment Information', 'flexpress'); ?></h3>
                        
                        <div id="flowguard-payment-form" class="payment-form-container">
                            <div class="payment-loading">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden"><?php esc_html_e('Loading payment form...', 'flexpress'); ?></span>
                                </div>
                                <p class="mt-3"><?php esc_html_e('Loading secure payment form...', 'flexpress'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Security Info -->
                    <div class="payment-security-info text-center mt-4">
                        <div class="security-badges">
                            <span class="badge bg-success">
                                <i class="fas fa-shield-alt me-1"></i>
                                <?php esc_html_e('256-bit SSL Encryption', 'flexpress'); ?>
                            </span>
                            <span class="badge bg-info">
                                <i class="fas fa-lock me-1"></i>
                                <?php esc_html_e('PCI DSS Compliant', 'flexpress'); ?>
                            </span>
                            <span class="badge bg-warning">
                                <i class="fas fa-credit-card me-1"></i>
                                <?php esc_html_e('3D Secure Enabled', 'flexpress'); ?>
                            </span>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <?php esc_html_e('Your payment information is processed securely and never stored on our servers.', 'flexpress'); ?>
                        </small>
                    </div>
                    
                    <!-- Payment Help -->
                    <div class="payment-help mt-4">
                        <div class="accordion" id="paymentHelpAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="helpHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpCollapse" aria-expanded="false" aria-controls="helpCollapse">
                                        <i class="fas fa-question-circle me-2"></i>
                                        <?php esc_html_e('Need Help?', 'flexpress'); ?>
                                    </button>
                                </h2>
                                <div id="helpCollapse" class="accordion-collapse collapse" aria-labelledby="helpHeading" data-bs-parent="#paymentHelpAccordion">
                                    <div class="accordion-body">
                                        <h6 class="mt-3"><?php esc_html_e('Security Features:', 'flexpress'); ?></h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-shield-alt me-2"></i><?php esc_html_e('256-bit SSL encryption', 'flexpress'); ?></li>
                                            <li><i class="fas fa-lock me-2"></i><?php esc_html_e('PCI DSS compliance', 'flexpress'); ?></li>
                                            <li><i class="fas fa-user-shield me-2"></i><?php esc_html_e('3D Secure authentication', 'flexpress'); ?></li>
                                            <li><i class="fas fa-eye-slash me-2"></i><?php esc_html_e('No card data storage', 'flexpress'); ?></li>
                                        </ul>
                                        
                                        <div class="mt-3">
                                            <a href="<?php echo home_url('/contact'); ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?php esc_html_e('Contact Support', 'flexpress'); ?>
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
</div>

<!-- Load validation styles -->
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/flowguard-validation.css">

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
        document.getElementById('error-message').textContent = '<?php esc_html_e('Invalid payment session. Please try again.', 'flexpress'); ?>';
        document.getElementById('payment-error').style.display = 'block';
        return;
    }
    
    // Validate session ID format (basic check)
    if (!sessionId.match(/^[a-f0-9-]{36}$/i)) {
        console.error('Invalid session ID format:', sessionId);
        document.getElementById('error-message').textContent = '<?php esc_html_e('Invalid payment session format. Please try again.', 'flexpress'); ?>';
        document.getElementById('payment-error').style.display = 'block';
        return;
    }
    
    // Show loading message
    document.getElementById('pending-message').textContent = '<?php esc_html_e('Loading secure payment form...', 'flexpress'); ?>';
    document.getElementById('payment-pending').style.display = 'block';
    
    // Load Flowguard SDK and validation system
    Promise.all([
        loadFlowguardSDK(),
        loadValidationSystem()
    ]).then(() => {
        initializePaymentForm(sessionId);
    }).catch(error => {
        console.error('Failed to load payment system:', error);
        document.getElementById('error-message').textContent = '<?php esc_html_e('Error loading payment form. Please try again.', 'flexpress'); ?>';
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
    
    // Load validation system
    function loadValidationSystem() {
        return new Promise((resolve, reject) => {
            // Check if validation system is already loaded
            if (window.FlowguardValidation) {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = '<?php echo get_template_directory_uri(); ?>/assets/js/flowguard-validation.js';
            script.onload = () => {
                console.log('Flowguard validation system loaded successfully');
                resolve();
            };
            script.onerror = (error) => {
                console.error('Failed to load validation system:', error);
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
                    <div class="field-group remember-card-group">
                        <div id="remember-element" class="flowguard-field-container">
                            <div class="field-loading">
                                <div class="loading-spinner"></div>
                                <span>Loading remember card option...</span>
                            </div>
                        </div>
                        <div class="remember-card-info" id="remember-card-info">
                            <i class="fas fa-info-circle"></i>
                            <span>Your card details will be securely stored in your browser for faster future payments. You can remove saved cards anytime.</span>
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
                },
                remember: {
                    target: '#remember-element'
                },
                styles: {
                    price: {
                        base: {
                            color: "#ffffff"
                        },
                        title: {},
                        value: {}
                    },
                    remember: {
                        checkbox: {
                            base: {
                                size: "16px"
                            }
                        },
                        label: {
                            base: {
                                color: "#ffffff",
                                "font-size": "14px",
                                "font-weight": "400"
                            },
                            hover: {
                                color: "#ff6b6b",
                                "text-decoration": "underline"
                            }
                        },
                        link: {
                            base: {
                                color: "#ff6b6b",
                                "font-size": "12px",
                                "text-decoration": "underline"
                            },
                            hover: {
                                color: "#ffffff",
                                "font-weight": "600"
                            }
                        }
                    }
                }
            });
            
            console.log('Flowguard initialized with field elements');
            
            // Initialize validation system
            let validationSystem = null;
            if (window.FlowguardValidation) {
                validationSystem = new FlowguardValidation(flowguard, {
                    showFieldErrors: true,
                    showGlobalErrors: true,
                    autoRetry: true,
                    maxRetries: 3,
                    retryDelay: 2000,
                    enableHelp: true
                });
                console.log('Validation system initialized');
            }
            
            // Wait for elements to be ready before enabling submit
            const submitButton = document.getElementById('submit-payment');
            if (submitButton) {
                // Initially disable the button
                submitButton.disabled = true;
                submitButton.textContent = 'Loading payment form...';
                
                // Track which elements have loaded
                const loadedElements = new Set();
                const allElements = ['cardNumber', 'expDate', 'cardholder', 'cvv', 'price', 'remember'];
                
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
                                                elementName === 'cvv' ? 'cvv-element' :
                                                elementName === 'remember' ? 'remember-element' : 'price-element';
                                
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
                                        
                                        // Adjust iframe height after it loads
                                        setTimeout(() => {
                                            const iframe = fieldContainer.querySelector('iframe');
                                            if (iframe) {
                                                iframe.style.height = 'auto';
                                                iframe.style.minHeight = '40px';
                                                iframe.style.maxHeight = 'none';
                                                console.log('Adjusted iframe height to auto');
                                            }
                                        }, 100);
                                    } else {
                                        fieldContainer.classList.add('loaded');
                                        
                                        // Special handling for remember element
                                        if (elementName === 'remember') {
                                            // Show remember card info after a short delay
                                            setTimeout(() => {
                                                const rememberInfo = document.getElementById('remember-card-info');
                                                if (rememberInfo) {
                                                    rememberInfo.classList.add('show');
                                                }
                                            }, 500);
                                        }
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
                    
                    // Let Flowguard handle validation - we'll just check if the form is ready
                    console.log('Attempting payment submission - letting Flowguard handle validation');
                    
                    // Proceed with submission - let Flowguard handle validation
                    if (typeof flowguard.submit === 'function') {
                        console.log('Calling flowguard.submit() - Flowguard will handle validation');
                        this.disabled = true;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                        
                        // Hide any previous error messages
                        document.getElementById('payment-error').style.display = 'none';
                        
                        // Show pending message
                        document.getElementById('pending-message').textContent = '<?php esc_html_e('Processing your payment...', 'flexpress'); ?>';
                        document.getElementById('payment-pending').style.display = 'block';
                        
                        try {
                            flowguard.submit();
                            
                            // Set a timeout to prevent infinite spinning
                            const timeoutId = setTimeout(() => {
                                console.warn('Payment submission timeout - resetting button');
                                this.disabled = false;
                                this.innerHTML = '<i class="fas fa-credit-card me-2"></i>Complete Payment';
                                document.getElementById('payment-pending').style.display = 'none';
                                
                                document.getElementById('error-message').textContent = '<?php esc_html_e('Payment submission timed out. Please try again.', 'flexpress'); ?>';
                                document.getElementById('payment-error').style.display = 'block';
                            }, 30000); // 30 second timeout
                            
                            // Store timeout ID for potential cleanup
                            this._paymentTimeout = timeoutId;
                            
                        } catch (error) {
                            console.error('Error submitting payment:', error);
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-credit-card me-2"></i>Complete Payment';
                            document.getElementById('payment-pending').style.display = 'none';
                            
                            // Show error message
                            document.getElementById('error-message').textContent = '<?php esc_html_e('Failed to submit payment. Please try again.', 'flexpress'); ?>';
                            document.getElementById('payment-error').style.display = 'block';
                        }
                    } else {
                        console.error('flowguard.submit is not a function');
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-credit-card me-2"></i>Complete Payment';
                        
                        document.getElementById('error-message').textContent = '<?php esc_html_e('Payment form is not ready. Please refresh the page.', 'flexpress'); ?>';
                        document.getElementById('payment-error').style.display = 'block';
                    }
                });
            }
            
            // Debug: Check what methods are available on the payment form
            console.log('Payment form methods:', Object.getOwnPropertyNames(flowguard));
            console.log('Payment form prototype methods:', Object.getOwnPropertyNames(Object.getPrototypeOf(flowguard)));
            
            // Debug: Check if validation methods exist
            console.log('flowguard.validate exists:', typeof flowguard.validate === 'function');
            console.log('flowguard.getValidationErrors exists:', typeof flowguard.getValidationErrors === 'function');
            console.log('flowguard.submit exists:', typeof flowguard.submit === 'function');
            
            setupEventHandlers(flowguard, validationSystem);
            
            // Hide loading message
            document.getElementById('payment-pending').style.display = 'none';
            console.log('Flowguard payment form initialized successfully');
            
        } catch (error) {
            console.error('Error initializing payment form:', error);
            document.getElementById('error-message').textContent = '<?php esc_html_e('Error initializing payment form. Please try again.', 'flexpress'); ?>';
            document.getElementById('payment-error').style.display = 'block';
            document.getElementById('payment-pending').style.display = 'none';
        }
    }
    
    // Setup event handlers for payment form
    function setupEventHandlers(paymentForm, validationSystem) {
        // Check if the payment form has event handling methods
        if (typeof paymentForm.on === 'function') {
            // Setup event handlers using .on() method
            paymentForm.on('payment.success', (event) => {
                console.log('Payment successful:', event);
                
                // Clear any pending timeout
                const submitButton = document.getElementById('submit-payment');
                if (submitButton && submitButton._paymentTimeout) {
                    clearTimeout(submitButton._paymentTimeout);
                    submitButton._paymentTimeout = null;
                }
                
                // Handle through validation system if available
                if (validationSystem) {
                    validationSystem.handlePaymentSuccess(event);
                } else {
                    // Fallback to direct handling
                    document.getElementById('payment-pending').style.display = 'none';
                    document.getElementById('success-message').textContent = '<?php esc_html_e('Payment successful! Redirecting...', 'flexpress'); ?>';
                    document.getElementById('payment-success').style.display = 'block';
                }
                
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
                
                // Clear any pending timeout
                const submitButton = document.getElementById('submit-payment');
                if (submitButton) {
                    if (submitButton._paymentTimeout) {
                        clearTimeout(submitButton._paymentTimeout);
                        submitButton._paymentTimeout = null;
                    }
                    
                    // Reset submit button state
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-credit-card me-2"></i>Complete Payment';
                }
                
                // Handle specific Flowguard errors
                let errorMessage = 'Payment failed. Please try again.';
                let showRefreshButton = false;
                
                if (event.code === 422 || event.status === 422) {
                    errorMessage = 'Payment session expired.';
                    showRefreshButton = true;
                } else if (event.message && event.message.includes('sessionId')) {
                    errorMessage = 'Invalid payment session.';
                    showRefreshButton = true;
                } else if (event.message && event.message.includes('order-data')) {
                    errorMessage = 'Unable to load payment information.';
                    showRefreshButton = true;
                } else if (event.message) {
                    errorMessage = event.message;
                }
                
                // Handle through validation system if available
                if (validationSystem) {
                    validationSystem.handlePaymentError({
                        ...event,
                        message: errorMessage
                    });
                } else {
                    // Fallback to direct handling
                    document.getElementById('payment-pending').style.display = 'none';
                    document.getElementById('error-message').textContent = errorMessage;
                    document.getElementById('payment-error').style.display = 'block';
                    
                    // Show refresh button for session-related errors
                    const refreshButton = document.getElementById('refresh-session');
                    if (showRefreshButton && refreshButton) {
                        refreshButton.style.display = 'block';
                    }
                }
            });
            
            paymentForm.on('payment.pending', (event) => {
                console.log('Payment pending:', event);
                
                // Handle through validation system if available
                if (validationSystem) {
                    validationSystem.handlePaymentPending(event);
                } else {
                    // Fallback to direct handling
                    document.getElementById('payment-pending').textContent = event.message || 'Payment is being processed...';
                }
            });
            
            // Add validation-specific events
            paymentForm.on('field.change', (event) => {
                if (validationSystem) {
                    validationSystem.handleFieldChange(event);
                }
            });
            
            paymentForm.on('field.blur', (event) => {
                if (validationSystem) {
                    validationSystem.handleFieldBlur(event);
                }
            });
            
            paymentForm.on('field.focus', (event) => {
                if (validationSystem) {
                    validationSystem.handleFieldFocus(event);
                }
            });
            
            paymentForm.on('form.validate', (event) => {
                if (validationSystem) {
                    validationSystem.handleFormValidation(event);
                }
            });
            
        } else if (typeof paymentForm.addEventListener === 'function') {
            // Try addEventListener if available
            paymentForm.addEventListener('payment.success', (event) => {
                console.log('Payment successful:', event);
                
                if (validationSystem) {
                    validationSystem.handlePaymentSuccess(event);
                } else {
                    document.getElementById('payment-pending').style.display = 'none';
                    document.getElementById('success-message').textContent = '<?php esc_html_e('Payment successful! Redirecting...', 'flexpress'); ?>';
                    document.getElementById('payment-success').style.display = 'block';
                }
                
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
                
                if (validationSystem) {
                    validationSystem.handlePaymentError(event);
                } else {
                    document.getElementById('payment-pending').style.display = 'none';
                    document.getElementById('error-message').textContent = event.message || '<?php esc_html_e('Payment failed. Please try again.', 'flexpress'); ?>';
                    document.getElementById('payment-error').style.display = 'block';
                }
            });
        } else {
            console.log('No event handling methods found on payment form');
            console.log('Payment form will handle events internally');
        }
    }
    
    // Add refresh button functionality
    document.getElementById('refresh-session').addEventListener('click', function() {
        console.log('Refreshing payment session...');
        window.location.reload();
    });
    
    // Add global error handler for Flowguard API errors
    window.addEventListener('error', function(event) {
        if (event.message && event.message.includes('flowguard.yoursafe.com')) {
            console.error('Flowguard API error detected:', event);
            
            // Check if it's a 422 error (session expired)
            if (event.message.includes('422') || event.message.includes('Unprocessable Content')) {
                const submitButton = document.getElementById('submit-payment');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-credit-card me-2"></i>Complete Payment';
                }
                
                document.getElementById('payment-pending').style.display = 'none';
                document.getElementById('error-message').textContent = '<?php esc_html_e('Payment session expired.', 'flexpress'); ?>';
                document.getElementById('payment-error').style.display = 'block';
                
                // Show refresh button
                const refreshButton = document.getElementById('refresh-session');
                if (refreshButton) {
                    refreshButton.style.display = 'block';
                }
            }
        }
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
    
    // Watch for iframe addition and adjust height
    const priceContainer = document.getElementById('price-element');
    if (priceContainer && priceContainer.nodeType === Node.ELEMENT_NODE) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    const iframe = priceContainer.querySelector('iframe');
                    if (iframe) {
                        console.log('Iframe detected, adjusting height');
                        iframe.style.height = 'auto';
                        iframe.style.minHeight = '40px';
                        iframe.style.maxHeight = 'none';
                        iframe.style.width = '100%';
                        iframe.style.display = 'block';
                        iframe.style.margin = '0 auto';
                    }
                }
            });
        });
        
        try {
            observer.observe(priceContainer, { childList: true, subtree: true });
            console.log('Started watching for iframe changes');
        } catch (error) {
            console.error('Failed to observe price container:', error, priceContainer);
        }
    } else {
        console.warn('Price container element not found or invalid:', priceContainer);
    }
});
</script>

<?php get_footer(); ?>
