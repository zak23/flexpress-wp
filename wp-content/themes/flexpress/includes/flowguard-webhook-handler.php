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

    // Auto-approve pending affiliate commissions older than 14 days
    if (!wp_next_scheduled('flexpress_affiliate_auto_approve')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'flexpress_affiliate_auto_approve');
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
    // Ensure caches reflect new status immediately
    if (function_exists('flexpress_invalidate_user_cache')) {
        flexpress_invalidate_user_cache($user_id);
    }
    
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
    $reference_id = $payload['referenceId'] ?? '';
    $reference_data = flexpress_flowguard_parse_enhanced_reference($reference_id);
    
    // Resolve full promo code from session when available
    $resolved_promo_code = '';
    if (function_exists('flexpress_flowguard_get_session_by_reference') && !empty($reference_id)) {
        $session_row = flexpress_flowguard_get_session_by_reference($reference_id);
        if ($session_row && !empty($session_row['promo_code'])) {
            $resolved_promo_code = $session_row['promo_code'];
        }
    }
    if (empty($resolved_promo_code)) {
        $resolved_promo_code = $reference_data['promo_code'] ?? '';
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
        'order_type' => 'subscription',
        'reference_id' => $payload['referenceId'] ?? '',
        'affiliate_code' => $reference_data['affiliate_code'] ?? '',
        'promo_code' => $resolved_promo_code,
        'signup_source' => $reference_data['signup_source'] ?? '',
        'plan_id' => $reference_data['plan_id'] ?? ''
    ]);

    // Record promo usage in centralized tables if available
    if (!empty($resolved_promo_code)) {
        global $wpdb;
        $promo_codes_table = $wpdb->prefix . 'flexpress_promo_codes';
        if ($wpdb->get_var("SHOW TABLES LIKE '$promo_codes_table'") === $promo_codes_table) {
            $promo_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$promo_codes_table} WHERE LOWER(code) = LOWER(%s) LIMIT 1",
                $resolved_promo_code
            ));
            if ($promo_id) {
                // Increment usage_count
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$promo_codes_table} SET usage_count = usage_count + 1 WHERE id = %d",
                    $promo_id
                ));
                // Insert usage row if usage table exists
                $promo_usage_table = $wpdb->prefix . 'flexpress_promo_usage';
                if ($wpdb->get_var("SHOW TABLES LIKE '$promo_usage_table'") === $promo_usage_table) {
                    $wpdb->insert(
                        $promo_usage_table,
                        array(
                            'promo_code_id' => intval($promo_id),
                            'promo_code' => strtolower($resolved_promo_code),
                            'user_id' => intval($user_id),
                            'order_id' => (string)($payload['transactionId'] ?? ''),
                            'plan_id' => (string)($reference_data['plan_id'] ?? ''),
                            'original_amount' => floatval($payload['priceAmount']),
                            'discount_amount' => 0.00,
                            'final_amount' => floatval($payload['priceAmount']),
                            'used_at' => current_time('mysql'),
                            'ip_address' => '',
                            'user_agent' => ''
                        ),
                        array('%d','%s','%d','%s','%s','%f','%f','%f','%s','%s','%s')
                    );
                }
            }
        }
    }
    
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
    $reference_id = $payload['referenceId'] ?? '';
    $reference_data = flexpress_flowguard_parse_enhanced_reference($reference_id);
    $episode_id = $reference_data['episode_id'] ?? 0;
    
    if (!$episode_id) {
        error_log('Flowguard Webhook: No episode ID found in reference: ' . ($payload['referenceId'] ?? ''));
        return;
    }
    
    // Resolve full promo code from session when available
    $resolved_promo_code = '';
    if (function_exists('flexpress_flowguard_get_session_by_reference') && !empty($reference_id)) {
        $session_row = flexpress_flowguard_get_session_by_reference($reference_id);
        if ($session_row && !empty($session_row['promo_code'])) {
            $resolved_promo_code = $session_row['promo_code'];
        }
    }
    if (empty($resolved_promo_code)) {
        $resolved_promo_code = $reference_data['promo_code'] ?? '';
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
        'promo_code' => $resolved_promo_code,
        'signup_source' => $reference_data['signup_source'] ?? '',
        'plan_id' => 'ppv_episode_' . $episode_id
    ]);

    // Record promo usage in centralized tables if available (PPV)
    if (!empty($resolved_promo_code)) {
        global $wpdb;
        $promo_codes_table = $wpdb->prefix . 'flexpress_promo_codes';
        if ($wpdb->get_var("SHOW TABLES LIKE '$promo_codes_table'") === $promo_codes_table) {
            $promo_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$promo_codes_table} WHERE LOWER(code) = LOWER(%s) LIMIT 1",
                $resolved_promo_code
            ));
            if ($promo_id) {
                // Increment usage_count
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$promo_codes_table} SET usage_count = usage_count + 1 WHERE id = %d",
                    $promo_id
                ));
                // Insert usage row if usage table exists
                $promo_usage_table = $wpdb->prefix . 'flexpress_promo_usage';
                if ($wpdb->get_var("SHOW TABLES LIKE '$promo_usage_table'") === $promo_usage_table) {
                    $wpdb->insert(
                        $promo_usage_table,
                        array(
                            'promo_code_id' => intval($promo_id),
                            'promo_code' => strtolower($resolved_promo_code),
                            'user_id' => intval($user_id),
                            'order_id' => (string)($payload['transactionId'] ?? ''),
                            'plan_id' => 'ppv_episode_' . intval($episode_id),
                            'original_amount' => floatval($payload['priceAmount']),
                            'discount_amount' => 0.00,
                            'final_amount' => floatval($payload['priceAmount']),
                            'used_at' => current_time('mysql'),
                            'ip_address' => '',
                            'user_agent' => ''
                        ),
                        array('%d','%s','%d','%s','%s','%f','%f','%f','%s','%s','%s')
                    );
                }
            }
        }
    }
    
    // If it's a PPV purchase, grant access to the episode
    if ($episode_id > 0) {
        error_log('Flowguard Webhook: Granting access to episode ' . $episode_id . ' for user ' . $user_id);
        $ppv_purchases = get_user_meta($user_id, 'ppv_purchases', true) ?: [];
        error_log('Flowguard Webhook: Current PPV purchases: ' . print_r($ppv_purchases, true));
        
        if (!in_array($episode_id, $ppv_purchases)) {
            $ppv_purchases[] = $episode_id;
            $result = update_user_meta($user_id, 'ppv_purchases', $ppv_purchases);
            error_log('Flowguard Webhook: Updated PPV purchases: ' . print_r($ppv_purchases, true) . ' (Result: ' . ($result ? 'success' : 'failed') . ')');
            if (function_exists('flexpress_invalidate_user_cache')) {
                flexpress_invalidate_user_cache($user_id);
            }
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
    if (function_exists('flexpress_invalidate_user_cache')) {
        flexpress_invalidate_user_cache($user_id);
    }
    
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
    if (function_exists('flexpress_invalidate_user_cache')) {
        flexpress_invalidate_user_cache($user_id);
    }
    
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
    if (function_exists('flexpress_invalidate_user_cache')) {
        flexpress_invalidate_user_cache($user_id);
    }
    
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
    
    // Handle access revocation and user banning
    flexpress_handle_refund_access_revocation($user_id, $payload);
    
    // Log activity
    flexpress_flowguard_log_activity(
        $user_id,
        'flowguard_refund_' . $payload['postbackType'],
        'Refund processed via Flowguard - Access revoked and user banned',
        $payload
    );
    
    // Send Discord notification
    flexpress_discord_notify_refund($payload, $user_id);
    
    error_log('Flowguard Webhook: Refund processed for user ' . $user_id . ' - Access revoked and user banned');
}

/**
 * Handle access revocation and user banning for refunds/chargebacks
 * 
 * @param int $user_id User ID
 * @param array $payload Webhook payload
 */
function flexpress_handle_refund_access_revocation($user_id, $payload) {
    $user = get_userdata($user_id);
    if (!$user) {
        error_log('FlexPress Refund: User not found for ID: ' . $user_id);
        return;
    }
    
    $order_type = $payload['orderType'] ?? '';
    $postback_type = $payload['postbackType'] ?? '';
    $reference_id = $payload['referenceId'] ?? '';
    
    error_log('FlexPress Refund: Processing refund for user ' . $user_id . ' - Order Type: ' . $order_type . ', Postback Type: ' . $postback_type . ', Reference: ' . $reference_id);
    
    // 1. Revoke subscription access
    if ($order_type === 'subscription') {
        flexpress_update_membership_status($user_id, 'banned');
        if (function_exists('flexpress_invalidate_user_cache')) {
            flexpress_invalidate_user_cache($user_id);
        }
        error_log('FlexPress Refund: Banned user ' . $user_id . ' for subscription refund/chargeback');
    }
    
    // 2. Revoke PPV access if it's a PPV purchase
    $reference_data = flexpress_flowguard_parse_enhanced_reference($reference_id);
    if ($reference_data['is_ppv'] && !empty($reference_data['episode_id'])) {
        $episode_id = $reference_data['episode_id'];
        
        // Remove episode access
        delete_user_meta($user_id, 'purchased_episode_' . $episode_id);
        
        // Remove from purchased episodes list
        $purchased_episodes = get_user_meta($user_id, 'purchased_episodes', true);
        if (is_array($purchased_episodes)) {
            $purchased_episodes = array_diff($purchased_episodes, [$episode_id]);
            update_user_meta($user_id, 'purchased_episodes', $purchased_episodes);
            if (function_exists('flexpress_invalidate_user_cache')) {
                flexpress_invalidate_user_cache($user_id);
            }
        }
        
        // Remove from PPV purchases list
        $ppv_purchases = get_user_meta($user_id, 'ppv_purchases', true);
        if (is_array($ppv_purchases)) {
            $ppv_purchases = array_diff($ppv_purchases, [$episode_id]);
            update_user_meta($user_id, 'ppv_purchases', $ppv_purchases);
            if (function_exists('flexpress_invalidate_user_cache')) {
                flexpress_invalidate_user_cache($user_id);
            }
        }
        
        // Remove transaction details
        delete_user_meta($user_id, 'ppv_transaction_' . $episode_id);
        
        error_log('FlexPress Refund: Revoked PPV access to episode ' . $episode_id . ' for user ' . $user_id . ' (Reference: ' . $reference_id . ')');
    } else {
        error_log('FlexPress Refund: No PPV episode found in reference: ' . $reference_id);
    }
    
    // 3. Cancel active subscription if user has one
    $current_membership_status = get_user_meta($user_id, 'membership_status', true);
    if (in_array($current_membership_status, ['active', 'cancelled'])) {
        // Get Flowguard sale ID (this is what we use to cancel subscriptions)
        $flowguard_sale_id = get_user_meta($user_id, 'flowguard_sale_id', true);
        
        if ($flowguard_sale_id) {
            try {
                $flowguard_api = flexpress_get_flowguard_api();
                $cancel_result = $flowguard_api->cancel_subscription($flowguard_sale_id);
                
                if ($cancel_result['success']) {
                    error_log('FlexPress Refund: Successfully cancelled subscription for banned user ' . $user_id . ' (Sale ID: ' . $flowguard_sale_id . ')');
                    
                    // Update membership status to cancelled
                    flexpress_update_membership_status($user_id, 'cancelled');
                    
                    // Log the cancellation
                    flexpress_flowguard_log_activity(
                        $user_id,
                        'flowguard_subscription_cancelled_ban',
                        'Subscription cancelled due to ban for refund/chargeback',
                        array(
                            'sale_id' => $flowguard_sale_id,
                            'reason' => 'Refund/Chargeback: ' . $postback_type
                        )
                    );
                } else {
                    error_log('FlexPress Refund: Failed to cancel subscription for banned user ' . $user_id . ': ' . ($cancel_result['message'] ?? 'Unknown error'));
                }
            } catch (Exception $e) {
                error_log('FlexPress Refund: Exception cancelling subscription for banned user ' . $user_id . ': ' . $e->getMessage());
            }
        } else {
            error_log('FlexPress Refund: No Flowguard sale ID found for banned user ' . $user_id);
        }
    }
    
    // 4. Ban the user
    flexpress_update_membership_status($user_id, 'banned');
    if (function_exists('flexpress_invalidate_user_cache')) {
        flexpress_invalidate_user_cache($user_id);
    }
    
    // 5. Add email to blacklist
    flexpress_add_email_to_blacklist($user->user_email, 'Refund/Chargeback: ' . $postback_type);
    
    // 6. Log the ban reason
    update_user_meta($user_id, 'ban_reason', 'Refund/Chargeback: ' . $postback_type . ' - Transaction ID: ' . ($payload['transactionId'] ?? ''));
    update_user_meta($user_id, 'ban_date', current_time('mysql'));
    
    error_log('FlexPress Refund: User ' . $user_id . ' (' . $user->user_email . ') banned for ' . $postback_type);
}

/**
 * Cancel subscription for banned user
 * 
 * @param int $user_id User ID
 * @param string $reason Reason for cancellation
 * @return bool Success status
 */
function flexpress_cancel_subscription_for_banned_user($user_id, $reason = 'User banned') {
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }
    
    $membership_status = get_user_meta($user_id, 'membership_status', true);
    
    // Only cancel if user has active or cancelled status
    if (!in_array($membership_status, ['active', 'cancelled'])) {
        error_log('FlexPress Ban: User ' . $user_id . ' does not have active subscription to cancel');
        return false;
    }
    
    // Get Flowguard sale ID
    $flowguard_sale_id = get_user_meta($user_id, 'flowguard_sale_id', true);
    
    if (!$flowguard_sale_id) {
        error_log('FlexPress Ban: No Flowguard sale ID found for user ' . $user_id);
        return false;
    }
    
    try {
        $flowguard_api = flexpress_get_flowguard_api();
        $cancel_result = $flowguard_api->cancel_subscription($flowguard_sale_id);
        
        if ($cancel_result['success']) {
            error_log('FlexPress Ban: Successfully cancelled subscription for banned user ' . $user_id . ' (Sale ID: ' . $flowguard_sale_id . ')');
            
            // Update membership status to cancelled
            flexpress_update_membership_status($user_id, 'cancelled');
            
            // Log the cancellation
            flexpress_flowguard_log_activity(
                $user_id,
                'flowguard_subscription_cancelled_ban',
                'Subscription cancelled due to ban: ' . $reason,
                array(
                    'sale_id' => $flowguard_sale_id,
                    'reason' => $reason
                )
            );
            
            return true;
        } else {
            error_log('FlexPress Ban: Failed to cancel subscription for banned user ' . $user_id . ': ' . ($cancel_result['message'] ?? 'Unknown error'));
            return false;
        }
    } catch (Exception $e) {
        error_log('FlexPress Ban: Exception cancelling subscription for banned user ' . $user_id . ': ' . $e->getMessage());
        return false;
    }
}

