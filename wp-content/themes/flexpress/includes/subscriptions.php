<?php
/**
 * FlexPress Subscription Helper
 *
 * Derives a normalized subscription type label for a user.
 * Returns one of: "Free Trial", "membership", "none".
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get user subscription type label.
 *
 * Priority:
 *  - Active trial → "Free Trial"
 *  - Active paid membership → "membership"
 *  - Otherwise → "none"
 *
 * @param int $user_id User ID. Defaults to current user.
 * @return string One of: Free Trial | membership | none
 */
function flexpress_get_user_subscription_type($user_id = null)
{
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return 'none';
    }

    // Detect active trial
    $trial_expires_at = get_user_meta($user_id, 'trial_expires_at', true);
    if (!empty($trial_expires_at)) {
        $expires_ts = strtotime($trial_expires_at);
        // 1-day grace period consistent with flexpress_get_membership_status
        $expires_with_grace = $expires_ts + DAY_IN_SECONDS;
        if ($expires_with_grace >= current_time('timestamp')) {
            $type = 'Free Trial';
            /**
             * Filter the derived subscription type label.
             *
             * @param string $type    The derived type label.
             * @param int    $user_id The user ID.
             */
            return apply_filters('flexpress_user_subscription_type', $type, $user_id);
        }
    }

    // Detect paid membership
    // Consider 'active' and 'cancelled' as having membership benefits until expiry if set
    $membership_status = function_exists('flexpress_get_membership_status')
        ? flexpress_get_membership_status($user_id)
        : (get_user_meta($user_id, 'membership_status', true) ?: 'none');

    if (in_array($membership_status, array('active', 'cancelled'), true)) {
        // If an explicit expiry is present and in future, still count as membership
        $membership_expires = get_user_meta($user_id, 'membership_expires', true);
        if (empty($membership_expires) || strtotime($membership_expires) >= current_time('timestamp')) {
            $type = 'membership';
            return apply_filters('flexpress_user_subscription_type', $type, $user_id);
        }
    }

    $type = 'none';
    return apply_filters('flexpress_user_subscription_type', $type, $user_id);
}


















