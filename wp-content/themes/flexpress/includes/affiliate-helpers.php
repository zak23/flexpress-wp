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
function flexpress_generate_affiliate_code($seed = '') {
    global $wpdb;

    $base = '';
    if ($seed !== '') {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $seed));
        $base = substr($base, 0, 15);
    }

    do {
        $code = $base ?: 'AFF' . strtoupper(wp_generate_password(8, false));
        if ($base) {
            $code .= wp_rand(100, 999);
        }
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE affiliate_code = %s",
            $code
        ));
    } while ($exists);
    
    return $code;
}

/**
 * Decode the affiliate tracking cookie payload.
 *
 * @param string $cookie_value Raw cookie value.
 * @return array|null Decoded tracking data.
 */
function flexpress_decode_affiliate_tracking_cookie($cookie_value) {
    if (empty($cookie_value)) {
        return null;
    }

    $decoded = base64_decode(sanitize_text_field(wp_unslash($cookie_value)), true);
    if ($decoded === false) {
        return null;
    }

    $data = json_decode($decoded, true);
    if (!is_array($data) || empty($data['affiliate_id']) || empty($data['timestamp'])) {
        return null;
    }

    if ((time() - intval($data['timestamp'])) > 30 * DAY_IN_SECONDS) {
        return null;
    }

    return array(
        'affiliate_id' => intval($data['affiliate_id']),
        'promo_code_id' => !empty($data['promo_code_id']) ? intval($data['promo_code_id']) : null,
        'click_id' => !empty($data['click_id']) ? intval($data['click_id']) : null,
        'timestamp' => intval($data['timestamp']),
    );
}

/**
 * Get persisted affiliate tracking data for a user.
 *
 * @param int $user_id User ID.
 * @return array|null Tracking data.
 */
function flexpress_get_user_affiliate_tracking_data($user_id) {
    $tracking = get_user_meta($user_id, 'affiliate_tracking_data', true);
    if (is_array($tracking) && !empty($tracking['affiliate_id'])) {
        return array(
            'affiliate_id' => intval($tracking['affiliate_id']),
            'promo_code_id' => !empty($tracking['promo_code_id']) ? intval($tracking['promo_code_id']) : null,
            'click_id' => !empty($tracking['click_id']) ? intval($tracking['click_id']) : null,
            'timestamp' => !empty($tracking['timestamp']) ? intval($tracking['timestamp']) : 0,
        );
    }

    $legacy = get_user_meta($user_id, 'affiliate_referred_by', true);
    if (is_array($legacy) && !empty($legacy['affiliate_id'])) {
        return array(
            'affiliate_id' => intval($legacy['affiliate_id']),
            'promo_code_id' => !empty($legacy['promo_code_id']) ? intval($legacy['promo_code_id']) : null,
            'click_id' => !empty($legacy['click_id']) ? intval($legacy['click_id']) : null,
            'timestamp' => !empty($legacy['timestamp']) ? intval($legacy['timestamp']) : 0,
        );
    }

    return null;
}

/**
 * Get the affiliate code stored for a user.
 *
 * @param int $user_id User ID.
 * @return string Affiliate code.
 */
function flexpress_get_user_affiliate_code($user_id) {
    $code = get_user_meta($user_id, 'affiliate_referred_code', true);
    if ($code) {
        return sanitize_text_field($code);
    }

    $tracking = flexpress_get_user_affiliate_tracking_data($user_id);
    if (!$tracking || empty($tracking['affiliate_id'])) {
        return '';
    }

    $affiliate = flexpress_get_affiliate_by_id(intval($tracking['affiliate_id']));
    return $affiliate ? sanitize_text_field($affiliate->affiliate_code) : '';
}

/**
 * Link an affiliate row to a WordPress user, creating one if needed.
 *
 * @param object $affiliate Affiliate row.
 * @return int|WP_Error User ID or error.
 */
