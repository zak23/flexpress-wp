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
function flexpress_get_flowguard_api()
{
    $flowguard_settings = get_option('flexpress_flowguard_settings', []);

    if (empty($flowguard_settings['shop_id']) || empty($flowguard_settings['signature_key'])) {
        return false;
    }

    return new FlexPress_Flowguard_API(
        $flowguard_settings['shop_id'],
        $flowguard_settings['signature_key']
    );
}

/**
 * Create Flowguard subscription
 * 
 * @param int $user_id User ID
 * @param int $plan_id Plan ID
 * @return array Response data
 */
function flexpress_flowguard_create_subscription($user_id, $plan_id)
{
    error_log('FlexPress: Creating Flowguard subscription for user ' . $user_id . ', plan ' . $plan_id);

    $plan = flexpress_get_pricing_plan($plan_id);
    if (!$plan) {
        error_log('FlexPress: Invalid plan ID: ' . $plan_id);
        return ['success' => false, 'error' => 'Invalid plan'];
    }

    $user = get_userdata($user_id);
    if (!$user) {
        error_log('FlexPress: Invalid user ID: ' . $user_id);
        return ['success' => false, 'error' => 'Invalid user'];
    }

    $api = flexpress_get_flowguard_api();
    if (!$api) {
        error_log('FlexPress: Flowguard API not configured');
        return ['success' => false, 'error' => 'Flowguard API not configured'];
    }

    // Generate enhanced reference ID with meaningful user data
    $enhanced_reference = flexpress_flowguard_generate_enhanced_reference($user_id, $plan_id);

    $subscription_data = [
        'priceAmount' => number_format($plan['price'], 2, '.', ''),
        'priceCurrency' => flexpress_flowguard_convert_currency_symbol_to_code($plan['currency']),
        'successUrl' => home_url('/payment-success'),
        'declineUrl' => home_url('/payment-declined?plan=' . urlencode($plan_id)),
        'postbackUrl' => home_url('/wp-admin/admin-ajax.php?action=flowguard_webhook'),
        'email' => $user->user_email,
        'subscriptionType' => $plan['plan_type'] === 'one_time' ? 'one-time' : 'recurring',
        'period' => flexpress_format_plan_duration_for_flowguard($plan['duration'], $plan['duration_unit']),
        'referenceId' => $enhanced_reference
    ];

    // Add trial information if enabled
    if (!empty($plan['trial_enabled'])) {
        $subscription_data['trialAmount'] = number_format($plan['trial_price'], 2, '.', '');
        $subscription_data['trialPeriod'] = flexpress_format_plan_duration_for_flowguard(
            $plan['trial_duration'],
            $plan['trial_duration_unit']
        );
    }

    error_log('FlexPress: Calling Flowguard API with data: ' . json_encode($subscription_data));

    $result = $api->start_subscription($subscription_data);

    error_log('FlexPress: Flowguard API response: ' . json_encode($result));

    if ($result['success']) {
        // Store session data for webhook processing
        update_user_meta($user_id, 'flowguard_session_id', $result['session_id']);
        update_user_meta($user_id, 'flowguard_plan_id', $plan_id);
        update_user_meta($user_id, 'flowguard_reference_id', $subscription_data['referenceId']);
        // Persist session with promo code for full resolution later
        // Get applied promo code from request or user meta
        $applied_promo = '';
        if (isset($_REQUEST['promo'])) {
            $applied_promo = sanitize_text_field($_REQUEST['promo']);
        }
        if (empty($applied_promo)) {
            $applied_promo = get_user_meta($user_id, 'applied_promo_code', true);
        }
        $session_data = array(
            'session_id' => $result['session_id'],
            'user_id' => $user_id,
            'plan_id' => $plan_id,
            'session_type' => 'subscription',
            'status' => 'pending',
            'amount' => floatval($subscription_data['priceAmount']),
            'currency' => $subscription_data['priceCurrency'],
            'reference_id' => $subscription_data['referenceId'],
            'promo_code' => $applied_promo ?: null,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+2 hours'))
        );
        if (function_exists('flexpress_flowguard_store_session')) {
            flexpress_flowguard_store_session($session_data);
        }

        error_log('FlexPress: Successfully created Flowguard session: ' . $result['session_id']);

        return [
            'success' => true,
            'session_id' => $result['session_id'],
            'payment_url' => home_url('/payment?session_id=' . $result['session_id'])
        ];
    }

    error_log('FlexPress: Flowguard API failed: ' . $result['error']);
    return $result;
}

