<?php
/**
 * FlexPress Verotel Orphaned Webhooks Management
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Verotel Orphaned Webhooks Settings Class
 */
class FlexPress_Verotel_Orphaned_Webhooks {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('wp_ajax_flexpress_clear_orphaned_webhooks', array($this, 'ajax_clear_orphaned_webhooks'));
        add_action('wp_ajax_flexpress_process_orphaned_webhook', array($this, 'ajax_process_orphaned_webhook'));
    }
    
    /**
     * Add menu page under FlexPress Settings
     */
    public function add_menu_page() {
        // Temporarily disabled - functionality moved to main Verotel settings
        /*
        add_submenu_page(
            'flexpress-settings',
            __('Orphaned Webhooks', 'flexpress'),
            __('Orphaned Webhooks', 'flexpress'),
            'manage_options',
            'flexpress-orphaned-webhooks',
            array($this, 'render_page')
        );
        */
    }
    
    /**
     * Render the orphaned webhooks page
     */
    public function render_page() {
        $orphaned_webhooks = get_option('flexpress_verotel_orphaned_webhooks', array());
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Verotel Orphaned Webhooks', 'flexpress'); ?></h1>
            
            <?php if (empty($orphaned_webhooks)): ?>
                <div class="notice notice-success">
                    <p><?php esc_html_e('No orphaned webhooks found. All webhooks are being processed correctly.', 'flexpress'); ?></p>
                </div>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php esc_html_e('Warning:', 'flexpress'); ?></strong>
                        <?php echo sprintf(
                            esc_html__('Found %d orphaned webhooks that require manual review. These are webhooks for users that no longer exist in WordPress but have active subscriptions in Verotel.', 'flexpress'),
                            count($orphaned_webhooks)
                        ); ?>
                    </p>
                </div>
                
                <div class="tablenav top">
                    <button type="button" class="button button-secondary" id="clear-all-orphaned-webhooks">
                        <?php esc_html_e('Clear All Orphaned Webhooks', 'flexpress'); ?>
                    </button>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Timestamp', 'flexpress'); ?></th>
                            <th scope="col"><?php esc_html_e('Event', 'flexpress'); ?></th>
                            <th scope="col"><?php esc_html_e('Sale ID', 'flexpress'); ?></th>
                            <th scope="col"><?php esc_html_e('Transaction ID', 'flexpress'); ?></th>
                            <th scope="col"><?php esc_html_e('Amount', 'flexpress'); ?></th>
                            <th scope="col"><?php esc_html_e('Details', 'flexpress'); ?></th>
                            <th scope="col"><?php esc_html_e('Actions', 'flexpress'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orphaned_webhooks as $index => $webhook): ?>
                            <tr>
                                <td><?php echo esc_html($webhook['timestamp']); ?></td>
                                <td>
                                    <span class="event-<?php echo esc_attr($webhook['event']); ?>">
                                        <?php echo esc_html(ucfirst($webhook['event'])); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($webhook['sale_id']); ?></td>
                                <td><?php echo esc_html($webhook['transaction_id']); ?></td>
                                <td>
                                    <?php if (!empty($webhook['currency']) && !empty($webhook['amount'])): ?>
                                        <?php echo esc_html($webhook['currency'] . ' ' . $webhook['amount']); ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <details>
                                        <summary><?php esc_html_e('View Raw Data', 'flexpress'); ?></summary>
                                        <pre style="font-size: 11px; max-height: 200px; overflow-y: auto;"><?php echo esc_html(json_encode($webhook['webhook_data'], JSON_PRETTY_PRINT)); ?></pre>
                                    </details>
                                </td>
                                <td>
                                    <button type="button" class="button button-small process-webhook" data-index="<?php echo esc_attr($index); ?>" data-event="<?php echo esc_attr($webhook['event']); ?>">
                                        <?php esc_html_e('Mark as Reviewed', 'flexpress'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div class="card" style="margin-top: 20px;">
                <h2><?php esc_html_e('What are Orphaned Webhooks?', 'flexpress'); ?></h2>
                <p><?php esc_html_e('Orphaned webhooks occur when:', 'flexpress'); ?></p>
                <ul>
                    <li><?php esc_html_e('A user account is deleted from WordPress but their subscription still exists in Verotel', 'flexpress'); ?></li>
                    <li><?php esc_html_e('Verotel sends webhook notifications for these orphaned subscriptions', 'flexpress'); ?></li>
                    <li><?php esc_html_e('Critical events (cancellations, refunds, chargebacks) need manual review', 'flexpress'); ?></li>
                </ul>
                
                <h3><?php esc_html_e('Recommended Actions:', 'flexpress'); ?></h3>
                <ul>
                    <li><strong><?php esc_html_e('Cancellations/Credits:', 'flexpress'); ?></strong> <?php esc_html_e('Verify the cancellation/refund was processed correctly in your Verotel Control Center', 'flexpress'); ?></li>
                    <li><strong><?php esc_html_e('Chargebacks:', 'flexpress'); ?></strong> <?php esc_html_e('Review chargeback details and take appropriate action', 'flexpress'); ?></li>
                    <li><strong><?php esc_html_e('Cleanup:', 'flexpress'); ?></strong> <?php esc_html_e('Cancel orphaned subscriptions in Verotel Control Center to prevent future webhooks', 'flexpress'); ?></li>
                </ul>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#clear-all-orphaned-webhooks').on('click', function() {
                if (confirm('<?php esc_js(__('Are you sure you want to clear all orphaned webhooks? This action cannot be undone.', 'flexpress')); ?>')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'flexpress_clear_orphaned_webhooks',
                            nonce: '<?php echo wp_create_nonce('flexpress_orphaned_webhooks_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('Error: ' + response.data);
                            }
                        }
                    });
                }
            });
            
            $('.process-webhook').on('click', function() {
                var index = $(this).data('index');
                var event = $(this).data('event');
                var row = $(this).closest('tr');
                
                if (confirm('<?php esc_js(__('Mark this webhook as reviewed and remove it from the list?', 'flexpress')); ?>')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'flexpress_process_orphaned_webhook',
                            index: index,
                            nonce: '<?php echo wp_create_nonce('flexpress_orphaned_webhooks_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                row.fadeOut();
                            } else {
                                alert('Error: ' + response.data);
                            }
                        }
                    });
                }
            });
        });
        </script>
        
        <style>
        .event-cancel, .event-cancellation { color: #d63638; font-weight: bold; }
        .event-credit { color: #e65100; font-weight: bold; }
        .event-chargeback { color: #d32f2f; font-weight: bold; background: #ffebee; padding: 2px 6px; border-radius: 3px; }
        .event-initial { color: #2e7d32; }
        .event-rebill { color: #1976d2; }
        </style>
        <?php
    }
    
    /**
     * AJAX handler to clear all orphaned webhooks
     */
    public function ajax_clear_orphaned_webhooks() {
        if (!wp_verify_nonce($_POST['nonce'], 'flexpress_orphaned_webhooks_nonce')) {
            wp_send_json_error(__('Security check failed', 'flexpress'));
            exit;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'flexpress'));
            exit;
        }
        
        delete_option('flexpress_verotel_orphaned_webhooks');
        wp_send_json_success(__('All orphaned webhooks cleared', 'flexpress'));
    }
    
    /**
     * AJAX handler to process a single orphaned webhook
     */
    public function ajax_process_orphaned_webhook() {
        if (!wp_verify_nonce($_POST['nonce'], 'flexpress_orphaned_webhooks_nonce')) {
            wp_send_json_error(__('Security check failed', 'flexpress'));
            exit;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'flexpress'));
            exit;
        }
        
        $index = intval($_POST['index']);
        $orphaned_webhooks = get_option('flexpress_verotel_orphaned_webhooks', array());
        
        if (!isset($orphaned_webhooks[$index])) {
            wp_send_json_error(__('Webhook not found', 'flexpress'));
            exit;
        }
        
        // Remove the webhook from the list
        unset($orphaned_webhooks[$index]);
        $orphaned_webhooks = array_values($orphaned_webhooks); // Re-index array
        
        update_option('flexpress_verotel_orphaned_webhooks', $orphaned_webhooks);
        wp_send_json_success(__('Webhook marked as reviewed', 'flexpress'));
    }
}

// Initialize the orphaned webhooks management
new FlexPress_Verotel_Orphaned_Webhooks(); 