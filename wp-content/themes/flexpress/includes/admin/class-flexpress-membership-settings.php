<?php
/**
 * FlexPress Membership Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Membership Settings Class
 */
class FlexPress_Membership_Settings {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_membership_settings_page'), 20); // Higher priority
        add_action('admin_init', array($this, 'register_membership_settings'));
        add_action('show_user_profile', array($this, 'add_verotel_user_fields'));
        add_action('edit_user_profile', array($this, 'add_verotel_user_fields'));
        add_action('personal_options_update', array($this, 'save_verotel_user_fields'));
        add_action('edit_user_profile_update', array($this, 'save_verotel_user_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_post_sync_episode_dates', array($this, 'handle_sync_episode_dates'));
        add_action('admin_post_flexpress_clear_debug_log', array($this, 'handle_clear_debug_log'));
        add_action('wp_ajax_delete_member_user', array($this, 'handle_delete_member_user'));
        add_action('wp_ajax_add_purchased_episode', array($this, 'handle_add_purchased_episode'));
        add_action('wp_ajax_remove_purchased_episode', array($this, 'handle_remove_purchased_episode'));
    }

    /**
     * Add the membership settings page to admin menu
     */
    public function add_membership_settings_page() {
        add_submenu_page(
            'flexpress-settings',
            __('Manage Members', 'flexpress'),
            __('Manage Members', 'flexpress'),
            'edit_users',
            'flexpress-manage-members',
            array($this, 'render_members_page')
        );
        
        add_submenu_page(
            'flexpress-settings',
            __('Tools', 'flexpress'),
            __('Tools', 'flexpress'),
            'edit_users',
            'flexpress-tools',
            array($this, 'render_tools_page')
        );
    }

    /**
     * Register settings
     */
    public function register_membership_settings() {
        register_setting('flexpress_membership_settings', 'flexpress_membership_settings');

        add_settings_section(
            'flexpress_membership_general_section',
            __('Flexpay General Settings', 'flexpress'),
            array($this, 'render_membership_general_section'),
            'flexpress_membership_settings'
        );
        
        add_settings_field(
            'verotel_enabled',
            __('Enable Verotel Integration', 'flexpress'),
            array($this, 'render_verotel_enabled_field'),
            'flexpress_membership_settings',
            'flexpress_membership_general_section'
        );
        
        add_settings_field(
            'verotel_site_id',
            __('Verotel Site ID', 'flexpress'),
            array($this, 'render_verotel_site_id_field'),
            'flexpress_membership_settings',
            'flexpress_membership_general_section'
        );
        
        add_settings_field(
            'verotel_api_token',
            __('Verotel API Token', 'flexpress'),
            array($this, 'render_verotel_api_token_field'),
            'flexpress_membership_settings',
            'flexpress_membership_general_section'
        );
    }

