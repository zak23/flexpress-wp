<?php
/**
 * Flowguard Integration Functions
 * 
 * Helper functions for integrating Flowguard payment processing
 * with FlexPress theme functionality.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get Flowguard API instance
 * 
 * @return FlexPress_Flowguard_API|false API instance or false on error
 */
function flexpress_get_flowguard_api() {
    $flowguard_settings = get_option('flexpress_flowguard_settings', []);
    
    if (empty($flowguard_settings['shop_id']) || empty($flowguard_settings['signature_key'])) {
        return false;
    }
    
    return new FlexPress_Flowguard_API(
        $flowguard_settings['shop_id'],
        $flowguard_settings['signature_key'],
        $flowguard_settings['environment'] ?? 'sandbox'
    );
}

/**
 * Create Flowguard subscription
 * 
 * @param int $user_id User ID
 * @param int $plan_id Plan ID
 * @return array Response data
 */
function flexpress_flowguard_create_subscription($user_id, $plan_id) {
    $plan = flexpress_get_pricing_plan($plan_id);
    if (!$plan) {
        return ['success' => false, 'error' => 'Invalid plan'];
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid user'];
    }
    
    $api = flexpress_get_flowguard_api();
    if (!$api) {
        return ['success' => false, 'error' => 'Flowguard API not configured'];
    }
    
    $subscription_data = [
        'priceAmount' => number_format($plan['price'], 2, '.', ''),
        'priceCurrency' => flexpress_flowguard_convert_currency_symbol_to_code($plan['currency']),
        'successUrl' => home_url('/payment-success'),
        'declineUrl' => home_url('/payment-declined'),
        'postbackUrl' => home_url('/wp-admin/admin-ajax.php?action=flowguard_webhook'),
        'email' => $user->user_email,
        'subscriptionType' => $plan['plan_type'] === 'one_time' ? 'one-time' : 'recurring',
        'period' => flexpress_format_plan_duration_for_flowguard($plan['duration'], $plan['duration_unit']),
        'referenceId' => 'user_' . $user_id . '_plan_' . $plan_id
    ];
    
    // Add trial information if enabled
    if (!empty($plan['trial_enabled'])) {
        $subscription_data['trialAmount'] = number_format($plan['trial_price'], 2, '.', '');
        $subscription_data['trialPeriod'] = flexpress_format_plan_duration_for_flowguard(
            $plan['trial_duration'], 
            $plan['trial_duration_unit']
        );
    }
    
    $result = $api->start_subscription($subscription_data);
    
    if ($result['success']) {
        // Store session data for webhook processing
        update_user_meta($user_id, 'flowguard_session_id', $result['session_id']);
        update_user_meta($user_id, 'flowguard_plan_id', $plan_id);
        update_user_meta($user_id, 'flowguard_reference_id', $subscription_data['referenceId']);
        
        return [
            'success' => true,
            'session_id' => $result['session_id'],
            'payment_url' => home_url('/payment?session_id=' . $result['session_id'])
        ];
    }
    
    return $result;
}

/**
 * Create Flowguard PPV purchase
 * 
 * @param int $user_id User ID
 * @param int $episode_id Episode ID
 * @return array Response data
 */
function flexpress_flowguard_create_ppv_purchase($user_id, $episode_id, $final_price = null) {
    $episode = get_post($episode_id);
    if (!$episode || $episode->post_type !== 'episode') {
        return ['success' => false, 'error' => 'Invalid episode'];
    }
    
    // Get PPV price from episode (using episode_price ACF field)
    $ppv_price = get_field('episode_price', $episode_id);
    if (!$ppv_price || $ppv_price <= 0) {
        return ['success' => false, 'error' => 'Episode not available for PPV'];
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid user'];
    }
    
    $api = flexpress_get_flowguard_api();
    if (!$api) {
        return ['success' => false, 'error' => 'Flowguard API not configured'];
    }
    
    // Use final price if provided (includes member discounts), otherwise use base PPV price
    $price_to_charge = $final_price !== null ? $final_price : $ppv_price;
    
    // Ensure minimum price for Flowguard ($2.95)
    if ($price_to_charge < 2.95) {
        $price_to_charge = 2.95;
    }
    
    // Create unique transaction reference
    $transaction_ref = 'ppv_' . $episode_id . '_' . $user_id . '_' . time();
    
    $purchase_data = [
        'priceAmount' => number_format($price_to_charge, 2, '.', ''),
        'priceCurrency' => 'USD',
        'successUrl' => home_url('/payment-success?episode_id=' . $episode_id . '&ref=' . $transaction_ref),
        'declineUrl' => get_permalink($episode_id) . '?payment=cancelled',
        'postbackUrl' => home_url('/wp-admin/admin-ajax.php?action=flowguard_webhook'),
        'email' => $user->user_email,
        'referenceId' => $transaction_ref
    ];
    
    $result = $api->start_purchase($purchase_data);
    
    if ($result['success']) {
        // Store session data for webhook processing
        update_user_meta($user_id, 'flowguard_ppv_session_id', $result['session_id']);
        update_user_meta($user_id, 'flowguard_ppv_episode_id', $episode_id);
        update_user_meta($user_id, 'flowguard_ppv_reference_id', $transaction_ref);
        update_user_meta($user_id, 'flowguard_ppv_price', $price_to_charge);
        
        // Store pending transaction for validation
        update_user_meta($user_id, 'pending_ppv_' . $transaction_ref, [
            'episode_id' => $episode_id,
            'price' => $price_to_charge,
            'base_price' => $ppv_price,
            'created' => current_time('mysql')
        ]);
        
        return [
            'success' => true,
            'session_id' => $result['session_id'],
            'payment_url' => home_url('/payment?session_id=' . $result['session_id'])
        ];
    }
    
    return $result;
}

