<?php
/**
 * Flowguard Validation System Test Page
 * 
 * Test page to demonstrate and validate the payment form validation system.
 * This page simulates various error scenarios and validation states.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

get_header();
?>

<main id="primary" class="site-main validation-test-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="test-container">
                    <div class="test-header text-center mb-4">
                        <h1 class="test-title">FLOWGUARD VALIDATION SYSTEM TEST</h1>
                        <p class="test-subtitle">Comprehensive testing of payment form validation and error handling</p>
                    </div>
                    
                    <!-- Test Controls -->
                    <div class="test-controls mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Validation Tests</h5>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" onclick="testCardNumberValidation()">
                                        Test Card Number Validation
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="testExpDateValidation()">
                                        Test Expiry Date Validation
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="testCVVValidation()">
                                        Test CVV Validation
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="testCardholderValidation()">
                                        Test Cardholder Validation
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>Error Scenarios</h5>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-danger" onclick="testNetworkError()">
                                        Test Network Error
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="testTimeoutError()">
                                        Test Timeout Error
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="testPaymentDeclined()">
                                        Test Payment Declined
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="testFraudDetection()">
                                        Test Fraud Detection
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Test Results -->
                    <div class="test-results">
                        <h5>Test Results</h5>
                        <div id="test-output" class="test-output">
                            <p class="text-muted">Click a test button above to see results...</p>
                        </div>
                    </div>
                    
                    <!-- Mock Payment Form -->
                    <div class="mock-payment-form mt-4">
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
                        <button class="btn btn-primary" onclick="testFormSubmission()">
                            Test Form Submission
                        </button>
                    </div>
                    
                    <!-- Validation System Status -->
                    <div class="validation-status mt-4">
                        <h5>Validation System Status</h5>
                        <div id="validation-status" class="status-display">
                            <div class="status-item">
                                <span class="status-label">Validation System:</span>
                                <span class="status-value" id="system-status">Not Initialized</span>
                            </div>
                            <div class="status-item">
                                <span class="status-label">Form Valid:</span>
                                <span class="status-value" id="form-valid">Unknown</span>
                            </div>
                            <div class="status-item">
                                <span class="status-label">Error Count:</span>
                                <span class="status-value" id="error-count">0</span>
                            </div>
                            <div class="status-item">
                                <span class="status-label">Retry Count:</span>
                                <span class="status-value" id="retry-count">0</span>
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
class MockValidationSystem {
    constructor() {
        this.validationState = {
            isValid: false,
            errors: {},
            warnings: {},
            retryCount: 0,
            lastError: null
        };
        
        this.errorMessages = {
            'CARD_NUMBER_INVALID': 'Please enter a valid card number',
            'CARD_NUMBER_REQUIRED': 'Card number is required',
            'EXP_DATE_INVALID': 'Please enter a valid expiry date (MM/YY)',
            'EXP_DATE_REQUIRED': 'Expiry date is required',
            'CVV_INVALID': 'Please enter a valid CVV',
            'CVV_REQUIRED': 'CVV is required',
            'CARDHOLDER_REQUIRED': 'Cardholder name is required',
            'CARDHOLDER_INVALID': 'Please enter a valid name',
            'NETWORK_ERROR': 'Network connection error. Please check your internet connection.',
            'TIMEOUT_ERROR': 'Request timed out. Please try again.',
            'PAYMENT_DECLINED': 'Payment was declined. Please try a different card.',
            'FRAUD_DETECTED': 'Transaction flagged for security review.'
        };
        
        this.init();
    }
    
    init() {
        this.updateStatus();
        this.setupFormValidation();
        this.log('Validation system initialized', 'info');
    }
    
    setupFormValidation() {
        const inputs = document.querySelectorAll('.mock-payment-form input');
        inputs.forEach(input => {
            input.addEventListener('input', (e) => {
                this.validateField(e.target);
            });
            
            input.addEventListener('blur', (e) => {
                this.validateField(e.target, true);
            });
        });
    }
    
    validateField(input, isBlur = false) {
        const fieldId = input.id;
        const value = input.value.trim();
        
        let result = { isValid: true, message: null };
        
        switch (fieldId) {
            case 'test-card-number':
                result = this.validateCardNumber(value, isBlur);
                break;
            case 'test-exp-date':
                result = this.validateExpDate(value, isBlur);
                break;
            case 'test-cvv':
                result = this.validateCVV(value, isBlur);
                break;
            case 'test-cardholder':
                result = this.validateCardholder(value, isBlur);
                break;
        }
        
        this.updateFieldUI(input, result);
        this.updateValidationState();
        
        return result;
    }
    
    validateCardNumber(value, isBlur = false) {
        if (!value) {
            return { isValid: false, message: this.errorMessages.CARD_NUMBER_REQUIRED };
        }
        
        const cleanValue = value.replace(/[\s-]/g, '');
        
        if (!/^\d+$/.test(cleanValue)) {
            return { isValid: false, message: this.errorMessages.CARD_NUMBER_INVALID };
        }
        
        if (cleanValue.length < 13 || cleanValue.length > 19) {
            return { isValid: false, message: this.errorMessages.CARD_NUMBER_INVALID };
        }
        
        if (!this.validateLuhn(cleanValue)) {
            return { isValid: false, message: this.errorMessages.CARD_NUMBER_INVALID };
        }
        
        return { isValid: true, message: null };
    }
    
    validateExpDate(value, isBlur = false) {
        if (!value) {
            return { isValid: false, message: this.errorMessages.EXP_DATE_REQUIRED };
        }
        
        const formatMatch = value.match(/^(\d{1,2})\/(\d{2,4})$/);
        if (!formatMatch) {
            return { isValid: false, message: this.errorMessages.EXP_DATE_INVALID };
        }
        
        const month = parseInt(formatMatch[1], 10);
        const year = parseInt(formatMatch[2], 10);
        
        if (month < 1 || month > 12) {
            return { isValid: false, message: this.errorMessages.EXP_DATE_INVALID };
        }
        
        const fullYear = year < 100 ? 2000 + year : year;
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;
        
        if (fullYear < currentYear || (fullYear === currentYear && month < currentMonth)) {
            return { isValid: false, message: this.errorMessages.EXP_DATE_INVALID };
        }
        
        return { isValid: true, message: null };
    }
    
    validateCVV(value, isBlur = false) {
        if (!value) {
            return { isValid: false, message: this.errorMessages.CVV_REQUIRED };
        }
        
        if (!/^\d+$/.test(value)) {
            return { isValid: false, message: this.errorMessages.CVV_INVALID };
        }
        
        if (value.length < 3 || value.length > 4) {
            return { isValid: false, message: this.errorMessages.CVV_INVALID };
        }
        
        return { isValid: true, message: null };
    }
    
    validateCardholder(value, isBlur = false) {
        if (!value) {
            return { isValid: false, message: this.errorMessages.CARDHOLDER_REQUIRED };
        }
        
        const cleanValue = value.trim();
        
        if (cleanValue.length < 2 || cleanValue.length > 50) {
            return { isValid: false, message: this.errorMessages.CARDHOLDER_INVALID };
        }
        
        if (!/^[a-zA-Z\s\-']+$/.test(cleanValue)) {
            return { isValid: false, message: this.errorMessages.CARDHOLDER_INVALID };
        }
        
        return { isValid: true, message: null };
    }
    
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
    
    updateFieldUI(input, result) {
        const feedback = input.parentNode.querySelector('.invalid-feedback');
        
        input.classList.remove('is-valid', 'is-invalid');
        
        if (result.isValid) {
            input.classList.add('is-valid');
            if (feedback) feedback.textContent = '';
        } else {
            input.classList.add('is-invalid');
            if (feedback) feedback.textContent = result.message;
        }
    }
    
    updateValidationState() {
        const inputs = document.querySelectorAll('.mock-payment-form input');
        let isValid = true;
        const errors = {};
        
        inputs.forEach(input => {
            const fieldId = input.id;
            const value = input.value.trim();
            
            if (!value) {
                isValid = false;
                errors[fieldId] = 'Field is required';
            } else if (input.classList.contains('is-invalid')) {
                isValid = false;
                const feedback = input.parentNode.querySelector('.invalid-feedback');
                errors[fieldId] = feedback ? feedback.textContent : 'Invalid value';
            }
        });
        
        this.validationState.isValid = isValid;
        this.validationState.errors = errors;
        
        this.updateStatus();
    }
    
    updateStatus() {
        document.getElementById('system-status').textContent = 'Initialized';
        document.getElementById('system-status').className = 'status-value success';
        
        document.getElementById('form-valid').textContent = this.validationState.isValid ? 'Yes' : 'No';
        document.getElementById('form-valid').className = `status-value ${this.validationState.isValid ? 'success' : 'error'}`;
        
        document.getElementById('error-count').textContent = Object.keys(this.validationState.errors).length;
        document.getElementById('retry-count').textContent = this.validationState.retryCount;
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
    
    simulateError(errorCode, message) {
        this.validationState.lastError = { code: errorCode, message };
        this.validationState.retryCount++;
        
        this.log(`Simulated ${errorCode}: ${message}`, 'error');
        this.updateStatus();
    }
}

// Initialize mock validation system
let mockValidation = null;

document.addEventListener('DOMContentLoaded', function() {
    mockValidation = new MockValidationSystem();
});

// Test functions
function testCardNumberValidation() {
    const testCases = [
        { value: '', expected: false, description: 'Empty card number' },
        { value: '1234', expected: false, description: 'Too short card number' },
        { value: '12345678901234567890', expected: false, description: 'Too long card number' },
        { value: 'abcd123456789012', expected: false, description: 'Non-numeric card number' },
        { value: '4111111111111111', expected: true, description: 'Valid Visa card' },
        { value: '5555555555554444', expected: true, description: 'Valid Mastercard' },
        { value: '378282246310005', expected: true, description: 'Valid American Express' },
        { value: '4000000000000002', expected: false, description: 'Invalid Luhn check' }
    ];
    
    mockValidation.log('Testing card number validation...', 'info');
    
    testCases.forEach(testCase => {
        const result = mockValidation.validateCardNumber(testCase.value, true);
        const passed = result.isValid === testCase.expected;
        
        mockValidation.log(`${testCase.description}: ${passed ? 'PASS' : 'FAIL'} (${testCase.value})`, 
                          passed ? 'success' : 'error');
    });
}

function testExpDateValidation() {
    const testCases = [
        { value: '', expected: false, description: 'Empty expiry date' },
        { value: '13/25', expected: false, description: 'Invalid month' },
        { value: '12/20', expected: false, description: 'Expired card' },
        { value: '12/25', expected: true, description: 'Valid future date' },
        { value: '1/26', expected: true, description: 'Valid single digit month' },
        { value: '12/2025', expected: true, description: 'Valid 4-digit year' },
        { value: '12-25', expected: false, description: 'Wrong separator' }
    ];
    
    mockValidation.log('Testing expiry date validation...', 'info');
    
    testCases.forEach(testCase => {
        const result = mockValidation.validateExpDate(testCase.value, true);
        const passed = result.isValid === testCase.expected;
        
        mockValidation.log(`${testCase.description}: ${passed ? 'PASS' : 'FAIL'} (${testCase.value})`, 
                          passed ? 'success' : 'error');
    });
}

function testCVVValidation() {
    const testCases = [
        { value: '', expected: false, description: 'Empty CVV' },
        { value: '12', expected: false, description: 'Too short CVV' },
        { value: '12345', expected: false, description: 'Too long CVV' },
        { value: 'abc', expected: false, description: 'Non-numeric CVV' },
        { value: '123', expected: true, description: 'Valid 3-digit CVV' },
        { value: '1234', expected: true, description: 'Valid 4-digit CVV' }
    ];
    
    mockValidation.log('Testing CVV validation...', 'info');
    
    testCases.forEach(testCase => {
        const result = mockValidation.validateCVV(testCase.value, true);
        const passed = result.isValid === testCase.expected;
        
        mockValidation.log(`${testCase.description}: ${passed ? 'PASS' : 'FAIL'} (${testCase.value})`, 
                          passed ? 'success' : 'error');
    });
}

function testCardholderValidation() {
    const testCases = [
        { value: '', expected: false, description: 'Empty cardholder name' },
        { value: 'A', expected: false, description: 'Too short name' },
        { value: 'A'.repeat(51), expected: false, description: 'Too long name' },
        { value: 'John123', expected: false, description: 'Name with numbers' },
        { value: 'John@Doe', expected: false, description: 'Name with special chars' },
        { value: 'John Doe', expected: true, description: 'Valid name with space' },
        { value: 'Mary-Jane', expected: true, description: 'Valid name with hyphen' },
        { value: "O'Connor", expected: true, description: 'Valid name with apostrophe' }
    ];
    
    mockValidation.log('Testing cardholder validation...', 'info');
    
    testCases.forEach(testCase => {
        const result = mockValidation.validateCardholder(testCase.value, true);
        const passed = result.isValid === testCase.expected;
        
        mockValidation.log(`${testCase.description}: ${passed ? 'PASS' : 'FAIL'} (${testCase.value})`, 
                          passed ? 'success' : 'error');
    });
}

function testNetworkError() {
    mockValidation.simulateError('NETWORK_ERROR', 'Network connection error. Please check your internet connection.');
}

function testTimeoutError() {
    mockValidation.simulateError('TIMEOUT_ERROR', 'Request timed out. Please try again.');
}

function testPaymentDeclined() {
    mockValidation.simulateError('PAYMENT_DECLINED', 'Payment was declined. Please try a different card.');
}

function testFraudDetection() {
    mockValidation.simulateError('FRAUD_DETECTED', 'Transaction flagged for security review.');
}

function testFormSubmission() {
    mockValidation.log('Testing form submission...', 'info');
    
    if (mockValidation.validationState.isValid) {
        mockValidation.log('Form is valid - submission would proceed', 'success');
    } else {
        mockValidation.log('Form is invalid - submission blocked', 'error');
        mockValidation.log(`Errors: ${Object.keys(mockValidation.validationState.errors).join(', ')}`, 'warning');
    }
}
</script>

<?php get_footer(); ?>
