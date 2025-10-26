<?php

/**
 * Flowguard Settings Admin Page
 * 
 * Provides admin interface for configuring Flowguard payment settings.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FlexPress Flowguard Settings
 */
class FlexPress_Flowguard_Settings
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('flexpress_flowguard_settings', 'flexpress_flowguard_settings', array($this, 'sanitize_settings'));

        // API Configuration Section
        add_settings_section(
            'flowguard_api_section',
            'API Configuration',
            array($this, 'render_api_section'),
            'flexpress_flowguard_settings'
        );

        add_settings_field(
            'shop_id',
            'Shop ID',
            array($this, 'render_shop_id_field'),
            'flexpress_flowguard_settings',
            'flowguard_api_section'
        );

        add_settings_field(
            'signature_key',
            'Signature Key',
            array($this, 'render_signature_key_field'),
            'flexpress_flowguard_settings',
            'flowguard_api_section'
        );

        // Webhook Configuration Section
        add_settings_section(
            'flowguard_webhook_section',
            'Webhook Configuration',
            array($this, 'render_webhook_section'),
            'flexpress_flowguard_settings'
        );

        add_settings_field(
            'webhook_url',
            'Webhook URL',
            array($this, 'render_webhook_url_field'),
            'flexpress_flowguard_settings',
            'flowguard_webhook_section'
        );

        add_settings_field(
            'webhook_debug',
            'Webhook Debug Mode',
            array($this, 'render_webhook_debug_field'),
            'flexpress_flowguard_settings',
            'flowguard_webhook_section'
        );

        // Testing Section - handled separately in render_settings_page
    }

    /**
     * Sanitize settings
     * 
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        if (isset($input['shop_id'])) {
            $sanitized['shop_id'] = sanitize_text_field($input['shop_id']);
        }

        if (isset($input['signature_key'])) {
            $sanitized['signature_key'] = sanitize_text_field($input['signature_key']);
        }

        if (isset($input['webhook_debug'])) {
            $sanitized['webhook_debug'] = (bool) $input['webhook_debug'];
        }

        return $sanitized;
    }

    /**
     * Render API section description
     */
    public function render_api_section()
    {
        echo '<p>Configure your Flowguard API credentials from ControlCenter.</p>';
    }

    /**
     * Render shop ID field
     */
    public function render_shop_id_field()
    {
        $options = get_option('flexpress_flowguard_settings', array());
        $shop_id = $options['shop_id'] ?? '';
?>
        <input type="text"
            name="flexpress_flowguard_settings[shop_id]"
            value="<?php echo esc_attr($shop_id); ?>"
            class="regular-text"
            placeholder="134837" />
        <p class="description">Enter your Shop ID from ControlCenter.</p>
    <?php
    }

    /**
     * Render signature key field
     */
    public function render_signature_key_field()
    {
        $options = get_option('flexpress_flowguard_settings', array());
        $signature_key = $options['signature_key'] ?? '';
    ?>
        <input type="password"
            name="flexpress_flowguard_settings[signature_key]"
            value="<?php echo esc_attr($signature_key); ?>"
            class="regular-text"
            id="signature-key-field" />
        <button type="button"
            onclick="toggleSignatureVisibility()"
            class="button button-secondary">Show/Hide</button>
        <p class="description">Enter your Signature Key from ControlCenter.</p>
        <script>
            function toggleSignatureVisibility() {
                var field = document.getElementById('signature-key-field');
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
     * Render webhook section description
     */
    public function render_webhook_section()
    {
        echo '<p>Configure webhook settings for receiving payment notifications from Flowguard.</p>';
    }

    /**
     * Render webhook URL field
     */
    public function render_webhook_url_field()
    {
        $webhook_url = flexpress_flowguard_get_webhook_url();
    ?>
        <input type="text"
            value="<?php echo esc_attr($webhook_url); ?>"
            class="regular-text"
            readonly />
        <button type="button"
            onclick="copyToClipboard('<?php echo esc_js($webhook_url); ?>')"
            class="button button-secondary">Copy URL</button>
        <p class="description">Copy this URL to your ControlCenter webhook settings.</p>
        <script>
            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(function() {
                    alert('Webhook URL copied to clipboard!');
                }).catch(function(err) {
                    console.error('Could not copy text: ', err);
                    alert('Failed to copy URL. Please copy manually.');
                });
            }
        </script>
    <?php
    }

    /**
     * Render webhook debug field
     */
    public function render_webhook_debug_field()
    {
        $options = get_option('flexpress_flowguard_settings', array());
        $webhook_debug = $options['webhook_debug'] ?? false;
    ?>
        <label>
            <input type="checkbox"
                name="flexpress_flowguard_settings[webhook_debug]"
                value="1"
                <?php checked($webhook_debug); ?> />
            Enable webhook debug logging
        </label>
        <p class="description">Log all webhook requests to WordPress error log for debugging.</p>
    <?php
    }


    // Submenu is registered centrally in FlexPress_Settings to avoid duplicates

    /**
     * Enqueue admin scripts
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'flexpress-settings_page_flexpress-flowguard-settings') {
            return;
        }

        wp_enqueue_script('jquery');
    }

    /**
     * Render settings page
     */
    public function render_settings_page()
    {
    ?>
        <div class="wrap">
            <h1>Flowguard Settings</h1>

            <?php $this->render_status_overview(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_flowguard_settings');
                do_settings_sections('flexpress_flowguard_settings');
                submit_button('Save Flowguard Settings');
                ?>
            </form>

            <?php $this->render_testing_section(); ?>
            <?php $this->render_webhook_logs(); ?>
        </div>
    <?php
    }

    /**
     * Render status overview
     */
    private function render_status_overview()
    {
        $options = get_option('flexpress_flowguard_settings', array());
        $api = flexpress_get_flowguard_api();

    ?>
        <div class="card" style="max-width: 600px; margin-bottom: 20px;">
            <h2>Configuration Status</h2>
            <table class="form-table">
                <tr>
                    <th>Shop ID</th>
                    <td>
                        <?php if (!empty($options['shop_id'])): ?>
                            <span style="color: green;">✓ Configured</span> (<?php echo esc_html($options['shop_id']); ?>)
                        <?php else: ?>
                            <span style="color: red;">✗ Not configured</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Signature Key</th>
                    <td>
                        <?php if (!empty($options['signature_key'])): ?>
                            <span style="color: green;">✓ Configured</span>
                        <?php else: ?>
                            <span style="color: red;">✗ Not configured</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>API Status</th>
                    <td>
                        <?php if ($api): ?>
                            <span style="color: green;">✓ Ready</span>
                        <?php else: ?>
                            <span style="color: red;">✗ Not ready</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    <?php
    }

    /**
     * Render testing section
     */
    private function render_testing_section()
    {
    ?>
        <div class="card" style="max-width: 600px; margin-bottom: 20px;">
            <h2>Testing & Diagnostics</h2>

            <h3>Test API Connection</h3>
            <p>Test your Flowguard API configuration:</p>
            <button type="button"
                onclick="testFlowguardConnection()"
                class="button button-secondary">Test Connection</button>
            <div id="test-results" style="margin-top: 10px;"></div>

            <script>
                function testFlowguardConnection() {
                    var resultsDiv = document.getElementById('test-results');
                    resultsDiv.innerHTML = '<p>Testing connection...</p>';

                    jQuery.post(ajaxurl, {
                        action: 'test_flowguard_connection',
                        nonce: '<?php echo wp_create_nonce('test_flowguard_connection'); ?>'
                    }, function(response) {
                        if (response.success) {
                            resultsDiv.innerHTML = '<p style="color: green;">✓ Connection successful!</p>';
                        } else {
                            resultsDiv.innerHTML = '<p style="color: red;">✗ Connection failed: ' + response.data + '</p>';
                        }
                    });
                }
            </script>

            <h3>Webhook Test</h3>
            <p>Test webhook endpoint:</p>
            <button type="button"
                onclick="testWebhookEndpoint()"
                class="button button-secondary">Test Webhook</button>
            <div id="webhook-test-results" style="margin-top: 10px;"></div>

            <script>
                function testWebhookEndpoint() {
                    var resultsDiv = document.getElementById('webhook-test-results');
                    resultsDiv.innerHTML = '<p>Testing webhook...</p>';

                    jQuery.post(ajaxurl, {
                        action: 'test_flowguard_webhook',
                        nonce: '<?php echo wp_create_nonce('test_flowguard_webhook'); ?>'
                    }, function(response) {
                        if (response.success) {
                            resultsDiv.innerHTML = '<p style="color: green;">✓ Webhook endpoint working!</p>';
                        } else {
                            resultsDiv.innerHTML = '<p style="color: red;">✗ Webhook test failed: ' + response.data + '</p>';
                        }
                    });
                }
            </script>
        </div>
    <?php
    }

    /**
     * Render webhook logs
     */
    private function render_webhook_logs()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'flexpress_flowguard_webhooks';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;

        if (!$table_exists) {
            echo '<div class="card" style="max-width: 600px;"><h2>Webhook Logs</h2><p>Webhook logs table not found. Please run the database setup.</p></div>';
            return;
        }

        $webhooks = $wpdb->get_results(
            "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT 10",
            ARRAY_A
        );

    ?>
        <div class="card" style="max-width: 800px;">
            <h2>Recent Webhook Logs</h2>

            <?php if (empty($webhooks)): ?>
                <p>No webhook logs found.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Transaction ID</th>
                            <th>User ID</th>
                            <th>Processed</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($webhooks as $webhook): ?>
                            <tr>
                                <td><?php echo esc_html($webhook['event_type']); ?></td>
                                <td><?php echo esc_html($webhook['transaction_id']); ?></td>
                                <td><?php echo esc_html($webhook['user_id']); ?></td>
                                <td>
                                    <?php if ($webhook['processed']): ?>
                                        <span style="color: green;">✓</span>
                                    <?php else: ?>
                                        <span style="color: red;">✗</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($webhook['created_at']); ?></td>
                                <td>
                                    <button type="button"
                                        onclick="viewWebhookPayload(<?php echo $webhook['id']; ?>)"
                                        class="button button-small">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <script>
                function viewWebhookPayload(webhookId) {
                    jQuery.post(ajaxurl, {
                        action: 'get_webhook_payload',
                        webhook_id: webhookId,
                        nonce: '<?php echo wp_create_nonce('get_webhook_payload'); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('Webhook Payload:\n\n' + JSON.stringify(response.data, null, 2));
                        } else {
                            alert('Failed to load webhook payload');
                        }
                    });
                }
            </script>
        </div>
<?php
    }
}

