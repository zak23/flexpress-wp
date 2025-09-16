<?php
/**
 * FlexPress Affiliate Helper Functions
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Track affiliate commission for signup, rebill, or PPV
 *
 * @param string $promo_code The promo code used
 * @param int $user_id The user ID who made the purchase
 * @param string $transaction_type 'signup', 'rebill', or 'ppv'
 * @param string $plan_id The plan purchased
 * @param string $transaction_id The transaction ID
 * @param float $amount The amount paid
 */
function flexpress_track_affiliate_commission($promo_code, $user_id, $transaction_type, $plan_id, $transaction_id, $amount = 0.00) {
    if (empty($promo_code)) {
        return;
    }
    
    global $wpdb;
    
    // Find the affiliate by promo code
    $affiliate = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE affiliate_code = %s AND status = 'active'",
        $promo_code
    ));
    
    if (!$affiliate) {
        error_log("FlexPress Affiliate: No active affiliate found for promo code: $promo_code");
        return;
    }
    
    // Determine commission rate based on transaction type
    $commission_rate = ($transaction_type === 'signup') ? $affiliate->commission_signup : $affiliate->commission_rebill;
    
    // Calculate commission amount
    $commission_amount = 0.00;
    if ($affiliate->commission_type === 'percentage') {
        $commission_amount = ($amount * $commission_rate) / 100;
    } else {
        $commission_amount = $commission_rate; // Flat rate
    }
    
    // Insert commission record
    $commission_id = $wpdb->insert(
        $wpdb->prefix . 'flexpress_affiliate_commissions',
        array(
            'affiliate_id' => $affiliate->id,
            'user_id' => $user_id,
            'transaction_type' => $transaction_type,
            'transaction_id' => $transaction_id,
            'plan_id' => $plan_id,
            'revenue_amount' => floatval($amount),
            'commission_rate' => floatval($commission_rate),
            'commission_amount' => floatval($commission_amount),
            'commission_type' => $affiliate->commission_type,
            'status' => 'pending',
            'promo_code' => $promo_code,
            'created_at' => current_time('mysql')
        ),
        array('%d', '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s')
    );
    
    if ($commission_id) {
        // Update affiliate totals
        if ($transaction_type === 'signup') {
            $wpdb->update(
                $wpdb->prefix . 'flexpress_affiliates',
                array(
                    'total_signups' => $affiliate->total_signups + 1,
                    'total_revenue' => $affiliate->total_revenue + $amount,
                    'total_commission' => $affiliate->total_commission + $commission_amount,
                    'pending_commission' => $affiliate->pending_commission + $commission_amount,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $affiliate->id),
                array('%d', '%f', '%f', '%f', '%s'),
                array('%d')
            );
        } else if ($transaction_type === 'rebill') {
            $wpdb->update(
                $wpdb->prefix . 'flexpress_affiliates',
                array(
                    'total_rebills' => $affiliate->total_rebills + 1,
                    'total_revenue' => $affiliate->total_revenue + $amount,
                    'total_commission' => $affiliate->total_commission + $commission_amount,
                    'pending_commission' => $affiliate->pending_commission + $commission_amount,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $affiliate->id),
                array('%d', '%f', '%f', '%f', '%s'),
                array('%d')
            );
        }
        
        error_log("FlexPress Affiliate: Commission tracked - Affiliate: {$affiliate->display_name}, Type: $transaction_type, Amount: $$commission_amount");
    }
}

/**
 * Register a new affiliate
 *
 * @param array $affiliate_data Affiliate registration data
 * @return array|WP_Error Success with affiliate ID or error
 */
function flexpress_register_affiliate($affiliate_data) {
    global $wpdb;
    
    // Validate required fields
    $required_fields = array('display_name', 'email', 'affiliate_code');
    foreach ($required_fields as $field) {
        if (empty($affiliate_data[$field])) {
            return new WP_Error('missing_field', "Field '$field' is required");
        }
    }
    
    // Validate email
    if (!is_email($affiliate_data['email'])) {
        return new WP_Error('invalid_email', 'Invalid email address');
    }
    
    // Validate affiliate code format
    if (!preg_match('/^[a-zA-Z0-9-]{3,20}$/', $affiliate_data['affiliate_code'])) {
        return new WP_Error('invalid_code', 'Affiliate code must be 3-20 characters and contain only letters, numbers, and hyphens');
    }
    
    // Check if affiliate code already exists
    $existing_code = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE affiliate_code = %s",
        $affiliate_data['affiliate_code']
    ));
    
    if ($existing_code) {
        return new WP_Error('code_exists', 'Affiliate code already exists');
    }
    
    // Check if email already exists
    $existing_email = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE email = %s",
        $affiliate_data['email']
    ));
    
    if ($existing_email) {
        return new WP_Error('email_exists', 'Email already registered as affiliate');
    }
    
    // Set default commission rates from settings
    $affiliate_settings = get_option('flexpress_affiliate_settings', array());
    $default_signup_commission = floatval($affiliate_settings['commission_rate'] ?? 25);
    $default_rebill_commission = floatval($affiliate_settings['rebill_commission_rate'] ?? 10);
    
    // Insert new affiliate
    $affiliate_id = $wpdb->insert(
        $wpdb->prefix . 'flexpress_affiliates',
        array(
            'user_id' => intval($affiliate_data['user_id'] ?? 0),
            'affiliate_code' => sanitize_text_field($affiliate_data['affiliate_code']),
            'display_name' => sanitize_text_field($affiliate_data['display_name']),
            'email' => sanitize_email($affiliate_data['email']),
            'commission_signup' => floatval($affiliate_data['commission_signup'] ?? $default_signup_commission),
            'commission_rebill' => floatval($affiliate_data['commission_rebill'] ?? $default_rebill_commission),
            'commission_type' => sanitize_text_field($affiliate_data['commission_type'] ?? 'percentage'),
            'status' => !empty($affiliate_settings['auto_approve_affiliates']) ? 'active' : 'pending',
            'referral_url' => esc_url_raw($affiliate_data['referral_url'] ?? ''),
            'notes' => sanitize_textarea_field($affiliate_data['notes'] ?? ''),
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s')
    );
    
    if ($affiliate_id) {
        return array(
            'success' => true,
            'affiliate_id' => $affiliate_id,
            'status' => !empty($affiliate_settings['auto_approve_affiliates']) ? 'active' : 'pending'
        );
    } else {
        return new WP_Error('insert_failed', 'Failed to create affiliate account');
    }
}

