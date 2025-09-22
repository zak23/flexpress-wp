<?php
/**
 * Flowguard Webhook Handler
 * 
 * Handles incoming webhooks from Flowguard API for payment events.
 * Processes subscription and purchase events, updates user membership status,
 * and logs all activities.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Flowguard webhook handler
 * 
 * Processes incoming webhooks from Flowguard API
 */
function flexpress_flowguard_webhook_handler() {
    // Ensure we can capture all output for debugging
    if (!headers_sent()) {
        header('Content-Type: text/plain');
    }
    
    // Get webhook data
    $raw_body = file_get_contents('php://input');
    if (empty($raw_body)) {
        error_log('Flowguard Webhook: No data received');
        echo 'ERROR - No webhook data';
        exit;
    }
    
    // Parse JWT token
    $jwt_parts = explode('.', $raw_body);
    if (count($jwt_parts) !== 3) {
        error_log('Flowguard Webhook: Invalid JWT format');
        echo 'ERROR - Invalid JWT format';
        exit;
    }
    
    // Decode payload
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $jwt_parts[1])), true);
    if (!$payload) {
        error_log('Flowguard Webhook: Invalid JWT payload');
        echo 'ERROR - Invalid JWT payload';
        exit;
    }
    
    // Validate webhook signature
    $flowguard_settings = get_option('flexpress_flowguard_settings', []);
    if (empty($flowguard_settings['signature_key'])) {
        error_log('Flowguard Webhook: No signature key configured');
        echo 'ERROR - No signature key configured';
        exit;
    }
    
    $expected_signature = hash_hmac('sha256', $jwt_parts[0] . '.' . $jwt_parts[1], $flowguard_settings['signature_key'], true);
    $actual_signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $jwt_parts[2]));
    
    if (!hash_equals($expected_signature, $actual_signature)) {
        error_log('Flowguard Webhook: Invalid signature');
        echo 'ERROR - Invalid signature';
        exit;
    }
    
    // Log webhook for debugging
    error_log('Flowguard Webhook: ' . print_r($payload, true));
    
    // Process webhook based on type
    $postback_type = $payload['postbackType'] ?? '';
    $order_type = $payload['orderType'] ?? '';
    
    switch ($postback_type) {
        case 'approved':
            if ($order_type === 'subscription') {
                flexpress_flowguard_handle_subscription_approved($payload);
            } elseif ($order_type === 'purchase') {
                flexpress_flowguard_handle_purchase_approved($payload);
            }
            break;
            
        case 'rebill':
            flexpress_flowguard_handle_subscription_rebill($payload);
            break;
            
        case 'cancel':
            flexpress_flowguard_handle_subscription_cancel($payload);
            break;
            
        case 'expiry':
            flexpress_flowguard_handle_subscription_expiry($payload);
            break;
            
        case 'chargeback':
        case 'credit':
            flexpress_flowguard_handle_refund($payload);
            break;
            
        case 'uncancel':
            flexpress_flowguard_handle_subscription_uncancel($payload);
            break;
            
        case 'extend':
            flexpress_flowguard_handle_subscription_extend($payload);
            break;
            
        default:
            error_log('Flowguard Webhook: Unknown postback type: ' . $postback_type);
            break;
    }
    
    // Process affiliate commissions if system is enabled
    flexpress_process_affiliate_commission_from_webhook($payload);
    
    // Store webhook for analysis
    flexpress_flowguard_store_webhook($payload);
    
    echo 'OK';
    exit;
}

/**
 * Handle subscription approved webhook
 * 
 * @param array $payload Webhook payload
 */
