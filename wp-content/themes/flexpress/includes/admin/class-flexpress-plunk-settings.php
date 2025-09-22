<?php
/**
 * FlexPress Plunk Email Marketing Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Plunk Email Marketing Settings Class
 */
class FlexPress_Plunk_Settings {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_plunk_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Register settings
     */
    public function register_plunk_settings() {
        register_setting('flexpress_plunk_settings', 'flexpress_plunk_settings', array(
            'sanitize_callback' => array($this, 'sanitize_plunk_settings')
        ));

        // Plunk Configuration Section
        add_settings_section(
            'flexpress_plunk_config_section',
            __('Plunk Configuration', 'flexpress'),
            array($this, 'render_plunk_config_section'),
            'flexpress_plunk_settings'
        );

        // Public API Key
        add_settings_field(
            'public_api_key',
            __('Public API Key', 'flexpress'),
            array($this, 'render_public_api_key_field'),
            'flexpress_plunk_settings',
            'flexpress_plunk_config_section'
        );

        // Secret API Key
        add_settings_field(
            'secret_api_key',
            __('Secret API Key', 'flexpress'),
            array($this, 'render_secret_api_key_field'),
            'flexpress_plunk_settings',
            'flexpress_plunk_config_section'
        );

        // Install URL
        add_settings_field(
            'install_url',
            __('Install URL', 'flexpress'),
            array($this, 'render_install_url_field'),
            'flexpress_plunk_settings',
            'flexpress_plunk_config_section'
        );

        // Newsletter Settings Section
        add_settings_section(
            'flexpress_plunk_newsletter_section',
            __('Newsletter Settings', 'flexpress'),
            array($this, 'render_plunk_newsletter_section'),
            'flexpress_plunk_settings'
        );

        // Auto Subscribe New Users
        add_settings_field(
            'auto_subscribe_users',
            __('Auto Subscribe New Users', 'flexpress'),
            array($this, 'render_auto_subscribe_users_field'),
            'flexpress_plunk_settings',
            'flexpress_plunk_newsletter_section'
        );

        // Newsletter Modal Settings
        add_settings_field(
            'enable_newsletter_modal',
            __('Enable Newsletter Modal', 'flexpress'),
            array($this, 'render_enable_newsletter_modal_field'),
            'flexpress_plunk_settings',
            'flexpress_plunk_newsletter_section'
        );

        // Modal Delay
        add_settings_field(
            'modal_delay',
            __('Modal Delay (seconds)', 'flexpress'),
            array($this, 'render_modal_delay_field'),
            'flexpress_plunk_settings',
            'flexpress_plunk_newsletter_section'
        );

        // Test Connection Section
        add_settings_section(
            'flexpress_plunk_test_section',
            __('Test Connection', 'flexpress'),
            array($this, 'render_plunk_test_section'),
            'flexpress_plunk_settings'
        );

        add_settings_field(
            'test_connection',
            __('Test Plunk Connection', 'flexpress'),
            array($this, 'render_test_connection_field'),
            'flexpress_plunk_settings',
            'flexpress_plunk_test_section'
        );

        add_settings_field(
            'sync_users',
            __('Sync Users', 'flexpress'),
            array($this, 'render_sync_users_field'),
            'flexpress_plunk_settings',
            'flexpress_plunk_test_section'
        );
    }

