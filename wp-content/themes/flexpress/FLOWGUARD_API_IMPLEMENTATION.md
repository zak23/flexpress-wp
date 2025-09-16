# 🚀 Flowguard API Implementation Guide

## 📋 **API Overview**

Based on the official Flowguard API documentation, here's the complete implementation guide for integrating Flowguard into FlexPress.

## 🔑 **API Endpoints**

### **Base URL**
```
https://api.yoursafe.com/api/merchant
```

### **Authentication**
All endpoints use **JWT (JSON Web Token)** authentication with:
- **Algorithm**: HS256
- **Type**: JWT
- **Signature Key**: From ControlCenter

## 🏗️ **Core API Endpoints**

### **1. Subscription Start**
```
POST /subscription/start
```

**Purpose**: Initiate subscription process and get sessionId for Flowguard SDK

**JWT Payload Required Fields**:
```json
{
  "shopId": "65151",                    // Shop ID from ControlCenter
  "priceAmount": "10.00",              // Format: NNN.NN
  "priceCurrency": "USD",              // USD, EUR, GBP, AUD, CAD, CHF, DKK, NOK, SEK
  "successUrl": "https://merchant.com/success",
  "declineUrl": "https://merchant.com/decline", 
  "postbackUrl": "https://merchant.com/postback",
  "email": "shopper@example.com",
  "subscriptionType": "recurring",     // recurring, one-time
  "period": "P1M",                     // ISO 8601 format (P30D, P1M, P1Y)
  "referenceId": "optional_ref_123",   // Optional, max 100 chars
  "trialAmount": "5.00",               // Optional, format NNN.NN
  "trialPeriod": "P3D"                 // Optional, ISO 8601 format
}
```

**Response**:
```json
{
  "sessionId": "uuid-string"
}
```

### **2. Purchase Start**
```
POST /purchase/start
```

**Purpose**: Initiate single purchase and get sessionId for Flowguard SDK

**JWT Payload Required Fields**:
```json
{
  "shopId": "65151",
  "priceAmount": "10.00",
  "priceCurrency": "USD", 
  "successUrl": "https://merchant.com/success",
  "declineUrl": "https://merchant.com/decline",
  "postbackUrl": "https://merchant.com/postback",
  "email": "shopper@example.com",
  "referenceId": "optional_ref_123"    // Optional, max 100 chars
}
```

**Response**:
```json
{
  "sessionId": "uuid-string"
}
```

### **3. Subscription Cancel**
```
POST /subscription/cancel
```

**Purpose**: Cancel an existing subscription

**JWT Payload Required Fields**:
```json
{
  "saleId": "2080",                    // Sale ID of subscription to cancel
  "shopId": "65151",
  "cancelledBy": "merchant"            // Optional: merchant, buyer
}
```

## 🔄 **Postback System (Webhooks)**

### **Postback Format**
- **Content**: JWT token signed with your signature key
- **Response Required**: HTTP 200 OK within 30 seconds
- **Retry Policy**: Up to 3 retries if not 200 OK
- **Auto-refund**: Payment automatically refunded if postback fails

### **Postback Event Types**

#### **Purchase Events**
```json
// Purchase Approved
{
  "postbackType": "approved",
  "orderType": "purchase",
  "saleId": "2080",
  "transactionId": "12345",
  "shopId": "65151",
  "priceAmount": "10.00",
  "priceCurrency": "USD",
  "referenceId": "optional_ref_123"
}

// Purchase Refund (Chargeback/Credit)
{
  "postbackType": "chargeback", // or "credit"
  "orderType": "purchase",
  "saleId": "2080",
  "transactionId": "12346",
  "shopId": "65151",
  "parentId": "12345",          // Original transaction ID
  "priceAmount": "10.00",
  "priceCurrency": "USD",
  "referenceId": "optional_ref_123"
}
```

