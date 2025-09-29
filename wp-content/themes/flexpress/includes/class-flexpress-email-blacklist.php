<?php
/**
 * FlexPress Email Blacklist System
 * 
 * Handles email blacklisting to prevent refund/chargeback users from re-registering
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FlexPress_Email_Blacklist {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_flexpress_add_to_blacklist', array($this, 'handle_add_to_blacklist'));
        add_action('wp_ajax_flexpress_remove_from_blacklist', array($this, 'handle_remove_from_blacklist'));
        add_action('wp_ajax_flexpress_get_blacklist', array($this, 'handle_get_blacklist'));
        
        // Prevent registration for blacklisted emails
        add_filter('registration_errors', array($this, 'check_blacklist_on_registration'), 10, 3);
        add_filter('wp_pre_insert_user_data', array($this, 'check_blacklist_before_insert'), 10, 3);
    }
    
    /**
     * Add email to blacklist
     * 
     * @param string $email Email address
     * @param string $reason Reason for blacklisting
     * @return bool Success status
     */
    public static function add_email($email, $reason = '') {
        if (!is_email($email)) {
            return false;
        }
        
        $email = strtolower(trim($email));
        $blacklist = get_option('flexpress_email_blacklist', array());
        
        // Check if already blacklisted
        if (isset($blacklist[$email])) {
            return true; // Already blacklisted
        }
        
        $blacklist[$email] = array(
            'email' => $email,
            'reason' => sanitize_text_field($reason),
            'date_added' => current_time('mysql'),
            'added_by' => get_current_user_id() ?: 'system'
        );
        
        $result = update_option('flexpress_email_blacklist', $blacklist);
        
        if ($result) {
            error_log('FlexPress Blacklist: Added email ' . $email . ' - Reason: ' . $reason);
        }
        
        return $result;
    }
    
    /**
     * Remove email from blacklist
     * 
     * @param string $email Email address
     * @return bool Success status
     */
    public static function remove_email($email) {
        if (!is_email($email)) {
            return false;
        }
        
        $email = strtolower(trim($email));
        $blacklist = get_option('flexpress_email_blacklist', array());
        
        if (!isset($blacklist[$email])) {
            return true; // Not blacklisted
        }
        
        unset($blacklist[$email]);
        $result = update_option('flexpress_email_blacklist', $blacklist);
        
        if ($result) {
            error_log('FlexPress Blacklist: Removed email ' . $email);
        }
        
        return $result;
    }
    
    /**
     * Check if email is blacklisted
     * 
     * @param string $email Email address
     * @return bool|array False if not blacklisted, array with blacklist info if blacklisted
     */
    public static function is_blacklisted($email) {
        if (!is_email($email)) {
            return false;
        }
        
        $email = strtolower(trim($email));
        $blacklist = get_option('flexpress_email_blacklist', array());
        
        return isset($blacklist[$email]) ? $blacklist[$email] : false;
    }
    
    /**
     * Get all blacklisted emails
     * 
     * @return array Blacklisted emails
     */
    public static function get_blacklist() {
        return get_option('flexpress_email_blacklist', array());
    }
    
    /**
     * Check blacklist during registration
     * 
     * @param WP_Error $errors Registration errors
     * @param string $sanitized_user_login Sanitized username
     * @param string $user_email User email
     * @return WP_Error Modified errors
     */
    public function check_blacklist_on_registration($errors, $sanitized_user_login, $user_email) {
        $blacklist_info = self::is_blacklisted($user_email);
        
        if ($blacklist_info) {
            $errors->add('email_blacklisted', 
                sprintf(
                    __('This email address is not allowed to register. Reason: %s', 'flexpress'),
                    $blacklist_info['reason'] ?: 'Not specified'
                )
            );
        }
        
        return $errors;
    }
    
    /**
     * Check blacklist before user insertion
     * 
     * @param array $userdata User data
     * @param bool $update Whether this is an update
     * @param int $user_id User ID
     * @return array Modified user data
     */
    public function check_blacklist_before_insert($userdata, $update, $user_id) {
        if (!$update && isset($userdata['user_email'])) {
            $blacklist_info = self::is_blacklisted($userdata['user_email']);
            
            if ($blacklist_info) {
                wp_die(
                    sprintf(
                        __('Registration failed: This email address is not allowed to register. Reason: %s', 'flexpress'),
                        $blacklist_info['reason'] ?: 'Not specified'
                    ),
                    __('Registration Failed', 'flexpress'),
                    array('response' => 403)
                );
            }
        }
        
        return $userdata;
    }
    
    /**
     * Handle AJAX request to add email to blacklist
     */
    public function handle_add_to_blacklist() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'flexpress_blacklist_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'flexpress')));
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage blacklist.', 'flexpress')));
            return;
        }
        
        $email = sanitize_email($_POST['email'] ?? '');
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Invalid email address.', 'flexpress')));
            return;
        }
        
        $result = self::add_email($email, $reason);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Email added to blacklist successfully.', 'flexpress')));
        } else {
            wp_send_json_error(array('message' => __('Failed to add email to blacklist.', 'flexpress')));
        }
    }
    
    /**
     * Handle AJAX request to remove email from blacklist
     */
    public function handle_remove_from_blacklist() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'flexpress_blacklist_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'flexpress')));
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage blacklist.', 'flexpress')));
            return;
        }
        
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Invalid email address.', 'flexpress')));
            return;
        }
        
        $result = self::remove_email($email);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Email removed from blacklist successfully.', 'flexpress')));
        } else {
            wp_send_json_error(array('message' => __('Failed to remove email from blacklist.', 'flexpress')));
        }
    }
    
    /**
     * Handle AJAX request to get blacklist
     */
    public function handle_get_blacklist() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'flexpress_blacklist_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'flexpress')));
            return;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to view blacklist.', 'flexpress')));
            return;
        }
        
        $blacklist = self::get_blacklist();
        wp_send_json_success(array('blacklist' => $blacklist));
    }
}

// Initialize the blacklist system
new FlexPress_Email_Blacklist();

/**
 * Helper function to add email to blacklist
 * 
 * @param string $email Email address
 * @param string $reason Reason for blacklisting
 * @return bool Success status
 */
function flexpress_add_email_to_blacklist($email, $reason = '') {
    return FlexPress_Email_Blacklist::add_email($email, $reason);
}

/**
 * Helper function to check if email is blacklisted
 * 
 * @param string $email Email address
 * @return bool|array False if not blacklisted, array with blacklist info if blacklisted
 */
function flexpress_is_email_blacklisted($email) {
    return FlexPress_Email_Blacklist::is_blacklisted($email);
}
