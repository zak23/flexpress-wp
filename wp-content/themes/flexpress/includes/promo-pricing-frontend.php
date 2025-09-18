<?php
/**
 * Promo Codes Frontend Integration for Pricing Pages
 * 
 * Handles promo code display and application on pricing/join pages
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add promo code input to pricing page
 */
function flexpress_add_promo_code_to_pricing() {
    if (!is_page_template('page-templates/join.php') && !is_page_template('page-templates/pricing.php')) {
        return;
    }
    
    ?>
    <div class="promo-code-section" id="pricing-promo-section">
        <div class="promo-code-input">
            <label for="pricing-promo-code"><?php esc_html_e('Have a promo code?', 'flexpress'); ?></label>
            <div class="promo-code-field">
                <input type="text" id="pricing-promo-code" name="promo_code" 
                       placeholder="<?php esc_attr_e('Enter promo code', 'flexpress'); ?>">
                <button type="button" id="apply-pricing-promo" class="btn btn-outline-primary">
                    <?php esc_html_e('Apply', 'flexpress'); ?>
                </button>
            </div>
            <div id="pricing-promo-message" class="promo-code-message"></div>
        </div>
    </div>
    
    <style>
    .promo-code-section {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
    }
    
    .promo-code-input label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #495057;
    }
    
    .promo-code-field {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .promo-code-field input {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 16px;
    }
    
    .promo-code-field button {
        padding: 10px 20px;
        background: #007cba;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }
    
    .promo-code-field button:hover {
        background: #005a87;
    }
    
    .promo-code-message {
        margin-top: 10px;
        padding: 10px;
        border-radius: 4px;
        display: none;
    }
    
    .promo-code-message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .promo-code-message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .pricing-plan-card.promo-applied {
        border: 2px solid #28a745;
        background: #f8fff9;
    }
    
    .pricing-plan-card.promo-applied .plan-price {
        color: #28a745;
    }
    
    .pricing-plan-card.promo-applied .original-price {
        text-decoration: line-through;
        color: #6c757d;
        font-size: 0.9em;
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const promoInput = document.getElementById('pricing-promo-code');
        const applyBtn = document.getElementById('apply-pricing-promo');
        const messageDiv = document.getElementById('pricing-promo-message');
        const pricingCards = document.querySelectorAll('.pricing-plan-card');
        
        let currentPromoCode = '';
        let originalPrices = {};
        
        // Store original prices
        pricingCards.forEach(card => {
            const planId = card.dataset.planId;
            const priceElement = card.querySelector('.plan-price');
            if (planId && priceElement) {
                originalPrices[planId] = parseFloat(priceElement.textContent.replace(/[^0-9.]/g, ''));
            }
        });
        
        applyBtn.addEventListener('click', function() {
            const promoCode = promoInput.value.trim();
            
            if (!promoCode) {
                showMessage('Please enter a promo code', 'error');
                return;
            }
            
            applyPromoCode(promoCode);
        });
        
        function applyPromoCode(promoCode) {
            // Show loading state
            applyBtn.disabled = true;
            applyBtn.textContent = 'Applying...';
            
            // Get all plan IDs
            const planIds = Array.from(pricingCards).map(card => card.dataset.planId).filter(id => id);
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'validate_pricing_promo_code',
                    promo_code: promoCode,
                    plan_ids: JSON.stringify(planIds)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentPromoCode = promoCode;
                    updatePricingDisplay(data.data);
                    showMessage(data.data.message, 'success');
                } else {
                    showMessage(data.data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                applyBtn.disabled = false;
                applyBtn.textContent = 'Apply';
            });
        }
        
        function updatePricingDisplay(promoData) {
            pricingCards.forEach(card => {
                const planId = card.dataset.planId;
                if (!planId) return;
                
                // Check if this plan is affected by the promo
                const planDiscount = promoData.plan_discounts[planId];
                
                if (planDiscount && planDiscount.discount_amount > 0) {
                    // Apply discount to this plan
                    card.classList.add('promo-applied');
                    
                    const priceElement = card.querySelector('.plan-price');
                    if (priceElement) {
                        const originalPrice = originalPrices[planId];
                        const newPrice = planDiscount.final_amount;
                        
                        // Add original price display
                        if (!priceElement.querySelector('.original-price')) {
                            const originalPriceSpan = document.createElement('span');
                            originalPriceSpan.className = 'original-price';
                            originalPriceSpan.textContent = '$' + originalPrice.toFixed(2);
                            priceElement.insertBefore(originalPriceSpan, priceElement.firstChild);
                            priceElement.insertBefore(document.createTextNode(' '), priceElement.firstChild);
                        }
                        
                        // Update current price
                        const currentPriceSpan = priceElement.querySelector('.current-price') || priceElement;
                        if (currentPriceSpan.className === 'plan-price') {
                            currentPriceSpan.className = 'plan-price current-price';
                        }
                        currentPriceSpan.textContent = '$' + newPrice.toFixed(2);
                    }
                    
                    // Add discount badge
                    if (!card.querySelector('.discount-badge')) {
                        const discountBadge = document.createElement('div');
                        discountBadge.className = 'discount-badge';
                        discountBadge.textContent = 'Save $' + planDiscount.discount_amount.toFixed(2);
                        card.appendChild(discountBadge);
                    }
                } else if (promoData.unlocked_plans && promoData.unlocked_plans.includes(planId)) {
                    // Plan is unlocked by promo code
                    card.classList.add('promo-unlocked');
                    card.style.display = 'block'; // Show if it was hidden
                }
            });
        }
        
        function showMessage(message, type) {
            messageDiv.textContent = message;
            messageDiv.className = 'promo-code-message ' + type;
            messageDiv.style.display = 'block';
            
            // Hide message after 5 seconds
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
        
        // Add remove promo functionality
        const removePromoBtn = document.createElement('button');
        removePromoBtn.textContent = 'Remove Promo';
        removePromoBtn.className = 'btn btn-outline-secondary';
        removePromoBtn.style.display = 'none';
        removePromoBtn.addEventListener('click', function() {
            removePromoCode();
        });
        
        applyBtn.parentNode.appendChild(removePromoBtn);
        
        function removePromoCode() {
            currentPromoCode = '';
            
            // Reset all pricing cards
            pricingCards.forEach(card => {
                card.classList.remove('promo-applied', 'promo-unlocked');
                
                const priceElement = card.querySelector('.plan-price');
                if (priceElement) {
                    const planId = card.dataset.planId;
                    const originalPrice = originalPrices[planId];
                    
                    // Remove original price display
                    const originalPriceSpan = priceElement.querySelector('.original-price');
                    if (originalPriceSpan) {
                        originalPriceSpan.remove();
                    }
                    
                    // Reset price
                    priceElement.textContent = '$' + originalPrice.toFixed(2);
                    priceElement.className = 'plan-price';
                }
                
                // Remove discount badge
                const discountBadge = card.querySelector('.discount-badge');
                if (discountBadge) {
                    discountBadge.remove();
                }
            });
            
            showMessage('Promo code removed', 'success');
            removePromoBtn.style.display = 'none';
        }
        
        // Show remove button when promo is applied
        function showRemoveButton() {
            removePromoBtn.style.display = 'inline-block';
        }
        
        // Update the applyPromoCode function to show remove button
        const originalApplyPromoCode = applyPromoCode;
        applyPromoCode = function(promoCode) {
            originalApplyPromoCode(promoCode);
            if (currentPromoCode) {
                showRemoveButton();
            }
        };
    });
    </script>
    <?php
}
// Disabled - promo code section is now integrated directly into templates
// add_action('wp_footer', 'flexpress_add_promo_code_to_pricing');

