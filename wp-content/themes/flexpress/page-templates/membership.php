<?php
/**
 * Template Name: Membership Page
 *
 * @package FlexPress
 */

get_header();

// Get current user membership status
$membership_status = 'none';
$next_rebill_date = '';
$subscription_type = '';

if (is_user_logged_in() && function_exists('flexpress_get_membership_status')) {
    $current_user_id = get_current_user_id();
    $membership_status = flexpress_get_membership_status($current_user_id);
    $next_rebill_date = get_user_meta($current_user_id, 'next_rebill_date', true);
    $subscription_type = get_user_meta($current_user_id, 'subscription_type', true);
}

// Get pricing plans from FlexPress settings
$pricing_plans = flexpress_get_pricing_plans();
$featured_plan = flexpress_get_featured_pricing_plan();
?>

<div class="membership-page">
    <div class="container py-5">
        <div class="row justify-content-center mb-5">
            <div class="col-md-10 text-center">
                <h1 class="display-4 mb-4"><?php esc_html_e('Premium Membership', 'flexpress'); ?></h1>
                <p class="lead mb-4"><?php esc_html_e('Unlock unlimited access to our exclusive content with a premium membership.', 'flexpress'); ?></p>
                
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
            </div>
        </div>

        <div class="row justify-content-center">
            <?php if (!empty($pricing_plans)): ?>
                <?php foreach ($pricing_plans as $plan_id => $plan): ?>
                    <?php 
                        $is_featured = $featured_plan && $featured_plan['id'] === $plan_id;
                        $plan_features = isset($plan['features']) ? $plan['features'] : array();
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card membership-card h-100 <?php echo $is_featured ? 'border-primary' : ''; ?>">
                            <?php if ($is_featured): ?>
                                <div class="card-header bg-primary text-white text-center py-3">
                                    <span class="badge bg-white text-primary"><?php esc_html_e('Most Popular', 'flexpress'); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h2 class="card-title text-center mb-4"><?php echo esc_html($plan['name']); ?></h2>
                                <div class="price-container text-center mb-4">
                                    <span class="currency"><?php echo esc_html($plan['currency']); ?></span>
                                    <span class="price display-4"><?php echo esc_html(number_format($plan['price'], 2)); ?></span>
                                    <span class="period">/ <?php echo esc_html(flexpress_format_plan_duration($plan)); ?></span>
                                </div>
                                <p class="card-text text-center mb-4"><?php echo esc_html($plan['description']); ?></p>
                                
                                <?php if (!empty($plan_features)): ?>
                                    <ul class="list-group list-group-flush mb-4">
                                        <?php foreach ($plan_features as $feature): ?>
                                            <li class="list-group-item bg-transparent">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <?php echo esc_html($feature); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                
                                <div class="mt-auto">
                                    <?php if (!is_user_logged_in()): ?>
                                        <a href="<?php echo esc_url(home_url('/join')); ?>" class="btn btn-primary btn-lg w-100">
                                            <?php esc_html_e('Sign Up Now', 'flexpress'); ?>
                                        </a>
                                    <?php elseif ($membership_status !== 'active' && $membership_status !== 'banned'): ?>
                                        <button type="button" class="btn btn-primary btn-lg w-100 flexpress-select-plan" 
                                                data-plan-id="<?php echo esc_attr($plan_id); ?>"
                                                data-plan-name="<?php echo esc_attr($plan['name']); ?>"
                                                data-plan-price="<?php echo esc_attr($plan['price']); ?>"
                                                data-plan-currency="<?php echo esc_attr($plan['currency']); ?>">
                                            <?php esc_html_e('Subscribe Now', 'flexpress'); ?>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-lg w-100" disabled>
                                            <?php 
                                            if ($membership_status === 'active') {
                                                esc_html_e('Already Subscribed', 'flexpress');
                                            } else {
                                                esc_html_e('Account Suspended', 'flexpress');
                                            }
                                            ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <div class="alert alert-warning">
                        <p class="mb-0"><?php esc_html_e('No pricing plans are currently available. Please contact support.', 'flexpress'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row mt-5 justify-content-center">
            <div class="col-md-10">
                <div class="card bg-dark text-white">
                    <div class="card-body">
                        <h3 class="card-title mb-4"><?php esc_html_e('Premium Membership Benefits', 'flexpress'); ?></h3>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-film fa-2x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h4><?php esc_html_e('Unlimited Streaming', 'flexpress'); ?></h4>
                                        <p><?php esc_html_e('Watch as much as you want, whenever you want. No limits, no restrictions.', 'flexpress'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h4><?php esc_html_e('New Content Weekly', 'flexpress'); ?></h4>
                                        <p><?php esc_html_e('We add new premium videos every week, so you\'ll always have something fresh to watch.', 'flexpress'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-download fa-2x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h4><?php esc_html_e('Download Videos', 'flexpress'); ?></h4>
                                        <p><?php esc_html_e('Download videos to watch offline on your devices when you\'re on the go.', 'flexpress'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-mobile-alt fa-2x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h4><?php esc_html_e('Watch Anywhere', 'flexpress'); ?></h4>
                                        <p><?php esc_html_e('Stream on your TV, computer, tablet, or mobile device with our responsive player.', 'flexpress'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5 justify-content-center">
            <div class="col-md-8 text-center">
                <h2 class="mb-4"><?php esc_html_e('Frequently Asked Questions', 'flexpress'); ?></h2>
                
                <div class="accordion" id="membershipFAQ">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                <?php esc_html_e('How do I cancel my subscription?', 'flexpress'); ?>
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="faqOne" data-bs-parent="#membershipFAQ">
                            <div class="accordion-body">
                                <?php esc_html_e('You can cancel your subscription at any time from your my account page. Your membership will remain active until the end of your current billing period.', 'flexpress'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                <?php esc_html_e('Can I switch between plans?', 'flexpress'); ?>
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#membershipFAQ">
                            <div class="accordion-body">
                                <?php esc_html_e('Yes, you can upgrade or downgrade your plan at any time. If you upgrade, the new rate will be charged immediately. If you downgrade, the new rate will apply at your next billing cycle.', 'flexpress'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                <?php esc_html_e('Is there a free trial?', 'flexpress'); ?>
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#membershipFAQ">
                            <div class="accordion-body">
                                <?php esc_html_e('We occasionally offer free trial promotions for new members. Check our homepage or subscribe to our newsletter to stay informed about upcoming offers.', 'flexpress'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.flexpress-select-plan').on('click', function() {
        const $button = $(this);
        const planId = $button.data('plan-id');
        const planName = $button.data('plan-name');
        const planPrice = $button.data('plan-price');
        const planCurrency = $button.data('plan-currency');
        
        // Disable button and show loading state
        $button.prop('disabled', true);
        const originalText = $button.text();
        $button.html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...');
        
        // Create Flowguard payment session
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'flexpress_create_flowguard_payment',
                plan_id: planId,
                nonce: '<?php echo wp_create_nonce('flexpress_payment_nonce'); ?>'
            },
            success: function(response) {
                if (response.success && response.data.payment_url) {
                    // Redirect to payment page
                    window.location.href = response.data.payment_url;
                } else {
                    // Show error message
                    alert('Error creating payment session: ' + (response.data.message || 'Unknown error'));
                    $button.prop('disabled', false);
                    $button.text(originalText);
                }
            },
            error: function() {
                alert('Network error. Please try again.');
                $button.prop('disabled', false);
                $button.text(originalText);
            }
        });
    });
});
</script>

<?php get_footer(); ?> 