#### **Subscription Events**
```json
// Subscription Approved
{
  "postbackType": "approved",
  "orderType": "subscription",
  "saleId": "2080",
  "transactionId": "12345",
  "shopId": "65151",
  "priceAmount": "10.00",
  "priceCurrency": "USD",
  "subscriptionType": "recurring",     // recurring, one-time
  "subscriptionPhase": "normal",       // normal, trial
  "nextChargeOn": "2024-02-01",        // For recurring subscriptions
  "expiresOn": "2024-02-01",           // For one-time subscriptions
  "referenceId": "optional_ref_123"
}

// Subscription Rebill
{
  "postbackType": "rebill",
  "orderType": "subscription",
  "saleId": "2080",
  "transactionId": "12346",
  "shopId": "65151",
  "priceAmount": "10.00",
  "priceCurrency": "USD",
  "subscriptionType": "recurring",
  "subscriptionPhase": "normal",
  "nextChargeOn": "2024-03-01",
  "referenceId": "optional_ref_123"
}

// Subscription Cancel
{
  "postbackType": "cancel",
  "orderType": "subscription",
  "saleId": "2080",
  "shopId": "65151",
  "subscriptionType": "recurring",
  "subscriptionPhase": "normal",
  "cancelledBy": "user",               // user, support, merchant, system
  "expiresOn": "2024-02-01",           // When access expires
  "referenceId": "optional_ref_123"
}

// Subscription Expiration
{
  "postbackType": "expiry",
  "orderType": "subscription",
  "saleId": "2080",
  "shopId": "65151",
  "subscriptionType": "recurring",     // recurring, one-time
  "referenceId": "optional_ref_123"
}
```

## 💻 **PHP Implementation**

### **1. Flowguard API Client**

```php
<?php
/**
 * Flowguard API Client
 */
class FlexPress_Flowguard_API {
    private $api_base_url = 'https://api.yoursafe.com/api/merchant';
    private $shop_id;
    private $signature_key;
    private $environment;
    
    public function __construct($shop_id, $signature_key, $environment = 'sandbox') {
        $this->shop_id = $shop_id;
        $this->signature_key = $signature_key;
        $this->environment = $environment;
        
        if ($environment === 'sandbox') {
            $this->api_base_url = 'https://sandbox-api.yoursafe.com/api/merchant';
        }
    }
    
    /**
     * Create JWT token for API requests
     */
    private function create_jwt($payload) {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode($payload);
        
        $header_encoded = $this->base64url_encode($header);
        $payload_encoded = $this->base64url_encode($payload);
        
        $signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, $this->signature_key, true);
        $signature_encoded = $this->base64url_encode($signature);
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    }
    
    /**
     * Base64 URL encode
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Start subscription
     */
    public function start_subscription($data) {
        $payload = array_merge([
            'shopId' => $this->shop_id,
            'subscriptionType' => 'recurring',
            'period' => 'P30D'
        ], $data);
        
        $jwt = $this->create_jwt($payload);
        
        $response = wp_remote_post($this->api_base_url . '/subscription/start', [
            'headers' => [
                'Content-Type' => 'application/jwt',
                'Authorization' => 'Bearer ' . $jwt
            ],
            'body' => $jwt,
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }
        
        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            $data = json_decode($body, true);
            return ['success' => true, 'session_id' => $data['sessionId']];
        }
        
        return ['success' => false, 'error' => 'API Error: ' . $status_code];
    }
    
    /**
     * Start purchase
     */
    public function start_purchase($data) {
        $payload = array_merge([
            'shopId' => $this->shop_id
        ], $data);
        
        $jwt = $this->create_jwt($payload);
        
        $response = wp_remote_post($this->api_base_url . '/purchase/start', [
            'headers' => [
                'Content-Type' => 'application/jwt',
                'Authorization' => 'Bearer ' . $jwt
            ],
            'body' => $jwt,
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }
        
        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            $data = json_decode($body, true);
            return ['success' => true, 'session_id' => $data['sessionId']];
        }
        
        return ['success' => false, 'error' => 'API Error: ' . $status_code];
    }
    
    /**
     * Cancel subscription
     */
    public function cancel_subscription($sale_id, $cancelled_by = 'merchant') {
        $payload = [
            'shopId' => $this->shop_id,
            'saleId' => $sale_id,
            'cancelledBy' => $cancelled_by
        ];
        
        $jwt = $this->create_jwt($payload);
        
        $response = wp_remote_post($this->api_base_url . '/subscription/cancel', [
            'headers' => [
                'Content-Type' => 'application/jwt',
                'Authorization' => 'Bearer ' . $jwt
            ],
            'body' => $jwt,
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'API Error: ' . $status_code];
    }
}
```