/**
 * Redirect browser returns without authenticating users or changing entitlements.
 *
 * @return void
 */
function flexpress_flowguard_redirect_payment_return()
{
    nocache_headers();
    $args = [];
    foreach (['transaction_id', 'ref', 'session_id'] as $key) {
        if (isset($_GET[$key]) && is_string($_GET[$key])) {
            $args[$key] = sanitize_text_field(wp_unslash($_GET[$key]));
        }
    }
    $target = add_query_arg($args, home_url('/payment-success/'));
    if (!is_user_logged_in()) {
        $target = add_query_arg('redirect_to', $target, home_url('/login/'));
    }
    wp_safe_redirect($target);
    exit;
}

/**
 * Resolve a receipt only from a transaction owned by the signed-in customer.
 *
 * @param int    $user_id Customer ID.
 * @param string $transaction_id Provider transaction ID.
 * @param string $reference_id Merchant reference.
 * @return array|null Stored transaction or null while confirmation is pending.
 */
function flexpress_flowguard_get_return_transaction($user_id, $transaction_id = '', $reference_id = '')
{
    if (!$user_id) {
        return null;
    }
    $transaction = null;
    if ($transaction_id !== '') {
        $transaction = flexpress_flowguard_get_transaction($transaction_id);
    } elseif ($reference_id !== '') {
        global $wpdb;
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_flowguard_transactions WHERE user_id = %d AND reference_id = %s ORDER BY id DESC LIMIT 1",
            $user_id,
            $reference_id
        ), ARRAY_A);
    }
    if (!is_array($transaction) || (int) $transaction['user_id'] !== (int) $user_id) {
        return null;
    }
    return $transaction;
}

/**
 * Calculate an authoritative PPV quote for published, purchasable content.
 *
 * @param int $user_id Customer ID.
 * @param int $content_id Episode or Extras ID.
 * @return array Success, price and content type, or an error.
 */
function flexpress_flowguard_get_ppv_quote($user_id, $content_id)
{
    $content = get_post($content_id);
    if (!$content || $content->post_status !== 'publish' || !in_array($content->post_type, ['episode', 'extras'], true)) {
        return ['success' => false, 'error' => 'Content is not available for purchase.'];
    }
    if (!$user_id || !get_userdata($user_id) || flexpress_get_membership_status($user_id) === 'banned') {
        return ['success' => false, 'error' => 'Your account cannot purchase this content.'];
    }
    $access = $content->post_type === 'episode'
        ? flexpress_check_episode_access($content_id, $user_id, true)
        : flexpress_check_extras_access($content_id, $user_id);
    if (empty($access['show_purchase_button']) || !empty($access['has_access']) || !empty($access['is_unreleased'])) {
        return ['success' => false, 'error' => 'This content is already accessible or is not available for individual purchase.'];
    }
    $base_price = get_field($content->post_type === 'episode' ? 'episode_price' : 'extras_price', $content_id);
    if (!is_numeric($base_price) || !is_finite((float) $base_price) || (float) $base_price <= 0) {
        return ['success' => false, 'error' => 'Content does not have a valid purchase price.'];
    }
    $discount = 0;
    if ($access['access_type'] === 'mixed' && !empty($access['is_member'])) {
        $discount = max(0, min(100, (float) get_field('member_discount', $content_id)));
    }
    return [
        'success' => true,
        'content_type' => $content->post_type,
        'base_price' => (float) $base_price,
        'final_price' => max(2.95, round((float) $base_price * (1 - $discount / 100), 2))
    ];
}

/**
 * Create Flowguard PPV purchase
 * 
 * @param int $user_id User ID
 * @param int $episode_id Episode or Extras ID (legacy parameter name).
 * @param float|null $final_price Ignored legacy caller price; calculated on the server.
 * @param string|null $content_type Expected content type, if supplied.
 * @return array Response data
 */
