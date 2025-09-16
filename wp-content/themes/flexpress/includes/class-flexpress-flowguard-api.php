<?php
/**
 * Flowguard API Client
 * 
 * Handles all communication with the Flowguard API including:
 * - JWT token creation and validation
 * - Subscription management
 * - Purchase processing
 * - Webhook handling
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FlexPress Flowguard API Client
 */
class FlexPress_Flowguard_API {
    
    /**
     * API base URL for production
     */
    private $api_base_url_production = 'https://flowguard.yoursafe.com/api/merchant';
    
    /**
     * API base URL for sandbox (same as production for Flowguard)
     */
    private $api_base_url_sandbox = 'https://flowguard.yoursafe.com/api/merchant';
    
    /**
     * Current API base URL
     */
    private $api_base_url;
    
    /**
     * Shop ID from ControlCenter
     */
    private $shop_id;
    
    /**
     * Signature key for JWT signing
     */
    private $signature_key;
    
    /**
     * Environment (sandbox or production)
     */
    private $environment;
    
    /**
     * Constructor
     * 
     * @param string $shop_id Shop ID from ControlCenter
     * @param string $signature_key Signature key for JWT signing
     * @param string $environment Environment (sandbox or production)
     */
    public function __construct($shop_id, $signature_key, $environment = 'sandbox') {
        $this->shop_id = $shop_id;
        $this->signature_key = $signature_key;
        $this->environment = $environment;
        
        // Set API base URL (Flowguard uses same URL for both environments)
        $this->api_base_url = $this->api_base_url_production;
    }
    
    /**
     * Create JWT token for API requests
     * 
     * @param array $payload JWT payload data
     * @return string JWT token
     */
    private function create_jwt($payload) {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload_json = json_encode($payload);
        
        $header_encoded = $this->base64url_encode($header);
        $payload_encoded = $this->base64url_encode($payload_json);
        
        $signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, $this->signature_key, true);
        $signature_encoded = $this->base64url_encode($signature);
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    }
    
    /**
     * Base64 URL encode
     * 
     * @param string $data Data to encode
     * @return string Base64 URL encoded string
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     * 
     * @param string $data Data to decode
     * @return string Decoded string
     */
    private function base64url_decode($data) {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
    
    /**
     * Validate JWT token signature
     * 
     * @param string $jwt JWT token
     * @return bool True if valid, false otherwise
     */
    public function validate_jwt($jwt) {
        $jwt_parts = explode('.', $jwt);
        if (count($jwt_parts) !== 3) {
            return false;
        }
        
        $expected_signature = hash_hmac('sha256', $jwt_parts[0] . '.' . $jwt_parts[1], $this->signature_key, true);
        $actual_signature = $this->base64url_decode($jwt_parts[2]);
        
        return hash_equals($expected_signature, $actual_signature);
    }
    
    /**
     * Decode JWT payload
     * 
     * @param string $jwt JWT token
     * @return array|false Decoded payload or false on error
     */
    public function decode_jwt_payload($jwt) {
        $jwt_parts = explode('.', $jwt);
        if (count($jwt_parts) !== 3) {
            return false;
        }
        
        $payload = json_decode($this->base64url_decode($jwt_parts[1]), true);
        return $payload;
    }
    
    /**
     * Start subscription
     * 
     * @param array $data Subscription data
     * @return array Response data
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
        
        $error_data = json_decode($body, true);
        $error_message = 'API Error: ' . $status_code;
        if (isset($error_data['errors'])) {
            if (is_string($error_data['errors'])) {
                $error_message .= ' - ' . $error_data['errors'];
            } elseif (is_array($error_data['errors'])) {
                $error_message .= ' - ' . implode(', ', $error_data['errors']);
            }
        }
        
        return ['success' => false, 'error' => $error_message];
    }
    
    /**
     * Start purchase
     * 
     * @param array $data Purchase data
     * @return array Response data
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
        
        $error_data = json_decode($body, true);
        $error_message = 'API Error: ' . $status_code;
        if (isset($error_data['errors'])) {
            if (is_string($error_data['errors'])) {
                $error_message .= ' - ' . $error_data['errors'];
            } elseif (is_array($error_data['errors'])) {
                $error_message .= ' - ' . implode(', ', $error_data['errors']);
            }
        }
        
        return ['success' => false, 'error' => $error_message];
    }
    
    /**
     * Cancel subscription
     * 
     * @param string $sale_id Sale ID of subscription to cancel
     * @param string $cancelled_by Who cancelled the subscription (merchant, buyer)
     * @return array Response data
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
        
        $body = wp_remote_retrieve_body($response);
        $error_data = json_decode($body, true);
        $error_message = 'API Error: ' . $status_code;
        if (isset($error_data['errors'])) {
            if (is_string($error_data['errors'])) {
                $error_message .= ' - ' . $error_data['errors'];
            } elseif (is_array($error_data['errors'])) {
                $error_message .= ' - ' . implode(', ', $error_data['errors']);
            }
        }
        
        return ['success' => false, 'error' => $error_message];
    }
    
    /**
     * Get API base URL
     * 
     * @return string API base URL
     */
    public function get_api_base_url() {
        return $this->api_base_url;
    }
    
    /**
     * Get shop ID
     * 
     * @return string Shop ID
     */
    public function get_shop_id() {
        return $this->shop_id;
    }
    
    /**
     * Get environment
     * 
     * @return string Environment
     */
    public function get_environment() {
        return $this->environment;
    }
}
