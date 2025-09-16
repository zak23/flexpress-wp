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
    
    // Redirect logged-in users (non-active members) to membership page
    if (in_array($membership_status, ['expired', 'cancelled', 'none'])) {
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
?>

<main id="primary" class="site-main join-page py-5">
    <div class="container">
        <?php if ($payment_status === 'declined') : ?>
            <div class="alert alert-danger text-center mb-4">
                <?php esc_html_e('Your payment was declined. Please try again or contact support.', 'flexpress'); ?>
            </div>
        <?php endif; ?>
        
        <!-- Full-width Carousel Section -->
        <div class="row">
            <div class="col-12 mb-4">
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
        
        <!-- Registration Content -->
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <!-- Header -->
                <div class="text-center mb-4">
                    <?php if ($is_renewal_flow): ?>
                        <h1 class="h2 fw-bold text-white mb-3">WELCOME BACK, <?php echo strtoupper(esc_html($current_user->first_name ?: $current_user->display_name)); ?>!</h1>
                        <p class="text-white-50 mb-4">
                            <?php if ($membership_status === 'expired'): ?>
                                Your membership has expired. Choose a plan below to renew your access to premium content.
                            <?php else: ?>
                                Your membership was cancelled. Choose a plan below to restart your access to premium content.
                            <?php endif; ?>
                        </p>
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-user-check me-2"></i>
                            <strong>Signed in as:</strong> <?php echo esc_html($current_user->user_email); ?>
                            <a href="<?php echo wp_logout_url(home_url('/join')); ?>" class="alert-link ms-2">(Sign out)</a>
                        </div>
                    <?php else: ?>
                        <h1 class="h2 fw-bold text-white mb-3">JOIN <?php echo strtoupper(get_bloginfo('name')); ?></h1>
                        <p class="text-white-50 mb-4">Select your membership plan and get instant access to premium content</p>
                    <?php endif; ?>
                </div>

                <!-- Promo Code Section -->
                <div class="promo-section text-center mb-4">
                    <div class="promo-toggle mb-3">
                        <button type="button" class="btn btn-outline-light btn-sm" id="promo-toggle">
                            <i class="fas fa-tag me-2"></i>Have a promo code?
                        </button>
                    </div>
                    <div class="promo-form-container" style="display: <?php echo $promo_code ? 'block' : 'none'; ?>;">
                        <form class="d-flex mb-3 justify-content-center" id="promo-form" autocomplete="off">
                            <input type="text" class="form-control form-control-lg me-2" 
                                   placeholder="Enter promo code" id="promo_code" name="promo_code" 
                                   value="<?php echo esc_attr($promo_code); ?>" style="max-width: 300px;">
                            <button type="submit" class="btn btn-primary px-4 fw-bold">APPLY</button>
                        </form>
                        <div id="promo-message" class="alert" style="display: none;"></div>
                    </div>
                    <?php if (!$is_renewal_flow): ?>
                        <div class="mb-3">
                            <span class="text-white-50">Already have an account?</span>
                            <a href="/login" class="text-white fw-bold ms-2">Login here</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Plan Type Tabs -->
                <div class="plan-tabs mb-4">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="plan_type" id="recurring-tab" value="recurring" checked>
                        <label class="btn btn-outline-light btn-lg" for="recurring-tab">
                            <i class="fas fa-sync-alt me-2"></i>Recurring Plans
                        </label>
                        
                        <input type="radio" class="btn-check" name="plan_type" id="onetime-tab" value="onetime">
                        <label class="btn btn-outline-light btn-lg" for="onetime-tab">
                            <i class="fas fa-hourglass-half me-2"></i>One-Time Access
                        </label>
                    </div>
                </div>

                <!-- Registration Form -->
                <form id="flexpress-register-form" class="needs-validation" novalidate>
                    <input type="hidden" id="applied_promo_code" name="applied_promo_code" value="<?php echo esc_attr($promo_code); ?>">
                    
                    <!-- Plan Options Container -->
                    <div id="plan-options-container">
                        <div id="recurring-plans" class="plan-group">
                            <?php if (!empty($pricing_plans)): ?>
                                <?php foreach ($pricing_plans as $plan_id => $plan): ?>
                                    <?php 
                                        // Skip one-time plans in recurring section
                                        if (isset($plan['plan_type']) && $plan['plan_type'] === 'one_time') {
                                            continue;
                                        }
                                        $is_featured = $featured_plan && $featured_plan['id'] === $plan_id;
                                        flexpress_render_pricing_plan_card($plan_id, $plan, $is_featured);
                                    ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <p class="mb-0"><?php esc_html_e('No recurring plans are currently available. Please contact support.', 'flexpress'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div id="onetime-plans" class="plan-group" style="display: none;">
                            <?php if (!empty($pricing_plans)): ?>
                                <?php 
                                $has_onetime_plans = false;
                                $onetime_count = 0;
                                foreach ($pricing_plans as $plan_id => $plan): 
                                    if (isset($plan['plan_type']) && $plan['plan_type'] === 'one_time'):
                                        $has_onetime_plans = true;
                                        $onetime_count++;
                                        flexpress_render_pricing_plan_card($plan_id, $plan, false);
                                    endif;
                                endforeach;
                                
                                if (!$has_onetime_plans):
                                ?>
                                    <div class="alert alert-info">
                                        <p class="mb-0"><?php esc_html_e('No one-time access plans are currently available.', 'flexpress'); ?></p>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <p class="mb-0"><?php esc_html_e('No pricing plans are currently available. Please contact support.', 'flexpress'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Registration Fields -->
                    <div class="registration-fields mt-4">
                        <?php if ($is_renewal_flow): ?>
                            <!-- Hidden fields for renewal users -->
                            <input type="hidden" id="first_name" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>">
                            <input type="hidden" id="last_name" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>">
                            <input type="hidden" id="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>">
                            <input type="hidden" id="is_renewal" name="is_renewal" value="1">
                            <input type="hidden" id="user_id" name="user_id" value="<?php echo esc_attr($current_user->ID); ?>">
                            
                            <!-- Terms acknowledgment for renewal -->
                            <div class="renewal-terms mb-4">
                                <div class="form-check text-center">
                                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                    <label class="form-check-label text-white-50" for="terms">
                                        I agree to continue under the <a href="/terms" class="text-white">Terms of Service</a> and <a href="/privacy" class="text-white">Privacy Policy</a>
                                    </label>
                                    <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Full registration form for new users -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <input type="text" class="form-control form-control-lg" id="first_name" name="first_name" placeholder="First Name" required>
                                    <div class="invalid-feedback">Please enter your first name.</div>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control form-control-lg" id="last_name" name="last_name" placeholder="Last Name" required>
                                    <div class="invalid-feedback">Please enter your last name.</div>
                                </div>
                                <div class="col-12">
                                    <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="Email Address" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                                <div class="col-12">
                                    <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Password" required>
                                    <div class="invalid-feedback">Please enter a password.</div>
                                </div>
                                <div class="col-12">
                                    <input type="password" class="form-control form-control-lg" id="password_confirm" name="password_confirm" placeholder="Confirm Password" required>
                                    <div class="invalid-feedback">Please confirm your password.</div>
                                </div>
                                <div class="col-12 form-check mb-2 text-center">
                                    <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                    <label class="form-check-label text-white-50" for="terms">
                                        I agree to the <a href="/terms" class="text-white">Terms of Service</a> and <a href="/privacy" class="text-white">Privacy Policy</a>
                                    </label>
                                    <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-danger d-none" id="registration-error"></div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold py-3 mt-2" id="join-submit-btn">
                            <span class="submit-text">
                                <?php if ($is_renewal_flow): ?>
                                    RENEW MEMBERSHIP & PAY WITH FLOWGUARD
                                <?php else: ?>
                                    JOIN NOW & PAY WITH FLOWGUARD
                                <?php endif; ?>
                            </span>
                            <span class="loading-text d-none">
                                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                Redirecting to Payment...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<style>
.join-page {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    min-height: 100vh;
}

/* Carousel Styling */
#join-carousel {
    border-radius: 0;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    margin-bottom: 2rem;
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

.btn-primary {
    background: #ff6b6b;
    border-color: #ff6b6b;
    color: white;
}

.btn-primary:hover {
    background: #ff5252;
    border-color: #ff5252;
}

.btn-outline-light {
    border-color: rgba(255,255,255,0.3);
    color: rgba(255,255,255,0.8);
}

.btn-outline-light:hover,
.btn-outline-light.active,
.btn-outline-light:focus {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.5);
    color: white;
}

.form-control {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: white;
}

.form-control:focus {
    background: rgba(255,255,255,0.15);
    border-color: #ff6b6b;
    color: white;
    box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
}

.form-control::placeholder {
    color: rgba(255,255,255,0.5);
}

.plan-card {
    background: rgba(255,255,255,0.05);
    border: 2px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.plan-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    border-color: rgba(255,255,255,0.3);
}

.plan-card.selected {
    border-color: var(--color-accent);
    background: rgba(var(--color-accent-rgb), 0.1);
    box-shadow: 0 0 20px rgba(var(--color-accent-rgb), 0.3);
}

.plan-featured {
    border-color: var(--color-accent);
    box-shadow: 0 0 20px rgba(var(--color-accent-rgb), 0.3);
    position: relative;
    overflow: hidden;
}

.plan-featured::before {
    display: none;
}

.form-check-input:checked {
    background-color: var(--color-accent);
    border-color: var(--color-accent);
}

.promo-form-container {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

.alert {
    border: none;
    border-radius: 8px;
}

.text-white-50 a {
    color: rgba(255,255,255,0.8) !important;
    text-decoration: none;
}

.text-white-50 a:hover {
    color: white !important;
}

/* Hide promo-specific plans by default */
.plan-card[data-promo-only="true"] {
    display: none;
}

.plan-card[data-promo-only="true"].promo-visible {
    display: block;
}

/* Gradient overlay removed */

.form-check.text-center {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.form-check.text-center .form-check-input {
    float: none;
    margin-left: 0;
    margin-top: 0;
}

.form-check.text-center .form-check-label {
    padding-left: 0;
}

.form-check.text-center .invalid-feedback {
    text-align: center;
    width: 100%;
    position: absolute;
    left: 0;
    bottom: -20px;
}
</style>

<script>
// Pass data to external JavaScript file
window.pricingPlansData = <?php echo json_encode($pricing_plans); ?>;
window.currentPromoCode = '<?php echo esc_js($promo_code); ?>';
window.isRenewalFlow = <?php echo $is_renewal_flow ? 'true' : 'false'; ?>;
window.ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
window.joinNonce = '<?php echo wp_create_nonce('flexpress_join_form'); ?>';
</script>

<?php get_footer(); ?> 