function flexpress_link_or_create_affiliate_user($affiliate) {
    $user = null;

    if (!empty($affiliate->user_id)) {
        $user = get_user_by('id', intval($affiliate->user_id));
    }

    if (!$user && !empty($affiliate->email)) {
        $user = get_user_by('email', $affiliate->email);
    }

    if (!$user) {
        $username_base = sanitize_user(current(explode('@', $affiliate->email)), true);
        if (!$username_base) {
            $username_base = sanitize_user($affiliate->affiliate_code, true);
        }

        $username = $username_base;
        $suffix = 1;
        while (username_exists($username)) {
            $username = $username_base . $suffix;
            $suffix++;
        }

        $user_id = wp_create_user($username, wp_generate_password(20, true), $affiliate->email);
        if (is_wp_error($user_id)) {
            return $user_id;
        }

        wp_update_user(array(
            'ID' => intval($user_id),
            'display_name' => $affiliate->display_name,
        ));
        $user = get_user_by('id', intval($user_id));

        wp_mail(
            $affiliate->email,
            sprintf(__('Your %s affiliate account has been created', 'flexpress'), get_bloginfo('name')),
            sprintf(
                __("Your affiliate account is ready.\n\nUsername: %s\nSet your password here: %s\n\nDashboard: %s", 'flexpress'),
                $username,
                wp_lostpassword_url(),
                home_url('/affiliate-dashboard')
            )
        );
    }

    if (!$user) {
        return new WP_Error('affiliate_user_failed', __('Unable to link or create affiliate user.', 'flexpress'));
    }

    $user->add_role('affiliate_user');
    return intval($user->ID);
}

/**
 * Register an affiliate application using the v1 affiliate schema.
 *
 * @param array $data Affiliate application data.
 * @return array|WP_Error Created affiliate data or error.
 */
