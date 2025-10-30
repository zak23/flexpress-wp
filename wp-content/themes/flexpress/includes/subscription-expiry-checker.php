<?php
/**
 * FlexPress Subscription Expiry Checker
 * 
 * Automatically checks and marks subscriptions as expired if they've passed
 * their rebill date without receiving a webhook update from Flowguard.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check for expired subscriptions
 * 
 * Runs daily to check if any active subscriptions have passed their rebill date
 * without receiving a webhook update. Marks them as expired if so.
 */
function flexpress_check_subscription_expiry() {
    global $wpdb;
    
    // Get all users with active membership status
    $active_users = get_users(array(
        'meta_key' => 'membership_status',
        'meta_value' => 'active',
        'number' => -1
    ));
    
    $expired_count = 0;
    $grace_period_days = 1; // 1 day grace period past rebill date
    
    foreach ($active_users as $user) {
        $user_id = $user->ID;
        
        // Skip if user has cancelled membership
        $status = get_user_meta($user_id, 'membership_status', true);
        if ($status !== 'active') {
            continue;
        }
        
        // Get next rebill date
        $next_rebill_date = get_user_meta($user_id, 'next_rebill_date', true);
        
        if (empty($next_rebill_date)) {
            continue; // No rebill date set, skip
        }
        
        // Check if user has Flowguard subscription (has sale_id or transaction_id)
        $flowguard_sale_id = get_user_meta($user_id, 'flowguard_sale_id', true);
        $flowguard_transaction_id = get_user_meta($user_id, 'flowguard_transaction_id', true);
        
        // Only check users with Flowguard subscriptions
        if (empty($flowguard_sale_id) && empty($flowguard_transaction_id)) {
            continue;
        }
        
        // Calculate expiry timestamp (rebill date + grace period)
        $rebill_timestamp = strtotime($next_rebill_date);
        $expiry_timestamp = $rebill_timestamp + ($grace_period_days * DAY_IN_SECONDS);
        $current_timestamp = current_time('timestamp');
        
        // If past expiry date, mark as expired
        if ($current_timestamp > $expiry_timestamp) {
            // Update membership status
            flexpress_update_membership_status($user_id, 'expired');
            
            // Log activity
            if (class_exists('FlexPress_Activity_Logger')) {
                FlexPress_Activity_Logger::log_activity($user_id, 'subscription_auto_expired', sprintf(
                    'Subscription automatically expired: Rebill date passed (%s) without webhook update. Grace period: %d days.',
                    date('Y-m-d H:i:s', $rebill_timestamp),
                    $grace_period_days
                ));
            }
            
            $expired_count++;
            
            error_log(sprintf(
                'FlexPress Expiry Check: User %d marked as expired. Rebill date: %s, Current: %s',
                $user_id,
                $next_rebill_date,
                date('Y-m-d H:i:s', $current_timestamp)
            ));
        }
    }
    
    if ($expired_count > 0) {
        error_log(sprintf('FlexPress Expiry Check: Marked %d subscriptions as expired', $expired_count));
    }
    
    return $expired_count;
}

/**
 * Schedule daily expiry check
 */
function flexpress_schedule_expiry_check() {
    // Check if already scheduled
    if (!wp_next_scheduled('flexpress_daily_expiry_check')) {
        // Schedule for midnight each day
        wp_schedule_event(strtotime('tomorrow midnight'), 'daily', 'flexpress_daily_expiry_check');
    }
}

/**
 * Hook expiry check to scheduled event
 */
add_action('flexpress_daily_expiry_check', 'flexpress_check_subscription_expiry');

/**
 * Schedule on theme activation
 */
add_action('after_switch_theme', 'flexpress_schedule_expiry_check');

/**
 * Schedule on admin init (in case it wasn't scheduled)
 */
add_action('admin_init', 'flexpress_schedule_expiry_check');