### **2. Flowguard Integration Functions**

```php
<?php
/**
 * Flowguard Integration Functions
 */

/**
 * Create Flowguard subscription
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
    
    $flowguard_settings = get_option('flexpress_flowguard_settings', []);
    $api = new FlexPress_Flowguard_API(
        $flowguard_settings['shop_id'],
        $flowguard_settings['signature_key'],
        $flowguard_settings['environment']
    );
    
    $subscription_data = [
        'priceAmount' => number_format($plan['price'], 2, '.', ''),
        'priceCurrency' => flexpress_convert_currency_symbol_to_code($plan['currency']),
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
            'payment_url' => home_url('/flowguard-payment?session_id=' . $result['session_id'])
        ];
    }
    
    return $result;
}

/**
 * Create Flowguard PPV purchase
 */
function flexpress_flowguard_create_ppv_purchase($user_id, $episode_id) {
    $episode = get_post($episode_id);
    if (!$episode || $episode->post_type !== 'episode') {
        return ['success' => false, 'error' => 'Invalid episode'];
    }
    
    $ppv_price = get_field('ppv_price', $episode_id);
    if (!$ppv_price || $ppv_price <= 0) {
        return ['success' => false, 'error' => 'Episode not available for PPV'];
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid user'];
    }
    
    $flowguard_settings = get_option('flexpress_flowguard_settings', []);
    $api = new FlexPress_Flowguard_API(
        $flowguard_settings['shop_id'],
        $flowguard_settings['signature_key'],
        $flowguard_settings['environment']
    );
    
    $purchase_data = [
        'priceAmount' => number_format($ppv_price, 2, '.', ''),
        'priceCurrency' => 'USD',
        'successUrl' => home_url('/payment-success'),
        'declineUrl' => home_url('/payment-declined'),
        'postbackUrl' => home_url('/wp-admin/admin-ajax.php?action=flowguard_webhook'),
        'email' => $user->user_email,
        'referenceId' => 'ppv_user_' . $user_id . '_episode_' . $episode_id
    ];
    
    $result = $api->start_purchase($purchase_data);
    
    if ($result['success']) {
        // Store session data for webhook processing
        update_user_meta($user_id, 'flowguard_ppv_session_id', $result['session_id']);
        update_user_meta($user_id, 'flowguard_ppv_episode_id', $episode_id);
        update_user_meta($user_id, 'flowguard_ppv_reference_id', $purchase_data['referenceId']);
        
        return [
            'success' => true,
            'session_id' => $result['session_id'],
            'payment_url' => home_url('/flowguard-payment?session_id=' . $result['session_id'])
        ];
    }
    
    return $result;
}

/**
 * Format plan duration for Flowguard (ISO 8601)
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
```

### **3. Webhook Handler**

