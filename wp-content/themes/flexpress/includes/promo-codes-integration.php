<?php
/**
 * FlexPress Promo Codes Integration
 * Handles promo code application and validation in payment flows
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Promo Codes Integration Class
 */
class FlexPress_Promo_Codes_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_apply_promo_code', array($this, 'apply_promo_code'));
        add_action('wp_ajax_nopriv_apply_promo_code', array($this, 'apply_promo_code'));
        add_action('wp_ajax_remove_promo_code', array($this, 'remove_promo_code'));
        add_action('wp_ajax_nopriv_remove_promo_code', array($this, 'remove_promo_code'));
        
        // Hook into payment processing
        add_action('flexpress_before_payment_processing', array($this, 'apply_promo_to_payment'), 10, 2);
        add_action('flexpress_after_payment_success', array($this, 'record_promo_usage'), 10, 3);
        
        // Add promo code field to payment forms
        add_action('flexpress_payment_form_fields', array($this, 'add_promo_code_field'));
        
        // Enqueue frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }
    
    /**
     * Apply promo code via AJAX
     */
    public function apply_promo_code() {
        $code = sanitize_text_field($_POST['code'] ?? '');
        $plan_id = sanitize_text_field($_POST['plan_id'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        
        if (empty($code)) {
            wp_send_json_error(array('message' => 'Please enter a promo code'));
        }
        
        $promo_codes = new FlexPress_Promo_Codes();
        $validation = $promo_codes->validate_promo_code_logic($code, get_current_user_id(), $plan_id, $amount);
        
        if ($validation['valid']) {
            // Store promo code in session for payment processing
            if (!session_id()) {
                session_start();
            }
            $_SESSION['flexpress_applied_promo'] = array(
                'code' => $code,
                'promo_id' => $validation['promo_id'],
                'discount_amount' => $validation['discount_amount'],
                'final_amount' => $validation['final_amount']
            );
            
            wp_send_json_success($validation);
        } else {
            wp_send_json_error($validation);
        }
    }
    
    /**
     * Remove promo code via AJAX
     */
    public function remove_promo_code() {
        if (!session_id()) {
            session_start();
        }
        
        unset($_SESSION['flexpress_applied_promo']);
        
        wp_send_json_success(array('message' => 'Promo code removed'));
    }
    
    /**
     * Apply promo code to payment before processing
     */
    public function apply_promo_to_payment($payment_data, $user_id) {
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION['flexpress_applied_promo'])) {
            $promo = $_SESSION['flexpress_applied_promo'];
            
            // Update payment amount
            $payment_data['original_amount'] = $payment_data['amount'];
            $payment_data['amount'] = $promo['final_amount'];
            $payment_data['discount_amount'] = $promo['discount_amount'];
            $payment_data['promo_code'] = $promo['code'];
            $payment_data['promo_id'] = $promo['promo_id'];
        }
        
        return $payment_data;
    }
    
    /**
     * Record promo code usage after successful payment
     */
    public function record_promo_usage($payment_data, $transaction_id, $user_id) {
        if (!isset($payment_data['promo_id'])) {
            return;
        }
        
        global $wpdb;
        
        // Update usage count
        $promo_table = $wpdb->prefix . 'flexpress_promo_codes';
        $wpdb->query($wpdb->prepare(
            "UPDATE $promo_table SET usage_count = usage_count + 1 WHERE id = %d",
            $payment_data['promo_id']
        ));
        
        // Record usage details
        $usage_table = $wpdb->prefix . 'flexpress_promo_usage';
        $wpdb->insert(
            $usage_table,
            array(
                'promo_code_id' => $payment_data['promo_id'],
                'promo_code' => $payment_data['promo_code'],
                'user_id' => $user_id,
                'order_id' => $transaction_id,
                'plan_id' => $payment_data['plan_id'] ?? '',
                'original_amount' => $payment_data['original_amount'] ?? $payment_data['amount'],
                'discount_amount' => $payment_data['discount_amount'] ?? 0,
                'final_amount' => $payment_data['amount'],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ),
            array('%d', '%s', '%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s')
        );
        
        // Clear session
        if (session_id()) {
            unset($_SESSION['flexpress_applied_promo']);
        }
    }
    
    /**
     * Add promo code field to payment forms
     */
    public function add_promo_code_field() {
        ?>
        <div class="promo-code-section">
            <div class="promo-code-input">
                <label for="promo-code"><?php esc_html_e('Promo Code', 'flexpress'); ?></label>
                <div class="promo-code-field">
                    <input type="text" id="promo-code" name="promo_code" 
                           placeholder="<?php esc_attr_e('Enter promo code', 'flexpress'); ?>">
                    <button type="button" id="apply-promo-code" class="button">
                        <?php esc_html_e('Apply', 'flexpress'); ?>
                    </button>
                </div>
                <div id="promo-code-message" class="promo-code-message"></div>
            </div>
        </div>
        
        <style>
        .promo-code-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .promo-code-field {
            display: flex;
            gap: 10px;
            margin-top: 5px;
        }
        .promo-code-field input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .promo-code-message {
            margin-top: 10px;
            padding: 8px;
            border-radius: 4px;
            display: none;
        }
        .promo-code-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .promo-code-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .applied-promo {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .applied-promo .remove-promo {
            float: right;
            background: none;
            border: none;
            color: #0c5460;
            text-decoration: underline;
            cursor: pointer;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var appliedPromo = null;
            
            // Apply promo code
            $('#apply-promo-code').on('click', function() {
                var code = $('#promo-code').val().trim();
                var planId = $('input[name="plan_id"]').val() || '';
                var amount = parseFloat($('input[name="amount"]').val()) || 0;
                
                if (!code) {
                    showMessage('Please enter a promo code', 'error');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'apply_promo_code',
                        code: code,
                        plan_id: planId,
                        amount: amount
                    },
                    success: function(response) {
                        if (response.success) {
                            appliedPromo = response.data;
                            showAppliedPromo(response.data);
                            updatePaymentAmount(response.data.final_amount);
                        } else {
                            showMessage(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        showMessage('An error occurred. Please try again.', 'error');
                    }
                });
            });
            
            // Remove promo code
            $(document).on('click', '.remove-promo', function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'remove_promo_code'
                    },
                    success: function(response) {
                        if (response.success) {
                            appliedPromo = null;
                            hideAppliedPromo();
                            resetPaymentAmount();
                        }
                    }
                });
            });
            
            function showMessage(message, type) {
                var messageDiv = $('#promo-code-message');
                messageDiv.removeClass('success error').addClass(type);
                messageDiv.text(message).show();
                
                setTimeout(function() {
                    messageDiv.fadeOut();
                }, 5000);
            }
            
            function showAppliedPromo(data) {
                var discountText = '';
                if (data.discount_amount > 0) {
                    discountText = 'Discount: $' + data.discount_amount.toFixed(2);
                }
                
                var promoHtml = '<div class="applied-promo">' +
                    '<strong>Promo Code Applied:</strong> ' + data.code + '<br>' +
                    discountText + '<br>' +
                    '<span class="remove-promo">Remove</span>' +
                    '</div>';
                
                $('.promo-code-section').append(promoHtml);
                $('#promo-code').val('');
            }
            
            function hideAppliedPromo() {
                $('.applied-promo').remove();
            }
            
            function updatePaymentAmount(finalAmount) {
                $('input[name="amount"]').val(finalAmount.toFixed(2));
                $('.payment-amount').text('$' + finalAmount.toFixed(2));
            }
            
            function resetPaymentAmount() {
                // Reset to original amount - you may need to store this
                var originalAmount = $('input[name="original_amount"]').val();
                if (originalAmount) {
                    $('input[name="amount"]').val(originalAmount);
                    $('.payment-amount').text('$' + parseFloat(originalAmount).toFixed(2));
                }
            }
        });
        </script>
        <?php
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        if (is_page_template('page-templates/payment.php') || 
            is_page_template('page-templates/flowguard-payment.php')) {
            
            wp_enqueue_script('jquery');
            wp_localize_script('jquery', 'ajaxurl', admin_url('admin-ajax.php'));
        }
    }
    
    /**
     * Get promo code discount for display
     */
    public static function get_promo_discount_display($promo_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_promo_codes';
        
        $promo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $promo_id
        ));
        
        if (!$promo) {
            return '';
        }
        
        switch ($promo->discount_type) {
            case 'percentage':
                return $promo->discount_value . '% off';
            case 'fixed':
                return '$' . number_format($promo->discount_value, 2) . ' off';
            case 'free_trial':
                return $promo->discount_value . ' days free';
            default:
                return '';
        }
    }
    
    /**
     * Check if promo code is valid for current user
     */
    public static function is_promo_valid_for_user($code, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $promo_codes = new FlexPress_Promo_Codes();
        $validation = $promo_codes->validate_promo_code_logic($code, $user_id, '', 0);
        
        return $validation['valid'];
    }
}

// Initialize the promo codes integration
new FlexPress_Promo_Codes_Integration();
