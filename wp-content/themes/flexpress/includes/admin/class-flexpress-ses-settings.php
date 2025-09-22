<?php
/**
 * FlexPress Amazon SES Settings
 * 
 * Handles Amazon SES configuration and email delivery settings
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FlexPress_SES_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_ses_settings_page'));
        add_action('admin_init', array($this, 'register_ses_settings'));
        add_action('wp_ajax_flexpress_test_ses_email', array($this, 'test_ses_email'));
        add_action('wp_ajax_flexpress_get_ses_stats', array($this, 'get_ses_stats'));
    }
    
    /**
     * Add SES settings page to FlexPress menu
     */
    public function add_ses_settings_page() {
        add_submenu_page(
            'flexpress-settings',
            __('Amazon SES Settings', 'flexpress'),
            __('Amazon SES', 'flexpress'),
            'manage_options',
            'flexpress-ses-settings',
            array($this, 'render_ses_settings_page')
        );
    }
    
    /**
     * Register SES settings
     */
    public function register_ses_settings() {
        register_setting('flexpress_ses_settings', 'flexpress_ses_settings', array(
            'sanitize_callback' => array($this, 'sanitize_ses_settings')
        ));
        
        // SES Configuration Section
        add_settings_section(
            'flexpress_ses_config_section',
            __('Amazon SES Configuration', 'flexpress'),
            array($this, 'render_ses_config_section'),
            'flexpress_ses_settings'
        );
        
        // Enable SES
        add_settings_field(
            'enable_ses',
            __('Enable Amazon SES', 'flexpress'),
            array($this, 'render_enable_ses_field'),
            'flexpress_ses_settings',
            'flexpress_ses_config_section'
        );
        
        // AWS Region
        add_settings_field(
            'aws_region',
            __('AWS Region', 'flexpress'),
            array($this, 'render_aws_region_field'),
            'flexpress_ses_settings',
            'flexpress_ses_config_section'
        );
        
        // SMTP Host
        add_settings_field(
            'smtp_host',
            __('SMTP Host', 'flexpress'),
            array($this, 'render_smtp_host_field'),
            'flexpress_ses_settings',
            'flexpress_ses_config_section'
        );
        
        // SMTP Port
        add_settings_field(
            'smtp_port',
            __('SMTP Port', 'flexpress'),
            array($this, 'render_smtp_port_field'),
            'flexpress_ses_settings',
            'flexpress_ses_config_section'
        );
        
        // SMTP Encryption
        add_settings_field(
            'smtp_encryption',
            __('SMTP Encryption', 'flexpress'),
            array($this, 'render_smtp_encryption_field'),
            'flexpress_ses_settings',
            'flexpress_ses_config_section'
        );
        
        // SMTP Username
        add_settings_field(
            'smtp_username',
            __('SMTP Username', 'flexpress'),
            array($this, 'render_smtp_username_field'),
            'flexpress_ses_settings',
            'flexpress_ses_config_section'
        );
        
        // SMTP Password
        add_settings_field(
            'smtp_password',
            __('SMTP Password', 'flexpress'),
            array($this, 'render_smtp_password_field'),
            'flexpress_ses_settings',
            'flexpress_ses_config_section'
        );
        
        // From Email
        add_settings_field(
            'from_email',
            __('From Email', 'flexpress'),
            array($this, 'render_from_email_field'),
            'flexpress_ses_settings',
            'flexpress_ses_config_section'
        );
        
        // From Name
        add_settings_field(
            'from_name',
            __('From Name', 'flexpress'),
            array($this, 'render_from_name_field'),
            'flexpress_ses_settings',
            'flexpress_ses_config_section'
        );
        
        // Use Environment Variables
        add_settings_field(
            'use_env_vars',
            __('Use Environment Variables', 'flexpress'),
            array($this, 'render_use_env_vars_field'),
            'flexpress_ses_settings',
            'flexpress_ses_config_section'
        );
        
        // Email Testing Section
        add_settings_section(
            'flexpress_ses_testing_section',
            __('Email Testing', 'flexpress'),
            array($this, 'render_ses_testing_section'),
            'flexpress_ses_settings'
        );
        
        // Test Email
        add_settings_field(
            'test_email',
            __('Test Email Address', 'flexpress'),
            array($this, 'render_test_email_field'),
            'flexpress_ses_settings',
            'flexpress_ses_testing_section'
        );
    }
    
    /**
     * Sanitize SES settings
     */
    public function sanitize_ses_settings($input) {
        $sanitized = array();
        
        $sanitized['enable_ses'] = isset($input['enable_ses']) ? (bool) $input['enable_ses'] : false;
        $sanitized['aws_region'] = sanitize_text_field($input['aws_region'] ?? 'us-east-1');
        $sanitized['smtp_host'] = sanitize_text_field($input['smtp_host'] ?? '');
        $sanitized['smtp_port'] = absint($input['smtp_port'] ?? 587);
        $sanitized['smtp_encryption'] = sanitize_text_field($input['smtp_encryption'] ?? 'tls');
        $sanitized['smtp_username'] = sanitize_text_field($input['smtp_username'] ?? '');
        $sanitized['smtp_password'] = sanitize_text_field($input['smtp_password'] ?? '');
        $sanitized['from_email'] = sanitize_email($input['from_email'] ?? '');
        $sanitized['from_name'] = sanitize_text_field($input['from_name'] ?? get_bloginfo('name'));
        $sanitized['test_email'] = sanitize_email($input['test_email'] ?? '');
        $sanitized['use_env_vars'] = isset($input['use_env_vars']) ? (bool) $input['use_env_vars'] : false;
        
        return $sanitized;
    }
    
    /**
     * Render SES settings page
     */
    public function render_ses_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="flexpress-admin-header">
                <h2><?php _e('Amazon SES Email Configuration', 'flexpress'); ?></h2>
                <p><?php _e('Configure Amazon SES for reliable email delivery from your WordPress site.', 'flexpress'); ?></p>
            </div>
            
            <div class="flexpress-admin-content">
                <div class="flexpress-admin-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('flexpress_ses_settings');
                        do_settings_sections('flexpress_ses_settings');
                        submit_button(__('Save SES Settings', 'flexpress'));
                        ?>
                    </form>
                </div>
                
                <div class="flexpress-admin-sidebar">
                    <div class="flexpress-admin-box">
                        <h3><?php _e('Quick Actions', 'flexpress'); ?></h3>
                        <p>
                            <button type="button" id="test-ses-email" class="button button-secondary">
                                <?php _e('Send Test Email', 'flexpress'); ?>
                            </button>
                        </p>
                        <p>
                            <button type="button" id="get-ses-stats" class="button button-secondary">
                                <?php _e('Check SES Stats', 'flexpress'); ?>
                            </button>
                        </p>
                    </div>
                    
                    <div class="flexpress-admin-box">
                        <h3><?php _e('Setup Guide', 'flexpress'); ?></h3>
                        <ol>
                            <li><?php _e('Create AWS account and activate SES', 'flexpress'); ?></li>
                            <li><?php _e('Verify your domain in SES console', 'flexpress'); ?></li>
                            <li><?php _e('Create SMTP credentials', 'flexpress'); ?></li>
                            <li><?php _e('Configure settings below', 'flexpress'); ?></li>
                            <li><?php _e('Test email delivery', 'flexpress'); ?></li>
                        </ol>
                    </div>
                    
                    <div class="flexpress-admin-box">
                        <h3><?php _e('Status', 'flexpress'); ?></h3>
                        <div id="ses-status">
                            <?php $this->render_ses_status(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-ses-email').on('click', function() {
                var testEmail = $('input[name="flexpress_ses_settings[test_email]"]').val();
                if (!testEmail) {
                    alert('<?php _e('Please enter a test email address first.', 'flexpress'); ?>');
                    return;
                }
                
                $(this).prop('disabled', true).text('<?php _e('Sending...', 'flexpress'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'flexpress_test_ses_email',
                        email: testEmail,
                        nonce: '<?php echo wp_create_nonce('flexpress_ses_test'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Test email sent successfully!', 'flexpress'); ?>');
                        } else {
                            alert('<?php _e('Error: ', 'flexpress'); ?>' + response.data);
                        }
                    },
                    error: function() {
                        alert('<?php _e('Failed to send test email.', 'flexpress'); ?>');
                    },
                    complete: function() {
                        $('#test-ses-email').prop('disabled', false).text('<?php _e('Send Test Email', 'flexpress'); ?>');
                    }
                });
            });
            
            $('#get-ses-stats').on('click', function() {
                $(this).prop('disabled', true).text('<?php _e('Loading...', 'flexpress'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'flexpress_get_ses_stats',
                        nonce: '<?php echo wp_create_nonce('flexpress_ses_stats'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#ses-status').html(response.data);
                        } else {
                            alert('<?php _e('Error: ', 'flexpress'); ?>' + response.data);
                        }
                    },
                    error: function() {
                        alert('<?php _e('Failed to get SES stats.', 'flexpress'); ?>');
                    },
                    complete: function() {
                        $('#get-ses-stats').prop('disabled', false).text('<?php _e('Check SES Stats', 'flexpress'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render SES configuration section
     */
    public function render_ses_config_section() {
        echo '<p>' . esc_html__('Configure your Amazon SES SMTP settings for reliable email delivery.', 'flexpress') . '</p>';
    }
    
    /**
     * Render enable SES field
     */
    public function render_enable_ses_field() {
        $options = get_option('flexpress_ses_settings', array());
        $value = isset($options['enable_ses']) ? $options['enable_ses'] : false;
        ?>
        <input type="checkbox" 
               name="flexpress_ses_settings[enable_ses]" 
               value="1" 
               <?php checked($value, true); ?>>
        <p class="description"><?php esc_html_e('Enable Amazon SES for email delivery.', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render AWS region field
     */
    public function render_aws_region_field() {
        $options = get_option('flexpress_ses_settings', array());
        $value = isset($options['aws_region']) ? $options['aws_region'] : 'us-east-1';
        $regions = array(
            'us-east-1' => 'US East (N. Virginia)',
            'us-west-2' => 'US West (Oregon)',
            'eu-west-1' => 'Europe (Ireland)',
            'ap-southeast-1' => 'Asia Pacific (Singapore)',
            'ap-southeast-2' => 'Asia Pacific (Sydney)'
        );
        ?>
        <select name="flexpress_ses_settings[aws_region]">
            <?php foreach ($regions as $region => $name): ?>
                <option value="<?php echo esc_attr($region); ?>" <?php selected($value, $region); ?>>
                    <?php echo esc_html($name . ' (' . $region . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Select your AWS region for SES.', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render SMTP host field
     */
    public function render_smtp_host_field() {
        $options = get_option('flexpress_ses_settings', array());
        $region = isset($options['aws_region']) ? $options['aws_region'] : 'us-east-1';
        $value = isset($options['smtp_host']) ? $options['smtp_host'] : 'email-smtp.' . $region . '.amazonaws.com';
        ?>
        <input type="text" 
               name="flexpress_ses_settings[smtp_host]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text">
        <p class="description"><?php esc_html_e('SMTP host for your AWS region.', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render SMTP port field
     */
    public function render_smtp_port_field() {
        $options = get_option('flexpress_ses_settings', array());
        $value = isset($options['smtp_port']) ? $options['smtp_port'] : 587;
        ?>
        <select name="flexpress_ses_settings[smtp_port]">
            <option value="587" <?php selected($value, 587); ?>><?php _e('587 (TLS)', 'flexpress'); ?></option>
            <option value="465" <?php selected($value, 465); ?>><?php _e('465 (SSL)', 'flexpress'); ?></option>
            <option value="25" <?php selected($value, 25); ?>><?php _e('25 (Unencrypted)', 'flexpress'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('SMTP port (587 recommended for TLS).', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render SMTP encryption field
     */
    public function render_smtp_encryption_field() {
        $options = get_option('flexpress_ses_settings', array());
        $value = isset($options['smtp_encryption']) ? $options['smtp_encryption'] : 'tls';
        ?>
        <select name="flexpress_ses_settings[smtp_encryption]">
            <option value="tls" <?php selected($value, 'tls'); ?>><?php _e('TLS', 'flexpress'); ?></option>
            <option value="ssl" <?php selected($value, 'ssl'); ?>><?php _e('SSL', 'flexpress'); ?></option>
            <option value="none" <?php selected($value, 'none'); ?>><?php _e('None', 'flexpress'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('SMTP encryption method.', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render SMTP username field
     */
    public function render_smtp_username_field() {
        $options = get_option('flexpress_ses_settings', array());
        $value = isset($options['smtp_username']) ? $options['smtp_username'] : '';
        ?>
        <input type="text" 
               name="flexpress_ses_settings[smtp_username]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text">
        <p class="description"><?php esc_html_e('SMTP username from AWS SES console.', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render SMTP password field
     */
    public function render_smtp_password_field() {
        $options = get_option('flexpress_ses_settings', array());
        $value = isset($options['smtp_password']) ? $options['smtp_password'] : '';
        ?>
        <input type="password" 
               name="flexpress_ses_settings[smtp_password]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text">
        <p class="description"><?php esc_html_e('SMTP password from AWS SES console.', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render from email field
     */
    public function render_from_email_field() {
        $options = get_option('flexpress_ses_settings', array());
        $site_domain = parse_url(home_url(), PHP_URL_HOST);
        $default_value = 'noreply@' . $site_domain;
        $value = isset($options['from_email']) && !empty($options['from_email']) ? $options['from_email'] : $default_value;
        ?>
        <input type="email" 
               name="flexpress_ses_settings[from_email]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text">
        <p class="description"><?php esc_html_e('Verified email address in SES (must be verified).', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render from name field
     */
    public function render_from_name_field() {
        $options = get_option('flexpress_ses_settings', array());
        $value = isset($options['from_name']) && !empty($options['from_name']) ? $options['from_name'] : get_bloginfo('name');
        ?>
        <input type="text" 
               name="flexpress_ses_settings[from_name]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text">
        <p class="description"><?php esc_html_e('Sender name for outgoing emails.', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render SES testing section
     */
    public function render_ses_testing_section() {
        echo '<p>' . esc_html__('Test your SES configuration by sending a test email.', 'flexpress') . '</p>';
    }
    
    /**
     * Render test email field
     */
    public function render_test_email_field() {
        $options = get_option('flexpress_ses_settings', array());
        $value = isset($options['test_email']) ? $options['test_email'] : '';
        ?>
        <input type="email" 
               name="flexpress_ses_settings[test_email]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text">
        <p class="description"><?php esc_html_e('Email address to send test emails to.', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render use environment variables field
     */
    public function render_use_env_vars_field() {
        $options = get_option('flexpress_ses_settings', array());
        $value = isset($options['use_env_vars']) ? $options['use_env_vars'] : false;
        ?>
        <input type="checkbox" 
               name="flexpress_ses_settings[use_env_vars]" 
               value="1" 
               <?php checked($value, true); ?>>
        <p class="description">
            <?php esc_html_e('Use environment variables for SES configuration instead of storing credentials in database.', 'flexpress'); ?>
            <br>
            <strong><?php esc_html_e('Environment Variables:', 'flexpress'); ?></strong>
            <br>
            <code>SES_AWS_REGION</code>, <code>SES_SMTP_HOST</code>, <code>SES_SMTP_PORT</code>, <code>SES_SMTP_USERNAME</code>, <code>SES_SMTP_PASSWORD</code>, <code>SES_FROM_EMAIL</code>, <code>SES_FROM_NAME</code>
        </p>
        <?php
    }
    
    /**
     * Render SES status
     */
    public function render_ses_status() {
        $options = get_option('flexpress_ses_settings', array());
        $enabled = isset($options['enable_ses']) ? $options['enable_ses'] : false;
        
        if ($enabled) {
            echo '<p style="color: green;">✓ ' . __('SES Enabled', 'flexpress') . '</p>';
            
            $required_fields = array('smtp_host', 'smtp_username', 'smtp_password', 'from_email');
            $missing_fields = array();
            
            foreach ($required_fields as $field) {
                if (empty($options[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (empty($missing_fields)) {
                echo '<p style="color: green;">✓ ' . __('Configuration Complete', 'flexpress') . '</p>';
            } else {
                echo '<p style="color: orange;">⚠ ' . __('Missing Configuration', 'flexpress') . '</p>';
            }
        } else {
            echo '<p style="color: red;">✗ ' . __('SES Disabled', 'flexpress') . '</p>';
        }
    }
    
    /**
     * Test SES email via AJAX
     */
    public function test_ses_email() {
        check_ajax_referer('flexpress_ses_test', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'flexpress'));
        }
        
        $email = sanitize_email($_POST['email']);
        if (empty($email)) {
            wp_send_json_error(__('Invalid email address.', 'flexpress'));
        }
        
        $subject = sprintf(__('[%s] SES Test Email', 'flexpress'), get_bloginfo('name'));
        $message = sprintf(__('This is a test email from %s to verify Amazon SES configuration.', 'flexpress'), get_bloginfo('name'));
        $message .= "\n\n" . __('If you received this email, your SES configuration is working correctly!', 'flexpress');
        
        $sent = wp_mail($email, $subject, $message);
        
        if ($sent) {
            wp_send_json_success(__('Test email sent successfully!', 'flexpress'));
        } else {
            wp_send_json_error(__('Failed to send test email. Check your configuration.', 'flexpress'));
        }
    }
    
    /**
     * Get SES stats via AJAX
     */
    public function get_ses_stats() {
        check_ajax_referer('flexpress_ses_stats', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'flexpress'));
        }
        
        $options = get_option('flexpress_ses_settings', array());
        $enabled = isset($options['enable_ses']) ? $options['enable_ses'] : false;
        
        if (!$enabled) {
            wp_send_json_error(__('SES is not enabled.', 'flexpress'));
        }
        
        // Get recent email logs (if available)
        $recent_emails = get_option('flexpress_ses_email_log', array());
        $total_sent = count($recent_emails);
        $recent_count = count(array_filter($recent_emails, function($email) {
            return strtotime($email['timestamp']) > (time() - 24 * 60 * 60); // Last 24 hours
        }));
        
        $stats_html = '<div class="ses-stats">';
        $stats_html .= '<p><strong>' . __('Total Emails Sent:', 'flexpress') . '</strong> ' . $total_sent . '</p>';
        $stats_html .= '<p><strong>' . __('Last 24 Hours:', 'flexpress') . '</strong> ' . $recent_count . '</p>';
        $stats_html .= '<p><strong>' . __('Status:', 'flexpress') . '</strong> ' . __('Active', 'flexpress') . '</p>';
        $stats_html .= '</div>';
        
        wp_send_json_success($stats_html);
    }
}

// Initialize the SES settings
new FlexPress_SES_Settings();
