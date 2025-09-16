<?php
/**
 * FlexPress Verotel Diagnostics
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Verotel Diagnostics Class
 */
class FlexPress_Verotel_Diagnostics {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        // AJAX handlers for signature testing removed - no longer needed
    }
    
    /**
     * Add menu page under FlexPress Settings
     */
    public function add_menu_page() {
        // Temporarily disabled - functionality moved to main Verotel settings
        /*
        add_submenu_page(
            'flexpress-settings',
            __('Verotel Diagnostics', 'flexpress'),
            __('Verotel Diagnostics', 'flexpress'),
            'manage_options',
            'flexpress-verotel-diagnostics',
            array($this, 'render_page')
        );
        */
    }
    
    /**
     * Render the diagnostics page
     */
    public function render_page() {
        $verotel_settings = get_option('flexpress_verotel_settings', array());
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Verotel Diagnostics', 'flexpress'); ?></h1>
            
            <div class="card">
                <h2><?php esc_html_e('Configuration Status', 'flexpress'); ?></h2>
                
                <?php
                $merchant_id = isset($verotel_settings['verotel_merchant_id']) ? $verotel_settings['verotel_merchant_id'] : '';
                $shop_id = isset($verotel_settings['verotel_shop_id']) ? $verotel_settings['verotel_shop_id'] : '';
                $signature_key = isset($verotel_settings['verotel_signature_key']) ? $verotel_settings['verotel_signature_key'] : '';
                
                $config_status = array(
                    'Merchant ID' => !empty($merchant_id) ? '✅ Configured' : '❌ Missing',
                    'Shop ID' => !empty($shop_id) ? '✅ Configured' : '❌ Missing',
                    'Signature Key' => !empty($signature_key) ? '✅ Configured' : '❌ Missing',
                );
                ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Setting', 'flexpress'); ?></th>
                            <th><?php esc_html_e('Status', 'flexpress'); ?></th>
                            <th><?php esc_html_e('Value', 'flexpress'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($config_status as $setting => $status): ?>
                            <tr>
                                <td><?php echo esc_html($setting); ?></td>
                                <td><?php echo $status; ?></td>
                                <td>
                                    <?php
                                    switch ($setting) {
                                        case 'Merchant ID':
                                            echo $merchant_id ? esc_html($merchant_id) : '<em>Not set</em>';
                                            break;
                                        case 'Shop ID':
                                            echo $shop_id ? esc_html($shop_id) : '<em>Not set</em>';
                                            break;
                                        case 'Signature Key':
                                            echo $signature_key ? '<code>' . esc_html(substr($signature_key, 0, 8)) . '...</code>' : '<em>Not set</em>';
                                            break;
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2><?php esc_html_e('Live Signature Debugging', 'flexpress'); ?></h2>
                <p><?php esc_html_e('Test signature calculation with real webhook data from logs:', 'flexpress'); ?></p>
                
                <div style="background: #f0f0f0; padding: 15px; margin: 10px 0; font-family: monospace; font-size: 12px;">
                    <strong>Recent webhook data from logs:</strong><br>
                    CCBrand=TESTING&custom1=Zak&custom2=Dev&custom3=20%7Cplan_monthly&event=initial&nextChargeOn=2025-07-16&paymentMethod=CC&period=P30D&priceAmount=29.95&priceCurrency=USD&saleID=59075796&shopID=133772&subscriptionType=recurring&transactionID=104201393&truncatedPAN=XXXXXXXXXXXX8924&type=subscription
                </div>
                
                <div id="signature-debug-tool">
                    <h3><?php esc_html_e('Real-time Signature Calculator', 'flexpress'); ?></h3>
                    
                    <table class="wp-list-table widefat fixed striped" style="max-width: 100%;">
                        <tbody>
                            <tr>
                                <td style="width: 150px;"><strong>Current Signature Key:</strong></td>
                                <td><code><?php echo $signature_key ? esc_html($signature_key) : 'Not configured'; ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>Expected Signature:</strong></td>
                                <td><code>248a0f87790d3e705e8c9807f1a01af0cedddcc91a968f8d9669e2c2a65b41a7</code></td>
                            </tr>
                            <tr>
                                <td><strong>Received Signature:</strong></td>
                                <td><code>273dc4743a5e1b257ed19b0ac4c4b495354dcbe6cc2bc11ab6e543cf8a29c8d5</code></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h4><?php esc_html_e('Test Different URL Encoding Methods', 'flexpress'); ?></h4>
                    <button type="button" class="button button-primary" id="test-signature-calculations">
                        <?php esc_html_e('Run Signature Tests', 'flexpress'); ?>
                    </button>
                    
                    <div id="signature-test-results" style="margin-top: 15px;"></div>
                </div>
            </div>
            
            <div class="card">
                <h2><?php esc_html_e('Webhook URLs', 'flexpress'); ?></h2>
                <p><?php esc_html_e('Copy these URLs to your Verotel Control Center:', 'flexpress'); ?></p>
                
                <table class="wp-list-table widefat fixed striped">
                    <tbody>
                        <tr>
                            <td><strong><?php esc_html_e('Postback URL', 'flexpress'); ?></strong></td>
                            <td>
                                <code><?php echo esc_html(home_url('/wp-admin/admin-ajax.php?action=verotel_webhook')); ?></code>
                                <button type="button" class="button button-small" onclick="copyToClipboard('<?php echo esc_js(home_url('/wp-admin/admin-ajax.php?action=verotel_webhook')); ?>')">
                                    <?php esc_html_e('Copy', 'flexpress'); ?>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Success URL', 'flexpress'); ?></strong></td>
                            <td>
                                <code><?php echo esc_html(home_url('/wp-admin/admin-ajax.php?action=verotel_payment_return')); ?></code>
                                <button type="button" class="button button-small" onclick="copyToClipboard('<?php echo esc_js(home_url('/wp-admin/admin-ajax.php?action=verotel_payment_return')); ?>')">
                                    <?php esc_html_e('Copy', 'flexpress'); ?>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Decline URL', 'flexpress'); ?></strong></td>
                            <td>
                                <code><?php echo esc_html(home_url('/join?payment=declined')); ?></code>
                                <button type="button" class="button button-small" onclick="copyToClipboard('<?php echo esc_js(home_url('/join?payment=declined')); ?>')">
                                    <?php esc_html_e('Copy', 'flexpress'); ?>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2><?php esc_html_e('Webhook Status', 'flexpress'); ?></h2>
                <p><?php esc_html_e('Monitor your Verotel webhook integration status:', 'flexpress'); ?></p>
                
                <?php
                // Check for recent successful webhooks
                $successful_webhooks = get_option('flexpress_verotel_successful_webhooks', []);
                $orphaned_webhooks = get_option('flexpress_verotel_orphaned_webhooks', []);
                ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Status', 'flexpress'); ?></th>
                            <th><?php esc_html_e('Count', 'flexpress'); ?></th>
                            <th><?php esc_html_e('Last Event', 'flexpress'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span style="color: green;">✅</span> <?php esc_html_e('Successful Webhooks', 'flexpress'); ?></td>
                            <td><?php echo count($successful_webhooks); ?></td>
                            <td>
                                <?php 
                                if (!empty($successful_webhooks)) {
                                    $latest = end($successful_webhooks);
                                    echo esc_html($latest['timestamp'] ?? 'N/A');
                                } else {
                                    esc_html_e('None', 'flexpress');
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><span style="color: orange;">⚠️</span> <?php esc_html_e('Orphaned Webhooks', 'flexpress'); ?></td>
                            <td><?php echo count($orphaned_webhooks); ?></td>
                            <td>
                                <?php 
                                if (!empty($orphaned_webhooks)) {
                                    $latest = end($orphaned_webhooks);
                                    echo esc_html($latest['timestamp'] ?? 'N/A');
                                } else {
                                    esc_html_e('None', 'flexpress');
                                }
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <?php if (!empty($orphaned_webhooks)): ?>
                    <div class="notice notice-warning" style="margin-top: 15px;">
                        <p><strong><?php esc_html_e('Note:', 'flexpress'); ?></strong> <?php esc_html_e('Orphaned webhooks are events where no user could be identified. This is normal for test webhooks.', 'flexpress'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2><?php esc_html_e('Troubleshooting Steps', 'flexpress'); ?></h2>
                
                <ol>
                    <li>
                        <strong><?php esc_html_e('Verify Verotel Settings:', 'flexpress'); ?></strong>
                        <p><?php esc_html_e('Go to FlexPress Settings > Verotel and ensure all fields are correctly filled.', 'flexpress'); ?></p>
                    </li>
                    
                    <li>
                        <strong><?php esc_html_e('Check Verotel Control Center:', 'flexpress'); ?></strong>
                        <p><?php esc_html_e('Ensure the postback URL is set to:', 'flexpress'); ?> <code><?php echo esc_html(home_url('/wp-admin/admin-ajax.php?action=verotel_webhook')); ?></code></p>
                    </li>
                    
                    <li>
                        <strong><?php esc_html_e('Test Webhook Connectivity:', 'flexpress'); ?></strong>
                        <p><?php esc_html_e('Use the signature testing tool above with real webhook data from Verotel.', 'flexpress'); ?></p>
                    </li>
                    
                    <li>
                        <strong><?php esc_html_e('Review Orphaned Webhooks:', 'flexpress'); ?></strong>
                        <p><?php esc_html_e('Check FlexPress Settings > Orphaned Webhooks for failed events that need manual review.', 'flexpress'); ?></p>
                    </li>
                </ol>
            </div>
        </div>
        
        <script type="text/javascript">
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('<?php esc_js(__('Copied to clipboard!', 'flexpress')); ?>');
            }, function(err) {
                alert('<?php esc_js(__('Failed to copy: ', 'flexpress')); ?>' + err);
            });
        }
        </script>
        <?php
    }
    
    // Signature testing AJAX handlers removed - no longer needed
}

// Initialize the Verotel diagnostics
new FlexPress_Verotel_Diagnostics(); 