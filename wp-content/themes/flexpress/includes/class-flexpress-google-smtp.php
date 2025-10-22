<?php

/**
 * FlexPress Google SMTP Integration
 * 
 * Handles SMTP configuration and email delivery through Google SMTP
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FlexPress_Google_SMTP
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('phpmailer_init', array($this, 'configure_smtp'));
        add_action('wp_mail_failed', array($this, 'log_email_failure'));
        add_action('wp_mail_succeeded', array($this, 'log_email_success'));
        add_action('wp_ajax_test_google_smtp_connection', array($this, 'test_google_smtp_connection'));
    }

    /**
     * Configure SMTP settings for PHPMailer
     */
    public function configure_smtp($phpmailer)
    {
        $options = get_option('flexpress_google_smtp_settings', array());

        // Debug logging
        error_log('FlexPress Google SMTP: configure_smtp called');
        error_log('FlexPress Google SMTP: Options = ' . print_r($options, true));

        // Check if Google SMTP is enabled
        if (!isset($options['enable_google_smtp']) || !$options['enable_google_smtp']) {
            error_log('FlexPress Google SMTP: Not enabled, skipping');
            return;
        }

        // Check if we should use Google SMTP for this email
        if (!$this->should_use_google_smtp($phpmailer, $options)) {
            error_log('FlexPress Google SMTP: Should not use Google SMTP for this email, skipping');
            return;
        }

        error_log('FlexPress Google SMTP: Proceeding with Google SMTP configuration');

        // Get configuration
        $config = $this->get_google_smtp_config($options);

        // Validate required settings
        $required_fields = array('smtp_host', 'smtp_username', 'smtp_password', 'from_email');
        foreach ($required_fields as $field) {
            if (empty($config[$field])) {
                error_log('FlexPress Google SMTP: Missing required field: ' . $field);
                return;
            }
        }

        // Configure PHPMailer for SMTP
        $phpmailer->isSMTP();
        $phpmailer->Host = $config['smtp_host'];
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $config['smtp_username'];
        $phpmailer->Password = $config['smtp_password'];
        $phpmailer->Port = $config['smtp_port'];

        // Debug: Log authentication details (without password)
        error_log('FlexPress Google SMTP: Auth details - Host: ' . $config['smtp_host'] . ', Port: ' . $config['smtp_port'] . ', Username: ' . $config['smtp_username'] . ', Password length: ' . strlen($config['smtp_password']));

        // Set encryption
        if ($config['smtp_encryption'] === 'tls') {
            $phpmailer->SMTPSecure = 'tls';
        } elseif ($config['smtp_encryption'] === 'ssl') {
            $phpmailer->SMTPSecure = 'ssl';
        }

        // Set from email and name
        $from_email = $config['from_email'];
        $from_name = $config['from_name'];

        $phpmailer->setFrom($from_email, $from_name);

        // Set reply-to if not already set
        if (empty($phpmailer->getReplyToAddresses())) {
            $phpmailer->addReplyTo($from_email, $from_name);
        }

        // Log SMTP configuration
        error_log('FlexPress Google SMTP: SMTP configured for ' . $from_email . ' via ' . $config['smtp_host']);
    }

    /**
     * Determine if Google SMTP should be used for this email
     */
    private function should_use_google_smtp($phpmailer, $options)
    {
        error_log('FlexPress Google SMTP: should_use_google_smtp called');

        // If "use for internal only" is disabled, use Google SMTP for all emails
        if (!isset($options['use_for_internal_only']) || !$options['use_for_internal_only']) {
            error_log('FlexPress Google SMTP: use_for_internal_only is disabled, using Google SMTP for all emails');
            return true;
        }

        // Check if any recipient is on the same domain as the from email
        $from_email = $phpmailer->From;
        $from_domain = substr(strrchr($from_email, "@"), 1);

        error_log('FlexPress Google SMTP: From email = ' . $from_email . ', From domain = ' . $from_domain);

        $recipients = $phpmailer->getAllRecipientAddresses();
        error_log('FlexPress Google SMTP: Recipients = ' . print_r($recipients, true));

        foreach (array_keys($recipients) as $email) {
            $recipient_domain = substr(strrchr($email, "@"), 1);
            error_log('FlexPress Google SMTP: Checking recipient ' . $email . ' (domain: ' . $recipient_domain . ')');

            if ($recipient_domain === $from_domain) {
                error_log('FlexPress Google SMTP: Found internal email, using Google SMTP');
                return true;
            }
        }

        error_log('FlexPress Google SMTP: No internal emails found, not using Google SMTP');
        return false;
    }

    /**
     * Get Google SMTP configuration
     */
    private function get_google_smtp_config($options)
    {
        $config = array();

        $config['smtp_host'] = $options['smtp_host'] ?? 'smtp.gmail.com';
        $config['smtp_port'] = $options['smtp_port'] ?? 587;
        $config['smtp_encryption'] = $options['smtp_encryption'] ?? 'tls';
        $config['smtp_username'] = $options['smtp_username'] ?? '';
        $config['smtp_password'] = $options['smtp_password'] ?? '';
        $config['from_email'] = $options['from_email'] ?? '';
        $config['from_name'] = $options['from_name'] ?? get_bloginfo('name');

        return $config;
    }

    /**
     * Log email failure
     */
    public function log_email_failure($wp_error)
    {
        error_log('FlexPress Google SMTP: Email failed - ' . $wp_error->get_error_message());

        // Log additional error details
        $error_data = $wp_error->get_error_data();
        if ($error_data) {
            error_log('FlexPress Google SMTP: Error data - ' . print_r($error_data, true));
        }

        // Log all error codes
        $error_codes = $wp_error->get_error_codes();
        if ($error_codes) {
            error_log('FlexPress Google SMTP: Error codes - ' . implode(', ', $error_codes));
        }
    }

    /**
     * Log email success
     */
    public function log_email_success($mail_data)
    {
        error_log('FlexPress Google SMTP: Email sent successfully to ' . implode(', ', array_keys($mail_data['to'])));
    }

    /**
     * Test Google SMTP connection via AJAX
     */
    public function test_google_smtp_connection()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'test_google_smtp_connection')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $options = get_option('flexpress_google_smtp_settings', array());

        // Validate required settings
        if (empty($options['smtp_username']) || empty($options['smtp_password']) || empty($options['from_email'])) {
            wp_send_json_error('Please configure SMTP username, password, and from email before testing.');
            return;
        }

        $test_email = $options['test_email'] ?? '';
        if (empty($test_email)) {
            wp_send_json_error('Please enter a test email address.');
            return;
        }

        // Create test email
        $subject = 'FlexPress Google SMTP Test - ' . date('Y-m-d H:i:s');
        $message = 'This is a test email from FlexPress Google SMTP integration.' . "\n\n";
        $message .= 'Test Details:' . "\n";
        $message .= '- Time: ' . date('Y-m-d H:i:s') . "\n";
        $message .= '- Site: ' . get_bloginfo('name') . "\n";
        $message .= '- URL: ' . home_url() . "\n";
        $message .= '- SMTP Host: ' . ($options['smtp_host'] ?? 'smtp.gmail.com') . "\n";
        $message .= '- SMTP Port: ' . ($options['smtp_port'] ?? 587) . "\n";
        $message .= '- Encryption: ' . ($options['smtp_encryption'] ?? 'tls') . "\n";

        // Send test email
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . ($options['from_name'] ?? get_bloginfo('name')) . ' <' . $options['from_email'] . '>'
        );

        $result = wp_mail($test_email, $subject, $message, $headers);

        if ($result) {
            wp_send_json_success('Test email sent successfully! Check your inbox for the test message.');
        } else {
            wp_send_json_error('Failed to send test email. Check your SMTP configuration and error logs.');
        }
    }
}

// Initialize the Google SMTP integration
new FlexPress_Google_SMTP();
