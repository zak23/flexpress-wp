/**
 * Flowguard Payment Form Validation System
 * 
 * Comprehensive validation and error handling for Flowguard payment forms.
 * Implements real-time validation, user-friendly error messages, and recovery mechanisms.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

class FlowguardValidation {
    constructor(paymentForm, options = {}) {
        this.paymentForm = paymentForm;
        this.options = {
            showFieldErrors: true,
            showGlobalErrors: true,
            autoRetry: true,
            maxRetries: 3,
            retryDelay: 2000,
            enableHelp: true,
            ...options
        };
        
        this.validationState = {
            isValid: false,
            errors: {},
            warnings: {},
            retryCount: 0,
            lastError: null
        };
        
        this.fieldValidators = {
            cardNumber: this.validateCardNumber.bind(this),
            expDate: this.validateExpDate.bind(this),
            cvv: this.validateCVV.bind(this),
            cardholder: this.validateCardholder.bind(this)
        };
        
        this.errorMessages = {
            // Card Number Errors
            'CARD_NUMBER_INVALID': 'Please enter a valid card number',
            'CARD_NUMBER_REQUIRED': 'Please enter your card number',
            'CARD_NUMBER_UNSUPPORTED': 'We don\'t accept this type of card',
            'CARD_NUMBER_EXPIRED': 'This card has expired',
            'CARD_NUMBER_LUHN_FAILED': 'Please check your card number',
            
            // Expiry Date Errors
            'EXP_DATE_INVALID': 'Please enter a valid expiry date',
            'EXP_DATE_REQUIRED': 'Please enter your card\'s expiry date',
            'EXP_DATE_PAST': 'Your card has expired',
            'EXP_DATE_FORMAT': 'Please enter expiry date as MM/YY',
            
            // CVV Errors
            'CVV_INVALID': 'Please enter a valid security code',
            'CVV_REQUIRED': 'Please enter your card\'s security code',
            'CVV_LENGTH': 'Security code must be 3-4 digits',
            'CVV_FORMAT': 'Security code must contain only numbers',
            
            // Cardholder Errors
            'CARDHOLDER_REQUIRED': 'Please enter the cardholder name',
            'CARDHOLDER_INVALID': 'Please enter a valid name',
            'CARDHOLDER_LENGTH': 'Name must be 2-50 characters',
            'CARDHOLDER_FORMAT': 'Name contains invalid characters',
            
            // General Errors
            'NETWORK_ERROR': 'Network connection error. Please check your internet connection.',
            'TIMEOUT_ERROR': 'Request timed out. Please try again.',
            'SERVER_ERROR': 'Server error occurred. Please try again later.',
            'VALIDATION_ERROR': 'Please check your payment information',
            'PAYMENT_DECLINED': 'Payment was declined. Please try a different card.',
            'INSUFFICIENT_FUNDS': 'Insufficient funds. Please try a different card.',
            'CARD_BLOCKED': 'Card is blocked. Please contact your bank.',
            'FRAUD_DETECTED': 'Transaction flagged for security review.',
            'RATE_LIMITED': 'Too many attempts. Please wait before trying again.',
            'SESSION_EXPIRED': 'Payment session expired. Please start over.',
            'INVALID_SESSION': 'Invalid payment session. Please try again.',
            
            // 3D Secure Errors
            'THREEDS_REQUIRED': 'Additional verification required',
            'THREEDS_FAILED': 'Verification failed. Please try again.',
            'THREEDS_CANCELLED': 'Verification was cancelled',
            'THREEDS_TIMEOUT': 'Verification timed out',
            
            // Unknown Errors
            'UNKNOWN_ERROR': 'An unexpected error occurred. Please try again.'
        };
        
        this.helpMessages = {
            cardNumber: 'Enter your 13-19 digit card number without spaces or dashes',
            expDate: 'Enter expiry date in MM/YY format (e.g., 12/25)',
            cvv: 'Enter the 3-4 digit security code on the back of your card',
            cardholder: 'Enter the name exactly as it appears on your card'
        };
        
        this.init();
    }
    
    /**
     * Initialize validation system
     */
    init() {
        this.setupEventListeners();
        this.createValidationUI();
        this.setupFieldWatchers();
        console.log('Flowguard validation system initialized');
    }
    
    /**
     * Setup event listeners for validation
     */
    setupEventListeners() {
        // Listen for Flowguard form events
        if (this.paymentForm && typeof this.paymentForm.on === 'function') {
            // Form validation events
            this.paymentForm.on('field.change', (event) => {
                this.handleFieldChange(event);
            });
            
            this.paymentForm.on('field.blur', (event) => {
                this.handleFieldBlur(event);
            });
            
            this.paymentForm.on('field.focus', (event) => {
                this.handleFieldFocus(event);
            });
            
            this.paymentForm.on('form.validate', (event) => {
                this.handleFormValidation(event);
            });
            
            // Payment events
            this.paymentForm.on('payment.error', (event) => {
                this.handlePaymentError(event);
            });
            
            this.paymentForm.on('payment.pending', (event) => {
                this.handlePaymentPending(event);
            });
            
            this.paymentForm.on('payment.success', (event) => {
                this.handlePaymentSuccess(event);
            });
            
            // Network events
            this.paymentForm.on('network.error', (event) => {
                this.handleNetworkError(event);
            });
            
            this.paymentForm.on('network.timeout', (event) => {
                this.handleTimeoutError(event);
            });
        }
        
        // Setup global error handlers
        window.addEventListener('unhandledrejection', (event) => {
            this.handleUnhandledError(event.reason);
        });
        
        // Setup network status monitoring
        window.addEventListener('online', () => {
            this.handleNetworkStatusChange(true);
        });
        
        window.addEventListener('offline', () => {
            this.handleNetworkStatusChange(false);
        });
    }
    
    /**
     * Create validation UI elements
     */
    createValidationUI() {
        // Create validation container
        const validationContainer = document.createElement('div');
        validationContainer.id = 'flowguard-validation-container';
        validationContainer.className = 'validation-container';
        
        // Create field error containers
        const fieldErrors = document.createElement('div');
        fieldErrors.id = 'field-errors';
        fieldErrors.className = 'field-errors';
        
        // Create global error container
        const globalErrors = document.createElement('div');
        globalErrors.id = 'global-errors';
        globalErrors.className = 'global-errors';
        
        // Create help container
        const helpContainer = document.createElement('div');
        helpContainer.id = 'validation-help';
        helpContainer.className = 'validation-help';
        
        // Create retry button
        const retryButton = document.createElement('button');
        retryButton.id = 'retry-payment';
        retryButton.className = 'btn btn-outline-warning btn-sm';
        retryButton.innerHTML = '<i class="fas fa-redo me-1"></i>Retry Payment';
        retryButton.style.display = 'none';
        retryButton.addEventListener('click', () => this.retryPayment());
        
        // Assemble validation UI
        validationContainer.appendChild(fieldErrors);
        validationContainer.appendChild(globalErrors);
        validationContainer.appendChild(helpContainer);
        validationContainer.appendChild(retryButton);
        
        // Insert after payment form
        const paymentForm = document.getElementById('flowguard-payment-form');
        if (paymentForm && paymentForm.parentNode) {
            paymentForm.parentNode.insertBefore(validationContainer, paymentForm.nextSibling);
        }
        
        // Add validation styles
        this.addValidationStyles();
    }
    
    /**
     * Add validation-specific styles
     */
    addValidationStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .validation-container {
                margin-top: 1rem;
            }
            
            .field-errors {
                margin-bottom: 1rem;
            }
            
            .field-error {
                background: #3a1e1e;
                color: #dc3545;
                border: 1px solid #dc3545;
                border-radius: 4px;
                padding: 0.75rem 1rem;
                margin-bottom: 0.5rem;
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .field-error i {
                color: #dc3545;
            }
            
            .global-errors {
                margin-bottom: 1rem;
            }
            
            .global-error {
                background: #3a1e1e;
                color: #dc3545;
                border: 1px solid #dc3545;
                border-radius: 4px;
                padding: 1rem 1.5rem;
                margin-bottom: 0.5rem;
                font-size: 1rem;
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .global-error i {
                color: #dc3545;
                margin-top: 0.125rem;
            }
            
            .validation-help {
                background: #1e3a1e;
                color: #28a745;
                border: 1px solid #28a745;
                border-radius: 4px;
                padding: 0.75rem 1rem;
                margin-bottom: 1rem;
                font-size: 0.9rem;
                display: none;
            }
            
            .validation-help.show {
                display: block;
            }
            
            .validation-help i {
                color: #28a745;
                margin-right: 0.5rem;
            }
            
            .field-container.error {
                border-color: #dc3545 !important;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
            }
            
            .field-container.warning {
                border-color: #ffc107 !important;
                box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
            }
            
            .field-container.success {
                border-color: #28a745 !important;
                box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
            }
            
            .retry-button {
                margin-top: 1rem;
            }
            
            .validation-summary {
                background: #1a1a1a;
                border: 1px solid #333;
                border-radius: 4px;
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .validation-summary h6 {
                color: #ffffff;
                margin-bottom: 0.5rem;
                font-size: 0.9rem;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            
            .validation-summary ul {
                margin: 0;
                padding-left: 1rem;
                color: #888;
                font-size: 0.85rem;
            }
            
            .validation-summary li {
                margin-bottom: 0.25rem;
            }
            
            .loading-validation {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                color: #888;
                font-size: 0.9rem;
                margin-bottom: 1rem;
            }
            
            .loading-validation .spinner {
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
            
            .error-icon {
                color: #dc3545;
                margin-right: 0.5rem;
            }
            
            .warning-icon {
                color: #ffc107;
                margin-right: 0.5rem;
            }
            
            .success-icon {
                color: #28a745;
                margin-right: 0.5rem;
            }
            
            .help-icon {
                color: #17a2b8;
                margin-right: 0.5rem;
            }
        `;
        document.head.appendChild(style);
    }
    
    /**
     * Setup field watchers for real-time validation
     */
    setupFieldWatchers() {
        // Watch for field containers
        const fieldContainers = document.querySelectorAll('.flowguard-field-container');
        fieldContainers.forEach(container => {
            // Add validation classes
            container.classList.add('validation-enabled');
            
            // Watch for iframe changes (Flowguard fields are in iframes)
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        const iframe = container.querySelector('iframe');
                        if (iframe) {
                            this.setupIframeValidation(iframe, container);
                        }
                    }
                });
            });
            
            observer.observe(container, { childList: true, subtree: true });
        });
    }
    
    /**
     * Setup validation for iframe fields
     */
    setupIframeValidation(iframe, container) {
        try {
            // Try to access iframe content for validation
            iframe.addEventListener('load', () => {
                try {
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    if (iframeDoc) {
                        // Setup input event listeners
                        const inputs = iframeDoc.querySelectorAll('input');
                        inputs.forEach(input => {
                            input.addEventListener('input', (e) => {
                                this.validateField(container.id, e.target.value);
                            });
                            
                            input.addEventListener('blur', (e) => {
                                this.validateField(container.id, e.target.value, true);
                            });
                        });
                    }
                } catch (error) {
                    // Cross-origin restrictions - use alternative validation
                    this.setupAlternativeValidation(iframe, container);
                }
            });
        } catch (error) {
            console.log('Cannot access iframe content for validation:', error);
            this.setupAlternativeValidation(iframe, container);
        }
    }
    
    /**
     * Setup alternative validation when iframe access is restricted
     */
    setupAlternativeValidation(iframe, container) {
        // Use visual cues and external validation
        const fieldId = container.id;
        
        // Add focus/blur handlers to container
        container.addEventListener('focusin', () => {
            this.showFieldHelp(fieldId);
        });
        
        container.addEventListener('focusout', () => {
            this.hideFieldHelp();
        });
        
        // Monitor iframe size changes as validation indicator
        const resizeObserver = new ResizeObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.contentRect.height > 40) {
                    // Field might have validation message
                    this.checkFieldStatus(fieldId);
                }
            });
        });
        
        resizeObserver.observe(iframe);
    }
    
    /**
     * Handle field change events
     */
    handleFieldChange(event) {
        const { field, value } = event;
        this.validateField(field, value);
        this.updateFieldStatus(field, 'typing');
    }
    
    /**
     * Handle field blur events
     */
    handleFieldBlur(event) {
        const { field, value } = event;
        this.validateField(field, value, true);
        this.updateFieldStatus(field, 'blurred');
    }
    
    /**
     * Handle field focus events
     */
    handleFieldFocus(event) {
        const { field } = event;
        this.showFieldHelp(field);
        this.updateFieldStatus(field, 'focused');
    }
    
    /**
     * Handle form validation events
     */
    handleFormValidation(event) {
        const { isValid, errors } = event;
        this.validationState.isValid = isValid;
        this.validationState.errors = errors || {};
        
        this.updateValidationUI();
        this.showValidationSummary();
    }
    
    /**
     * Handle payment error events
     */
    handlePaymentError(event) {
        this.validationState.lastError = event;
        this.validationState.retryCount++;
        
        const errorCode = event.code || event.error || 'UNKNOWN_ERROR';
        const errorMessage = this.getErrorMessage(errorCode);
        
        this.showGlobalError(errorMessage, errorCode);
        this.showRetryButton();
        
        // Log error for debugging
        console.error('Payment error:', event);
        
        // Auto-retry for certain errors
        if (this.options.autoRetry && this.shouldAutoRetry(errorCode)) {
            this.scheduleRetry();
        }
    }
    
    /**
     * Handle payment pending events
     */
    handlePaymentPending(event) {
        this.hideAllErrors();
        this.showPendingMessage(event.message || 'Processing payment...');
    }
    
    /**
     * Handle payment success events
     */
    handlePaymentSuccess(event) {
        this.hideAllErrors();
        this.hideRetryButton();
        this.showSuccessMessage('Payment successful!');
    }
    
    /**
     * Handle network error events
     */
    handleNetworkError(event) {
        this.showGlobalError('Network connection error. Please check your internet connection.', 'NETWORK_ERROR');
        this.showRetryButton();
    }
    
    /**
     * Handle timeout error events
     */
    handleTimeoutError(event) {
        this.showGlobalError('Request timed out. Please try again.', 'TIMEOUT_ERROR');
        this.showRetryButton();
    }
    
    /**
     * Handle unhandled errors
     */
    handleUnhandledError(error) {
        console.error('Unhandled error:', error);
        this.showGlobalError('An unexpected error occurred. Please try again.', 'UNKNOWN_ERROR');
        this.showRetryButton();
    }
    
    /**
     * Handle network status changes
     */
    handleNetworkStatusChange(isOnline) {
        if (!isOnline) {
            this.showGlobalError('You are currently offline. Please check your internet connection.', 'NETWORK_ERROR');
        } else {
            this.hideGlobalError('NETWORK_ERROR');
        }
    }
    
    /**
     * Validate individual field
     */
    validateField(fieldId, value, isBlur = false) {
        const fieldName = this.getFieldName(fieldId);
        const validator = this.fieldValidators[fieldName];
        
        if (!validator) {
            return;
        }
        
        const result = validator(value, isBlur);
        
        if (result.isValid) {
            this.clearFieldError(fieldId);
            this.updateFieldStatus(fieldId, 'success');
        } else {
            this.showFieldError(fieldId, result.message);
            this.updateFieldStatus(fieldId, 'error');
        }
        
        return result;
    }
    
    /**
     * Validate card number
     */
    validateCardNumber(value, isBlur = false) {
        if (!value || value.trim() === '') {
            return { isValid: false, message: 'Please enter your card number' };
        }
        
        // Remove spaces and dashes
        const cleanValue = value.replace(/[\s-]/g, '');
        
        // Check if it's all digits
        if (!/^\d+$/.test(cleanValue)) {
            return { isValid: false, message: 'Please enter a valid card number' };
        }
        
        // Check length
        if (cleanValue.length < 13 || cleanValue.length > 19) {
            return { isValid: false, message: 'Please enter a complete card number' };
        }
        
        // Luhn algorithm check
        if (!this.validateLuhn(cleanValue)) {
            return { isValid: false, message: 'Please check your card number' };
        }
        
        // Check card type
        const cardType = this.detectCardType(cleanValue);
        if (!cardType) {
            return { isValid: false, message: 'We don\'t accept this type of card' };
        }
        
        return { isValid: true, message: null, cardType };
    }
    
    /**
     * Validate expiry date
     */
    validateExpDate(value, isBlur = false) {
        if (!value || value.trim() === '') {
            return { isValid: false, message: this.errorMessages.EXP_DATE_REQUIRED };
        }
        
        // Check format MM/YY or MM/YYYY
        const formatMatch = value.match(/^(\d{1,2})\/(\d{2,4})$/);
        if (!formatMatch) {
            return { isValid: false, message: this.errorMessages.EXP_DATE_FORMAT };
        }
        
        const month = parseInt(formatMatch[1], 10);
        const year = parseInt(formatMatch[2], 10);
        
        // Validate month
        if (month < 1 || month > 12) {
            return { isValid: false, message: this.errorMessages.EXP_DATE_INVALID };
        }
        
        // Convert year to full year
        const fullYear = year < 100 ? 2000 + year : year;
        
        // Check if expired
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;
        
        if (fullYear < currentYear || (fullYear === currentYear && month < currentMonth)) {
            return { isValid: false, message: this.errorMessages.EXP_DATE_PAST };
        }
        
        return { isValid: true, message: null };
    }
    
    /**
     * Validate CVV
     */
    validateCVV(value, isBlur = false) {
        if (!value || value.trim() === '') {
            return { isValid: false, message: this.errorMessages.CVV_REQUIRED };
        }
        
        // Check if it's all digits
        if (!/^\d+$/.test(value)) {
            return { isValid: false, message: this.errorMessages.CVV_FORMAT };
        }
        
        // Check length
        if (value.length < 3 || value.length > 4) {
            return { isValid: false, message: this.errorMessages.CVV_LENGTH };
        }
        
        return { isValid: true, message: null };
    }
    
    /**
     * Validate cardholder name
     */
    validateCardholder(value, isBlur = false) {
        if (!value || value.trim() === '') {
            return { isValid: false, message: this.errorMessages.CARDHOLDER_REQUIRED };
        }
        
        const cleanValue = value.trim();
        
        // Check length
        if (cleanValue.length < 2 || cleanValue.length > 50) {
            return { isValid: false, message: this.errorMessages.CARDHOLDER_LENGTH };
        }
        
        // Check for valid characters (letters, spaces, hyphens, apostrophes)
        if (!/^[a-zA-Z\s\-']+$/.test(cleanValue)) {
            return { isValid: false, message: this.errorMessages.CARDHOLDER_FORMAT };
        }
        
        return { isValid: true, message: null };
    }
    
    /**
     * Validate Luhn algorithm
     */
    validateLuhn(cardNumber) {
        let sum = 0;
        let isEven = false;
        
        for (let i = cardNumber.length - 1; i >= 0; i--) {
            let digit = parseInt(cardNumber[i], 10);
            
            if (isEven) {
                digit *= 2;
                if (digit > 9) {
                    digit -= 9;
                }
            }
            
            sum += digit;
            isEven = !isEven;
        }
        
        return sum % 10 === 0;
    }
    
    /**
     * Detect card type
     */
    detectCardType(cardNumber) {
        const patterns = {
            visa: /^4/,
            mastercard: /^5[1-5]/,
            amex: /^3[47]/,
            discover: /^6(?:011|5)/,
            diners: /^3[0689]/,
            jcb: /^35/
        };
        
        for (const [type, pattern] of Object.entries(patterns)) {
            if (pattern.test(cardNumber)) {
                return type;
            }
        }
        
        return null;
    }
    
    /**
     * Get field name from field ID
     */
    getFieldName(fieldId) {
        const mapping = {
            'card-number-element': 'cardNumber',
            'exp-date-element': 'expDate',
            'cvv-element': 'cvv',
            'cardholder-element': 'cardholder'
        };
        
        return mapping[fieldId] || fieldId;
    }
    
    /**
     * Get error message for error code
     */
    getErrorMessage(errorCode) {
        return this.errorMessages[errorCode] || this.errorMessages.UNKNOWN_ERROR;
    }
    
    /**
     * Show field error
     */
    showFieldError(fieldId, message) {
        const fieldErrors = document.getElementById('field-errors');
        if (!fieldErrors) return;
        
        // Remove existing error for this field
        this.clearFieldError(fieldId);
        
        // Create error element
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.id = `error-${fieldId}`;
        errorElement.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i>
            <span>${message}</span>
        `;
        
        fieldErrors.appendChild(errorElement);
    }
    
    /**
     * Clear field error
     */
    clearFieldError(fieldId) {
        const existingError = document.getElementById(`error-${fieldId}`);
        if (existingError) {
            existingError.remove();
        }
    }
    
    /**
     * Show global error
     */
    showGlobalError(message, errorCode = 'UNKNOWN_ERROR') {
        const globalErrors = document.getElementById('global-errors');
        if (!globalErrors) return;
        
        // Remove existing global errors
        this.hideGlobalError();
        
        // Create error element
        const errorElement = document.createElement('div');
        errorElement.className = 'global-error';
        errorElement.id = `global-error-${errorCode}`;
        errorElement.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Payment Error</strong>
                <div>${message}</div>
            </div>
        `;
        
        globalErrors.appendChild(errorElement);
    }
    
    /**
     * Hide global error
     */
    hideGlobalError(errorCode = null) {
        if (errorCode) {
            const errorElement = document.getElementById(`global-error-${errorCode}`);
            if (errorElement) {
                errorElement.remove();
            }
        } else {
            const globalErrors = document.getElementById('global-errors');
            if (globalErrors) {
                globalErrors.innerHTML = '';
            }
        }
    }
    
    /**
     * Show field help
     */
    showFieldHelp(fieldId) {
        const fieldName = this.getFieldName(fieldId);
        const helpMessage = this.helpMessages[fieldName];
        
        if (!helpMessage) return;
        
        const helpContainer = document.getElementById('validation-help');
        if (helpContainer) {
            helpContainer.innerHTML = `
                <i class="fas fa-info-circle"></i>
                <span>${helpMessage}</span>
            `;
            helpContainer.classList.add('show');
        }
    }
    
    /**
     * Hide field help
     */
    hideFieldHelp() {
        const helpContainer = document.getElementById('validation-help');
        if (helpContainer) {
            helpContainer.classList.remove('show');
        }
    }
    
    /**
     * Update field status
     */
    updateFieldStatus(fieldId, status) {
        const container = document.getElementById(fieldId);
        if (!container) return;
        
        // Remove existing status classes
        container.classList.remove('error', 'warning', 'success', 'typing', 'focused', 'blurred');
        
        // Add new status class
        container.classList.add(status);
    }
    
    /**
     * Update validation UI
     */
    updateValidationUI() {
        // Update field statuses based on validation state
        Object.keys(this.validationState.errors).forEach(fieldId => {
            this.updateFieldStatus(fieldId, 'error');
        });
    }
    
    /**
     * Show validation summary
     */
    showValidationSummary() {
        const summaryContainer = document.getElementById('validation-summary');
        if (!summaryContainer) {
            // Create summary container
            const container = document.createElement('div');
            container.id = 'validation-summary';
            container.className = 'validation-summary';
            
            const validationContainer = document.getElementById('flowguard-validation-container');
            if (validationContainer) {
                validationContainer.insertBefore(container, validationContainer.firstChild);
            }
        }
        
        const summaryContainer = document.getElementById('validation-summary');
        const errorCount = Object.keys(this.validationState.errors).length;
        
        if (errorCount > 0) {
            summaryContainer.innerHTML = `
                <h6><i class="fas fa-exclamation-triangle error-icon"></i>Please fix the following errors:</h6>
                <ul>
                    ${Object.values(this.validationState.errors).map(error => `<li>${error}</li>`).join('')}
                </ul>
            `;
            summaryContainer.style.display = 'block';
        } else {
            summaryContainer.style.display = 'none';
        }
    }
    
    /**
     * Show retry button
     */
    showRetryButton() {
        const retryButton = document.getElementById('retry-payment');
        if (retryButton) {
            retryButton.style.display = 'block';
        }
    }
    
    /**
     * Hide retry button
     */
    hideRetryButton() {
        const retryButton = document.getElementById('retry-payment');
        if (retryButton) {
            retryButton.style.display = 'none';
        }
    }
    
    /**
     * Hide all errors
     */
    hideAllErrors() {
        this.hideGlobalError();
        const fieldErrors = document.getElementById('field-errors');
        if (fieldErrors) {
            fieldErrors.innerHTML = '';
        }
    }
    
    /**
     * Show pending message
     */
    showPendingMessage(message) {
        const pendingDiv = document.getElementById('payment-pending');
        if (pendingDiv) {
            pendingDiv.textContent = message;
            pendingDiv.style.display = 'block';
        }
    }
    
    /**
     * Show success message
     */
    showSuccessMessage(message) {
        const successDiv = document.getElementById('payment-success');
        if (successDiv) {
            successDiv.textContent = message;
            successDiv.style.display = 'block';
        }
    }
    
    /**
     * Check if error should trigger auto-retry
     */
    shouldAutoRetry(errorCode) {
        const autoRetryErrors = [
            'NETWORK_ERROR',
            'TIMEOUT_ERROR',
            'SERVER_ERROR',
            'RATE_LIMITED'
        ];
        
        return autoRetryErrors.includes(errorCode) && 
               this.validationState.retryCount < this.options.maxRetries;
    }
    
    /**
     * Schedule retry
     */
    scheduleRetry() {
        setTimeout(() => {
            this.retryPayment();
        }, this.options.retryDelay);
    }
    
    /**
     * Retry payment
     */
    retryPayment() {
        if (this.paymentForm && typeof this.paymentForm.submit === 'function') {
            this.hideAllErrors();
            this.hideRetryButton();
            this.showPendingMessage('Retrying payment...');
            
            try {
                this.paymentForm.submit();
            } catch (error) {
                console.error('Error retrying payment:', error);
                this.showGlobalError('Failed to retry payment. Please try again.', 'RETRY_FAILED');
            }
        }
    }
    
    /**
     * Check field status
     */
    checkFieldStatus(fieldId) {
        // This would be called when we detect field changes
        // Implementation depends on specific field monitoring
    }
    
    /**
     * Get validation state
     */
    getValidationState() {
        return { ...this.validationState };
    }
    
    /**
     * Reset validation state
     */
    resetValidationState() {
        this.validationState = {
            isValid: false,
            errors: {},
            warnings: {},
            retryCount: 0,
            lastError: null
        };
        
        this.hideAllErrors();
        this.hideRetryButton();
    }
}

// Export for global use
window.FlowguardValidation = FlowguardValidation;