function flexpress_flowguard_handle_subscription_approved($payload) {
    $user_id = flexpress_flowguard_get_user_from_reference($payload['referenceId'] ?? '');
    if (!$user_id) {
        error_log('Flowguard Webhook: User not found for reference: ' . ($payload['referenceId'] ?? ''));
        return;
    }
    
    // Update user membership status
    flexpress_update_membership_status($user_id, 'active');
    
    // Store subscription details
    update_user_meta($user_id, 'flowguard_sale_id', $payload['saleId']);
    update_user_meta($user_id, 'flowguard_transaction_id', $payload['transactionId']);
    update_user_meta($user_id, 'subscription_amount', $payload['priceAmount']);
    update_user_meta($user_id, 'subscription_currency', $payload['priceCurrency']);
    update_user_meta($user_id, 'subscription_start_date', current_time('mysql'));
    
    if ($payload['subscriptionType'] === 'recurring' && !empty($payload['nextChargeOn'])) {
        update_user_meta($user_id, 'next_rebill_date', $payload['nextChargeOn']);
    }
    
    if ($payload['subscriptionType'] === 'one-time' && !empty($payload['expiresOn'])) {
        update_user_meta($user_id, 'membership_expires', $payload['expiresOn']);
    }
    
    // Parse enhanced reference data
    $reference_data = flexpress_flowguard_parse_enhanced_reference($payload['referenceId'] ?? '');
    
    // Store transaction with enhanced reference data
    flexpress_flowguard_store_transaction([
        'user_id' => $user_id,
        'transaction_id' => $payload['transactionId'],
        'session_id' => '', // Not available in webhook
        'sale_id' => $payload['saleId'],
        'amount' => floatval($payload['priceAmount']),
        'currency' => $payload['priceCurrency'],
        'status' => 'approved',
        'order_type' => 'subscription',
        'reference_id' => $payload['referenceId'] ?? '',
        'affiliate_code' => $reference_data['affiliate_code'] ?? '',
        'promo_code' => $reference_data['promo_code'] ?? '',
        'signup_source' => $reference_data['signup_source'] ?? '',
        'plan_id' => $reference_data['plan_id'] ?? ''
    ]);
    
    // Log activity
    flexpress_flowguard_log_activity(
        $user_id,
        'flowguard_subscription_approved',
        'Subscription approved via Flowguard',
        $payload
    );
    
    // Send Discord notification
    flexpress_discord_notify_subscription_approved($payload, $user_id);
    
    error_log('Flowguard Webhook: Subscription approved for user ' . $user_id);
}

/**
 * Handle purchase approved webhook
 * 
 * @param array $payload Webhook payload
 */
function flexpress_flowguard_handle_purchase_approved($payload) {
    $user_id = flexpress_flowguard_get_user_from_reference($payload['referenceId'] ?? '');
    if (!$user_id) {
        error_log('Flowguard Webhook: User not found for purchase reference: ' . ($payload['referenceId'] ?? ''));
        return;
    }
    
    // Parse enhanced reference data for PPV purchase
    $reference_data = flexpress_flowguard_parse_enhanced_reference($payload['referenceId'] ?? '');
    $episode_id = $reference_data['episode_id'] ?? 0;
    
    if (!$episode_id) {
        error_log('Flowguard Webhook: No episode ID found in reference: ' . ($payload['referenceId'] ?? ''));
        return;
    }
    
    // Store transaction with enhanced reference data
    flexpress_flowguard_store_transaction([
        'user_id' => $user_id,
        'transaction_id' => $payload['transactionId'],
        'session_id' => '', // Not available in webhook
        'sale_id' => $payload['saleId'],
        'amount' => floatval($payload['priceAmount']),
        'currency' => $payload['priceCurrency'],
        'status' => 'approved',
        'order_type' => 'purchase',
        'reference_id' => $payload['referenceId'] ?? '',
        'affiliate_code' => $reference_data['affiliate_code'] ?? '',
        'promo_code' => $reference_data['promo_code'] ?? '',
        'signup_source' => $reference_data['signup_source'] ?? '',
        'plan_id' => 'ppv_episode_' . $episode_id
    ]);
    
    // If it's a PPV purchase, grant access to the episode
    if ($episode_id > 0) {
        error_log('Flowguard Webhook: Granting access to episode ' . $episode_id . ' for user ' . $user_id);
        $ppv_purchases = get_user_meta($user_id, 'ppv_purchases', true) ?: [];
        error_log('Flowguard Webhook: Current PPV purchases: ' . print_r($ppv_purchases, true));
        
        if (!in_array($episode_id, $ppv_purchases)) {
            $ppv_purchases[] = $episode_id;
            $result = update_user_meta($user_id, 'ppv_purchases', $ppv_purchases);
            error_log('Flowguard Webhook: Updated PPV purchases: ' . print_r($ppv_purchases, true) . ' (Result: ' . ($result ? 'success' : 'failed') . ')');
        } else {
            error_log('Flowguard Webhook: Episode ' . $episode_id . ' already in user purchases');
        }
    } else {
        error_log('Flowguard Webhook: No episode ID found in reference: ' . $reference_id);
    }
    
    // Log activity
    flexpress_flowguard_log_activity(
        $user_id,
        'flowguard_purchase_approved',
        'Purchase approved via Flowguard',
        $payload
    );
    
    // Send Discord notification
    flexpress_discord_notify_purchase_approved($payload, $user_id, $episode_id);
    
    error_log('Flowguard Webhook: Purchase approved for user ' . $user_id);
}