function flexpress_flowguard_create_ppv_purchase($user_id, $episode_id, $final_price = null, $content_type = null)
{
    // $final_price is retained for compatibility but is never trusted.
    $quote = flexpress_flowguard_get_ppv_quote($user_id, $episode_id);
    if (!$quote['success']) {
        return $quote;
    }
    if ($content_type !== null && $content_type !== $quote['content_type']) {
        return ['success' => false, 'error' => 'Content type mismatch.'];
    }
    $content_type = $quote['content_type'];
    $ppv_price = $quote['base_price'];
    $price_to_charge = $quote['final_price'];
    $user = get_userdata($user_id);
    $api = flexpress_get_flowguard_api();
    if (!$api) {
        return ['success' => false, 'error' => 'Flowguard API not configured'];
    }

    // Generate enhanced reference for PPV purchase
    $transaction_ref = flexpress_flowguard_generate_enhanced_ppv_reference($user_id, $episode_id, $content_type);

    $purchase_data = [
        'priceAmount' => number_format($price_to_charge, 2, '.', ''),
        'priceCurrency' => 'USD',
        'successUrl' => add_query_arg('ref', $transaction_ref, home_url('/payment-success/')),
        'declineUrl' => add_query_arg('ref', $transaction_ref, home_url('/payment-declined/')),
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

        // Persist session with promo code for full resolution later
        $applied_promo = '';
        if (isset($_REQUEST['promo'])) {
            $applied_promo = sanitize_text_field($_REQUEST['promo']);
        }
        if (empty($applied_promo)) {
            $applied_promo = get_user_meta($user_id, 'applied_promo_code', true);
        }
        if (function_exists('flexpress_flowguard_store_session')) {
            flexpress_flowguard_store_session([
                'session_id' => $result['session_id'],
                'user_id' => $user_id,
                'episode_id' => $episode_id,
                'session_type' => 'purchase',
                'status' => 'pending',
                'amount' => floatval($price_to_charge),
                'currency' => 'USD',
                'reference_id' => $transaction_ref,
                'promo_code' => $applied_promo ?: null,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+2 hours'))
            ]);
        }

        return [
            'success' => true,
            'final_price' => $price_to_charge,
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
function flexpress_format_plan_duration_for_flowguard($duration, $duration_unit)
{
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
function flexpress_flowguard_convert_currency_symbol_to_code($currency_symbol)
{
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
 * Generate enhanced Flowguard reference ID with meaningful user data
 * 
 * @param int $user_id User ID
 * @param string $plan_id Plan ID
 * @param array $additional_data Additional data to include
 * @return string Enhanced reference ID
 */
function flexpress_flowguard_generate_enhanced_reference($user_id, $plan_id, $additional_data = array())
{
    global $wpdb;

    $user = get_userdata($user_id);
    if (!$user) {
        return 'user_' . $user_id . '_plan_' . $plan_id;
    }

    // Get user's affiliate tracking data
    $affiliate_code = function_exists('flexpress_get_user_affiliate_code') ? flexpress_get_user_affiliate_code($user_id) : get_user_meta($user_id, 'affiliate_referred_code', true);
    $promo_code = get_user_meta($user_id, 'applied_promo_code', true);
    $signup_source = get_user_meta($user_id, 'signup_source', true);
    $registration_date = get_user_meta($user_id, 'registration_date', true);

    // Build reference components
    $components = array();

    // User ID (always first)
    $components[] = 'uid' . $user_id;

    // Affiliate code (if exists, otherwise use placeholder)
    if (!empty($affiliate_code)) {
        $components[] = 'aff' . substr($affiliate_code, 0, 8); // Limit length
    } else {
        $components[] = 'affnone'; // Placeholder for no affiliate
    }

    // Promo code (if exists, otherwise use placeholder) + durable identifier
    if (!empty($promo_code)) {
        $components[] = 'promo' . substr($promo_code, 0, 8); // Truncated for legacy parsing
        // Prefer a stable numeric promo ID when available; otherwise include a short hash
        $promo_identifier_added = false;
        if (isset($wpdb)) {
            $promo_table = $wpdb->prefix . 'flexpress_promo_codes';
            if ($wpdb->get_var("SHOW TABLES LIKE '$promo_table'") === $promo_table) {
                $promo_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$promo_table} WHERE LOWER(code) = LOWER(%s) LIMIT 1",
                    $promo_code
                ));
                if (!empty($promo_id)) {
                    $components[] = 'prm' . intval($promo_id);
                    $promo_identifier_added = true;
                }
            }
        }
        if (!$promo_identifier_added) {
            $hash = substr(base_convert(substr(sha1(strtolower($promo_code)), 0, 10), 16, 36), 0, 6);
            $components[] = 'prh' . $hash;
        }
    } else {
        $components[] = 'promonone'; // Placeholder for no promo
    }

    // Signup source (if exists, otherwise use placeholder)
    if (!empty($signup_source)) {
        $components[] = 'src' . substr($signup_source, 0, 6); // Limit length
    } else {
        $components[] = 'srcnone'; // Placeholder for no source
    }

    // Plan ID
    $components[] = 'plan' . $plan_id;

    // Registration timestamp (if exists, otherwise use current time)
    if (!empty($registration_date)) {
        $timestamp = strtotime($registration_date);
        $components[] = 'reg' . $timestamp; // Full timestamp
    } else {
        $timestamp = time();
        $components[] = 'reg' . $timestamp; // Full current timestamp
    }

    // Add microtime for additional uniqueness
    $microtime = substr(microtime(true) * 1000000, -6); // Last 6 digits of microtime
    $components[] = 'mic' . $microtime;

    // Add random component for maximum uniqueness
    $random = substr(md5(uniqid(rand(), true)), 0, 8);
    $components[] = 'rnd' . $random;

    // Join components with underscores
    $reference = implode('_', $components);

    // Ensure reference ID is unique by checking if it exists in database
    $existing_refs = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}flexpress_flowguard_transactions WHERE reference_id = %s",
        $reference
    ));

    if ($existing_refs > 0) {
        // Add additional random component if reference already exists
        $extra_random = substr(md5(uniqid(rand(), true)), 0, 8);
        $reference .= '_' . $extra_random;
    }

    // Store the enhanced reference data for later retrieval
    update_user_meta($user_id, 'flowguard_enhanced_reference', $reference);
    update_user_meta($user_id, 'flowguard_reference_data', array(
        'user_id' => $user_id,
        'plan_id' => $plan_id,
        'affiliate_code' => $affiliate_code,
        'promo_code' => $promo_code,
        'signup_source' => $signup_source,
        'registration_date' => $registration_date,
        'generated_at' => current_time('mysql')
    ));

    return $reference;
}