/**
 * AJAX handler for validating promo codes on pricing page
 */
function flexpress_ajax_validate_pricing_promo_code() {
    check_ajax_referer('flexpress_promo_nonce', 'nonce');
    
    $promo_code = sanitize_text_field($_POST['promo_code']);
    $plan_ids = json_decode(stripslashes($_POST['plan_ids']), true);
    
    if (empty($promo_code) || empty($plan_ids)) {
        wp_send_json_error(array('message' => 'Invalid request'));
    }
    
    $plan_discounts = array();
    $unlocked_plans = array();
    
    // Validate promo code for each plan
    foreach ($plan_ids as $plan_id) {
        $plan = flexpress_get_pricing_plan($plan_id);
        if (!$plan) continue;
        
        $validation = flexpress_validate_enhanced_promo_code($promo_code, $plan_id, $plan['price']);
        
        if ($validation['success']) {
            if ($validation['code_type'] === 'centralized') {
                $plan_discounts[$plan_id] = array(
                    'discount_amount' => $validation['discount_amount'],
                    'final_amount' => $validation['final_amount'],
                    'original_amount' => $plan['price']
                );
            } elseif ($validation['code_type'] === 'legacy') {
                $unlocked_plans = array_merge($unlocked_plans, $validation['unlocked_plans']);
            }
        }
    }
    
    if (empty($plan_discounts) && empty($unlocked_plans)) {
        wp_send_json_error(array('message' => 'Invalid promo code'));
    }
    
    wp_send_json_success(array(
        'message' => 'Promo code applied successfully',
        'plan_discounts' => $plan_discounts,
        'unlocked_plans' => $unlocked_plans
    ));
}
add_action('wp_ajax_validate_pricing_promo_code', 'flexpress_ajax_validate_pricing_promo_code');
add_action('wp_ajax_nopriv_validate_pricing_promo_code', 'flexpress_ajax_validate_pricing_promo_code');