function flexpress_register_affiliate($data) {
    global $wpdb;

    if (!flexpress_is_affiliate_system_enabled()) {
        return new WP_Error('affiliate_disabled', __('Affiliate system is currently disabled.', 'flexpress'));
    }

    $display_name = sanitize_text_field($data['display_name'] ?? $data['affiliate_name'] ?? '');
    $email = sanitize_email($data['email'] ?? $data['affiliate_email'] ?? '');
    $affiliate_code = sanitize_text_field($data['affiliate_code'] ?? $data['desired_affiliate_id'] ?? '');
    $website = esc_url_raw($data['website'] ?? $data['referral_url'] ?? $data['affiliate_website'] ?? '');
    $notes = sanitize_textarea_field($data['notes'] ?? $data['marketing_experience'] ?? '');
    $user_id = intval($data['user_id'] ?? 0);
    $settings = get_option('flexpress_affiliate_settings', array());

    if (!$display_name || !$email || !$affiliate_code) {
        return new WP_Error('missing_fields', __('Display name, email, and affiliate code are required.', 'flexpress'));
    }

    if (!is_email($email)) {
        return new WP_Error('invalid_email', __('Please enter a valid email address.', 'flexpress'));
    }

    if (!preg_match('/^[A-Za-z0-9-]{3,20}$/', $affiliate_code)) {
        return new WP_Error('invalid_affiliate_code', __('Affiliate code must be 3-20 characters and contain only letters, numbers, and hyphens.', 'flexpress'));
    }

    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE affiliate_code = %s OR email = %s LIMIT 1",
        $affiliate_code,
        $email
    ));
    if ($existing) {
        return new WP_Error('affiliate_exists', __('An affiliate with this email or code already exists.', 'flexpress'));
    }

    if (!$user_id) {
        $user = get_user_by('email', $email);
        if ($user) {
            $user_id = intval($user->ID);
        }
    }

    $payout_method = sanitize_text_field($data['payout_method'] ?? 'paypal');
    $allowed_methods = array('paypal', 'crypto', 'aus_bank_transfer', 'yoursafe', 'ach', 'swift');
    if (!in_array($payout_method, $allowed_methods, true)) {
        $payout_method = 'paypal';
    }

    $payout_details = (string) ($data['payout_details'] ?? '');
    $encrypted_payout_details = $payout_details !== '' ? flexpress_encrypt_payout_details($payout_details) : '';
    $status = !empty($settings['auto_approve_affiliates']) ? 'active' : 'pending';
    $referral_url = flexpress_create_affiliate_referral_url($affiliate_code);
    $application_data = array_merge($data, array(
        'ip_address' => flexpress_get_client_ip(),
        'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        'submitted_at' => current_time('mysql'),
    ));

    $inserted = $wpdb->insert(
        $wpdb->prefix . 'flexpress_affiliates',
        array(
            'user_id' => $user_id,
            'affiliate_code' => $affiliate_code,
            'display_name' => $display_name,
            'email' => $email,
            'website' => $website,
            'payout_method' => $payout_method,
            'payout_details' => $encrypted_payout_details,
            'tax_info' => sanitize_textarea_field($data['tax_info'] ?? ''),
            'commission_initial' => floatval($settings['commission_rate'] ?? 25.00),
            'commission_rebill' => floatval($settings['rebill_commission_rate'] ?? 10.00),
            'commission_unlock' => floatval($settings['unlock_commission_rate'] ?? 15.00),
            'payout_threshold' => floatval($settings['minimum_payout'] ?? 100.00),
            'status' => $status,
            'referral_url' => $referral_url,
            'application_data' => wp_json_encode($application_data),
            'notes' => $notes,
            'created_at' => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s')
    );

    if (!$inserted) {
        return new WP_Error('insert_failed', __('Failed to submit application. Please try again.', 'flexpress'));
    }

    if ($status === 'active') {
        $affiliate = flexpress_get_affiliate_by_id(intval($wpdb->insert_id));
        $linked_user_id = flexpress_link_or_create_affiliate_user($affiliate);
        if (!is_wp_error($linked_user_id)) {
            $wpdb->update(
                $wpdb->prefix . 'flexpress_affiliates',
                array('user_id' => intval($linked_user_id)),
                array('id' => intval($wpdb->insert_id)),
                array('%d'),
                array('%d')
            );
        }
    }

    return array(
        'id' => intval($wpdb->insert_id),
        'status' => $status,
        'affiliate_code' => $affiliate_code,
        'referral_url' => $referral_url,
    );
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
    $params = array('aff' => $affiliate_code); // Use new ?aff parameter
    
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

    $transaction_id = sanitize_text_field($transaction_id);
    if ($transaction_id === '' || $amount <= 0) {
        return false;
    }

    $duplicate = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}flexpress_affiliate_transactions WHERE flowguard_transaction_id = %s OR transaction_id = %s LIMIT 1",
        $transaction_id,
        $transaction_id
    ));
    if ($duplicate) {
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
         total_commission = total_commission + %f,
         pending_commission = pending_commission + %f,
         total_signups = total_signups + CASE WHEN %s = 'initial' THEN 1 ELSE 0 END,
         total_rebills = total_rebills + CASE WHEN %s = 'rebill' THEN 1 ELSE 0 END,
         total_unlocks = total_unlocks + CASE WHEN %s = 'unlock' THEN 1 ELSE 0 END
         WHERE id = %d",
        $amount,
        $commission_amount,
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

    $approved = $wpdb->get_row($wpdb->prepare(
        "SELECT
            COALESCE(SUM(commission_amount), 0) AS amount,
            MIN(DATE(created_at)) AS period_start,
            MAX(DATE(created_at)) AS period_end
         FROM {$wpdb->prefix}flexpress_affiliate_transactions
         WHERE affiliate_id = %d AND status = 'approved'",
        $affiliate_id
    ));
    $payout_amount = floatval($approved->amount ?? 0);
    if ($payout_amount < floatval($affiliate->payout_threshold)) {
        return false;
    }

    $period_start = !empty($approved->period_start) ? $approved->period_start : ($payout_data['period_start'] ?? date('Y-m-01'));
    $period_end = !empty($approved->period_end) ? $approved->period_end : ($payout_data['period_end'] ?? date('Y-m-t'));
    
    // Create payout record
    $result = $wpdb->insert(
        $wpdb->prefix . 'flexpress_affiliate_payouts',
        array(
            'affiliate_id' => $affiliate_id,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'total_commissions' => $payout_amount,
            'payout_amount' => $payout_amount,
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
    
    return $payout_id;
}

/**
 * Complete a pending/processing affiliate payout and mark covered commissions paid.
 *
 * @param int $payout_id Payout ID.
 * @param string $reference_id External payment reference.
 * @param string $notes Optional notes.
 * @return bool True on success.
 */
function flexpress_complete_affiliate_payout($payout_id, $reference_id = '', $notes = '') {
    global $wpdb;

    $payout = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliate_payouts WHERE id = %d",
        $payout_id
    ));
    if (!$payout || !in_array($payout->status, array('pending', 'processing'), true)) {
        return false;
    }

    $paid_at = current_time('mysql');
    $updated = $wpdb->update(
        $wpdb->prefix . 'flexpress_affiliate_payouts',
        array(
            'status' => 'completed',
            'reference_id' => sanitize_text_field($reference_id),
            'notes' => sanitize_textarea_field($notes),
            'processed_at' => $paid_at,
        ),
        array('id' => $payout_id),
        array('%s', '%s', '%s', '%s'),
        array('%d')
    );
    if ($updated === false) {
        return false;
    }

    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}flexpress_affiliate_transactions
         SET status = 'paid', paid_at = %s
         WHERE affiliate_id = %d
           AND status = 'approved'
           AND created_at >= %s
           AND created_at <= %s",
        $paid_at,
        intval($payout->affiliate_id),
        $payout->period_start . ' 00:00:00',
        $payout->period_end . ' 23:59:59'
    ));

    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}flexpress_affiliates
         SET paid_commission = paid_commission + %f,
             approved_commission = GREATEST(approved_commission - %f, 0)
         WHERE id = %d",
        floatval($payout->payout_amount),
        floatval($payout->payout_amount),
        intval($payout->affiliate_id)
    ));

    return true;
}

