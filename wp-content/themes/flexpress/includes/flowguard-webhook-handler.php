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
    
    // Store transaction
    flexpress_flowguard_store_transaction([
        'user_id' => $user_id,
        'transaction_id' => $payload['transactionId'],
        'session_id' => '', // Not available in webhook
        'sale_id' => $payload['saleId'],
        'amount' => floatval($payload['priceAmount']),
        'currency' => $payload['priceCurrency'],
        'status' => 'approved',
        'order_type' => 'subscription',
        'reference_id' => $payload['referenceId'] ?? ''
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
    
    // Extract episode ID from reference if it's a PPV purchase
    $episode_id = 0;
    if (preg_match('/episode_(\d+)/', $payload['referenceId'] ?? '', $matches)) {
        $episode_id = intval($matches[1]);
    }
    
    // Store transaction
    flexpress_flowguard_store_transaction([
        'user_id' => $user_id,
        'transaction_id' => $payload['transactionId'],
        'session_id' => '', // Not available in webhook
        'sale_id' => $payload['saleId'],
        'amount' => floatval($payload['priceAmount']),
        'currency' => $payload['priceCurrency'],
        'status' => 'approved',
        'order_type' => 'purchase',
        'reference_id' => $payload['referenceId'] ?? ''
    ]);
    
    // If it's a PPV purchase, grant access to the episode
    if ($episode_id > 0) {
        $ppv_purchases = get_user_meta($user_id, 'ppv_purchases', true) ?: [];
        if (!in_array($episode_id, $ppv_purchases)) {
            $ppv_purchases[] = $episode_id;
            update_user_meta($user_id, 'ppv_purchases', $ppv_purchases);
        }
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
    
    // Update membership status
    flexpress_update_membership_status($user_id, 'active');
    
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
        'Subscription extended via Flowguard',
        $payload
    );
    
    error_log('Flowguard Webhook: Subscription extended for user ' . $user_id);
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

// Register webhook handler
add_action('wp_ajax_nopriv_flowguard_webhook', 'flexpress_flowguard_webhook_handler');
add_action('wp_ajax_flowguard_webhook', 'flexpress_flowguard_webhook_handler');
