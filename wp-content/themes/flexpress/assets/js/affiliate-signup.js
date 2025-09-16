/**
 * FlexPress Affiliate Signup Form Validation
 * 
 * Provides real-time form validation, affiliate code availability checking,
 * and enhanced user experience for the affiliate registration process.
 */

document.addEventListener('DOMContentLoaded', function() {
    const affiliateForm = document.getElementById('affiliate-signup-form');
    const affiliateCodeInput = document.getElementById('affiliate_code');
    const displayNameInput = document.getElementById('display_name');
    const emailInput = document.getElementById('email');
    const referralUrlInput = document.getElementById('referral_url');
    const generateCodeBtn = document.getElementById('generate-code-btn');
    const submitBtn = document.querySelector('button[name="submit_affiliate_application"]');
    
    if (!affiliateForm) {
        return; // Exit if form not found
    }
    
    // Form validation state
    let validationState = {
        affiliateCode: false,
        displayName: false,
        email: false,
        referralUrl: true // Optional field
    };
    
    /**
     * Update submit button state based on validation
     */
    function updateSubmitButton() {
        const isValid = Object.values(validationState).every(Boolean);
        if (submitBtn) {
            submitBtn.disabled = !isValid;
            submitBtn.classList.toggle('btn-secondary', !isValid);
            submitBtn.classList.toggle('btn-primary', isValid);
        }
    }
    
    /**
     * Show validation feedback
     */
    function showValidationFeedback(input, isValid, message = '') {
        const feedbackElement = input.parentElement.querySelector('.invalid-feedback') || 
                               input.parentElement.querySelector('.valid-feedback');
        
        // Remove existing feedback classes
        input.classList.remove('is-valid', 'is-invalid');
        
        if (isValid) {
            input.classList.add('is-valid');
            if (feedbackElement) {
                feedbackElement.textContent = message || 'Looks good!';
                feedbackElement.className = 'valid-feedback';
            }
        } else {
            input.classList.add('is-invalid');
            if (feedbackElement) {
                feedbackElement.textContent = message;
                feedbackElement.className = 'invalid-feedback';
            }
        }
    }
    
    /**
     * Validate affiliate code format and availability
     */
    async function validateAffiliateCode(code) {
        // Format validation
        if (!code || code.length < 3) {
            return { valid: false, message: 'Code must be at least 3 characters long' };
        }
        
        if (code.length > 20) {
            return { valid: false, message: 'Code must be 20 characters or less' };
        }
        
        if (!/^[a-zA-Z0-9-]+$/.test(code)) {
            return { valid: false, message: 'Code can only contain letters, numbers, and hyphens' };
        }
        
        // Check availability via AJAX
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'check_affiliate_code_availability',
                    code: code,
                    nonce: affiliateNonce || ''
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                return { valid: true, message: 'Code is available!' };
            } else {
                return { valid: false, message: data.data.message || 'Code is not available' };
            }
        } catch (error) {
            console.warn('Error checking code availability:', error);
            return { valid: true, message: 'Unable to verify availability (will check on submit)' };
        }
    }
    
    /**
     * Validate display name
     */
    function validateDisplayName(name) {
        if (!name || name.trim().length < 2) {
            return { valid: false, message: 'Display name must be at least 2 characters' };
        }
        
        if (name.trim().length > 50) {
            return { valid: false, message: 'Display name must be 50 characters or less' };
        }
        
        return { valid: true, message: 'Display name looks good!' };
    }
    
    /**
     * Validate email address
     */
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!email) {
            return { valid: false, message: 'Email address is required' };
        }
        
        if (!emailRegex.test(email)) {
            return { valid: false, message: 'Please enter a valid email address' };
        }
        
        return { valid: true, message: 'Email looks good!' };
    }
    
    /**
     * Validate referral URL
     */
    function validateReferralUrl(url) {
        if (!url) {
            return { valid: true, message: '' }; // Optional field
        }
        
        try {
            new URL(url);
            return { valid: true, message: 'URL looks good!' };
        } catch {
            return { valid: false, message: 'Please enter a valid URL (including http:// or https://)' };
        }
    }
    
    /**
     * Generate affiliate code suggestion
     */
    async function generateAffiliateCode() {
        const displayName = displayNameInput ? displayNameInput.value : '';
        
        if (!displayName) {
            alert('Please enter your display name first');
            return;
        }
        
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'generate_affiliate_code_suggestion',
                    name: displayName,
                    nonce: affiliateNonce || ''
                })
            });
            
            const data = await response.json();
            
            if (data.success && affiliateCodeInput) {
                affiliateCodeInput.value = data.data.code;
                // Trigger validation
                affiliateCodeInput.dispatchEvent(new Event('input'));
            } else {
                alert('Unable to generate code suggestion. Please create one manually.');
            }
        } catch (error) {
            console.error('Error generating code:', error);
            alert('Unable to generate code suggestion. Please create one manually.');
        }
    }
    
    // Event Listeners
    
    // Affiliate code validation with debouncing
    if (affiliateCodeInput) {
        let codeTimeout;
        affiliateCodeInput.addEventListener('input', function() {
            clearTimeout(codeTimeout);
            const code = this.value.trim();
            
            if (!code) {
                validationState.affiliateCode = false;
                showValidationFeedback(this, false, 'Affiliate code is required');
                updateSubmitButton();
                return;
            }
            
            // Debounce validation
            codeTimeout = setTimeout(async () => {
                const validation = await validateAffiliateCode(code);
                validationState.affiliateCode = validation.valid;
                showValidationFeedback(this, validation.valid, validation.message);
                updateSubmitButton();
            }, 500);
        });
    }
    
    // Display name validation
    if (displayNameInput) {
        displayNameInput.addEventListener('input', function() {
            const validation = validateDisplayName(this.value);
            validationState.displayName = validation.valid;
            showValidationFeedback(this, validation.valid, validation.message);
            updateSubmitButton();
        });
    }
    
    // Email validation
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            const validation = validateEmail(this.value);
            validationState.email = validation.valid;
            showValidationFeedback(this, validation.valid, validation.message);
            updateSubmitButton();
        });
    }
    
    // Referral URL validation
    if (referralUrlInput) {
        referralUrlInput.addEventListener('input', function() {
            const validation = validateReferralUrl(this.value);
            validationState.referralUrl = validation.valid;
            showValidationFeedback(this, validation.valid, validation.message);
            updateSubmitButton();
        });
    }
    
    // Generate code button
    if (generateCodeBtn) {
        generateCodeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            generateAffiliateCode();
        });
    }
    
    // Form submission
    if (affiliateForm) {
        affiliateForm.addEventListener('submit', function(e) {
            // Perform final validation
            const isFormValid = Object.values(validationState).every(Boolean);
            
            if (!isFormValid) {
                e.preventDefault();
                alert('Please fix all validation errors before submitting');
                return false;
            }
            
            // Show loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            }
        });
    }
    
    // Initialize validation on page load
    if (affiliateCodeInput && affiliateCodeInput.value) {
        affiliateCodeInput.dispatchEvent(new Event('input'));
    }
    if (displayNameInput && displayNameInput.value) {
        displayNameInput.dispatchEvent(new Event('input'));
    }
    if (emailInput && emailInput.value) {
        emailInput.dispatchEvent(new Event('input'));
    }
    if (referralUrlInput && referralUrlInput.value) {
        referralUrlInput.dispatchEvent(new Event('input'));
    }
    
    // Update submit button state initially
    updateSubmitButton();
}); 