    /**
     * Render the manage members page
     */
    public function render_members_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Manage Members', 'flexpress'); ?></h1>
            <?php $this->render_members_list(); ?>
        </div>
        <?php
    }

    /**
     * Render the tools page
     */
    public function render_tools_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Tools', 'flexpress'); ?></h1>
            <?php $this->render_tools_tab(); ?>
        </div>
        <?php
    }

    /**
     * Render the membership general section
     */
    public function render_membership_general_section() {
        echo '<p>' . esc_html__('Configure general Flexpay integration settings.', 'flexpress') . '</p>';
    }

    /**
     * Render the Verotel enabled field
     */
    public function render_verotel_enabled_field() {
        $options = get_option('flexpress_membership_settings', array());
        $verotel_enabled = isset($options['verotel_enabled']) ? $options['verotel_enabled'] : 0;
        ?>
        <label for="flexpress_membership_settings[verotel_enabled]">
            <input type="checkbox" id="flexpress_membership_settings[verotel_enabled]" name="flexpress_membership_settings[verotel_enabled]" value="1" <?php checked(1, $verotel_enabled); ?> />
            <?php esc_html_e('Enable Verotel integration', 'flexpress'); ?>
        </label>
        <?php
    }

    /**
     * Render the Verotel Site ID field
     */
    public function render_verotel_site_id_field() {
        $options = get_option('flexpress_membership_settings', array());
        $verotel_site_id = isset($options['verotel_site_id']) ? $options['verotel_site_id'] : '';
        ?>
        <input type="text" id="flexpress_membership_settings[verotel_site_id]" name="flexpress_membership_settings[verotel_site_id]" value="<?php echo esc_attr($verotel_site_id); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Enter your Verotel Site ID.', 'flexpress'); ?></p>
        <?php
    }

    /**
     * Render the Verotel API Token field
     */
    public function render_verotel_api_token_field() {
        $options = get_option('flexpress_membership_settings', array());
        $verotel_api_token = isset($options['verotel_api_token']) ? $options['verotel_api_token'] : '';
        ?>
        <input type="password" id="flexpress_membership_settings[verotel_api_token]" name="flexpress_membership_settings[verotel_api_token]" value="<?php echo esc_attr($verotel_api_token); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Enter your Verotel API token.', 'flexpress'); ?></p>
        <?php
    }



    /**
     * Render tools tab
     */
    private function render_tools_tab() {
        // Display success message if dates were synced
        if (isset($_GET['dates_synced']) && isset($_GET['count'])) {
            $count = intval($_GET['count']);
            $errors = isset($_GET['errors']) ? intval($_GET['errors']) : 0;
            $direction = isset($_GET['direction']) ? $_GET['direction'] : 'wp_to_acf';
            $direction_text = $direction === 'wp_to_acf' 
                ? __('WordPress dates to custom fields', 'flexpress')
                : __('custom fields to WordPress dates', 'flexpress');
            
            if ($errors > 0) {
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p>
                        <?php printf(esc_html__('Synchronized %s for %d episodes with %d errors. Check the server error log for details.', 'flexpress'), $direction_text, $count, $errors); ?>
                    </p>
                </div>
                <?php
            } else {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php printf(esc_html__('Successfully synchronized %s for %d episodes.', 'flexpress'), $direction_text, $count); ?></p>
                </div>
                <?php
            }
        }

        // Display notices for debug log clear action
        if (isset($_GET['debug_cleared']) && $_GET['debug_cleared'] === '1') {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('debug.log has been cleared.', 'flexpress'); ?></p>
            </div>
            <?php
        } elseif (isset($_GET['debug_error'])) {
            $error_code = sanitize_text_field($_GET['debug_error']);
            $message = __('Failed to clear debug.log. Please check file permissions.', 'flexpress');
            if ($error_code === 'not_found') {
                $message = __('debug.log not found in wp-content/.', 'flexpress');
            } elseif ($error_code === 'permission') {
                $message = __('Insufficient permissions to clear debug.log.', 'flexpress');
            }
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
            <?php
        }
        
        // Check if create test user button was clicked
        if (isset($_POST['create_test_user']) && current_user_can('create_users')) {
            check_admin_referer('create_test_user', 'test_user_nonce');
            
            $result = $this->create_test_user();
            
            if (is_wp_error($result)) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result->get_error_message()) . '</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Test user created successfully with ID: ', 'flexpress') . $result . '</p></div>';
            }
        }
        
        // Episode Date Synchronization tool
        ?>
        <div class="card">
            <h2 class="title"><?php esc_html_e('Episode Date Synchronization', 'flexpress'); ?></h2>
            <div class="inside">
                <p><?php esc_html_e('Use this tool to synchronize dates between WordPress post dates and ACF release dates for all episodes.', 'flexpress'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('sync_episode_dates_nonce', 'sync_dates_nonce'); ?>
                    <input type="hidden" name="action" value="sync_episode_dates">
                    
                    <p>
                        <label>
                            <input type="radio" name="sync_direction" value="wp_to_acf" checked>
                            <?php esc_html_e('Update ACF release dates to match WordPress post dates', 'flexpress'); ?>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="radio" name="sync_direction" value="acf_to_wp">
                            <?php esc_html_e('Update WordPress post dates to match ACF release dates', 'flexpress'); ?>
                        </label>
                    </p>
                    
                    <p class="submit">
                        <?php submit_button(__('Synchronize Episode Dates', 'flexpress'), 'primary', 'sync_dates', false); ?>
                    </p>
                </form>
            </div>
        </div>

        <!-- Clear debug.log Tool -->
        <div class="card" style="margin-top: 20px;">
            <h2 class="title"><?php esc_html_e('Clear debug.log', 'flexpress'); ?></h2>
            <div class="inside">
                <?php
                $debug_log_path = WP_CONTENT_DIR . '/debug.log';
                $debug_exists = file_exists($debug_log_path);
                $size_text = $debug_exists ? size_format(filesize($debug_log_path)) : __('N/A', 'flexpress');
                $modified_text = $debug_exists ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($debug_log_path)) : __('N/A', 'flexpress');
                ?>
                <p><?php esc_html_e('This will truncate the WordPress debug log file at wp-content/debug.log.', 'flexpress'); ?></p>
                <ul style="margin-left: 18px; list-style: disc;">
                    <li><strong><?php esc_html_e('Path', 'flexpress'); ?>:</strong> <?php echo esc_html($debug_log_path); ?></li>
                    <li><strong><?php esc_html_e('Current size', 'flexpress'); ?>:</strong> <?php echo esc_html($size_text); ?></li>
                    <li><strong><?php esc_html_e('Last modified', 'flexpress'); ?>:</strong> <?php echo esc_html($modified_text); ?></li>
                </ul>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Are you sure you want to clear wp-content/debug.log? This cannot be undone.');">
                    <?php wp_nonce_field('flexpress_clear_debug_log_action', 'flexpress_clear_debug_log_nonce'); ?>
                    <input type="hidden" name="action" value="flexpress_clear_debug_log">
                    <?php submit_button(__('Clear debug.log', 'flexpress'), 'delete'); ?>
                </form>
            </div>
        </div>
        
        <!-- Test User Creation Tool -->
        <div class="card" style="margin-top: 20px;">
            <h2 class="title"><?php esc_html_e('Create Test User', 'flexpress'); ?></h2>
            <div class="inside">
                <p><?php esc_html_e('This will create a test user with subscription data for testing purposes.', 'flexpress'); ?></p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('create_test_user', 'test_user_nonce'); ?>
                    
                    <p>
                        <label for="test_membership_status"><?php esc_html_e('Membership Status:', 'flexpress'); ?></label>
                        <select name="test_membership_status" id="test_membership_status">
                            <option value="active"><?php esc_html_e('Active', 'flexpress'); ?></option>
                            <option value="cancelled"><?php esc_html_e('Cancelled', 'flexpress'); ?></option>
                            <option value="expired"><?php esc_html_e('Expired', 'flexpress'); ?></option>
                            <option value="banned"><?php esc_html_e('Banned', 'flexpress'); ?></option>
                            <option value="none"><?php esc_html_e('None', 'flexpress'); ?></option>
                        </select>
                    </p>
                    
                    <p>
                        <label for="test_subscription_type"><?php esc_html_e('Subscription Type:', 'flexpress'); ?></label>
                        <select name="test_subscription_type" id="test_subscription_type">
                            <option value="monthly"><?php esc_html_e('Monthly', 'flexpress'); ?></option>
                            <option value="quarterly"><?php esc_html_e('Quarterly', 'flexpress'); ?></option>
                            <option value="annual"><?php esc_html_e('Annual', 'flexpress'); ?></option>
                        </select>
                    </p>
                    
                    <p>
                        <input type="submit" name="create_test_user" class="button button-primary" value="<?php esc_attr_e('Create Test User', 'flexpress'); ?>" />
                    </p>
                </form>
            </div>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2 class="title"><?php esc_html_e('Import/Export Membership Data', 'flexpress'); ?></h2>
            <div class="inside">
                <p><?php esc_html_e('Coming soon: Tools for importing and exporting membership data.', 'flexpress'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Handle Clear debug.log action
     */
    public function handle_clear_debug_log() {
        // Capability check
        if (!current_user_can('manage_options')) {
            wp_redirect(add_query_arg(array(
                'page' => 'flexpress-tools',
                'debug_error' => 'permission'
            ), admin_url('admin.php')));
            exit;
        }

        // Nonce check
        if (!isset($_POST['flexpress_clear_debug_log_nonce']) || !wp_verify_nonce($_POST['flexpress_clear_debug_log_nonce'], 'flexpress_clear_debug_log_action')) {
            wp_redirect(add_query_arg(array(
                'page' => 'flexpress-tools',
                'debug_error' => 'permission'
            ), admin_url('admin.php')));
            exit;
        }

        $log_path = WP_CONTENT_DIR . '/debug.log';

        if (!file_exists($log_path)) {
            wp_redirect(add_query_arg(array(
                'page' => 'flexpress-tools',
                'debug_error' => 'not_found'
            ), admin_url('admin.php')));
            exit;
        }

        $cleared = false;
        // Try truncate via file_put_contents
        $result = @file_put_contents($log_path, '');
        if ($result !== false) {
            $cleared = true;
        } else {
            // Fallback to fopen with write mode (truncates)
            $handle = @fopen($log_path, 'w');
            if ($handle) {
                @fclose($handle);
                $cleared = true;
            } else {
                // Attempt to relax permissions and try again
                @chmod($log_path, 0664);
                $result_retry = @file_put_contents($log_path, '');
                if ($result_retry !== false) {
                    $cleared = true;
                }
            }
        }

        if ($cleared) {
            wp_redirect(add_query_arg(array(
                'page' => 'flexpress-tools',
                'debug_cleared' => '1'
            ), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array(
                'page' => 'flexpress-tools',
                'debug_error' => 'write_failed'
            ), admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * Create a test user with subscription data
     *
     * @return int|WP_Error User ID or error.
     */
    public function create_test_user() {
        // Generate a random username and email
        $random_string = substr(md5(uniqid(mt_rand(), true)), 0, 8);
        $username = 'testuser_' . $random_string;
        $email = 'test_' . $random_string . '@example.com';
        
        // Create the user
        $user_id = wp_create_user($username, wp_generate_password(), $email);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Set user role
        $user = new WP_User($user_id);
        $user->set_role('subscriber');
        
        // Add user meta
        $membership_status = sanitize_text_field($_POST['test_membership_status']);
        $subscription_type = sanitize_text_field($_POST['test_subscription_type']);
        
        update_user_meta($user_id, 'first_name', 'Test');
        update_user_meta($user_id, 'last_name', 'User');
        update_user_meta($user_id, 'membership_status', $membership_status);
        update_user_meta($user_id, 'subscription_type', $subscription_type);
        update_user_meta($user_id, 'subscription_start_date', date('Y-m-d H:i:s'));
        
        // Set next rebill date based on subscription type
        $rebill_date = '';
        switch ($subscription_type) {
            case 'monthly':
                $rebill_date = date('Y-m-d H:i:s', strtotime('+1 month'));
                break;
            case 'quarterly':
                $rebill_date = date('Y-m-d H:i:s', strtotime('+3 months'));
                break;
            case 'annual':
                $rebill_date = date('Y-m-d H:i:s', strtotime('+1 year'));
                break;
        }
        
        update_user_meta($user_id, 'next_rebill_date', $rebill_date);
                    update_user_meta($user_id, 'verotel_transaction_id', 'tx_test_' . $random_string);
        update_user_meta($user_id, 'verotel_transaction_id', 'tx_test_' . $random_string);
        
        return $user_id;
    }

    /**
     * Render the members list
     */
    public function render_members_list() {
        // Handle status refresh request
        if (isset($_GET['refresh_status']) && isset($_GET['user_id']) && current_user_can('edit_users')) {
            $user_id = intval($_GET['user_id']);
            // Force refresh by calling flexpress_get_membership_status which checks trial expiration
            if (function_exists('flexpress_get_membership_status')) {
                flexpress_get_membership_status($user_id);
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Membership status refreshed successfully.', 'flexpress') . '</p></div>';
            }
        }
        
        // Check if we need to update a user
        if (isset($_POST['update_user_membership']) && isset($_POST['user_id']) && current_user_can('edit_users')) {
            check_admin_referer('update_user_membership', 'membership_nonce');
            
            $user_id = intval($_POST['user_id']);
            $membership_status = sanitize_text_field($_POST['membership_status']);
            $subscription_type = sanitize_text_field($_POST['subscription_type']);
            $next_rebill_date = sanitize_text_field($_POST['next_rebill_date']);
            $trial_expires_at = isset($_POST['trial_expires_at']) && !empty($_POST['trial_expires_at']) ? sanitize_text_field($_POST['trial_expires_at']) : '';
            $verotel_subscriber_id = isset($_POST['verotel_subscriber_id']) ? sanitize_text_field($_POST['verotel_subscriber_id']) : '';
            $flowguard_subscriber_id = isset($_POST['flowguard_subscriber_id']) ? sanitize_text_field($_POST['flowguard_subscriber_id']) : '';
            
            // Convert datetime-local to MySQL format if provided
            $old_trial_expires_at = get_user_meta($user_id, 'trial_expires_at', true);
            if (!empty($trial_expires_at)) {
                $trial_expires_at = date('Y-m-d H:i:s', strtotime($trial_expires_at));
            } else {
                // If empty, delete the meta
                delete_user_meta($user_id, 'trial_expires_at');
            }
            
            // Check if trial expiration date was updated and trial is now active
            if (!empty($trial_expires_at)) {
                $expires_timestamp = strtotime($trial_expires_at);
                $expires_with_grace = $expires_timestamp + (1 * DAY_IN_SECONDS);
                $current_status = get_user_meta($user_id, 'membership_status', true);
                
                // If trial expiration is in the future (with grace period) and status is expired, update to active
                if ($expires_with_grace > current_time('timestamp') && $current_status === 'expired') {
                    $membership_status = 'active';
                }
                // If trial expiration is in the past (with grace period) and status is active, update to expired
                elseif ($expires_with_grace < current_time('timestamp') && $current_status === 'active') {
                    $membership_status = 'expired';
                }
            }
            
            // Update user meta
            update_user_meta($user_id, 'membership_status', $membership_status);
            update_user_meta($user_id, 'subscription_type', $subscription_type);
            update_user_meta($user_id, 'next_rebill_date', $next_rebill_date);
            if (!empty($trial_expires_at)) {
                update_user_meta($user_id, 'trial_expires_at', $trial_expires_at);
            }
            update_user_meta($user_id, 'verotel_subscriber_id', $verotel_subscriber_id);
            update_user_meta($user_id, 'flowguard_subscriber_id', $flowguard_subscriber_id);
            
            // Clear user cache to ensure updated status is reflected immediately
            wp_cache_delete($user_id, 'user_meta');
            clean_user_cache($user_id);
            
            // Clear Redis object cache if available
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
            // Force refresh of user meta
            delete_user_meta($user_id, 'membership_status');
            update_user_meta($user_id, 'membership_status', $membership_status);
            
            // Calculate and update subscription start date if it doesn't exist
            $subscription_start = get_user_meta($user_id, 'subscription_start_date', true);
            if (empty($subscription_start) && $membership_status === 'active') {
                update_user_meta($user_id, 'subscription_start_date', date('Y-m-d H:i:s'));
            }
            
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('User membership details updated successfully.', 'flexpress') . '</p></div>';
            echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__('Note: If the user is currently logged in, they may need to refresh their browser or log out and back in to see the updated status.', 'flexpress') . '</p></div>';
        }
        
        // Get all users
        $users = get_users(array(
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
        
        // Get current user to edit if specified
        $edit_user_id = isset($_GET['edit_user']) ? intval($_GET['edit_user']) : 0;
        $editing = false;
        
        if ($edit_user_id > 0) {
            $editing = true;
            $edit_user = get_user_by('id', $edit_user_id);
            
            if (!$edit_user) {
                $editing = false;
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('User not found.', 'flexpress') . '</p></div>';
            }
        }
        
        if ($editing) {
            // Show edit form
            $this->render_user_edit_form($edit_user);
        } else {
            // Show users table
            ?>
            <h2><?php esc_html_e('Manage Members', 'flexpress'); ?></h2>
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get">
                        <input type="hidden" name="page" value="flexpress-manage-members">
                        <select name="status_filter">
                            <option value=""><?php esc_html_e('All statuses', 'flexpress'); ?></option>
                            <option value="active" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'active'); ?>><?php esc_html_e('Active', 'flexpress'); ?></option>
                            <option value="cancelled" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'cancelled'); ?>><?php esc_html_e('Cancelled', 'flexpress'); ?></option>
                            <option value="expired" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'expired'); ?>><?php esc_html_e('Expired', 'flexpress'); ?></option>
                            <option value="banned" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'banned'); ?>><?php esc_html_e('Banned', 'flexpress'); ?></option>
                            <option value="none" <?php selected(isset($_GET['status_filter']) ? $_GET['status_filter'] : '', 'none'); ?>><?php esc_html_e('None', 'flexpress'); ?></option>
                        </select>
                        <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'flexpress'); ?>">
                    </form>
                </div>
                <br class="clear">
            </div>
            
            <table class="wp-list-table widefat fixed striped users">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Name', 'flexpress'); ?></th>
                        <th><?php esc_html_e('Email', 'flexpress'); ?></th>
                        <th><?php esc_html_e('Username', 'flexpress'); ?></th>
                        <th><?php esc_html_e('Membership Status', 'flexpress'); ?></th>
                        <th><?php esc_html_e('Subscription Type', 'flexpress'); ?></th>
                        <th><?php esc_html_e('Next Rebill', 'flexpress'); ?></th>
                        <th><?php esc_html_e('Actions', 'flexpress'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
                    
                    $found_users = false;
                    
                    foreach ($users as $user) {
                        $user_id = $user->ID;
                        $membership_status = get_user_meta($user_id, 'membership_status', true) ?: 'none';
                        
                        // Apply status filter if set
                        if (!empty($status_filter) && $membership_status !== $status_filter) {
                            continue;
                        }
                        
                        $found_users = true;
                        $subscription_type = get_user_meta($user_id, 'subscription_type', true);
                        $next_rebill_date = get_user_meta($user_id, 'next_rebill_date', true);
                        
                        $status_class = '';
                        switch ($membership_status) {
                            case 'active':
                                $status_class = 'status-active';
                                break;
                            case 'cancelled':
                                $status_class = 'status-cancelled';
                                break;
                            case 'expired':
                            case 'banned':
                                $status_class = 'status-expired';
                                break;
                            default:
                                $status_class = 'status-none';
                                break;
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo esc_html($user->user_login); ?></td>
                            <td><span class="membership-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html(ucfirst($membership_status)); ?></span></td>
                            <td><?php echo esc_html($subscription_type); ?></td>
                            <td><?php 
                        if ($next_rebill_date) {
                            // Convert UTC timestamp to site timezone
                            $utc_timestamp = strtotime($next_rebill_date);
                            $site_time = $utc_timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS);
                            echo esc_html(date_i18n(get_option('date_format'), $site_time));
                        } else {
                            echo '—';
                        } 
                    ?></td>
                            <td>
                                <a href="?page=flexpress-manage-members&edit_user=<?php echo esc_attr($user_id); ?>" class="button button-small">
                                    <?php esc_html_e('Edit', 'flexpress'); ?>
                                </a>
                                <button type="button" class="button button-small button-link-delete delete-user-btn" 
                                        data-user-id="<?php echo esc_attr($user_id); ?>" 
                                        data-user-name="<?php echo esc_attr($user->display_name); ?>"
                                        style="margin-left: 5px;">
                                    <?php esc_html_e('Delete', 'flexpress'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php
                    }
                    
                    if (!$found_users) {
                        ?>
                        <tr>
                            <td colspan="7"><?php esc_html_e('No users found matching the filter criteria.', 'flexpress'); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <?php
        }
    }
    
    /**
     * Render user edit form
     *
     * @param WP_User $user User object.
     */
    public function render_user_edit_form($user) {
        $user_id = $user->ID;
        
        // Use flexpress_get_membership_status() to ensure trial expiration is checked and status is auto-updated
        $membership_status = function_exists('flexpress_get_membership_status') 
            ? flexpress_get_membership_status($user_id) 
            : (get_user_meta($user_id, 'membership_status', true) ?: 'none');
        
        $subscription_type = get_user_meta($user_id, 'subscription_type', true);
        $subscription_start = get_user_meta($user_id, 'subscription_start_date', true);
        $next_rebill_date = get_user_meta($user_id, 'next_rebill_date', true);
        $trial_expires_at = get_user_meta($user_id, 'trial_expires_at', true);
        $verotel_subscriber_id = get_user_meta($user_id, 'verotel_subscriber_id', true);
        $verotel_transaction_id = get_user_meta($user_id, 'verotel_transaction_id', true);
        $flowguard_subscriber_id = get_user_meta($user_id, 'flowguard_subscriber_id', true);
        
        // Check if trial is actually expired (with 1-day grace period)
        $trial_is_expired = false;
        if (!empty($trial_expires_at)) {
            $expires_timestamp = strtotime($trial_expires_at);
            // Add 1 day grace period
            $expires_with_grace = $expires_timestamp + (1 * DAY_IN_SECONDS);
            $trial_is_expired = $expires_with_grace < current_time('timestamp');
        }
        ?>
        <h2><?php echo esc_html(sprintf(__('Edit Membership for %s', 'flexpress'), $user->display_name)); ?></h2>
        
        <p>
            <a href="?page=flexpress-manage-members" class="button"><?php esc_html_e('Back to Members List', 'flexpress'); ?></a>
            <?php if (!empty($trial_expires_at)): ?>
                <a href="<?php echo esc_url(add_query_arg(array('refresh_status' => '1', 'user_id' => $user_id), admin_url('admin.php?page=flexpress-manage-members&edit_user=' . $user_id))); ?>" class="button">
                    <?php esc_html_e('Refresh Status', 'flexpress'); ?>
                </a>
            <?php endif; ?>
        </p>
        
        <?php if ($trial_is_expired && $membership_status === 'active'): ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong><?php esc_html_e('Warning:', 'flexpress'); ?></strong> <?php esc_html_e('This user has an expired trial, but their status is still set to Active. Click "Refresh Status" to automatically update it to Expired.', 'flexpress'); ?></p>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('update_user_membership', 'membership_nonce'); ?>
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
            
            <table class="form-table">
                <tr>
                    <th><label for="membership_status"><?php esc_html_e('Membership Status', 'flexpress'); ?></label></th>
                    <td>
                        <select name="membership_status" id="membership_status">
                            <option value="none" <?php selected($membership_status, 'none'); ?>><?php esc_html_e('None', 'flexpress'); ?></option>
                            <option value="active" <?php selected($membership_status, 'active'); ?>><?php esc_html_e('Active', 'flexpress'); ?></option>
                            <option value="cancelled" <?php selected($membership_status, 'cancelled'); ?>><?php esc_html_e('Cancelled', 'flexpress'); ?></option>
                            <option value="expired" <?php selected($membership_status, 'expired'); ?>><?php esc_html_e('Expired', 'flexpress'); ?></option>
                            <option value="banned" <?php selected($membership_status, 'banned'); ?>><?php esc_html_e('Banned', 'flexpress'); ?></option>
                        </select>
                        <?php if ($trial_is_expired && $membership_status === 'expired'): ?>
                            <p class="description" style="color: #d63638;">
                                <strong><?php esc_html_e('Trial Expired', 'flexpress'); ?></strong> - <?php esc_html_e('Status automatically set to Expired due to trial expiration.', 'flexpress'); ?>
                            </p>
                        <?php elseif (!empty($trial_expires_at) && !$trial_is_expired): ?>
                            <p class="description">
                                <?php 
                                $expires_in = human_time_diff(current_time('timestamp'), strtotime($trial_expires_at));
                                printf(esc_html__('Trial active - expires in %s.', 'flexpress'), $expires_in);
                                ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="subscription_type"><?php esc_html_e('Subscription Type', 'flexpress'); ?></label></th>
                    <td>
                        <?php 
                        $derived_label = function_exists('flexpress_get_user_subscription_type')
                            ? flexpress_get_user_subscription_type($user->ID)
                            : '';
                        if (empty($derived_label)) {
                            $derived_label = 'none';
                        }
                        ?>
                        <strong><?php echo esc_html($derived_label); ?></strong>
                    </td>
                </tr>
                
                <tr>
                    <th><label><?php esc_html_e('Subscription Start Date', 'flexpress'); ?></label></th>
                    <td>
                        <?php 
                    if ($subscription_start) {
                        // Convert UTC timestamp to site timezone
                        $utc_timestamp = strtotime($subscription_start);
                        $site_time = $utc_timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS);
                        echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $site_time));
                    } else {
                        echo esc_html__('Not set', 'flexpress');
                    }
                    ?>
                        <p class="description"><?php esc_html_e('Start date is set automatically when a subscription becomes active.', 'flexpress'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="next_rebill_date"><?php esc_html_e('Next Rebill Date', 'flexpress'); ?></label></th>
                    <td>
                        <input type="date" name="next_rebill_date" id="next_rebill_date" value="<?php echo $next_rebill_date ? esc_attr(date('Y-m-d', strtotime($next_rebill_date))) : ''; ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('The next date when the subscription will be billed.', 'flexpress'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="trial_expires_at"><?php esc_html_e('Trial Expiration Date', 'flexpress'); ?></label></th>
                    <td>
                        <input type="datetime-local" name="trial_expires_at" id="trial_expires_at" value="<?php echo $trial_expires_at ? esc_attr(date('Y-m-d\TH:i', strtotime($trial_expires_at))) : ''; ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('The date and time when the trial period expires. Leave empty if this is not a trial account.', 'flexpress'); ?></p>
                        <?php if ($trial_expires_at): ?>
                            <?php
                            $is_expired = strtotime($trial_expires_at) < current_time('timestamp');
                            $expires_in = human_time_diff(current_time('timestamp'), strtotime($trial_expires_at));
                            ?>
                            <p class="description">
                                <strong><?php echo $is_expired ? esc_html__('Status: Expired', 'flexpress') : sprintf(esc_html__('Status: Active (expires in %s)', 'flexpress'), $expires_in); ?></strong>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="verotel_subscriber_id"><?php esc_html_e('Verotel Subscriber ID', 'flexpress'); ?></label></th>
                    <td>
                        <input type="text" name="verotel_subscriber_id" id="verotel_subscriber_id" value="<?php echo esc_attr($verotel_subscriber_id); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Legacy Verotel subscriber ID (read-only)', 'flexpress'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="flowguard_subscriber_id"><?php esc_html_e('Flowguard Subscriber ID', 'flexpress'); ?></label></th>
                    <td>
                        <input type="text" name="flowguard_subscriber_id" id="flowguard_subscriber_id" value="<?php echo esc_attr($flowguard_subscriber_id); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Flowguard subscriber ID for subscription management', 'flexpress'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th><label><?php esc_html_e('Verotel Transaction ID', 'flexpress'); ?></label></th>
                    <td>
                        <?php echo esc_html($verotel_transaction_id); ?>
                    </td>
                </tr>
                
                <tr>
                    <th><label><?php esc_html_e('Account Created', 'flexpress'); ?></label></th>
                    <td>
                        <?php 
                    // Convert UTC timestamp to site timezone
                    $utc_timestamp = strtotime($user->user_registered);
                    $site_time = $utc_timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS);
                    echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $site_time)); 
                    ?>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="update_user_membership" class="button button-primary" value="<?php esc_attr_e('Update Membership', 'flexpress'); ?>" />
            </p>
        </form>
        
        <!-- Episode Access Management -->
        <?php $this->render_purchased_episodes_section($user); ?>
        
        <!-- User Activity Log - Inline Implementation -->
        <div class="card" style="margin-top: 20px;">
            <h2 class="title"><?php esc_html_e('User Activity Log', 'flexpress'); ?></h2>
            <div class="inside">
                <?php if (class_exists('FlexPress_Activity_Logger')): ?>
                    <?php
                    $activities = FlexPress_Activity_Logger::get_user_activity($user_id, 20);
                    $total_activities = FlexPress_Activity_Logger::get_user_activity_count($user_id);
                    ?>
                    <?php if (empty($activities)): ?>
                        <p><?php esc_html_e('No activity recorded for this user.', 'flexpress'); ?></p>
                    <?php else: ?>
                        <p><?php echo sprintf(esc_html__('Showing latest 20 activities (Total: %d)', 'flexpress'), $total_activities); ?></p>
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Date/Time', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Event Type', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Description', 'flexpress'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td>
                                    <?php
                                    // The activity logger uses current_time('mysql') which already stores local time
                                    // So we don't need to add the GMT offset - just use the timestamp as-is
                                    $local_timestamp = strtotime($activity->created_at);
                                    
                                    // Get date and time formats, with fallbacks
                                    $date_format = get_option('date_format');
                                    if (empty($date_format)) $date_format = 'F j, Y';
                                    
                                    $time_format = get_option('time_format');
                                    if (empty($time_format)) $time_format = 'g:i a';
                                    ?>
                                    <strong><?php echo esc_html(date_i18n($date_format, $local_timestamp)); ?></strong><br>
                                    <small><?php echo esc_html(date_i18n($time_format, $local_timestamp)); ?></small>
                                </td>
                                        <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $activity->event_type))); ?></td>
                                        <td><?php echo esc_html($activity->event_description); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="color: red;"><?php esc_html_e('FlexPress_Activity_Logger class not found.', 'flexpress'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render user activity log
     *
     * @param int $user_id User ID.
     */
    public function render_user_activity_log($user_id) {
        if (!class_exists('FlexPress_Activity_Logger')) {
            ?>
            <div class="card" style="margin-top: 20px;">
                <h2 class="title"><?php esc_html_e('User Activity Log', 'flexpress'); ?></h2>
                <div class="inside">
                    <p style="color: red;"><?php esc_html_e('FlexPress_Activity_Logger class not found.', 'flexpress'); ?></p>
                </div>
            </div>
            <?php
            return;
        }
        
        // Get activity logs for this user
        $activities = FlexPress_Activity_Logger::get_user_activity($user_id, 20);
        $total_activities = FlexPress_Activity_Logger::get_user_activity_count($user_id);
        
        ?>
        <div class="card" style="margin-top: 20px;">
            <h2 class="title"><?php esc_html_e('User Activity Log', 'flexpress'); ?></h2>
            <div class="inside">
                <?php if (empty($activities)): ?>
                    <p><?php esc_html_e('No activity recorded for this user.', 'flexpress'); ?></p>
                <?php else: ?>
                    <p><?php echo sprintf(esc_html__('Showing latest 20 activities (Total: %d)', 'flexpress'), $total_activities); ?></p>
                    
                    <table class="wp-list-table widefat striped activity-log-table">
                        <thead>
                            <tr>
                                <th style="width: 140px;"><?php esc_html_e('Date/Time', 'flexpress'); ?></th>
                                <th style="width: 110px;"><?php esc_html_e('Event Type', 'flexpress'); ?></th>
                                <th style="width: auto;"><?php esc_html_e('Description', 'flexpress'); ?></th>
                                <th style="width: 100px;"><?php esc_html_e('IP Address', 'flexpress'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td>
                                        <?php
                                        // The activity logger uses current_time('mysql') which already stores local time
                                        // So we don't need to add the GMT offset - just use the timestamp as-is
                                        $local_timestamp = strtotime($activity->created_at);
                                        
                                        // Get date and time formats, with fallbacks to ensure time is always shown
                                        $date_format = get_option('date_format');
                                        if (empty($date_format)) $date_format = 'F j, Y';
                                        
                                        $time_format = get_option('time_format');
                                        if (empty($time_format)) $time_format = 'g:i a';
                                        
                                        ?>
                                        <strong><?php echo esc_html(date_i18n($date_format, $local_timestamp)); ?></strong><br>
                                        <small><?php echo esc_html(date_i18n($time_format, $local_timestamp)); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $event_class = 'activity-' . str_replace('_', '-', $activity->event_type);
                                        $event_label = $this->get_event_type_label($activity->event_type);
                                        ?>
                                        <span class="activity-badge <?php echo esc_attr($event_class); ?>">
                                            <?php echo esc_html($event_label); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><strong><?php echo esc_html($activity->event_description); ?></strong></div>
                                        <?php if (!empty($activity->event_data)): ?>
                                            <div class="activity-details">
                                                <?php $this->render_activity_details($activity->event_data); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo esc_html($activity->ip_address ?: 'N/A'); ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($total_activities > 20): ?>
                        <p style="margin-top: 10px;">
                            <em><?php echo sprintf(esc_html__('Showing latest 20 of %d total activities.', 'flexpress'), $total_activities); ?></em>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .activity-log-table {
            table-layout: auto !important;
            width: 100% !important;
        }
        
        .activity-log-table th,
        .activity-log-table td {
            word-wrap: break-word;
            vertical-align: top;
            padding: 8px 10px;
        }
        
        .activity-log-table th:nth-child(1),
        .activity-log-table td:nth-child(1) {
            width: 140px;
            min-width: 140px;
        }
        
        .activity-log-table th:nth-child(2),
        .activity-log-table td:nth-child(2) {
            width: 110px;
            min-width: 110px;
            text-align: center;
        }
        
        .activity-log-table th:nth-child(3),
        .activity-log-table td:nth-child(3) {
            width: auto;
            min-width: 200px;
        }
        
        .activity-log-table th:nth-child(4),
        .activity-log-table td:nth-child(4) {
            width: 100px;
            min-width: 100px;
            text-align: center;
        }
        
        .activity-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: white;
            white-space: nowrap;
        }
        
        .activity-verotel-initial { background-color: #28a745; }
        .activity-verotel-rebill { background-color: #17a2b8; }
        .activity-verotel-cancellation { background-color: #dc3545; }
        .activity-verotel-uncancel { background-color: #28a745; }
        .activity-verotel-extend { background-color: #ffc107; color: #212529; }
        .activity-verotel-expiration { background-color: #6c757d; }
        .activity-verotel-downgrade { background-color: #fd7e14; }
        .activity-verotel-upgrade { background-color: #20c997; }
        .activity-verotel-credit { background-color: #6f42c1; }
        .activity-verotel-chargeback { background-color: #e83e8c; }
        .activity-user-registered { background-color: #007cba; }
        .activity-membership-status-change { background-color: #00a0d2; }
        .activity-ppv-purchase { background-color: #28a745; }
        .activity-ppv-purchase-confirmed { background-color: #20c997; }
        .activity-verotel-unknown { background-color: #6c757d; }
        
        .activity-details {
            font-size: 12px;
            color: #666;
            background: #f8f9fa;
            padding: 5px 8px;
            border-radius: 3px;
            border-left: 3px solid #007cba;
            margin-top: 5px;
            word-wrap: break-word;
        }
        
        .activity-details strong {
            color: #333;
        }
        </style>
        <?php
    }
    
    /**
     * Get human-readable label for event type
     *
     * @param string $event_type Event type.
     * @return string Human-readable label.
     */
    private function get_event_type_label($event_type) {
        $labels = array(
            'verotel_initial' => 'New Subscription',
            'verotel_rebill' => 'Rebill',
            'verotel_cancellation' => 'Cancelled',
            'verotel_uncancel' => 'Reactivated',
            'verotel_extend' => 'Extended',
            'verotel_expiration' => 'Expired',
            'verotel_downgrade' => 'Downgraded',
            'verotel_upgrade' => 'Upgraded',
            'verotel_credit' => 'Refund',
            'verotel_chargeback' => 'Chargeback',
            'user_registered' => 'Registered',
            'membership_status_change' => 'Status Change',
            'verotel_unknown' => 'Unknown Event'
        );
        
        return isset($labels[$event_type]) ? $labels[$event_type] : ucfirst(str_replace('_', ' ', $event_type));
    }
    
    /**
     * Render activity details
     *
     * @param array $event_data Event data.
     */
    private function render_activity_details($event_data) {
        if (empty($event_data) || !is_array($event_data)) {
            return;
        }
        
        $important_fields = array(
            'priceAmount' => 'Amount',
            'priceCurrency' => 'Currency',
            'nextChargeOn' => 'Next Charge',
            'transactionID' => 'Transaction ID',
            'paymentMethod' => 'Payment Method',
            'period' => 'Period',
            'subscriptionType' => 'Subscription Type',
            'selected_plan' => 'Selected Plan',
            'old_status' => 'From Status',
            'new_status' => 'To Status',
            'reason' => 'Reason'
        );
        
        $details = array();
        foreach ($important_fields as $key => $label) {
            if (isset($event_data[$key]) && !empty($event_data[$key])) {
                $value = $event_data[$key];
                if ($key === 'nextChargeOn') {
                    // Convert UTC timestamp to site timezone
                    $utc_timestamp = strtotime($value);
                    $site_time = $utc_timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS);
                    $value = date_i18n(get_option('date_format'), $site_time);
                }
                $details[] = "<strong>{$label}:</strong> {$value}";
            }
        }
        
        if (!empty($details)) {
            echo implode(' • ', $details);
        }
    }
    
    /**
     * Add Verotel user fields to user profile
     *
     * @param WP_User $user User object.
     */
    public function add_verotel_user_fields($user) {
        if (!current_user_can('edit_users')) {
            return;
        }
        
        $membership_status = get_user_meta($user->ID, 'membership_status', true) ?: 'none';
        $subscription_type = get_user_meta($user->ID, 'subscription_type', true);
        $subscription_start = get_user_meta($user->ID, 'subscription_start_date', true);
        $next_rebill_date = get_user_meta($user->ID, 'next_rebill_date', true);
        $trial_expires_at = get_user_meta($user->ID, 'trial_expires_at', true);
        $verotel_subscriber_id = get_user_meta($user->ID, 'verotel_subscriber_id', true);
        $verotel_transaction_id = get_user_meta($user->ID, 'verotel_transaction_id', true);
        $flowguard_subscriber_id = get_user_meta($user->ID, 'flowguard_subscriber_id', true);
        ?>
        <h2><?php esc_html_e('Membership Information', 'flexpress'); ?></h2>
        <table class="form-table">
            <tr>
                <th><label for="membership_status"><?php esc_html_e('Membership Status', 'flexpress'); ?></label></th>
                <td>
                    <select name="membership_status" id="membership_status">
                        <option value="none" <?php selected($membership_status, 'none'); ?>><?php esc_html_e('None', 'flexpress'); ?></option>
                        <option value="active" <?php selected($membership_status, 'active'); ?>><?php esc_html_e('Active', 'flexpress'); ?></option>
                        <option value="cancelled" <?php selected($membership_status, 'cancelled'); ?>><?php esc_html_e('Cancelled', 'flexpress'); ?></option>
                        <option value="expired" <?php selected($membership_status, 'expired'); ?>><?php esc_html_e('Expired', 'flexpress'); ?></option>
                        <option value="banned" <?php selected($membership_status, 'banned'); ?>><?php esc_html_e('Banned', 'flexpress'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="subscription_type"><?php esc_html_e('Subscription Type', 'flexpress'); ?></label></th>
                <td>
                    <?php 
                    $derived_label = function_exists('flexpress_get_user_subscription_type')
                        ? flexpress_get_user_subscription_type($user_id)
                        : '';
                    if (empty($derived_label)) {
                        $derived_label = 'none';
                    }
                    ?>
                    <strong><?php echo esc_html($derived_label); ?></strong>
                </td>
            </tr>
            
            <tr>
                <th><label for="next_rebill_date"><?php esc_html_e('Next Rebill Date', 'flexpress'); ?></label></th>
                <td>
                    <input type="date" name="next_rebill_date" id="next_rebill_date" value="<?php echo $next_rebill_date ? esc_attr(date('Y-m-d', strtotime($next_rebill_date))) : ''; ?>" class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th><label for="trial_expires_at"><?php esc_html_e('Trial Expiration Date', 'flexpress'); ?></label></th>
                <td>
                    <input type="datetime-local" name="trial_expires_at" id="trial_expires_at" value="<?php echo $trial_expires_at ? esc_attr(date('Y-m-d\TH:i', strtotime($trial_expires_at))) : ''; ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e('The date and time when the trial period expires. Leave empty if this is not a trial account.', 'flexpress'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="verotel_subscriber_id"><?php esc_html_e('Verotel Subscriber ID', 'flexpress'); ?></label></th>
                <td>
                    <input type="text" name="verotel_subscriber_id" id="verotel_subscriber_id" value="<?php echo esc_attr($verotel_subscriber_id); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e('Legacy Verotel subscriber ID', 'flexpress'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="flowguard_subscriber_id"><?php esc_html_e('Flowguard Subscriber ID', 'flexpress'); ?></label></th>
                <td>
                    <input type="text" name="flowguard_subscriber_id" id="flowguard_subscriber_id" value="<?php echo esc_attr($flowguard_subscriber_id); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e('Flowguard subscriber ID for subscription management', 'flexpress'); ?></p>
                </td>
            </tr>

        </table>
        
        <!-- Purchased Episodes Management -->
        <h2><?php esc_html_e('Purchased Episodes', 'flexpress'); ?></h2>
        <?php $this->render_purchased_episodes_section($user); ?>
        <?php
    }
    
    /**
     * Save Verotel user fields
     *
     * @param int $user_id User ID.
     */
    public function save_verotel_user_fields($user_id) {
        if (!current_user_can('edit_users')) {
            return;
        }
        
        update_user_meta($user_id, 'membership_status', sanitize_text_field($_POST['membership_status']));
        update_user_meta($user_id, 'subscription_type', sanitize_text_field($_POST['subscription_type']));
        update_user_meta($user_id, 'next_rebill_date', sanitize_text_field($_POST['next_rebill_date']));
        
        // Handle trial expiration date
        $old_trial_expires_at = get_user_meta($user_id, 'trial_expires_at', true);
        $trial_expires_at = isset($_POST['trial_expires_at']) && !empty($_POST['trial_expires_at']) ? sanitize_text_field($_POST['trial_expires_at']) : '';
        if (!empty($trial_expires_at)) {
            $trial_expires_at = date('Y-m-d H:i:s', strtotime($trial_expires_at));
            update_user_meta($user_id, 'trial_expires_at', $trial_expires_at);
            
            // Check if trial expiration date was updated and automatically update status
            $expires_timestamp = strtotime($trial_expires_at);
            $expires_with_grace = $expires_timestamp + (1 * DAY_IN_SECONDS);
            $current_status = get_user_meta($user_id, 'membership_status', true);
            
            // If trial expiration is in the future (with grace period) and status is expired, update to active
            if ($expires_with_grace > current_time('timestamp') && $current_status === 'expired') {
                update_user_meta($user_id, 'membership_status', 'active');
            }
            // If trial expiration is in the past (with grace period) and status is active, update to expired
            elseif ($expires_with_grace < current_time('timestamp') && $current_status === 'active') {
                update_user_meta($user_id, 'membership_status', 'expired');
            }
        } else {
            delete_user_meta($user_id, 'trial_expires_at');
        }
        
        update_user_meta($user_id, 'verotel_subscriber_id', sanitize_text_field($_POST['verotel_subscriber_id']));
        update_user_meta($user_id, 'flowguard_subscriber_id', sanitize_text_field($_POST['flowguard_subscriber_id']));
        
        // Clear user cache to ensure updated status is reflected immediately
        wp_cache_delete($user_id, 'user_meta');
        clean_user_cache($user_id);
        
        // Clear Redis object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Force refresh of user meta
        delete_user_meta($user_id, 'membership_status');
        update_user_meta($user_id, 'membership_status', sanitize_text_field($_POST['membership_status']));
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only enqueue on our settings pages
        if (strpos($hook, 'flexpress-manage-members') === false && strpos($hook, 'flexpress-tools') === false && $hook != 'user-edit.php' && $hook != 'profile.php') {
            return;
        }
        
        wp_enqueue_style('flexpress-admin', FLEXPRESS_URL . '/assets/css/admin.css', array(), FLEXPRESS_VERSION);
        
        // Enqueue JavaScript for delete functionality on members tab
        if (strpos($hook, 'flexpress-manage-members') !== false) {
            wp_enqueue_script('flexpress-membership-admin', FLEXPRESS_URL . '/assets/js/membership-admin.js', array('jquery'), FLEXPRESS_VERSION, true);
            
            // Localize script with AJAX URL and nonce
            wp_localize_script('flexpress-membership-admin', 'flexpressMembershipAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'deleteUserNonce' => wp_create_nonce('flexpress_delete_user_nonce'),
                'confirmDeleteMessage' => __('Are you sure you want to delete this user? This action cannot be undone.', 'flexpress'),
                'deletingMessage' => __('Deleting user...', 'flexpress'),
                'deleteSuccessMessage' => __('User deleted successfully.', 'flexpress'),
                'deleteErrorMessage' => __('Error deleting user. Please try again.', 'flexpress')
            ));
        }
    }

    /**
     * Handle episode date synchronization
     */
    public function handle_sync_episode_dates() {
        // Verify nonce and user capabilities
        if (!isset($_POST['sync_dates_nonce']) || !wp_verify_nonce($_POST['sync_dates_nonce'], 'sync_episode_dates_nonce') || !current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'flexpress'));
        }
        
        // Get all episodes
        $episodes = get_posts(array(
            'post_type' => 'episode',
            'posts_per_page' => -1
        ));
        
        $count = 0;
        $errors = 0;
        $sync_direction = isset($_POST['sync_direction']) ? sanitize_text_field($_POST['sync_direction']) : 'wp_to_acf';
        
        foreach ($episodes as $episode) {
            if ($sync_direction === 'wp_to_acf') {
                // Update ACF release date to match WordPress post date
                update_field('release_date', $episode->post_date, $episode->ID);
                $count++;
            } else {
                // Update WordPress post date to match ACF release date
                $release_date = get_field('release_date', $episode->ID);
                
                if (!empty($release_date)) {
                    // Parse date more carefully
                    $timestamp = false;
                    
                    // Try different date format interpretations
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2} [ap]m$/i', $release_date)) {
                        // Format like "23/03/2025 12:00 am"
                        $date_parts = explode(' ', $release_date);
                        $date_numbers = explode('/', $date_parts[0]);
                        $time_parts = explode(':', $date_parts[1]);
                        
                        // Convert to yyyy-mm-dd format
                        $formatted_date = sprintf(
                            '%04d-%02d-%02d %02d:%02d:00',
                            intval($date_numbers[2]), // Year
                            intval($date_numbers[1]), // Month
                            intval($date_numbers[0]), // Day
                            $date_parts[2] === 'pm' && $time_parts[0] != 12 ? intval($time_parts[0]) + 12 : 
                               ($date_parts[2] === 'am' && $time_parts[0] == 12 ? 0 : intval($time_parts[0])), // Hour
                            intval($time_parts[1])  // Minute
                        );
                    } else {
                        // Try standard strtotime conversion
                        $timestamp = strtotime($release_date);
                        if ($timestamp) {
                            $formatted_date = date('Y-m-d H:i:s', $timestamp);
                        } else {
                            // Log error
                            error_log("Failed to parse date for episode #{$episode->ID}: {$release_date}");
                            $errors++;
                            continue;
                        }
                    }
                    
                    // Only update if we have a valid date and it's different from the current post date
                    if (!empty($formatted_date) && $formatted_date !== $episode->post_date) {
                        $result = wp_update_post(array(
                            'ID' => $episode->ID,
                            'post_date' => $formatted_date,
                            'post_date_gmt' => get_gmt_from_date($formatted_date)
                        ));
                        
                        if ($result) {
                            $count++;
                        } else {
                            $errors++;
                            error_log("Failed to update post date for episode #{$episode->ID}");
                        }
                    }
                }
            }
        }
        
        // Redirect back with message
        wp_redirect(add_query_arg(array(
            'page' => 'flexpress-tools',
            'dates_synced' => '1',
            'count' => $count,
            'errors' => $errors,
            'direction' => $sync_direction
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Handle AJAX request to delete a member user
     */
    public function handle_delete_member_user() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'flexpress_delete_user_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'flexpress')));
            return;
        }

        // Check user capabilities
        if (!current_user_can('delete_users')) {
            wp_send_json_error(array('message' => __('You do not have permission to delete users.', 'flexpress')));
            return;
        }

        // Get user ID
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Invalid user ID.', 'flexpress')));
            return;
        }

        // Get user data before deletion for logging
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found.', 'flexpress')));
            return;
        }

        // Prevent deletion of current user
        if ($user_id === get_current_user_id()) {
            wp_send_json_error(array('message' => __('You cannot delete your own account.', 'flexpress')));
            return;
        }

        // Prevent deletion of admin users (safety check)
        if (user_can($user_id, 'manage_options')) {
            wp_send_json_error(array('message' => __('Cannot delete administrator users.', 'flexpress')));
            return;
        }

        // Get membership data for logging
        $membership_status = get_user_meta($user_id, 'membership_status', true);
        $subscription_type = get_user_meta($user_id, 'subscription_type', true);
        $verotel_subscriber_id = get_user_meta($user_id, 'verotel_subscriber_id', true);

        // Log the deletion
        error_log(sprintf(
            'FlexPress: User deleted via admin - ID: %d, Name: %s, Email: %s, Membership: %s, Subscription: %s, Verotel ID: %s',
            $user_id,
            $user->display_name,
            $user->user_email,
            $membership_status ?: 'none',
            $subscription_type ?: 'none',
            $verotel_subscriber_id ?: 'none'
        ));

        // Delete the user (this will also delete all user meta)
        $deleted = wp_delete_user($user_id);

        if ($deleted) {
            wp_send_json_success(array(
                'message' => sprintf(__('User "%s" has been successfully deleted.', 'flexpress'), $user->display_name)
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete user. Please try again.', 'flexpress')));
        }
    }

    /**
     * Render purchased episodes management section
     */
    public function render_purchased_episodes_section($user) {
        try {
            $user_id = $user->ID;
            
            // Get all types of episode access
            $purchased_episodes = get_user_meta($user_id, 'purchased_episodes', true);
            if (!is_array($purchased_episodes)) {
                $purchased_episodes = array();
            }
            
            // Get PPV purchases
            $ppv_purchases = get_user_meta($user_id, 'ppv_purchases', true);
            if (!is_array($ppv_purchases)) {
                $ppv_purchases = array();
            }
            
            // Get membership status
            $membership_status = get_user_meta($user_id, 'membership_status', true);
            $subscription_type = get_user_meta($user_id, 'subscription_type', true);
            
            // Combine all specific episode unlocks (not membership-based access)
            $all_episode_unlocks = array_unique(array_merge($purchased_episodes, $ppv_purchases));
            $total_episode_unlocks = count($all_episode_unlocks);
            
            // Get all episodes for the add dropdown
            $all_episodes = get_posts(array(
                'post_type' => 'episode',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            
            if (!$all_episodes) {
                $all_episodes = array();
            }
        ?>
        
        <div id="purchased-episodes-section">
            <!-- Add Episode Form -->
            <div class="card" style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 15px 0;"><?php esc_html_e('Add Episode Access', 'flexpress'); ?></h3>
                <div style="display: flex; gap: 10px; align-items: end;">
                    <div style="flex: 1;">
                        <label for="episode-select"><?php esc_html_e('Select Episode:', 'flexpress'); ?></label>
                        <select id="episode-select" style="width: 100%; margin-top: 5px;">
                            <option value=""><?php esc_html_e('Choose an episode...', 'flexpress'); ?></option>
                            <?php foreach ($all_episodes as $episode): ?>
                                <?php 
                                // Skip episodes user already has specific unlocks for
                                $has_unlock = in_array($episode->ID, $all_episode_unlocks);
                                if (!$has_unlock): 
                                ?>
                                    <option value="<?php echo esc_attr($episode->ID); ?>">
                                        #<?php echo $episode->ID; ?> - <?php echo esc_html($episode->post_title); ?>
                                        <?php 
                                        $price = function_exists('get_field') ? get_field('episode_price', $episode->ID) : null;
                                        if ($price) {
                                            echo ' ($' . number_format($price, 2) . ')';
                                        }
                                        ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label><?php esc_html_e('Or by ID:', 'flexpress'); ?></label>
                        <input type="number" id="episode-id-input" placeholder="Episode ID" min="1" style="width: 100px; margin-top: 5px;">
                    </div>
                    <div>
                        <button type="button" id="add-episode-btn" class="button button-primary" data-user-id="<?php echo esc_attr($user_id); ?>">
                            <?php esc_html_e('Add Access', 'flexpress'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Membership Status -->
            <?php if ($membership_status === 'active' || $subscription_type): ?>
                <div class="card" style="margin-bottom: 20px;">
                    <h3 style="margin: 0 0 15px 0;">
                        <?php esc_html_e('Membership Status', 'flexpress'); ?>
                        <span class="count" style="color: #28a745;">(<?php esc_html_e('Active', 'flexpress'); ?>)</span>
                    </h3>
                    <div class="membership-access-info" style="background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px; padding: 10px;">
                        <strong><?php esc_html_e('Active Membership:', 'flexpress'); ?></strong>
                        <?php esc_html_e('This user has access to all episodes through their active membership.', 'flexpress'); ?>
                        <?php 
                        $derived_subscription_type = function_exists('flexpress_get_user_subscription_type')
                            ? flexpress_get_user_subscription_type($user_id)
                            : ($subscription_type ?: '');
                        if (!empty($derived_subscription_type)) : ?>
                            <br><small><?php printf(__('Subscription Type: %s', 'flexpress'), esc_html($derived_subscription_type)); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Individual Episode Unlocks -->
            <div class="card">
                <h3 style="margin: 0 0 15px 0;">
                    <?php esc_html_e('Individual Episode Unlocks', 'flexpress'); ?>
                    <span class="count">(<?php echo $total_episode_unlocks; ?>)</span>
                </h3>
                
                <div id="purchased-episodes-list">
                    <?php if ($total_episode_unlocks === 0): ?>
                        <p class="no-episodes"><?php esc_html_e('No individual episode unlocks granted yet.', 'flexpress'); ?></p>
                    <?php else: ?>
                        <div class="episodes-grid" style="display: grid; gap: 10px;">
                            <?php foreach ($all_episode_unlocks as $episode_id): ?>
                                <?php 
                                try {
                                    $episode = get_post($episode_id);
                                    if (!$episode) continue;
                                    
                                    $purchase_date = get_user_meta($user_id, 'purchased_episode_' . $episode_id, true);
                                    $price = function_exists('get_field') ? get_field('episode_price', $episode_id) : null;
                                    $access_type = function_exists('get_field') ? get_field('access_type', $episode_id) : null;
                                    $episode_permalink = function_exists('get_permalink') ? get_permalink($episode_id) : '#';
                                    
                                    // Determine unlock type for this episode
                                    $unlock_type = '';
                                    if (in_array($episode_id, $ppv_purchases)) {
                                        $unlock_type = 'PPV Purchase';
                                    } elseif (in_array($episode_id, $purchased_episodes)) {
                                        $unlock_type = 'Direct Purchase';
                                    } else {
                                        $unlock_type = 'Manual Grant';
                                    }
                                ?>
                                <div class="episode-item" data-episode-id="<?php echo esc_attr($episode_id); ?>" style="display: flex; align-items: center; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                                    <div style="flex: 1;">
                                        <strong>#<?php echo $episode_id; ?> - <?php echo esc_html($episode->post_title); ?></strong>
                                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                            <span style="color: #0073aa; font-weight: bold;"><?php echo esc_html($unlock_type); ?></span>
                                            <?php if ($price): ?>
                                                • <span>Price: $<?php echo number_format($price, 2); ?></span>
                                            <?php endif; ?>
                                            <?php if ($access_type): ?>
                                                • <span>Type: <?php echo esc_html(ucfirst(str_replace('_', ' ', $access_type))); ?></span>
                                            <?php endif; ?>
                                            <?php if ($purchase_date): ?>
                                                • <span>Unlocked: <?php echo date('M j, Y', strtotime($purchase_date)); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <a href="<?php echo esc_url($episode_permalink); ?>" class="button button-small" target="_blank" style="margin-right: 5px;">
                                            <?php esc_html_e('View', 'flexpress'); ?>
                                        </a>
                                        <button type="button" class="button button-small button-link-delete remove-episode-btn" 
                                                data-episode-id="<?php echo esc_attr($episode_id); ?>" 
                                                data-user-id="<?php echo esc_attr($user_id); ?>"
                                                title="<?php esc_attr_e('Remove this episode unlock', 'flexpress'); ?>">
                                            <?php esc_html_e('Remove', 'flexpress'); ?>
                                        </button>
                                    </div>
                                </div>
                                <?php
                                } catch (Exception $e) {
                                    error_log('FlexPress Debug: Error rendering episode ' . $episode_id . ': ' . $e->getMessage());
                                    echo '<div style="color: red; padding: 10px;">Error loading episode #' . $episode_id . '</div>';
                                }
                                ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        console.log('FlexPress Debug: Episode management script loaded for user <?php echo $user_id; ?>');
        jQuery(document).ready(function($) {
            console.log('FlexPress Debug: Episode management DOM ready');
            
            // Add episode access
            $('#add-episode-btn').on('click', function() {
                console.log('FlexPress Debug: Add episode button clicked');
                var episodeId = $('#episode-select').val() || $('#episode-id-input').val();
                var userId = $(this).data('user-id');
                
                if (!episodeId) {
                    alert('Please select an episode or enter an episode ID.');
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('Adding...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'add_purchased_episode',
                        user_id: userId,
                        episode_id: episodeId,
                        nonce: '<?php echo function_exists('wp_create_nonce') ? wp_create_nonce('manage_purchased_episodes') : ''; ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload(); // Refresh to show updated list
                        } else {
                            alert(response.data.message || 'Error adding episode access.');
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Add Access');
                    }
                });
            });
            
            // Remove episode access
            $(document).on('click', '.remove-episode-btn', function() {
                if (!confirm('Are you sure you want to remove access to this episode?')) {
                    return;
                }
                
                var episodeId = $(this).data('episode-id');
                var userId = $(this).data('user-id');
                var $btn = $(this);
                var $item = $btn.closest('.episode-item');
                
                $btn.prop('disabled', true).text('Removing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'remove_purchased_episode',
                        user_id: userId,
                        episode_id: episodeId,
                        nonce: '<?php echo function_exists('wp_create_nonce') ? wp_create_nonce('manage_purchased_episodes') : ''; ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $item.fadeOut(300, function() {
                                $(this).remove();
                                // Update count (no need to subtract 1 since the item is already removed)
                                var newCount = $('.episode-item').length;
                                $('.count').text('(' + newCount + ')');
                                
                                // Show "no episodes" message if empty
                                if (newCount === 0) {
                                    $('#purchased-episodes-list .episodes-grid').replaceWith('<p class="no-episodes">No individual episode unlocks granted yet.</p>');
                                }
                                
                                // Add episode back to dropdown
                                var episodeTitle = response.data.episode_title;
                                var episodePrice = response.data.episode_price;
                                var priceText = episodePrice ? ' ($' + parseFloat(episodePrice).toFixed(2) + ')' : '';
                                $('#episode-select').append('<option value="' + episodeId + '">#' + episodeId + ' - ' + episodeTitle + priceText + '</option>');
                            });
                        } else {
                            alert(response.data.message || 'Error removing episode access.');
                            $btn.prop('disabled', false).text('Remove');
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                        $btn.prop('disabled', false).text('Remove');
                    }
                });
            });
            
            // Clear episode ID input when dropdown changes
            $('#episode-select').on('change', function() {
                if ($(this).val()) {
                    $('#episode-id-input').val('');
                }
            });
            
            // Clear dropdown when ID input changes
            $('#episode-id-input').on('input', function() {
                if ($(this).val()) {
                    $('#episode-select').val('');
                }
            });
        });
        </script>
        <?php
        } catch (Exception $e) {
            error_log('FlexPress Debug: Error in render_purchased_episodes_section: ' . $e->getMessage());
            echo '<div class="notice notice-error"><p>Error loading episode management section. Please check the error log.</p></div>';
        }
    }

    /**
     * Handle AJAX request to add purchased episode
     */
    public function handle_add_purchased_episode() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'manage_purchased_episodes')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'flexpress')));
            return;
        }

        // Check user capabilities
        if (!current_user_can('edit_users')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage user episodes.', 'flexpress')));
            return;
        }

        // Get parameters
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $episode_id = isset($_POST['episode_id']) ? intval($_POST['episode_id']) : 0;
        
        if (!$user_id || !$episode_id) {
            wp_send_json_error(array('message' => __('Invalid user ID or episode ID.', 'flexpress')));
            return;
        }

        // Verify user exists
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found.', 'flexpress')));
            return;
        }

        // Verify episode exists
        $episode = get_post($episode_id);
        if (!$episode || $episode->post_type !== 'episode') {
            wp_send_json_error(array('message' => __('Episode not found.', 'flexpress')));
            return;
        }

        // Check if user already has access
        $already_purchased = get_user_meta($user_id, 'purchased_episode_' . $episode_id, true);
        if ($already_purchased) {
            wp_send_json_error(array('message' => __('User already has access to this episode.', 'flexpress')));
            return;
        }

        // Add episode access
        update_user_meta($user_id, 'purchased_episode_' . $episode_id, current_time('mysql'));
        
        // Add to purchased episodes list
        $purchased_episodes = get_user_meta($user_id, 'purchased_episodes', true);
        if (!is_array($purchased_episodes)) {
            $purchased_episodes = array();
        }
        if (!in_array($episode_id, $purchased_episodes)) {
            $purchased_episodes[] = $episode_id;
            update_user_meta($user_id, 'purchased_episodes', $purchased_episodes);
        }

        // Log the action
        error_log(sprintf(
            'FlexPress: Episode access added via admin - User: %s (ID: %d), Episode: %s (ID: %d), Admin: %s (ID: %d)',
            $user->display_name,
            $user_id,
            $episode->post_title,
            $episode_id,
            wp_get_current_user()->display_name,
            get_current_user_id()
        ));

        wp_send_json_success(array(
            'message' => sprintf(__('Access to "%s" has been granted to %s.', 'flexpress'), $episode->post_title, $user->display_name),
            'episode_title' => $episode->post_title,
            'episode_id' => $episode_id
        ));
    }

    /**
     * Handle AJAX request to remove purchased episode
     */
    public function handle_remove_purchased_episode() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'manage_purchased_episodes')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'flexpress')));
            return;
        }

        // Check user capabilities
        if (!current_user_can('edit_users')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage user episodes.', 'flexpress')));
            return;
        }

        // Get parameters
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $episode_id = isset($_POST['episode_id']) ? intval($_POST['episode_id']) : 0;
        
        if (!$user_id || !$episode_id) {
            wp_send_json_error(array('message' => __('Invalid user ID or episode ID.', 'flexpress')));
            return;
        }

        // Verify user exists
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array('message' => __('User not found.', 'flexpress')));
            return;
        }

        // Get episode info before removal (for logging)
        $episode = get_post($episode_id);
        $episode_title = $episode ? $episode->post_title : "Episode #$episode_id";
        $episode_price = function_exists('get_field') ? get_field('episode_price', $episode_id) : null;

        // Remove episode access
        delete_user_meta($user_id, 'purchased_episode_' . $episode_id);
        
        // Remove from purchased episodes list
        $purchased_episodes = get_user_meta($user_id, 'purchased_episodes', true);
        if (is_array($purchased_episodes)) {
            $purchased_episodes = array_diff($purchased_episodes, array($episode_id));
            update_user_meta($user_id, 'purchased_episodes', $purchased_episodes);
        }

        // Remove from PPV purchases list
        $ppv_purchases = get_user_meta($user_id, 'ppv_purchases', true);
        if (is_array($ppv_purchases)) {
            $ppv_purchases = array_diff($ppv_purchases, array($episode_id));
            update_user_meta($user_id, 'ppv_purchases', $ppv_purchases);
        }

        // Remove transaction details if they exist
        delete_user_meta($user_id, 'ppv_transaction_' . $episode_id);

        // Log the action
        error_log(sprintf(
            'FlexPress: Episode access removed via admin - User: %s (ID: %d), Episode: %s (ID: %d), Admin: %s (ID: %d)',
            $user->display_name,
            $user_id,
            $episode_title,
            $episode_id,
            wp_get_current_user()->display_name,
            get_current_user_id()
        ));

        wp_send_json_success(array(
            'message' => sprintf(__('Access to "%s" has been removed from %s.', 'flexpress'), $episode_title, $user->display_name),
            'episode_title' => $episode_title,
            'episode_price' => $episode_price,
            'episode_id' => $episode_id
        ));
    }
}

// Initialize the Membership Settings only in admin
if (is_admin()) {
    new FlexPress_Membership_Settings();
} 