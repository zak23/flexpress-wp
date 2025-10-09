<?php
/**
 * Template Name: Membership Page
 *
 * @package FlexPress
 */

get_header();

// Get current user membership status
$is_logged_in = is_user_logged_in();
$membership_status = 'none';
$next_rebill_date = '';
$subscription_type = '';

if ($is_logged_in && function_exists('flexpress_get_membership_status')) {
    $current_user_id = get_current_user_id();
    $membership_status = flexpress_get_membership_status($current_user_id);
    $next_rebill_date = get_user_meta($current_user_id, 'next_rebill_date', true);
    $subscription_type = get_user_meta($current_user_id, 'subscription_type', true);
}

// Check for promo code in URL (supports both /membership/code and /membership?promo=code)
$promo_code = get_query_var('promo');
if (empty($promo_code)) {
    $promo_code = isset($_GET['promo']) ? sanitize_text_field($_GET['promo']) : '';
}
$promo_code = sanitize_text_field($promo_code);

// Get pricing plans from FlexPress settings (include promo code to unlock promo-only plans)
$pricing_plans = flexpress_get_pricing_plans(true, $promo_code);
$featured_plan = flexpress_get_featured_pricing_plan();

// Check for error messages
$error_message = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'payment_session_needed':
            $error_message = __('Payment session creation is required. Please try again.', 'flexpress');
            break;
        case 'invalid_plan':
            $error_message = __('The selected plan is not valid. Please choose a different plan.', 'flexpress');
            break;
        case 'session_creation_failed':
            $error_message = __('Failed to create payment session. Please try again.', 'flexpress');
            break;
        case 'flowguard_not_available':
            $error_message = __('Payment system is temporarily unavailable. Please try again later.', 'flexpress');
            break;
        default:
            $error_message = __('An error occurred. Please try again.', 'flexpress');
    }
}
?>

