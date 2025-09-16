<?php
/**
 * Template Name: My Account
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

get_header();

// Add dashboard body class for better styling
add_filter('body_class', function($classes) {
    $classes[] = 'dashboard-page';
    return $classes;
});
?>

<div class="site-main dashboard-page">
    <div class="container py-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="user-profile text-center mb-4">
                            <?php
                            $user_id = get_current_user_id();
                            $avatar = get_avatar($user_id, 100, '', '', array('class' => 'rounded-circle mb-3'));
                            echo $avatar;
                            ?>
                            <h3 class="h5 mb-1"><?php echo esc_html(get_user_meta($user_id, 'first_name', true) . ' ' . get_user_meta($user_id, 'last_name', true)); ?></h3>
                            <p class="text-muted mb-0"><?php echo esc_html(get_user_by('id', $user_id)->user_email); ?></p>
                        </div>

                        <div class="list-group">
                            <a href="#purchases" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                                <i class="bi bi-collection-play me-2"></i>
                                <?php esc_html_e('Unlocked Episodes', 'flexpress'); ?>
                            </a>
                            <a href="#profile" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="bi bi-person me-2"></i>
                                <?php esc_html_e('Profile Settings', 'flexpress'); ?>
                            </a>
                            <a href="#billing" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="bi bi-credit-card me-2"></i>
                                <?php esc_html_e('Billing', 'flexpress'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <?php
                // Show payment success message
                if (isset($_GET['payment']) && $_GET['payment'] === 'success') {
                    $plan_name = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : 'your selected plan';
                    ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong><?php esc_html_e('Payment Successful!', 'flexpress'); ?></strong>
                        <?php printf(esc_html__('Welcome! Your %s subscription is now active and you have full access to all content.', 'flexpress'), esc_html($plan_name)); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php
                }
                
                // Show renewal success message
                if (isset($_GET['renewal']) && $_GET['renewal'] === 'success') {
                    $plan_name = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : 'your plan';
                    ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-refresh me-2"></i>
                        <strong><?php esc_html_e('Membership Renewed!', 'flexpress'); ?></strong>
                        <?php printf(esc_html__('Great! Your %s membership has been successfully renewed and you now have full access to all content.', 'flexpress'), esc_html($plan_name)); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php
                } elseif (isset($_GET['renewal']) && $_GET['renewal'] === 'cancelled') {
                    ?>
                    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong><?php esc_html_e('Renewal Cancelled', 'flexpress'); ?></strong>
                        <?php esc_html_e('Your membership renewal was cancelled. You can try again anytime using the Renew Membership button below.', 'flexpress'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php
                }
                ?>
                
                <div class="tab-content">
                    <!-- Purchased Episodes -->
                    <div class="tab-pane fade show active" id="purchases">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="h5 mb-0"><?php esc_html_e('Unlocked Episodes', 'flexpress'); ?></h2>
                            </div>
                            <div class="card-body">
                                <?php
                                $purchased_episodes = get_user_meta($user_id, 'purchased_episodes', true) ?: [];
                                $ppv_purchases = get_user_meta($user_id, 'ppv_purchases', true) ?: [];
                                $all_purchased_episodes = array_unique(array_merge($purchased_episodes, $ppv_purchases));
                                
                                if (!empty($all_purchased_episodes)):
                                    $args = array(
                                        'post_type' => 'episode',
                                        'post__in' => $all_purchased_episodes,
                                        'posts_per_page' => -1,
                                        'orderby' => 'meta_value',
                                        'meta_key' => 'release_date',
                                        'order' => 'DESC'
                                    );
                                    
                                    $query = new WP_Query($args);
                                    
                                    if ($query->have_posts()):
                                ?>
                                    <div class="row g-4">
                                        <?php
                                        while ($query->have_posts()): $query->the_post();
                                        ?>
                                            <div class="col-md-6">
                                                <?php get_template_part('template-parts/content', 'episode-card'); ?>
                                            </div>
                                        <?php
                                        endwhile;
                                        wp_reset_postdata();
                                        ?>
                                    </div>
                                <?php
                                    endif;
                                else:
                                ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-collection-play display-1 text-muted mb-3"></i>
                                        <h3 class="h5 mb-3"><?php esc_html_e('No Unlocked Episodes Yet', 'flexpress'); ?></h3>
                                        <p class="text-muted mb-4"><?php esc_html_e('You have not unlocked any episodes yet. Your unlocked episodes will appear here once you purchase them.', 'flexpress'); ?></p>
                                        <a href="<?php echo esc_url(home_url('/episodes')); ?>" class="btn btn-primary">
                                            <?php esc_html_e('Browse Episodes', 'flexpress'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Settings -->
                    <div class="tab-pane fade" id="profile">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="h5 mb-0"><?php esc_html_e('Profile Settings', 'flexpress'); ?></h2>
                            </div>
                            <div class="card-body">
                                <div class="user-info mb-4">
                                    <h3 class="h6 mb-3"><?php esc_html_e('Account Information', 'flexpress'); ?></h3>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1">
                                                <strong><?php esc_html_e('Username:', 'flexpress'); ?></strong>
                                                <?php echo esc_html(get_user_by('id', $user_id)->user_login); ?>
                                            </p>
                                            <p class="mb-1">
                                                <strong><?php esc_html_e('Email:', 'flexpress'); ?></strong>
                                                <?php echo esc_html(get_user_by('id', $user_id)->user_email); ?>
                                            </p>
                                            <p class="mb-1">
                                                <strong><?php esc_html_e('Membership Status:', 'flexpress'); ?></strong>
                                                <span class="badge bg-<?php 
                                                    $status = get_user_meta($user_id, 'membership_status', true) ?: 'none';
                                                    $status_color = '';
                                                    switch($status) {
                                                        case 'active':
                                                            $status_color = 'success';
                                                            break;
                                                        case 'cancelled':
                                                            $status_color = 'warning';
                                                            break;
                                                        case 'expired':
                                                        case 'banned':
                                                            $status_color = 'danger';
                                                            break;
                                                        default:
                                                            $status_color = 'secondary';
                                                    }
                                                    echo $status_color;
                                                ?>">
                                                    <?php echo esc_html(ucfirst($status)); ?>
                                                </span>
                                            </p>
                                            <p class="mb-1">
                                                <strong><?php esc_html_e('Date Joined:', 'flexpress'); ?></strong>
                                                <?php 
                                                    $user_info = get_userdata($user_id);
                                                    // Convert UTC timestamp to site timezone
                    $utc_timestamp = strtotime($user_info->user_registered);
                    $site_time = $utc_timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS);
                    echo esc_html(date_i18n(get_option('date_format'), $site_time)); 
                                                ?>
                                            </p>
                                            <?php if ($status === 'active'): ?>
                                                <p class="mb-1">
                                                    <strong><?php esc_html_e('Next Rebill:', 'flexpress'); ?></strong>
                                                    <?php 
                                                        $next_rebill = get_user_meta($user_id, 'next_rebill_date', true);
                                                        if ($next_rebill) {
                                    // Convert UTC timestamp to site timezone
                                    $utc_timestamp = strtotime($next_rebill);
                                    $site_time = $utc_timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS);
                                    echo esc_html(date_i18n(get_option('date_format'), $site_time));
                                } else {
                                    echo esc_html__('Not available', 'flexpress');
                                }
                                                    ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <form method="post" id="profile-form">
                                    <?php wp_nonce_field('flexpress_dashboard_nonce', 'nonce'); ?>
                                    <input type="hidden" name="action" value="flexpress_update_profile">
                                    
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label"><?php esc_html_e('First Name', 'flexpress'); ?></label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo esc_attr(get_user_meta($user_id, 'first_name', true)); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label"><?php esc_html_e('Last Name', 'flexpress'); ?></label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo esc_attr(get_user_meta($user_id, 'last_name', true)); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="display_name" class="form-label"><?php esc_html_e('Display Name', 'flexpress'); ?></label>
                                        <input type="text" class="form-control" id="display_name" name="display_name" value="<?php echo esc_attr(function_exists('flexpress_get_user_display_name') ? flexpress_get_user_display_name($user_id) : get_user_meta($user_id, 'flexpress_display_name', true)); ?>" placeholder="How you want to appear in messages and comments">
                                        <div class="form-text">This is how your name will appear when sending messages to models.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label"><?php esc_html_e('Email Address', 'flexpress'); ?></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo esc_attr(get_user_by('id', $user_id)->user_email); ?>">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <?php esc_html_e('Update Profile', 'flexpress'); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Billing -->
                    <div class="tab-pane fade" id="billing">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="h5 mb-0"><?php esc_html_e('Billing Information', 'flexpress'); ?></h2>
                            </div>
                            <div class="card-body">
                                <?php
                                $membership_status = get_user_meta($user_id, 'membership_status', true) ?: 'none';
                                $subscription_type = get_user_meta($user_id, 'subscription_type', true);
                                $subscription_start = get_user_meta($user_id, 'subscription_start_date', true);
                                $next_rebill = get_user_meta($user_id, 'next_rebill_date', true);
                                $flowguard_transaction_id = get_user_meta($user_id, 'flowguard_transaction_id', true);
                                $flowguard_transaction_id = get_user_meta($user_id, 'flowguard_transaction_id', true);
                                ?>
                                
                                <div class="subscription-info mb-4">
                                    <h3 class="h6 mb-3"><?php esc_html_e('Current Subscription', 'flexpress'); ?></h3>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1">
                                                <strong><?php esc_html_e('Status:', 'flexpress'); ?></strong>
                                                <span class="badge bg-<?php 
                                                    $status_color = '';
                                                    switch($membership_status) {
                                                        case 'active':
                                                            $status_color = 'success';
                                                            break;
                                                        case 'cancelled':
                                                            $status_color = 'warning';
                                                            break;
                                                        case 'expired':
                                                        case 'banned':
                                                            $status_color = 'danger';
                                                            break;
                                                        default:
                                                            $status_color = 'secondary';
                                                    }
                                                    echo $status_color;
                                                ?>">
                                                    <?php echo esc_html(ucfirst($membership_status)); ?>
                                                </span>
                                            </p>

                                            <?php if ($subscription_start): ?>
                                                <p class="mb-1">
                                                    <strong><?php esc_html_e('Started:', 'flexpress'); ?></strong>
                                                    <?php 
                                                    // Convert UTC timestamp to site timezone
                                                    $utc_timestamp = strtotime($subscription_start);
                                                    $site_time = $utc_timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS);
                                                    echo esc_html(date_i18n(get_option('date_format'), $site_time)); 
                                                    ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if ($next_rebill): ?>
                                                <p class="mb-0">
                                                    <strong>
                                                        <?php 
                                                        if ($membership_status === 'cancelled') {
                                                            esc_html_e('Expires:', 'flexpress');
                                                        } else {
                                                            esc_html_e('Next Rebill:', 'flexpress');
                                                        }
                                                        ?>
                                                    </strong>
                                                    <?php 
                                                    // Convert UTC timestamp to site timezone
                                                    $utc_timestamp = strtotime($next_rebill);
                                                    $site_time = $utc_timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS);
                                                    echo esc_html(date_i18n(get_option('date_format'), $site_time)); 
                                                    ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if ($flowguard_transaction_id): ?>
                                                <p class="mb-0">
                                                    <strong><?php esc_html_e('Transaction ID:', 'flexpress'); ?></strong>
                                                    <?php echo esc_html($flowguard_transaction_id); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($membership_status === 'active'): ?>
                                    <div class="mt-3">
                                        <?php
                                        // Check if user has Flowguard subscription
                                        $flowguard_sale_id = get_user_meta($user_id, 'flowguard_sale_id', true);
                                        $flowguard_transaction_id = get_user_meta($user_id, 'flowguard_transaction_id', true);
                                        $has_flowguard_subscription = !empty($flowguard_sale_id) || !empty($flowguard_transaction_id);
                                        ?>
                                        
                                        <?php if ($has_flowguard_subscription): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger me-2" id="cancel-flowguard-subscription">
                                            <i class="fas fa-times-circle me-1"></i>
                                            <?php esc_html_e('Cancel Flowguard Subscription', 'flexpress'); ?>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-sm btn-outline-warning me-2" id="cancel-subscription">
                                            <?php esc_html_e('Cancel Subscription', 'flexpress'); ?>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="update-payment-method">
                                            <?php esc_html_e('Update Payment Method', 'flexpress'); ?>
                                        </button>
                                    </div>
                                    <?php elseif ($membership_status === 'expired' || $membership_status === 'none'): ?>
                                    <div class="mt-3">
                                        <?php
                                        // Get user's previous plan or featured plan for renewal
                                        $previous_plan = get_user_meta($user_id, 'subscription_plan', true);
                                        $renewal_plan = $previous_plan ?: null;
                                        
                                        // If no previous plan, get featured plan
                                        if (!$renewal_plan) {
                                            $featured_plan = flexpress_get_featured_pricing_plan();
                                            $renewal_plan = $featured_plan ? $featured_plan['id'] : 'monthly';
                                        }
                                        
                                        // Get plan details for display
                                        $plan_details = flexpress_get_pricing_plan($renewal_plan);
                                        $plan_name = $plan_details ? $plan_details['name'] : 'Premium Membership';
                                        $plan_price = $plan_details ? $plan_details['price'] : '9.99';
                                        $plan_currency = $plan_details ? ($plan_details['currency'] ?: '$') : '$';
                                        ?>
                                        <button type="button" class="btn btn-primary me-2" id="renew-membership" data-plan-id="<?php echo esc_attr($renewal_plan); ?>">
                                            <i class="fas fa-refresh me-1"></i>
                                            <?php printf(esc_html__('Renew %s (%s%s)', 'flexpress'), $plan_name, $plan_currency, $plan_price); ?>
                                        </button>
                                        <a href="<?php echo esc_url(home_url('/membership')); ?>" class="btn btn-outline-secondary btn-sm">
                                            <?php esc_html_e('View All Plans', 'flexpress'); ?>
                                        </a>
                                        
                                        <?php if ($membership_status === 'expired'): ?>
                                        <div class="alert alert-warning mt-3">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong><?php esc_html_e('Membership Expired', 'flexpress'); ?></strong>
                                            <?php esc_html_e('Your membership has expired. Renew now to regain access to all premium content.', 'flexpress'); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php elseif ($membership_status === 'cancelled'): ?>
                                    <div class="mt-3">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong><?php esc_html_e('Membership Cancelled', 'flexpress'); ?></strong>
                                            <?php 
                                            $expires_date = get_user_meta($user_id, 'membership_expires', true) ?: get_user_meta($user_id, 'next_rebill_date', true);
                                            if ($expires_date) {
                                                $utc_timestamp = strtotime($expires_date);
                                                $site_time = $utc_timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS);
                                                $formatted_date = date_i18n(get_option('date_format'), $site_time);
                                                printf(esc_html__('Your membership is cancelled but remains active until %s.', 'flexpress'), $formatted_date);
                                            } else {
                                                esc_html_e('Your membership is cancelled.', 'flexpress');
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="billing-history mt-4">
                                    <?php
                                    // Get user billing history from multiple sources - MOVED UP TO FIX SCOPE
                                    $transactions = function_exists('flexpress_get_user_billing_history') 
                                        ? flexpress_get_user_billing_history($user_id, 20) 
                                        : array();
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h3 class="h6 mb-0"><?php esc_html_e('Billing History', 'flexpress'); ?></h3>
                                        <?php if (!empty($transactions)): ?>
                                            <small class="text-muted">
                                                <?php 
                                                printf(
                                                    esc_html__('%d transactions found', 'flexpress'), 
                                                    count($transactions)
                                                ); 
                                                ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th><?php esc_html_e('Date', 'flexpress'); ?></th>
                                                    <th><?php esc_html_e('Description', 'flexpress'); ?></th>
                                                    <th><?php esc_html_e('Amount', 'flexpress'); ?></th>
                                                    <th><?php esc_html_e('Transaction ID', 'flexpress'); ?></th>
                                                    <th><?php esc_html_e('Status', 'flexpress'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($transactions)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        <?php esc_html_e('No billing history available yet', 'flexpress'); ?>
                                                    </td>
                                                </tr>
                                                <?php else: 
                                                    foreach ($transactions as $transaction): 
                                                        // Determine status badge color
                                                        $status_class = 'secondary';
                                                        switch ($transaction['status']) {
                                                            case 'paid':
                                                            case 'completed':
                                                                $status_class = 'success';
                                                                break;
                                                            case 'cancelled':
                                                                $status_class = 'warning';
                                                                break;
                                                            case 'refunded':
                                                            case 'chargeback':
                                                                $status_class = 'danger';
                                                                break;
                                                            case 'expired':
                                                                $status_class = 'secondary';
                                                                break;
                                                        }
                                                ?>
                                                <tr>
                                                    <td>
                                                        <?php 
                                                        if (!empty($transaction['date'])) {
                                                            // Convert UTC timestamp to site timezone
                                                            $utc_timestamp = strtotime($transaction['date']);
                                                            $site_time = $utc_timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS);
                                                            echo esc_html(date_i18n(get_option('date_format'), $site_time)); 
                                                        } else {
                                                            echo '<span class="text-muted">N/A</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php echo esc_html($transaction['description']); ?>
                                                        <?php if (isset($transaction['type']) && $transaction['type'] === 'ppv_purchase'): ?>
                                                            <small class="text-muted d-block">Pay-per-view</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($transaction['amount'])): ?>
                                                            <span class="<?php echo strpos($transaction['amount'], '-') === 0 ? 'text-danger' : 'text-success'; ?>">
                                                                <?php echo esc_html($transaction['amount']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <code class="small"><?php echo esc_html($transaction['transaction_id']); ?></code>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $status_class; ?> small">
                                                            <?php echo esc_html(ucfirst($transaction['status'])); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    endforeach;
                                                endif; 
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
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
    // Profile form submission
    $('#profile-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        // Show loading state
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Updating...');
        
        // Clear any existing alerts
        $('.alert.profile-alert').remove();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $form.before('<div class="alert alert-success profile-alert"><i class="fas fa-check-circle me-2"></i>' + response.data.message + '</div>');
                    
                    // Update display name field if it was changed
                    var newDisplayName = $('#display_name').val();
                    if (newDisplayName) {
                        // Update any displayed names on the page
                        $('.user-display-name').text(newDisplayName);
                    }
                } else {
                    // Show error message
                    $form.before('<div class="alert alert-danger profile-alert"><i class="fas fa-exclamation-circle me-2"></i>' + response.data.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $form.before('<div class="alert alert-danger profile-alert"><i class="fas fa-exclamation-circle me-2"></i>An error occurred while updating your profile. Please try again.</div>');
            },
            complete: function() {
                // Reset button state
                $submitBtn.prop('disabled', false).text(originalText);
                
                // Scroll to top of form to show alert
                $('html, body').animate({
                    scrollTop: $('.profile-alert').offset().top - 100
                }, 500);
            }
        });
    });
    
    // Flowguard subscription cancellation
    $('#cancel-flowguard-subscription').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.html();
        
        // Show confirmation dialog
        if (!confirm('<?php esc_html_e('Are you sure you want to cancel your Flowguard subscription? This action cannot be undone.', 'flexpress'); ?>')) {
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i><?php esc_html_e('Cancelling...', 'flexpress'); ?>');
        
        // Clear any existing alerts
        $('.alert.flowguard-alert').remove();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'flexpress_cancel_flowguard_subscription',
                nonce: '<?php echo wp_create_nonce('flexpress_cancel_flowguard_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $button.closest('.mt-3').before('<div class="alert alert-success flowguard-alert"><i class="fas fa-check-circle me-2"></i>' + response.data.message + '</div>');
                    
                    // Hide the cancel button
                    $button.hide();
                    
                    // Reload page after 3 seconds to update the UI
                    setTimeout(function() {
                        window.location.reload();
                    }, 3000);
                } else {
                    // Show error message
                    $button.closest('.mt-3').before('<div class="alert alert-danger flowguard-alert"><i class="fas fa-exclamation-circle me-2"></i>' + response.data.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $button.closest('.mt-3').before('<div class="alert alert-danger flowguard-alert"><i class="fas fa-exclamation-circle me-2"></i><?php esc_html_e('An error occurred while cancelling your subscription. Please try again.', 'flexpress'); ?></div>');
            },
            complete: function() {
                // Reset button state
                $button.prop('disabled', false).html(originalText);
                
                // Scroll to top of alerts to show message
                $('html, body').animate({
                    scrollTop: $('.flowguard-alert').offset().top - 100
                }, 500);
            }
        });
    });
});
</script>

<?php
get_footer(); 