/**
 * Handle subscription rebill webhook
 * 
 * @param array $payload Webhook payload
 */
function flexpress_flowguard_handle_subscription_rebill($payload) {
    $user_id = flexpress_flowguard_get_user_from_reference($payload['referenceId'] ?? '');
    if (!$user_id) {
        error_log('Flowguard Webhook: User not found for rebill reference: ' . ($payload['referenceId'] ?? ''));
        return;
    }
    
    // Update membership status
    flexpress_update_membership_status($user_id, 'active');
    
    // Update next rebill date
    if (!empty($payload['nextChargeOn'])) {
        update_user_meta($user_id, 'next_rebill_date', $payload['nextChargeOn']);
    }
    
    // Store transaction
    flexpress_flowguard_store_transaction([
        'user_id' => $user_id,
        'transaction_id' => $payload['transactionId'],
        'session_id' => '', // Not available in webhook
        'sale_id' => $payload['saleId'],
        'amount' => floatval($payload['priceAmount']),
        'currency' => $payload['priceCurrency'],
        'status' => 'rebill',
        'order_type' => 'subscription',
        'reference_id' => $payload['referenceId'] ?? ''
    ]);
    
    // Log activity
    flexpress_flowguard_log_activity(
        $user_id,
        'flowguard_subscription_rebill',
        'Subscription rebill via Flowguard',
        $payload
    );
    
    // Send Discord notification
    flexpress_discord_notify_subscription_rebill($payload, $user_id);
    
    error_log('Flowguard Webhook: Subscription rebill for user ' . $user_id);
}

/**
 * Handle subscription cancel webhook
 * 
 * @param array $payload Webhook payload
 */
function flexpress_flowguard_handle_subscription_cancel($payload) {
    $user_id = flexpress_flowguard_get_user_from_reference($payload['referenceId'] ?? '');
    if (!$user_id) {
        error_log('Flowguard Webhook: User not found for cancel reference: ' . ($payload['referenceId'] ?? ''));
        return;
    }
    
    // Update membership status
    flexpress_update_membership_status($user_id, 'cancelled');
    
    // Set expiration date if provided
    if (!empty($payload['expiresOn'])) {
        update_user_meta($user_id, 'membership_expires', $payload['expiresOn']);
    }
    
    // Log activity
    flexpress_flowguard_log_activity(
        $user_id,
        'flowguard_subscription_cancelled',
        'Subscription cancelled via Flowguard',
        $payload
    );
    
    // Send Discord notification
    flexpress_discord_notify_subscription_cancel($payload, $user_id);
    
    error_log('Flowguard Webhook: Subscription cancelled for user ' . $user_id);
}

/**
 * Handle subscription expiry webhook
 * 
 * @param array $payload Webhook payload
 */
