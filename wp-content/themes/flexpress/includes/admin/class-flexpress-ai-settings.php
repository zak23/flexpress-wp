<?php
/** @package FlexPress */
if (!defined('ABSPATH')) { exit; }

class FlexPress_AI_Settings {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_submenu_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_flexpress_test_grok_connection', array($this, 'test_connection'));
        add_filter('option_page_capability_flexpress_ai_settings', array($this, 'settings_capability'));
    }

    public function add_submenu_page() {
        add_submenu_page('flexpress-settings', __('AI Settings', 'flexpress'), __('AI', 'flexpress'), flexpress_get_founder_capability(), 'flexpress-ai-settings', array($this, 'render_page'));
    }

    public function register_settings() {
        register_setting('flexpress_ai_settings', 'flexpress_ai_settings', array('sanitize_callback' => array($this, 'sanitize_settings')));
    }

    /** Keep save access aligned with the founder-only menu and AJAX endpoint. */
    public function settings_capability() {
        return flexpress_get_founder_capability();
    }

    public function sanitize_settings($input) {
        $current = get_option('flexpress_ai_settings', array());
        $current = is_array($current) ? $current : array();
        $settings = array(
            'casting_spam_enabled' => !empty($input['casting_spam_enabled']),
            'grok_model' => isset($input['grok_model']) && $input['grok_model'] !== '' ? sanitize_text_field($input['grok_model']) : FlexPress_Grok_Spam_Classifier::DEFAULT_MODEL,
            'grok_api_key' => $current['grok_api_key'] ?? '',
        );
        if (!empty($input['grok_api_key'])) {
            $settings['grok_api_key'] = sanitize_text_field($input['grok_api_key']);
        }
        return $settings;
    }

    public function render_page() {
        if (!flexpress_current_user_is_founder()) { wp_die(esc_html__('You do not have permission to access AI settings.', 'flexpress')); }
        $settings = get_option('flexpress_ai_settings', array());
        $settings = is_array($settings) ? $settings : array();
        $configured = !empty($settings['grok_api_key']);
        $suffix = $configured ? substr($settings['grok_api_key'], -4) : '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('AI Settings', 'flexpress'); ?></h1>
            <p><?php esc_html_e('Grok reviews successful casting applications for spam. Screening is informational and never prevents delivery.', 'flexpress'); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields('flexpress_ai_settings'); ?>
                <table class="form-table" role="presentation"><tbody>
                    <tr><th scope="row"><?php esc_html_e('Casting spam screening', 'flexpress'); ?></th><td><label><input type="checkbox" name="flexpress_ai_settings[casting_spam_enabled]" value="1" <?php checked(!empty($settings['casting_spam_enabled'])); ?> /> <?php esc_html_e('Review successful Contact Form 7 casting applications with Grok', 'flexpress'); ?></label></td></tr>
                    <tr><th scope="row"><label for="flexpress-grok-api-key"><?php esc_html_e('Grok API key', 'flexpress'); ?></label></th><td><input id="flexpress-grok-api-key" type="password" name="flexpress_ai_settings[grok_api_key]" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo esc_attr($configured ? sprintf(__('Configured (ends in %s)', 'flexpress'), $suffix) : __('Paste your xAI API key', 'flexpress')); ?>" /><p class="description"><?php esc_html_e('Leave blank to retain the saved key. Casting submissions, including supplied contact details, are sent to xAI when screening is enabled.', 'flexpress'); ?></p></td></tr>
                    <tr><th scope="row"><label for="flexpress-grok-model"><?php esc_html_e('Grok model', 'flexpress'); ?></label></th><td><input id="flexpress-grok-model" type="text" name="flexpress_ai_settings[grok_model]" value="<?php echo esc_attr($settings['grok_model'] ?? FlexPress_Grok_Spam_Classifier::DEFAULT_MODEL); ?>" class="regular-text" /><p class="description"><?php esc_html_e('Use an xAI model that supports structured outputs.', 'flexpress'); ?></p></td></tr>
                </tbody></table>
                <?php submit_button(); ?>
            </form>
            <hr />
            <h2><?php esc_html_e('Connection test', 'flexpress'); ?></h2>
            <button id="flexpress-test-grok" type="button" class="button button-secondary"><?php esc_html_e('Test Grok connection', 'flexpress'); ?></button> <span id="flexpress-grok-test-result" aria-live="polite"></span>
            <script>jQuery(function($){ $('#flexpress-test-grok').on('click', function(){ var $button=$(this), $result=$('#flexpress-grok-test-result'); $button.prop('disabled',true); $result.text('<?php echo esc_js(__('Testing…', 'flexpress')); ?>'); $.post(ajaxurl,{action:'flexpress_test_grok_connection',nonce:'<?php echo esc_js(wp_create_nonce('flexpress_test_grok_connection')); ?>'}).done(function(response){$result.text(response.success ? response.data : response.data);}).fail(function(){$result.text('<?php echo esc_js(__('Connection test failed.', 'flexpress')); ?>');}).always(function(){$button.prop('disabled',false);}); }); });</script>
        </div>
        <?php
    }

    public function test_connection() {
        check_ajax_referer('flexpress_test_grok_connection', 'nonce');
        if (!flexpress_current_user_is_founder()) { wp_send_json_error(__('You do not have permission to test this connection.', 'flexpress'), 403); }
        $result = (new FlexPress_Grok_Spam_Classifier())->test_connection();
        if (is_wp_error($result)) { wp_send_json_error($result->get_error_message()); }
        wp_send_json_success(__('Grok connection succeeded.', 'flexpress'));
    }
}

if (is_admin()) { new FlexPress_AI_Settings(); }
