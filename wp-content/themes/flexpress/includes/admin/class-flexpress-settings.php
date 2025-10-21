<?php
/**
 * FlexPress Settings Page
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Settings Page Class
 */
class FlexPress_Settings {
    /**
     * Settings page slug
     *
     * @var string
     */
    private $page_slug = 'flexpress-settings';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'), 10);
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add the settings page to the admin menu
     */
    public function add_settings_page() {
        add_menu_page(
            __('FlexPress', 'flexpress'),
            __('FlexPress', 'flexpress'),
            'manage_options',
            $this->page_slug,
            array($this, 'render_settings_page'),
            'dashicons-admin-settings',
            30
        );
        
        // Add submenu pages in the desired order
        add_submenu_page(
            $this->page_slug,
            __('General', 'flexpress'),
            __('General', 'flexpress'),
            'manage_options',
            $this->page_slug,
            array($this, 'render_settings_page')
        );

        // Add new Pages & Menus submenu (moved out of General page)
        add_submenu_page(
            $this->page_slug,
            __('Pages & Menus', 'flexpress'),
            __('Pages & Menus', 'flexpress'),
            'manage_options',
            'flexpress-pages-menus',
            array($this, 'render_pages_menus_page')
        );

        // Add Auto-Setup Management submenu
        add_submenu_page(
            $this->page_slug,
            __('Auto-Setup', 'flexpress'),
            __('Auto-Setup', 'flexpress'),
            'manage_options',
            'flexpress-auto-setup',
            array($this, 'render_auto_setup_page')
        );

        // Add Discord Notifications submenu
        add_submenu_page(
            $this->page_slug,
            __('Discord Notifications', 'flexpress'),
            __('Discord', 'flexpress'),
            'manage_options',
            'flexpress-discord-settings',
            array($this, 'render_discord_settings_page')
        );

        // Add Cloudflare Turnstile submenu
        add_submenu_page(
            $this->page_slug,
            __('Cloudflare Turnstile', 'flexpress'),
            __('Turnstile', 'flexpress'),
            'manage_options',
            'flexpress-turnstile-settings',
            array($this, 'render_turnstile_settings_page')
        );

        // Add Plunk Email Marketing submenu
        add_submenu_page(
            $this->page_slug,
            __('Plunk Email Marketing', 'flexpress'),
            __('Plunk', 'flexpress'),
            'manage_options',
            'flexpress-plunk-settings',
            array($this, 'render_plunk_settings_page')
        );

        // Add Google SMTP submenu
        add_submenu_page(
            $this->page_slug,
            __('Google SMTP', 'flexpress'),
            __('Google SMTP', 'flexpress'),
            'manage_options',
            'flexpress-google-smtp-settings',
            array($this, 'render_google_smtp_settings_page')
        );
        
        // Add SMTP2Go submenu
        add_submenu_page(
            $this->page_slug,
            __('SMTP2Go', 'flexpress'),
            __('SMTP2Go', 'flexpress'),
            'manage_options',
            'flexpress-smtp2go-settings',
            array($this, 'render_smtp2go_settings_page')
        );

        // Add Flowguard submenu (centralized under FlexPress)
        add_submenu_page(
            $this->page_slug,
            __('Flowguard', 'flexpress'),
            __('Flowguard', 'flexpress'),
            'manage_options',
            'flexpress-flowguard-settings',
            function() {
                if (class_exists('FlexPress_Flowguard_Settings')) {
                    $obj = new FlexPress_Flowguard_Settings();
                    $obj->render_settings_page();
                } else {
                    echo '<div class="wrap"><h1>Flowguard Settings</h1><p>Flowguard settings class not found.</p></div>';
                }
            }
        );

        // Add Earnings submenu
        add_submenu_page(
            $this->page_slug,
            __('Earnings', 'flexpress'),
            __('Earnings', 'flexpress'),
            'manage_options',
            'flexpress-earnings',
            function() {
                if (class_exists('FlexPress_Earnings_Settings')) {
                    $obj = new FlexPress_Earnings_Settings();
                    $obj->render_earnings_page();
                } else {
                    echo '<div class="wrap"><h1>Earnings</h1><p>Earnings settings class not found.</p></div>';
                }
            }
        );

        // Add Tools submenu
        add_submenu_page(
            $this->page_slug,
            __('Tools', 'flexpress'),
            __('Tools', 'flexpress'),
            'manage_options',
            'flexpress-tools',
            array($this, 'render_tools_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // General settings are handled by FlexPress_General_Settings class
        // No need to register them here to avoid conflicts
        
        // Register auto-setup settings
        register_setting('flexpress_auto_setup_settings', 'flexpress_disable_auto_setup');
        
        // Register Discord settings
        register_setting('flexpress_discord_settings', 'flexpress_discord_settings', array(
            'sanitize_callback' => 'flexpress_sanitize_discord_settings'
        ));
        
        // Discord Configuration Section
        add_settings_section(
            'discord_config_section',
            'Discord Configuration',
            array($this, 'render_discord_config_section'),
            'flexpress_discord_settings'
        );
        
        add_settings_field(
            'webhook_url',
            'Default Discord Webhook URL',
            array($this, 'render_discord_webhook_url_field'),
            'flexpress_discord_settings',
            'discord_config_section'
        );
        
        add_settings_field(
            'webhook_url_financial',
            'Financial Notifications Webhook',
            array($this, 'render_discord_webhook_url_financial_field'),
            'flexpress_discord_settings',
            'discord_config_section'
        );
        
        add_settings_field(
            'webhook_url_contact',
            'Contact Forms Webhook',
            array($this, 'render_discord_webhook_url_contact_field'),
            'flexpress_discord_settings',
            'discord_config_section'
        );
        
        add_settings_field(
            'test_connection',
            'Test Connection',
            array($this, 'render_discord_test_connection_field'),
            'flexpress_discord_settings',
            'discord_config_section'
        );
        
        // Discord Notification Settings Section
        add_settings_section(
            'discord_notifications_section',
            'Notification Settings',
            array($this, 'render_discord_notifications_section'),
            'flexpress_discord_settings'
        );
        
        add_settings_field(
            'notify_subscriptions',
            'New Subscriptions',
            array($this, 'render_discord_notify_subscriptions_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_rebills',
            'Subscription Rebills',
            array($this, 'render_discord_notify_rebills_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_cancellations',
            'Subscription Cancellations',
            array($this, 'render_discord_notify_cancellations_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_expirations',
            'Subscription Expirations',
            array($this, 'render_discord_notify_expirations_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_ppv',
            'PPV Purchases',
            array($this, 'render_discord_notify_ppv_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_refunds',
            'Refunds & Chargebacks',
            array($this, 'render_discord_notify_refunds_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_extensions',
            'Subscription Extensions',
            array($this, 'render_discord_notify_extensions_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
        
        add_settings_field(
            'notify_talent_applications',
            'Talent Applications',
            array($this, 'render_discord_notify_talent_applications_field'),
            'flexpress_discord_settings',
            'discord_notifications_section'
        );
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('FlexPress General Settings', 'flexpress'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_general_settings');
                do_settings_sections('flexpress_general_settings');
                submit_button();
                ?>
            </form>
            
            <?php
            // Show success messages
            if (isset($_GET['legal_created']) && $_GET['legal_created'] === '1'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Legal pages and menu have been created successfully.', 'flexpress'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['legal_failed']) && $_GET['legal_failed'] === '1'): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html_e('Failed to create legal pages and menu. Please check error logs.', 'flexpress'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['created']) && $_GET['created'] === '1'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Default pages and menus have been created successfully.', 'flexpress'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['main_footer_created']) && $_GET['main_footer_created'] === '1'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Main footer pages and menu have been created successfully.', 'flexpress'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['main_footer_failed']) && $_GET['main_footer_failed'] === '1'): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html_e('Failed to create main footer pages and menu. Please check error logs.', 'flexpress'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['support_created']) && $_GET['support_created'] === '1'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Support pages and menu have been created successfully.', 'flexpress'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['support_failed']) && $_GET['support_failed'] === '1'): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html_e('Failed to create support pages and menu. Please check error logs.', 'flexpress'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the Tools page
     */
    public function render_tools_page() {
        // Handle form submissions
        if (isset($_POST['cancel_banned_subscriptions']) && wp_verify_nonce($_POST['cancel_banned_nonce'], 'cancel_banned_subscriptions')) {
            if (function_exists('flexpress_cancel_subscriptions_for_banned_users')) {
                $results = flexpress_cancel_subscriptions_for_banned_users();
                $message = sprintf(
                    __('Bulk cancellation completed. Total: %d, Success: %d, Failed: %d', 'flexpress'),
                    $results['total'],
                    $results['success'],
                    $results['failed']
                );
                echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
                
                if (!empty($results['errors'])) {
                    echo '<div class="notice notice-warning"><p><strong>Errors:</strong></p><ul>';
                    foreach ($results['errors'] as $error) {
                        echo '<li>' . esc_html($error) . '</li>';
                    }
                    echo '</ul></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Function not available', 'flexpress') . '</p></div>';
            }
        }
        
        // Handle test refund
        if (isset($_POST['test_refund']) && wp_verify_nonce($_POST['test_refund_nonce'], 'test_refund')) {
            $reference_id = sanitize_text_field($_POST['reference_id']);
            if (function_exists('flexpress_test_refund_webhook') && !empty($reference_id)) {
                $result = flexpress_test_refund_webhook($reference_id);
                if ($result['success']) {
                    echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid reference ID or function not available', 'flexpress') . '</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('FlexPress Tools', 'flexpress'); ?></h1>
            
            <div class="card" style="max-width: 600px;">
                <h2><?php esc_html_e('Banned User Management', 'flexpress'); ?></h2>
                <p><?php esc_html_e('Cancel active subscriptions for all banned users to prevent rebilling.', 'flexpress'); ?></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('cancel_banned_subscriptions', 'cancel_banned_nonce'); ?>
                    <input type="submit" name="cancel_banned_subscriptions" class="button button-primary" 
                           value="<?php esc_attr_e('Cancel Subscriptions for Banned Users', 'flexpress'); ?>"
                           onclick="return confirm('<?php esc_attr_e('Are you sure you want to cancel all active subscriptions for banned users?', 'flexpress'); ?>');" />
                </form>
            </div>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2><?php esc_html_e('Test Refund Webhook', 'flexpress'); ?></h2>
                <p><?php esc_html_e('Test the refund webhook functionality for a specific reference ID.', 'flexpress'); ?></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('test_refund', 'test_refund_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="reference_id"><?php esc_html_e('Reference ID', 'flexpress'); ?></label></th>
                            <td>
                                <input type="text" name="reference_id" id="reference_id" value="ppv_ep666870_uid49_affnone_promonone_srcreferr_ts59121900" class="regular-text" />
                                <p class="description"><?php esc_html_e('Enter the reference ID to test refund processing', 'flexpress'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <input type="submit" name="test_refund" class="button button-secondary" 
                           value="<?php esc_attr_e('Test Refund Webhook', 'flexpress'); ?>"
                           onclick="return confirm('<?php esc_attr_e('Are you sure you want to test the refund webhook? This will process a refund for the specified reference.', 'flexpress'); ?>');" />
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Render the Pages & Menus tools page (legacy auto-setup helpers)
     */
    public function render_pages_menus_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('FlexPress Pages & Menus Tools', 'flexpress'); ?></h1>

            <?php if (function_exists('flexpress_add_auto_setup_status_section')) {
                // Display auto-setup status summary
                flexpress_add_auto_setup_status_section();
            } ?>

            <p><?php esc_html_e('Use these helper buttons to (re)create default pages and menus if the automatic setup failed or you need to run it again.', 'flexpress'); ?></p>

            <div style="margin-bottom:20px;">
                <!-- Default full auto-setup -->
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-right:15px;">
                    <?php wp_nonce_field('create_pages_menus_nonce', 'create_pages_menus_nonce'); ?>
                    <input type="hidden" name="action" value="create_default_pages_menus" />
                    <input type="submit" class="button button-primary" value="<?php esc_attr_e('Create Default Pages & Menus', 'flexpress'); ?>" />
                </form>

                <!-- Footer pages & menu -->
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-right:15px;">
                    <?php wp_nonce_field('create_main_footer_pages_nonce', 'create_main_footer_pages_nonce'); ?>
                    <input type="hidden" name="action" value="create_main_footer_pages_menu" />
                    <input type="submit" class="button button-primary" value="<?php esc_attr_e('Create Main Footer Pages & Menu', 'flexpress'); ?>" />
                </form>

                <!-- Support pages & menu -->
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-right:15px;">
                    <?php wp_nonce_field('create_support_pages_nonce', 'create_support_pages_nonce'); ?>
                    <input type="hidden" name="action" value="create_support_pages_menu" />
                    <input type="submit" class="button button-primary" value="<?php esc_attr_e('Create Support Pages & Menu', 'flexpress'); ?>" />
                </form>

                <!-- Legal pages & menu -->
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                    <?php wp_nonce_field('create_legal_pages_nonce', 'create_legal_pages_nonce'); ?>
                    <input type="hidden" name="action" value="create_legal_pages_menu" />
                    <input type="submit" class="button button-secondary" value="<?php esc_attr_e('Create Legal Pages & Menu', 'flexpress'); ?>" />
                </form>
            </div>

            <hr />
            <h2><?php esc_html_e('What each button does', 'flexpress'); ?></h2>
            <ul style="list-style:disc;padding-left:20px;">
                <li><strong><?php esc_html_e('Default Pages & Menus', 'flexpress'); ?></strong> – <?php esc_html_e('Runs the full auto-setup creating all required pages and menus in one click.', 'flexpress'); ?></li>
                <li><strong><?php esc_html_e('Main Footer Pages & Menu', 'flexpress'); ?></strong> – <?php esc_html_e('Creates Home, Episodes, Models, Extras, Livestream, About, Casting and Contact pages then builds the Footer menu.', 'flexpress'); ?></li>
                <li><strong><?php esc_html_e('Support Pages & Menu', 'flexpress'); ?></strong> – <?php esc_html_e('Creates Join, Login, My Account, Reset Password, Cancel Membership and Affiliates pages and builds the Support menu.', 'flexpress'); ?></li>
                <li><strong><?php esc_html_e('Legal Pages & Menu', 'flexpress'); ?></strong> – <?php esc_html_e('Creates Privacy Policy, Customer Terms, 2257 Compliance, Anti-Slavery Policy and Content Removal pages and builds the Legal menu.', 'flexpress'); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render the Auto-Setup management page
     */
    public function render_auto_setup_page() {
        ?>
        <div class="wrap">
            <h1>🚀 FlexPress Auto-Setup Management</h1>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2 class="title">Current Status</h2>
                
                <?php
                $setup_completed = get_option('flexpress_auto_setup_completed', false);
                $setup_date = get_option('flexpress_auto_setup_date', '');
                $setup_results = get_option('flexpress_auto_setup_results', array());
                ?>
                
                <?php if ($setup_completed): ?>
                    <div class="notice notice-success inline">
                        <p>
                            <strong>✅ Auto-Setup Completed!</strong><br>
                            Setup Date: <?php echo esc_html($setup_date ? date('F j, Y g:i a', strtotime($setup_date)) : 'Unknown'); ?>
                        </p>
                    </div>
                    
                    <?php if (!empty($setup_results)): ?>
                        <h3>Setup Results:</h3>
                        <ul style="margin-left: 20px;">
                            <li><?php echo $setup_results['main_footer'] ? '✅' : '❌'; ?> Main Footer Pages & Menu</li>
                            <li><?php echo $setup_results['support'] ? '✅' : '❌'; ?> Support Pages & Menu</li>
                            <li><?php echo $setup_results['legal'] ? '✅' : '❌'; ?> Legal Pages & Menu</li>
                            <li>📊 Found <?php echo esc_html($setup_results['existing_pages'] ?? 0); ?> existing pages during setup</li>
                        </ul>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="notice notice-warning inline">
                        <p>
                            <strong>⚠️ Auto-Setup Not Completed</strong><br>
                            The automatic setup may not have run yet or failed.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2 class="title">Auto-Setup Settings</h2>
                
                <div style="margin-bottom: 20px;">
                    <h3>⚙️ Auto-Setup Configuration</h3>
                    <p>Control when auto-setup runs:</p>
                    
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('flexpress_auto_setup_settings');
                        ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="flexpress_disable_auto_setup">Disable Auto-Setup</label>
                                </th>
                                <td>
                                    <input type="checkbox" id="flexpress_disable_auto_setup" 
                                           name="flexpress_disable_auto_setup" value="1" 
                                           <?php checked(get_option('flexpress_disable_auto_setup', false)); ?> />
                                    <label for="flexpress_disable_auto_setup">
                                        Check this to completely disable automatic setup (recommended for production sites)
                                    </label>
                                    <p class="description">
                                        When disabled, auto-setup will only run when manually triggered.
                                    </p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button('Save Auto-Setup Settings'); ?>
                    </form>
                </div>
                
                <hr />
                
                <h2 class="title">Manual Controls</h2>
                
                <div style="margin-bottom: 20px;">
                    <h3>🚀 Quick Auto-Setup (Recommended)</h3>
                    <p>Use this button to run the complete auto-setup process:</p>
                    
                    <button type="button" id="flexpress-quick-setup-btn" class="button button-primary button-hero">
                        🚀 Run Complete Auto-Setup Now
                    </button>
                    
                    <div id="flexpress-quick-setup-status" style="margin-top: 10px; display: none;"></div>
                </div>
                
                <hr />
                
                <div style="margin-bottom: 20px;">
                    <h3>🔄 Force Re-Run</h3>
                    <p>This will delete the current setup and recreate everything from scratch:</p>
                    
                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=flexpress_force_auto_setup'), 'flexpress_force_setup'); ?>" 
                       class="button button-secondary"
                       onclick="return confirm('⚠️ WARNING: This will recreate all pages and menus. Are you sure?');">
                        🔄 Force Re-Run Auto-Setup
                    </a>
                </div>
                
                <hr />
                
                <div style="margin-bottom: 20px;">
                    <h3>🧹 Reset Setup Status</h3>
                    <p>This will mark auto-setup as incomplete, allowing it to run again:</p>
                    
                    <button type="button" id="flexpress-reset-status-btn" class="button button-secondary">
                        🧹 Reset Setup Status
                    </button>
                    
                    <div id="flexpress-reset-status-status" style="margin-top: 10px; display: none;"></div>
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Quick Setup Button
                $('#flexpress-quick-setup-btn').on('click', function() {
                    var $btn = $(this);
                    var $status = $('#flexpress-quick-setup-status');
                    
                    $btn.prop('disabled', true).text('⏳ Running Auto-Setup...');
                    $status.html('<div class="notice notice-info inline"><p>🔄 Running complete auto-setup process...</p></div>').show();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'flexpress_manual_auto_setup',
                            nonce: '<?php echo wp_create_nonce('flexpress_manual_setup'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $status.html('<div class="notice notice-success inline"><p>✅ ' + response.data + '</p></div>');
                                setTimeout(function() {
                                    location.reload();
                                }, 3000);
                            } else {
                                $status.html('<div class="notice notice-error inline"><p>❌ ' + response.data + '</p></div>');
                                $btn.prop('disabled', false).text('🚀 Run Complete Auto-Setup Now');
                            }
                        },
                        error: function() {
                            $status.html('<div class="notice notice-error inline"><p>❌ Failed to run auto-setup. Please check error logs.</p></div>');
                            $btn.prop('disabled', false).text('🚀 Run Complete Auto-Setup Now');
                        }
                    });
                });
                
                // Reset Status Button
                $('#flexpress-reset-status-btn').on('click', function() {
                    var $btn = $(this);
                    var $status = $('#flexpress-reset-status-status');
                    
                    if (confirm('Are you sure you want to reset the auto-setup status? This will allow auto-setup to run again.')) {
                        $btn.prop('disabled', true).text('⏳ Resetting...');
                        $status.html('<div class="notice notice-info inline"><p>🔄 Resetting setup status...</p></div>').show();
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'flexpress_reset_setup_status',
                                nonce: '<?php echo wp_create_nonce('flexpress_reset_status'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $status.html('<div class="notice notice-success inline"><p>✅ ' + response.data + '</p></div>');
                                    setTimeout(function() {
                                        location.reload();
                                    }, 2000);
                                } else {
                                    $status.html('<div class="notice notice-error inline"><p>❌ ' + response.data + '</p></div>');
                                    $btn.prop('disabled', false).text('🧹 Reset Setup Status');
                                }
                            },
                            error: function() {
                                $status.html('<div class="notice notice-error inline"><p>❌ Failed to reset status. Please check error logs.</p></div>');
                                $btn.prop('disabled', false).text('🧹 Reset Setup Status');
                            }
                        });
                    }
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Render the Discord settings page
     */
    public function render_discord_settings_page() {
        ?>
        <div class="wrap">
            <h1>💬 Discord Notifications</h1>
            
            <div class="card" style="max-width: 800px; margin-bottom: 20px;">
                <h2>🎯 Real-Time Payment & Activity Notifications</h2>
                <p>Get instant Discord notifications for all critical events happening on your site:</p>
                
                <div style="background: #f0f0f0; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <h3>📋 What You'll Get Notified About:</h3>
                    <ul style="margin-left: 20px;">
                        <li><strong>🎉 New Member Signups</strong> - When someone subscribes to your site</li>
                        <li><strong>💰 Subscription Rebills</strong> - Successful recurring payments</li>
                        <li><strong>❌ Subscription Cancellations</strong> - When members cancel</li>
                        <li><strong>⏰ Subscription Expirations</strong> - When memberships expire</li>
                        <li><strong>🎬 PPV Purchases</strong> - Pay-per-view episode purchases</li>
                        <li><strong>⚠️ Refunds & Chargebacks</strong> - Payment issues and disputes</li>
                        <li><strong>🌟 Talent Applications</strong> - New performer applications</li>
                    </ul>
                </div>
                
                <div style="background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <h3>🔧 How to Set Up Discord Webhooks:</h3>
                    <ol style="margin-left: 20px;">
                        <li><strong>Go to your Discord server</strong> → Server Settings → Integrations</li>
                        <li><strong>Click "Create Webhook"</strong> in the Webhooks section</li>
                        <li><strong>Choose a channel</strong> where you want notifications (e.g., #payments, #notifications)</li>
                        <li><strong>Copy the webhook URL</strong> and paste it in the form below</li>
                        <li><strong>Customize which events</strong> you want to be notified about</li>
                        <li><strong>Test the connection</strong> to make sure everything works</li>
                    </ol>
                </div>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('flexpress_discord_settings');
                do_settings_sections('flexpress_discord_settings');
                submit_button('Save Discord Settings');
                ?>
            </form>
            
            <?php $this->render_discord_preview(); ?>
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
        </script>
        <?php
    }

    /**
     * Render Discord notification preview
     */
    private function render_discord_preview() {
        ?>
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>👀 Notification Preview</h2>
            <p>Here's what your Discord notifications will look like:</p>
            
            <div style="background: #2f3136; color: #dcddde; padding: 20px; border-radius: 8px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 15px 0;">
                <div style="display: flex; align-items: center; margin-bottom: 15px;">
                    <span style="font-size: 28px; margin-right: 12px;">🎉</span>
                    <strong style="color: #ffffff; font-size: 18px;">New Member Signup!</strong>
                </div>
                
                <div style="background: #36393f; padding: 15px; border-radius: 6px; margin: 15px 0;">
                    <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; margin-bottom: 8px;">
                        <div style="color: #b9bbbe; font-weight: bold;">Member:</div>
                        <div>John Doe</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; margin-bottom: 8px;">
                        <div style="color: #b9bbbe; font-weight: bold;">Email:</div>
                        <div>john@example.com</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; margin-bottom: 8px;">
                        <div style="color: #b9bbbe; font-weight: bold;">Amount:</div>
                        <div>USD 29.95</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; margin-bottom: 8px;">
                        <div style="color: #b9bbbe; font-weight: bold;">Type:</div>
                        <div>Recurring</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 120px 1fr; gap: 8px; margin-bottom: 8px;">
                        <div style="color: #b9bbbe; font-weight: bold;">Next Charge:</div>
                        <div>Jan 15, 2025</div>
                    </div>
                </div>
                
                <div style="font-size: 12px; color: #72767d; margin-top: 15px; display: flex; align-items: center;">
                    <img src="<?php echo get_site_icon_url(16); ?>" style="width: 16px; height: 16px; border-radius: 50%; margin-right: 8px;" />
                    <?php echo get_bloginfo('name'); ?> • Flowguard • Just now
                </div>
            </div>
            
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin: 15px 0;">
                <h4 style="margin-top: 0; color: #856404;">💡 Pro Tips:</h4>
                <ul style="margin-bottom: 0; color: #856404;">
                    <li><strong>Create separate channels</strong> for different types of notifications (e.g., #payments, #talent-applications)</li>
                    <li><strong>Use @mentions</strong> in your Discord webhook settings to ping specific team members</li>
                    <li><strong>Set up role-based notifications</strong> so different team members get different types of alerts</li>
                    <li><strong>Test regularly</strong> to ensure notifications are working properly</li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Render Discord config section description
     */
    public function render_discord_config_section() {
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
     * Render Discord webhook URL field
     */
    public function render_discord_webhook_url_field() {
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
     * Render Discord financial webhook URL field
     */
    public function render_discord_webhook_url_financial_field() {
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
     * Render Discord contact webhook URL field
     */
    public function render_discord_webhook_url_contact_field() {
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
     * Render Discord test connection field
     */
    public function render_discord_test_connection_field() {
        ?>
        <button type="button" 
                onclick="testDiscordConnection()" 
                class="button button-secondary">Test Discord Connection</button>
        <div id="discord-test-results" style="margin-top: 10px;"></div>
        <p class="description">Test your Discord webhook connection by sending a test notification.</p>
        <?php
    }

    /**
     * Render Discord notifications section description
     */
    public function render_discord_notifications_section() {
        echo '<p>Choose which events should trigger Discord notifications. All notifications include rich embeds with detailed information.</p>';
    }

    /**
     * Render Discord notify subscriptions field
     */
    public function render_discord_notify_subscriptions_field() {
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
     * Render Discord notify rebills field
     */
    public function render_discord_notify_rebills_field() {
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
     * Render Discord notify cancellations field
     */
    public function render_discord_notify_cancellations_field() {
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
     * Render Discord notify expirations field
     */
    public function render_discord_notify_expirations_field() {
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
     * Render Discord notify PPV field
     */
    public function render_discord_notify_ppv_field() {
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
     * Render Discord notify refunds field
     */
    public function render_discord_notify_refunds_field() {
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
     * Render Discord notify extensions field
     */
    public function render_discord_notify_extensions_field() {
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
     * Render Discord notify talent applications field
     */
    public function render_discord_notify_talent_applications_field() {
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
     * Render the Turnstile settings page
     */
    public function render_turnstile_settings_page() {
        // This method will be handled by the FlexPress_Turnstile_Settings class
        // We just need to include the class file
        if (class_exists('FlexPress_Turnstile_Settings')) {
            $turnstile_settings = new FlexPress_Turnstile_Settings();
            $turnstile_settings->render_turnstile_settings_page();
        } else {
            echo '<div class="wrap"><h1>Turnstile Settings</h1><p>Turnstile settings class not found.</p></div>';
        }
    }

    /**
     * Render the Plunk settings page
     */
    public function render_plunk_settings_page() {
        // This method will be handled by the FlexPress_Plunk_Settings class
        // We just need to include the class file
        if (class_exists('FlexPress_Plunk_Settings')) {
            $plunk_settings = new FlexPress_Plunk_Settings();
            $plunk_settings->render_plunk_settings_page();
        } else {
            echo '<div class="wrap"><h1>Plunk Settings</h1><p>Plunk settings class not found.</p></div>';
        }
    }

    /**
     * Render the Google SMTP settings page
     */
    public function render_google_smtp_settings_page() {
        // This method will be handled by the FlexPress_Google_SMTP_Settings class
        // We just need to include the class file
        if (class_exists('FlexPress_Google_SMTP_Settings')) {
            $google_smtp_settings = new FlexPress_Google_SMTP_Settings();
            $google_smtp_settings->render_google_smtp_settings_page();
        } else {
            echo '<div class="wrap"><h1>Google SMTP Settings</h1><p>Google SMTP settings class not found.</p></div>';
        }
    }
    
    /**
     * Render the SMTP2Go settings page
     */
    public function render_smtp2go_settings_page() {
        // This method will be handled by the FlexPress_SMTP2Go_Settings class
        // We just need to include the class file
        if (class_exists('FlexPress_SMTP2Go_Settings')) {
            $smtp2go_settings = new FlexPress_SMTP2Go_Settings();
            $smtp2go_settings->render_smtp2go_settings_page();
        } else {
            echo '<div class="wrap"><h1>SMTP2Go Settings</h1><p>SMTP2Go settings class not found.</p></div>';
        }
    }
}

// Initialize the settings page only in admin
if (is_admin()) {
    new FlexPress_Settings();
} 