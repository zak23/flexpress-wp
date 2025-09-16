<?php
/**
 * Template Name: Join Page (Flowguard)
 * Description: A modern membership signup page using Flowguard payment processing
 */

get_header();

// Check for promo code in URL (supports both /join/code and /join?promo=code)
$promo_code = get_query_var('promo');
if (empty($promo_code)) {
    $promo_code = isset($_GET['promo']) ? sanitize_text_field($_GET['promo']) : '';
}
$promo_code = sanitize_text_field($promo_code);

// Check user login and membership status
$is_logged_in = is_user_logged_in();
$current_user = null;
$membership_status = 'none';
$is_renewal_flow = false;

if ($is_logged_in) {
    $current_user = wp_get_current_user();
    $membership_status = flexpress_get_membership_status();
    
    // Redirect active members away from join page
    if ($membership_status === 'active') {
        wp_redirect(home_url('/dashboard/'));
        exit;
    }
    
    // Enable renewal flow for expired/cancelled members
    if (in_array($membership_status, ['expired', 'cancelled'])) {
        $is_renewal_flow = true;
    }
}

// Get pricing plans (include promo code to unlock promo-only plans)
$pricing_plans = flexpress_get_pricing_plans(true, $promo_code);
$featured_plan = flexpress_get_featured_pricing_plan();

// Check payment status
$payment_status = isset($_GET['payment']) ? sanitize_text_field($_GET['payment']) : '';
$registered = isset($_GET['registered']) ? sanitize_text_field($_GET['registered']) : '';
?>

