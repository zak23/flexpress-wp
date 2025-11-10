<?php
/**
 * Promo and Trial helper wrappers for lifecycle automations
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create a 7-day (or custom) single-use promo code for a user and context.
 *
 * @param int $user_id
 * @param string $context Short context label, e.g. 'winback'
 * @param float $amountOrPercent If 0-100 => percentage, else fixed amount in site currency
 * @param string $expiresAt MySQL datetime string (optional). Defaults to +7 days.
 * @return string|false The promo code or false on failure
 */
function flexpress_create_user_promo($user_id, $context, $amountOrPercent = 20, $expiresAt = '')
{
    global $wpdb;
    $table = $wpdb->prefix . 'flexpress_promo_codes';
    if (!$wpdb->get_var("SHOW TABLES LIKE '{$table}'")) {
        return false;
    }
    $user_id = intval($user_id);
    $context = sanitize_key($context);
    $is_percentage = ($amountOrPercent >= 0 && $amountOrPercent <= 100);
    $discount_type = $is_percentage ? 'percentage' : 'fixed';
    $discount_value = floatval($amountOrPercent);
    if (empty($expiresAt)) {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
    }
    // Ensure one active code per context
    $meta_key = 'flexpress_last_promo_' . $context;
    $existing_code = get_user_meta($user_id, $meta_key, true);
    if (!empty($existing_code)) {
        // Optionally, deactivate old code; for safety we just return existing if still valid
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE code = %s LIMIT 1", $existing_code));
        if ($row && $row->status === 'active' && (empty($row->valid_until) || strtotime($row->valid_until) > time())) {
            return $existing_code;
        }
    }
    // Generate unique code
    $base = strtoupper($context) . '-' . $user_id . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
    $code = $base;
    // Insert
    $res = $wpdb->insert(
        $table,
        array(
            'code' => $code,
            'name' => ucfirst($context) . ' ' . $user_id,
            'description' => 'Auto-generated for user ' . $user_id . ' (' . $context . ')',
            'discount_type' => $discount_type,
            'discount_value' => $discount_value,
            'minimum_amount' => 0,
            'maximum_discount' => 0,
            'usage_limit' => 1,
            'user_limit' => 1,
            'valid_from' => current_time('mysql'),
            'valid_until' => $expiresAt,
            'applicable_plans' => '',
            'status' => 'active',
            'created_by' => get_current_user_id()
        ),
        array('%s','%s','%s','%s','%f','%f','%f','%d','%d','%s','%s','%s','%s','%d')
    );
    if ($res === false) {
        return false;
    }
    update_user_meta($user_id, $meta_key, $code);
    update_user_meta($user_id, $meta_key . '_expires', $expiresAt);
    return $code;
}

/**
 * Generate a single-use free trial link for a user.
 *
 * @param int $user_id
 * @param string $validUntil MySQL datetime string (optional). Defaults to +7 days.
 * @param bool $singleUse
 * @return string|false The URL or false on failure
 */
function flexpress_generate_trial_link($user_id, $validUntil = '', $singleUse = true)
{
    if (empty($validUntil)) {
        $validUntil = date('Y-m-d H:i:s', strtotime('+7 days'));
    }
    $args = array(
        'duration' => 7,
        'created_by' => get_current_user_id(),
        'expires_at' => $validUntil,
        'max_uses' => $singleUse ? 1 : 100,
        'notes' => 'Auto-generated referral for user ' . intval($user_id)
    );
    $id = flexpress_create_trial_link($args);
    if (!$id) {
        return false;
    }
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT token FROM {$wpdb->prefix}flexpress_trial_links WHERE id = %d", $id));
    if (!$row || empty($row->token)) {
        return false;
    }
    // Track last generated for user
    update_user_meta($user_id, 'flexpress_last_trial_token', $row->token);
    update_user_meta($user_id, 'flexpress_last_trial_expires', $validUntil);
    return flexpress_get_trial_link_url($row->token);
}