```php
<?php
/**
 * Flowguard Webhook Handler
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
            
        default:
            error_log('Flowguard Webhook: Unknown postback type: ' . $postback_type);
            break;
    }
    
    // Store webhook for analysis
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'flexpress_flowguard_webhooks',
        [
            'webhook_id' => $payload['transactionId'] ?? uniqid(),
            'event_type' => $postback_type,
            'transaction_id' => $payload['transactionId'] ?? '',
            'user_id' => flexpress_flowguard_get_user_from_reference($payload['referenceId'] ?? ''),
            'payload' => json_encode($payload),
            'processed' => 1,
            'created_at' => current_time('mysql')
        ]
    );
    
    echo 'OK';
    exit;
}

/**
 * Handle subscription approved webhook
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
    
    // Log activity
    if (class_exists('FlexPress_Activity_Logger')) {
        FlexPress_Activity_Logger::log_activity(
            $user_id,
            'flowguard_subscription_approved',
            'Subscription approved via Flowguard',
            $payload
        );
    }
    
    error_log('Flowguard Webhook: Subscription approved for user ' . $user_id);
}

/**
 * Handle subscription rebill webhook
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
    
    // Log activity
    if (class_exists('FlexPress_Activity_Logger')) {
        FlexPress_Activity_Logger::log_activity(
            $user_id,
            'flowguard_subscription_rebill',
            'Subscription rebill via Flowguard',
            $payload
        );
    }
    
    error_log('Flowguard Webhook: Subscription rebill for user ' . $user_id);
}

/**
 * Handle subscription cancel webhook
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
    if (class_exists('FlexPress_Activity_Logger')) {
        FlexPress_Activity_Logger::log_activity(
            $user_id,
            'flowguard_subscription_cancelled',
            'Subscription cancelled via Flowguard',
            $payload
        );
    }
    
    error_log('Flowguard Webhook: Subscription cancelled for user ' . $user_id);
}

/**
 * Get user ID from reference ID
 */
function flexpress_flowguard_get_user_from_reference($reference_id) {
    if (empty($reference_id)) {
        return 0;
    }
    
    // Extract user ID from reference format: "user_123_plan_456" or "ppv_user_123_episode_456"
    if (preg_match('/user_(\d+)/', $reference_id, $matches)) {
        return intval($matches[1]);
    }
    
    return 0;
}

// Register webhook handler
add_action('wp_ajax_nopriv_flowguard_webhook', 'flexpress_flowguard_webhook_handler');
add_action('wp_ajax_flowguard_webhook', 'flexpress_flowguard_webhook_handler');
```

## 🎨 **Frontend Implementation**

### **1. Flowguard SDK Integration**

```javascript
/**
 * Flowguard SDK Integration
 */
class FlexPressFlowguard {
    constructor(config) {
        this.apiKey = config.apiKey;
        this.merchantId = config.merchantId;
        this.environment = config.environment;
        this.sessionId = null;
        this.init();
    }
    
    async init() {
        await this.loadSDK();
    }
    
    async loadSDK() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://flowguard.yoursafe.com/js/sdk/v1/flowguard.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    async initPaymentForm(containerId, sessionId, options = {}) {
        if (!window.Flowguard) {
            throw new Error('Flowguard SDK not loaded');
        }
        
        const container = document.getElementById(containerId);
        if (!container) {
            throw new Error('Payment container not found');
        }
        
        // Initialize Flowguard payment form
        const paymentForm = new Flowguard.PaymentForm({
            sessionId: sessionId,
            container: container,
            options: {
                theme: 'dark',
                locale: 'en_US',
                enable3DSecure: true,
                rememberCard: true,
                ...options
            }
        });
        
        // Setup event handlers
        paymentForm.on('payment.success', this.handlePaymentSuccess.bind(this));
        paymentForm.on('payment.error', this.handlePaymentError.bind(this));
        paymentForm.on('payment.pending', this.handlePaymentPending.bind(this));
        
        return paymentForm;
    }
    
    handlePaymentSuccess(event) {
        console.log('Payment successful:', event);
        // Redirect to success page
        window.location.href = '/payment-success?transaction_id=' + event.transactionId;
    }
    
    handlePaymentError(event) {
        console.error('Payment error:', event);
        this.showErrorMessage(event.message);
    }
    
    handlePaymentPending(event) {
        console.log('Payment pending:', event);
        this.showPendingMessage(event.message);
    }
    
    showErrorMessage(message) {
        // Show error message to user
        const errorDiv = document.getElementById('payment-error');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
    }
    
    showPendingMessage(message) {
        // Show pending message to user
        const pendingDiv = document.getElementById('payment-pending');
        if (pendingDiv) {
            pendingDiv.textContent = message;
            pendingDiv.style.display = 'block';
        }
    }
}
```

