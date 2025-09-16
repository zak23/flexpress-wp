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
        add_action('admin_menu', array($this, 'add_settings_page'));
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
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Register the general settings option
        register_setting('flexpress_general_settings', 'flexpress_general_settings', array(
            'sanitize_callback' => 'flexpress_sanitize_general_settings'
        ));
        
        // Register auto-setup settings
        register_setting('flexpress_auto_setup_settings', 'flexpress_disable_auto_setup');
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
}

// Initialize the settings page only in admin
if (is_admin()) {
    new FlexPress_Settings();
} 