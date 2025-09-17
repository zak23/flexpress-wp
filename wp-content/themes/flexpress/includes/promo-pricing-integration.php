<?php
/**
 * Promo Codes & Pricing Integration
 * 
 * Bridges the existing pricing system with the new centralized promo codes system
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced promo code validation that works with both systems
 *
 * @param string $promo_code The promo code to validate
 * @param string $plan_id The plan ID (optional)
 * @param float $amount The order amount (optional)
 * @return array Result with success status, discount info, and unlocked plans
 */
function flexpress_validate_enhanced_promo_code($promo_code, $plan_id = '', $amount = 0.00) {
    if (empty($promo_code)) {
        return array(
            'success' => false,
            'message' => 'Please enter a promo code',
            'discount_amount' => 0.00,
            'final_amount' => $amount,
            'unlocked_plans' => array(),
            'code_type' => 'none'
        );
    }
    
    $code = strtoupper(trim($promo_code));
    
    // Check centralized promo codes system first
    $centralized_result = flexpress_validate_centralized_promo_code($code, $plan_id, $amount);
    if ($centralized_result['success']) {
        return $centralized_result;
    }
    
    // Fall back to existing plan-specific promo codes
    $legacy_result = flexpress_validate_legacy_promo_code($code, $plan_id);
    if ($legacy_result['success']) {
        return $legacy_result;
    }
    
    return array(
        'success' => false,
        'message' => 'Invalid promo code',
        'discount_amount' => 0.00,
        'final_amount' => $amount,
        'unlocked_plans' => array(),
        'code_type' => 'none'
    );
}

/**
 * Validate centralized promo codes (new system)
 *
 * @param string $promo_code The promo code
 * @param string $plan_id The plan ID
 * @param float $amount The order amount
 * @return array Validation result
 */
function flexpress_validate_centralized_promo_code($promo_code, $plan_id, $amount) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'flexpress_promo_codes';
    
    // Check if centralized promo codes table exists
    if (!$wpdb->get_var("SHOW TABLES LIKE '$table_name'")) {
        return array('success' => false);
    }
    
    $promo = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE code = %s AND status = 'active'",
        $promo_code
    ));
    
    if (!$promo) {
        return array('success' => false);
    }
    
    // Check validity dates
    $now = current_time('mysql');
    if ($promo->valid_from && $promo->valid_from > $now) {
        return array(
            'success' => false,
            'message' => 'This promo code is not yet valid'
        );
    }
    
    if ($promo->valid_until && $promo->valid_until < $now) {
        return array(
            'success' => false,
            'message' => 'This promo code has expired'
        );
    }
    
    // Check usage limits
    if ($promo->usage_limit > 0) {
        $usage_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}flexpress_promo_usage WHERE promo_code = %s",
            $promo_code
        ));
        
        if ($usage_count >= $promo->usage_limit) {
            return array(
                'success' => false,
                'message' => 'This promo code has reached its usage limit'
            );
        }
    }
    
    // Check minimum amount
    if ($promo->minimum_amount > 0 && $amount < $promo->minimum_amount) {
        return array(
            'success' => false,
            'message' => sprintf('Minimum order amount of $%.2f required', $promo->minimum_amount)
        );
    }
    
    // Check plan restrictions
    if (!empty($promo->applicable_plans)) {
        $applicable_plans = explode(',', $promo->applicable_plans);
        if (!in_array('all', $applicable_plans) && !in_array($plan_id, $applicable_plans)) {
            return array(
                'success' => false,
                'message' => 'This promo code is not valid for the selected plan'
            );
        }
    }
    
    // Calculate discount
    $discount_amount = 0.00;
    switch ($promo->discount_type) {
        case 'percentage':
            $discount_amount = ($amount * $promo->discount_value) / 100;
            if ($promo->maximum_discount > 0) {
                $discount_amount = min($discount_amount, $promo->maximum_discount);
            }
            break;
        case 'fixed':
            $discount_amount = $promo->discount_value;
            break;
        case 'free_trial':
            // For free trials, we'll handle this in the payment processing
            $discount_amount = $amount; // Full discount for free trial
            break;
    }
    
    $final_amount = max(0, $amount - $discount_amount);
    
    return array(
        'success' => true,
        'message' => 'Promo code applied successfully',
        'discount_amount' => $discount_amount,
        'final_amount' => $final_amount,
        'unlocked_plans' => array(), // Centralized codes don't unlock plans
        'code_type' => 'centralized',
        'promo_data' => $promo
    );
}

/**
 * Validate legacy plan-specific promo codes (existing system)
 *
 * @param string $promo_code The promo code
 * @param string $plan_id The plan ID
 * @return array Validation result
 */