/**
 * Format plan duration for Flowguard (ISO 8601)
 * 
 * @param int $duration Duration value
 * @param string $duration_unit Duration unit (days, months, years)
 * @return string ISO 8601 duration format
 */
function flexpress_format_plan_duration_for_flowguard($duration, $duration_unit) {
    $duration = intval($duration);
    
    switch ($duration_unit) {
        case 'days':
            return 'P' . $duration . 'D';
        case 'months':
            return 'P' . $duration . 'M';
        case 'years':
            return 'P' . $duration . 'Y';
        default:
            return 'P30D';
    }
}

/**
 * Convert currency symbol to currency code for Flowguard
 * 
 * @param string $currency_symbol Currency symbol
 * @return string Currency code
 */
function flexpress_flowguard_convert_currency_symbol_to_code($currency_symbol) {
    $currency_map = [
        '$' => 'USD',
        '€' => 'EUR',
        '£' => 'GBP',
        'A$' => 'AUD',
        'C$' => 'CAD',
        'CHF' => 'CHF',
        'kr' => 'DKK',
        'NOK' => 'NOK',
        'SEK' => 'SEK'
    ];
    
    return $currency_map[$currency_symbol] ?? 'USD';
}

/**
 * Get user ID from Flowguard reference ID
 * 
 * @param string $reference_id Reference ID
 * @return int User ID or 0 if not found
 */
function flexpress_flowguard_get_user_from_reference($reference_id) {
    if (empty($reference_id)) {
        return 0;
    }
    
    // Handle new PPV reference format: ppv_123_456_789 (ppv_episodeId_userId_timestamp)
    if (preg_match('/^ppv_(\d+)_(\d+)_\d+$/', $reference_id, $matches)) {
        return intval($matches[2]); // Return user ID
    }
    
    // Handle legacy format: "user_123_plan_456" or "ppv_user_123_episode_456"
    if (preg_match('/user_(\d+)/', $reference_id, $matches)) {
        return intval($matches[1]);
    }
    
    return 0;
}

/**
 * Cancel Flowguard subscription
 * 
 * @param int $user_id User ID
 * @param string $cancelled_by Who cancelled (merchant, buyer)
 * @return array Response data
 */
function flexpress_flowguard_cancel_subscription($user_id, $cancelled_by = 'merchant') {
    $sale_id = get_user_meta($user_id, 'flowguard_sale_id', true);
    if (empty($sale_id)) {
        return ['success' => false, 'error' => 'No active subscription found'];
    }
    
    $api = flexpress_get_flowguard_api();
    if (!$api) {
        return ['success' => false, 'error' => 'Flowguard API not configured'];
    }
    
    $result = $api->cancel_subscription($sale_id, $cancelled_by);
    
    if ($result['success']) {
        // Update user membership status
        flexpress_update_membership_status($user_id, 'cancelled');
        
        // Log activity
        if (class_exists('FlexPress_Activity_Logger')) {
            FlexPress_Activity_Logger::log_activity(
                $user_id,
                'flowguard_subscription_cancelled',
                'Subscription cancelled via Flowguard',
                ['sale_id' => $sale_id, 'cancelled_by' => $cancelled_by]
            );
        }
    }
    
    return $result;
}

