<?php
/**
 * Template Name: Join Page
 * Description: A template for the membership signup page
 */

get_header();

// Check for promo code in URL (supports both /join/code and /join?promo=code)
$promo_code = get_query_var('promo');
if (empty($promo_code)) {
    $promo_code = isset($_GET['promo']) ? sanitize_text_field($_GET['promo']) : '';
}
$promo_code = sanitize_text_field($promo_code);

// Check for plan parameter
$selected_plan = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : '';

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
    
    // Redirect logged-in users (non-active members) to membership page, unless they have a plan selected
    if (in_array($membership_status, ['expired', 'cancelled', 'none']) && empty($selected_plan)) {
        wp_redirect(home_url('/membership/'));
        exit;
    }
}

// Get pricing plans (include promo code to unlock promo-only plans)
$pricing_plans = flexpress_get_pricing_plans(true, $promo_code);
$featured_plan = flexpress_get_featured_pricing_plan();

// Initialize Flowguard API
$flowguard_api = flexpress_get_flowguard_api();

// After initializing variables and before the main markup
$payment_status = isset($_GET['payment']) ? sanitize_text_field($_GET['payment']) : '';

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
        <!-- Full-width Carousel Section -->
        <div class="row mb-5">
            <div class="col-12">
                <?php
                // Handle local development environment with missing functions
                if (!function_exists('get_post_meta')) {
                    function get_post_meta($post_id, $key, $single = false) {
                        return array(); // Return empty array for local development
                    }
                }
                
                if (!function_exists('get_the_ID')) {
                    function get_the_ID() {
                        return 1; // Return dummy ID for local development
                    }
                }
                
                if (!function_exists('get_template_directory_uri')) {
                    function get_template_directory_uri() {
                        return '/wp-content/themes/flexpress'; // Return dummy path for local development
                    }
                }

                // Get carousel settings
                $carousel_interval = get_post_meta(get_the_ID(), 'join_carousel_interval', true);
                if (empty($carousel_interval) || !is_numeric($carousel_interval)) {
                    $carousel_interval = 5000; // Default to 5 seconds
                } else {
                    $carousel_interval = intval($carousel_interval) * 1000; // Convert seconds to milliseconds
                }

                // Get carousel slides from post meta or use defaults
                $carousel_slides = get_post_meta(get_the_ID(), 'join_carousel_slides', true);
                
                if (empty($carousel_slides) || !is_array($carousel_slides)) {
                    // Default slides if none are set
                    $carousel_slides = array(
                        array(
                            'image' => get_template_directory_uri() . '/assets/images/join-carousel-1.jpg',
                            'heading' => 'FULL LENGTH EPISODES',
                            'alt' => 'Full Length Episodes'
                        ),
                        array(
                            'image' => get_template_directory_uri() . '/assets/images/join-carousel-2.jpg',
                            'heading' => 'WEEKLY UPDATES',
                            'alt' => 'Weekly Updates'
                        ),
                        array(
                            'image' => get_template_directory_uri() . '/assets/images/join-carousel-3.jpg',
                            'heading' => 'PREMIUM CONTENT',
                            'alt' => 'Premium Content'
                        ),
                    );
                }
                ?>
                <div id="join-carousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="<?php echo $carousel_interval; ?>">
                    <div class="carousel-inner">
                        <?php 
                        $first_slide = true;
                        foreach ($carousel_slides as $slide): 
                            $image = isset($slide['image']) ? $slide['image'] : '';
                            $heading = isset($slide['heading']) ? $slide['heading'] : '';
                            $alt = isset($slide['alt']) ? $slide['alt'] : $heading;
                        ?>
                            <div class="carousel-item <?php echo $first_slide ? 'active' : ''; ?>">
                                <?php if ($image): ?>
                                    <img src="<?php echo $image; ?>" class="d-block w-100" alt="<?php echo $alt; ?>">
                                <?php endif; ?>
                                <div class="carousel-caption">
                                    <h1><?php echo $heading; ?></h1>
                                </div>
                            </div>
                        <?php 
                            $first_slide = false;
                        endforeach; 
                        ?>
                    </div>

                    <!-- Controls -->
                    <a class="carousel-control-prev" href="#join-carousel" role="button" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </a>
                    <a class="carousel-control-next" href="#join-carousel" role="button" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Header Section -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-10 text-center">
                <h1 class="display-4 mb-4"><?php esc_html_e('Join Premium Membership', 'flexpress'); ?></h1>
                <p class="lead mb-4"><?php esc_html_e('Unlock unlimited access to our exclusive content with a premium membership.', 'flexpress'); ?></p>
                
                <?php if ($payment_status === 'declined'): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php esc_html_e('Your payment was declined. Please try again or contact support.', 'flexpress'); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo esc_html($error_message); ?>
                    </div>
                <?php endif; ?>
                
                    <?php if ($is_renewal_flow): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-user-check me-2"></i>
                        <strong>Welcome back, <?php echo esc_html($current_user->first_name ?: $current_user->display_name); ?>!</strong>
                            <?php if ($membership_status === 'expired'): ?>
                                Your membership has expired. Choose a plan below to renew your access to premium content.
                            <?php else: ?>
                                Your membership was cancelled. Choose a plan below to restart your access to premium content.
                            <?php endif; ?>
                        <div class="mt-2">
                            <strong>Signed in as:</strong> <?php echo esc_html($current_user->user_email); ?>
                            <a href="<?php echo wp_logout_url(home_url('/join')); ?>" class="alert-link ms-2">(Sign out)</a>
                        </div>
                    </div>
                    <?php endif; ?>
            </div>
                </div>

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
                                    <?php echo sprintf(esc_html__('Promo code "%s" applied!', 'flexpress'), esc_html($promo_code)); ?>
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
                                $plan_features = isset($plan['features']) ? $plan['features'] : array();
                                $plan_type_class = 'plan-type-' . $plan['plan_type'];
                            ?>
                            <div class="membership-plan-item <?php echo $is_featured ? 'popular-plan' : ''; ?> <?php echo esc_attr($plan_type_class); ?>" 
                                 data-plan-type="<?php echo esc_attr($plan['plan_type']); ?>"
                                 data-plan-id="<?php echo esc_attr($plan_id); ?>"
                                 data-plan-price="<?php echo esc_attr($plan['price']); ?>"
                                 data-plan-currency="<?php echo esc_attr($plan['currency']); ?>"
                                 data-plan-name="<?php echo esc_attr($plan['name']); ?>"
                                 data-trial-enabled="<?php echo esc_attr($plan['trial_enabled'] ?? 0); ?>"
                                 data-trial-price="<?php echo esc_attr($plan['trial_price'] ?? 0); ?>"
                                 data-trial-duration="<?php echo esc_attr($plan['trial_duration'] ?? 0); ?>"
                                 data-trial-duration-unit="<?php echo esc_attr($plan['trial_duration_unit'] ?? 'days'); ?>">
                                <div class="plan-content">
                                    <div class="plan-info">
                                        <div class="plan-header">
                                            <h5 class="plan-name"><?php echo esc_html($plan['name']); ?></h5>
                                            <?php if ($is_featured): ?>
                                            <span class="popular-badge"><?php esc_html_e('MOST POPULAR', 'flexpress'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($plan['description'])): ?>
                                        <p class="plan-description"><?php echo esc_html($plan['description']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($plan['plan_type'] === 'recurring'): ?>
                                        <p class="plan-billing"><?php esc_html_e('Recurring Charge / Billed As', 'flexpress'); ?> <?php echo esc_html($plan['currency']); ?><?php echo esc_html(number_format($plan['price'], 2)); ?></p>
                                        <?php elseif ($plan['plan_type'] === 'one_time'): ?>
                                        <p class="plan-billing"><?php esc_html_e('One time charge Billed As', 'flexpress'); ?> <?php echo esc_html($plan['currency']); ?><?php echo esc_html(number_format($plan['price'], 2)); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="plan-pricing">
                                        <div class="price">
                                            <span class="price-amount"><?php echo esc_html(flexpress_get_daily_rate_display($plan)); ?></span>
                                            <small class="price-period">/Per Day</small>
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
                <div class="accordion" id="joinFAQ">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                <i class="fas fa-question-circle me-3"></i>
                                <?php esc_html_e('How do I cancel my subscription?', 'flexpress'); ?>
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="faqOne" data-bs-parent="#joinFAQ">
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
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#joinFAQ">
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
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#joinFAQ">
                            <div class="accordion-body">
                                <p class="mb-0"><?php esc_html_e('We occasionally offer free trial promotions for new members. Check our homepage or subscribe to our newsletter to stay informed about upcoming offers.', 'flexpress'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Join Page Carousel Styling */
#join-carousel {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    margin-bottom: 3rem;
    position: relative;
}

/* 16:9 Aspect Ratio Container */
#join-carousel .carousel-item {
    position: relative;
    padding-top: 56.25%; /* 16:9 Ratio (9/16 * 100%) */
    background-color: rgba(0,0,0,0.2);
    overflow: hidden;
}

