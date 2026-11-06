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
    // Use the same decision as content access, including cancelled memberships.
    $users = get_users([
        'meta_query' => [[
            'key' => 'membership_status',
            'value' => ['active', 'cancelled'],
            'compare' => 'IN'
        ]],
        'fields' => 'ID',
        'number' => -1
    ]);
    $expired_count = 0;
    foreach ($users as $user_id) {
        if (flexpress_get_membership_status((int) $user_id) === 'expired') {
            $expired_count++;
        }
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