### **2. Payment Page Template**

```php
<?php
/**
 * Template Name: Flowguard Payment
 */
get_header();

$session_id = isset($_GET['session_id']) ? sanitize_text_field($_GET['session_id']) : '';

if (empty($session_id)) {
    wp_redirect(home_url('/join'));
    exit;
}
?>

<main id="primary" class="site-main flowguard-payment-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="payment-container">
                    <h1 class="text-center mb-4">Complete Your Payment</h1>
                    
                    <div id="payment-error" class="alert alert-danger" style="display: none;"></div>
                    <div id="payment-pending" class="alert alert-warning" style="display: none;"></div>
                    
                    <div id="flowguard-payment-form" class="payment-form-container">
                        <!-- Flowguard payment form will be rendered here -->
                    </div>
                    
                    <div class="payment-security-info text-center mt-4">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            Your payment is secured with 256-bit SSL encryption
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sessionId = '<?php echo esc_js($session_id); ?>';
    
    // Initialize Flowguard
    const flowguard = new FlexPressFlowguard({
        apiKey: '<?php echo esc_js(get_option('flexpress_flowguard_settings')['api_key'] ?? ''); ?>',
        merchantId: '<?php echo esc_js(get_option('flexpress_flowguard_settings')['shop_id'] ?? ''); ?>',
        environment: '<?php echo esc_js(get_option('flexpress_flowguard_settings')['environment'] ?? 'sandbox'); ?>'
    });
    
    // Initialize payment form
    flowguard.initPaymentForm('flowguard-payment-form', sessionId, {
        theme: 'dark',
        locale: 'en_US'
    }).catch(error => {
        console.error('Error initializing payment form:', error);
        document.getElementById('payment-error').textContent = 'Error loading payment form. Please try again.';
        document.getElementById('payment-error').style.display = 'block';
    });
});
</script>

<?php get_footer(); ?>
```

## 🔧 **Admin Settings Implementation**