#join-carousel .carousel-item img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

#join-carousel .carousel-caption {
    background: transparent;
    border-radius: 0;
    padding: 0 35px 35px;
    bottom: 0;
    left: 0;
    right: 0;
    text-align: left;
}

#join-carousel .carousel-caption h1 {
    font-size: 3rem;
    font-weight: bold;
    margin: 0;
    text-shadow: 2px 2px 10px rgba(0,0,0,0.9);
    letter-spacing: 2px;
    text-transform: uppercase;
    display: inline-block;
    position: relative;
}

#join-carousel .carousel-control-prev,
#join-carousel .carousel-control-next {
    width: 10%;
    opacity: 0.8;
}

/* Force promo code section to match membership page exactly */
.membership-page .promo-code-section {
    text-align: center !important;
    margin-bottom: 2rem !important;
    background: transparent !important;
    background-color: transparent !important;
    padding: 0 !important;
    margin: 0 0 2rem 0 !important;
    border-radius: 0 !important;
    border: none !important;
    box-shadow: none !important;
    position: static !important;
    top: auto !important;
    left: auto !important;
    right: auto !important;
    bottom: auto !important;
    z-index: auto !important;
}

/* Fix MOST POPULAR badge to stay on one line */
.membership-page .popular-badge {
    white-space: nowrap !important;
    flex-shrink: 0 !important;
    font-size: 0.65rem !important;
}