/**
 * Test refund webhook for PPV episode
 * 
 * @param string $reference_id Reference ID to test
 * @return array Test results
 */
function flexpress_test_refund_webhook($reference_id) {
    // Create test payload
    $test_payload = array(
        'postbackType' => 'credit',
        'orderType' => 'purchase',
        'referenceId' => $reference_id,
        'transactionId' => 'test_' . time(),
        'saleId' => 'test_sale_' . time(),
        'priceAmount' => '9.99',
        'priceCurrency' => 'USD'
    );
    
    error_log('FlexPress Test: Simulating refund webhook for reference: ' . $reference_id);
    
    // Call the refund handler
    flexpress_flowguard_handle_refund($test_payload);
    
    return array(
        'success' => true,
        'message' => 'Test refund webhook processed for reference: ' . $reference_id
    );
}

/**
 * Cancel subscriptions for all banned users with active subscriptions
 * 
 * @return array Results array with success/failure counts
 */
function flexpress_cancel_subscriptions_for_banned_users() {
    global $wpdb;
    
    // Find all users with banned status but active/cancelled membership
    $banned_users = $wpdb->get_results("
        SELECT u.ID, u.user_email, um1.meta_value as membership_status, um2.meta_value as flowguard_sale_id
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'membership_status'
        LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'flowguard_sale_id'
        WHERE um1.meta_value = 'banned'
        AND um2.meta_value IS NOT NULL
        AND um2.meta_value != ''
    ");
    
    $results = array(
        'total' => count($banned_users),
        'success' => 0,
        'failed' => 0,
        'errors' => array()
    );
    
    foreach ($banned_users as $user) {
        $success = flexpress_cancel_subscription_for_banned_user($user->ID, 'Bulk cancellation for banned user');
        
        if ($success) {
            $results['success']++;
        } else {
            $results['failed']++;
            $results['errors'][] = 'Failed to cancel subscription for user ' . $user->ID . ' (' . $user->user_email . ')';
        }
    }
    
    error_log('FlexPress Ban: Bulk subscription cancellation completed. Total: ' . $results['total'] . ', Success: ' . $results['success'] . ', Failed: ' . $results['failed']);
    
    return $results;
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
    
    // Prepare activity description with new date
    $description = 'Subscription extended via Flowguard (status preserved: ' . $current_status . ')';
    
    // Add new date information based on subscription type
    if ($payload['subscriptionType'] === 'recurring' && !empty($payload['nextChargeOn'])) {
        $next_charge_date = date('M j, Y', strtotime($payload['nextChargeOn']));
        $description .= ' - Next charge: ' . $next_charge_date;
    } elseif ($payload['subscriptionType'] === 'one-time' && !empty($payload['expiresOn'])) {
        $expiration_date = date('M j, Y', strtotime($payload['expiresOn']));
        $description .= ' - New expiration: ' . $expiration_date;
    }
    
    // Log activity
    flexpress_flowguard_log_activity(
        $user_id,
        'flowguard_subscription_extended',
        $description,
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
    
    // Determine attribution: promo code mapping takes precedence over cookie
    $affiliate_id = null;
    $promo_code_id = null;
    $click_id = null;

    // Attempt to resolve promo code from session or reference
    $reference_id = $payload['referenceId'] ?? '';
    $resolved_promo_code = '';
    if (function_exists('flexpress_flowguard_get_session_by_reference') && !empty($reference_id)) {
        $session_row = flexpress_flowguard_get_session_by_reference($reference_id);
        if ($session_row && !empty($session_row['promo_code'])) {
            $resolved_promo_code = $session_row['promo_code'];
        }
    }
    if (empty($resolved_promo_code) && function_exists('flexpress_flowguard_parse_enhanced_reference')) {
        $reference_data = flexpress_flowguard_parse_enhanced_reference($reference_id);
        if (!empty($reference_data['promo_code'])) {
            $resolved_promo_code = $reference_data['promo_code'];
        }
    }

    if (!empty($resolved_promo_code)) {
        // Map promo code to affiliate
        $promo_row = flexpress_get_promo_code_by_code($resolved_promo_code);
        if ($promo_row && !empty($promo_row->affiliate_id)) {
            $affiliate_id = intval($promo_row->affiliate_id);
            $promo_code_id = intval($promo_row->id);
        }
    }

    // If no affiliate via promo, fall back to tracking cookie
    if (!$affiliate_id) {
        $tracker = FlexPress_Affiliate_Tracker::get_instance();
        $tracking_data = $tracker->get_tracking_data_from_cookie();
        if ($tracking_data) {
            $affiliate_id = intval($tracking_data['affiliate_id']);
            $promo_code_id = $tracking_data['promo_code_id'] ?? $promo_code_id;
            $click_id = $tracking_data['click_id'] ?? null;
        }
    }

    if (!$affiliate_id) {
        return; // No affiliate attribution available
    }
    
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

/**
 * Cron: Auto-approve affiliate commissions older than 14 days
 */
add_action('flexpress_affiliate_auto_approve', function () {
    global $wpdb;
    $threshold = gmdate('Y-m-d H:i:s', time() - 14 * DAY_IN_SECONDS);
    $rows = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}flexpress_affiliate_transactions WHERE status = 'pending' AND created_at <= %s",
        $threshold
    ));
    if (!$rows) {
        return;
    }
    foreach ($rows as $tx_id) {
        flexpress_approve_affiliate_commission(intval($tx_id));
    }
});