/**
 * Cancel affiliate commissions linked to a refunded/charged back Flowguard transaction.
 *
 * @param array $payload Flowguard refund payload.
 * @return int Number of cancelled rows.
 */
function flexpress_cancel_affiliate_commission_for_refund($payload) {
    global $wpdb;

    $parent_id = sanitize_text_field($payload['parentId'] ?? '');
    $transaction_id = sanitize_text_field($payload['transactionId'] ?? '');
    $lookup_id = $parent_id ?: $transaction_id;
    if ($lookup_id === '') {
        return 0;
    }

    $transactions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliate_transactions
         WHERE (flowguard_transaction_id = %s OR transaction_id = %s)
           AND status IN ('pending', 'approved')",
        $lookup_id,
        $lookup_id
    ));
    if (!$transactions) {
        return 0;
    }

    $cancelled = 0;
    foreach ($transactions as $transaction) {
        $updated = $wpdb->update(
            $wpdb->prefix . 'flexpress_affiliate_transactions',
            array(
                'status' => 'cancelled',
                'notes' => trim(($transaction->notes ? $transaction->notes . "\n" : '') . 'Cancelled due to Flowguard ' . sanitize_text_field($payload['postbackType'] ?? 'refund')),
            ),
            array('id' => intval($transaction->id)),
            array('%s', '%s'),
            array('%d')
        );
        if ($updated === false) {
            continue;
        }

        if ($transaction->status === 'pending') {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}flexpress_affiliates
                 SET pending_commission = GREATEST(pending_commission - %f, 0)
                 WHERE id = %d",
                floatval($transaction->commission_amount),
                intval($transaction->affiliate_id)
            ));
        } elseif ($transaction->status === 'approved') {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}flexpress_affiliates
                 SET approved_commission = GREATEST(approved_commission - %f, 0)
                 WHERE id = %d",
                floatval($transaction->commission_amount),
                intval($transaction->affiliate_id)
            ));
        }
        $cancelled++;
    }

    return $cancelled;
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
