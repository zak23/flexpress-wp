<?php
/**
 * Discord Settings Admin Page
 * 
 * Provides admin interface for configuring Discord webhook notifications.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FlexPress Discord Settings
 */
class FlexPress_Discord_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('flexpress_discord_settings', 'flexpress_discord_settings', array($this, 'sanitize_settings'));
        
        // Discord Configuration Section
        add_settings_section(
            'discord_config_section',
            'Discord Configuration',
            array($this, 'render_config_section'),
            'flexpress_discord_settings'
        );
        
        add_settings_field(
            'webhook_url',
            'Default Discord Webhook URL',
            array($this, 'render_webhook_url_field'),
            'flexpress_discord_settings',
            'discord_config_section'
        );
        
        add_settings_field(
            'webhook_url_financial',
            'Financial Notifications Webhook',
            array($this, 'render_webhook_url_financial_field'),
            'flexpress_discord_settings',
            'discord_config_section'
        );
        
        add_settings_field(
            'webhook_url_contact',
            'Contact Forms Webhook',
            array($this, 'render_webhook_url_contact_field'),
            'flexpress_discord_settings',
            'discord_config_section'
        );
        
        add_settings_field(
            'test_connection',
            'Test Connection',
            array($this, 'render_test_connection_field'),
            'flexpress_discord_settings',
            'discord_config_section'
        );
        
        add_settings_field(
            'test_casting_notification',
            'Test Casting Notification',
            array($this, 'render_test_casting_notification_field'),
            'flexpress_discord_settings',
            'discord_config_section'
        );
        
        // Notification Settings Section
        add_settings_section(
            'discord_notifications_section',
            'Notification Settings',
            array($this, 'render_notifications_section'),
            'flexpress_discord_settings'
        );
        
        add_settings_field(
            'notify_subscriptions',
            'New Subscriptions',
            array($this, 'render_notify_subscriptions_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_rebills',
            'Subscription Rebills',
            array($this, 'render_notify_rebills_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_cancellations',
            'Subscription Cancellations',
            array($this, 'render_notify_cancellations_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_expirations',
            'Subscription Expirations',
            array($this, 'render_notify_expirations_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_ppv',
            'PPV Purchases',
            array($this, 'render_notify_ppv_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_refunds',
            'Refunds & Chargebacks',
            array($this, 'render_notify_refunds_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_extensions',
            'Subscription Extensions',
            array($this, 'render_notify_extensions_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_talent_applications',
            'Talent Applications',
            array($this, 'render_notify_talent_applications_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        // Contact Form 7 notification settings
        add_settings_field(
            'notify_contact_forms',
            'Contact Forms',
            array($this, 'render_notify_contact_forms_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_casting_applications',
            'Casting Applications',
            array($this, 'render_notify_casting_applications_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_support_requests',
            'Support Requests',
            array($this, 'render_notify_support_requests_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_content_removal',
            'Content Removal Requests',
            array($this, 'render_notify_content_removal_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_general_forms',
            'General Forms',
            array($this, 'render_notify_general_forms_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_form_failures',
            'Form Submission Failures',
            array($this, 'render_notify_form_failures_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize webhook URLs
        $webhook_fields = [
            'webhook_url',
            'webhook_url_financial',
            'webhook_url_contact'
        ];
        
        foreach ($webhook_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = esc_url_raw($input[$field]);
            }
        }
        
        if (isset($input['notify_subscriptions'])) {
            $sanitized['notify_subscriptions'] = (bool) $input['notify_subscriptions'];
        }
        
        if (isset($input['notify_rebills'])) {
            $sanitized['notify_rebills'] = (bool) $input['notify_rebills'];
        }
        
        if (isset($input['notify_cancellations'])) {
            $sanitized['notify_cancellations'] = (bool) $input['notify_cancellations'];
        }
        
        if (isset($input['notify_expirations'])) {
            $sanitized['notify_expirations'] = (bool) $input['notify_expirations'];
        }
        
        if (isset($input['notify_ppv'])) {
            $sanitized['notify_ppv'] = (bool) $input['notify_ppv'];
        }
        
        if (isset($input['notify_refunds'])) {
            $sanitized['notify_refunds'] = (bool) $input['notify_refunds'];
        }
        
        if (isset($input['notify_extensions'])) {
            $sanitized['notify_extensions'] = (bool) $input['notify_extensions'];
        }
        
        if (isset($input['notify_talent_applications'])) {
            $sanitized['notify_talent_applications'] = (bool) $input['notify_talent_applications'];
        }
        
        // Contact Form 7 notification settings
        if (isset($input['notify_contact_forms'])) {
            $sanitized['notify_contact_forms'] = (bool) $input['notify_contact_forms'];
        }
        
        if (isset($input['notify_casting_applications'])) {
            $sanitized['notify_casting_applications'] = (bool) $input['notify_casting_applications'];
        }
        
        if (isset($input['notify_support_requests'])) {
            $sanitized['notify_support_requests'] = (bool) $input['notify_support_requests'];
        }
        
        if (isset($input['notify_content_removal'])) {
            $sanitized['notify_content_removal'] = (bool) $input['notify_content_removal'];
        }
        
        if (isset($input['notify_general_forms'])) {
            $sanitized['notify_general_forms'] = (bool) $input['notify_general_forms'];
        }
        
        if (isset($input['notify_form_failures'])) {
            $sanitized['notify_form_failures'] = (bool) $input['notify_form_failures'];
        }
        
        return $sanitized;
    }
    
    /**
     * Render config section description
     */
    public function render_config_section() {
        echo '<p>Configure Discord webhook URLs for different types of notifications. You can use separate channels for different notification types or use the default webhook for all notifications.</p>';
        echo '<p><strong>💡 Pro Tips:</strong></p>';
        echo '<ul>';
        echo '<li><strong>Create separate channels</strong> for different types of notifications (e.g., #financial-alerts, #contact-forms)</li>';
        echo '<li><strong>Use @mentions</strong> in your Discord webhook settings to ping specific team members</li>';
        echo '<li><strong>Set up role-based notifications</strong> so different team members get different types of alerts</li>';
        echo '<li><strong>Test regularly</strong> to ensure notifications are working properly</li>';
        echo '</ul>';
        echo '<p><strong>How to get Discord webhook URLs:</strong></p>';
        echo '<ol>';
        echo '<li>Go to your Discord server settings</li>';
        echo '<li>Navigate to Integrations → Webhooks</li>';
        echo '<li>Click "Create Webhook" for each channel you want to use</li>';
        echo '<li>Copy the webhook URLs and paste them in the appropriate fields below</li>';
        echo '</ol>';
    }
    
    /**
     * Render webhook URL field
     */
    public function render_webhook_url_field() {
        $options = get_option('flexpress_discord_settings', array());
        $webhook_url = $options['webhook_url'] ?? '';
        ?>
        <input type="url" 
               name="flexpress_discord_settings[webhook_url]" 
               value="<?php echo esc_attr($webhook_url); ?>" 
               class="regular-text" 
               placeholder="https://discord.com/api/webhooks/..." />
        <p class="description">Default webhook URL used for all notifications if specific channel webhooks are not configured. This should start with "https://discord.com/api/webhooks/".</p>
        <?php
    }
    
    /**
     * Render financial webhook URL field
     */
    public function render_webhook_url_financial_field() {
        $options = get_option('flexpress_discord_settings', array());
        $webhook_url = $options['webhook_url_financial'] ?? '';
        ?>
        <input type="url" 
               name="flexpress_discord_settings[webhook_url_financial]" 
               value="<?php echo esc_attr($webhook_url); ?>" 
               class="regular-text" 
               placeholder="https://discord.com/api/webhooks/..." />
        <p class="description">Webhook URL for financial notifications (subscriptions, payments, refunds, chargebacks). Leave empty to use default webhook.</p>
        <?php
    }
    
    /**
     * Render contact webhook URL field
     */
    public function render_webhook_url_contact_field() {
        $options = get_option('flexpress_discord_settings', array());
        $webhook_url = $options['webhook_url_contact'] ?? '';
        ?>
        <input type="url" 
               name="flexpress_discord_settings[webhook_url_contact]" 
               value="<?php echo esc_attr($webhook_url); ?>" 
               class="regular-text" 
               placeholder="https://discord.com/api/webhooks/..." />
        <p class="description">Webhook URL for contact form notifications (talent applications, general inquiries). Leave empty to use default webhook.</p>
        <?php
    }
    
    /**
     * Render test connection field
     */
    public function render_test_connection_field() {
        ?>
        <button type="button" 
                onclick="testDiscordConnection()" 
                class="button button-secondary">Test Discord Connection</button>
        <div id="discord-test-results" style="margin-top: 10px;"></div>
        <p class="description">Test your Discord webhook connection by sending a test notification.</p>
        <?php
    }
    
    /**
     * Render test casting notification field
     */
    public function render_test_casting_notification_field() {
        ?>
        <button type="button" 
                onclick="testCastingNotification()" 
                class="button button-secondary">🌟 Test Casting Notification</button>
        <div id="casting-test-results" style="margin-top: 10px;"></div>
        <p class="description">Send a test casting application notification to Discord.</p>
        <?php
    }
    
    /**
     * Render notifications section description
     */
    public function render_notifications_section() {
        echo '<p>Choose which events should trigger Discord notifications. All notifications include rich embeds with detailed information.</p>';
    }
    
    /**
     * Render notify subscriptions field
     */
    public function render_notify_subscriptions_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_subscriptions = $options['notify_subscriptions'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_subscriptions]" 
                   value="1" 
                   <?php checked($notify_subscriptions); ?> />
            Send notifications for new member subscriptions
        </label>
        <p class="description">🎉 Notifications include member name, email, amount, subscription type, and transaction details.</p>
        <?php
    }
    
    /**
     * Render notify rebills field
     */
    public function render_notify_rebills_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_rebills = $options['notify_rebills'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_rebills]" 
                   value="1" 
                   <?php checked($notify_rebills); ?> />
            Send notifications for successful subscription rebills
        </label>
        <p class="description">💰 Notifications include member name, amount, transaction ID, and next charge date.</p>
        <?php
    }
    
    /**
     * Render notify cancellations field
     */
    public function render_notify_cancellations_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_cancellations = $options['notify_cancellations'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_cancellations]" 
                   value="1" 
                   <?php checked($notify_cancellations); ?> />
            Send notifications for subscription cancellations
        </label>
        <p class="description">❌ Notifications include member name, cancellation reason, and access expiration date.</p>
        <?php
    }
    
    /**
     * Render notify expirations field
     */
    public function render_notify_expirations_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_expirations = $options['notify_expirations'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_expirations]" 
                   value="1" 
                   <?php checked($notify_expirations); ?> />
            Send notifications for subscription expirations
        </label>
        <p class="description">⏰ Notifications include member name and subscription type.</p>
        <?php
    }
    
    /**
     * Render notify PPV field
     */
    public function render_notify_ppv_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_ppv = $options['notify_ppv'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_ppv]" 
                   value="1" 
                   <?php checked($notify_ppv); ?> />
            Send notifications for PPV purchases
        </label>
        <p class="description">🎬 Notifications include member name, amount, episode title, and transaction details.</p>
        <?php
    }
    
    /**
     * Render notify refunds field
     */
    public function render_notify_refunds_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_refunds = $options['notify_refunds'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_refunds]" 
                   value="1" 
                   <?php checked($notify_refunds); ?> />
            Send notifications for refunds and chargebacks
        </label>
        <p class="description">⚠️ Notifications include member name, amount, refund type, and transaction details.</p>
        <?php
    }
    
    /**
     * Render notify extensions field
     */
    public function render_notify_extensions_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_extensions = $options['notify_extensions'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_extensions]" 
                   value="1" 
                   <?php checked($notify_extensions); ?> />
            Send notifications for subscription extensions
        </label>
        <p class="description">🔄 Notifications include member name, amount, subscription type, and new expiration/charge dates.</p>
        <?php
    }
    
    /**
     * Render notify talent applications field
     */
    public function render_notify_talent_applications_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_talent_applications = $options['notify_talent_applications'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_talent_applications]" 
                   value="1" 
                   <?php checked($notify_talent_applications); ?> />
            Send notifications for talent applications
        </label>
        <p class="description">🌟 Notifications include applicant name, contact info, experience, and bio.</p>
        <?php
    }
    
    /**
     * Render notify contact forms field
     */
    public function render_notify_contact_forms_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_contact_forms = $options['notify_contact_forms'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_contact_forms]" 
                   value="1" 
                   <?php checked($notify_contact_forms); ?> />
            Send notifications for contact form submissions
        </label>
        <p class="description">📧 Notifications include sender name, email, subject, and message.</p>
        <?php
    }
    
    /**
     * Render notify casting applications field
     */
    public function render_notify_casting_applications_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_casting_applications = $options['notify_casting_applications'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_casting_applications]" 
                   value="1" 
                   <?php checked($notify_casting_applications); ?> />
            Send notifications for casting applications
        </label>
        <p class="description">🌟 Notifications include applicant details, age, experience, and social media.</p>
        <?php
    }
    
    /**
     * Render notify support requests field
     */
    public function render_notify_support_requests_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_support_requests = $options['notify_support_requests'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_support_requests]" 
                   value="1" 
                   <?php checked($notify_support_requests); ?> />
            Send notifications for support requests
        </label>
        <p class="description">🆘 Notifications include issue type, priority, and detailed message.</p>
        <?php
    }
    
    /**
     * Render notify content removal field
     */
    public function render_notify_content_removal_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_content_removal = $options['notify_content_removal'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_content_removal]" 
                   value="1" 
                   <?php checked($notify_content_removal); ?> />
            Send notifications for content removal requests
        </label>
        <p class="description">⚠️ Notifications include content URL, removal reason, and identity verification details.</p>
        <?php
    }
    
    /**
     * Render notify general forms field
     */
    public function render_notify_general_forms_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_general_forms = $options['notify_general_forms'] ?? true;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_general_forms]" 
                   value="1" 
                   <?php checked($notify_general_forms); ?> />
            Send notifications for other form submissions
        </label>
        <p class="description">📝 Notifications for any Contact Form 7 forms not specifically categorized.</p>
        <?php
    }
    
    /**
     * Render notify form failures field
     */
    public function render_notify_form_failures_field() {
        $options = get_option('flexpress_discord_settings', array());
        $notify_form_failures = $options['notify_form_failures'] ?? false;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_discord_settings[notify_form_failures]" 
                   value="1" 
                   <?php checked($notify_form_failures); ?> />
            Send notifications for form submission failures
        </label>
        <p class="description">⚠️ Get alerted when forms fail to send properly (useful for debugging).</p>
        <?php
    }
    
    /**
     * Add submenu page
     */
    public function add_submenu_page() {
        // Add as submenu under FlexPress Settings only (remove standalone top-level menu)
        add_submenu_page(
            'flexpress-settings',
            'Discord Notifications',
            'Discord',
            'manage_options',
            'flexpress-discord-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'flexpress-settings_page_flexpress-discord-settings') {
            return;
        }
        
        wp_enqueue_script('jquery');
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Discord Notifications</h1>
            
            <?php $this->render_status_overview(); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_discord_settings');
                do_settings_sections('flexpress_discord_settings');
                submit_button('Save Discord Settings');
                ?>
            </form>
            
            <?php $this->render_notification_preview(); ?>
        </div>
        
        <script>
        function testDiscordConnection() {
            var resultsDiv = document.getElementById('discord-test-results');
            resultsDiv.innerHTML = '<p>Testing Discord connection...</p>';
            
            jQuery.post(ajaxurl, {
                action: 'test_discord_connection',
                nonce: '<?php echo wp_create_nonce('test_discord_connection'); ?>'
            }, function(response) {
                if (response.success) {
                    resultsDiv.innerHTML = '<p style="color: green;">✓ Discord connection successful! Check your Discord channel for the test notification.</p>';
                } else {
                    resultsDiv.innerHTML = '<p style="color: red;">✗ Discord connection failed: ' + response.data + '</p>';
                }
            });
        }
        
        function testCastingNotification() {
            var resultsDiv = document.getElementById('casting-test-results');
            resultsDiv.innerHTML = '<p>🌟 Sending test casting notification...</p>';
            
            jQuery.post(ajaxurl, {
                action: 'test_casting_discord_notification',
                nonce: '<?php echo wp_create_nonce('test_casting_discord_notification'); ?>'
            }, function(response) {
                if (response.success) {
                    resultsDiv.innerHTML = '<p style="color: green;">✓ Casting notification sent successfully! Check your Discord channel for the test notification.</p>';
                } else {
                    resultsDiv.innerHTML = '<p style="color: red;">✗ Casting notification failed: ' + response.data + '</p>';
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Render status overview
     */
    private function render_status_overview() {
        $options = get_option('flexpress_discord_settings', array());
        
        ?>
        <div class="card" style="max-width: 600px; margin-bottom: 20px;">
            <h2>Discord Configuration Status</h2>
            <table class="form-table">
                <tr>
                    <th>Webhook URL</th>
                    <td>
                        <?php if (!empty($options['webhook_url'])): ?>
                            <span style="color: green;">✓ Configured</span>
                            <br><small><?php echo esc_html(substr($options['webhook_url'], 0, 50) . '...'); ?></small>
                        <?php else: ?>
                            <span style="color: red;">✗ Not configured</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Notifications Enabled</th>
                    <td>
                        <?php
                        $enabled_count = 0;
                        $total_count = 9;
                        
                        if ($options['notify_subscriptions'] ?? true) $enabled_count++;
                        if ($options['notify_rebills'] ?? true) $enabled_count++;
                        if ($options['notify_cancellations'] ?? true) $enabled_count++;
                        if ($options['notify_expirations'] ?? true) $enabled_count++;
                        if ($options['notify_extensions'] ?? true) $enabled_count++;
                        if ($options['notify_ppv'] ?? true) $enabled_count++;
                        if ($options['notify_refunds'] ?? true) $enabled_count++;
                        if ($options['notify_talent_applications'] ?? true) $enabled_count++;
                        if ($options['notify_contact_forms'] ?? true) $enabled_count++;
                        if ($options['notify_casting_applications'] ?? true) $enabled_count++;
                        if ($options['notify_support_requests'] ?? true) $enabled_count++;
                        if ($options['notify_content_removal'] ?? true) $enabled_count++;
                        if ($options['notify_general_forms'] ?? true) $enabled_count++;
                        if ($options['notify_form_failures'] ?? false) $enabled_count++;
                        
                        $status_color = $enabled_count > 0 ? 'green' : 'red';
                        ?>
                        <span style="color: <?php echo $status_color; ?>;">
                            <?php echo $enabled_count; ?> of <?php echo $total_count; ?> notification types enabled
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Connection Status</th>
                    <td>
                        <?php if (!empty($options['webhook_url'])): ?>
                            <span style="color: blue;">Ready to test</span>
                        <?php else: ?>
                            <span style="color: red;">Not configured</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render notification preview
     */
    private function render_notification_preview() {
        ?>
        <div class="card" style="max-width: 600px;">
            <h2>Notification Preview</h2>
            <p>Here's what your Discord notifications will look like:</p>
            
            <div style="background: #2f3136; color: #dcddde; padding: 15px; border-radius: 8px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 10px 0;">
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <span style="font-size: 24px; margin-right: 10px;">🎉</span>
                    <strong style="color: #ffffff;">New Member Signup!</strong>
                </div>
                
                <div style="background: #36393f; padding: 10px; border-radius: 4px; margin: 10px 0;">
                    <div style="display: flex; margin-bottom: 5px;">
                        <div style="width: 100px; color: #b9bbbe;">Member:</div>
                        <div>John Doe</div>
                    </div>
                    <div style="display: flex; margin-bottom: 5px;">
                        <div style="width: 100px; color: #b9bbbe;">Email:</div>
                        <div>john@example.com</div>
                    </div>
                    <div style="display: flex; margin-bottom: 5px;">
                        <div style="width: 100px; color: #b9bbbe;">Amount:</div>
                        <div>USD 29.95</div>
                    </div>
                    <div style="display: flex; margin-bottom: 5px;">
                        <div style="width: 100px; color: #b9bbbe;">Type:</div>
                        <div>Recurring</div>
                    </div>
                </div>
                
                <div style="font-size: 12px; color: #72767d; margin-top: 10px;">
                    <?php echo get_bloginfo('name'); ?> • Flowguard • Just now
                </div>
            </div>
            
            <p><em>All notifications include rich embeds with detailed information, color coding, and timestamps.</em></p>
        </div>
        <?php
    }
}

// Initialize the settings page
new FlexPress_Discord_Settings();

// AJAX handler for testing Discord connection
add_action('wp_ajax_test_discord_connection', 'flexpress_test_discord_connection');

/**
 * Test Discord webhook connection
 */
function flexpress_test_discord_connection() {
    check_ajax_referer('test_discord_connection', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $result = flexpress_discord_test_connection();
    
    if ($result) {
        wp_send_json_success('Discord webhook test successful');
    } else {
        wp_send_json_error('Discord webhook test failed. Check your webhook URL and try again.');
    }
}

/**
 * Test casting Discord notification function
 */
function flexpress_test_casting_discord_notification_function() {
    // Check Discord settings
    $discord_settings = get_option('flexpress_discord_settings', []);
    
    if (empty($discord_settings['webhook_url'])) {
        return false;
    }
    
    // Check casting notifications setting
    $notify_casting = $discord_settings['notify_casting_applications'] ?? true;
    if (!$notify_casting) {
        return false;
    }
    
    // Create test casting application data
    $test_casting_data = [
        'name' => 'Test Applicant',
        'email' => 'test@example.com',
        'phone' => '+61 4XX XXX XXX',
        'age' => '25',
        'experience' => 'I have 2 years of experience in adult entertainment and modeling.',
        'social_media' => 'Instagram: @testmodel, OnlyFans: testmodel',
        'message' => 'I am very interested in joining your cast. I love the high-quality content you produce.'
    ];
    
    // Initialize Discord notifications
    $discord = new FlexPress_Discord_Notifications();
    
    // Create casting application embed
    $embed = $discord->create_general_embed(
        '🌟 Casting Application',
        'New casting form submission received from ' . get_bloginfo('name'),
        0xff6b35, // Orange color for casting
        [
            [
                'name' => 'Name',
                'value' => $test_casting_data['name'],
                'inline' => true
            ],
            [
                'name' => 'Email',
                'value' => $test_casting_data['email'],
                'inline' => true
            ],
            [
                'name' => 'Age',
                'value' => $test_casting_data['age'],
                'inline' => true
            ],
            [
                'name' => 'Phone',
                'value' => $test_casting_data['phone'],
                'inline' => true
            ],
            [
                'name' => 'Experience',
                'value' => $test_casting_data['experience'],
                'inline' => false
            ],
            [
                'name' => 'Social Media',
                'value' => $test_casting_data['social_media'],
                'inline' => false
            ],
            [
                'name' => 'Message',
                'value' => $test_casting_data['message'],
                'inline' => false
            ],
            [
                'name' => 'Submitted',
                'value' => current_time('Y-m-d H:i:s'),
                'inline' => true
            ]
        ]
    );
    
    // Send notification
    $result = $discord->send_notification($embed, '🌟 **New casting application received!**', 'contact');
    
    return $result;
}

// AJAX handler for testing casting Discord notification
add_action('wp_ajax_test_casting_discord_notification', 'flexpress_test_casting_discord_notification');

/**
 * Test casting Discord notification
 */
function flexpress_test_casting_discord_notification() {
    check_ajax_referer('test_casting_discord_notification', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $result = flexpress_test_casting_discord_notification_function();
    
    if ($result) {
        wp_send_json_success('Casting notification test successful');
    } else {
        wp_send_json_error('Casting notification test failed. Check your Discord settings and try again.');
    }
}