/**
 * Generate enhanced Flowguard reference ID for PPV purchases
 * 
 * @param int $user_id User ID
 * @param int $episode_id Episode ID
 * @return string Enhanced PPV reference ID
 */
function flexpress_flowguard_generate_enhanced_ppv_reference($user_id, $episode_id, $content_type = 'episode')
{
    global $wpdb;

    $user = get_userdata($user_id);
    if (!$user) {
        return 'ppv_' . $episode_id . '_' . $user_id . '_' . time();
    }

    // Get user's affiliate tracking data
    $affiliate_code = function_exists('flexpress_get_user_affiliate_code') ? flexpress_get_user_affiliate_code($user_id) : get_user_meta($user_id, 'affiliate_referred_code', true);
    $promo_code = get_user_meta($user_id, 'applied_promo_code', true);
    $signup_source = get_user_meta($user_id, 'signup_source', true);
    $registration_date = get_user_meta($user_id, 'registration_date', true);

    // Build reference components for PPV
    $components = array();

    // PPV identifier (always first)
    $components[] = 'ppv';

    // Preserve the content type in the signed payment reference.
    $components[] = ($content_type === 'extras' ? 'ex' : 'ep') . $episode_id;

    // User ID
    $components[] = 'uid' . $user_id;

    // Affiliate code (if exists, otherwise use placeholder)
    if (!empty($affiliate_code)) {
        $components[] = 'aff' . substr($affiliate_code, 0, 8); // Limit length
    } else {
        $components[] = 'affnone'; // Placeholder for no affiliate
    }

    // Promo code (if exists, otherwise use placeholder) + durable identifier
    if (!empty($promo_code)) {
        $components[] = 'promo' . substr($promo_code, 0, 8);
        $promo_identifier_added = false;
        if (isset($wpdb)) {
            $promo_table = $wpdb->prefix . 'flexpress_promo_codes';
            if ($wpdb->get_var("SHOW TABLES LIKE '$promo_table'") === $promo_table) {
                $promo_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$promo_table} WHERE LOWER(code) = LOWER(%s) LIMIT 1",
                    $promo_code
                ));
                if (!empty($promo_id)) {
                    $components[] = 'prm' . intval($promo_id);
                    $promo_identifier_added = true;
                }
            }
        }
        if (!$promo_identifier_added) {
            $hash = substr(base_convert(substr(sha1(strtolower($promo_code)), 0, 10), 16, 36), 0, 6);
            $components[] = 'prh' . $hash;
        }
    } else {
        $components[] = 'promonone';
    }

    // Signup source (if exists, otherwise use placeholder)
    if (!empty($signup_source)) {
        $components[] = 'src' . substr($signup_source, 0, 6); // Limit length
    } else {
        $components[] = 'srcnone'; // Placeholder for no source
    }

    // Purchase timestamp (last 8 digits)
    $timestamp = time();
    $components[] = 'ts' . substr($timestamp, -8);

    // Join components with underscores
    $reference = implode('_', $components);

    // Store the enhanced PPV reference data for later retrieval
    update_user_meta($user_id, 'flowguard_enhanced_ppv_reference', $reference);
    update_user_meta($user_id, 'flowguard_ppv_reference_data', array(
        'user_id' => $user_id,
        'episode_id' => $episode_id,
        'affiliate_code' => $affiliate_code,
        'promo_code' => $promo_code,
        'signup_source' => $signup_source,
        'registration_date' => $registration_date,
        'purchase_timestamp' => $timestamp,
        'generated_at' => current_time('mysql')
    ));

    return $reference;
}