```php
<?php
/**
 * Flowguard Settings Admin Page
 */
class FlexPress_Flowguard_Settings {
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_submenu_page'));
    }
    
    public function register_settings() {
        register_setting('flexpress_flowguard_settings', 'flexpress_flowguard_settings');
        
        // API Configuration Section
        add_settings_section(
            'flowguard_api_section',
            'API Configuration',
            array($this, 'render_api_section'),
            'flexpress_flowguard_settings'
        );
        
        add_settings_field(
            'shop_id',
            'Shop ID',
            array($this, 'render_shop_id_field'),
            'flexpress_flowguard_settings',
            'flowguard_api_section'
        );
        
        add_settings_field(
            'signature_key',
            'Signature Key',
            array($this, 'render_signature_key_field'),
            'flexpress_flowguard_settings',
            'flowguard_api_section'
        );
        
        add_settings_field(
            'environment',
            'Environment',
            array($this, 'render_environment_field'),
            'flexpress_flowguard_settings',
            'flowguard_api_section'
        );
        
        // Webhook Configuration Section
        add_settings_section(
            'flowguard_webhook_section',
            'Webhook Configuration',
            array($this, 'render_webhook_section'),
            'flexpress_flowguard_settings'
        );
        
        add_settings_field(
            'webhook_url',
            'Webhook URL',
            array($this, 'render_webhook_url_field'),
            'flexpress_flowguard_settings',
            'flowguard_webhook_section'
        );
    }
    
    public function render_shop_id_field() {
        $options = get_option('flexpress_flowguard_settings', array());
        $shop_id = $options['shop_id'] ?? '';
        ?>
        <input type="text" name="flexpress_flowguard_settings[shop_id]" value="<?php echo esc_attr($shop_id); ?>" class="regular-text" />
        <p class="description">Enter your Shop ID from ControlCenter.</p>
        <?php
    }
    
    public function render_signature_key_field() {
        $options = get_option('flexpress_flowguard_settings', array());
        $signature_key = $options['signature_key'] ?? '';
        ?>
        <input type="password" name="flexpress_flowguard_settings[signature_key]" value="<?php echo esc_attr($signature_key); ?>" class="regular-text" />
        <button type="button" onclick="toggleSignatureVisibility()" class="button button-secondary">Show/Hide</button>
        <p class="description">Enter your Signature Key from ControlCenter.</p>
        <script>
        function toggleSignatureVisibility() {
            var field = document.querySelector('input[name="flexpress_flowguard_settings[signature_key]"]');
            if (field.type === 'password') {
                field.type = 'text';
            } else {
                field.type = 'password';
            }
        }
        </script>
        <?php
    }
    
    public function render_environment_field() {
        $options = get_option('flexpress_flowguard_settings', array());
        $environment = $options['environment'] ?? 'sandbox';
        ?>
        <select name="flexpress_flowguard_settings[environment]">
            <option value="sandbox" <?php selected($environment, 'sandbox'); ?>>Sandbox</option>
            <option value="production" <?php selected($environment, 'production'); ?>>Production</option>
        </select>
        <p class="description">Select your environment. Use Sandbox for testing.</p>
        <?php
    }
    
    public function render_webhook_url_field() {
        $webhook_url = home_url('/wp-admin/admin-ajax.php?action=flowguard_webhook');
        ?>
        <input type="text" value="<?php echo esc_attr($webhook_url); ?>" class="regular-text" readonly />
        <button type="button" onclick="copyToClipboard('<?php echo esc_js($webhook_url); ?>')" class="button button-secondary">Copy URL</button>
        <p class="description">Copy this URL to your ControlCenter webhook settings.</p>
        <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('URL copied to clipboard!');
            });
        }
        </script>
        <?php
    }
    
    public function add_submenu_page() {
        add_submenu_page(
            'flexpress-settings',
            'Flowguard',
            'Flowguard',
            'manage_options',
            'flexpress-flowguard-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Flowguard Settings</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_flowguard_settings');
                do_settings_sections('flexpress_flowguard_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new FlexPress_Flowguard_Settings();
```

## 📊 **Database Schema**

```sql
-- Flowguard webhooks table
CREATE TABLE wp_flexpress_flowguard_webhooks (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    webhook_id varchar(255) NOT NULL,
    event_type varchar(100) NOT NULL,
    transaction_id varchar(255),
    user_id bigint(20),
    payload longtext NOT NULL,
    processed tinyint(1) DEFAULT 0,
    created_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY webhook_id (webhook_id),
    KEY event_type (event_type),
    KEY processed (processed)
);

-- Flowguard transactions table
CREATE TABLE wp_flexpress_flowguard_transactions (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    transaction_id varchar(255) NOT NULL,
    session_id varchar(255) NOT NULL,
    sale_id varchar(255),
    amount decimal(10,2) NOT NULL,
    currency varchar(3) NOT NULL,
    status varchar(50) NOT NULL,
    order_type varchar(50) NOT NULL,
    reference_id varchar(255),
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY transaction_id (transaction_id),
    KEY user_id (user_id),
    KEY status (status)
);
```

This implementation provides a complete Flowguard integration that replaces Verotel FlexPay with enhanced security features, better user experience, and comprehensive webhook handling.
