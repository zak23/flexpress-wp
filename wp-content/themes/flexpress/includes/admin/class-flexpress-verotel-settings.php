<?php
/**
 * FlexPress Verotel Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Verotel Settings Class
 */
class FlexPress_Verotel_Settings {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_submenu_page'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('flexpress_verotel_settings', 'flexpress_verotel_settings');

        add_settings_section(
            'flexpress_verotel_general_section',
            __('Verotel FlexPay Settings', 'flexpress'),
            array($this, 'render_verotel_general_section'),
            'flexpress_verotel_settings'
        );

        add_settings_field(
            'verotel_merchant_id',
            __('Merchant ID', 'flexpress'),
            array($this, 'render_verotel_merchant_id_field'),
            'flexpress_verotel_settings',
            'flexpress_verotel_general_section'
        );

        add_settings_field(
            'verotel_shop_id',
            __('Shop ID', 'flexpress'),
            array($this, 'render_verotel_shop_id_field'),
            'flexpress_verotel_settings',
            'flexpress_verotel_general_section'
        );

        add_settings_field(
            'verotel_signature_key',
            __('Signature Key', 'flexpress'),
            array($this, 'render_verotel_signature_key_field'),
            'flexpress_verotel_settings',
            'flexpress_verotel_general_section'
        );

        add_settings_section(
            'flexpress_verotel_urls_section',
            __('Verotel URL Configuration', 'flexpress'),
            array($this, 'render_verotel_urls_section'),
            'flexpress_verotel_settings'
        );

        add_settings_field(
            'verotel_success_url',
            __('Success URL', 'flexpress'),
            array($this, 'render_verotel_success_url_field'),
            'flexpress_verotel_settings',
            'flexpress_verotel_urls_section'
        );

        add_settings_field(
            'verotel_decline_url',
            __('Decline URL', 'flexpress'),
            array($this, 'render_verotel_decline_url_field'),
            'flexpress_verotel_settings',
            'flexpress_verotel_urls_section'
        );

        add_settings_field(
            'verotel_postback_url',
            __('Postback URL (Webhook)', 'flexpress'),
            array($this, 'render_verotel_postback_url_field'),
            'flexpress_verotel_settings',
            'flexpress_verotel_urls_section'
        );

        add_settings_field(
            'verotel_cancel_url',
            __('Cancel URL', 'flexpress'),
            array($this, 'render_verotel_cancel_url_field'),
            'flexpress_verotel_settings',
            'flexpress_verotel_urls_section'
        );
        