/**
 * Parse enhanced Flowguard reference ID to extract meaningful data
 * 
 * @param string $reference_id Reference ID
 * @return array Parsed reference data
 */
function flexpress_flowguard_parse_enhanced_reference($reference_id)
{
    if (empty($reference_id)) {
        return array();
    }

    $data = array(
        'user_id' => 0,
        'plan_id' => '',
        'affiliate_code' => '',
        'promo_code' => '',
        'signup_source' => '',
        'registration_timestamp' => '',
        'is_enhanced' => false,
        'is_ppv' => false,
        'content_type' => 'episode'
    );

    // Handle enhanced PPV references (ppv_ep123_uid456_affABC_promoXYZ_srcgoogle_ts12345678)
    if (preg_match('/^ppv_(ep|ex)(\d+)_uid(\d+)(?:_|$)/', $reference_id, $content_match)) {
        $data['content_type'] = $content_match[1] === 'ex' ? 'extras' : 'episode';
        $data['episode_id'] = intval($content_match[2]);
        $data['is_ppv'] = true;
        $data['is_enhanced'] = true;

        // Extract user ID
        if (preg_match('/uid(\d+)/', $reference_id, $matches)) {
            $data['user_id'] = intval($matches[1]);
        }

        // Extract affiliate code
        if (preg_match('/aff([^_]+)/', $reference_id, $matches)) {
            $affiliate_code = $matches[1];
            $data['affiliate_code'] = ($affiliate_code === 'none') ? '' : $affiliate_code;
        }

        // Extract promo code
        if (preg_match('/promo([^_]+)/', $reference_id, $matches)) {
            $promo_code = $matches[1];
            $data['promo_code'] = ($promo_code === 'none') ? '' : $promo_code;
        }

        // Extract signup source
        if (preg_match('/src([^_]+)/', $reference_id, $matches)) {
            $signup_source = $matches[1];
            $data['signup_source'] = ($signup_source === 'none') ? '' : $signup_source;
        }

        // Extract purchase timestamp
        if (preg_match('/ts(\d+)/', $reference_id, $matches)) {
            $data['purchase_timestamp'] = $matches[1];
        }

        return $data;
    }

    // Handle legacy PPV references (ppv_episodeId_userId_timestamp)
    if (preg_match('/^ppv_(\d+)_(\d+)_\d+$/', $reference_id, $matches)) {
        $data['is_ppv'] = true;
        $data['episode_id'] = intval($matches[1]);
        $data['user_id'] = intval($matches[2]);
        return $data;
    }

    // Handle enhanced references (uid123_affABC123_promoXYZ_prm1|prhabc123_src..._reg..._plan456)
    if (preg_match('/^uid(\d+)/', $reference_id)) {
        $data['is_enhanced'] = true;

        // Extract user ID
        if (preg_match('/uid(\d+)/', $reference_id, $matches)) {
            $data['user_id'] = intval($matches[1]);
        }

        // Extract affiliate code
        if (preg_match('/aff([^_]+)/', $reference_id, $matches)) {
            $affiliate_code = $matches[1];
            $data['affiliate_code'] = ($affiliate_code === 'none') ? '' : $affiliate_code;
        }

        // Extract promo code
        if (preg_match('/promo([^_]+)/', $reference_id, $matches)) {
            $promo_code = $matches[1];
            $data['promo_code'] = ($promo_code === 'none') ? '' : $promo_code;
        }

        // Extract durable promo identifier: numeric ID or short hash
        if (preg_match('/prm(\d+)/', $reference_id, $matches)) {
            $data['promo_id'] = intval($matches[1]);
        }
        if (preg_match('/prh([a-z0-9]+)/', $reference_id, $matches)) {
            $data['promo_hash'] = $matches[1];
        }

        // Extract signup source
        if (preg_match('/src([^_]+)/', $reference_id, $matches)) {
            $signup_source = $matches[1];
            $data['signup_source'] = ($signup_source === 'none') ? '' : $signup_source;
        }

        // Extract plan ID
        if (preg_match('/plan([^_]+)/', $reference_id, $matches)) {
            $data['plan_id'] = $matches[1];
        }

        // Extract registration timestamp
        if (preg_match('/reg(\d+)/', $reference_id, $matches)) {
            $data['registration_timestamp'] = $matches[1];
        }

        return $data;
    }

    // Handle legacy format: "user_123_plan_456"
    if (preg_match('/user_(\d+)/', $reference_id, $matches)) {
        $data['user_id'] = intval($matches[1]);
        return $data;
    }

    return $data;
}

