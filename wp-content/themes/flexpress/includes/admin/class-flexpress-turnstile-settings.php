<?php
/**
 * FlexPress Cloudflare Turnstile Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Cloudflare Turnstile Settings Class
 */
class FlexPress_Turnstile_Settings {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_turnstile_settings_page'));
        add_action('admin_init', array($this, 'register_turnstile_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add the Turnstile settings page to admin menu
     */
    public function add_turnstile_settings_page() {
        add_submenu_page(
            'flexpress-settings',
            __('Cloudflare Turnstile', 'flexpress'),
            __('Turnstile', 'flexpress'),
            'manage_options',
            'flexpress-turnstile-settings',
            array($this, 'render_turnstile_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_turnstile_settings() {
        register_setting('flexpress_turnstile_settings', 'flexpress_turnstile_settings', array(
            'sanitize_callback' => array($this, 'sanitize_turnstile_settings')
        ));

        // Turnstile Configuration Section
        add_settings_section(
            'flexpress_turnstile_config_section',
            __('Turnstile Configuration', 'flexpress'),
            array($this, 'render_turnstile_config_section'),
            'flexpress_turnstile_settings'
        );

        // Site Key
        add_settings_field(
            'site_key',
            __('Site Key', 'flexpress'),
            array($this, 'render_site_key_field'),
            'flexpress_turnstile_settings',
            'flexpress_turnstile_config_section'
        );

        // Secret Key
        add_settings_field(
            'secret_key',
            __('Secret Key', 'flexpress'),
            array($this, 'render_secret_key_field'),
            'flexpress_turnstile_settings',
            'flexpress_turnstile_config_section'
        );

        // Theme
        add_settings_field(
            'theme',
            __('Theme', 'flexpress'),
            array($this, 'render_theme_field'),
            'flexpress_turnstile_settings',
            'flexpress_turnstile_config_section'
        );

        // Size
        add_settings_field(
            'size',
            __('Size', 'flexpress'),
            array($this, 'render_size_field'),
            'flexpress_turnstile_settings',
            'flexpress_turnstile_config_section'
        );

        // Form Protection Section
        add_settings_section(
            'flexpress_turnstile_forms_section',
            __('Form Protection', 'flexpress'),
            array($this, 'render_turnstile_forms_section'),
            'flexpress_turnstile_settings'
        );

        // Contact Forms
        add_settings_field(
            'protect_contact_forms',
            __('Contact Forms', 'flexpress'),
            array($this, 'render_protect_contact_forms_field'),
            'flexpress_turnstile_settings',
            'flexpress_turnstile_forms_section'
        );

        // Comment Forms
        add_settings_field(
            'protect_comment_forms',
            __('Comment Forms', 'flexpress'),
            array($this, 'render_protect_comment_forms_field'),
            'flexpress_turnstile_settings',
            'flexpress_turnstile_forms_section'
        );

        // Registration Forms
        add_settings_field(
            'protect_registration_forms',
            __('Registration Forms', 'flexpress'),
            array($this, 'render_protect_registration_forms_field'),
            'flexpress_turnstile_settings',
            'flexpress_turnstile_forms_section'
        );

        // Login Forms
        add_settings_field(
            'protect_login_forms',
            __('Login Forms', 'flexpress'),
            array($this, 'render_protect_login_forms_field'),
            'flexpress_turnstile_settings',
            'flexpress_turnstile_forms_section'
        );

        // Test Connection Section
        add_settings_section(
            'flexpress_turnstile_test_section',
            __('Test Connection', 'flexpress'),
            array($this, 'render_turnstile_test_section'),
            'flexpress_turnstile_settings'
        );

        add_settings_field(
            'test_connection',
            __('Test Turnstile', 'flexpress'),
            array($this, 'render_test_connection_field'),
            'flexpress_turnstile_settings',
            'flexpress_turnstile_test_section'
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'flexpress_page_flexpress-turnstile-settings') {
            return;
        }

        wp_enqueue_script('flexpress-turnstile-admin', get_template_directory_uri() . '/assets/js/turnstile-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('flexpress-turnstile-admin', 'flexpressTurnstileAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flexpress_turnstile_test')
        ));
    }

    /**
     * Sanitize Turnstile settings
     */
    public function sanitize_turnstile_settings($input) {
        $sanitized = array();

        // Sanitize site key
        if (isset($input['site_key'])) {
            $sanitized['site_key'] = sanitize_text_field($input['site_key']);
        }

        // Sanitize secret key
        if (isset($input['secret_key'])) {
            $sanitized['secret_key'] = sanitize_text_field($input['secret_key']);
        }

        // Sanitize theme
        if (isset($input['theme'])) {
            $sanitized['theme'] = in_array($input['theme'], array('light', 'dark', 'auto')) ? $input['theme'] : 'auto';
        }

        // Sanitize size
        if (isset($input['size'])) {
            $sanitized['size'] = in_array($input['size'], array('normal', 'compact')) ? $input['size'] : 'normal';
        }

        // Sanitize form protection settings
        $sanitized['protect_contact_forms'] = isset($input['protect_contact_forms']) ? 1 : 0;
        $sanitized['protect_comment_forms'] = isset($input['protect_comment_forms']) ? 1 : 0;
        $sanitized['protect_registration_forms'] = isset($input['protect_registration_forms']) ? 1 : 0;
        $sanitized['protect_login_forms'] = isset($input['protect_login_forms']) ? 1 : 0;

        return $sanitized;
    }

    /**
     * Render the Turnstile settings page
     */
    public function render_turnstile_settings_page() {
        ?>
        <div class="wrap">
            <h1>🛡️ Cloudflare Turnstile Protection</h1>
            
            <div class="card" style="max-width: 800px; margin-bottom: 20px;">
                <h2>🔒 Advanced Bot Protection</h2>
                <p>Cloudflare Turnstile provides invisible bot protection for your forms without requiring users to solve CAPTCHAs. It's privacy-focused and GDPR compliant.</p>
                
                <div style="background: #f0f0f0; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <h3>📋 What Turnstile Protects:</h3>
                    <ul style="margin-left: 20px;">
                        <li><strong>📧 Contact Forms</strong> - Prevent spam submissions</li>
                        <li><strong>💬 Comment Forms</strong> - Block automated comments</li>
                        <li><strong>👤 Registration Forms</strong> - Stop fake account creation</li>
                        <li><strong>🔑 Login Forms</strong> - Prevent brute force attacks</li>
                    </ul>
                </div>
                
                <div style="background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <h3>🔧 How to Set Up Turnstile:</h3>
                    <ol style="margin-left: 20px;">
                        <li><strong>Go to Cloudflare Dashboard</strong> → Turnstile → Add Site</li>
                        <li><strong>Enter your domain</strong> and choose widget mode</li>
                        <li><strong>Copy your Site Key</strong> and Secret Key</li>
                        <li><strong>Paste the keys</strong> in the form below</li>
                        <li><strong>Choose which forms</strong> to protect</li>
                        <li><strong>Test the connection</strong> to ensure everything works</li>
                    </ol>
                </div>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_turnstile_settings');
                do_settings_sections('flexpress_turnstile_settings');
                submit_button('Save Turnstile Settings');
                ?>
            </form>
            
            <?php $this->render_turnstile_preview(); ?>
        </div>
        <?php
    }

    /**
     * Render Turnstile preview
     */
    private function render_turnstile_preview() {
        ?>
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>👀 Turnstile Widget Preview</h2>
            <p>Here's what the Turnstile widget will look like on your forms:</p>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0; border: 1px solid #dee2e6;">
                <div style="display: flex; align-items: center; margin-bottom: 15px;">
                    <span style="font-size: 24px; margin-right: 12px;">🛡️</span>
                    <strong style="font-size: 16px;">Cloudflare Turnstile</strong>
                </div>
                
                <div style="background: #ffffff; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef; margin: 15px 0;">
                    <div style="display: flex; align-items: center; justify-content: center; height: 65px; background: #f8f9fa; border-radius: 4px; border: 1px dashed #6c757d;">
                        <span style="color: #6c757d; font-size: 14px;">Turnstile Widget</span>
                    </div>
                </div>
                
                <div style="font-size: 12px; color: #6c757d; margin-top: 15px;">
                    This widget will appear on protected forms to verify human users
                </div>
            </div>
            
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin: 15px 0;">
                <h4 style="margin-top: 0; color: #856404;">💡 Pro Tips:</h4>
                <ul style="margin-bottom: 0; color: #856404;">
                    <li><strong>Invisible Mode</strong> - Most users won't see the widget unless suspicious activity is detected</li>
                    <li><strong>Privacy-First</strong> - No personal data is collected or stored</li>
                    <li><strong>GDPR Compliant</strong> - No cookies or tracking required</li>
                    <li><strong>Mobile Friendly</strong> - Works seamlessly on all devices</li>
                    <li><strong>Performance Optimized</strong> - Minimal impact on page load times</li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Render Turnstile config section description
     */
    public function render_turnstile_config_section() {
        echo '<p>Configure your Cloudflare Turnstile keys and widget appearance. You can get these keys from your Cloudflare dashboard.</p>';
    }

    /**
     * Render site key field
     */
    public function render_site_key_field() {
        $options = get_option('flexpress_turnstile_settings', array());
        $site_key = $options['site_key'] ?? '';
        ?>
        <input type="text" 
               name="flexpress_turnstile_settings[site_key]" 
               value="<?php echo esc_attr($site_key); ?>" 
               class="regular-text" 
               placeholder="0x4AAAAAA..." />
        <p class="description">Enter your Turnstile Site Key from Cloudflare dashboard.</p>
        <?php
    }

    /**
     * Render secret key field
     */
    public function render_secret_key_field() {
        $options = get_option('flexpress_turnstile_settings', array());
        $secret_key = $options['secret_key'] ?? '';
        ?>
        <input type="password" 
               name="flexpress_turnstile_settings[secret_key]" 
               value="<?php echo esc_attr($secret_key); ?>" 
               class="regular-text" 
               placeholder="0x4AAAAAA..." />
        <p class="description">Enter your Turnstile Secret Key from Cloudflare dashboard. This is used for server-side validation.</p>
        <?php
    }

    /**
     * Render theme field
     */
    public function render_theme_field() {
        $options = get_option('flexpress_turnstile_settings', array());
        $theme = $options['theme'] ?? 'auto';
        ?>
        <select name="flexpress_turnstile_settings[theme]">
            <option value="auto" <?php selected($theme, 'auto'); ?>>Auto (follows system theme)</option>
            <option value="light" <?php selected($theme, 'light'); ?>>Light</option>
            <option value="dark" <?php selected($theme, 'dark'); ?>>Dark</option>
        </select>
        <p class="description">Choose the widget theme. Auto will match your site's theme.</p>
        <?php
    }

    /**
     * Render size field
     */
    public function render_size_field() {
        $options = get_option('flexpress_turnstile_settings', array());
        $size = $options['size'] ?? 'normal';
        ?>
        <select name="flexpress_turnstile_settings[size]">
            <option value="normal" <?php selected($size, 'normal'); ?>>Normal</option>
            <option value="compact" <?php selected($size, 'compact'); ?>>Compact</option>
        </select>
        <p class="description">Choose the widget size. Compact is smaller and less intrusive.</p>
        <?php
    }

    /**
     * Render Turnstile forms section description
     */
    public function render_turnstile_forms_section() {
        echo '<p>Choose which forms should be protected by Turnstile. All forms will show the Turnstile widget to verify human users.</p>';
    }

    /**
     * Render protect contact forms field
     */
    public function render_protect_contact_forms_field() {
        $options = get_option('flexpress_turnstile_settings', array());
        $protect_contact_forms = $options['protect_contact_forms'] ?? 1;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_turnstile_settings[protect_contact_forms]" 
                   value="1" 
                   <?php checked($protect_contact_forms); ?> />
            Protect Contact Forms (Contact Form 7, WPForms, etc.)
        </label>
        <p class="description">📧 Adds Turnstile protection to all contact forms on your site.</p>
        <?php
    }

    /**
     * Render protect comment forms field
     */
    public function render_protect_comment_forms_field() {
        $options = get_option('flexpress_turnstile_settings', array());
        $protect_comment_forms = $options['protect_comment_forms'] ?? 1;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_turnstile_settings[protect_comment_forms]" 
                   value="1" 
                   <?php checked($protect_comment_forms); ?> />
            Protect Comment Forms
        </label>
        <p class="description">💬 Adds Turnstile protection to WordPress comment forms.</p>
        <?php
    }

    /**
     * Render protect registration forms field
     */
    public function render_protect_registration_forms_field() {
        $options = get_option('flexpress_turnstile_settings', array());
        $protect_registration_forms = $options['protect_registration_forms'] ?? 1;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_turnstile_settings[protect_registration_forms]" 
                   value="1" 
                   <?php checked($protect_registration_forms); ?> />
            Protect Registration Forms
        </label>
        <p class="description">👤 Adds Turnstile protection to user registration forms.</p>
        <?php
    }

    /**
     * Render protect login forms field
     */
    public function render_protect_login_forms_field() {
        $options = get_option('flexpress_turnstile_settings', array());
        $protect_login_forms = $options['protect_login_forms'] ?? 1;
        ?>
        <label>
            <input type="checkbox" 
                   name="flexpress_turnstile_settings[protect_login_forms]" 
                   value="1" 
                   <?php checked($protect_login_forms); ?> />
            Protect Login Forms
        </label>
        <p class="description">🔑 Adds Turnstile protection to user login forms.</p>
        <?php
    }

    /**
     * Render Turnstile test section description
     */
    public function render_turnstile_test_section() {
        echo '<p>Test your Turnstile configuration to ensure everything is working correctly.</p>';
    }

    /**
     * Render test connection field
     */
    public function render_test_connection_field() {
        ?>
        <button type="button" 
                id="test-turnstile-connection" 
                class="button button-secondary">Test Turnstile Connection</button>
        <div id="turnstile-test-results" style="margin-top: 10px;"></div>
        <p class="description">Test your Turnstile keys and configuration.</p>
        <?php
    }
}

// Initialize the Turnstile settings page only in admin
if (is_admin()) {
    new FlexPress_Turnstile_Settings();
}
