<?php
/**
 * FlexPress Promo Codes Class
 * Handles promo code validation and application logic
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Promo Codes Class
 */
class FlexPress_Promo_Codes {
    
    /**
     * Validate promo code logic
     *
     * @param string $code The promo code
     * @param int $user_id The user ID
     * @param string $plan_id The plan ID
     * @param float $amount The amount
     * @return array Validation result
     */
    public function validate_promo_code_logic($code, $user_id, $plan_id, $amount) {
        global $wpdb;
        
        // Sanitize inputs
        $code = sanitize_text_field($code);
        $user_id = intval($user_id);
        $plan_id = sanitize_text_field($plan_id);
        $amount = floatval($amount);
        
        if (empty($code)) {
            return array(
                'valid' => false,
                'message' => 'Please enter a promo code'
            );
        }
        
        // Get promo code from database
        $promo_table = $wpdb->prefix . 'flexpress_promo_codes';
        $promo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$promo_table} WHERE code = %s AND status = 'active'",
            $code
        ));
        
        if (!$promo) {
            return array(
                'valid' => false,
                'message' => 'Invalid promo code'
            );
        }
        
        // Check if promo code has expired
        if (!empty($promo->expiry_date) && strtotime($promo->expiry_date) < time()) {
            return array(
                'valid' => false,
                'message' => 'This promo code has expired'
            );
        }
        
        // Check usage limit
        if ($promo->usage_limit > 0) {
            $usage_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}flexpress_promo_usage WHERE promo_code_id = %d",
                $promo->id
            ));
            
            if ($usage_count >= $promo->usage_limit) {
                return array(
                    'valid' => false,
                    'message' => 'This promo code has reached its usage limit'
                );
            }
        }
        
        // Check user limit
        if ($promo->user_limit > 0) {
            $user_usage_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}flexpress_promo_usage WHERE promo_code_id = %d AND user_id = %d",
                $promo->id,
                $user_id
            ));
            
            if ($user_usage_count >= $promo->user_limit) {
                return array(
                    'valid' => false,
                    'message' => 'You have already used this promo code the maximum number of times'
                );
            }
        }
        
        // Check minimum amount
        if ($promo->minimum_amount > 0 && $amount < $promo->minimum_amount) {
            return array(
                'valid' => false,
                'message' => 'This promo code requires a minimum purchase of $' . number_format($promo->minimum_amount, 2)
            );
        }
        
        // Calculate discount
        $discount_amount = 0;
        if ($promo->discount_type === 'percentage') {
            $discount_amount = ($amount * $promo->discount_value) / 100;
        } else {
            $discount_amount = $promo->discount_value;
        }
        
        // Apply maximum discount limit
        if ($promo->maximum_discount > 0 && $discount_amount > $promo->maximum_discount) {
            $discount_amount = $promo->maximum_discount;
        }
        
        // Calculate final amount
        $final_amount = max(0, $amount - $discount_amount);
        
        return array(
            'valid' => true,
            'promo_id' => $promo->id,
            'code' => $promo->code,
            'name' => $promo->name,
            'discount_amount' => $discount_amount,
            'final_amount' => $final_amount,
            'message' => 'Promo code applied successfully!'
        );
    }
    
    /**
     * Record promo code usage
     *
     * @param int $promo_id The promo code ID
     * @param string $code The promo code
     * @param int $user_id The user ID
     * @param float $amount The amount
     * @param float $discount_amount The discount amount
     * @return bool Success status
     */
    public function record_usage($promo_id, $code, $user_id, $amount, $discount_amount) {
        global $wpdb;
        
        $usage_table = $wpdb->prefix . 'flexpress_promo_usage';
        
        $result = $wpdb->insert(
            $usage_table,
            array(
                'promo_code_id' => intval($promo_id),
                'promo_code' => sanitize_text_field($code),
                'user_id' => intval($user_id),
                'amount' => floatval($amount),
                'discount_amount' => floatval($discount_amount),
                'used_at' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%f', '%f', '%s')
        );
        
        return $result !== false;
    }
}