/**
 * Get user ID from Flowguard reference ID (backward compatibility)
 * 
 * @param string $reference_id Reference ID
 * @return int User ID or 0 if not found
 */
function flexpress_flowguard_get_user_from_reference($reference_id)
{
    $parsed = flexpress_flowguard_parse_enhanced_reference($reference_id);
    return $parsed['user_id'] ?? 0;
}

/**
 * Cancel Flowguard subscription
 * 
 * @param int $user_id User ID
 * @param string $cancelled_by Who cancelled (merchant, buyer)
 * @return array Response data
 */
function flexpress_flowguard_cancel_subscription($user_id, $cancelled_by = 'merchant')
{
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
function flexpress_flowguard_get_subscription_status($user_id)
{
    // Prefer flowguard_subscriber_id (subscription ID), fall back to flowguard_sale_id for backward compatibility
    $sale_id = get_user_meta($user_id, 'flowguard_subscriber_id', true);
    if (empty($sale_id)) {
        $sale_id = get_user_meta($user_id, 'flowguard_sale_id', true);
    }
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
function flexpress_flowguard_has_active_subscription($user_id)
{
    $status = flexpress_flowguard_get_subscription_status($user_id);
    return $status['has_active_subscription'];
}

/**
 * Get Flowguard webhook URL
 * 
 * @return string Webhook URL
 */
function flexpress_flowguard_get_webhook_url()
{
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
function flexpress_flowguard_log_activity($user_id, $action, $description, $data = [])
{
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
function flexpress_flowguard_store_transaction($transaction_data)
{
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
            'affiliate_code' => $transaction_data['affiliate_code'] ?? '',
            'promo_code' => $transaction_data['promo_code'] ?? '',
            'signup_source' => $transaction_data['signup_source'] ?? '',
            'plan_id' => $transaction_data['plan_id'] ?? '',
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
            '%s', // affiliate_code
            '%s', // promo_code
            '%s', // signup_source
            '%s', // plan_id
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
function flexpress_flowguard_update_transaction($transaction_id, $status, $additional_data = [])
{
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
function flexpress_flowguard_get_transaction($transaction_id)
{
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
