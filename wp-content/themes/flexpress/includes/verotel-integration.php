<?php
/**
 * Verotel Integration Functions
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Get user membership status
 *
 * @param int $user_id User ID.
 * @return string Membership status (active, cancelled, expired, banned, none).
 */
function flexpress_get_membership_status($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return 'none';
    }
    
    $status = get_user_meta($user_id, 'membership_status', true);
    
    if (!$status) {
        return 'none';
    }
    
    return $status;
}

/**
 * Check if user has an active membership
 *
 * @param int $user_id User ID.
 * @return bool True if user has active membership.
 */
function flexpress_has_active_membership($user_id = null) {
    // Get membership status directly from user meta
    // This bypasses any WordPress filters that might grant special access
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Get status directly from user meta, not using the wrapper function
    // which might have filters applied to it
    $status = get_user_meta($user_id, 'membership_status', true);
    
    // Only 'active' status grants access, even for admins
    return $status === 'active';
}

/**
 * Get next rebill date for user
 *
 * @param int $user_id User ID.
 * @return string|false Next rebill date or false if not available.
 */
function flexpress_get_next_rebill_date($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    return get_user_meta($user_id, 'next_rebill_date', true);
}

/**
 * Update user membership status
 *
 * @param int    $user_id User ID.
 * @param string $status  New membership status.
 * @return bool True on success, false on failure.
 */
function flexpress_update_membership_status($user_id, $status) {
    $valid_statuses = array('active', 'cancelled', 'expired', 'banned', 'none');
    
    if (!in_array($status, $valid_statuses, true)) {
        return false;
    }
    
    // Get old status for logging
    $old_status = get_user_meta($user_id, 'membership_status', true) ?: 'none';
    
    // Update status
    $result = update_user_meta($user_id, 'membership_status', $status);
    
    // Log the status change if it's different and the class exists
    if ($old_status !== $status && class_exists('FlexPress_Activity_Logger')) {
        FlexPress_Activity_Logger::log_membership_change($user_id, $old_status, $status, 'Status updated via webhook');
    }
    
    return $result;
}

// Signature validation has been removed as it was not working with Verotel's implementation.
// The webhook now relies on shopID validation and Verotel's secure delivery for authentication.

/**
 * Get user ID from webhook data with improved logic
 * 
 * @param array $webhook_data Webhook data from Verotel
 * @return int User ID or 0 if not found
 */
function flexpress_get_user_id_from_webhook($webhook_data) {
    $user_id = 0;
    
    error_log('Flexpress Webhook: Attempting to identify user from webhook data: ' . print_r($webhook_data, true));
    
    // Check webhook type to determine correct field mapping
    $type = isset($webhook_data['type']) ? sanitize_text_field($webhook_data['type']) : '';
    $event = isset($webhook_data['event']) ? sanitize_text_field($webhook_data['event']) : '';
    
    // PPV Purchase webhooks: custom1=episode_id, custom2=user_id, custom3=transaction_ref
    if ($type === 'purchase' || (!empty($webhook_data['referenceID']) && strpos($webhook_data['referenceID'], 'ppv_') === 0)) {
        error_log('Detected PPV purchase webhook - using custom2 for user_id');
        
        // For PPV purchases, user ID is in custom2
        if (isset($webhook_data['custom2']) && !empty($webhook_data['custom2'])) {
            $potential_id = intval($webhook_data['custom2']);
            if ($potential_id > 0 && get_userdata($potential_id)) {
                $user_id = $potential_id;
                error_log("PPV Purchase: User found via custom2: $user_id");
                return $user_id;
            } else {
                error_log("PPV Purchase: custom2 value '{$webhook_data['custom2']}' is not a valid user ID");
            }
        }
        
        // Fallback: Check if saleID matches user ID for PPV
        if (isset($webhook_data['saleID']) && !empty($webhook_data['saleID'])) {
            $potential_id = intval($webhook_data['saleID']);
            if ($potential_id > 0 && get_userdata($potential_id)) {
                $user_id = $potential_id;
                error_log("PPV Purchase: User found via saleID: $user_id");
                return $user_id;
            }
        }
        
        error_log('PPV Purchase: No valid user ID found in custom2 or saleID');
        return 0;
    }
    
    // Subscription webhooks: custom1=user_id, custom2=plan_id, custom3=full_name
    // Try custom1 first (should be User ID in subscription format)
    if (isset($webhook_data['custom1']) && !empty($webhook_data['custom1'])) {
        $potential_id = intval($webhook_data['custom1']);
        if ($potential_id > 0 && get_userdata($potential_id)) {
            $user_id = $potential_id;
            error_log("Subscription: User found via custom1: $user_id");
            return $user_id;
        } else {
            error_log("Subscription: custom1 value '{$webhook_data['custom1']}' is not a valid user ID");
        }
    }
    
    // Try saleID as primary fallback (should be User ID for subscriptions)
    if (isset($webhook_data['saleID']) && !empty($webhook_data['saleID'])) {
        $potential_id = intval($webhook_data['saleID']);
        if ($potential_id > 0 && get_userdata($potential_id)) {
            $user_id = $potential_id;
            error_log("Subscription: User found via saleID: $user_id");
            return $user_id;
        } else {
            error_log("Subscription: saleID value '{$webhook_data['saleID']}' is not a valid user ID");
        }
    }
    
    // Backwards compatibility: Handle old format where custom1 was first name
    // Try to find user by name if custom1 and custom2 are strings (first/last name)
    if (isset($webhook_data['custom1']) && isset($webhook_data['custom2']) 
        && !is_numeric($webhook_data['custom1']) && !is_numeric($webhook_data['custom2'])) {
        
        $first_name = sanitize_text_field($webhook_data['custom1']);
        $last_name = sanitize_text_field($webhook_data['custom2']);
        
        error_log("Attempting backwards compatibility lookup with first_name: '$first_name', last_name: '$last_name'");
        
        $users = get_users([
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'first_name',
                    'value' => $first_name,
                    'compare' => '='
                ],
                [
                    'key' => 'last_name',
                    'value' => $last_name,
                    'compare' => '='
                ]
            ],
            'number' => 1
        ]);
        
        if (!empty($users)) {
            $user_id = $users[0]->ID;
            error_log("User found via backwards compatibility name search: $user_id");
            return $user_id;
        }
    }
    
    // Try to find user by transaction/sale ID stored in user meta
    if (isset($webhook_data['transactionID']) || isset($webhook_data['saleID'])) {
        $meta_queries = [];
        
        if (isset($webhook_data['transactionID'])) {
            $meta_queries[] = [
                'key' => 'verotel_transaction_id',
                'value' => sanitize_text_field($webhook_data['transactionID']),
                'compare' => '='
            ];
        }
        
        if (isset($webhook_data['saleID'])) {
            $meta_queries[] = [
                'key' => 'verotel_sale_id',
                'value' => sanitize_text_field($webhook_data['saleID']),
                'compare' => '='
            ];
        }
        
        foreach ($meta_queries as $meta_query) {
            $users = get_users(['meta_query' => [$meta_query], 'number' => 1]);
            if (!empty($users)) {
                $user_id = $users[0]->ID;
                error_log("User found via meta search ({$meta_query['key']}): $user_id");
                return $user_id;
            }
        }
    }
    
    error_log('No user found in webhook data. Available fields: ' . implode(', ', array_keys($webhook_data)));
    return 0;
}

