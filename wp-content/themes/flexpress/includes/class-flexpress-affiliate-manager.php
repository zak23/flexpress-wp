<?php
/**
 * FlexPress Affiliate Manager
 * 
 * Core class for managing affiliate operations, applications, and data.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FlexPress Affiliate Manager Class
 */
class FlexPress_Affiliate_Manager {
    
    /**
     * Instance of the class
     * 
     * @var FlexPress_Affiliate_Manager
     */
    private static $instance = null;
    
    /**
     * Get instance of the class
     * 
     * @return FlexPress_Affiliate_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('wp_ajax_nopriv_affiliate_application', array($this, 'handle_affiliate_application'));
        add_action('wp_ajax_affiliate_application', array($this, 'handle_affiliate_application'));
        add_action('wp_ajax_approve_affiliate', array($this, 'approve_affiliate'));
        add_action('wp_ajax_reject_affiliate', array($this, 'reject_affiliate'));
        add_action('wp_ajax_suspend_affiliate', array($this, 'suspend_affiliate'));
    }
    
    /**
     * Check if affiliate system is enabled
     * 
     * @return bool True if enabled
     */
    public function is_system_enabled() {
        $settings = get_option('flexpress_affiliate_settings', array());
        return !empty($settings['module_enabled']);
    }
    
