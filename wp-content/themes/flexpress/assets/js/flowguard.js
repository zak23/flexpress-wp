/**
 * Flowguard SDK Integration
 * 
 * Handles frontend integration with Flowguard payment SDK.
 * Provides seamless payment form embedding and event handling.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

class FlexPressFlowguard {
    constructor(config) {
        this.apiKey = config.apiKey;
        this.merchantId = config.merchantId;
        this.environment = config.environment;
        this.sessionId = null;
        this.paymentForm = null;
        this.init();
    }
    
    /**
     * Initialize Flowguard
     */
    async init() {
        await this.loadSDK();
    }
    
    /**
     * Load Flowguard SDK
     */
    async loadSDK() {
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
    
    /**
     * Initialize payment form
     * 
     * @param {string} containerId Container element ID
     * @param {string} sessionId Session ID from API
     * @param {object} options Payment form options
     * @return {Promise} Payment form instance
     */
    async initPaymentForm(containerId, sessionId, options = {}) {
        if (!window.Flowguard) {
            throw new Error('Flowguard SDK not loaded');
        }
        
        const container = document.getElementById(containerId);
        if (!container) {
            throw new Error('Payment container not found: ' + containerId);
        }
        
        this.sessionId = sessionId;
        
        // Default options
        const defaultOptions = {
            theme: 'dark',
            locale: 'en_US',
            enable3DSecure: true,
            rememberCard: true,
            showCardIcons: true,
            showSecurityBadge: true,
            customStyles: {
                primaryColor: '#ff6b6b',
                backgroundColor: '#1a1a1a',
                textColor: '#ffffff',
                borderColor: '#333333',
                borderRadius: '8px',
                fontFamily: 'Inter, sans-serif'
            }
        };
        
        const formOptions = { ...defaultOptions, ...options };
        
        try {
            // Initialize Flowguard payment form
            this.paymentForm = new Flowguard.PaymentForm({
                sessionId: sessionId,
                container: container,
                options: formOptions
            });
            
            // Setup event handlers
            this.setupEventHandlers();
            
            console.log('Flowguard payment form initialized successfully');
            return this.paymentForm;
            
        } catch (error) {
            console.error('Error initializing Flowguard payment form:', error);
            throw error;
        }
    }
    
    /**
     * Setup event handlers
     */
    setupEventHandlers() {
        if (!this.paymentForm) {
            return;
        }
        
        // Payment success
        this.paymentForm.on('payment.success', (event) => {
            console.log('Payment successful:', event);
            this.handlePaymentSuccess(event);
        });
        
        // Payment error
        this.paymentForm.on('payment.error', (event) => {
            console.error('Payment error:', event);
            this.handlePaymentError(event);
        });
        
        // Payment pending
        this.paymentForm.on('payment.pending', (event) => {
            console.log('Payment pending:', event);
            this.handlePaymentPending(event);
        });
        
        // Form validation
        this.paymentForm.on('form.validation', (event) => {
            console.log('Form validation:', event);
            this.handleFormValidation(event);
        });
        
        // Card type detection
        this.paymentForm.on('card.type.detected', (event) => {
            console.log('Card type detected:', event);
            this.handleCardTypeDetection(event);
        });
    }
    
    /**
     * Handle payment success
     * 
     * @param {object} event Payment success event
     */
    handlePaymentSuccess(event) {
        // Hide any error messages
        this.hideErrorMessage();
        this.hidePendingMessage();
        
        // Show success message
        this.showSuccessMessage('Payment successful! Redirecting...');
        
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
    }
    
    /**
     * Handle payment error
     * 
     * @param {object} event Payment error event
     */
    handlePaymentError(event) {
        this.hideSuccessMessage();
        this.hidePendingMessage();
        
        let errorMessage = 'Payment failed. Please try again.';
        
        if (event.message) {
            errorMessage = event.message;
        } else if (event.error) {
            errorMessage = event.error;
        }
        
        this.showErrorMessage(errorMessage);
        
        // Log error for debugging
        console.error('Payment error details:', event);
    }
    
    /**
     * Handle payment pending
     * 
     * @param {object} event Payment pending event
     */
    handlePaymentPending(event) {
        this.hideErrorMessage();
        this.hideSuccessMessage();
        
        let pendingMessage = 'Payment is being processed...';
        
        if (event.message) {
            pendingMessage = event.message;
        }
        
        this.showPendingMessage(pendingMessage);
    }
    
    /**
     * Handle form validation
     * 
     * @param {object} event Validation event
     */
    handleFormValidation(event) {
        // Update form validation UI
        if (event.isValid) {
            this.hideErrorMessage();
        } else {
            this.showErrorMessage('Please check your payment information');
        }
    }
    
    /**
     * Handle card type detection
     * 
     * @param {object} event Card type event
     */
    handleCardTypeDetection(event) {
        // Update UI to show detected card type
        const cardTypeElement = document.getElementById('detected-card-type');
        if (cardTypeElement && event.cardType) {
            cardTypeElement.textContent = event.cardType;
            cardTypeElement.style.display = 'block';
        }
    }
    
    /**
     * Show error message
     * 
     * @param {string} message Error message
     */
    showErrorMessage(message) {
        const errorDiv = document.getElementById('payment-error');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            errorDiv.className = 'alert alert-danger';
        }
    }
    
    /**
     * Hide error message
     */
    hideErrorMessage() {
        const errorDiv = document.getElementById('payment-error');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }
    
    /**
     * Show success message
     * 
     * @param {string} message Success message
     */
    showSuccessMessage(message) {
        const successDiv = document.getElementById('payment-success');
        if (successDiv) {
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            successDiv.className = 'alert alert-success';
        }
    }
    
    /**
     * Hide success message
     */
    hideSuccessMessage() {
        const successDiv = document.getElementById('payment-success');
        if (successDiv) {
            successDiv.style.display = 'none';
        }
    }
    
    /**
     * Show pending message
     * 
     * @param {string} message Pending message
     */
    showPendingMessage(message) {
        const pendingDiv = document.getElementById('payment-pending');
        if (pendingDiv) {
            pendingDiv.textContent = message;
            pendingDiv.style.display = 'block';
            pendingDiv.className = 'alert alert-warning';
        }
    }
    
    /**
     * Hide pending message
     */
    hidePendingMessage() {
        const pendingDiv = document.getElementById('payment-pending');
        if (pendingDiv) {
            pendingDiv.style.display = 'none';
        }
    }
    
    /**
     * Destroy payment form
     */
    destroy() {
        if (this.paymentForm) {
            this.paymentForm.destroy();
            this.paymentForm = null;
        }
    }
    
    /**
     * Get payment form instance
     * 
     * @return {object|null} Payment form instance
     */
    getPaymentForm() {
        return this.paymentForm;
    }
    
    /**
     * Check if payment form is ready
     * 
     * @return {boolean} True if ready
     */
    isReady() {
        return this.paymentForm !== null;
    }
}