        // Debug section and signature bypass removed - no longer needed
    }

    /**
     * Render the Verotel general section
     */
    public function render_verotel_general_section() {
        echo '<p>' . esc_html__('Configure your Verotel FlexPay integration settings.', 'flexpress') . '</p>';
    }

    /**
     * Render the Verotel Merchant ID field
     */
    public function render_verotel_merchant_id_field() {
        $options = get_option('flexpress_verotel_settings', array());
        $merchant_id = isset($options['verotel_merchant_id']) ? $options['verotel_merchant_id'] : '9804000001074300';
        ?>
        <input type="text" id="flexpress_verotel_settings[verotel_merchant_id]" name="flexpress_verotel_settings[verotel_merchant_id]" value="<?php echo esc_attr($merchant_id); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Enter your Verotel Merchant ID (Customer ID).', 'flexpress'); ?></p>
        <?php
    }

    /**
     * Render the Verotel Shop ID field
     */
    public function render_verotel_shop_id_field() {
        $options = get_option('flexpress_verotel_settings', array());
        $shop_id = isset($options['verotel_shop_id']) ? $options['verotel_shop_id'] : '133772';
        ?>
        <input type="text" id="flexpress_verotel_settings[verotel_shop_id]" name="flexpress_verotel_settings[verotel_shop_id]" value="<?php echo esc_attr($shop_id); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Enter your Verotel Shop ID.', 'flexpress'); ?></p>
        <?php
    }

    /**
     * Render the Verotel Signature Key field
     */
    public function render_verotel_signature_key_field() {
        $options = get_option('flexpress_verotel_settings', array());
        $signature_key = isset($options['verotel_signature_key']) ? $options['verotel_signature_key'] : 'uHrSH2CqRJpbgXhJtuYPyd3dE7rpb4';
        ?>
        <input type="password" id="flexpress_verotel_settings[verotel_signature_key]" name="flexpress_verotel_settings[verotel_signature_key]" value="<?php echo esc_attr($signature_key); ?>" class="regular-text" />
        <button type="button" onclick="toggleSignatureVisibility()" class="button button-secondary">Show/Hide</button>
        <p class="description">
            <?php esc_html_e('Enter your Verotel Signature Key exactly as shown in your Verotel Control Center.', 'flexpress'); ?>
            <br><strong><?php esc_html_e('Important:', 'flexpress'); ?></strong> <?php esc_html_e('This must match exactly - any extra spaces or characters will cause webhook failures.', 'flexpress'); ?>
        </p>
        <script>
        function toggleSignatureVisibility() {
            var field = document.getElementById('flexpress_verotel_settings[verotel_signature_key]');
            if (field.type === 'password') {
                field.type = 'text';
            } else {
                field.type = 'password';
            }
        }
        </script>
        <?php
    }

    /**
     * Render the Verotel URLs section
     */
    public function render_verotel_urls_section() {
        echo '<p>' . esc_html__('Configure the URLs that Verotel will use for redirects and notifications. Copy these URLs to your Verotel account settings.', 'flexpress') . '</p>';
    }

    /**
     * Render the Verotel Success URL field
     */
    public function render_verotel_success_url_field() {
        $success_url = admin_url('admin-ajax.php?action=verotel_payment_return');
        ?>
        <input type="text" value="<?php echo esc_attr($success_url); ?>" class="regular-text" readonly />
        <p class="description">
            <?php esc_html_e('Copy this URL to your Verotel account as the "Success URL". Users will be redirected here after successful payment.', 'flexpress'); ?>
            <br><strong><?php esc_html_e('Note:', 'flexpress'); ?></strong> <?php esc_html_e('This URL can be overridden via the successURL parameter in API calls.', 'flexpress'); ?>
        </p>
        <?php
    }

    /**
     * Render the Verotel Decline URL field
     */
    public function render_verotel_decline_url_field() {
        $decline_url = home_url('/join?payment=declined');
        ?>
        <input type="text" value="<?php echo esc_attr($decline_url); ?>" class="regular-text" readonly />
        <p class="description">
            <?php esc_html_e('Copy this URL to your Verotel account as the "Decline URL". Users will be redirected here after failed payment.', 'flexpress'); ?>
            <br><strong><?php esc_html_e('Note:', 'flexpress'); ?></strong> <?php esc_html_e('This URL can be overridden via the declineURL parameter in API calls.', 'flexpress'); ?>
        </p>
        <?php
    }

    /**
     * Render the Verotel Postback URL field
     */
    public function render_verotel_postback_url_field() {
        $postback_url = admin_url('admin-ajax.php?action=verotel_webhook');
        ?>
        <input type="text" value="<?php echo esc_attr($postback_url); ?>" class="regular-text" readonly />
        <button type="button" onclick="copyToClipboard('<?php echo esc_js($postback_url); ?>')" class="button button-secondary">Copy URL</button>
        
        <p class="description">
            <?php esc_html_e('Copy this URL to your Verotel account as the "Postback URL". This is where Verotel will send webhook notifications.', 'flexpress'); ?>
            <br><strong><?php esc_html_e('Important:', 'flexpress'); ?></strong> <?php esc_html_e('This URL is required for proper subscription management.', 'flexpress'); ?>
        </p>
        
        <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('URL copied to clipboard!');
            });
        }
        </script>
        <?php
    }

    /**
     * Render the Verotel Cancel URL field
     */
    public function render_verotel_cancel_url_field() {
        $cancel_url = home_url('/my-account?subscription=cancelled');
        ?>
        <input type="text" value="<?php echo esc_attr($cancel_url); ?>" class="regular-text" readonly />
        <p class="description">
            <?php esc_html_e('Copy this URL to your Verotel account as the "Cancel URL". Users will be redirected here after cancelling their subscription.', 'flexpress'); ?>
        </p>
        <?php
    }

    // Debug section and signature bypass functions removed - no longer needed

    /**
     * Add submenu page under FlexPress Settings
     */
    public function add_submenu_page() {
        add_submenu_page(
            'flexpress-settings',
            __('Verotel', 'flexpress'),
            __('Verotel', 'flexpress'),
            'manage_options',
            'flexpress-verotel-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render the Verotel settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Verotel Settings', 'flexpress'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_verotel_settings');
                do_settings_sections('flexpress_verotel_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

// Initialize the Verotel settings
new FlexPress_Verotel_Settings(); 