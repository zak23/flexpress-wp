<?php
/**
 * FlexPress Google SMTP Settings Page
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Google SMTP Settings Page Class
 */
class FlexPress_Google_SMTP_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_google_smtp_settings'));
    }
    
    /**
     * Register Google SMTP settings
     */
    public function register_google_smtp_settings() {
        register_setting('flexpress_google_smtp_settings', 'flexpress_google_smtp_settings', array(
            'sanitize_callback' => array($this, 'sanitize_google_smtp_settings')
        ));
        
        // Google SMTP Configuration Section
        add_settings_section(
            'flexpress_google_smtp_config_section',
            __('Google SMTP Configuration', 'flexpress'),
            array($this, 'render_google_smtp_config_section'),
            'flexpress_google_smtp_settings'
        );
        
        // Enable Google SMTP
        add_settings_field(
            'enable_google_smtp',
            __('Enable Google SMTP', 'flexpress'),
            array($this, 'render_enable_google_smtp_field'),
            'flexpress_google_smtp_settings',
            'flexpress_google_smtp_config_section'
        );
        
        // SMTP Host
        add_settings_field(
            'smtp_host',
            __('SMTP Host', 'flexpress'),
            array($this, 'render_smtp_host_field'),
            'flexpress_google_smtp_settings',
            'flexpress_google_smtp_config_section'
        );
        
        // SMTP Port
        add_settings_field(
            'smtp_port',
            __('SMTP Port', 'flexpress'),
            array($this, 'render_smtp_port_field'),
            'flexpress_google_smtp_settings',
            'flexpress_google_smtp_config_section'
        );
        
        // SMTP Encryption
        add_settings_field(
            'smtp_encryption',
            __('SMTP Encryption', 'flexpress'),
            array($this, 'render_smtp_encryption_field'),
            'flexpress_google_smtp_settings',
            'flexpress_google_smtp_config_section'
        );
        
        // SMTP Username
        add_settings_field(
            'smtp_username',
            __('SMTP Username (Email)', 'flexpress'),
            array($this, 'render_smtp_username_field'),
            'flexpress_google_smtp_settings',
            'flexpress_google_smtp_config_section'
        );
        
        // SMTP Password
        add_settings_field(
            'smtp_password',
            __('App Password', 'flexpress'),
            array($this, 'render_smtp_password_field'),
            'flexpress_google_smtp_settings',
            'flexpress_google_smtp_config_section'
        );
        
        // From Email
        add_settings_field(
            'from_email',
            __('From Email', 'flexpress'),
            array($this, 'render_from_email_field'),
            'flexpress_google_smtp_settings',
            'flexpress_google_smtp_config_section'
        );
        
        // From Name
        add_settings_field(
            'from_name',
            __('From Name', 'flexpress'),
            array($this, 'render_from_name_field'),
            'flexpress_google_smtp_settings',
            'flexpress_google_smtp_config_section'
        );
        
        // Use for Internal Emails Only
        add_settings_field(
            'use_for_internal_only',
            __('Use for Internal Emails Only', 'flexpress'),
            array($this, 'render_use_for_internal_only_field'),
            'flexpress_google_smtp_settings',
            'flexpress_google_smtp_config_section'
        );
        
        // Email Testing Section
        add_settings_section(
            'flexpress_google_smtp_testing_section',
            __('Email Testing', 'flexpress'),
            array($this, 'render_google_smtp_testing_section'),
            'flexpress_google_smtp_settings'
        );
        
        // Test Email
        add_settings_field(
            'test_email',
            __('Test Email Address', 'flexpress'),
            array($this, 'render_test_email_field'),
            'flexpress_google_smtp_settings',
            'flexpress_google_smtp_testing_section'
        );
    }
    
    /**
     * Sanitize Google SMTP settings
     */
    public function sanitize_google_smtp_settings($input) {
        $sanitized = array();
        
        $sanitized['enable_google_smtp'] = isset($input['enable_google_smtp']) ? (bool) $input['enable_google_smtp'] : false;
        $sanitized['smtp_host'] = sanitize_text_field($input['smtp_host'] ?? 'smtp.gmail.com');
        $sanitized['smtp_port'] = absint($input['smtp_port'] ?? 587);
        $sanitized['smtp_encryption'] = sanitize_text_field($input['smtp_encryption'] ?? 'tls');
        $sanitized['smtp_username'] = sanitize_email($input['smtp_username'] ?? '');
        $sanitized['smtp_password'] = sanitize_text_field($input['smtp_password'] ?? '');
        $sanitized['from_email'] = sanitize_email($input['from_email'] ?? '');
        $sanitized['from_name'] = sanitize_text_field($input['from_name'] ?? get_bloginfo('name'));
        $sanitized['test_email'] = sanitize_email($input['test_email'] ?? '');
        $sanitized['use_for_internal_only'] = isset($input['use_for_internal_only']) ? (bool) $input['use_for_internal_only'] : true;
        
        return $sanitized;
    }
    
    /**
     * Render Google SMTP settings page
     */
    public function render_google_smtp_settings_page() {
        ?>
        <div class="wrap">
            <h1>📧 Google SMTP Settings</h1>
            
            <div class="card" style="max-width: 800px; margin-bottom: 20px;">
                <h2>🔧 Google Workspace SMTP Configuration</h2>
                <p>Configure Google SMTP for reliable email delivery, especially for internal emails to your own domain.</p>
                
                <div style="background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <h3>💡 Why Use Google SMTP?</h3>
                    <ul style="margin-left: 20px;">
                        <li><strong>✅ Reliable Internal Delivery</strong> - Perfect for contact forms sent to your own domain</li>
                        <li><strong>🚫 No SES Bounce Issues</strong> - Avoids Amazon SES delivery problems to your own domain</li>
                        <li><strong>🔒 Secure Authentication</strong> - Uses App Passwords for enhanced security</li>
                        <li><strong>📈 High Deliverability</strong> - Google's reputation ensures emails reach inboxes</li>
                        <li><strong>⚡ Fast Setup</strong> - Works with existing Google Workspace accounts</li>
                    </ul>
                </div>
                
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin: 15px 0;">
                    <h4 style="margin-top: 0; color: #856404;">🔐 How to Set Up App Password:</h4>
                    <ol style="margin-bottom: 0; color: #856404;">
                        <li><strong>Enable 2-Factor Authentication</strong> on your Google account</li>
                        <li><strong>Go to Google Account Settings</strong> → Security → 2-Step Verification</li>
                        <li><strong>Click "App passwords"</strong> at the bottom of the page</li>
                        <li><strong>Select "Mail"</strong> and generate a new app password</li>
                        <li><strong>Copy the 16-character password</strong> and paste it in the App Password field below</li>
                    </ol>
                </div>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_google_smtp_settings');
                do_settings_sections('flexpress_google_smtp_settings');
                submit_button('Save Google SMTP Settings');
                ?>
            </form>
            
            <?php $this->render_google_smtp_test_section(); ?>
        </div>
        
        <script>
        function testGoogleSMTPConnection() {
            var resultsDiv = document.getElementById('google-smtp-test-results');
            resultsDiv.innerHTML = '<p>Testing Google SMTP connection...</p>';
            
            jQuery.post(ajaxurl, {
                action: 'test_google_smtp_connection',
                nonce: '<?php echo wp_create_nonce('test_google_smtp_connection'); ?>'
            }, function(response) {
                if (response.success) {
                    resultsDiv.innerHTML = '<p style="color: green;">✓ Google SMTP connection successful! Check your email for the test message.</p>';
                } else {
                    resultsDiv.innerHTML = '<p style="color: red;">✗ Google SMTP connection failed: ' + response.data + '</p>';
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Render Google SMTP test section
     */
    private function render_google_smtp_test_section() {
        ?>
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>🧪 Test Google SMTP Connection</h2>
            <p>Test your Google SMTP configuration by sending a test email.</p>
            
            <button type="button" 
                    onclick="testGoogleSMTPConnection()" 
                    class="button button-secondary">Test Google SMTP Connection</button>
            <div id="google-smtp-test-results" style="margin-top: 10px;"></div>
            
            <p class="description">This will send a test email to verify your Google SMTP configuration is working correctly.</p>
        </div>
        <?php
    }
    
    /**
     * Render Google SMTP config section description
     */
    public function render_google_smtp_config_section() {
        echo '<p>Configure Google SMTP settings for reliable email delivery. This is especially useful for contact forms and internal communications.</p>';
        echo '<p><strong>Default Settings:</strong> SMTP Host: smtp.gmail.com, Port: 587, Encryption: TLS</p>';
    }
    
    /**
     * Render enable Google SMTP field
     */
    public function render_enable_google_smtp_field() {
        $options = get_option('flexpress_google_smtp_settings', array());
        $enable_google_smtp = $options['enable_google_smtp'] ?? false;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_google_smtp_settings[enable_google_smtp]" 
                   value="1" 
                   <?php checked($enable_google_smtp); ?> />
            Enable Google SMTP for email delivery
        </label>
        <p class="description">When enabled, WordPress will use Google SMTP instead of the default mail function.</p>
        <?php
    }
    
    /**
     * Render SMTP host field
     */
    public function render_smtp_host_field() {
        $options = get_option('flexpress_google_smtp_settings', array());
        $smtp_host = $options['smtp_host'] ?? 'smtp.gmail.com';
        ?>
        <input type="text" 
               name="flexpress_google_smtp_settings[smtp_host]" 
               value="<?php echo esc_attr($smtp_host); ?>" 
               class="regular-text" 
               placeholder="smtp.gmail.com" />
        <p class="description">Google SMTP server hostname. Default: smtp.gmail.com</p>
        <?php
    }
    
    /**
     * Render SMTP port field
     */
    public function render_smtp_port_field() {
        $options = get_option('flexpress_google_smtp_settings', array());
        $smtp_port = $options['smtp_port'] ?? 587;
        ?>
        <input type="number" 
               name="flexpress_google_smtp_settings[smtp_port]" 
               value="<?php echo esc_attr($smtp_port); ?>" 
               class="small-text" 
               min="1" 
               max="65535" />
        <p class="description">SMTP port number. Default: 587 (TLS) or 465 (SSL)</p>
        <?php
    }
    
    /**
     * Render SMTP encryption field
     */
    public function render_smtp_encryption_field() {
        $options = get_option('flexpress_google_smtp_settings', array());
        $smtp_encryption = $options['smtp_encryption'] ?? 'tls';
        ?>
        <select name="flexpress_google_smtp_settings[smtp_encryption]">
            <option value="tls" <?php selected($smtp_encryption, 'tls'); ?>>TLS (Recommended)</option>
            <option value="ssl" <?php selected($smtp_encryption, 'ssl'); ?>>SSL</option>
            <option value="none" <?php selected($smtp_encryption, 'none'); ?>>None (Not Recommended)</option>
        </select>
        <p class="description">Encryption method. TLS is recommended for port 587, SSL for port 465.</p>
        <?php
    }
    
    /**
     * Render SMTP username field
     */
    public function render_smtp_username_field() {
        $options = get_option('flexpress_google_smtp_settings', array());
        $smtp_username = $options['smtp_username'] ?? '';
        ?>
        <input type="email" 
               name="flexpress_google_smtp_settings[smtp_username]" 
               value="<?php echo esc_attr($smtp_username); ?>" 
               class="regular-text" 
               placeholder="your-email@gmail.com" />
        <p class="description">Your Google Workspace email address (e.g., noreply@<?php echo parse_url(home_url(), PHP_URL_HOST); ?>)</p>
        <?php
    }
    
    /**
     * Render SMTP password field
     */
    public function render_smtp_password_field() {
        $options = get_option('flexpress_google_smtp_settings', array());
        $smtp_password = $options['smtp_password'] ?? '';
        ?>
        <input type="password" 
               name="flexpress_google_smtp_settings[smtp_password]" 
               value="<?php echo esc_attr($smtp_password); ?>" 
               class="regular-text" 
               placeholder="16-character app password" />
        <p class="description">Google App Password (16 characters). <strong>Not your regular password!</strong></p>
        <?php
    }
    
    /**
     * Render from email field
     */
    public function render_from_email_field() {
        $options = get_option('flexpress_google_smtp_settings', array());
        $from_email = $options['from_email'] ?? '';
        ?>
        <input type="email" 
               name="flexpress_google_smtp_settings[from_email]" 
               value="<?php echo esc_attr($from_email); ?>" 
               class="regular-text" 
               placeholder="noreply@<?php echo parse_url(home_url(), PHP_URL_HOST); ?>" />
        <p class="description">Email address that will appear as the sender. Should match your Google Workspace domain.</p>
        <?php
    }
    
    /**
     * Render from name field
     */
    public function render_from_name_field() {
        $options = get_option('flexpress_google_smtp_settings', array());
        $from_name = $options['from_name'] ?? get_bloginfo('name');
        ?>
        <input type="text" 
               name="flexpress_google_smtp_settings[from_name]" 
               value="<?php echo esc_attr($from_name); ?>" 
               class="regular-text" 
               placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>" />
        <p class="description">Display name for the sender (e.g., "<?php echo get_bloginfo('name'); ?>" or "FlexPress Support")</p>
        <?php
    }
    
    /**
     * Render use for internal emails only field
     */
    public function render_use_for_internal_only_field() {
        $options = get_option('flexpress_google_smtp_settings', array());
        $use_for_internal_only = $options['use_for_internal_only'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_google_smtp_settings[use_for_internal_only]" 
                   value="1" 
                   <?php checked($use_for_internal_only); ?> />
            Use Google SMTP only for emails to your own domain
        </label>
        <p class="description">When enabled, Google SMTP will only be used for emails sent to addresses on your domain (e.g., contact@<?php echo parse_url(home_url(), PHP_URL_HOST); ?>). External emails will use the default mail system or Amazon SES.</p>
        <?php
    }
    
    /**
     * Render Google SMTP testing section description
     */
    public function render_google_smtp_testing_section() {
        echo '<p>Test your Google SMTP configuration to ensure emails are being sent successfully.</p>';
    }
    
    /**
     * Render test email field
     */
    public function render_test_email_field() {
        $options = get_option('flexpress_google_smtp_settings', array());
        $test_email = $options['test_email'] ?? '';
        ?>
        <input type="email" 
               name="flexpress_google_smtp_settings[test_email]" 
               value="<?php echo esc_attr($test_email); ?>" 
               class="regular-text" 
               placeholder="test@example.com" />
        <p class="description">Email address to send test messages to. Use your own email for testing.</p>
        <?php
    }
}

// Initialize the Google SMTP settings only in admin
if (is_admin()) {
    new FlexPress_Google_SMTP_Settings();
}