    /**
     * Enqueue admin scripts
     */
	public function enqueue_admin_scripts($hook) {
		if ($hook !== 'flexpress-settings_page_flexpress-plunk-settings') {
            return;
        }

        wp_enqueue_script('flexpress-plunk-admin', get_template_directory_uri() . '/assets/js/plunk-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('flexpress-plunk-admin', 'flexpressPlunkAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flexpress_plunk_test')
        ));
    }

    /**
     * Sanitize Plunk settings
     */
    public function sanitize_plunk_settings($input) {
        $sanitized = array();

        // Sanitize public API key
        if (isset($input['public_api_key'])) {
            $sanitized['public_api_key'] = sanitize_text_field($input['public_api_key']);
        }

        // Sanitize secret API key
        if (isset($input['secret_api_key'])) {
            $sanitized['secret_api_key'] = sanitize_text_field($input['secret_api_key']);
        }

        // Sanitize install URL
        if (isset($input['install_url'])) {
            $sanitized['install_url'] = esc_url_raw($input['install_url']);
        }

        // Sanitize newsletter settings
        $sanitized['auto_subscribe_users'] = isset($input['auto_subscribe_users']) ? 1 : 0;
        $sanitized['enable_newsletter_modal'] = isset($input['enable_newsletter_modal']) ? 1 : 0;
        
        // Sanitize modal delay
        if (isset($input['modal_delay'])) {
            $sanitized['modal_delay'] = absint($input['modal_delay']);
            if ($sanitized['modal_delay'] < 1) {
                $sanitized['modal_delay'] = 5; // Default 5 seconds
            }
        } else {
            $sanitized['modal_delay'] = 5;
        }

        return $sanitized;
    }

    /**
     * Render the Plunk settings page
     */
    public function render_plunk_settings_page() {
        ?>
        <div class="wrap">
            <h1>📧 Plunk Email Marketing</h1>
            
            <div class="card" style="max-width: 800px; margin-bottom: 20px;">
                <h2>🚀 Automated Email Marketing</h2>
                <p>Plunk provides powerful email marketing automation with user segmentation, subscription management, and comprehensive tracking.</p>
                
                <div style="background: #f0f0f0; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <h3>📋 Key Features:</h3>
                    <ul style="margin-left: 20px;">
                        <li><strong>📧 Automated User Registration</strong> - New users automatically added to Plunk</li>
                        <li><strong>📱 Newsletter Subscription Management</strong> - Frontend and backend subscription controls</li>
                        <li><strong>🛡️ Security Integration</strong> - Cloudflare Turnstile and honeypot protection</li>
                        <li><strong>👥 User Segmentation</strong> - Automatic tagging based on user behavior</li>
                        <li><strong>📊 Event Tracking</strong> - Comprehensive activity tracking</li>
                        <li><strong>⚙️ Admin Management</strong> - WordPress admin interface for contact management</li>
                    </ul>
                </div>
                
                <div style="background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <h3>🔧 How to Set Up Plunk:</h3>
                    <ol style="margin-left: 20px;">
                        <li><strong>Create Plunk Account</strong> → Sign up at <a href="https://plunk.com" target="_blank">plunk.com</a></li>
                        <li><strong>Get API Credentials</strong> → Copy your Public API Key (pk_...), Secret API Key (sk_...), and Install URL</li>
                        <li><strong>Configure Settings</strong> → Enter your credentials in the form below</li>
                        <li><strong>Enable Features</strong> → Choose which features to activate</li>
                        <li><strong>Test Connection</strong> → Verify your setup is working</li>
                    </ol>
                </div>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_plunk_settings');
                do_settings_sections('flexpress_plunk_settings');
                submit_button('Save Plunk Settings');
                ?>
            </form>
            
            <?php $this->render_plunk_preview(); ?>
        </div>
        
        <?php
    }

    /**
     * Render Plunk preview
     */
    private function render_plunk_preview() {
        ?>
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>👀 Newsletter Modal Preview</h2>
            <p>Here's what the newsletter signup modal will look like:</p>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0; border: 1px solid #dee2e6;">
                <div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h3 style="color: #e91e63; margin-bottom: 10px;">Never Miss an Episode!</h3>
                        <p style="color: #666; margin-bottom: 20px;">Subscribe to our newsletter and be the first to know when new content drops!</p>
                    </div>
                    
                    <form style="max-width: 300px; margin: 0 auto;">
                        <div style="margin-bottom: 15px;">
                            <input type="email" placeholder="Enter your email" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        
                        <div style="margin-bottom: 15px; display: flex; justify-content: center;">
                            <div style="background: #f0f0f0; padding: 10px; border-radius: 4px; border: 1px dashed #999; font-size: 12px; color: #666;">
                                Turnstile Widget
                            </div>
                        </div>
                        
                        <button type="submit" style="width: 100%; background: #e91e63; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer;">
                            Subscribe Now
                        </button>
                    </form>
                </div>
            </div>
            
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin: 15px 0;">
                <h4 style="margin-top: 0; color: #856404;">💡 Pro Tips:</h4>
                <ul style="margin-bottom: 0; color: #856404;">
                    <li><strong>Automatic Integration</strong> - New users are automatically added to Plunk when they register</li>
                    <li><strong>User Segmentation</strong> - Users are automatically tagged based on their behavior and membership status</li>
                    <li><strong>Event Tracking</strong> - Track user actions like video views, purchases, and engagement</li>
                    <li><strong>Security Protection</strong> - All forms are protected with Turnstile and honeypot</li>
                    <li><strong>Admin Management</strong> - Manage contacts directly from WordPress admin</li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Render Plunk config section description
     */
    public function render_plunk_config_section() {
        echo '<p>Configure your Plunk API credentials. Enter both your Public API Key (pk_...) for frontend event publishing and your Secret API Key (sk_...) for secure backend operations. You can get these from your Plunk dashboard.</p>';
    }

    /**
     * Render public API key field
     */
    public function render_public_api_key_field() {
        $options = get_option('flexpress_plunk_settings', array());
        $public_api_key = $options['public_api_key'] ?? '';
        ?>
        <input type="password" 
               name="flexpress_plunk_settings[public_api_key]" 
               value="<?php echo esc_attr($public_api_key); ?>" 
               class="regular-text" 
               placeholder="pk_..." />
        <p class="description">Enter your Plunk Public API Key (pk_...). This key is used for frontend services and can only publish events.</p>
        <?php
    }

    /**
     * Render secret API key field
     */
    public function render_secret_api_key_field() {
        $options = get_option('flexpress_plunk_settings', array());
        $secret_api_key = $options['secret_api_key'] ?? '';
        ?>
        <input type="password" 
               name="flexpress_plunk_settings[secret_api_key]" 
               value="<?php echo esc_attr($secret_api_key); ?>" 
               class="regular-text" 
               placeholder="sk_..." />
        <p class="description">Enter your Plunk Secret API Key (sk_...). This key gives complete access to your Plunk setup for backend services. Keep it secret.</p>
        <?php
    }

    /**
     * Render install URL field
     */
    public function render_install_url_field() {
        $options = get_option('flexpress_plunk_settings', array());
        $install_url = $options['install_url'] ?? '';
        ?>
        <input type="url" 
               name="flexpress_plunk_settings[install_url]" 
               value="<?php echo esc_attr($install_url); ?>" 
               class="regular-text" 
               placeholder="https://your-install.plunk.com" />
        <p class="description">Enter your Plunk Install URL (e.g., https://your-install.plunk.com).</p>
        <?php
    }

    /**
     * Render Plunk newsletter section description
     */
    public function render_plunk_newsletter_section() {
        echo '<p>Configure newsletter and email marketing settings.</p>';
    }

    /**
     * Render auto subscribe users field
     */
    public function render_auto_subscribe_users_field() {
        $options = get_option('flexpress_plunk_settings', array());
        $auto_subscribe_users = $options['auto_subscribe_users'] ?? 1;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_plunk_settings[auto_subscribe_users]" 
                   value="1" 
                   <?php checked($auto_subscribe_users); ?> />
            Automatically subscribe new users to newsletter when they register
        </label>
        <p class="description">📧 New users will be automatically added to Plunk with appropriate tags.</p>
        <?php
    }

    /**
     * Render enable newsletter modal field
     */
    public function render_enable_newsletter_modal_field() {
        $options = get_option('flexpress_plunk_settings', array());
        $enable_newsletter_modal = $options['enable_newsletter_modal'] ?? 1;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_plunk_settings[enable_newsletter_modal]" 
                   value="1" 
                   <?php checked($enable_newsletter_modal); ?> />
            Show newsletter signup modal to visitors
        </label>
        <p class="description">📱 Display a newsletter signup modal with Turnstile protection.</p>
        <?php
    }

    /**
     * Render modal delay field
     */
    public function render_modal_delay_field() {
        $options = get_option('flexpress_plunk_settings', array());
        $modal_delay = $options['modal_delay'] ?? 5;
        ?>
        <input type="number" 
               name="flexpress_plunk_settings[modal_delay]" 
               value="<?php echo esc_attr($modal_delay); ?>" 
               min="1" 
               max="60" 
               class="small-text" />
        <p class="description">How many seconds to wait before showing the newsletter modal (1-60 seconds).</p>
        <?php
    }

    /**
     * Render Plunk test section description
     */
    public function render_plunk_test_section() {
        echo '<p>Test your Plunk configuration to ensure everything is working correctly.</p>';
    }

    /**
     * Render test connection field
     */
    public function render_test_connection_field() {
        ?>
        <button type="button" id="test-plunk-connection" class="button button-secondary">Test Plunk Connection</button>
        <div id="plunk-test-results" style="margin-top: 10px;"></div>
        <p class="description">Test your Plunk API credentials and connection.</p>
        <?php
    }

    /**
     * Render sync users field
     */
    public function render_sync_users_field() {
        ?>
        <div>
            <label for="sync-limit">Number of users to sync:</label>
            <input type="number" id="sync-limit" value="50" min="1" max="200" class="small-text" style="margin-left: 10px;">
            <button type="button" id="sync-users-btn" class="button button-secondary" style="margin-left: 10px;">Sync Users</button>
        </div>
        <div id="sync-users-results" style="margin-top: 10px;"></div>
        <p class="description">Sync existing WordPress users with Plunk. This will create contacts for users who don't already have Plunk contact IDs.</p>
        <?php
    }
}

// Initialize the Plunk settings page only in admin
if (is_admin()) {
    new FlexPress_Plunk_Settings();
}
