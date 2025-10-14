<?php
/**
 * FlexPress Affiliate Helper Functions
 * 
 * Utility functions for the affiliate system.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if affiliate system is enabled
 * 
 * @return bool True if enabled
 */
function flexpress_is_affiliate_system_enabled() {
    $settings = get_option('flexpress_affiliate_settings', array());
    return !empty($settings['module_enabled']);
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function flexpress_get_client_ip() {
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Generate unique affiliate code
 * 
 * @return string Unique affiliate code
 */
function flexpress_generate_affiliate_code() {
    global $wpdb;
    
    do {
        $code = 'AFF' . strtoupper(wp_generate_password(8, false));
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE affiliate_code = %s",
            $code
        ));
    } while ($exists);
    
    return $code;
}

/**
 * Create affiliate referral URL
 * 
 * @param string $affiliate_code Affiliate code
 * @param string $promo_code Optional promo code
 * @return string Referral URL
 */
function flexpress_create_affiliate_referral_url($affiliate_code, $promo_code = '') {
    $base_url = home_url('/join');
    $params = array('ref' => $affiliate_code);
    
    if (!empty($promo_code)) {
        $params['promo'] = $promo_code;
    }
    
    return add_query_arg($params, $base_url);
}

/**
 * Get affiliate by code
 * 
 * @param string $affiliate_code Affiliate code
 * @return object|null Affiliate object or null
 */
function flexpress_get_affiliate_by_code($affiliate_code) {
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE affiliate_code = %s",
        $affiliate_code
    ));
}

/**
 * Get affiliate by ID
 * 
 * @param int $affiliate_id Affiliate ID
 * @return object|null Affiliate object or null
 */
function flexpress_get_affiliate_by_id($affiliate_id) {
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE id = %d",
        $affiliate_id
    ));
}

/**
 * Get promo code by code
 * 
 * @param string $promo_code Promo code
 * @return object|null Promo code object or null
 */
function flexpress_get_promo_code_by_code($promo_code) {
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliate_promo_codes WHERE code = %s AND status = 'active'",
        $promo_code
    ));
}

/**
 * Check if promo code is valid
 * 
 * @param string $promo_code Promo code
 * @return bool True if valid
 */
function flexpress_is_promo_code_valid($promo_code) {
    $promo_data = flexpress_get_promo_code_by_code($promo_code);
    
    if (!$promo_data) {
        return false;
    }
    
    // Check usage limit
    if ($promo_data->usage_limit && $promo_data->usage_count >= $promo_data->usage_limit) {
        return false;
    }
    
    // Check validity dates
    $now = current_time('mysql');
    
    if ($promo_data->valid_from && $now < $promo_data->valid_from) {
        return false;
    }
    
    if ($promo_data->valid_until && $now > $promo_data->valid_until) {
        return false;
    }
    
    return true;
}

/**
 * Apply promo code pricing
 * 
 * @param string $promo_code Promo code
 * @param array $pricing_data Original pricing data
 * @return array Modified pricing data
 */
function flexpress_apply_promo_code_pricing($promo_code, $pricing_data) {
    $promo_data = flexpress_get_promo_code_by_code($promo_code);
    
    if (!$promo_data || empty($promo_data->custom_pricing_json)) {
        return $pricing_data;
    }
    
    $custom_pricing = json_decode($promo_data->custom_pricing_json, true);
    
    if (!$custom_pricing) {
        return $pricing_data;
    }
    
    // Apply custom pricing overrides
    foreach ($custom_pricing as $plan_id => $plan_pricing) {
        if (isset($pricing_data[$plan_id])) {
            $pricing_data[$plan_id] = array_merge($pricing_data[$plan_id], $plan_pricing);
        }
    }
    
    return $pricing_data;
}

/**
 * Process affiliate commission
 * 
 * @param int $affiliate_id Affiliate ID
 * @param int $user_id User ID
 * @param string $transaction_type Transaction type
 * @param string $transaction_id Transaction ID
 * @param string $plan_id Plan ID
 * @param float $amount Transaction amount
 * @param int|null $promo_code_id Promo code ID
 * @param int|null $click_id Click ID
 * @return bool True on success
 */