/**
 * Initialize Flowguard payment form
 * 
 * @param {string} sessionId Session ID
 * @param {object} options Payment options
 */
function initFlowguardPayment(sessionId, options = {}) {
    // Get Flowguard settings from localized script
    const flowguardConfig = window.flowguardConfig || {};
    
    if (!flowguardConfig.shopId || !flowguardConfig.environment) {
        console.error('Flowguard configuration not found');
        return;
    }
    
    // Initialize Flowguard
    const flowguard = new FlexPressFlowguard({
        apiKey: flowguardConfig.apiKey || '',
        merchantId: flowguardConfig.shopId,
        environment: flowguardConfig.environment
    });
    
    // Initialize payment form
    flowguard.initPaymentForm('flowguard-payment-form', sessionId, options)
        .then(() => {
            console.log('Flowguard payment form ready');
        })
        .catch((error) => {
            console.error('Error initializing Flowguard payment form:', error);
            document.getElementById('payment-error').textContent = 'Error loading payment form. Please try again.';
            document.getElementById('payment-error').style.display = 'block';
        });
    
    return flowguard;
}

/**
 * Handle promo code application
 * 
 * @param {string} promoCode Promo code
 * @return {Promise} Promise resolving to discount info
 */
async function applyPromoCode(promoCode) {
    try {
        const response = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'apply_promo_code',
                promo_code: promoCode,
                nonce: window.flowguardConfig.nonce
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        } else {
            throw new Error(data.data || 'Invalid promo code');
        }
    } catch (error) {
        console.error('Error applying promo code:', error);
        throw error;
    }
}

/**
 * Handle plan selection
 * 
 * @param {string} planId Plan ID
 * @return {Promise} Promise resolving to payment URL
 */
async function selectPlan(planId) {
    try {
        const response = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'create_flowguard_subscription',
                plan_id: planId,
                nonce: window.flowguardConfig.nonce
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        } else {
            throw new Error(data.data || 'Failed to create subscription');
        }
    } catch (error) {
        console.error('Error selecting plan:', error);
        throw error;
    }
}

/**
 * Handle PPV purchase
 * 
 * @param {string} episodeId Episode ID
 * @return {Promise} Promise resolving to payment URL
 */
async function purchasePPV(episodeId) {
    try {
        const response = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'create_flowguard_ppv_purchase',
                episode_id: episodeId,
                nonce: window.flowguardConfig.nonce
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        } else {
            throw new Error(data.data || 'Failed to create PPV purchase');
        }
    } catch (error) {
        console.error('Error creating PPV purchase:', error);
        throw error;
    }
}

// Export for global use
window.FlexPressFlowguard = FlexPressFlowguard;
window.initFlowguardPayment = initFlowguardPayment;
window.applyPromoCode = applyPromoCode;
window.selectPlan = selectPlan;
window.purchasePPV = purchasePPV;