function flexpress_validate_legacy_promo_code($promo_code, $plan_id) {
    // Use existing function
    $unlocked_plans = flexpress_get_plans_for_promo_code($promo_code);
    
    if (empty($unlocked_plans)) {
        return array('success' => false);
    }
    
    return array(
        'success' => true,
        'message' => 'Promo code unlocks special plans',
        'discount_amount' => 0.00,
        'final_amount' => 0.00, // Will be calculated based on unlocked plan
        'unlocked_plans' => $unlocked_plans,
        'code_type' => 'legacy'
    );
}

/**
 * Apply promo code to pricing plans
 *
 * @param string $promo_code The promo code
 * @param array $plans The pricing plans array
 * @return array Modified plans with discounts applied
 */
function flexpress_apply_promo_to_plans($promo_code, $plans) {
    if (empty($promo_code)) {
        return $plans;
    }
    
    $code = strtoupper(trim($promo_code));
    
    foreach ($plans as $plan_id => &$plan) {
        // Check if this plan is unlocked by the promo code
        $validation = flexpress_validate_enhanced_promo_code($code, $plan_id, $plan['price']);
        
        if ($validation['success']) {
            if ($validation['code_type'] === 'centralized') {
                // Apply discount to plan price
                $plan['original_price'] = $plan['price'];
                $plan['price'] = $validation['final_amount'];
                $plan['discount_amount'] = $validation['discount_amount'];
                $plan['promo_applied'] = true;
                $plan['promo_code'] = $code;
            } elseif ($validation['code_type'] === 'legacy') {
                // Plan is unlocked by legacy promo code
                $plan['unlocked_by_promo'] = true;
                $plan['promo_code'] = $code;
            }
        }
    }
    
    return $plans;
}

/**
 * Get pricing plans with promo code applied
 *
 * @param string $promo_code The promo code to apply
 * @param bool $active_only Whether to return only active plans
 * @return array Pricing plans with discounts applied
 */
function flexpress_get_pricing_plans_with_promo($promo_code = '', $active_only = true) {
    // Get plans using existing function
    $plans = flexpress_get_pricing_plans($active_only, $promo_code);
    
    // Apply centralized promo codes if provided
    if (!empty($promo_code)) {
        $plans = flexpress_apply_promo_to_plans($promo_code, $plans);
    }
    
    return $plans;
}

/**
 * Track promo code usage (enhanced version)
 *
 * @param string $promo_code The promo code used
 * @param int $user_id The user ID
 * @param string $plan_id The plan ID
 * @param string $transaction_id The transaction ID
 * @param float $original_amount The original amount
 * @param float $discount_amount The discount amount
 * @param float $final_amount The final amount
 */
function flexpress_track_enhanced_promo_usage($promo_code, $user_id, $plan_id, $transaction_id, $original_amount, $discount_amount, $final_amount) {
    if (empty($promo_code)) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'flexpress_promo_usage';
    
    // Check if centralized promo codes table exists
    $promo_codes_table = $wpdb->prefix . 'flexpress_promo_codes';
    if ($wpdb->get_var("SHOW TABLES LIKE '$promo_codes_table'")) {
        // Get promo code ID for centralized codes
        $promo_code_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $promo_codes_table WHERE code = %s",
            $promo_code
        ));
    } else {
        $promo_code_id = 0; // Legacy codes
    }
    
    $wpdb->insert(
        $table_name,
        array(
            'promo_code_id' => $promo_code_id,
            'promo_code' => strtoupper(trim($promo_code)),
            'user_id' => $user_id,
            'order_id' => $transaction_id,
            'plan_id' => $plan_id,
            'original_amount' => $original_amount,
            'discount_amount' => $discount_amount,
            'final_amount' => $final_amount,
            'used_at' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ),
        array('%d', '%s', '%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s')
    );
    
    // Update usage count for centralized codes
    if ($promo_code_id > 0) {
        $wpdb->query($wpdb->prepare(
            "UPDATE $promo_codes_table SET usage_count = usage_count + 1 WHERE id = %d",
            $promo_code_id
        ));
    }
}

/**
 * Get promo code statistics
 *
 * @param string $promo_code Optional specific promo code
 * @return array Statistics array
 */
function flexpress_get_enhanced_promo_stats($promo_code = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'flexpress_promo_usage';
    
    if (!$wpdb->get_var("SHOW TABLES LIKE '$table_name'")) {
        return array(
            'total_usage' => 0,
            'total_discounts' => 0.00,
            'total_revenue' => 0.00
        );
    }
    
    if ($promo_code) {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE promo_code = %s ORDER BY used_at DESC",
            strtoupper(trim($promo_code))
        ));
    } else {
        $results = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY used_at DESC");
    }
    
    $total_usage = count($results);
    $total_discounts = array_sum(array_column($results, 'discount_amount'));
    $total_revenue = array_sum(array_column($results, 'final_amount'));
    
    return array(
        'total_usage' => $total_usage,
        'total_discounts' => $total_discounts,
        'total_revenue' => $total_revenue,
        'recent_usage' => array_slice($results, 0, 10)
    );
}