function flexpress_flowguard_handle_subscription_expiry($payload) {
    $user_id = flexpress_flowguard_get_user_from_reference($payload['referenceId'] ?? '');
    if (!$user_id) {
        error_log('Flowguard Webhook: User not found for expiry reference: ' . ($payload['referenceId'] ?? ''));
        return;
    }
    
    // Update membership status
    flexpress_update_membership_status($user_id, 'expired');
    
    // Log activity
    flexpress_flowguard_log_activity(
        $user_id,
        'flowguard_subscription_expired',
        'Subscription expired via Flowguard',
        $payload
    );
    
    // Send Discord notification
    flexpress_discord_notify_subscription_expiry($payload, $user_id);
    
    error_log('Flowguard Webhook: Subscription expired for user ' . $user_id);
}

/**
 * Handle refund webhook (chargeback or credit)
 * 
 * @param array $payload Webhook payload
 */
function flexpress_flowguard_handle_refund($payload) {
    $user_id = flexpress_flowguard_get_user_from_reference($payload['referenceId'] ?? '');
    if (!$user_id) {
        error_log('Flowguard Webhook: User not found for refund reference: ' . ($payload['referenceId'] ?? ''));
        return;
    }
    
    // Store refund transaction
    flexpress_flowguard_store_transaction([
        'user_id' => $user_id,
        'transaction_id' => $payload['transactionId'],
        'session_id' => '', // Not available in webhook
        'sale_id' => $payload['saleId'],
        'amount' => floatval($payload['priceAmount']),
        'currency' => $payload['priceCurrency'],
        'status' => $payload['postbackType'], // chargeback or credit
        'order_type' => $payload['orderType'],
        'reference_id' => $payload['referenceId'] ?? ''
    ]);
    
    // If it's a subscription refund, update membership status
    if ($payload['orderType'] === 'subscription') {
        flexpress_update_membership_status($user_id, 'refunded');
    }
    
    // Log activity
    flexpress_flowguard_log_activity(
        $user_id,
        'flowguard_refund_' . $payload['postbackType'],
        'Refund processed via Flowguard',
        $payload
    );
    
    // Send Discord notification
    flexpress_discord_notify_refund($payload, $user_id);
    
    error_log('Flowguard Webhook: Refund processed for user ' . $user_id);
}

/**
 * Handle subscription uncancel webhook
 * 
 * @param array $payload Webhook payload
 */
function flexpress_flowguard_handle_subscription_uncancel($payload) {
    $user_id = flexpress_flowguard_get_user_from_reference($payload['referenceId'] ?? '');
    if (!$user_id) {
        error_log('Flowguard Webhook: User not found for uncancel reference: ' . ($payload['referenceId'] ?? ''));
        return;
    }
    
    // Update membership status
    flexpress_update_membership_status($user_id, 'active');
    
    // Update next charge date if provided
    if (!empty($payload['nextChargeOn'])) {
        update_user_meta($user_id, 'next_rebill_date', $payload['nextChargeOn']);
    }
    
    // Log activity
    flexpress_flowguard_log_activity(
        $user_id,
        'flowguard_subscription_uncancelled',
        'Subscription uncancelled via Flowguard',
        $payload
    );
    
    error_log('Flowguard Webhook: Subscription uncancelled for user ' . $user_id);
}

/**
 * Handle subscription extend webhook
 * 
 * @param array $payload Webhook payload
 */
function flexpress_flowguard_handle_subscription_extend($payload) {
    $user_id = flexpress_flowguard_get_user_from_reference($payload['referenceId'] ?? '');
    if (!$user_id) {
        error_log('Flowguard Webhook: User not found for extend reference: ' . ($payload['referenceId'] ?? ''));
        return;
    }
    
    // Get current membership status - DO NOT change the status, only update dates
    $current_status = flexpress_get_membership_status($user_id);
    
    // Update dates based on subscription type
    if ($payload['subscriptionType'] === 'recurring' && !empty($payload['nextChargeOn'])) {
        update_user_meta($user_id, 'next_rebill_date', $payload['nextChargeOn']);
    }
    
    if ($payload['subscriptionType'] === 'one-time' && !empty($payload['expiresOn'])) {
        update_user_meta($user_id, 'membership_expires', $payload['expiresOn']);
    }
    
    // Log activity
    flexpress_flowguard_log_activity(
        $user_id,
        'flowguard_subscription_extended',
        'Subscription extended via Flowguard (status preserved: ' . $current_status . ')',
        $payload
    );
    
    // Send Discord notification
    flexpress_discord_notify_subscription_extend($payload, $user_id);
    
    error_log('Flowguard Webhook: Subscription extended for user ' . $user_id . ' (status preserved: ' . $current_status . ')');
}