/**
 * Get Flowguard subscription status
 * 
 * @param int $user_id User ID
 * @return array Subscription status data
 */
function flexpress_flowguard_get_subscription_status($user_id) {
    $sale_id = get_user_meta($user_id, 'flowguard_sale_id', true);
    $transaction_id = get_user_meta($user_id, 'flowguard_transaction_id', true);
    $membership_status = get_user_meta($user_id, 'membership_status', true);
    $next_rebill_date = get_user_meta($user_id, 'next_rebill_date', true);
    $membership_expires = get_user_meta($user_id, 'membership_expires', true);
    
    return [
        'sale_id' => $sale_id,
        'transaction_id' => $transaction_id,
        'membership_status' => $membership_status,
        'next_rebill_date' => $next_rebill_date,
        'membership_expires' => $membership_expires,
        'has_active_subscription' => !empty($sale_id) && $membership_status === 'active'
    ];
}

/**
 * Check if user has active Flowguard subscription
 * 
 * @param int $user_id User ID
 * @return bool True if user has active subscription
 */
function flexpress_flowguard_has_active_subscription($user_id) {
    $status = flexpress_flowguard_get_subscription_status($user_id);
    return $status['has_active_subscription'];
}

/**
 * Get Flowguard webhook URL
 * 
 * @return string Webhook URL
 */
function flexpress_flowguard_get_webhook_url() {
    return home_url('/wp-admin/admin-ajax.php?action=flowguard_webhook');
}

/**
 * Log Flowguard activity
 * 
 * @param int $user_id User ID
 * @param string $action Action performed
 * @param string $description Description of action
 * @param array $data Additional data
 */
function flexpress_flowguard_log_activity($user_id, $action, $description, $data = []) {
    if (class_exists('FlexPress_Activity_Logger')) {
        FlexPress_Activity_Logger::log_activity($user_id, $action, $description, $data);
    }
    
    // Also log to WordPress error log for debugging
    error_log("Flowguard Activity - User {$user_id}: {$action} - {$description}");
    if (!empty($data)) {
        error_log("Flowguard Activity Data: " . print_r($data, true));
    }
}

/**
 * Store Flowguard transaction
 * 
 * @param array $transaction_data Transaction data
 * @return int|false Transaction ID or false on error
 */
function flexpress_flowguard_store_transaction($transaction_data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_flowguard_transactions';
    
    $result = $wpdb->insert(
        $table_name,
        [
            'user_id' => $transaction_data['user_id'],
            'transaction_id' => $transaction_data['transaction_id'],
            'session_id' => $transaction_data['session_id'],
            'sale_id' => $transaction_data['sale_id'] ?? '',
            'amount' => $transaction_data['amount'],
            'currency' => $transaction_data['currency'],
            'status' => $transaction_data['status'],
            'order_type' => $transaction_data['order_type'],
            'reference_id' => $transaction_data['reference_id'] ?? '',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ],
        [
            '%d', // user_id
            '%s', // transaction_id
            '%s', // session_id
            '%s', // sale_id
            '%f', // amount
            '%s', // currency
            '%s', // status
            '%s', // order_type
            '%s', // reference_id
            '%s', // created_at
            '%s'  // updated_at
        ]
    );
    
    if ($result === false) {
        error_log('Flowguard: Failed to store transaction - ' . $wpdb->last_error);
        return false;
    }
    
    return $wpdb->insert_id;
}

/**
 * Update Flowguard transaction status
 * 
 * @param string $transaction_id Transaction ID
 * @param string $status New status
 * @param array $additional_data Additional data to update
 * @return bool True on success, false on error
 */
function flexpress_flowguard_update_transaction($transaction_id, $status, $additional_data = []) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_flowguard_transactions';
    
    $update_data = array_merge([
        'status' => $status,
        'updated_at' => current_time('mysql')
    ], $additional_data);
    
    $result = $wpdb->update(
        $table_name,
        $update_data,
        ['transaction_id' => $transaction_id],
        array_fill(0, count($update_data), '%s'),
        ['%s']
    );
    
    if ($result === false) {
        error_log('Flowguard: Failed to update transaction - ' . $wpdb->last_error);
        return false;
    }
    
    return true;
}

/**
 * Get Flowguard transaction by ID
 * 
 * @param string $transaction_id Transaction ID
 * @return array|false Transaction data or false if not found
 */
function flexpress_flowguard_get_transaction($transaction_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_flowguard_transactions';
    
    $transaction = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE transaction_id = %s",
            $transaction_id
        ),
        ARRAY_A
    );
    
    return $transaction ?: false;
}
