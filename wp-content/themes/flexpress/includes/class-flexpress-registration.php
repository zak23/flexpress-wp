<?php
/**
 * Handles the registration form functionality
 */
class FlexPress_Registration {
    /**
     * Initialize the registration functionality
     */
    public function __construct() {
        add_shortcode('flexpress_register_form', array($this, 'render_registration_form'));
        add_action('wp_ajax_nopriv_flexpress_register', array($this, 'handle_registration'));
        add_action('wp_ajax_flexpress_register', array($this, 'handle_registration'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueue_scripts() {
        // Always enqueue the script and localization, but handle conflicts in the JS
        wp_enqueue_script(
            'flexpress-registration',
            get_template_directory_uri() . '/assets/js/registration.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('flexpress-registration', 'flexpressRegistration', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flexpress-registration-nonce'),
            'isJoinPage' => is_page('join') || is_page_template('page-templates/join.php')
        ));
    }

    /**
     * Render the registration form
     */
    public function render_registration_form() {
        ob_start();
        ?>
        <form id="flexpress-register-form" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="invalid-feedback">Please enter a password.</div>
            </div>

            <div class="mb-3">
                <label for="password_confirm" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                <div class="invalid-feedback">Please confirm your password.</div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">
                    I agree to the <a href="/terms" class="text-primary">Terms of Service</a> and
                    <a href="/privacy" class="text-primary">Privacy Policy</a>
                </label>
                <div class="invalid-feedback">You must agree to the terms and conditions.</div>
            </div>

            <div class="alert alert-danger d-none" id="registration-error"></div>

            <button type="submit" class="btn btn-primary w-100">
                Create Account
            </button>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle the registration form submission
     */
    public function handle_registration() {
        check_ajax_referer('flexpress-registration-nonce', 'nonce');

        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];
        $selected_plan = sanitize_text_field($_POST['selected_plan']);

        // Validate input
        if (empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Please fill in all required fields.'));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Please enter a valid email address.'));
        }

        if ($password !== $password_confirm) {
            wp_send_json_error(array('message' => 'Passwords do not match.'));
        }

        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'This email address is already registered.'));
        }

        // Check if email is blacklisted
        if (class_exists('FlexPress_Email_Blacklist')) {
            $blacklist_info = FlexPress_Email_Blacklist::is_blacklisted($email);
            if ($blacklist_info) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        'This email address is not allowed to register. Reason: %s',
                        $blacklist_info['reason'] ?: 'Not specified'
                    )
                ));
            }
        }

        // Create user
        $user_id = wp_create_user($email, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        // Set display name from email prefix (part before @)
        $email_parts = explode('@', $email);
        $display_name = $email_parts[0];
        update_user_meta($user_id, 'flexpress_display_name', $display_name);
        
        // Store registration timestamp and IP for tracking
        update_user_meta($user_id, 'registration_date', current_time('mysql'));
        update_user_meta($user_id, 'registration_ip', $_SERVER['REMOTE_ADDR']);
        
        // Store signup source and tracking data
        $signup_source = 'direct'; // Default source
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $referrer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
            if (strpos($referrer, 'google') !== false) {
                $signup_source = 'google';
            } elseif (strpos($referrer, 'facebook') !== false) {
                $signup_source = 'facebook';
            } elseif (strpos($referrer, 'twitter') !== false) {
                $signup_source = 'twitter';
            } elseif (strpos($referrer, 'instagram') !== false) {
                $signup_source = 'instagram';
            } elseif (strpos($referrer, 'reddit') !== false) {
                $signup_source = 'reddit';
            } else {
                $signup_source = 'referral';
            }
        }
        update_user_meta($user_id, 'signup_source', $signup_source);
        
        // Check for affiliate referral
        if (!empty($_COOKIE['flexpress_affiliate_tracking'])) {
            $affiliate_data = sanitize_text_field($_COOKIE['flexpress_affiliate_tracking']);
            update_user_meta($user_id, 'affiliate_referred_by', $affiliate_data);
        }
        
        // Store selected plan for later reference
        if (!empty($selected_plan)) {
            update_user_meta($user_id, 'selected_plan', $selected_plan);
        }

        // Set user role
        $user = new WP_User($user_id);
        $user->set_role('subscriber');

        // Log user registration activity
        if (class_exists('FlexPress_Activity_Logger')) {
            FlexPress_Activity_Logger::log_registration($user_id, $selected_plan, array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'selected_plan' => $selected_plan,
                'registration_ip' => $_SERVER['REMOTE_ADDR']
            ));
        }

        // Create subscription if plan is selected
        if (!empty($selected_plan)) {
            $this->create_subscription($user_id, $selected_plan);
        }

        // Log the user in
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        wp_send_json_success(array(
            'message' => 'Registration successful!',
            'redirect_url' => home_url('/my-account')
        ));
    }

    /**
     * Create a subscription for the user
     */
    private function create_subscription($user_id, $plan) {
        // Get the appropriate price ID based on the plan
        $price_id = $plan === 'annual' ? 'price_annual' : 'price_monthly';

        // Create the subscription using Verotel
        $subscription = flexpress_create_verotel_subscription($user_id, $price_id);

        if (is_wp_error($subscription)) {
            // Log the error but don't prevent registration
            error_log('Failed to create subscription: ' . $subscription->get_error_message());
            return;
        }

        // Update user meta with subscription details
        if (isset($subscription['id'])) {
            update_user_meta($user_id, 'subscription_id', $subscription['id']);
        }

        if (isset($subscription['transaction_id'])) {
            update_user_meta($user_id, 'verotel_transaction_id', $subscription['transaction_id']);
        }
        
        // Set initial subscription status (will be updated by webhook when payment is confirmed)
        update_user_meta($user_id, 'subscription_status', 'pending');
        update_user_meta($user_id, 'subscription_plan', $plan);
        
        // Log subscription creation for debugging
        error_log(sprintf(
            'FlexPress: Created subscription for user ID %d with plan %s. Transaction ID: %s',
            $user_id,
            $plan,
            isset($subscription['transaction_id']) ? $subscription['transaction_id'] : 'N/A'
        ));
    }
}

// Initialize the registration functionality
new FlexPress_Registration(); 