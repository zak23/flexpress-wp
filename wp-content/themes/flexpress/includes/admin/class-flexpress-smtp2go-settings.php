<?php

/**
 * FlexPress SMTP2Go Settings
 * 
 * Admin settings page for SMTP2Go configuration
 * 
 * @package FlexPress
 * @since 1.0.0
 */

class FlexPress_SMTP2Go_Settings
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_test_smtp2go_connection', array($this, 'test_connection'));
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        // Register setting
        register_setting(
            'flexpress_smtp2go_settings',
            'flexpress_smtp2go_settings',
            array($this, 'sanitize_settings')
        );

        // Add settings section
        add_settings_section(
            'flexpress_smtp2go_section',
            __('SMTP2Go Configuration', 'flexpress'),
            array($this, 'render_section_description'),
            'flexpress-smtp2go-settings'
        );

        // Add settings fields
        $this->add_settings_fields();
    }

    /**
     * Add settings fields
     */
    private function add_settings_fields()
    {
        // Enable SMTP2Go
        add_settings_field(
            'enable_smtp2go',
            __('Enable SMTP2Go', 'flexpress'),
            array($this, 'render_enable_field'),
            'flexpress-smtp2go-settings',
            'flexpress_smtp2go_section'
        );

        // SMTP Host
        add_settings_field(
            'smtp_host',
            __('SMTP Host', 'flexpress'),
            array($this, 'render_smtp_host_field'),
            'flexpress-smtp2go-settings',
            'flexpress_smtp2go_section'
        );

        // SMTP Port
        add_settings_field(
            'smtp_port',
            __('SMTP Port', 'flexpress'),
            array($this, 'render_smtp_port_field'),
            'flexpress-smtp2go-settings',
            'flexpress_smtp2go_section'
        );

        // SMTP Encryption
        add_settings_field(
            'smtp_encryption',
            __('Encryption', 'flexpress'),
            array($this, 'render_smtp_encryption_field'),
            'flexpress-smtp2go-settings',
            'flexpress_smtp2go_section'
        );

        // SMTP Username
        add_settings_field(
            'smtp_username',
            __('SMTP Username', 'flexpress'),
            array($this, 'render_smtp_username_field'),
            'flexpress-smtp2go-settings',
            'flexpress_smtp2go_section'
        );

        // SMTP Password
        add_settings_field(
            'smtp_password',
            __('SMTP Password', 'flexpress'),
            array($this, 'render_smtp_password_field'),
            'flexpress-smtp2go-settings',
            'flexpress_smtp2go_section'
        );

        // From Email
        add_settings_field(
            'from_email',
            __('From Email', 'flexpress'),
            array($this, 'render_from_email_field'),
            'flexpress-smtp2go-settings',
            'flexpress_smtp2go_section'
        );

        // From Name
        add_settings_field(
            'from_name',
            __('From Name', 'flexpress'),
            array($this, 'render_from_name_field'),
            'flexpress-smtp2go-settings',
            'flexpress_smtp2go_section'
        );

        // Use for Internal Only
        add_settings_field(
            'use_for_internal_only',
            __('Use for Internal Emails Only', 'flexpress'),
            array($this, 'render_internal_only_field'),
            'flexpress-smtp2go-settings',
            'flexpress_smtp2go_section'
        );

        // Test Email
        add_settings_field(
            'test_email',
            __('Test Email Address', 'flexpress'),
            array($this, 'render_test_email_field'),
            'flexpress-smtp2go-settings',
            'flexpress_smtp2go_section'
        );
    }

    /**
     * Sanitize settings
     * 
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        $sanitized['enable_smtp2go'] = isset($input['enable_smtp2go']) ? 1 : 0;
        $sanitized['smtp_host'] = sanitize_text_field($input['smtp_host'] ?? '');
        $sanitized['smtp_port'] = intval($input['smtp_port'] ?? 587);
        $sanitized['smtp_encryption'] = sanitize_text_field($input['smtp_encryption'] ?? 'tls');
        $sanitized['smtp_username'] = sanitize_text_field($input['smtp_username'] ?? '');
        $sanitized['smtp_password'] = sanitize_text_field($input['smtp_password'] ?? '');
        $sanitized['from_email'] = sanitize_email($input['from_email'] ?? '');
        $sanitized['from_name'] = sanitize_text_field($input['from_name'] ?? '');
        $sanitized['use_for_internal_only'] = isset($input['use_for_internal_only']) ? 1 : 0;
        $sanitized['test_email'] = sanitize_email($input['test_email'] ?? '');

        return $sanitized;
    }

    /**
     * Render section description
     */
    public function render_section_description()
    {
        echo '<p>' . __('Configure SMTP2Go settings for reliable email delivery. SMTP2Go is perfect for internal emails and handles domain authentication automatically.', 'flexpress') . '</p>';
    }

    /**
     * Render enable field
     */
    public function render_enable_field()
    {
        $options = get_option('flexpress_smtp2go_settings', array());
        $value = isset($options['enable_smtp2go']) ? $options['enable_smtp2go'] : 0;

        echo '<input type="checkbox" name="flexpress_smtp2go_settings[enable_smtp2go]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Enable SMTP2Go for email delivery', 'flexpress') . '</p>';
    }

    /**
     * Render SMTP host field
     */
    public function render_smtp_host_field()
    {
        $options = get_option('flexpress_smtp2go_settings', array());
        $value = $options['smtp_host'] ?? 'mail.smtp2go.com';

        echo '<input type="text" name="flexpress_smtp2go_settings[smtp_host]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('SMTP2Go server hostname', 'flexpress') . '</p>';
    }

    /**
     * Render SMTP port field
     */
    public function render_smtp_port_field()
    {
        $options = get_option('flexpress_smtp2go_settings', array());
        $value = $options['smtp_port'] ?? 587;

        echo '<input type="number" name="flexpress_smtp2go_settings[smtp_port]" value="' . esc_attr($value) . '" class="small-text" min="1" max="65535" />';
        echo '<p class="description">' . __('SMTP port (587 for TLS, 465 for SSL)', 'flexpress') . '</p>';
    }

    /**
     * Render SMTP encryption field
     */
    public function render_smtp_encryption_field()
    {
        $options = get_option('flexpress_smtp2go_settings', array());
        $value = $options['smtp_encryption'] ?? 'tls';

        echo '<select name="flexpress_smtp2go_settings[smtp_encryption]">';
        echo '<option value="tls" ' . selected('tls', $value, false) . '>TLS</option>';
        echo '<option value="ssl" ' . selected('ssl', $value, false) . '>SSL</option>';
        echo '<option value="" ' . selected('', $value, false) . '>None</option>';
        echo '</select>';
        echo '<p class="description">' . __('Encryption method', 'flexpress') . '</p>';
    }

    /**
     * Render SMTP username field
     */
    public function render_smtp_username_field()
    {
        $options = get_option('flexpress_smtp2go_settings', array());
        $value = $options['smtp_username'] ?? '';

        echo '<input type="text" name="flexpress_smtp2go_settings[smtp_username]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Your SMTP2Go username', 'flexpress') . '</p>';
    }

    /**
     * Render SMTP password field
     */
    public function render_smtp_password_field()
    {
        $options = get_option('flexpress_smtp2go_settings', array());
        $value = $options['smtp_password'] ?? '';

        echo '<input type="password" name="flexpress_smtp2go_settings[smtp_password]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Your SMTP2Go password', 'flexpress') . '</p>';
    }

    /**
     * Render from email field
     */
    public function render_from_email_field()
    {
        $options = get_option('flexpress_smtp2go_settings', array());
        $value = $options['from_email'] ?? '';

        echo '<input type="email" name="flexpress_smtp2go_settings[from_email]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Email address to send from', 'flexpress') . '</p>';
    }

    /**
     * Render from name field
     */
    public function render_from_name_field()
    {
        $options = get_option('flexpress_smtp2go_settings', array());
        // Default to site title if not set, but show actual saved value in the input
        $default_name = get_bloginfo('name') ?: 'Site Name';
        $value = !empty($options['from_name']) ? $options['from_name'] : '';

        echo '<input type="text" name="flexpress_smtp2go_settings[from_name]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . sprintf(__('Name to send from (defaults to "%s" if left empty)', 'flexpress'), esc_html($default_name)) . '</p>';
    }

    /**
     * Render internal only field
     */
    public function render_internal_only_field()
    {
        $options = get_option('flexpress_smtp2go_settings', array());
        $value = isset($options['use_for_internal_only']) ? $options['use_for_internal_only'] : 1;

        echo '<input type="checkbox" name="flexpress_smtp2go_settings[use_for_internal_only]" value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">' . __('Only use SMTP2Go for internal emails (same domain as sender)', 'flexpress') . '</p>';
    }

    /**
     * Render test email field
     */
    public function render_test_email_field()
    {
        $options = get_option('flexpress_smtp2go_settings', array());
        $value = $options['test_email'] ?? '';

        echo '<input type="email" name="flexpress_smtp2go_settings[test_email]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Email address to send test emails to', 'flexpress') . '</p>';
    }

    /**
     * Render SMTP2Go settings page
     */
    public function render_smtp2go_settings_page()
    {
?>
        <div class="wrap">
            <h1><?php _e('SMTP2Go Settings', 'flexpress'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_smtp2go_settings');
                do_settings_sections('flexpress-smtp2go-settings');
                submit_button();
                ?>
            </form>

            <div class="card">
                <h2><?php _e('Test SMTP2Go Connection', 'flexpress'); ?></h2>
                <p><?php _e('Send a test email to verify your SMTP2Go configuration.', 'flexpress'); ?></p>
                <button type="button" id="test-smtp2go" class="button"><?php _e('Send Test Email', 'flexpress'); ?></button>
                <div id="smtp2go-test-result"></div>
            </div>

            <div class="card">
                <h2><?php _e('SMTP2Go Setup Guide', 'flexpress'); ?></h2>
                <ol>
                    <li><?php _e('Sign up for a free SMTP2Go account at <a href="https://smtp2go.com" target="_blank">smtp2go.com</a>', 'flexpress'); ?></li>
                    <li><?php _e('Verify your domain in SMTP2Go dashboard', 'flexpress'); ?></li>
                    <li><?php _e('Get your SMTP credentials from the dashboard', 'flexpress'); ?></li>
                    <li><?php _e('Enter your credentials above and test the connection', 'flexpress'); ?></li>
                </ol>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#test-smtp2go').click(function() {
                    var button = $(this);
                    var result = $('#smtp2go-test-result');

                    button.prop('disabled', true).text('Testing...');
                    result.html('');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'test_smtp2go_connection',
                            nonce: '<?php echo wp_create_nonce('test_smtp2go'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                result.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                            } else {
                                result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                            }
                        },
                        error: function() {
                            result.html('<div class="notice notice-error"><p>Test failed. Please check your configuration.</p></div>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('Send Test Email');
                        }
                    });
                });
            });
        </script>