.membership-page .promo-code-label {
    color: white !important;
    font-size: 1rem !important;
    margin-bottom: 1rem !important;
    cursor: pointer !important;
    background: transparent !important;
}

.membership-page .promo-code-input .form-control {
    background: rgba(255, 255, 255, 0.1) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: white !important;
    border-radius: 0.375rem 0 0 0.375rem !important;
}

.membership-page .promo-code-input .form-control:focus {
    background: rgba(255, 255, 255, 0.15) !important;
    border-color: rgba(255, 255, 255, 0.4) !important;
    color: white !important;
    box-shadow: none !important;
}

.membership-page .promo-code-input .form-control::placeholder {
    color: rgba(255, 255, 255, 0.6) !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    #join-carousel .carousel-caption h1 {
        font-size: 2rem;
    }
}
</style>

<?php
// Enqueue and localize promo code script data
wp_localize_script('jquery', 'flexpressPromo', array(
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
        const billingTextElement = jQuery('#billing-text');
        
        // Trial information
        const trialEnabled = planElement.data('trial-enabled') == 1;
        const trialPrice = planElement.data('trial-price');
        const trialDuration = planElement.data('trial-duration');
        const trialDurationUnit = planElement.data('trial-duration-unit');
        
        let billingText = '';
        
        if (planType === 'recurring') {
            billingText = 'Your subscription will automatically renew at ' + planCurrency + planPrice.toFixed(2) + ' every 30 days unless cancelled. You may cancel at any time';
        } else if (planType === 'one_time') {
            billingText = 'This is a one-time payment of ' + planCurrency + planPrice.toFixed(2) + '. No recurring charges will be applied.';
        } else {
            // Default fallback
            billingText = 'Your subscription will automatically renew at ' + planCurrency + planPrice.toFixed(2) + ' every 30 days unless cancelled. You may cancel at any time';
        }
        
        billingTextElement.text(billingText);
        
        // Update trial disclaimer
        updateTrialDisclaimer(trialEnabled, trialPrice, trialDuration, trialDurationUnit, planCurrency, planPrice);
    }
    
    // Function to update trial disclaimer
    function updateTrialDisclaimer(trialEnabled, trialPrice, trialDuration, trialDurationUnit, planCurrency, planPrice) {
        const trialDisclaimerElement = jQuery('#trial-disclaimer');
        const trialTextElement = jQuery('#trial-text-content');
        
        if (trialEnabled && trialPrice > 0 && trialDuration > 0) {
            // Format trial duration text
            let durationText = trialDuration + ' ' + trialDurationUnit;
            if (trialDuration > 1 && trialDurationUnit === 'day') {
                durationText = trialDuration + ' days';
            } else if (trialDuration > 1 && trialDurationUnit === 'week') {
                durationText = trialDuration + ' weeks';
            } else if (trialDuration > 1 && trialDurationUnit === 'month') {
                durationText = trialDuration + ' months';
            }
            
            const trialText = '* Limited Access ' + durationText + ' trial automatically rebilling at ' + planCurrency + planPrice.toFixed(2) + ' every 30 days until cancelled';
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
    
    // Continue button functionality
    jQuery('#join-continue-btn').on('click', function(e) {
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
            // Logged in user - proceed to payment
            let paymentUrl = '<?php echo esc_url(home_url('/payment')); ?>?plan=' + encodeURIComponent(planId);
            
            // Add promo code if applied
            if (appliedPromo && appliedPromo.code) {
                paymentUrl += '&promo=' + encodeURIComponent(appliedPromo.code);
            }
            
            console.log('Redirecting logged in user to:', paymentUrl);
            window.location.href = paymentUrl;
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
    
    function showPromoMessage(message, type) {
        const messageDiv = jQuery('#membership-promo-message');
        messageDiv.removeClass('success error info').addClass(type);
        messageDiv.html('<div class="promo-applied-message text-' + type + '"><i class="fas fa-check-circle me-1"></i>' + message + '</div>').show();
        
        setTimeout(() => {
            messageDiv.hide();
        }, 5000);
    }
    
    // Store original prices
    jQuery('.membership-plan-item .price-amount').each(function() {
        const originalPrice = parseFloat(jQuery(this).text().replace(/[^0-9.]/g, ''));
        jQuery(this).data('original-price', originalPrice);
    });
});
</script>

<?php get_footer(); ?> 