<div class="membership-page">
    <div class="container py-5">
        <div class="row justify-content-center mb-5">
            <div class="col-md-10 text-center">
                <h1 class="display-4 mb-4"><?php esc_html_e('Premium Membership', 'flexpress'); ?></h1>
                <p class="lead mb-4"><?php esc_html_e('Unlock unlimited access to our exclusive content with a premium membership.', 'flexpress'); ?></p>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo esc_html($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($membership_status === 'active'): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php esc_html_e('You already have an active membership!', 'flexpress'); ?>
                        <?php if ($subscription_type): ?>
                            <strong><?php echo esc_html($subscription_type); ?></strong>
                        <?php endif; ?>
                        <?php if ($next_rebill_date): ?>
                            <div class="mt-2">
                                <?php esc_html_e('Next billing date:', 'flexpress'); ?> 
                                <strong>
                                    <?php 
                                    // Convert UTC timestamp to site timezone
                                    $utc_timestamp = strtotime($next_rebill_date);
                                    $site_time = $utc_timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS);
                                    echo esc_html(date_i18n(get_option('date_format'), $site_time)); 
                                    ?>
                                </strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="<?php echo esc_url(get_permalink(get_page_by_path('dashboard'))); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            <?php esc_html_e('Go to Dashboard', 'flexpress'); ?>
                        </a>
                    </div>
                <?php elseif ($membership_status === 'cancelled'): ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php esc_html_e('Your membership has been cancelled but remains active until the end of your billing period.', 'flexpress'); ?>
                        <?php if ($next_rebill_date): ?>
                            <div class="mt-2">
                                <?php esc_html_e('Access expires on:', 'flexpress'); ?> 
                                <strong>
                                    <?php 
                                    // Convert UTC timestamp to site timezone
                                    $utc_timestamp = strtotime($next_rebill_date);
                                    $site_time = $utc_timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS);
                                    echo esc_html(date_i18n(get_option('date_format'), $site_time)); 
                                    ?>
                                </strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="<?php echo esc_url(get_permalink(get_page_by_path('dashboard'))); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            <?php esc_html_e('Go to Dashboard', 'flexpress'); ?>
                        </a>
                    </div>
                <?php elseif ($membership_status === 'expired' || $membership_status === 'banned'): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-times-circle me-2"></i>
                        <?php 
                        if ($membership_status === 'expired') {
                            esc_html_e('Your membership has expired. Please renew to regain access.', 'flexpress');
                        } else {
                            esc_html_e('Your account has been suspended. Please contact support for assistance.', 'flexpress');
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php 
                // Redirect to dashboard if user has active or cancelled membership
                if ($membership_status === 'active' || $membership_status === 'cancelled') {
                    $dashboard_url = get_permalink(get_page_by_path('dashboard'));
                    if ($dashboard_url) {
                        wp_redirect($dashboard_url);
                        exit;
                    }
                }
                ?>
            </div>
        </div>

        <?php if ($membership_status !== 'active' && $membership_status !== 'cancelled'): ?>
        <!-- Promo Code Section -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-8 col-xl-6">
                <div class="promo-code-section">
                    <p class="promo-code-label">
                        <?php esc_html_e('Have a coupon? Use it here', 'flexpress'); ?>
                        <i class="fas fa-chevron-down ms-2"></i>
                    </p>
                    <div class="promo-code-input">
                        <div class="input-group">
                            <input type="text" id="membership-promo-code" class="form-control" 
                                   placeholder="<?php esc_attr_e('Enter your promo code', 'flexpress'); ?>"
                                   value="<?php echo esc_attr($promo_code); ?>">
                            <button type="button" id="apply-membership-promo" class="btn btn-primary">
                                <?php esc_html_e('Apply', 'flexpress'); ?>
                            </button>
                        </div>
                        <div id="membership-promo-message" class="promo-code-message mt-2">
                            <?php if (!empty($promo_code)): ?>
                                <div class="promo-applied-message text-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    <?php echo sprintf(esc_html__('Promo code "%s" applied!', 'flexpress'), esc_html($promo_code ?: '')); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Membership Selection Section -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-8 col-xl-6">
                <div class="membership-selection-header">
                    <h2 class="text-center mb-4"><?php esc_html_e('2. Select Deal', 'flexpress'); ?></h2>
                    
                    <!-- Plan Type Toggle -->
                    <div class="plan-type-toggle mb-4">
                        <div class="toggle-buttons">
                            <button type="button" class="toggle-btn active" data-plan-type="recurring">
                                <?php esc_html_e('Recurring', 'flexpress'); ?>
                            </button>
                            <button type="button" class="toggle-btn" data-plan-type="one_time">
                                <?php esc_html_e('One Time', 'flexpress'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <h3 class="text-center mb-4"><?php esc_html_e('Choose Join Option', 'flexpress'); ?></h3>
                </div>
                
                <div class="membership-plans-list">
                    <?php if (!empty($pricing_plans)): ?>
                        <?php 
                        // Sort plans by price (cheapest to most expensive)
                        $sorted_plans = $pricing_plans;
                        uasort($sorted_plans, function($a, $b) {
                            return $a['price'] <=> $b['price'];
                        });
                        ?>
                        <?php foreach ($sorted_plans as $plan_id => $plan): ?>
                            <?php 
                                $is_featured = $featured_plan && $featured_plan['id'] === $plan_id;
                                $is_promo_only = !empty($plan['promo_only']);
                                $plan_features = isset($plan['features']) ? $plan['features'] : array();
                                $plan_type_class = 'plan-type-' . $plan['plan_type'];
                            ?>
                            <div class="membership-plan-item <?php echo $is_featured ? 'popular-plan' : ''; ?> <?php echo $is_promo_only ? 'promo-only-plan' : ''; ?> <?php echo esc_attr($plan_type_class); ?>" 
                                 data-plan-type="<?php echo esc_attr($plan['plan_type']); ?>"
                                 data-plan-id="<?php echo esc_attr($plan_id); ?>"
                                 data-plan-price="<?php echo esc_attr($plan['price']); ?>"
                                 data-plan-currency="<?php echo esc_attr($plan['currency']); ?>"
                                 data-plan-name="<?php echo esc_attr($plan['name']); ?>"
                                 data-trial-enabled="<?php echo esc_attr($plan['trial_enabled'] ?? 0); ?>"
                                 data-trial-price="<?php echo esc_attr($plan['trial_price'] ?? 0); ?>"
                                 data-trial-duration="<?php echo esc_attr($plan['trial_duration'] ?? 0); ?>"
                                 data-trial-duration-unit="<?php echo esc_attr($plan['trial_duration_unit'] ?? 'days'); ?>"
                                 data-duration="<?php echo esc_attr($plan['duration'] ?? 30); ?>"
                                 data-duration-unit="<?php echo esc_attr($plan['duration_unit'] ?? 'days'); ?>">
                                <div class="plan-content">
                                    <div class="plan-info">
                                        <div class="plan-header">
                                            <h5 class="plan-name"><?php echo esc_html($plan['name']); ?></h5>
                                            <div class="plan-badges">
                                                <?php if ($is_featured): ?>
                                                    <span class="popular-badge"><?php esc_html_e('MOST POPULAR', 'flexpress'); ?></span>
                                                <?php elseif ($is_promo_only): ?>
                                                    <span class="promo-only-badge"><?php esc_html_e('PROMO ONLY', 'flexpress'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($plan['description'])): ?>
                                        <p class="plan-description"><?php echo esc_html($plan['description']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($plan['plan_type'] === 'recurring'): ?>
                                            <?php if (!empty($plan['trial_enabled']) && !empty($plan['trial_price']) && !empty($plan['trial_duration'])): ?>
                                                <?php
                                                // Format trial duration text
                                                $trial_duration_text = $plan['trial_duration'] . ' ' . $plan['trial_duration_unit'];
                                                if ($plan['trial_duration'] > 1 && $plan['trial_duration_unit'] === 'day') {
                                                    $trial_duration_text = $plan['trial_duration'] . ' days';
                                                } elseif ($plan['trial_duration'] > 1 && $plan['trial_duration_unit'] === 'week') {
                                                    $trial_duration_text = $plan['trial_duration'] . ' weeks';
                                                } elseif ($plan['trial_duration'] > 1 && $plan['trial_duration_unit'] === 'month') {
                                                    $trial_duration_text = $plan['trial_duration'] . ' months';
                                                }
                                                
                                                // Format billing cycle duration text
                                                $billing_duration_text = $plan['duration'] . ' ' . $plan['duration_unit'];
                                                if ($plan['duration'] > 1 && $plan['duration_unit'] === 'day') {
                                                    $billing_duration_text = $plan['duration'] . ' days';
                                                } elseif ($plan['duration'] > 1 && $plan['duration_unit'] === 'week') {
                                                    $billing_duration_text = $plan['duration'] . ' weeks';
                                                } elseif ($plan['duration'] > 1 && $plan['duration_unit'] === 'month') {
                                                    $billing_duration_text = $plan['duration'] . ' months';
                                                } elseif ($plan['duration'] > 1 && $plan['duration_unit'] === 'year') {
                                                    $billing_duration_text = $plan['duration'] . ' years';
                                                }
                                                ?>
                                                <p class="plan-billing">
                                                    <span class="trial-info">
                                                        <?php echo esc_html($trial_duration_text); ?> trial for <?php echo esc_html($plan['currency']); ?><?php echo esc_html(number_format($plan['trial_price'], 2)); ?>
                                                    </span>
                                                    <br>
                                                    <span class="then-billing">
                                                        Then <?php echo esc_html($plan['currency']); ?><?php echo esc_html(number_format($plan['price'], 2)); ?> every <?php echo esc_html($billing_duration_text); ?>
                                                    </span>
                                                </p>
                                            <?php else: ?>
                                                <p class="plan-billing"><?php esc_html_e('Recurring Charge / Billed As', 'flexpress'); ?> <?php echo esc_html($plan['currency']); ?><?php echo esc_html(number_format($plan['price'], 2)); ?></p>
                                            <?php endif; ?>
                                        <?php elseif ($plan['plan_type'] === 'one_time'): ?>
                                            <p class="plan-billing"><?php esc_html_e('One time charge Billed As', 'flexpress'); ?> <?php echo esc_html($plan['currency']); ?><?php echo esc_html(number_format($plan['price'], 2)); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="plan-pricing">
                                        <div class="price">
                                            <span class="price-amount"><?php echo esc_html(flexpress_get_daily_rate_display($plan)); ?></span>
                                            <small class="price-period">/Per Day<?php if (!empty($plan['trial_enabled']) && !empty($plan['trial_price']) && !empty($plan['trial_duration'])): ?> <span class="trial-rate-indicator">(Trial Rate)</span><?php endif; ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <p class="mb-0"><?php esc_html_e('No pricing plans are currently available. Please contact support.', 'flexpress'); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!$is_logged_in): ?>
        <!-- Registration/Login Section for Non-Logged In Users -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-8 col-xl-6">
                <div class="registration-section">
                    <h3 class="text-center mb-4"><?php esc_html_e('3. Create Your Account', 'flexpress'); ?></h3>
                    
                    <div class="auth-toggle mb-4">
                        <div class="toggle-buttons">
                            <button type="button" class="toggle-btn active" data-auth-type="register">
                                <?php esc_html_e('Register', 'flexpress'); ?>
                            </button>
                            <button type="button" class="toggle-btn" data-auth-type="login">
                                <?php esc_html_e('Login', 'flexpress'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Registration Form -->
                    <div id="register-form" class="auth-form">
                        <form id="membership-register-form" method="post">
                            <div class="mb-3">
                                <label for="reg-email" class="form-label"><?php esc_html_e('Email Address', 'flexpress'); ?></label>
                                <input type="email" class="form-control" id="reg-email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="reg-password" class="form-label"><?php esc_html_e('Password', 'flexpress'); ?></label>
                                <input type="password" class="form-control" id="reg-password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="reg-confirm-password" class="form-label"><?php esc_html_e('Confirm Password', 'flexpress'); ?></label>
                                <input type="password" class="form-control" id="reg-confirm-password" name="confirm_password" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="reg-terms" name="terms" required>
                                <label class="form-check-label" for="reg-terms">
                                    <?php esc_html_e('I agree to the', 'flexpress'); ?>
                                    <a href="<?php echo esc_url(home_url('/terms')); ?>" target="_blank" class="legal-link"><?php esc_html_e('Terms of Service', 'flexpress'); ?></a>
                                    <?php esc_html_e('and', 'flexpress'); ?>
                                    <a href="<?php echo esc_url(home_url('/privacy-policy')); ?>" target="_blank" class="legal-link"><?php esc_html_e('Privacy Policy', 'flexpress'); ?></a>
                                </label>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Login Form -->
                    <div id="login-form" class="auth-form" style="display: none;">
                        <form id="membership-login-form" method="post">
                            <div class="mb-3">
                                <label for="login-email" class="form-label"><?php esc_html_e('Email Address', 'flexpress'); ?></label>
                                <input type="email" class="form-control" id="login-email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="login-password" class="form-label"><?php esc_html_e('Password', 'flexpress'); ?></label>
                                <input type="password" class="form-control" id="login-password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <a href="<?php echo esc_url(home_url('/lost-password')); ?>" class="legal-link"><?php esc_html_e('Forgot your password?', 'flexpress'); ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Legal Text and Continue Button -->
        <div class="row justify-content-center mb-4">
            <div class="col-lg-8 col-xl-6">
                <div class="legal-continue-section">
                    <div class="legal-text mb-4">
                        <p class="legal-disclaimer" id="dynamic-legal-text">
                            <?php esc_html_e('By clicking CONTINUE, you confirm that you are at least 18 years old and agree to our', 'flexpress'); ?>
                            <a href="<?php echo esc_url(home_url('/terms')); ?>" class="legal-link"><?php esc_html_e('Terms of Service', 'flexpress'); ?></a>.
                            <span id="billing-text"><?php esc_html_e('Your subscription will automatically renew at $29.95 every 30 days unless cancelled. You may cancel at any time', 'flexpress'); ?></span>
                            <a href="<?php echo esc_url(home_url('/dashboard')); ?>" class="legal-link"><?php esc_html_e('here', 'flexpress'); ?></a>.
                        </p>
                    </div>
                    
                    <div class="continue-button-section text-center">
                        <button type="button" id="membership-continue-btn" class="btn btn-primary btn-lg continue-btn">
                            <?php esc_html_e('CONTINUE >', 'flexpress'); ?>
                        </button>
                    </div>
                    
                    <div class="trial-disclaimer mt-3" id="trial-disclaimer" style="display: none;">
                        <p class="trial-text">
                            <em id="trial-text-content"><?php esc_html_e('* Limited Access 2 day trial automatically rebilling at $34.95 every 30 days until cancelled', 'flexpress'); ?></em>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Benefits Section -->
        <div class="row mt-5 justify-content-center">
            <div class="col-12 text-center mb-5">
                <h2 class="mb-3"><?php esc_html_e('Premium Membership Benefits', 'flexpress'); ?></h2>
                <p class="lead text-muted"><?php esc_html_e('Everything you need for the ultimate viewing experience', 'flexpress'); ?></p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card bg-dark">
                    <div class="card-body p-5">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="benefit-item d-flex align-items-start">
                                    <div class="benefit-icon flex-shrink-0 me-4">
                                        <div class="icon-wrapper">
                                            <i class="fas fa-film fa-2x"></i>
                                        </div>
                                    </div>
                                    <div class="benefit-content">
                                        <h4 class="benefit-title mb-3"><?php esc_html_e('Unlimited Streaming', 'flexpress'); ?></h4>
                                        <p class="benefit-description mb-0"><?php esc_html_e('Watch as much as you want, whenever you want. No limits, no restrictions.', 'flexpress'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="benefit-item d-flex align-items-start">
                                    <div class="benefit-icon flex-shrink-0 me-4">
                                        <div class="icon-wrapper">
                                            <i class="fas fa-calendar-alt fa-2x"></i>
                                        </div>
                                    </div>
                                    <div class="benefit-content">
                                        <h4 class="benefit-title mb-3"><?php esc_html_e('New Content Weekly', 'flexpress'); ?></h4>
                                        <p class="benefit-description mb-0"><?php esc_html_e('We add new premium videos every week, so you\'ll always have something fresh to watch.', 'flexpress'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="benefit-item d-flex align-items-start">
                                    <div class="benefit-icon flex-shrink-0 me-4">
                                        <div class="icon-wrapper">
                                            <i class="fas fa-download fa-2x"></i>
                                        </div>
                                    </div>
                                    <div class="benefit-content">
                                        <h4 class="benefit-title mb-3"><?php esc_html_e('Download Videos', 'flexpress'); ?></h4>
                                        <p class="benefit-description mb-0"><?php esc_html_e('Download videos to watch offline on your devices when you\'re on the go.', 'flexpress'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="benefit-item d-flex align-items-start">
                                    <div class="benefit-icon flex-shrink-0 me-4">
                                        <div class="icon-wrapper">
                                            <i class="fas fa-mobile-alt fa-2x"></i>
                                        </div>
                                    </div>
                                    <div class="benefit-content">
                                        <h4 class="benefit-title mb-3"><?php esc_html_e('Watch Anywhere', 'flexpress'); ?></h4>
                                        <p class="benefit-description mb-0"><?php esc_html_e('Stream on your TV, computer, tablet, or mobile device with our responsive player.', 'flexpress'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="row mt-5 justify-content-center">
            <div class="col-12 text-center mb-5">
                <h2 class="mb-3"><?php esc_html_e('Frequently Asked Questions', 'flexpress'); ?></h2>
                <p class="lead text-muted"><?php esc_html_e('Got questions? We\'ve got answers', 'flexpress'); ?></p>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="accordion" id="membershipFAQ">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                <i class="fas fa-question-circle me-3"></i>
                                <?php esc_html_e('How do I cancel my subscription?', 'flexpress'); ?>
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="faqOne" data-bs-parent="#membershipFAQ">
                            <div class="accordion-body">
                                <p class="mb-0"><?php esc_html_e('You can cancel your subscription at any time from your my account page. Your membership will remain active until the end of your current billing period.', 'flexpress'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                <i class="fas fa-exchange-alt me-3"></i>
                                <?php esc_html_e('Can I switch between plans?', 'flexpress'); ?>
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#membershipFAQ">
                            <div class="accordion-body">
                                <p class="mb-0"><?php esc_html_e('Yes, you can upgrade or downgrade your plan at any time. If you upgrade, the new rate will be charged immediately. If you downgrade, the new rate will apply at your next billing cycle.', 'flexpress'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                <i class="fas fa-gift me-3"></i>
                                <?php esc_html_e('Is there a free trial?', 'flexpress'); ?>
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#membershipFAQ">
                            <div class="accordion-body">
                                <p class="mb-0"><?php esc_html_e('We occasionally offer free trial promotions for new members. Check our homepage or subscribe to our newsletter to stay informed about upcoming offers.', 'flexpress'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Fix MOST POPULAR badge to stay on one line */
.membership-page .popular-badge {
    white-space: nowrap !important;
    flex-shrink: 0 !important;
    font-size: 0.65rem !important;
}

/* Promo Code Section Styling */
.membership-page .promo-code-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.membership-page .promo-code-label {
    color: var(--color-text-muted);
    cursor: pointer;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.membership-page .promo-code-label i {
    transition: transform 0.3s ease;
}

.membership-page .promo-code-section:hover .promo-code-label i {
    transform: rotate(180deg);
}

.membership-page .promo-code-input .form-control {
    background-color: var(--color-background);
    border: 1px solid var(--color-border);
    color: var(--color-text);
    border-radius: 8px;
}

.membership-page .promo-code-input .form-control:focus {
    background-color: var(--color-background);
    border-color: var(--color-primary);
    color: var(--color-text);
    box-shadow: 0 0 0 0.2rem rgba(var(--color-primary-rgb), 0.25);
}

.membership-page .promo-code-input .form-control::placeholder {
    color: var(--color-text-muted);
}

.membership-page .promo-code-input .btn-primary {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
    border-radius: 8px;
}

.membership-page .promo-code-input .btn-primary:hover {
    background-color: var(--color-primary-hover);
    border-color: var(--color-primary-hover);
}

/* Promo Code Message Styling */
.membership-page .promo-code-message {
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.membership-page .promo-code-message.success {
    color: var(--color-success);
}

.membership-page .promo-code-message.error {
    color: var(--color-danger);
}

.membership-page .promo-code-message.info {
    color: var(--color-info);
}

/* Trial Display Styling */
.trial-info {
    color: var(--color-accent) !important;
    font-weight: bold !important;
    font-size: 0.9rem !important;
}

.then-billing {
    color: rgba(255, 255, 255, 0.8) !important;
    font-size: 0.85rem !important;
}

.trial-rate-indicator {
    color: var(--color-accent) !important;
    font-weight: bold !important;
    font-size: 0.8rem !important;
}

.plan-badges {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 0.5rem !important;
    margin-top: 0.5rem !important;
}

/* Responsive Design for Trial Elements */
@media (max-width: 768px) {
    .plan-badges {
        gap: 0.3rem !important;
    }
}
</style>

<?php
// Enqueue and localize promo code script data
wp_enqueue_script('jquery');

// Create a unique script handle for localization
$script_handle = 'flexpress-promo-script';
wp_register_script($script_handle, '', array('jquery'), '1.0', true);
wp_enqueue_script($script_handle);

wp_localize_script($script_handle, 'flexpressPromo', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('flexpress_promo_nonce')
));
?>

<script>
jQuery(document).ready(function() {
    // Check if promo code is already applied from URL
    let appliedPromo = null;
    const urlPromoCode = '<?php echo esc_js($promo_code); ?>';
    if (urlPromoCode) {
        appliedPromo = {
            code: urlPromoCode
        };
    }
    
    let selectedPlan = null;
    let currentPlanType = 'recurring';
    
    // Function to update legal text based on selected plan
    function updateLegalText(planElement) {
        const planType = planElement.data('plan-type');
        const planPrice = planElement.data('plan-price');
        const planCurrency = planElement.data('plan-currency');
        const planDuration = planElement.data('duration');
        const planDurationUnit = planElement.data('duration-unit');
        const billingTextElement = jQuery('#billing-text');
        
        // Trial information
        const trialEnabled = planElement.data('trial-enabled') == 1;
        const trialPrice = planElement.data('trial-price');
        const trialDuration = planElement.data('trial-duration');
        const trialDurationUnit = planElement.data('trial-duration-unit');
        
        let billingText = '';
        
        // Format duration text
        let durationText = planDuration + ' ' + planDurationUnit;
        if (planDuration > 1 && planDurationUnit === 'day') {
            durationText = planDuration + ' days';
        } else if (planDuration > 1 && planDurationUnit === 'week') {
            durationText = planDuration + ' weeks';
        } else if (planDuration > 1 && planDurationUnit === 'month') {
            durationText = planDuration + ' months';
        } else if (planDuration > 1 && planDurationUnit === 'year') {
            durationText = planDuration + ' years';
        }
        
        if (planType === 'recurring') {
            billingText = 'Your subscription will automatically renew at ' + planCurrency + planPrice.toFixed(2) + ' every ' + durationText + ' unless cancelled. You may cancel at any time';
        } else if (planType === 'one_time') {
            billingText = 'This is a one-time payment of ' + planCurrency + planPrice.toFixed(2) + '. No recurring charges will be applied.';
        } else if (planType === 'lifetime') {
            billingText = 'This is a one-time payment of ' + planCurrency + planPrice.toFixed(2) + ' for lifetime access. No recurring charges will be applied.';
        } else {
            // Default fallback
            billingText = 'Your subscription will automatically renew at ' + planCurrency + planPrice.toFixed(2) + ' every ' + durationText + ' unless cancelled. You may cancel at any time';
        }
        
        billingTextElement.text(billingText);
        
        // Update trial disclaimer
        updateTrialDisclaimer(trialEnabled, trialPrice, trialDuration, trialDurationUnit, planCurrency, planPrice, planDuration, planDurationUnit);
    }
    
    // Function to update trial disclaimer
    function updateTrialDisclaimer(trialEnabled, trialPrice, trialDuration, trialDurationUnit, planCurrency, planPrice, planDuration, planDurationUnit) {
        const trialDisclaimerElement = jQuery('#trial-disclaimer');
        const trialTextElement = jQuery('#trial-text-content');
        
        if (trialEnabled && trialPrice > 0 && trialDuration > 0) {
            // Format trial duration text
            let trialDurationText = trialDuration + ' ' + trialDurationUnit;
            if (trialDuration > 1 && trialDurationUnit === 'day') {
                trialDurationText = trialDuration + ' days';
            } else if (trialDuration > 1 && trialDurationUnit === 'week') {
                trialDurationText = trialDuration + ' weeks';
            } else if (trialDuration > 1 && trialDurationUnit === 'month') {
                trialDurationText = trialDuration + ' months';
            }
            
            // Format billing cycle duration text
            let billingDurationText = planDuration + ' ' + planDurationUnit;
            if (planDuration > 1 && planDurationUnit === 'day') {
                billingDurationText = planDuration + ' days';
            } else if (planDuration > 1 && planDurationUnit === 'week') {
                billingDurationText = planDuration + ' weeks';
            } else if (planDuration > 1 && planDurationUnit === 'month') {
                billingDurationText = planDuration + ' months';
            } else if (planDuration > 1 && planDurationUnit === 'year') {
                billingDurationText = planDuration + ' years';
            }
            
            const trialText = '* Limited Access ' + trialDurationText + ' trial automatically rebilling at ' + planCurrency + planPrice.toFixed(2) + ' every ' + billingDurationText + ' until cancelled';
            trialTextElement.text(trialText);
            trialDisclaimerElement.show();
        } else {
            trialDisclaimerElement.hide();
        }
    }
    
    // Plan type toggle functionality
    jQuery('.toggle-btn[data-plan-type]').on('click', function() {
        const planType = jQuery(this).data('plan-type');
        currentPlanType = planType;
        
        // Update toggle buttons
        jQuery('.toggle-btn[data-plan-type]').removeClass('active');
        jQuery(this).addClass('active');
        
        // Show/hide plans based on type
        jQuery('.membership-plan-item').hide();
        jQuery('.membership-plan-item[data-plan-type="' + planType + '"]').show();
        
        // Reset selection
        jQuery('.membership-plan-item').removeClass('selected');
        jQuery('.membership-plan-item.popular-plan').removeClass('no-highlight');
        selectedPlan = null;
    });
    
    // Auth type toggle functionality (for non-logged in users)
    jQuery('.toggle-btn[data-auth-type]').on('click', function() {
        const authType = jQuery(this).data('auth-type');
        
        // Update toggle buttons
        jQuery('.toggle-btn[data-auth-type]').removeClass('active');
        jQuery(this).addClass('active');
        
        // Show/hide forms
        if (authType === 'register') {
            jQuery('#register-form').show();
            jQuery('#login-form').hide();
        } else {
            jQuery('#register-form').hide();
            jQuery('#login-form').show();
        }
    });
    
    // Plan selection functionality
    jQuery('.membership-plan-item').on('click', function() {
        jQuery('.membership-plan-item').removeClass('selected');
        jQuery('.membership-plan-item.popular-plan').removeClass('no-highlight');
        jQuery(this).addClass('selected');
        
        // Add no-highlight class to popular plans that are not selected
        jQuery('.membership-plan-item.popular-plan:not(.selected)').addClass('no-highlight');
        
        selectedPlan = jQuery(this);
        
        // Update legal text based on selected plan
        updateLegalText(jQuery(this));
    });
    
    // Continue button functionality - attach after DOM is ready
    jQuery(document).ready(function() {
        jQuery('#membership-continue-btn').on('click', function(e) {
            e.preventDefault();
            console.log('Continue button clicked!');
            console.log('Selected plan:', selectedPlan);
            
            if (!selectedPlan) {
                alert('Please select a membership plan first.');
                return;
            }
            
            const planId = selectedPlan.data('plan-id');
            const planName = selectedPlan.find('.plan-name').text();
            
            // Get the price - check for discounted price first, then original price
            let planPrice;
            const discountedPriceElement = selectedPlan.find('.discounted-price');
            if (discountedPriceElement.length > 0) {
                planPrice = discountedPriceElement.text().replace(/[^0-9.]/g, '');
            } else {
                planPrice = selectedPlan.find('.price-amount').text().replace(/[^0-9.]/g, '');
            }
            
            console.log('Plan ID:', planId);
            console.log('Plan Name:', planName);
            console.log('Plan Price:', planPrice);
            
            // Check if user is logged in
            const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
            
            if (isLoggedIn) {
                // Logged in user - create Flowguard session via AJAX
                console.log('Creating Flowguard session for logged in user');
                
                // Disable button and show loading state
                const $continueBtn = jQuery('#membership-continue-btn');
                $continueBtn.prop('disabled', true);
                const originalText = $continueBtn.text();
                $continueBtn.html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...');
                
                // Create Flowguard payment session
                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'flexpress_create_flowguard_payment',
                        plan_id: planId,
                        promo_code: appliedPromo ? appliedPromo.code : '',
                        nonce: '<?php echo wp_create_nonce('flexpress_payment_nonce'); ?>'
                    },
                    success: function(response) {
                        console.log('AJAX Success Response:', response);
                        if (response.success && response.data.payment_url) {
                            // Redirect to payment page
                            window.location.href = response.data.payment_url;
                        } else {
                            // Show error message
                            alert('Error creating payment session: ' + (response.data.message || 'Unknown error'));
                            $continueBtn.prop('disabled', false);
                            $continueBtn.text(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', xhr, status, error);
                        alert('Network error. Please try again.');
                        $continueBtn.prop('disabled', false);
                        $continueBtn.text(originalText);
                    }
                });
            } else {
                // Non-logged in user - validate form and then register/login
                const activeForm = jQuery('.auth-form:visible');
                const isRegisterForm = activeForm.attr('id') === 'register-form';
                
                if (isRegisterForm) {
                    // Validate registration form
                    const email = jQuery('#reg-email').val();
                    const password = jQuery('#reg-password').val();
                    const confirmPassword = jQuery('#reg-confirm-password').val();
                    const termsAccepted = jQuery('#reg-terms').is(':checked');
                    
                    if (!email || !password || !confirmPassword) {
                        alert('Please fill in all fields.');
                        return;
                    }
                    
                    if (password !== confirmPassword) {
                        alert('Passwords do not match.');
                        return;
                    }
                    
                    if (!termsAccepted) {
                        alert('Please accept the terms and conditions.');
                        return;
                    }
                    
                    // TODO: Submit registration and then redirect to payment
                    alert('Registration functionality will be implemented next.');
                } else {
                    // Validate login form
                    const email = jQuery('#login-email').val();
                    const password = jQuery('#login-password').val();
                    
                    if (!email || !password) {
                        alert('Please fill in all fields.');
                        return;
                    }
                    
                    // TODO: Submit login and then redirect to payment
                    alert('Login functionality will be implemented next.');
                }
            }
        });
    });
    
    // Initialize: show recurring plans by default
    jQuery('.membership-plan-item').hide();
    jQuery('.membership-plan-item[data-plan-type="recurring"]').show();
    
    // Auto-select the most popular plan on page load
    const popularPlan = jQuery('.membership-plan-item.popular-plan:visible').first();
    if (popularPlan.length > 0) {
        // Apply the same logic as clicking a plan
        jQuery('.membership-plan-item').removeClass('selected');
        jQuery('.membership-plan-item.popular-plan').removeClass('no-highlight');
        popularPlan.addClass('selected');
        
        // Add no-highlight class to other popular plans that are not selected
        jQuery('.membership-plan-item.popular-plan:not(.selected)').addClass('no-highlight');
        
        selectedPlan = popularPlan;
        
        // Update legal text for auto-selected plan
        updateLegalText(popularPlan);
        
        console.log('Auto-selected popular plan:', selectedPlan);
    }
    
    // Debug: Check if continue button exists
    const continueBtn = jQuery('#membership-continue-btn');
    console.log('Continue button found:', continueBtn.length > 0);
    
    // Prevent Enter key from submitting promo code
    jQuery('#membership-promo-code').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            jQuery('#apply-membership-promo').click();
        }
    });
    
    // Promo code functionality
    jQuery('.promo-code-label').on('click', function() {
        jQuery('.promo-code-input').toggleClass('show');
    });
    jQuery('#apply-membership-promo').on('click', function() {
        const code = jQuery('#membership-promo-code').val().trim();
        
        if (!code) {
            showPromoMessage('Please enter a promo code', 'error');
            return;
        }
        
        // Apply promo code
        jQuery.ajax({
            url: flexpressPromo.ajaxurl,
            type: 'POST',
            data: {
                action: 'apply_promo_code',
                code: code,
                nonce: flexpressPromo.nonce
            },
            success: function(response) {
                if (response.success) {
                    appliedPromo = response.data;
                    showPromoMessage('Promo code "' + response.data.code + '" applied! You saved ' + response.data.discount_value + '% on your subscription.', 'success');
                    jQuery('#membership-promo-code').val('');
                    
                    // Reload the page to show promo-only plans and updated pricing
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('promo', response.data.code);
                    window.location.href = currentUrl.toString();
                } else {
                    showPromoMessage(response.data.message || 'Invalid promo code', 'error');
                }
            },
            error: function() {
                showPromoMessage('An error occurred. Please try again.', 'error');
            }
        });
    });
    
    // Remove promo code
    function removePromoCode() {
        jQuery.ajax({
            url: flexpressPromo.ajaxurl,
            type: 'POST',
            data: {
                action: 'remove_promo_code',
                nonce: flexpressPromo.nonce
            },
            success: function(response) {
                if (response.success) {
                    appliedPromo = null;
                    showPromoMessage('Promo code removed', 'info');
                    updatePlanPrices();
                }
            }
        });
    }
    
    function showPromoMessage(message, type) {
        const messageDiv = jQuery('#membership-promo-message');
        messageDiv.removeClass('success error info').addClass(type);
        messageDiv.html('<div class="promo-applied-message text-' + type + '"><i class="fas fa-check-circle me-1"></i>' + message + '</div>').show();
        
        setTimeout(() => {
            messageDiv.hide();
        }, 5000);
    }
    
    function updatePlanPrices() {
        console.log('updatePlanPrices called, appliedPromo:', appliedPromo);
        jQuery('.membership-card').each(function() {
            const $card = jQuery(this);
            const $price = $card.find('.price');
            const originalPrice = parseFloat($price.data('original-price') || $price.text().replace(/[^0-9.]/g, ''));
            console.log('Processing card, original price:', originalPrice);
            
            if (appliedPromo && appliedPromo.discount_type && appliedPromo.discount_value) {
                // Calculate discount based on promo code settings
                let discountAmount = 0;
                if (appliedPromo.discount_type === 'percentage') {
                    discountAmount = (originalPrice * appliedPromo.discount_value) / 100;
                } else {
                    discountAmount = appliedPromo.discount_value;
                }
                
                // Apply maximum discount limit if set
                if (appliedPromo.maximum_discount > 0 && discountAmount > appliedPromo.maximum_discount) {
                    discountAmount = appliedPromo.maximum_discount;
                }
                
                const discountedPrice = Math.max(0, originalPrice - discountAmount);
                
                // Update price display
                $price.html('<span class="original-price text-decoration-line-through text-muted me-2">$' + originalPrice.toFixed(2) + '</span><span class="discounted-price text-success fw-bold">$' + discountedPrice.toFixed(2) + '</span>');
                
                // Add discount indicator
                if (!$card.find('.discount-indicator').length) {
                    const discountText = appliedPromo.discount_type === 'percentage' 
                        ? appliedPromo.discount_value + '% OFF' 
                        : 'Save $' + discountAmount.toFixed(2);
                    $price.after('<small class="discount-indicator text-success d-block fw-bold">' + discountText + '</small>');
                }
            } else {
                $price.text('$' + originalPrice.toFixed(2));
                $price.removeClass('text-success');
                $card.find('.discount-indicator').remove();
            }
        });
    }
    
    // Store original prices
    jQuery('.membership-plan-item .price-amount').each(function() {
        const originalPrice = parseFloat(jQuery(this).text().replace(/[^0-9.]/g, ''));
        jQuery(this).data('original-price', originalPrice);
    });
    
    // Plan selection with promo code
    jQuery('.flexpress-select-plan').on('click', function() {
        const $button = jQuery(this);
        const planId = $button.data('plan-id');
        const planName = $button.data('plan-name');
        const planPrice = $button.data('plan-price');
        const planCurrency = $button.data('plan-currency');
        
        // Disable button and show loading state
        $button.prop('disabled', true);
        const originalText = $button.text();
        $button.html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...');
        
        // Create Flowguard payment session with promo code
        console.log('Making AJAX call for plan:', planId);
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'flexpress_create_flowguard_payment',
                plan_id: planId,
                promo_code: appliedPromo ? appliedPromo.code : '',
                nonce: '<?php echo wp_create_nonce('flexpress_payment_nonce'); ?>'
            },
            success: function(response) {
                console.log('AJAX Success Response:', response);
                if (response.success && response.data.payment_url) {
                    // Redirect to payment page
                    window.location.href = response.data.payment_url;
                } else {
                    // Check if we need to redirect to login
                    if (response.data && response.data.redirect) {
                        window.location.href = response.data.redirect;
                        return;
                    }
                    // Show error message
                    alert('Error creating payment session: ' + (response.data.message || 'Unknown error'));
                    $button.prop('disabled', false);
                    $button.text(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr, status, error);
                alert('Network error. Please try again.');
                $button.prop('disabled', false);
                $button.text(originalText);
            }
        });
    });
});
</script>

<?php get_footer(); ?> 
<?php get_footer(); ?> 