<?php
    }

    /**
     * Test SMTP2Go connection via AJAX
     */
    public function test_connection()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'test_smtp2go')) {
            wp_die('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $options = get_option('flexpress_smtp2go_settings', array());

        if (!isset($options['enable_smtp2go']) || !$options['enable_smtp2go']) {
            wp_send_json_error('SMTP2Go is not enabled');
            return;
        }

        $required_fields = array('smtp_host', 'smtp_username', 'smtp_password', 'from_email');
        foreach ($required_fields as $field) {
            if (empty($options[$field])) {
                wp_send_json_error('Missing required field: ' . $field);
                return;
            }
        }

        // Test SMTP connection by sending a test email
        try {
            // Send test email if test email address is configured
            if (!empty($options['test_email'])) {
                $test_result = $this->send_test_email($options);
                if ($test_result['success']) {
                    wp_send_json_success('SMTP2Go test email sent successfully to ' . $options['test_email']);
                } else {
                    wp_send_json_error('Test email failed: ' . $test_result['message']);
                }
            } else {
                // If no test email configured, just validate the settings
                wp_send_json_success('SMTP2Go settings validated successfully');
            }
        } catch (Exception $e) {
            wp_send_json_error('SMTP error: ' . $e->getMessage());
        }
    }

    /**
     * Send test email
     * 
     * @param array $options
     * @return array
     */
    private function send_test_email($options)
    {
        try {
            $to = $options['test_email'];
            $subject = 'SMTP2Go Test Email - ' . date('Y-m-d H:i:s');
            $message = 'This is a test email from FlexPress SMTP2Go integration.';

            // Default to site title if from_name is not set
            $from_name = !empty($options['from_name']) ? $options['from_name'] : (get_bloginfo('name') ?: '');

            $headers = array(
                'From: ' . $from_name . ' <' . $options['from_email'] . '>'
            );

            $result = wp_mail($to, $subject, $message, $headers);

            if ($result) {
                return array('success' => true, 'message' => 'Test email sent successfully');
            } else {
                return array('success' => false, 'message' => 'Failed to send test email');
            }
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'Test email error: ' . $e->getMessage());
        }
    }
}
