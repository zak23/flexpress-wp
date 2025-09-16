<?php
/**
 * Payment Validation Test Page
 * 
 * Simple test page to verify payment form validation works correctly.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

get_header();
?>

<main id="primary" class="site-main validation-test-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="test-container">
                    <div class="test-header text-center mb-4">
                        <h1 class="test-title">PAYMENT VALIDATION TEST</h1>
                        <p class="test-subtitle">Test the payment form validation without actual payment processing</p>
                    </div>
                    
                    <!-- Test Instructions -->
                    <div class="test-instructions mb-4">
                        <h5>Test Instructions:</h5>
                        <ol>
                            <li>Click "Complete Payment" without entering any details</li>
                            <li>Verify that validation error appears</li>
                            <li>Verify that button resets and doesn't spin forever</li>
                            <li>Test with partial data entry</li>
                            <li>Test with invalid data formats</li>
                        </ol>
                    </div>
                    
                    <!-- Mock Payment Form -->
                    <div class="mock-payment-form">
                        <h5>Mock Payment Form</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="test-card-number">Card Number</label>
                                    <input type="text" id="test-card-number" class="form-control" placeholder="1234 5678 9012 3456">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="test-exp-date">Expiry Date</label>
                                    <input type="text" id="test-exp-date" class="form-control" placeholder="MM/YY">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="test-cvv">CVV</label>
                                    <input type="text" id="test-cvv" class="form-control" placeholder="123">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="test-cardholder">Cardholder Name</label>
                                    <input type="text" id="test-cardholder" class="form-control" placeholder="John Doe">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Test Buttons -->
                        <div class="test-buttons">
                            <button id="test-submit-empty" class="btn btn-danger me-2">
                                <i class="fas fa-exclamation-triangle me-1"></i>Test Empty Form
                            </button>
                            <button id="test-submit-partial" class="btn btn-warning me-2">
                                <i class="fas fa-exclamation-circle me-1"></i>Test Partial Data
                            </button>
                            <button id="test-submit-invalid" class="btn btn-warning me-2">
                                <i class="fas fa-times-circle me-1"></i>Test Invalid Data
                            </button>
                            <button id="test-submit-valid" class="btn btn-success">
                                <i class="fas fa-check-circle me-1"></i>Test Valid Data
                            </button>
                        </div>
                    </div>
                    
                    <!-- Test Results -->
                    <div class="test-results mt-4">
                        <h5>Test Results</h5>
                        <div id="test-output" class="test-output">
                            <p class="text-muted">Click a test button above to see results...</p>
                        </div>
                    </div>
                    
                    <!-- Validation Status -->
                    <div class="validation-status mt-4">
                        <h5>Validation Status</h5>
                        <div id="validation-status" class="status-display">
                            <div class="status-item">
                                <span class="status-label">Form Valid:</span>
                                <span class="status-value" id="form-valid">Unknown</span>
                            </div>
                            <div class="status-item">
                                <span class="status-label">Error Count:</span>
                                <span class="status-value" id="error-count">0</span>
                            </div>
                            <div class="status-item">
                                <span class="status-label">Button State:</span>
                                <span class="status-value" id="button-state">Ready</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Load test page styles -->
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/assets/css/validation-test.css">

<script>
// Mock validation system for testing
class MockPaymentValidation {
    constructor() {
        this.validationState = {
            isValid: false,
            errors: {},
            buttonState: 'ready'
        };
        
        this.init();
    }
    
    init() {
        this.setupTestButtons();
        this.updateStatus();
        this.log('Mock payment validation system initialized', 'info');
    }
    
    setupTestButtons() {
        // Test empty form
        document.getElementById('test-submit-empty').addEventListener('click', () => {
            this.clearForm();
            this.testSubmission();
        });
        
        // Test partial data
        document.getElementById('test-submit-partial').addEventListener('click', () => {
            this.fillPartialData();
            this.testSubmission();
        });
        
        // Test invalid data
        document.getElementById('test-submit-invalid').addEventListener('click', () => {
            this.fillInvalidData();
            this.testSubmission();
        });
        
        // Test valid data
        document.getElementById('test-submit-valid').addEventListener('click', () => {
            this.fillValidData();
            this.testSubmission();
        });
    }
    
    clearForm() {
        document.getElementById('test-card-number').value = '';
        document.getElementById('test-exp-date').value = '';
        document.getElementById('test-cvv').value = '';
        document.getElementById('test-cardholder').value = '';
        this.log('Form cleared', 'info');
    }
    
    fillPartialData() {
        document.getElementById('test-card-number').value = '4111111111111111';
        document.getElementById('test-exp-date').value = '';
        document.getElementById('test-cvv').value = '';
        document.getElementById('test-cardholder').value = '';
        this.log('Partial data filled (card number only)', 'info');
    }
    
    fillInvalidData() {
        document.getElementById('test-card-number').value = '1234';
        document.getElementById('test-exp-date').value = '13/25';
        document.getElementById('test-cvv').value = '12';
        document.getElementById('test-cardholder').value = 'A';
        this.log('Invalid data filled', 'info');
    }
    
    fillValidData() {
        document.getElementById('test-card-number').value = '4111111111111111';
        document.getElementById('test-exp-date').value = '12/25';
        document.getElementById('test-cvv').value = '123';
        document.getElementById('test-cardholder').value = 'John Doe';
        this.log('Valid data filled', 'info');
    }
    
    testSubmission() {
        this.log('Testing form submission...', 'info');
        
        // Simulate button click behavior
        this.setButtonState('processing');
        
        // Validate form
        const validationResult = this.validateForm();
        
        if (!validationResult.isValid) {
            this.log(`Validation failed: ${validationResult.errors.join(', ')}`, 'error');
            this.setButtonState('error');
            
            // Simulate error display
            setTimeout(() => {
                this.setButtonState('ready');
                this.log('Button reset after validation error', 'success');
            }, 2000);
        } else {
            this.log('Validation passed - would proceed with payment', 'success');
            this.setButtonState('success');
            
            // Simulate success
            setTimeout(() => {
                this.setButtonState('ready');
                this.log('Payment simulation completed', 'success');
            }, 3000);
        }
        
        this.updateStatus();
    }
    
    validateForm() {
        const errors = [];
        
        // Check card number
        const cardNumber = document.getElementById('test-card-number').value.trim();
        if (!cardNumber) {
            errors.push('Card number is required');
        } else if (cardNumber.length < 13) {
            errors.push('Card number is too short');
        }
        
        // Check expiry date
        const expDate = document.getElementById('test-exp-date').value.trim();
        if (!expDate) {
            errors.push('Expiry date is required');
        } else if (!/^\d{2}\/\d{2}$/.test(expDate)) {
            errors.push('Expiry date format is invalid');
        }
        
        // Check CVV
        const cvv = document.getElementById('test-cvv').value.trim();
        if (!cvv) {
            errors.push('CVV is required');
        } else if (cvv.length < 3) {
            errors.push('CVV is too short');
        }
        
        // Check cardholder name
        const cardholder = document.getElementById('test-cardholder').value.trim();
        if (!cardholder) {
            errors.push('Cardholder name is required');
        } else if (cardholder.length < 2) {
            errors.push('Cardholder name is too short');
        }
        
        this.validationState.isValid = errors.length === 0;
        this.validationState.errors = errors;
        
        return {
            isValid: this.validationState.isValid,
            errors: errors
        };
    }
    
    setButtonState(state) {
        this.validationState.buttonState = state;
        
        const buttonStates = {
            'ready': 'Ready',
            'processing': 'Processing...',
            'error': 'Error',
            'success': 'Success'
        };
        
        document.getElementById('button-state').textContent = buttonStates[state] || state;
        document.getElementById('button-state').className = `status-value ${state}`;
    }
    
    updateStatus() {
        document.getElementById('form-valid').textContent = this.validationState.isValid ? 'Yes' : 'No';
        document.getElementById('form-valid').className = `status-value ${this.validationState.isValid ? 'success' : 'error'}`;
        
        document.getElementById('error-count').textContent = this.validationState.errors.length;
    }
    
    log(message, type = 'info') {
        const output = document.getElementById('test-output');
        const timestamp = new Date().toLocaleTimeString();
        
        const resultDiv = document.createElement('div');
        resultDiv.className = `test-result ${type}`;
        resultDiv.innerHTML = `<strong>[${timestamp}]</strong> ${message}`;
        
        output.appendChild(resultDiv);
        output.scrollTop = output.scrollHeight;
    }
}

// Initialize mock validation system
let mockValidation = null;

document.addEventListener('DOMContentLoaded', function() {
    mockValidation = new MockPaymentValidation();
});
</script>

<?php get_footer(); ?>