/**
 * Get affiliate by code
 *
 * @param string $affiliate_code The affiliate code
 * @return object|null Affiliate data or null if not found
 */
function flexpress_get_affiliate_by_code($affiliate_code) {
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE affiliate_code = %s",
        $affiliate_code
    ));
}

/**
 * Get affiliate dashboard data
 *
 * @param int $affiliate_id The affiliate ID
 * @return array Dashboard data
 */
function flexpress_get_affiliate_dashboard_data($affiliate_id) {
    global $wpdb;
    
    $affiliate = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE id = %d",
        $affiliate_id
    ));
    
    if (!$affiliate) {
        return null;
    }
    
    // Get recent commissions
    $recent_commissions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliate_commissions 
         WHERE affiliate_id = %d 
         ORDER BY created_at DESC 
         LIMIT 10",
        $affiliate_id
    ));
    
    // Get commission stats by type
    $commission_stats = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            transaction_type,
            COUNT(*) as count,
            SUM(commission_amount) as total_commission,
            SUM(revenue_amount) as total_revenue
         FROM {$wpdb->prefix}flexpress_affiliate_commissions 
         WHERE affiliate_id = %d 
         GROUP BY transaction_type",
        $affiliate_id
    ));
    
    // Get monthly stats
    $monthly_stats = $wpdb->get_results($wpdb->prepare(
        "SELECT 
            DATE_FORMAT(created_at, '%%Y-%%m') as month,
            COUNT(*) as transactions,
            SUM(commission_amount) as commission,
            SUM(revenue_amount) as revenue
         FROM {$wpdb->prefix}flexpress_affiliate_commissions 
         WHERE affiliate_id = %d 
         AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
         GROUP BY DATE_FORMAT(created_at, '%%Y-%%m')
         ORDER BY month DESC",
        $affiliate_id
    ));
    
    return array(
        'affiliate' => $affiliate,
        'recent_commissions' => $recent_commissions,
        'commission_stats' => $commission_stats,
        'monthly_stats' => $monthly_stats
    );
}

/**
 * Generate unique affiliate code suggestion
 *
 * @param string $base_name Base name for code generation
 * @return string Unique affiliate code
 */
function flexpress_generate_affiliate_code($base_name) {
    global $wpdb;
    
    // Clean up base name
    $base_code = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $base_name));
    $base_code = substr($base_code, 0, 15);
    
    if (empty($base_code)) {
        $base_code = 'affiliate';
    }
    
    // Try the base code first
    $attempt = $base_code;
    $counter = 1;
    
    while (true) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE affiliate_code = %s",
            $attempt
        ));
        
        if (!$existing) {
            return $attempt;
        }
        
        $attempt = $base_code . $counter;
        $counter++;
        
        // Prevent infinite loop
        if ($counter > 999) {
            return $base_code . time();
        }
    }
}

/**
 * Update affiliate commission rates
 *
 * @param int $affiliate_id The affiliate ID
 * @param array $rates New commission rates
 * @return bool Success
 */
function flexpress_update_affiliate_rates($affiliate_id, $rates) {
    global $wpdb;
    
    $update_data = array();
    
    if (isset($rates['commission_signup'])) {
        $update_data['commission_signup'] = floatval($rates['commission_signup']);
    }
    
    if (isset($rates['commission_rebill'])) {
        $update_data['commission_rebill'] = floatval($rates['commission_rebill']);
    }
    
    if (isset($rates['commission_type'])) {
        $update_data['commission_type'] = sanitize_text_field($rates['commission_type']);
    }
    
    if (!empty($update_data)) {
        $update_data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            $wpdb->prefix . 'flexpress_affiliates',
            $update_data,
            array('id' => $affiliate_id),
            array_fill(0, count($update_data), '%s'),
            array('%d')
        );
    }
    
    return false;
} 