    /**
     * Generate unique affiliate code
     * 
     * @return string Unique affiliate code
     */
    public function generate_affiliate_code() {
        global $wpdb;
        
        do {
            $code = 'AFF' . strtoupper(wp_generate_password(8, false));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE affiliate_code = %s",
                $code
            ));
        } while ($exists);
        
        return $code;
    }
    
    /**
     * Create affiliate referral URL
     * 
     * @param string $affiliate_code Affiliate code
     * @param string $promo_code Optional promo code
     * @return string Referral URL
     */
    public function create_referral_url($affiliate_code, $promo_code = '') {
        $base_url = home_url('/join');
        $params = array('ref' => $affiliate_code);
        
        if (!empty($promo_code)) {
            $params['promo'] = $promo_code;
        }
        
        return add_query_arg($params, $base_url);
    }
    
    /**
     * Get affiliate by ID
     * 
     * @param int $affiliate_id Affiliate ID
     * @return object|null Affiliate object or null
     */
    public function get_affiliate($affiliate_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE id = %d",
            $affiliate_id
        ));
    }
    
    /**
     * Get affiliate by code
     * 
     * @param string $affiliate_code Affiliate code
     * @return object|null Affiliate object or null
     */
    public function get_affiliate_by_code($affiliate_code) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE affiliate_code = %s",
            $affiliate_code
        ));
    }
    
    /**
     * Get affiliate by email
     * 
     * @param string $email Email address
     * @return object|null Affiliate object or null
     */
    public function get_affiliate_by_email($email) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE email = %s",
            $email
        ));
    }
    
    /**
     * Get all affiliates with pagination
     * 
     * @param array $args Query arguments
     * @return array Affiliates array
     */
    public function get_affiliates($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => '',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clause = '';
        $where_values = array();
        
        if (!empty($args['status'])) {
            $where_clause = "WHERE status = %s";
            $where_values[] = $args['status'];
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $sql = "SELECT * FROM {$wpdb->prefix}flexpress_affiliates 
                $where_clause 
                ORDER BY $orderby 
                LIMIT %d OFFSET %d";
        
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        if (!empty($where_values)) {
            return $wpdb->get_results($wpdb->prepare($sql, $where_values));
        } else {
            return $wpdb->get_results($wpdb->prepare($sql, $args['limit'], $args['offset']));
        }
    }
    
    /**
     * Handle affiliate application submission
     */
    public function handle_affiliate_application() {
        check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
        
        if (!$this->is_system_enabled()) {
            wp_send_json_error(['message' => __('Affiliate system is currently disabled.', 'flexpress')]);
        }
        
        $data = array(
            'affiliate_name' => sanitize_text_field($_POST['affiliate_name'] ?? ''),
            'affiliate_email' => sanitize_email($_POST['affiliate_email'] ?? ''),
            'affiliate_website' => esc_url_raw($_POST['affiliate_website'] ?? ''),
            'payout_method' => sanitize_text_field($_POST['payout_method'] ?? 'paypal'),
            'payout_details' => sanitize_textarea_field($_POST['payout_details'] ?? ''),
            'tax_info' => sanitize_textarea_field($_POST['tax_info'] ?? ''),
            'marketing_experience' => sanitize_textarea_field($_POST['marketing_experience'] ?? ''),
            'terms_accepted' => !empty($_POST['terms_accepted']),
            'ip_address' => flexpress_get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'submitted_at' => current_time('mysql')
        );
        
        // Validation
        if (empty($data['affiliate_name']) || empty($data['affiliate_email'])) {
            wp_send_json_error(['message' => __('Name and email are required.', 'flexpress')]);
        }
        
        if (!is_email($data['affiliate_email'])) {
            wp_send_json_error(['message' => __('Please enter a valid email address.', 'flexpress')]);
        }
        
        if (!$data['terms_accepted']) {
            wp_send_json_error(['message' => __('You must accept the terms and conditions.', 'flexpress')]);
        }
        
        // Validate payout method
        $valid_payout_methods = ['paypal', 'crypto', 'aus_bank_transfer', 'yoursafe', 'ach', 'swift'];
        if (!in_array($data['payout_method'], $valid_payout_methods)) {
            wp_send_json_error(['message' => __('Please select a valid payout method.', 'flexpress')]);
        }
        
        if (empty($data['payout_details'])) {
            wp_send_json_error(['message' => __('Please provide payout details for your selected method.', 'flexpress')]);
        }
        
        // Check for existing application
        $existing = $this->get_affiliate_by_email($data['affiliate_email']);
        if ($existing) {
            wp_send_json_error(['message' => __('An application with this email already exists.', 'flexpress')]);
        }
        
        // Generate unique affiliate code
        $affiliate_code = $this->generate_affiliate_code();
        
        // Get default settings
        $settings = get_option('flexpress_affiliate_settings', array());
        
        // Insert application
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'flexpress_affiliates',
            array(
                'affiliate_code' => $affiliate_code,
                'display_name' => $data['affiliate_name'],
                'email' => $data['affiliate_email'],
                'website' => $data['affiliate_website'],
                'payout_method' => $data['payout_method'],
                'payout_details' => $data['payout_details'],
                'tax_info' => $data['tax_info'],
                'commission_initial' => $settings['commission_rate'] ?? 25.00,
                'commission_rebill' => $settings['rebill_commission_rate'] ?? 10.00,
                'commission_unlock' => $settings['unlock_commission_rate'] ?? 15.00,
                'payout_threshold' => $settings['minimum_payout'] ?? 100.00,
                'status' => 'pending',
                'application_data' => json_encode($data),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%s', '%s')
        );
        
        if ($result) {
            $affiliate_id = $wpdb->insert_id;
            
            // Create referral URL
            $referral_url = $this->create_referral_url($affiliate_code);
            $wpdb->update(
                $wpdb->prefix . 'flexpress_affiliates',
                array('referral_url' => $referral_url),
                array('id' => $affiliate_id),
                array('%s'),
                array('%d')
            );
            
            // Send notification email to admin
            $this->send_application_notification($data, $affiliate_id);
            
            wp_send_json_success(['message' => __('Application submitted successfully! We will review it and get back to you.', 'flexpress')]);
        } else {
            wp_send_json_error(['message' => __('Failed to submit application. Please try again.', 'flexpress')]);
        }
    }
    
    /**
     * Approve affiliate application
     */
    public function approve_affiliate() {
        check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'flexpress')]);
        }
        
        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);
        
        if (!$affiliate_id) {
            wp_send_json_error(['message' => __('Invalid affiliate ID.', 'flexpress')]);
        }
        
        $affiliate = $this->get_affiliate($affiliate_id);
        if (!$affiliate) {
            wp_send_json_error(['message' => __('Affiliate not found.', 'flexpress')]);
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'flexpress_affiliates',
            array('status' => 'active'),
            array('id' => $affiliate_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Send approval email to affiliate
            $this->send_approval_notification($affiliate);
            
            wp_send_json_success(['message' => __('Affiliate approved successfully.', 'flexpress')]);
        } else {
            wp_send_json_error(['message' => __('Failed to approve affiliate.', 'flexpress')]);
        }
    }
    
    /**
     * Reject affiliate application
     */
    public function reject_affiliate() {
        check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'flexpress')]);
        }
        
        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$affiliate_id) {
            wp_send_json_error(['message' => __('Invalid affiliate ID.', 'flexpress')]);
        }
        
        $affiliate = $this->get_affiliate($affiliate_id);
        if (!$affiliate) {
            wp_send_json_error(['message' => __('Affiliate not found.', 'flexpress')]);
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'flexpress_affiliates',
            array(
                'status' => 'rejected',
                'notes' => $reason
            ),
            array('id' => $affiliate_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Send rejection email to affiliate
            $this->send_rejection_notification($affiliate, $reason);
            
            wp_send_json_success(['message' => __('Affiliate rejected successfully.', 'flexpress')]);
        } else {
            wp_send_json_error(['message' => __('Failed to reject affiliate.', 'flexpress')]);
        }
    }
    
    /**
     * Suspend affiliate
     */
    public function suspend_affiliate() {
        check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'flexpress')]);
        }
        
        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$affiliate_id) {
            wp_send_json_error(['message' => __('Invalid affiliate ID.', 'flexpress')]);
        }
        
        $affiliate = $this->get_affiliate($affiliate_id);
        if (!$affiliate) {
            wp_send_json_error(['message' => __('Affiliate not found.', 'flexpress')]);
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'flexpress_affiliates',
            array(
                'status' => 'suspended',
                'notes' => $reason
            ),
            array('id' => $affiliate_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => __('Affiliate suspended successfully.', 'flexpress')]);
        } else {
            wp_send_json_error(['message' => __('Failed to suspend affiliate.', 'flexpress')]);
        }
    }
    
    /**
     * Send application notification to admin
     * 
     * @param array $data Application data
     * @param int $affiliate_id Affiliate ID
     */
    private function send_application_notification($data, $affiliate_id) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('New Affiliate Application - %s', 'flexpress'), $site_name);
        
        $message = sprintf(__("A new affiliate application has been submitted:\n\n", 'flexpress'));
        $message .= sprintf(__("Name: %s\n", 'flexpress'), $data['affiliate_name']);
        $message .= sprintf(__("Email: %s\n", 'flexpress'), $data['affiliate_email']);
        $message .= sprintf(__("Website: %s\n", 'flexpress'), $data['affiliate_website']);
        $message .= sprintf(__("Payout Method: %s\n", 'flexpress'), $data['payout_method']);
        $message .= sprintf(__("Submitted: %s\n", 'flexpress'), $data['submitted_at']);
        
        if (!empty($data['marketing_experience'])) {
            $message .= sprintf(__("\nMarketing Experience:\n%s\n", 'flexpress'), $data['marketing_experience']);
        }
        
        $message .= sprintf(__("\nAffiliate ID: %d\n", 'flexpress'), $affiliate_id);
        $message .= sprintf(__("Review at: %s\n", 'flexpress'), admin_url('admin.php?page=flexpress-affiliate-settings'));
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Send approval notification to affiliate
     * 
     * @param object $affiliate Affiliate object
     */
    private function send_approval_notification($affiliate) {
        $site_name = get_bloginfo('name');
        $dashboard_url = home_url('/affiliate-dashboard');
        
        $subject = sprintf(__('Your Affiliate Application Has Been Approved - %s', 'flexpress'), $site_name);
        
        $message = sprintf(__("Congratulations %s!\n\n", 'flexpress'), $affiliate->display_name);
        $message .= sprintf(__("Your affiliate application has been approved. You can now start promoting %s and earning commissions.\n\n", 'flexpress'), $site_name);
        $message .= sprintf(__("Your Affiliate Code: %s\n", 'flexpress'), $affiliate->affiliate_code);
        $message .= sprintf(__("Your Referral URL: %s\n", 'flexpress'), $affiliate->referral_url);
        $message .= sprintf(__("Dashboard: %s\n\n", 'flexpress'), $dashboard_url);
        $message .= sprintf(__("Commission Rates:\n", 'flexpress'));
        $message .= sprintf(__("- Initial Sales: %s%%\n", 'flexpress'), $affiliate->commission_initial);
        $message .= sprintf(__("- Recurring Payments: %s%%\n", 'flexpress'), $affiliate->commission_rebill);
        $message .= sprintf(__("- Unlock Purchases: %s%%\n", 'flexpress'), $affiliate->commission_unlock);
        $message .= sprintf(__("- Payout Threshold: $%s\n\n", 'flexpress'), $affiliate->payout_threshold);
        $message .= sprintf(__("Thank you for joining our affiliate program!\n\n", 'flexpress'));
        $message .= sprintf(__("Best regards,\n%s Team", 'flexpress'), $site_name);
        
        wp_mail($affiliate->email, $subject, $message);
    }
    
    /**
     * Send rejection notification to affiliate
     * 
     * @param object $affiliate Affiliate object
     * @param string $reason Rejection reason
     */
    private function send_rejection_notification($affiliate, $reason) {
        $site_name = get_bloginfo('name');
        
        $subject = sprintf(__('Affiliate Application Update - %s', 'flexpress'), $site_name);
        
        $message = sprintf(__("Dear %s,\n\n", 'flexpress'), $affiliate->display_name);
        $message .= sprintf(__("Thank you for your interest in our affiliate program. After careful review, we are unable to approve your application at this time.\n\n", 'flexpress'));
        
        if (!empty($reason)) {
            $message .= sprintf(__("Reason: %s\n\n", 'flexpress'), $reason);
        }
        
        $message .= sprintf(__("We encourage you to reapply in the future as our program requirements may change.\n\n", 'flexpress'));
        $message .= sprintf(__("Thank you for your understanding.\n\n", 'flexpress'));
        $message .= sprintf(__("Best regards,\n%s Team", 'flexpress'), $site_name);
        
        wp_mail($affiliate->email, $subject, $message);
    }
}

// Initialize the affiliate manager
FlexPress_Affiliate_Manager::get_instance();
