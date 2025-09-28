<?php
/**
 * FlexPress Amazon SES SMTP Integration
 * 
 * Handles SMTP configuration and email delivery through Amazon SES
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FlexPress_SES_SMTP {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('phpmailer_init', array($this, 'configure_smtp'));
        add_action('wp_mail_failed', array($this, 'log_email_failure'));
        add_action('wp_mail_succeeded', array($this, 'log_email_success'));
    }
    
    /**
     * Configure SMTP settings for PHPMailer
     */
    public function configure_smtp($phpmailer) {
        $options = get_option('flexpress_ses_settings', array());
        
        // Debug logging
        error_log('FlexPress SES: configure_smtp called');
        
        // Check if Google SMTP should handle this email instead
        if ($this->should_google_smtp_handle_email($phpmailer)) {
            error_log('FlexPress SES: Google SMTP should handle this email, skipping SES');
            return;
        }
        
        // Check if SMTP2Go should handle this email instead
        if ($this->should_smtp2go_handle_email($phpmailer)) {
            error_log('FlexPress SES: SMTP2Go should handle this email, skipping SES');
            return;
        }
        
        // Check if SES is enabled
        if (!isset($options['enable_ses']) || !$options['enable_ses']) {
            error_log('FlexPress SES: Not enabled, skipping');
            return;
        }
        
        error_log('FlexPress SES: Proceeding with SES configuration');
        
        // Get configuration from environment variables or database
        $config = $this->get_ses_config($options);
        
        // Validate required settings
        $required_fields = array('smtp_host', 'smtp_username', 'smtp_password', 'from_email');
        foreach ($required_fields as $field) {
            if (empty($config[$field])) {
                error_log('FlexPress SES: Missing required field: ' . $field);
                return;
            }
        }
        
        // Configure PHPMailer for SMTP
        $phpmailer->isSMTP();
        $phpmailer->Host = $config['smtp_host'];
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $config['smtp_username'];
        $phpmailer->Password = $config['smtp_password'];
        $phpmailer->Port = isset($config['smtp_port']) ? $config['smtp_port'] : 587;
        
        // Set encryption
        $encryption = isset($config['smtp_encryption']) ? $config['smtp_encryption'] : 'tls';
        if ($encryption === 'tls') {
            $phpmailer->SMTPSecure = 'tls';
        } elseif ($encryption === 'ssl') {
            $phpmailer->SMTPSecure = 'ssl';
        }
        
        // Set from email and name
        $from_email = $config['from_email'];
        $from_name = isset($config['from_name']) ? $config['from_name'] : get_bloginfo('name');
        
        $phpmailer->setFrom($from_email, $from_name);
        
        // Set reply-to if not already set
        if (empty($phpmailer->getReplyToAddresses())) {
            $phpmailer->addReplyTo($from_email, $from_name);
        }
        
        // Log SMTP configuration
        error_log('FlexPress SES: SMTP configured for ' . $from_email . ' via ' . $config['smtp_host']);
    }
    
    /**
     * Check if Google SMTP should handle this email instead of SES
     */
    private function should_google_smtp_handle_email($phpmailer) {
        $google_options = get_option('flexpress_google_smtp_settings', array());
        
        // Check if Google SMTP is enabled
        if (!isset($google_options['enable_google_smtp']) || !$google_options['enable_google_smtp']) {
            return false;
        }
        
        // If Google SMTP is set to handle all emails, let it handle this one
        if (!isset($google_options['use_for_internal_only']) || !$google_options['use_for_internal_only']) {
            error_log('FlexPress SES: Google SMTP handles all emails, letting it handle this one');
            return true;
        }
        
        // Check if this is an internal email (same domain)
        $from_email = $phpmailer->From;
        $from_domain = substr(strrchr($from_email, "@"), 1);
        
        $recipients = $phpmailer->getAllRecipientAddresses();
        foreach (array_keys($recipients) as $email) {
            $recipient_domain = substr(strrchr($email, "@"), 1);
            if ($recipient_domain === $from_domain) {
                error_log('FlexPress SES: Internal email detected, letting Google SMTP handle it');
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if SMTP2Go should handle this email
     * 
     * @param PHPMailer $phpmailer
     * @return bool
     */
    private function should_smtp2go_handle_email($phpmailer) {
        $smtp2go_options = get_option('flexpress_smtp2go_settings', array());
        
        if (!isset($smtp2go_options['enable_smtp2go']) || !$smtp2go_options['enable_smtp2go']) {
            return false;
        }
        
        if (!isset($smtp2go_options['use_for_internal_only']) || !$smtp2go_options['use_for_internal_only']) {
            error_log('FlexPress SES: SMTP2Go handles all emails, letting it handle this one');
            return true;
        }
        
        $from_email = $phpmailer->From;
        $from_domain = substr(strrchr($from_email, "@"), 1);
        
        $recipients = $phpmailer->getAllRecipientAddresses();
        $site_domain = parse_url(home_url(), PHP_URL_HOST);
        foreach (array_keys($recipients) as $email) {
            $recipient_domain = substr(strrchr($email, "@"), 1);
            if ($recipient_domain === $from_domain || $recipient_domain === $site_domain) {
                error_log('FlexPress SES: Internal email detected, letting SMTP2Go handle it');
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get SES configuration from environment variables or database
     */
    private function get_ses_config($options) {
        $config = array();
        
        // Check if environment variables should be used
        if (isset($options['use_env_vars']) && $options['use_env_vars']) {
            $config['aws_region'] = getenv('SES_AWS_REGION') ?: 'us-east-1';
            $config['smtp_host'] = getenv('SES_SMTP_HOST') ?: 'email-smtp.' . $config['aws_region'] . '.amazonaws.com';
            $config['smtp_port'] = getenv('SES_SMTP_PORT') ?: 587;
            $config['smtp_encryption'] = getenv('SES_SMTP_ENCRYPTION') ?: 'tls';
            $config['smtp_username'] = getenv('SES_SMTP_USERNAME') ?: '';
            $config['smtp_password'] = getenv('SES_SMTP_PASSWORD') ?: '';
            $config['from_email'] = getenv('SES_FROM_EMAIL') ?: '';
            $config['from_name'] = getenv('SES_FROM_NAME') ?: get_bloginfo('name');
        } else {
            // Use database values
            $config['aws_region'] = isset($options['aws_region']) ? $options['aws_region'] : 'us-east-1';
            $config['smtp_host'] = isset($options['smtp_host']) ? $options['smtp_host'] : '';
            $config['smtp_port'] = isset($options['smtp_port']) ? $options['smtp_port'] : 587;
            $config['smtp_encryption'] = isset($options['smtp_encryption']) ? $options['smtp_encryption'] : 'tls';
            $config['smtp_username'] = isset($options['smtp_username']) ? $options['smtp_username'] : '';
            $config['smtp_password'] = isset($options['smtp_password']) ? $options['smtp_password'] : '';
            $config['from_email'] = isset($options['from_email']) ? $options['from_email'] : '';
            $config['from_name'] = isset($options['from_name']) ? $options['from_name'] : get_bloginfo('name');
        }
        
        return $config;
    }
    
    /**
     * Log email failure
     */
    public function log_email_failure($wp_error) {
        $this->log_email_event('failure', $wp_error->get_error_message());
    }
    
    /**
     * Log email success
     */
    public function log_email_success($mail_data) {
        $this->log_email_event('success', 'Email sent successfully');
    }
    
    /**
     * Log email event
     */
    private function log_email_event($status, $message) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'status' => $status,
            'message' => $message,
            'to' => isset($_POST['to']) ? $_POST['to'] : 'unknown',
            'subject' => isset($_POST['subject']) ? $_POST['subject'] : 'unknown'
        );
        
        $email_log = get_option('flexpress_ses_email_log', array());
        $email_log[] = $log_entry;
        
        // Keep only last 100 entries
        if (count($email_log) > 100) {
            $email_log = array_slice($email_log, -100);
        }
        
        update_option('flexpress_ses_email_log', $email_log);
        
        error_log('FlexPress SES: ' . $status . ' - ' . $message);
    }
    
    /**
     * Get email statistics
     */
    public static function get_email_stats() {
        $email_log = get_option('flexpress_ses_email_log', array());
        
        $stats = array(
            'total' => count($email_log),
            'success' => 0,
            'failure' => 0,
            'last_24h' => 0,
            'last_7d' => 0
        );
        
        $now = time();
        $day_ago = $now - (24 * 60 * 60);
        $week_ago = $now - (7 * 24 * 60 * 60);
        
        foreach ($email_log as $entry) {
            $timestamp = strtotime($entry['timestamp']);
            
            if ($entry['status'] === 'success') {
                $stats['success']++;
            } else {
                $stats['failure']++;
            }
            
            if ($timestamp > $day_ago) {
                $stats['last_24h']++;
            }
            
            if ($timestamp > $week_ago) {
                $stats['last_7d']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Clear email log
     */
    public static function clear_email_log() {
        delete_option('flexpress_ses_email_log');
    }
    
    /**
     * Test SES connection
     */
    public static function test_connection() {
        $options = get_option('flexpress_ses_settings', array());
        
        if (!isset($options['enable_ses']) || !$options['enable_ses']) {
            return array('success' => false, 'message' => 'SES is not enabled');
        }
        
        // Get configuration
        $ses_smtp = new self();
        $config = $ses_smtp->get_ses_config($options);
        
        $required_fields = array('smtp_host', 'smtp_username', 'smtp_password', 'from_email');
        foreach ($required_fields as $field) {
            if (empty($config[$field])) {
                return array('success' => false, 'message' => 'Missing required field: ' . $field);
            }
        }
        
        // Test SMTP connection
        try {
            $phpmailer = new PHPMailer(true);
            $phpmailer->isSMTP();
            $phpmailer->Host = $config['smtp_host'];
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $config['smtp_username'];
            $phpmailer->Password = $config['smtp_password'];
            $phpmailer->Port = isset($config['smtp_port']) ? $config['smtp_port'] : 587;
            
            $encryption = isset($config['smtp_encryption']) ? $config['smtp_encryption'] : 'tls';
            if ($encryption === 'tls') {
                $phpmailer->SMTPSecure = 'tls';
            } elseif ($encryption === 'ssl') {
                $phpmailer->SMTPSecure = 'ssl';
            }
            
            $phpmailer->SMTPDebug = 0; // Disable debug output
            $phpmailer->Timeout = 10; // 10 second timeout
            
            $connected = $phpmailer->smtpConnect();
            $phpmailer->smtpClose();
            
            if ($connected) {
                return array('success' => true, 'message' => 'SMTP connection successful');
            } else {
                return array('success' => false, 'message' => 'SMTP connection failed');
            }
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'SMTP error: ' . $e->getMessage());
        }
    }
}

// Initialize the SES SMTP integration
new FlexPress_SES_SMTP();