<main id="primary" class="site-main join-page-flowguard py-5">
    <div class="container">
        <!-- Header Section -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h1 class="join-title mb-3">
                    <?php if ($is_renewal_flow): ?>
                        Renew Your Membership
                    <?php else: ?>
                        Join <?php echo get_bloginfo('name'); ?>
                    <?php endif; ?>
                </h1>
                <p class="join-subtitle lead">
                    <?php if ($is_renewal_flow): ?>
                        Welcome back! Choose a plan to reactivate your membership.
                    <?php else: ?>
                        Get unlimited access to exclusive content with our premium membership.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Promo Code Section -->
        <?php if (!$is_logged_in): ?>
        <div class="row justify-content-center mb-4">
            <div class="col-lg-6">
                <div class="promo-code-section">
                    <h3 class="text-center mb-3">Have a Promo Code?</h3>
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               id="promo-code-input" 
                               placeholder="Enter promo code"
                               value="<?php echo esc_attr($promo_code); ?>">
                        <button class="btn btn-primary" 
                                type="button" 
                                id="apply-promo-btn">
                            Apply Code
                        </button>
                    </div>
                    <div id="promo-feedback" class="mt-2"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Pricing Plans Section -->
        <div class="row justify-content-center">
            <?php if (!empty($pricing_plans)): ?>
                <?php foreach ($pricing_plans as $plan_id => $plan): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="pricing-card <?php echo $plan['featured'] ? 'featured' : ''; ?>" 
                             data-plan-id="<?php echo esc_attr($plan_id); ?>">
                            
                            <?php if ($plan['featured']): ?>
                                <div class="featured-badge">Most Popular</div>
                            <?php endif; ?>
                            
                            <div class="card-header text-center">
                                <h3 class="plan-name"><?php echo esc_html($plan['name']); ?></h3>
                                <div class="plan-price">
                                    <span class="currency"><?php echo esc_html($plan['currency']); ?></span>
                                    <span class="amount"><?php echo esc_html($plan['price']); ?></span>
                                    <?php if ($plan['plan_type'] === 'recurring'): ?>
                                        <span class="period">/<?php echo esc_html($plan['duration_unit']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($plan['description'])): ?>
                                    <p class="plan-description"><?php echo esc_html($plan['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body">
                                <?php if (!empty($plan['features'])): ?>
                                    <ul class="plan-features">
                                        <?php foreach ($plan['features'] as $feature): ?>
                                            <li>
                                                <i class="fas fa-check text-success me-2"></i>
                                                <?php echo esc_html($feature); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                
                                <?php if (!empty($plan['trial_enabled'])): ?>
                                    <div class="trial-info">
                                        <small class="text-muted">
                                            <i class="fas fa-gift me-1"></i>
                                            <?php echo esc_html($plan['trial_duration']); ?> 
                                            <?php echo esc_html($plan['trial_duration_unit']); ?> 
                                            trial for <?php echo esc_html($plan['currency'] . $plan['trial_price']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-footer">
                                <button class="btn btn-primary w-100 select-plan-btn" 
                                        data-plan-id="<?php echo esc_attr($plan_id); ?>"
                                        data-plan-name="<?php echo esc_attr($plan['name']); ?>"
                                        data-plan-price="<?php echo esc_attr($plan['price']); ?>"
                                        data-plan-currency="<?php echo esc_attr($plan['currency']); ?>">
                                    <?php if ($is_renewal_flow): ?>
                                        Renew Membership
                                    <?php else: ?>
                                        Choose Plan
                                    <?php endif; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <div class="alert alert-warning">
                        <h4>No Plans Available</h4>
                        <p>Please contact support for assistance.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Payment Security Info -->
        <div class="row justify-content-center mt-5">
            <div class="col-lg-8 text-center">
                <div class="security-info">
                    <h4 class="mb-3">Secure Payment Processing</h4>
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
                    <p class="text-muted mt-3">
                        Your payment information is processed securely by Flowguard and never stored on our servers.
                    </p>
                </div>
            </div>
        </div>

        <!-- Registration Success Message -->
        <?php if ($registered === '1'): ?>
            <div class="row justify-content-center mt-4">
                <div class="col-lg-6">
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4>Welcome!</h4>
                        <p>Your account has been created successfully. Now choose a plan to get started!</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Payment Status Messages -->
        <?php if ($payment_status === 'success'): ?>
            <div class="row justify-content-center mt-4">
                <div class="col-lg-6">
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4>Payment Successful!</h4>
                        <p>Your membership has been activated. Welcome aboard!</p>
                        <a href="<?php echo home_url('/dashboard'); ?>" class="btn btn-success">
                            Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif ($payment_status === 'declined'): ?>
            <div class="row justify-content-center mt-4">
                <div class="col-lg-6">
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-times-circle fa-2x mb-2"></i>
                        <h4>Payment Declined</h4>
                        <p>We're sorry, but your payment could not be processed. Please try again.</p>
                        <a href="<?php echo home_url('/contact'); ?>" class="btn btn-outline-primary">
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.join-page-flowguard {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    min-height: 100vh;
}

.join-title {
    color: #ffffff;
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.join-subtitle {
    color: #b0b0b0;
    font-size: 1.2rem;
}

.pricing-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.pricing-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
}

.pricing-card.featured {
    border-color: #ff6b6b;
    box-shadow: 0 20px 40px rgba(255, 107, 107, 0.2);
}

.featured-badge {
    position: absolute;
    top: -1px;
    right: -1px;
    background: #ff6b6b;
    color: #ffffff;
    padding: 0.5rem 1rem;
    border-radius: 0 16px 0 16px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.card-header {
    background: rgba(255, 255, 255, 0.03);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding: 2rem 1.5rem 1rem;
}

.plan-name {
    color: #ffffff;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.plan-price {
    color: #ff6b6b;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.plan-price .currency {
    font-size: 1.5rem;
    vertical-align: top;
}

.plan-price .period {
    font-size: 1rem;
    color: #b0b0b0;
    font-weight: 400;
}

.plan-description {
    color: #b0b0b0;
    margin-bottom: 0;
}

.card-body {
    padding: 1.5rem;
    color: #b0b0b0;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0;
}

.plan-features li {
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.plan-features li:last-child {
    border-bottom: none;
}

.trial-info {
    background: rgba(255, 107, 107, 0.1);
    border: 1px solid rgba(255, 107, 107, 0.2);
    border-radius: 8px;
    padding: 0.75rem;
    margin-top: 1rem;
    text-align: center;
}

.card-footer {
    background: rgba(255, 255, 255, 0.03);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    padding: 1.5rem;
    border-radius: 0 0 16px 16px;
}

.btn-primary {
    background: #ff6b6b;
    border-color: #ff6b6b;
    border-radius: 8px;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #ff5252;
    border-color: #ff5252;
    transform: translateY(-2px);
}

.promo-code-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.security-info {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.security-badges .badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.alert {
    border-radius: 12px;
    border: none;
    padding: 2rem;
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

@media (max-width: 768px) {
    .join-title {
        font-size: 2rem;
    }
    
    .pricing-card {
        margin-bottom: 2rem;
    }
    
    .plan-price {
        font-size: 2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Promo code handling
    const promoInput = document.getElementById('promo-code-input');
    const applyPromoBtn = document.getElementById('apply-promo-btn');
    const promoFeedback = document.getElementById('promo-feedback');
    
    if (applyPromoBtn) {
        applyPromoBtn.addEventListener('click', function() {
            const promoCode = promoInput.value.trim();
            if (!promoCode) {
                promoFeedback.innerHTML = '<div class="alert alert-warning">Please enter a promo code</div>';
                return;
            }
            
            applyPromoBtn.disabled = true;
            applyPromoBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
            
            // Apply promo code via AJAX
            fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'apply_promo_code',
                    promo_code: promoCode,
                    nonce: window.flowguardConfig.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    promoFeedback.innerHTML = '<div class="alert alert-success">Promo code applied successfully!</div>';
                    // Reload page to show updated plans
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    promoFeedback.innerHTML = '<div class="alert alert-danger">' + data.data + '</div>';
                }
            })
            .catch(error => {
                promoFeedback.innerHTML = '<div class="alert alert-danger">Error applying promo code</div>';
            })
            .finally(() => {
                applyPromoBtn.disabled = false;
                applyPromoBtn.innerHTML = 'Apply Code';
            });
        });
    }
    
    // Plan selection handling
    const selectPlanBtns = document.querySelectorAll('.select-plan-btn');
    
    selectPlanBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const planId = this.dataset.planId;
            const planName = this.dataset.planName;
            const planPrice = this.dataset.planPrice;
            const planCurrency = this.dataset.planCurrency;
            
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Create Flowguard subscription
            fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'create_flowguard_subscription',
                    plan_id: planId,
                    nonce: window.flowguardConfig.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to Flowguard payment page
                    window.location.href = data.data.payment_url;
                } else {
                    // Show error message
                    alert('Error: ' + data.data);
                    this.disabled = false;
                    this.innerHTML = '<?php echo $is_renewal_flow ? "Renew Membership" : "Choose Plan"; ?>';
                }
            })
            .catch(error => {
                alert('Error creating subscription: ' + error.message);
                this.disabled = false;
                this.innerHTML = '<?php echo $is_renewal_flow ? "Renew Membership" : "Choose Plan"; ?>';
            });
        });
    });
    
    // Auto-submit promo code on Enter
    if (promoInput) {
        promoInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyPromoBtn.click();
            }
        });
    }
});
</script>

<?php get_footer(); ?>
