<?php

/**
 * FlexPress SMTP2Go Integration
 * 
 * Handles SMTP2Go SMTP configuration for internal emails
 * 
 * @package FlexPress
 * @since 1.0.0
 */

class FlexPress_SMTP2Go
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('phpmailer_init', array($this, 'configure_smtp'));
        add_action('wp_mail_failed', array($this, 'log_email_failure'));
    }

    /**
     * Configure PHPMailer for SMTP2Go
     * 
     * @param PHPMailer $phpmailer
     */
    public function configure_smtp($phpmailer)
    {
        $options = get_option('flexpress_smtp2go_settings', array());

        // Debug logging
        error_log('FlexPress SMTP2Go: configure_smtp called');

        // Check if SMTP2Go is enabled
        if (!isset($options['enable_smtp2go']) || !$options['enable_smtp2go']) {
            error_log('FlexPress SMTP2Go: Not enabled, skipping');
            return;
        }

        // Check if this should be handled by SMTP2Go
        if (!$this->should_use_smtp2go($phpmailer)) {
            error_log('FlexPress SMTP2Go: Should not handle this email, skipping');
            return;
        }

        error_log('FlexPress SMTP2Go: Proceeding with SMTP2Go configuration');

        // Configure SMTP2Go settings
        $phpmailer->isSMTP();
        $phpmailer->Host = $options['smtp_host'] ?? 'mail.smtp2go.com';
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $options['smtp_username'] ?? '';
        $phpmailer->Password = $options['smtp_password'] ?? '';
        $phpmailer->SMTPSecure = $options['smtp_encryption'] ?? 'tls';
        $phpmailer->Port = $options['smtp_port'] ?? 587;

        // Set from address if configured
        if (!empty($options['from_email'])) {
            // Default to site title if from_name is not set
            $from_name = !empty($options['from_name']) ? $options['from_name'] : (get_bloginfo('name') ?: '');
            $phpmailer->setFrom($options['from_email'], $from_name);
            error_log('FlexPress SMTP2Go: Set From address to ' . $options['from_email'] . ' (' . $from_name . ')');
        } else {
            error_log('FlexPress SMTP2Go: No from_email configured in options');
        }

        // Debug: Log configuration details (without password)
        error_log('FlexPress SMTP2Go: Auth details - Host: ' . $phpmailer->Host . ', Port: ' . $phpmailer->Port . ', Username: ' . $phpmailer->Username . ', Password length: ' . strlen($phpmailer->Password));
        error_log('FlexPress SMTP2Go: SMTP configured for ' . $phpmailer->From . ' via ' . $phpmailer->Host);
    }

    /**
     * Determine if SMTP2Go should handle this email
     * 
     * @param PHPMailer $phpmailer
     * @return bool
     */
    private function should_use_smtp2go($phpmailer)
    {
        $options = get_option('flexpress_smtp2go_settings', array());

        error_log('FlexPress SMTP2Go: should_use_smtp2go called');

        // Check if internal emails only is enabled
        if (!isset($options['use_for_internal_only']) || !$options['use_for_internal_only']) {
            error_log('FlexPress SMTP2Go: Handles all emails');
            return true;
        }

        // Get from domain
        $from_email = $phpmailer->From;
        $from_domain = substr(strrchr($from_email, "@"), 1);

        error_log('FlexPress SMTP2Go: From email = ' . $from_email . ', From domain = ' . $from_domain);

        // Get recipient domains
        $recipients = $phpmailer->getAllRecipientAddresses();
        error_log('FlexPress SMTP2Go: Recipients = ' . print_r($recipients, true));

        // Check if any recipient is from the same domain as the sender
        // OR if any recipient is from the site domain
        $site_domain = parse_url(home_url(), PHP_URL_HOST);
        foreach (array_keys($recipients) as $email) {
            $recipient_domain = substr(strrchr($email, "@"), 1);
            error_log('FlexPress SMTP2Go: Checking recipient ' . $email . ' (domain: ' . $recipient_domain . ')');

            if ($recipient_domain === $from_domain || $recipient_domain === $site_domain) {
                error_log('FlexPress SMTP2Go: Found internal email, using SMTP2Go');
                return true;
            }
        }

        error_log('FlexPress SMTP2Go: No internal emails found, not using SMTP2Go');
        return false;
    }

    /**
     * Log email failure
     * 
     * @param WP_Error $wp_error
     */
    public function log_email_failure($wp_error)
    {
        error_log('FlexPress SMTP2Go: Email failed - ' . $wp_error->get_error_message());

        // Log additional error details
        $error_data = $wp_error->get_error_data();
        if ($error_data) {
            error_log('FlexPress SMTP2Go: Error data - ' . print_r($error_data, true));
        }

        // Log all error codes
        $error_codes = $wp_error->get_error_codes();
        if ($error_codes) {
            error_log('FlexPress SMTP2Go: Error codes - ' . implode(', ', $error_codes));
        }
    }
}

// Initialize the SMTP2Go integration
new FlexPress_SMTP2Go();