function flexpress_process_affiliate_commission($affiliate_id, $user_id, $transaction_type, $transaction_id, $plan_id, $amount, $promo_code_id = null, $click_id = null) {
    global $wpdb;
    
    // Get affiliate data
    $affiliate = flexpress_get_affiliate_by_id($affiliate_id);
    
    if (!$affiliate || $affiliate->status !== 'active') {
        return false;
    }
    
    // Determine commission rate based on transaction type
    $commission_rate = 0;
    switch ($transaction_type) {
        case 'initial':
            $commission_rate = $affiliate->commission_initial;
            break;
        case 'rebill':
            $commission_rate = $affiliate->commission_rebill;
            break;
        case 'unlock':
            $commission_rate = $affiliate->commission_unlock;
            break;
        default:
            return false;
    }
    
    // Calculate commission amount
    $commission_amount = ($amount * $commission_rate) / 100;
    
    // Insert transaction record
    $result = $wpdb->insert(
        $wpdb->prefix . 'flexpress_affiliate_transactions',
        array(
            'affiliate_id' => $affiliate_id,
            'promo_code_id' => $promo_code_id,
            'user_id' => $user_id,
            'transaction_type' => $transaction_type,
            'transaction_id' => $transaction_id,
            'flowguard_transaction_id' => $transaction_id,
            'plan_id' => $plan_id,
            'revenue_amount' => $amount,
            'commission_rate' => $commission_rate,
            'commission_amount' => $commission_amount,
            'status' => 'pending',
            'click_id' => $click_id,
            'created_at' => current_time('mysql')
        ),
        array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%d', '%s')
    );
    
    if (!$result) {
        return false;
    }
    
    $transaction_record_id = $wpdb->insert_id;
    
    // Update affiliate stats
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}flexpress_affiliates SET 
         total_revenue = total_revenue + %f,
         pending_commission = pending_commission + %f,
         total_signups = total_signups + CASE WHEN %s = 'initial' THEN 1 ELSE 0 END,
         total_rebills = total_rebills + CASE WHEN %s = 'rebill' THEN 1 ELSE 0 END,
         total_unlocks = total_unlocks + CASE WHEN %s = 'unlock' THEN 1 ELSE 0 END
         WHERE id = %d",
        $amount,
        $commission_amount,
        $transaction_type,
        $transaction_type,
        $transaction_type,
        $affiliate_id
    ));
    
    // Update promo code stats if applicable
    if ($promo_code_id) {
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}flexpress_affiliate_promo_codes SET 
             usage_count = usage_count + 1,
             revenue_generated = revenue_generated + %f,
             commission_earned = commission_earned + %f
             WHERE id = %d",
            $amount,
            $commission_amount,
            $promo_code_id
        ));
    }
    
    // Mark click as converted if applicable
    if ($click_id) {
        $tracker = FlexPress_Affiliate_Tracker::get_instance();
        $tracker->mark_click_converted($click_id, $transaction_type, $amount);
    }
    
    return true;
}

/**
 * Approve affiliate commission
 * 
 * @param int $transaction_id Transaction ID
 * @return bool True on success
 */
function flexpress_approve_affiliate_commission($transaction_id) {
    global $wpdb;
    
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliate_transactions WHERE id = %d",
        $transaction_id
    ));
    
    if (!$transaction || $transaction->status !== 'pending') {
        return false;
    }
    
    // Update transaction status
    $result = $wpdb->update(
        $wpdb->prefix . 'flexpress_affiliate_transactions',
        array(
            'status' => 'approved',
            'approved_at' => current_time('mysql')
        ),
        array('id' => $transaction_id),
        array('%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        return false;
    }
    
    // Update affiliate balances
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}flexpress_affiliates SET 
         pending_commission = pending_commission - %f,
         approved_commission = approved_commission + %f
         WHERE id = %d",
        $transaction->commission_amount,
        $transaction->commission_amount,
        $transaction->affiliate_id
    ));
    
    return true;
}

/**
 * Process affiliate payout
 * 
 * @param int $affiliate_id Affiliate ID
 * @param array $payout_data Payout data
 * @return bool True on success
 */
function flexpress_process_affiliate_payout($affiliate_id, $payout_data) {
    global $wpdb;
    
    // Get affiliate data
    $affiliate = flexpress_get_affiliate_by_id($affiliate_id);
    
    if (!$affiliate || $affiliate->approved_commission < $affiliate->payout_threshold) {
        return false;
    }
    
    // Create payout record
    $result = $wpdb->insert(
        $wpdb->prefix . 'flexpress_affiliate_payouts',
        array(
            'affiliate_id' => $affiliate_id,
            'period_start' => $payout_data['period_start'],
            'period_end' => $payout_data['period_end'],
            'total_commissions' => $affiliate->approved_commission,
            'payout_amount' => $affiliate->approved_commission,
            'payout_method' => $affiliate->payout_method,
            'payout_details' => $affiliate->payout_details,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s')
    );
    
    if (!$result) {
        return false;
    }
    
    $payout_id = $wpdb->insert_id;
    
    // Update affiliate balances
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}flexpress_affiliates SET 
         paid_commission = paid_commission + %f,
         approved_commission = 0
         WHERE id = %d",
        $affiliate->approved_commission,
        $affiliate_id
    ));
    
    // Mark transactions as paid
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}flexpress_affiliate_transactions SET 
         status = 'paid',
         paid_at = %s
         WHERE affiliate_id = %d AND status = 'approved'",
        current_time('mysql'),
        $affiliate_id
    ));
    
    return $payout_id;
}

/**
 * Get affiliate statistics
 * 
 * @param int $affiliate_id Affiliate ID
 * @param string $period Period (7d, 30d, 90d, 1y)
 * @return array Statistics
 */
