<?php
/**
 * FlexPress Plunk Subscriber Management
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Plunk Subscriber Class
 */
class FlexPress_Plunk_Subscriber {
    /**
     * Plunk API instance
     *
     * @var FlexPress_Plunk_API
     */
    private $api;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api = new FlexPress_Plunk_API();
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // User registration hook
        add_action('user_register', array($this, 'subscribe_new_user'), 10, 1);
        
        // User deletion hook
        add_action('delete_user', array($this, 'delete_plunk_contact'), 10, 1);
        
        // AJAX handlers
        add_action('wp_ajax_plunk_newsletter_signup', array($this, 'handle_newsletter_signup'));
        add_action('wp_ajax_nopriv_plunk_newsletter_signup', array($this, 'handle_newsletter_signup'));
        
        add_action('wp_ajax_plunk_toggle_subscription', array($this, 'handle_toggle_subscription'));
        add_action('wp_ajax_nopriv_plunk_toggle_subscription', array($this, 'handle_toggle_subscription'));
        
        // Test connection AJAX
        add_action('wp_ajax_test_plunk_connection', array($this, 'test_plunk_connection'));
        
        // Sync users AJAX
        add_action('wp_ajax_plunk_sync_users', array($this, 'sync_users'));
    }

    /**
     * Subscribe new user to Plunk
     */
    public function subscribe_new_user($user_id) {
        if (!$this->api->is_configured()) {
            return;
        }

        $options = get_option('flexpress_plunk_settings', array());
        if (empty($options['auto_subscribe_users'])) {
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        // Check if contact already exists
        $existing_contact = $this->api->get_contact_by_email($user->user_email);
        
        $contact_data = array(
            'email' => $user->user_email,
            'subscribed' => true,
            'data' => array(
                'name' => $user->display_name,
                'signupDate' => date('c'),
                'source' => 'Membership Registration',
                'userType' => 'member',
                'membershipStatus' => 'active',
                'userId' => (string) $user_id
            )
        );
        
        if (!is_wp_error($existing_contact) && isset($existing_contact['id'])) {
            // Update existing contact
            $result = $this->api->update_contact($existing_contact['id'], $contact_data);
            if (!is_wp_error($result)) {
                update_user_meta($user_id, 'plunk_contact_id', $existing_contact['id']);
            }
        } else {
            // Create new contact
            $result = $this->api->add_contact($contact_data);
            if (!is_wp_error($result) && isset($result['id'])) {
                update_user_meta($user_id, 'plunk_contact_id', $result['id']);
                
                // Track registration event
                $this->api->track_event($result['id'], 'user-registration', $user->user_email, array(
                    'userId' => (string) $user_id,
                    'timestamp' => date('c')
                ));
            }
        }
    }

    /**
     * Delete Plunk contact when user is deleted
     */
    public function delete_plunk_contact($user_id) {
        if (!$this->api->is_configured()) {
            return;
        }

        $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
        
        if (!$contact_id) {
            // Try to find by email
            $user = get_userdata($user_id);
            if ($user && $user->user_email) {
                $contact = $this->api->get_contact_by_email($user->user_email);
                if (!is_wp_error($contact) && isset($contact['id'])) {
                    $contact_id = $contact['id'];
                }
            }
        }
        
        if ($contact_id) {
            $this->api->delete_contact($contact_id);
        }
    }

    /**
     * Handle newsletter signup AJAX request
     */
    public function handle_newsletter_signup() {
        // Verify Turnstile token if Turnstile is enabled
        if (flexpress_is_turnstile_enabled()) {
            $token = $_POST['cf-turnstile-response'] ?? '';
            if (!flexpress_validate_turnstile_response($token)) {
                wp_send_json_error('Security verification failed');
                return;
            }
        }
        
        // Check honeypot
        if (!empty($_POST['website'])) {
            wp_send_json_error('Bot detected');
            return;
        }
        
        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
            return;
        }

        if (!$this->api->is_configured()) {
            wp_send_json_error('Plunk is not configured. Please enter Public API Key, Secret API Key, and Install URL.');
            return;
        }

        $existing_contact = $this->api->get_contact_by_email($email);
        
        if (is_wp_error($existing_contact)) {
            // Create new contact
            $contact_data = array(
                'email' => $email,
                'subscribed' => false, // Will be confirmed via email
                'data' => array(
                    'signupDate' => date('c'),
                    'source' => 'Newsletter Modal',
                    'userType' => 'newsletter_subscriber'
                )
            );
            
            $result = $this->api->add_contact($contact_data);
            
            if (!is_wp_error($result) && isset($result['id'])) {
                $this->api->track_event($result['id'], 'newsletter-signup', $email, array(
                    'source' => 'modal',
                    'timestamp' => date('c')
                ));
                
                wp_send_json_success(array(
                    'message' => 'Thank you for signing up! Please check your email to confirm your subscription.',
                    'new_subscriber' => true
                ));
            } else {
                wp_send_json_error('Failed to create contact: ' . (is_wp_error($result) ? $result->get_error_message() : 'Unknown error'));
            }
        } else {
            // Track event for existing contact
            $this->api->track_event($existing_contact['id'], 'newsletter-signup', $email, array(
                'source' => 'modal',
                'timestamp' => date('c')
            ));
            
            wp_send_json_success(array(
                'message' => 'Thanks for signing up!',
                'already_exists' => true
            ));
        }
    }

    /**
     * Handle subscription toggle AJAX request
     */
    public function handle_toggle_subscription() {
        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in to manage your subscription');
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'plunk_toggle_subscription')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $user = wp_get_current_user();
        $contact_id = get_user_meta($user->ID, 'plunk_contact_id', true);
        
        if (!$contact_id) {
            // Try to find by email
            $contact = $this->api->get_contact_by_email($user->user_email);
            if (!is_wp_error($contact) && isset($contact['id'])) {
                $contact_id = $contact['id'];
                update_user_meta($user->ID, 'plunk_contact_id', $contact_id);
            } else {
                wp_send_json_error('Contact not found');
                return;
            }
        }

        $action = sanitize_text_field($_POST['action_type']);
        
        if ($action === 'subscribe') {
            $result = $this->api->subscribe_contact($contact_id, $user->user_email);
            $message = 'Successfully subscribed to newsletter';
        } elseif ($action === 'unsubscribe') {
            $result = $this->api->unsubscribe_contact($contact_id, $user->user_email);
            $message = 'Successfully unsubscribed from newsletter';
        } else {
            wp_send_json_error('Invalid action');
            return;
        }

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array('message' => $message));
        }
    }

    /**
     * Test Plunk connection
     */
    public function test_plunk_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'flexpress_plunk_test')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        if (!$this->api->is_configured()) {
            error_log('[FlexPress][Plunk][Test] Not configured: missing keys or install URL');
            wp_send_json_error('Please enter Public API Key, Secret API Key, and Install URL first.');
            return;
        }

        error_log('[FlexPress][Plunk][Test] Starting connection test');
        $result = $this->api->test_connection();
        
        if (is_wp_error($result)) {
            error_log('[FlexPress][Plunk][Test][Error] ' . $result->get_error_message());
            wp_send_json_error('Plunk connection failed: ' . $result->get_error_message());
        } else {
            error_log('[FlexPress][Plunk][Test] Success');
            wp_send_json_success('Plunk connection successful! Your API credentials are valid.');
        }
    }

    /**
     * Sync users with Plunk
     */
    public function sync_users() {
        if (!wp_verify_nonce($_POST['nonce'], 'flexpress_plunk_test')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $limit = absint($_POST['limit'] ?? 50);
        $results = $this->api->sync_existing_users($limit);
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($results as $result) {
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => "Sync completed. {$success_count} users synced successfully, {$error_count} errors.",
            'results' => $results
        ));
    }

    /**
     * Get user's Plunk contact data
     */
    public function get_user_contact_data($user_id) {
        $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
        
        if ($contact_id) {
            return $this->api->get_contact_by_id($contact_id);
        } else {
            $user = get_userdata($user_id);
            if ($user) {
                return $this->api->get_contact_by_email($user->user_email);
            }
        }
        
        return new WP_Error('contact_not_found', 'Contact not found');
    }

    /**
     * Track custom event for user
     */
    public function track_user_event($user_id, $event_name, $event_data = array()) {
        $contact_id = get_user_meta($user_id, 'plunk_contact_id', true);
        
        if (!$contact_id) {
            $user = get_userdata($user_id);
            if ($user) {
                $contact = $this->api->get_contact_by_email($user->user_email);
                if (!is_wp_error($contact) && isset($contact['id'])) {
                    $contact_id = $contact['id'];
                    update_user_meta($user_id, 'plunk_contact_id', $contact_id);
                }
            }
        }
        
        if ($contact_id) {
            $user = get_userdata($user_id);
            return $this->api->track_event($contact_id, $event_name, $user->user_email, $event_data);
        }
        
        return new WP_Error('contact_not_found', 'Contact not found');
    }
}

// Initialize the Plunk subscriber management
new FlexPress_Plunk_Subscriber();