/**
 * Store webhook for analysis
 * 
 * @param array $payload Webhook payload
 */
function flexpress_flowguard_store_webhook($payload) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_flowguard_webhooks';
    
    $wpdb->insert(
        $table_name,
        [
            'webhook_id' => $payload['transactionId'] ?? uniqid(),
            'event_type' => $payload['postbackType'] ?? 'unknown',
            'transaction_id' => $payload['transactionId'] ?? '',
            'user_id' => flexpress_flowguard_get_user_from_reference($payload['referenceId'] ?? ''),
            'payload' => json_encode($payload),
            'processed' => 1,
            'created_at' => current_time('mysql')
        ],
        [
            '%s', // webhook_id
            '%s', // event_type
            '%s', // transaction_id
            '%d', // user_id
            '%s', // payload
            '%d', // processed
            '%s'  // created_at
        ]
    );
    
    if ($wpdb->last_error) {
        error_log('Flowguard: Failed to store webhook - ' . $wpdb->last_error);
    }
}

/**
 * Process affiliate commission from Flowguard webhook
 * 
 * @param array $payload Webhook payload
 */
function flexpress_process_affiliate_commission_from_webhook($payload) {
    // Check if affiliate system is enabled
    if (!flexpress_is_affiliate_system_enabled()) {
        return;
    }
    
    $postback_type = $payload['postbackType'] ?? '';
    $order_type = $payload['orderType'] ?? '';
    $transaction_id = $payload['transactionId'] ?? '';
    $amount = floatval($payload['amount'] ?? 0);
    $reference_id = $payload['referenceId'] ?? '';
    
    // Get user from reference ID
    $user_id = flexpress_flowguard_get_user_from_reference($reference_id);
    
    if (!$user_id || $amount <= 0) {
        return;
    }
    
    // Get tracking data from cookie
    $tracker = FlexPress_Affiliate_Tracker::get_instance();
    $tracking_data = $tracker->get_tracking_data_from_cookie();
    
    if (!$tracking_data) {
        return; // No affiliate tracking
    }
    
    $affiliate_id = $tracking_data['affiliate_id'];
    $promo_code_id = $tracking_data['promo_code_id'] ?? null;
    $click_id = $tracking_data['click_id'] ?? null;
    
    // Determine transaction type and plan ID
    $transaction_type = 'initial';
    $plan_id = $payload['planId'] ?? '';
    
    if ($postback_type === 'rebill') {
        $transaction_type = 'rebill';
    } elseif ($order_type === 'purchase') {
        $transaction_type = 'unlock';
    }
    
    // Process the commission
    $result = flexpress_process_affiliate_commission(
        $affiliate_id,
        $user_id,
        $transaction_type,
        $transaction_id,
        $plan_id,
        $amount,
        $promo_code_id,
        $click_id
    );
    
    if ($result) {
        error_log("Affiliate commission processed: Affiliate {$affiliate_id}, User {$user_id}, Amount {$amount}, Type {$transaction_type}");
    } else {
        error_log("Failed to process affiliate commission: Affiliate {$affiliate_id}, User {$user_id}, Amount {$amount}");
    }
}

// Register webhook handler
add_action('wp_ajax_nopriv_flowguard_webhook', 'flexpress_flowguard_webhook_handler');
add_action('wp_ajax_flowguard_webhook', 'flexpress_flowguard_webhook_handler');
