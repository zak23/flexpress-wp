/**
 * Join Page JavaScript
 * Handles both new registrations and renewals
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get global variables from PHP
    const pricingPlansData = window.pricingPlansData || {};
    let currentPromoCode = window.currentPromoCode || '';
    const isRenewalFlow = window.isRenewalFlow || false;
    const ajaxUrl = window.ajaxUrl || '/wp-admin/admin-ajax.php';
    const nonce = window.joinNonce || '';
    
    // Initialize elements
    const promoToggle = document.getElementById('promo-toggle');
    const promoContainer = document.querySelector('.promo-form-container');
    const promoForm = document.getElementById('promo-form');
    const promoInput = document.getElementById('promo_code');
    const promoMessage = document.getElementById('promo-message');
    const appliedPromoInput = document.getElementById('applied_promo_code');
    const planTypeRadios = document.querySelectorAll('input[name="plan_type"]');
    const recurringPlans = document.getElementById('recurring-plans');
    const onetimePlans = document.getElementById('onetime-plans');
    const form = document.getElementById('flexpress-register-form');
    const submitBtn = document.getElementById('join-submit-btn');
    
    // Auto-apply promo code if present in URL
    if (currentPromoCode && currentPromoCode.trim() !== '') {
        setTimeout(function() {
            applyPromoCode(currentPromoCode);
        }, 100);
    }
    
    // Promo toggle functionality
    if (promoToggle) {
        promoToggle.addEventListener('click', function() {
            const isVisible = promoContainer.style.display !== 'none';
            promoContainer.style.display = isVisible ? 'none' : 'block';
            if (!isVisible) {
                promoInput.focus();
            }
        });
    }
    
    // Promo code application
    if (promoForm) {
        promoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const code = promoInput.value.trim();
            
            if (!code) {
                showPromoMessage('Please enter a promo code', 'danger');
                return;
            }
            
            applyPromoCode(code);
        });
    }
    
    // Plan type switching
    planTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'recurring') {
                recurringPlans.style.display = 'block';
                onetimePlans.style.display = 'none';
            } else {
                recurringPlans.style.display = 'none';
                onetimePlans.style.display = 'block';
            }
        });
    });
    
    // Plan selection handling
    document.addEventListener('click', function(e) {
        const planCard = e.target.closest('.plan-card');
        if (planCard) {
            const radio = planCard.querySelector('input[type="radio"]');
            if (radio && !radio.checked) {
                radio.checked = true;
                updatePlanSelection();
            }
        }
    });
    
    document.addEventListener('change', function(e) {
        if (e.target.name === 'selected_plan') {
            updatePlanSelection();
        }
    });
    
    // Form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const selectedPlan = document.querySelector('input[name="selected_plan"]:checked');
            if (!selectedPlan) {
                showFormError('Please select a pricing plan');
                return;
            }
            
            // Validate form
            if (!validateForm()) {
                return;
            }
            
            // Submit registration/renewal and payment
            submitForm();
        });
    }
    
    // Helper functions
    function showPromoMessage(message, type) {
        if (promoMessage) {
            promoMessage.textContent = message;
            promoMessage.className = `alert alert-${type}`;
            promoMessage.style.display = 'block';
            
            if (type === 'success') {
                setTimeout(() => {
                    promoMessage.style.display = 'none';
                }, 3000);
            }
        }
    }
    
    function applyPromoCode(code) {
        if (!code || !code.trim()) {
            return;
        }
        
        const trimmedCode = code.trim().toLowerCase();
        
        // Update UI
        if (promoInput) {
            promoInput.value = trimmedCode;
        }
        
        // Show promo form if hidden
        if (promoContainer && promoContainer.style.display === 'none') {
            promoContainer.style.display = 'block';
        }
        
        // AJAX call to validate promo code
        const formData = new FormData();
        formData.append('action', 'flexpress_validate_promo_code');
        formData.append('promo_code', trimmedCode);
        formData.append('nonce', nonce);
        
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentPromoCode = trimmedCode;
                appliedPromoInput.value = trimmedCode;
                showPromoMessage(data.data.message, 'success');
                updatePlanVisibility(data.data.unlocked_plans);
                
                // Update URL without page reload
                const newUrl = window.location.origin + '/join/' + trimmedCode;
                window.history.replaceState({}, '', newUrl);
            } else {
                showPromoMessage(data.data.message, 'danger');
                currentPromoCode = '';
                appliedPromoInput.value = '';
                updatePlanVisibility([]);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showPromoMessage('Error validating promo code', 'danger');
        });
    }
    
    function updatePlanVisibility(unlockedPlans = []) {
        // Clear existing promo plans
        document.querySelectorAll('.promo-plan-added').forEach(el => el.remove());
        
        // Hide any existing promotional plans that might be in the regular list
        document.querySelectorAll('.plan-card[data-promo-only="true"]').forEach(el => {
            if (!el.classList.contains('promo-plan-added')) {
                el.style.display = 'none';
            }
        });

        if (unlockedPlans.length === 0) {
            return;
        }

        unlockedPlans.forEach(planId => {
            // Check if this plan is already visible in the regular list
            const existingPlan = document.querySelector(`input[value="${planId}"]`);
            if (existingPlan) {
                const planCard = existingPlan.closest('.plan-card');
                if (planCard) {
                    planCard.style.display = 'block';
                    planCard.classList.add('promo-visible');
                    return; // Don't fetch it again
                }
            }
            
            // Fetch plan card HTML for plans not in the regular list
            const formData = new FormData();
            formData.append('action', 'flexpress_get_plan_card');
            formData.append('nonce', nonce);
            formData.append('plan_id', planId);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.html) {
                    const planContainer = document.getElementById('recurring-plans');
                    if (planContainer) {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = data.data.html;
                        // Get the actual plan card element (skip text nodes)
                        let planCard = null;
                        for (let i = 0; i < tempDiv.childNodes.length; i++) {
                            if (tempDiv.childNodes[i].nodeType === 1) { // Element node
                                planCard = tempDiv.childNodes[i];
                                break;
                            }
                        }
                        if (planCard) {
                            planCard.classList.add('promo-plan-added');
                            planCard.classList.add('promo-visible');
                            planContainer.appendChild(planCard);
                        }
                    }
                }
            });
        });
    }
    
    function updatePlanSelection() {
        const selectedRadio = document.querySelector('input[name="selected_plan"]:checked');
        const planCards = document.querySelectorAll('.plan-card');
        
        planCards.forEach(card => {
            card.classList.remove('selected');
        });
        
        if (selectedRadio) {
            const selectedCard = selectedRadio.closest('.plan-card');
            if (selectedCard) {
                selectedCard.classList.add('selected');
                
                // Update submit button
                const planData = pricingPlansData[selectedRadio.value];
                if (planData && submitBtn) {
                    const submitText = submitBtn.querySelector('.submit-text');
                    const price = planData.trial_enabled ? planData.trial_price : planData.price;
                    if (submitText) {
                        const actionText = isRenewalFlow ? 'RENEW NOW' : 'JOIN NOW';
                        submitText.textContent = `${actionText} & PAY ${planData.currency}${parseFloat(price).toFixed(2)}`;
                    }
                }
            }
        }
    }
    
    function validateForm() {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim() || (field.type === 'checkbox' && !field.checked)) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Check password match (only for new registrations)
        if (!isRenewalFlow) {
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirm');
            if (password && passwordConfirm && password.value !== passwordConfirm.value) {
                passwordConfirm.classList.add('is-invalid');
                showFormError('Passwords do not match');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    function showFormError(message) {
        const errorDiv = document.getElementById('registration-error');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('d-none');
        }
    }
    
    function submitForm() {
        if (!submitBtn) return;
        
        // Show loading state
        submitBtn.disabled = true;
        const submitText = submitBtn.querySelector('.submit-text');
        const loadingText = submitBtn.querySelector('.loading-text');
        if (submitText) submitText.classList.add('d-none');
        if (loadingText) loadingText.classList.remove('d-none');
        
        // Submit to server
        const formData = new FormData(form);
        const actionName = isRenewalFlow ? 'flexpress_process_renewal_and_payment' : 'flexpress_process_registration_and_payment';
        formData.append('action', actionName);
        formData.append('nonce', nonce);
        
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.payment_url) {
                window.location.href = data.data.payment_url;
            } else {
                showFormError(data.data.message || 'An error occurred. Please try again.');
                resetSubmitButton();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFormError('An error occurred. Please try again.');
            resetSubmitButton();
        });
    }
    
    function resetSubmitButton() {
        if (!submitBtn) return;
        
        submitBtn.disabled = false;
        const submitText = submitBtn.querySelector('.submit-text');
        const loadingText = submitBtn.querySelector('.loading-text');
        if (submitText) submitText.classList.remove('d-none');
        if (loadingText) loadingText.classList.add('d-none');
    }
    
    // Initialize promo code if set in URL
    if (currentPromoCode && promoForm) {
        promoForm.dispatchEvent(new Event('submit'));
    }
}); 