function flexpress_get_affiliate_statistics($affiliate_id, $period = '30d') {
    $tracker = FlexPress_Affiliate_Tracker::get_instance();
    return $tracker->get_affiliate_stats($affiliate_id, $period);
}

/**
 * Get all affiliates with filters
 * 
 * @param array $args Query arguments
 * @return array Affiliates array
 */
function flexpress_get_affiliates($args = array()) {
    $manager = FlexPress_Affiliate_Manager::get_instance();
    return $manager->get_affiliates($args);
}

/**
 * Get affiliate dashboard data
 * 
 * @param int $affiliate_id Affiliate ID
 * @return array Dashboard data
 */
function flexpress_get_affiliate_dashboard_data($affiliate_id) {
    $dashboard = FlexPress_Affiliate_Dashboard::get_instance();
    return $dashboard->get_dashboard_data();
}

/**
 * Send affiliate notification email
 * 
 * @param string $type Email type
 * @param object $affiliate Affiliate object
 * @param array $data Additional data
 * @return bool True on success
 */
function flexpress_send_affiliate_notification($type, $affiliate, $data = array()) {
    $site_name = get_bloginfo('name');
    $admin_email = get_option('admin_email');
    
    switch ($type) {
        case 'application':
            $subject = sprintf(__('New Affiliate Application - %s', 'flexpress'), $site_name);
            $message = sprintf(__("A new affiliate application has been submitted:\n\nName: %s\nEmail: %s\nWebsite: %s\nPayout Method: %s\n\nReview at: %s", 'flexpress'), 
                $data['affiliate_name'],
                $data['affiliate_email'],
                $data['affiliate_website'],
                $data['payout_method'],
                admin_url('admin.php?page=flexpress-affiliate-settings')
            );
            return wp_mail($admin_email, $subject, $message);
            
        case 'approval':
            $subject = sprintf(__('Your Affiliate Application Has Been Approved - %s', 'flexpress'), $site_name);
            $message = sprintf(__("Congratulations %s!\n\nYour affiliate application has been approved. You can now start promoting %s and earning commissions.\n\nYour Affiliate Code: %s\nYour Referral URL: %s\nDashboard: %s", 'flexpress'),
                $affiliate->display_name,
                $site_name,
                $affiliate->affiliate_code,
                $affiliate->referral_url,
                home_url('/affiliate-dashboard')
            );
            return wp_mail($affiliate->email, $subject, $message);
            
        case 'rejection':
            $subject = sprintf(__('Affiliate Application Update - %s', 'flexpress'), $site_name);
            $message = sprintf(__("Dear %s,\n\nThank you for your interest in our affiliate program. After careful review, we are unable to approve your application at this time.\n\n%s\n\nWe encourage you to reapply in the future as our program requirements may change.", 'flexpress'),
                $affiliate->display_name,
                !empty($data['reason']) ? "Reason: " . $data['reason'] : ''
            );
            return wp_mail($affiliate->email, $subject, $message);
            
        default:
            return false;
    }
}

/**
 * Format affiliate commission amount
 * 
 * @param float $amount Amount
 * @param string $currency Currency code
 * @return string Formatted amount
 */
function flexpress_format_affiliate_commission($amount, $currency = 'USD') {
    $symbols = array(
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'CAD' => 'C$',
        'AUD' => 'A$'
    );
    
    $symbol = $symbols[$currency] ?? $currency . ' ';
    
    return $symbol . number_format($amount, 2);
}

/**
 * Get affiliate system settings
 * 
 * @return array Settings array
 */
function flexpress_get_affiliate_settings() {
    return get_option('flexpress_affiliate_settings', array());
}

/**
 * Update affiliate system settings
 * 
 * @param array $settings Settings array
 * @return bool True on success
 */
function flexpress_update_affiliate_settings($settings) {
    return update_option('flexpress_affiliate_settings', $settings);
}

/**
 * Encrypt sensitive payout details
 *
 * @param string $plaintext Plain text
 * @return string Encrypted base64 string
 */
function flexpress_encrypt_payout_details($plaintext) {
    if ($plaintext === '' || $plaintext === null) {
        return '';
    }
    $key = hash('sha256', AUTH_KEY . AUTH_SALT, true);
    $iv = substr(hash('sha256', SECURE_AUTH_SALT . NONCE_SALT, true), 0, 16);
    $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if ($ciphertext === false) {
        return '';
    }
    return base64_encode($ciphertext);
}

/**
 * Decrypt sensitive payout details
 *
 * @param string $encoded Base64 string
 * @return string Decrypted text
 */
function flexpress_decrypt_payout_details($encoded) {
    if ($encoded === '' || $encoded === null) {
        return '';
    }
    $key = hash('sha256', AUTH_KEY . AUTH_SALT, true);
    $iv = substr(hash('sha256', SECURE_AUTH_SALT . NONCE_SALT, true), 0, 16);
    $raw = base64_decode($encoded, true);
    if ($raw === false) {
        return '';
    }
    $plaintext = openssl_decrypt($raw, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $plaintext === false ? '' : $plaintext;
}