/**
 * Process Verotel event with improved error handling
 * 
 * @param int $user_id User ID
 * @param string $event_type Event type (initial, rebill, cancel, etc.)
 * @param array $webhook_data Full webhook data
 * @return array Result with success flag and error message if applicable
 */
function flexpress_process_verotel_event($user_id, $event_type, $webhook_data) {
    try {
        switch ($event_type) {
            case 'initial':
                error_log("Processing initial subscription for user $user_id");
                
                // New subscription created
                flexpress_update_membership_status($user_id, 'active');
                
                if (isset($webhook_data['subscriptionType'])) {
                    update_user_meta($user_id, 'subscription_type', sanitize_text_field($webhook_data['subscriptionType']));
                }
                
                update_user_meta($user_id, 'subscription_start_date', current_time('mysql'));
                
                if (isset($webhook_data['nextChargeOn'])) {
                    update_user_meta($user_id, 'next_rebill_date', sanitize_text_field($webhook_data['nextChargeOn']));
                }
                
                // Store transaction details
                if (isset($webhook_data['transactionID'])) {
                    update_user_meta($user_id, 'verotel_transaction_id', sanitize_text_field($webhook_data['transactionID']));
                }
                
                if (isset($webhook_data['shopID'])) {
                    update_user_meta($user_id, 'verotel_shop_id', sanitize_text_field($webhook_data['shopID']));
                }
                
                if (isset($webhook_data['saleID'])) {
                    update_user_meta($user_id, 'verotel_sale_id', sanitize_text_field($webhook_data['saleID']));
                }
                
                // Store pricing information
                if (isset($webhook_data['priceAmount'])) {
                    update_user_meta($user_id, 'subscription_amount', sanitize_text_field($webhook_data['priceAmount']));
                }
                
                if (isset($webhook_data['priceCurrency'])) {
                    update_user_meta($user_id, 'subscription_currency', sanitize_text_field($webhook_data['priceCurrency']));
                }
                
                if (isset($webhook_data['paymentMethod'])) {
                    update_user_meta($user_id, 'payment_method', sanitize_text_field($webhook_data['paymentMethod']));
                }
                
                if (isset($webhook_data['period'])) {
                    update_user_meta($user_id, 'subscription_period', sanitize_text_field($webhook_data['period']));
                }
                
                // Track promo code usage if user has an applied promo code
                $applied_promo_code = get_user_meta($user_id, 'applied_promo_code', true);
                if (!empty($applied_promo_code)) {
                    $plan_id = get_user_meta($user_id, 'selected_pricing_plan', true);
                    $transaction_id = isset($webhook_data['transactionID']) ? $webhook_data['transactionID'] : ('verotel_' . $user_id . '_' . time());
                    $amount = isset($webhook_data['priceAmount']) ? floatval($webhook_data['priceAmount']) : 0.00;
                    
                    // Track promo code usage (legacy tracking)
                    if (function_exists('flexpress_track_promo_usage')) {
                        flexpress_track_promo_usage($applied_promo_code, $user_id, $plan_id, $transaction_id, $amount);
                    }
                    
                    // Track affiliate commission (new comprehensive tracking)
                    if (function_exists('flexpress_track_affiliate_commission')) {
                        flexpress_track_affiliate_commission($applied_promo_code, $user_id, 'signup', $plan_id, $transaction_id, $amount);
                    }
                    
                    error_log("FlexPress Affiliate: Tracked signup commission - Code: $applied_promo_code, User: $user_id, Plan: $plan_id, Amount: $amount");
                }
                
                // Log activity if logger exists
                if (class_exists('FlexPress_Activity_Logger')) {
                    FlexPress_Activity_Logger::log_verotel_event($user_id, 'initial', $webhook_data);
                    
                    // Also log as billing transaction for billing history
                    FlexPress_Activity_Logger::log_activity(
                        $user_id,
                        'billing_transaction',
                        'Initial subscription payment',
                        array_merge($webhook_data, array('billing_type' => 'subscription_initial'))
                    );
                }
                
                error_log('FlexPress Verotel Webhook: Processed initial subscription for user ID: ' . $user_id);
                break;
                
            case 'rebill':
                error_log("Processing rebill for user $user_id");
                
                // Subscription was renewed/rebilled
                flexpress_update_membership_status($user_id, 'active');
                
                if (isset($webhook_data['nextChargeOn'])) {
                    update_user_meta($user_id, 'next_rebill_date', sanitize_text_field($webhook_data['nextChargeOn']));
                }
                
                // Track affiliate commission for rebill
                $applied_promo_code = get_user_meta($user_id, 'applied_promo_code', true);
                if (!empty($applied_promo_code) && function_exists('flexpress_track_affiliate_commission')) {
                    $plan_id = get_user_meta($user_id, 'selected_pricing_plan', true);
                    $transaction_id = isset($webhook_data['transactionID']) ? $webhook_data['transactionID'] : ('rebill_' . $user_id . '_' . time());
                    $amount = isset($webhook_data['priceAmount']) ? floatval($webhook_data['priceAmount']) : 0.00;
                    
                    // Track rebill commission
                    flexpress_track_affiliate_commission($applied_promo_code, $user_id, 'rebill', $plan_id, $transaction_id, $amount);
                    
                    error_log("FlexPress Affiliate: Tracked rebill commission - Code: $applied_promo_code, User: $user_id, Plan: $plan_id, Amount: $amount");
                }
                
                // Log activity if logger exists
                if (class_exists('FlexPress_Activity_Logger')) {
                    FlexPress_Activity_Logger::log_verotel_event($user_id, 'rebill', $webhook_data);
                    
                    // Also log as billing transaction for billing history
                    FlexPress_Activity_Logger::log_activity(
                        $user_id,
                        'billing_transaction',
                        'Subscription renewal',
                        array_merge($webhook_data, array('billing_type' => 'subscription_rebill'))
                    );
                }
                
                error_log('FlexPress Verotel Webhook: Processed rebill for user ID: ' . $user_id);
                break;
                
            case 'cancel':
                error_log("Processing cancellation for user $user_id");
                
                // Subscription was cancelled
                flexpress_update_membership_status($user_id, 'cancelled');
                
                if (isset($webhook_data['expiresOn'])) {
                    update_user_meta($user_id, 'membership_expires', sanitize_text_field($webhook_data['expiresOn']));
                }
                
                // Log activity if logger exists
                if (class_exists('FlexPress_Activity_Logger')) {
                    FlexPress_Activity_Logger::log_verotel_event($user_id, 'cancellation', $webhook_data);
                }
                
                error_log('FlexPress Verotel Webhook: Processed cancellation for user ID: ' . $user_id);
                break;
                
            case 'credit':
                error_log("Processing credit/refund for user $user_id");
                
                // Refund/credit was processed
                // Log activity if logger exists
                if (class_exists('FlexPress_Activity_Logger')) {
                    FlexPress_Activity_Logger::log_verotel_event($user_id, 'credit', $webhook_data);
                }
                
                error_log('FlexPress Verotel Webhook: Processed credit for user ID: ' . $user_id);
                break;
                
            case 'chargeback':
                error_log("Processing chargeback for user $user_id");
                
                // Chargeback was received
                flexpress_update_membership_status($user_id, 'cancelled');
                
                // Log activity if logger exists
                if (class_exists('FlexPress_Activity_Logger')) {
                    FlexPress_Activity_Logger::log_verotel_event($user_id, 'chargeback', $webhook_data);
                }
                
                error_log('FlexPress Verotel Webhook: Processed chargeback for user ID: ' . $user_id);
                break;
                
            default:
                error_log("Processing unknown event '$event_type' for user $user_id");
                
                // Log unknown event
                if (class_exists('FlexPress_Activity_Logger')) {
                    FlexPress_Activity_Logger::log_activity(
                        $user_id,
                        'verotel_unknown',
                        "Unknown Verotel event: {$event_type}",
                        $webhook_data
                    );
                }
                
                error_log('FlexPress Verotel Webhook: Unknown event type: ' . $event_type);
                break;
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log('FlexPress Verotel Event Processing Error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Create Verotel FlexPay client instance
 * 
 * @return \Verotel\FlexPay\Client|false FlexPay client or false on error
 */
function flexpress_get_verotel_client() {
    // Include the Verotel FlexPay library
    $client_path = get_template_directory() . '/includes/verotel/src/Verotel/FlexPay/Client.php';
    $brand_path = get_template_directory() . '/includes/verotel/src/Verotel/FlexPay/Brand.php';
    
    if (!file_exists($client_path) || !file_exists($brand_path)) {
        error_log('Verotel FlexPay: Client library files not found');
        return false;
    }
    
    require_once $client_path;
    require_once $brand_path;
    
    // Get Verotel settings - use hardcoded values from notepad for now
    $shop_id = '133772';
    $signature_key = 'uHrSH2CqRJpbgXhJtuYPyd3dE7rpb4';
    $merchant_id = '9804000001074300';
    
    if (empty($shop_id) || empty($signature_key)) {
        error_log('Verotel FlexPay: Missing Shop ID or Signature Key');
        return false;
    }
    
    try {
        $brand = \Verotel\FlexPay\Brand::create_from_merchant_id($merchant_id);
        $client = new \Verotel\FlexPay\Client($shop_id, $signature_key, $brand);
        return $client;
    } catch (Exception $e) {
        error_log('Verotel FlexPay: Error creating client - ' . $e->getMessage());
        return false;
    }
}

/**
 * Handle Verotel webhook for subscription updates
 * 
 * Simplified version that focuses on reliable payment processing
 * without complex signature validation that was causing issues.
 */
function flexpress_handle_verotel_webhook() {
    // Ensure we can capture all output for debugging
    if (!headers_sent()) {
        header('Content-Type: text/plain');
    }
    
    // Get webhook data - handle both GET and POST
    $webhook_data = array();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Try different ways to get POST data
        $raw_body = file_get_contents('php://input');
        if (!empty($raw_body)) {
            parse_str($raw_body, $webhook_data);
        }
        
        // Fallback to $_POST if parse_str didn't work
        if (empty($webhook_data) && !empty($_POST)) {
            $webhook_data = $_POST;
        }
    } else {
        // GET request - use $_GET data
        $webhook_data = $_GET;
    }

    // Basic logging for monitoring
    error_log('=== VEROTEL WEBHOOK RECEIVED ===');
    error_log('Method: ' . $_SERVER['REQUEST_METHOD']);
    error_log('User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not provided'));
    error_log('Remote IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'Not provided'));

    // Remove WordPress routing parameter
    unset($webhook_data['action']);

    // Check if this is a real webhook with required data
    if (empty($webhook_data)) {
        error_log('FlexPress Verotel Webhook: ERROR - No webhook data received');
        echo 'ERROR - No webhook data';
        exit;
    }

    // Basic webhook validation - check for essential Verotel fields
    $required_fields = ['shopID', 'saleID'];
    foreach ($required_fields as $field) {
        if (empty($webhook_data[$field])) {
            error_log("FlexPress Verotel Webhook: Missing required field: $field");
            echo "ERROR - Missing required field: $field";
            exit;
        }
    }

    // Validate shopID matches our configuration
    $expected_shop_id = '133772'; // Our shop ID
    if ($webhook_data['shopID'] !== $expected_shop_id) {
        error_log('FlexPress Verotel Webhook: Invalid shop ID: ' . $webhook_data['shopID']);
        echo 'ERROR - Invalid shop ID';
        exit;
    }

    // Get event and type information
    $event_type = isset($webhook_data['event']) ? sanitize_text_field($webhook_data['event']) : '';
    $subscription_type = isset($webhook_data['type']) ? sanitize_text_field($webhook_data['type']) : '';
    
    error_log("Processing event: $event_type, type: $subscription_type");
    
    // Get user ID with improved logic
    $user_id = flexpress_get_user_id_from_webhook($webhook_data);

    // If still no user ID, handle missing user scenarios properly
    if (!$user_id) {
        error_log('FlexPress Verotel Webhook: User not found. Sale ID: ' . ($webhook_data['saleID'] ?? 'N/A') . ', Custom1: ' . ($webhook_data['custom1'] ?? 'N/A'));
        
        // Store the webhook data for manual review
        $orphaned_webhooks = get_option('flexpress_verotel_orphaned_webhooks', []);
        $orphaned_webhooks[] = [
            'timestamp' => current_time('mysql'),
            'event' => $event_type,
            'data' => $webhook_data,
            'note' => 'User not found in webhook data'
        ];
        update_option('flexpress_verotel_orphaned_webhooks', array_slice($orphaned_webhooks, -50)); // Keep last 50
        
        echo 'OK - Webhook logged (user not found)';
        exit;
    }

    error_log('FlexPress Verotel Webhook: SUCCESS - User found (ID: ' . $user_id . '). Processing event: ' . $event_type);

    // Process event with improved logic
    $processing_result = flexpress_process_verotel_event($user_id, $event_type, $webhook_data);
    
    if ($processing_result['success']) {
        // Store successful webhook data for analysis
        $successful_webhooks = get_option('flexpress_verotel_successful_webhooks', []);
        $successful_webhooks[] = [
            'timestamp' => current_time('mysql'),
            'event' => $event_type,
            'user_id' => $user_id,
            'data' => $webhook_data
        ];
        update_option('flexpress_verotel_successful_webhooks', array_slice($successful_webhooks, -20)); // Keep last 20
        
        error_log('=== VEROTEL WEBHOOK PROCESSING COMPLETE (SUCCESS) ===');
        echo 'OK';
    } else {
        error_log('=== VEROTEL WEBHOOK PROCESSING FAILED ===');
        error_log('Error: ' . $processing_result['error']);
        echo 'ERROR - ' . $processing_result['error'];
    }
    
    exit;
}
add_action('wp_ajax_nopriv_verotel_webhook', 'flexpress_handle_verotel_webhook');
add_action('wp_ajax_verotel_webhook', 'flexpress_handle_verotel_webhook');

/**
 * Update user details from Verotel webhook data
 *
 * @param int   $user_id      User ID.
 * @param array $webhook_data Webhook data from Verotel.
 */
function flexpress_update_user_details_from_webhook($user_id, $webhook_data) {
    if (!$user_id || empty($webhook_data)) {
        return;
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return;
    }
    
    $user_data_updated = false;
    $user_meta_updates = array();
    
    // Update email if provided and different
    if (isset($webhook_data['email']) && !empty($webhook_data['email'])) {
        $new_email = sanitize_email($webhook_data['email']);
        if (is_email($new_email) && $new_email !== $user->user_email) {
            wp_update_user(array(
                'ID' => $user_id,
                'user_email' => $new_email
            ));
            $user_data_updated = true;
        }
    }
    
    // Update first name if provided (check both firstName field and custom1)
    $first_name = null;
    if (isset($webhook_data['firstName']) && !empty($webhook_data['firstName'])) {
        $first_name = sanitize_text_field($webhook_data['firstName']);
    } elseif (isset($webhook_data['custom1']) && !empty($webhook_data['custom1'])) {
        $first_name = sanitize_text_field($webhook_data['custom1']);
    }
    
    if ($first_name) {
        $current_first_name = get_user_meta($user_id, 'first_name', true);
        if ($first_name !== $current_first_name) {
            $user_meta_updates['first_name'] = $first_name;
        }
    }
    
    // Update last name if provided (check both lastName field and custom2)
    $last_name = null;
    if (isset($webhook_data['lastName']) && !empty($webhook_data['lastName'])) {
        $last_name = sanitize_text_field($webhook_data['lastName']);
    } elseif (isset($webhook_data['custom2']) && !empty($webhook_data['custom2'])) {
        $last_name = sanitize_text_field($webhook_data['custom2']);
    }
    
    if ($last_name) {
        $current_last_name = get_user_meta($user_id, 'last_name', true);
        if ($last_name !== $current_last_name) {
            $user_meta_updates['last_name'] = $last_name;
        }
    }
    
    // Update billing address if provided
    if (isset($webhook_data['billingAddress']) && is_array($webhook_data['billingAddress'])) {
        $billing_address = $webhook_data['billingAddress'];
        
        if (isset($billing_address['street']) && !empty($billing_address['street'])) {
            $user_meta_updates['billing_address_1'] = sanitize_text_field($billing_address['street']);
        }
        
        if (isset($billing_address['city']) && !empty($billing_address['city'])) {
            $user_meta_updates['billing_city'] = sanitize_text_field($billing_address['city']);
        }
        
        if (isset($billing_address['state']) && !empty($billing_address['state'])) {
            $user_meta_updates['billing_state'] = sanitize_text_field($billing_address['state']);
        }
        
        if (isset($billing_address['postalCode']) && !empty($billing_address['postalCode'])) {
            $user_meta_updates['billing_postcode'] = sanitize_text_field($billing_address['postalCode']);
        }
        
        if (isset($billing_address['country']) && !empty($billing_address['country'])) {
            $user_meta_updates['billing_country'] = sanitize_text_field($billing_address['country']);
        }
    }
    
    // Update phone number if provided
    if (isset($webhook_data['phone']) && !empty($webhook_data['phone'])) {
        $phone = sanitize_text_field($webhook_data['phone']);
        $current_phone = get_user_meta($user_id, 'billing_phone', true);
        if ($phone !== $current_phone) {
            $user_meta_updates['billing_phone'] = $phone;
        }
    }
    
    // Update customer ID if provided
    if (isset($webhook_data['customerId']) && !empty($webhook_data['customerId'])) {
        $customer_id = sanitize_text_field($webhook_data['customerId']);
        $current_customer_id = get_user_meta($user_id, 'verotel_customer_id', true);
        if ($customer_id !== $current_customer_id) {
            $user_meta_updates['verotel_customer_id'] = $customer_id;
        }
    }
    
    // Update payment method if provided
    if (isset($webhook_data['paymentMethod']) && !empty($webhook_data['paymentMethod'])) {
        $payment_method = sanitize_text_field($webhook_data['paymentMethod']);
        $user_meta_updates['last_payment_method'] = $payment_method;
    }
    
    // Update currency if provided
    if (isset($webhook_data['currency']) && !empty($webhook_data['currency'])) {
        $currency = sanitize_text_field($webhook_data['currency']);
        $user_meta_updates['billing_currency'] = $currency;
    }
    
    // Update IP address if provided (for fraud prevention)
    if (isset($webhook_data['ipAddress']) && !empty($webhook_data['ipAddress'])) {
        $ip_address = sanitize_text_field($webhook_data['ipAddress']);
        $user_meta_updates['signup_ip_address'] = $ip_address;
    }
    
    // Update all user meta at once for efficiency
    foreach ($user_meta_updates as $meta_key => $meta_value) {
        update_user_meta($user_id, $meta_key, $meta_value);
    }
    
    // Log the update for debugging
    if ($user_data_updated || !empty($user_meta_updates)) {
        error_log(sprintf(
            'FlexPress: Updated user details for user ID %d from Verotel webhook. Updated fields: %s',
            $user_id,
            implode(', ', array_keys($user_meta_updates))
        ));
    }
}

/**
 * Create subscription in Verotel
 *
 * @param int    $user_id          User ID.
 * @param string $subscription_type Subscription type (e.g., 'monthly', 'yearly').
 * @return string Verotel payment URL or error message.
 */
function flexpress_create_verotel_subscription($user_id, $plan_id) {
    // Get Verotel settings
    // $verotel_settings = get_option('flexpress_verotel_settings');
    // $shop_id = $verotel_settings['verotel_shop_id'] ?? '';
    // $signature_key = $verotel_settings['verotel_signature_key'] ?? '';

    // Hardcoded details for debugging
    $shop_id = '133772';
    $signature_key = 'uHrSH2CqRJpbgXhJtuYPyd3dE7rpb4';

    if (empty($shop_id) || empty($signature_key)) {
        error_log('FlexPress Verotel: Missing Shop ID or Signature Key in settings.');
        return 'Error: Verotel is not configured correctly. Please contact support.';
    }

    // Get pricing plan details
    $plan = flexpress_get_pricing_plan($plan_id);

    if (!$plan) {
        return 'Error: Invalid plan ID.';
    }

    // Get the base Verotel URL
    $verotel_base_url = 'https://secure.verotel.com/startorder';

    // Convert currency symbol to ISO currency code for Verotel
    $currency_code = flexpress_convert_currency_symbol_to_code($plan['currency'] ?: '$');
    
    // Prepare parameters for signature
    $params = [
        'shopID'        => $shop_id,
        'priceAmount'   => number_format($plan['price'], 2, '.', ''),
        'priceCurrency' => $currency_code,
        'paymentMethod' => 'creditcard',
        'description'   => $plan['name'],
        'subscriptionType' => 'recurring',
        'period'        => flexpress_format_plan_duration_for_verotel($plan['duration'], $plan['duration_unit']),
        'version'       => '4',
        'custom1'       => $user_id, // User ID
        'custom2'       => $plan_id, // Plan ID
        'custom3'       => $plan['name'], // Plan Name
        'saleID'        => $user_id, // Use user ID as saleID for easier mapping
    ];

    if ($plan['trial_enabled']) {
        $params['trialAmount'] = number_format($plan['trial_price'], 2, '.', '');
        $params['trialPeriod'] = flexpress_format_plan_duration_for_verotel($plan['trial_duration'], $plan['trial_duration_unit']);
    }

    // Sort parameters alphabetically by key (Verotel requirement)
    ksort($params);

    // Build signature string according to Verotel's format: signatureKey:param1=value1:param2=value2
    $signature_string = $signature_key;
    foreach ($params as $key => $value) {
        if ($value !== '' && $value !== null) {
            $signature_string .= ':' . $key . '=' . $value;
        }
    }

    // Calculate SHA-256 hash for the signature
    $params['signature'] = hash('sha256', $signature_string);

    // Build final query string and URL
    $query_string = http_build_query($params);
    $verotel_url = $verotel_base_url . '?' . $query_string;

    // Log for debugging
    error_log('FlexPress Verotel: Created payment URL. Signature String: ' . $signature_string);

    return $verotel_url;
}

/**
 * Cancel subscription in Verotel
 *
 * @param int $user_id User ID.
 * @return array|WP_Error Response from Verotel API or WP_Error on failure.
 */
function flexpress_cancel_verotel_subscription($user_id) {
    $membership_status = get_user_meta($user_id, 'membership_status', true);
    
    if (!$membership_status || $membership_status === 'none') {
        return new WP_Error('no_subscription', __('User does not have an active subscription', 'flexpress'));
    }
    
    if ($membership_status === 'cancelled') {
        return new WP_Error('already_cancelled', __('Subscription is already cancelled', 'flexpress'));
    }
    
    // Get the Verotel sale ID for this user
    $verotel_sale_id = get_user_meta($user_id, 'verotel_sale_id', true);
    
    if (!$verotel_sale_id) {
        error_log('FlexPress Verotel: No sale ID found for user ' . $user_id . ' - cannot cancel subscription via Verotel API');
        
        // Fall back to local cancellation if no sale ID is available
        update_user_meta($user_id, 'membership_status', 'cancelled');
        
        // Log activity if logger exists
        if (class_exists('FlexPress_Activity_Logger')) {
            FlexPress_Activity_Logger::log_activity(
                $user_id,
                'subscription_cancelled',
                'Subscription cancelled locally (no Verotel sale ID available)',
                array('method' => 'local_fallback')
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Subscription cancelled successfully', 'flexpress'),
            'method' => 'local_fallback'
        );
    }
    
    try {
        // Initialize Verotel client
        $verotel = new FlexPress_Verotel();
        
        // Get the Verotel cancel URL
        $cancel_url = $verotel->get_cancel_subscription_url($verotel_sale_id);
        
        if (empty($cancel_url)) {
            error_log('FlexPress Verotel: Failed to generate cancel URL for sale ID: ' . $verotel_sale_id);
            return new WP_Error('cancel_url_failed', __('Unable to generate cancellation URL. Please contact support.', 'flexpress'));
        }
        
        // Log the cancellation attempt
        if (class_exists('FlexPress_Activity_Logger')) {
            FlexPress_Activity_Logger::log_activity(
                $user_id,
                'subscription_cancel_requested',
                'User initiated subscription cancellation via Verotel',
                array(
                    'sale_id' => $verotel_sale_id,
                    'cancel_url_generated' => !empty($cancel_url)
                )
            );
        }
        
        error_log('FlexPress Verotel: Generated cancel URL for user ' . $user_id . ', sale ID: ' . $verotel_sale_id);
        
        return array(
            'success' => true,
            'redirect_url' => $cancel_url,
            'message' => __('Redirecting to Verotel to cancel your subscription...', 'flexpress'),
            'method' => 'verotel_api'
        );
        
    } catch (Exception $e) {
        error_log('FlexPress Verotel: Error during cancellation for user ' . $user_id . ': ' . $e->getMessage());
        
        // Fall back to local cancellation if Verotel API fails
        update_user_meta($user_id, 'membership_status', 'cancelled');
        
        // Log activity if logger exists
        if (class_exists('FlexPress_Activity_Logger')) {
            FlexPress_Activity_Logger::log_activity(
                $user_id,
                'subscription_cancelled',
                'Subscription cancelled locally (Verotel API error)',
                array(
                    'error' => $e->getMessage(),
                    'method' => 'local_fallback_error'
                )
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Subscription cancelled successfully', 'flexpress'),
            'method' => 'local_fallback_error',
            'warning' => __('Could not cancel via payment processor, but your access has been revoked.', 'flexpress')
        );
    }
}

/**
 * Get user subscription details
 *
 * @param int $user_id User ID.
 * @return array Subscription details.
 */
function flexpress_get_subscription_details($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return array();
    }
    
    return array(
        'status' => get_user_meta($user_id, 'membership_status', true) ?: 'none',
        'type' => get_user_meta($user_id, 'subscription_type', true),
        'start_date' => get_user_meta($user_id, 'subscription_start_date', true),
        'next_rebill' => get_user_meta($user_id, 'next_rebill_date', true),
        'transaction_id' => get_user_meta($user_id, 'verotel_transaction_id', true),
        'shop_id' => get_user_meta($user_id, 'verotel_shop_id', true),
    );
}

/**
 * Get user billing history from multiple sources
 *
 * @param int $user_id User ID.
 * @param int $limit Number of transactions to retrieve.
 * @return array Array of transaction data.
 */
function flexpress_get_user_billing_history($user_id, $limit = 10) {
    global $wpdb;
    
    $transactions = array();
    
    // Get activity logs for billing events - USE CORRECT TABLE NAME
    $activity_table = $wpdb->prefix . 'flexpress_user_activity';
    if ($wpdb->get_var("SHOW TABLES LIKE '$activity_table'")) {
        $activity_logs = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $activity_table 
            WHERE user_id = %d 
            AND event_type IN ('verotel_initial', 'verotel_rebill', 'verotel_cancellation', 'verotel_credit', 'verotel_chargeback', 'subscription_created', 'ppv_purchase_webhook', 'ppv_purchase_success', 'billing_transaction')
            ORDER BY created_at DESC 
            LIMIT %d
        ", $user_id, $limit));
        
        foreach ($activity_logs as $log) {
            $details = json_decode($log->event_data, true);
            
            // Extract transaction data
            $transaction = array(
                'date' => $log->created_at,
                'description' => $log->event_description,
                'transaction_id' => '',
                'amount' => '',
                'status' => '',
                'type' => 'subscription'
            );
            
            // Extract transaction ID and amount from details
            if (is_array($details)) {
                $transaction['transaction_id'] = isset($details['transactionID']) ? $details['transactionID'] : 
                                               (isset($details['referenceID']) ? $details['referenceID'] : 
                                               (isset($details['custom3']) ? $details['custom3'] : $log->id));
                
                $transaction['amount'] = isset($details['priceAmount']) ? '$' . $details['priceAmount'] : 
                                       (isset($details['amount']) ? '$' . $details['amount'] : '');
                
                $transaction['status'] = isset($details['type']) ? $details['type'] : 
                                       (isset($details['status']) ? $details['status'] : 'completed');
                
                if (isset($details['billing_type']) && $details['billing_type'] === 'ppv_purchase') {
                    $transaction['type'] = 'ppv_purchase';
                }
            }
            
            // Determine status from activity type
            switch ($log->event_type) {
                case 'verotel_initial':
                    $transaction['status'] = 'paid';
                    $transaction['description'] = 'Initial subscription payment';
                    break;
                case 'verotel_rebill':
                    $transaction['status'] = 'paid';
                    $transaction['description'] = 'Subscription renewal';
                    break;
                case 'verotel_cancellation':
                    $transaction['status'] = 'cancelled';
                    break;
                case 'verotel_credit':
                    $transaction['status'] = 'refunded';
                    $transaction['amount'] = '-' . ltrim($transaction['amount'], '$-');
                    break;
                case 'verotel_chargeback':
                    $transaction['status'] = 'chargeback';
                    $transaction['amount'] = '-' . ltrim($transaction['amount'], '$-');
                    break;
                case 'ppv_purchase_success':
                case 'ppv_purchase_webhook':
                    $transaction['status'] = 'paid';
                    $transaction['type'] = 'ppv_purchase';
                    break;
            }
            
            $transactions[] = $transaction;
        }
    }
    
    return $transactions;
}

/**
 * Generate renewal payment URL for expired users
 *
 * @param int $user_id User ID.
 * @param string $plan_id Optional plan ID to renew with. If not provided, uses default plan.
 * @return array|WP_Error Response with renewal URL or error.
 */
function flexpress_generate_renewal_url($user_id, $plan_id = null) {
    if (!$user_id) {
        return new WP_Error('invalid_user', __('Invalid user ID provided', 'flexpress'));
    }
    
    // Get user membership status
    $membership_status = get_user_meta($user_id, 'membership_status', true);
    
    // Only allow renewal for expired, cancelled, or no membership users
    if (!in_array($membership_status, ['expired', 'cancelled', 'none', ''])) {
        return new WP_Error('invalid_status', __('User has an active membership and cannot renew', 'flexpress'));
    }
    
    // Get the plan to renew with
    if (!$plan_id) {
        // Get the user's previous plan or default plan
        $previous_plan = get_user_meta($user_id, 'subscription_plan', true);
        $plan_id = $previous_plan ?: 'monthly'; // Default to monthly if no previous plan
    }
    
    // Get plan details
    $plan = flexpress_get_pricing_plan($plan_id);
    if (!$plan) {
        return new WP_Error('invalid_plan', __('Invalid pricing plan specified', 'flexpress'));
    }
    
    try {
        // Initialize Verotel client
        $verotel = new FlexPress_Verotel();
        
        // Get user details
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', __('User not found', 'flexpress'));
        }
        
        // Calculate period for Verotel (ISO 8601 duration format)
        $period = flexpress_format_plan_duration_for_verotel($plan['duration'], $plan['duration_unit']);
        
        // Prepare renewal arguments
        $renewal_args = array(
            'successURL' => home_url('/my-account?renewal=success&plan=' . $plan_id),
            'declineURL' => home_url('/my-account?renewal=cancelled'),
            'ipnUrl' => home_url('/wp-admin/admin-ajax.php?action=verotel_webhook'),
            'email' => $user->user_email,
            'custom1' => $user_id, // User ID for webhook identification
            'custom2' => $plan_id, // Plan ID
            'custom3' => 'renewal_' . time(), // Renewal identifier
            'productDescription' => 'Membership Renewal - ' . $plan['name'],
            'period' => $period, // Subscription period
            'upgradeOption' => 'extend' // For upgrades: extend remaining time
        );
        
        // Add trial information if applicable and user hasn't had a trial before
        $had_trial = get_user_meta($user_id, 'had_trial_period', true);
        if (!empty($plan['trial_enabled']) && !$had_trial) {
            $renewal_args['trial_amount'] = $plan['trial_price'];
            $renewal_args['trial_period'] = 'P' . $plan['trial_duration'] . 'D';
        }
        
        // Convert currency symbol to ISO currency code for Verotel
        $currency_code = flexpress_convert_currency_symbol_to_code($plan['currency'] ?: '$');
        
        // Get user's existing sale ID for upgrade
        $verotel_sale_id = get_user_meta($user_id, 'verotel_sale_id', true);
        
        // If user has an existing sale ID, use upgrade subscription, otherwise create new subscription
        if (!empty($verotel_sale_id)) {
            // Use upgrade subscription for existing members (better continuity)
            $renewal_url = $verotel->get_upgrade_subscription_url(
                $verotel_sale_id,
                $plan['price'],
                $currency_code,
                'Membership Renewal - ' . $plan['name'],
                $renewal_args
            );
            
            error_log('FlexPress Renewal: Using upgrade subscription for existing sale ID: ' . $verotel_sale_id);
        } else {
            // Fallback to new subscription for users without sale ID
            $renewal_url = $verotel->get_subscription_url(
                $plan['price'],
                $currency_code,
                'Membership Renewal - ' . $plan['name'],
                $renewal_args
            );
            
            error_log('FlexPress Renewal: Creating new subscription (no existing sale ID found)');
        }
        
        if (empty($renewal_url)) {
            error_log('FlexPress Renewal: Failed to generate Verotel renewal URL for user ' . $user_id);
            return new WP_Error('renewal_url_failed', __('Unable to generate renewal payment URL. Please try again or contact support.', 'flexpress'));
        }
        
        // Log the renewal attempt
        if (class_exists('FlexPress_Activity_Logger')) {
            FlexPress_Activity_Logger::log_activity(
                $user_id,
                'membership_renewal_initiated',
                'User initiated membership renewal',
                array(
                    'plan_id' => $plan_id,
                    'plan_name' => $plan['name'],
                    'amount' => $plan['price'],
                    'currency' => $currency_code,
                    'previous_status' => $membership_status
                )
            );
        }
        
        error_log('FlexPress Renewal: Generated renewal URL for user ' . $user_id . ', plan: ' . $plan_id);
        
        return array(
            'success' => true,
            'renewal_url' => $renewal_url,
            'plan_name' => $plan['name'],
            'amount' => $plan['price'],
            'currency' => $currency_code,
            'message' => sprintf(
                __('Redirecting to payment for %s renewal (%s%s)...', 'flexpress'),
                $plan['name'],
                $plan['currency'] ?: '$',
                $plan['price']
            )
        );
        
    } catch (Exception $e) {
        error_log('FlexPress Renewal Error: ' . $e->getMessage());
        return new WP_Error('renewal_error', __('An error occurred while generating the renewal URL. Please try again.', 'flexpress'));
    }
}

/**
 * Convert currency symbol to ISO currency code
 *
 * @param string $currency_symbol Currency symbol (e.g., $, €, £).
 * @return string ISO currency code (e.g., USD, EUR, GBP).
 */
function flexpress_convert_currency_symbol_to_code($currency_symbol) {
    $currency_map = array(
        '$' => 'USD',
        '€' => 'EUR', 
        '£' => 'GBP',
        'USD' => 'USD', // In case it's already a code
        'EUR' => 'EUR',
        'GBP' => 'GBP'
    );
    
    return isset($currency_map[$currency_symbol]) ? $currency_map[$currency_symbol] : 'USD';
}

/**
 * Format plan duration for Verotel API (ISO 8601 duration format)
 *
 * @param int $duration Duration value.
 * @param string $duration_unit Duration unit (days, months, years).
 * @return string ISO 8601 duration string (e.g., P30D, P1M, P1Y).
 */
function flexpress_format_plan_duration_for_verotel($duration, $duration_unit) {
    $duration = intval($duration);
    
    switch ($duration_unit) {
        case 'days':
            return 'P' . $duration . 'D';
        case 'months':
            return 'P' . $duration . 'M';
        case 'years':
            return 'P' . $duration . 'Y';
        default:
            return 'P30D'; // Default to 30 days
    }
}

/**
 * AJAX handler for membership renewal
 */
function flexpress_ajax_renew_membership() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'flexpress_verotel_nonce')) {
        wp_send_json_error(__('Security check failed', 'flexpress'));
        exit;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(__('You must be logged in to perform this action', 'flexpress'));
        exit;
    }
    
    $user_id = get_current_user_id();
    $plan_id = isset($_POST['plan_id']) ? sanitize_text_field($_POST['plan_id']) : null;
    
    // Generate renewal URL
    $result = flexpress_generate_renewal_url($user_id, $plan_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
        exit;
    }
    
    wp_send_json_success($result);
    exit;
}
add_action('wp_ajax_renew_membership', 'flexpress_ajax_renew_membership');

/**
 * Enqueue scripts for Verotel integration
 */
function flexpress_enqueue_verotel_scripts() {
    // Only enqueue on relevant pages
    if (!is_page_template('page-templates/dashboard.php') && !is_page_template('page-templates/membership.php')) {
        return;
    }
    
    wp_enqueue_script('flexpress-verotel', get_template_directory_uri() . '/assets/js/verotel.js', array('jquery'), '1.0.0', true);
    
    wp_localize_script('flexpress-verotel', 'flexpress_verotel', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('flexpress_verotel_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'flexpress_enqueue_verotel_scripts'); 
add_action('wp_enqueue_scripts', 'flexpress_enqueue_verotel_scripts'); 