// Initialize the settings page
new FlexPress_Flowguard_Settings();

// AJAX handlers for testing
add_action('wp_ajax_test_flowguard_connection', 'flexpress_test_flowguard_connection');
add_action('wp_ajax_test_flowguard_webhook', 'flexpress_test_flowguard_webhook');
add_action('wp_ajax_get_webhook_payload', 'flexpress_get_webhook_payload');

/**
 * Test Flowguard API connection
 */
function flexpress_test_flowguard_connection()
{
    check_ajax_referer('test_flowguard_connection', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $api = flexpress_get_flowguard_api();
    if (!$api) {
        wp_send_json_error('Flowguard API not configured');
    }

    // Test with a simple subscription request
    $test_data = [
        'priceAmount' => '2.95',
        'priceCurrency' => 'USD',
        'successUrl' => home_url('/test-success'),
        'declineUrl' => home_url('/test-decline'),
        'postbackUrl' => flexpress_flowguard_get_webhook_url(),
        'email' => 'test@example.com',
        'subscriptionType' => 'one-time',
        'period' => 'P2D',
        'referenceId' => 'test_connection_' . time()
    ];

    $result = $api->start_subscription($test_data);

    if ($result['success']) {
        wp_send_json_success('API connection successful');
    } else {
        wp_send_json_error($result['error']);
    }
}

/**
 * Test webhook endpoint
 */
function flexpress_test_flowguard_webhook()
{
    check_ajax_referer('test_flowguard_webhook', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    // Test webhook endpoint by making a request to it
    $webhook_url = flexpress_flowguard_get_webhook_url();

    $test_payload = [
        'postbackType' => 'approved',
        'orderType' => 'subscription',
        'saleId' => 'test_' . time(),
        'transactionId' => 'test_txn_' . time(),
        'shopId' => get_option('flexpress_flowguard_settings')['shop_id'] ?? '',
        'priceAmount' => '2.95',
        'priceCurrency' => 'USD',
        'subscriptionType' => 'one-time',
        'subscriptionPhase' => 'normal',
        'referenceId' => 'test_webhook_' . time()
    ];

    // Create test JWT
    $api = flexpress_get_flowguard_api();
    if (!$api) {
        wp_send_json_error('Flowguard API not configured');
    }

    // Use reflection to access private method for testing
    $reflection = new ReflectionClass($api);
    $create_jwt_method = $reflection->getMethod('create_jwt');
    $create_jwt_method->setAccessible(true);

    $test_jwt = $create_jwt_method->invoke($api, $test_payload);

    $response = wp_remote_post($webhook_url, [
        'body' => $test_jwt,
        'timeout' => 10
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error('Webhook test failed: ' . $response->get_error_message());
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code === 200) {
        wp_send_json_success('Webhook endpoint working correctly');
    } else {
        wp_send_json_error('Webhook returned status code: ' . $status_code);
    }
}

/**
 * Get webhook payload
 */
function flexpress_get_webhook_payload()
{
    check_ajax_referer('get_webhook_payload', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $webhook_id = intval($_POST['webhook_id']);

    global $wpdb;
    $table_name = $wpdb->prefix . 'flexpress_flowguard_webhooks';

    $webhook = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $webhook_id),
        ARRAY_A
    );

    if (!$webhook) {
        wp_send_json_error('Webhook not found');
    }

    $payload = json_decode($webhook['payload'], true);
    wp_send_json_success($payload);
}
