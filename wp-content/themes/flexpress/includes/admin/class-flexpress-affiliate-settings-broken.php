<?php
/**
 * FlexPress Affiliate & Promotional Code Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Required WordPress functions
if (!function_exists('add_submenu_page')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (!function_exists('wp_create_nonce')) {
    require_once(ABSPATH . 'wp-includes/pluggable.php');
}

if (!function_exists('get_option')) {
    require_once(ABSPATH . 'wp-includes/option.php');
}

/**
 * FlexPress Affiliate Settings Class
 */
class FlexPress_Affiliate_Settings {
    /**
     * Create promo code usage tracking table
     */
    public static function create_promo_usage_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'flexpress_promo_usage';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            promo_code varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            plan_id varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            transaction_id varchar(100) NOT NULL,
            used_at datetime NOT NULL,
            ip_address varchar(45) NOT NULL,
            PRIMARY KEY (id),
            KEY promo_code (promo_code),
            KEY user_id (user_id),
            KEY used_at (used_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create affiliate management tables
     */
    public static function create_affiliate_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create affiliates table
        $affiliates_table = $wpdb->prefix . 'flexpress_affiliates';
        $sql_affiliates = "CREATE TABLE IF NOT EXISTS $affiliates_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            affiliate_code varchar(50) NOT NULL UNIQUE,
            display_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            commission_signup decimal(5,2) NOT NULL DEFAULT 25.00,
            commission_rebill decimal(5,2) NOT NULL DEFAULT 10.00,
            commission_type enum('percentage', 'flat') NOT NULL DEFAULT 'percentage',
            status enum('pending', 'active', 'suspended', 'rejected') NOT NULL DEFAULT 'pending',
            total_signups bigint(20) NOT NULL DEFAULT 0,
            total_rebills bigint(20) NOT NULL DEFAULT 0,
            total_revenue decimal(10,2) NOT NULL DEFAULT 0.00,
            total_commission decimal(10,2) NOT NULL DEFAULT 0.00,
            pending_commission decimal(10,2) NOT NULL DEFAULT 0.00,
            paid_commission decimal(10,2) NOT NULL DEFAULT 0.00,
            referral_url varchar(255) NOT NULL DEFAULT '',
            notes text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY affiliate_code (affiliate_code),
            KEY user_id (user_id),
            KEY status (status),
            KEY email (email)
        ) $charset_collate;";
        
        // Create affiliate commissions table
        $commissions_table = $wpdb->prefix . 'flexpress_affiliate_commissions';
        $sql_commissions = "CREATE TABLE IF NOT EXISTS $commissions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            transaction_type enum('signup', 'rebill', 'ppv') NOT NULL,
            transaction_id varchar(100) NOT NULL,
            plan_id varchar(50) NOT NULL,
            revenue_amount decimal(10,2) NOT NULL,
            commission_rate decimal(5,2) NOT NULL,
            commission_amount decimal(10,2) NOT NULL,
            commission_type enum('percentage', 'flat') NOT NULL DEFAULT 'percentage',
            status enum('pending', 'approved', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
            promo_code varchar(50) NOT NULL DEFAULT '',
            notes text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            paid_at datetime NULL,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY user_id (user_id),
            KEY transaction_type (transaction_type),
            KEY status (status),
            KEY created_at (created_at),
            FOREIGN KEY (affiliate_id) REFERENCES {$affiliates_table}(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Create affiliate payouts table
        $payouts_table = $wpdb->prefix . 'flexpress_affiliate_payouts';
        $sql_payouts = "CREATE TABLE IF NOT EXISTS $payouts_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            period_start date NOT NULL,
            period_end date NOT NULL,
            total_commissions decimal(10,2) NOT NULL,
            payout_amount decimal(10,2) NOT NULL,
            payout_method enum('paypal', 'bank_transfer', 'check', 'crypto') NOT NULL DEFAULT 'paypal',
            payout_details text,
            status enum('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
            notes text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime NULL,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY period_end (period_end),
            KEY status (status),
            FOREIGN KEY (affiliate_id) REFERENCES {$affiliates_table}(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_affiliates);
        dbDelta($sql_commissions);
        dbDelta($sql_payouts);
        
        // Also create the promo usage table if it doesn't exist
        self::create_promo_usage_table();
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_affiliate_settings_page'));
        add_action('admin_init', array($this, 'register_affiliate_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_create_affiliate_code', array($this, 'create_affiliate_code'));
        add_action('wp_ajax_delete_affiliate_code', array($this, 'delete_affiliate_code'));
        add_action('wp_ajax_toggle_affiliate_status', array($this, 'toggle_affiliate_status'));
        add_action('wp_ajax_get_affiliate_stats', array($this, 'get_affiliate_stats'));
        add_action('wp_ajax_update_affiliate_code', array($this, 'update_affiliate_code'));
        add_action('wp_ajax_get_pricing_plans', array($this, 'get_pricing_plans'));
        add_action('wp_ajax_add_affiliate', array($this, 'add_affiliate'));
        add_action('wp_ajax_get_affiliate_details', array($this, 'get_affiliate_details'));
        add_action('wp_ajax_update_affiliate', array($this, 'update_affiliate'));
        add_action('wp_ajax_get_payouts_list', array($this, 'get_payouts_list'));
        add_action('wp_ajax_create_payout', array($this, 'create_payout'));
        add_action('wp_ajax_update_payout_status', array($this, 'update_payout_status'));
        add_action('wp_ajax_get_payout_details', array($this, 'get_payout_details'));
        add_action('wp_ajax_process_payout', array($this, 'process_payout'));
        add_action('wp_ajax_get_eligible_affiliates', array($this, 'get_eligible_affiliates'));
        
        // Create table on theme activation
        add_action('after_switch_theme', array(__CLASS__, 'create_affiliate_tables'));
    }

    /**
     * Add the affiliate settings page to admin menu
     */
    public function add_affiliate_settings_page() {
        add_submenu_page(
            'flexpress-settings',
            __('Affiliate System', 'flexpress'),
            __('Affiliate System', 'flexpress'),
            'manage_options',
            'flexpress-affiliate-settings',
            array($this, 'render_affiliate_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_affiliate_settings() {
        register_setting('flexpress_affiliate_settings', 'flexpress_affiliate_settings', array(
            'sanitize_callback' => array($this, 'sanitize_affiliate_settings')
        ));
        
        // Module Control Section
        add_settings_section(
            'flexpress_affiliate_module_section',
            __('Module Control', 'flexpress'),
            array($this, 'render_module_section'),
            'flexpress_affiliate_settings'
        );

        add_settings_field(
            'affiliate_module_enabled',
            __('Enable Affiliate System', 'flexpress'),
            array($this, 'render_module_toggle_field'),
            'flexpress_affiliate_settings',
            'flexpress_affiliate_module_section'
        );
        
        // Commission Settings Section
        add_settings_section(
            'flexpress_affiliate_commission_section',
            __('Commission Settings', 'flexpress'),
            array($this, 'render_commission_section'),
            'flexpress_affiliate_settings'
        );

        add_settings_field(
            'commission_rate',
            __('Initial Commission Rate (%)', 'flexpress'),
            array($this, 'render_commission_rate_field'),
            'flexpress_affiliate_settings',
            'flexpress_affiliate_commission_section'
        );

        add_settings_field(
            'rebill_commission_rate',
            __('Rebill Commission Rate (%)', 'flexpress'),
            array($this, 'render_rebill_commission_rate_field'),
            'flexpress_affiliate_settings',
            'flexpress_affiliate_commission_section'
        );

        add_settings_field(
            'unlock_commission_rate',
            __('Unlock Commission Rate (%)', 'flexpress'),
            array($this, 'render_unlock_commission_rate_field'),
            'flexpress_affiliate_settings',
            'flexpress_affiliate_commission_section'
        );
        
        // Payout Settings Section
        add_settings_section(
            'flexpress_affiliate_payout_section',
            __('Payout Settings', 'flexpress'),
            array($this, 'render_payout_section'),
            'flexpress_affiliate_settings'
        );

        add_settings_field(
            'minimum_payout',
            __('Minimum Payout ($)', 'flexpress'),
            array($this, 'render_minimum_payout_field'),
            'flexpress_affiliate_settings',
            'flexpress_affiliate_payout_section'
        );

        add_settings_field(
            'payout_schedule',
            __('Payout Schedule', 'flexpress'),
            array($this, 'render_payout_schedule_field'),
            'flexpress_affiliate_settings',
            'flexpress_affiliate_payout_section'
        );
        
        // General Settings Section
        add_settings_section(
            'flexpress_affiliate_general_section',
            __('General Settings', 'flexpress'),
            array($this, 'render_general_section'),
            'flexpress_affiliate_settings'
        );

        add_settings_field(
            'auto_approve_affiliates',
            __('Auto-approve Affiliates', 'flexpress'),
            array($this, 'render_auto_approve_field'),
            'flexpress_affiliate_settings',
            'flexpress_affiliate_general_section'
        );

        add_settings_field(
            'affiliate_terms',
            __('Affiliate Terms', 'flexpress'),
            array($this, 'render_affiliate_terms_field'),
            'flexpress_affiliate_settings',
            'flexpress_affiliate_general_section'
        );
    }

    /**
     * Sanitize affiliate settings data
     */
    public function sanitize_affiliate_settings($input) {
        if (!is_array($input)) {
            return array();
        }

        return array(
            'module_enabled' => !empty($input['module_enabled']),
            'commission_rate' => floatval($input['commission_rate'] ?? 25.00),
            'rebill_commission_rate' => floatval($input['rebill_commission_rate'] ?? 10.00),
            'unlock_commission_rate' => floatval($input['unlock_commission_rate'] ?? 15.00),
            'minimum_payout' => floatval($input['minimum_payout'] ?? 100.00),
            'payout_schedule' => sanitize_text_field($input['payout_schedule'] ?? 'monthly'),
            'auto_approve_affiliates' => !empty($input['auto_approve_affiliates']),
            'affiliate_terms' => wp_kses_post($input['affiliate_terms'] ?? ''),
        );
    }

    /**
     * Render module section description
     */
    public function render_module_section() {
        echo '<p>' . esc_html__('Control the affiliate system module. When disabled, all affiliate functionality is turned off.', 'flexpress') . '</p>';
    }
    
    /**
     * Render module toggle field
     */
    public function render_module_toggle_field() {
        $settings = get_option('flexpress_affiliate_settings', array());
        $enabled = !empty($settings['module_enabled']);
        ?>
        <label>
            <input type="checkbox" name="flexpress_affiliate_settings[module_enabled]" value="1" <?php checked($enabled); ?> />
            <?php esc_html_e('Enable the complete affiliate and promo-code management system', 'flexpress'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When disabled, all affiliate hooks stop firing and promo codes fall back to normal pricing rules.', 'flexpress'); ?>
        </p>
        <?php
    }
    
    /**
     * Render commission section description
     */
    public function render_commission_section() {
        echo '<p>' . esc_html__('Set default commission rates for affiliates. These can be overridden per affiliate.', 'flexpress') . '</p>';
    }
    
    /**
     * Render commission rate field
     */
    public function render_commission_rate_field() {
        $settings = get_option('flexpress_affiliate_settings', array());
        $value = $settings['commission_rate'] ?? 25.00;
        ?>
        <input type="number" name="flexpress_affiliate_settings[commission_rate]" 
               value="<?php echo esc_attr($value); ?>" 
               step="0.1" min="0" max="100" />
        <p class="description"><?php esc_html_e('Default commission percentage for initial sales', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render rebill commission rate field
     */
    public function render_rebill_commission_rate_field() {
        $settings = get_option('flexpress_affiliate_settings', array());
        $value = $settings['rebill_commission_rate'] ?? 10.00;
        ?>
        <input type="number" name="flexpress_affiliate_settings[rebill_commission_rate]" 
               value="<?php echo esc_attr($value); ?>" 
               step="0.1" min="0" max="100" />
        <p class="description"><?php esc_html_e('Default commission percentage for recurring payments/rebills', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render unlock commission rate field
     */
    public function render_unlock_commission_rate_field() {
        $settings = get_option('flexpress_affiliate_settings', array());
        $value = $settings['unlock_commission_rate'] ?? 15.00;
        ?>
        <input type="number" name="flexpress_affiliate_settings[unlock_commission_rate]" 
               value="<?php echo esc_attr($value); ?>" 
               step="0.1" min="0" max="100" />
        <p class="description"><?php esc_html_e('Default commission percentage for unlock purchases', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render payout section description
     */
    public function render_payout_section() {
        echo '<p>' . esc_html__('Configure payout settings for affiliates.', 'flexpress') . '</p>';
    }
    
    /**
     * Render minimum payout field
     */
    public function render_minimum_payout_field() {
        $settings = get_option('flexpress_affiliate_settings', array());
        $value = $settings['minimum_payout'] ?? 100.00;
        ?>
        <input type="number" name="flexpress_affiliate_settings[minimum_payout]" 
               value="<?php echo esc_attr($value); ?>" 
               step="0.01" min="0" />
        <p class="description"><?php esc_html_e('Minimum amount before payout is processed', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render payout schedule field
     */
    public function render_payout_schedule_field() {
        $settings = get_option('flexpress_affiliate_settings', array());
        $value = $settings['payout_schedule'] ?? 'monthly';
        ?>
        <select name="flexpress_affiliate_settings[payout_schedule]">
            <option value="weekly" <?php selected($value, 'weekly'); ?>><?php esc_html_e('Weekly', 'flexpress'); ?></option>
            <option value="monthly" <?php selected($value, 'monthly'); ?>><?php esc_html_e('Monthly', 'flexpress'); ?></option>
            <option value="quarterly" <?php selected($value, 'quarterly'); ?>><?php esc_html_e('Quarterly', 'flexpress'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('How often payouts are processed', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render general section description
     */
    public function render_general_section() {
        echo '<p>' . esc_html__('General affiliate system settings.', 'flexpress') . '</p>';
    }
    
    /**
     * Render auto approve field
     */
    public function render_auto_approve_field() {
        $settings = get_option('flexpress_affiliate_settings', array());
        $enabled = !empty($settings['auto_approve_affiliates']);
        ?>
        <label>
            <input type="checkbox" name="flexpress_affiliate_settings[auto_approve_affiliates]" value="1" <?php checked($enabled); ?> />
            <?php esc_html_e('Automatically approve new affiliate applications', 'flexpress'); ?>
        </label>
        <p class="description"><?php esc_html_e('When enabled, new applications are automatically approved without manual review', 'flexpress'); ?></p>
        <?php
    }
    
    /**
     * Render affiliate terms field
     */
    public function render_affiliate_terms_field() {
        $settings = get_option('flexpress_affiliate_settings', array());
        $value = $settings['affiliate_terms'] ?? '';
        ?>
        <textarea name="flexpress_affiliate_settings[affiliate_terms]" rows="10" cols="50" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php esc_html_e('Terms and conditions for affiliates. This will be displayed on the application form.', 'flexpress'); ?></p>
        <?php
    }

    /**
     * Render the affiliate settings page
     */
    public function render_affiliate_settings_page() {
        $affiliate_settings = get_option('flexpress_affiliate_settings', array());
        $is_enabled = !empty($affiliate_settings['module_enabled']);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Affiliate & Promotional Code Management', 'flexpress'); ?></h1>
            
            <?php if (!$is_enabled): ?>
                <div class="notice notice-warning">
                    <p><strong><?php esc_html_e('Affiliate System Disabled', 'flexpress'); ?></strong> - <?php esc_html_e('The affiliate system is currently disabled. Enable it below to start using affiliate features.', 'flexpress'); ?></p>
                </div>
            <?php else: ?>
                <div class="notice notice-success">
                    <p><strong><?php esc_html_e('Affiliate System Active', 'flexpress'); ?></strong> - <?php esc_html_e('The affiliate system is enabled and ready to use.', 'flexpress'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="affiliate-dashboard">
                <!-- Stats Overview -->
                <?php if ($is_enabled): ?>
                <div class="stats-cards">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3><?php esc_html_e('Active Affiliates', 'flexpress'); ?></h3>
                            <div class="stat-number"><?php echo $this->get_active_affiliate_count(); ?></div>
                        </div>
                        <div class="stat-card">
                            <h3><?php esc_html_e('Pending Applications', 'flexpress'); ?></h3>
                            <div class="stat-number"><?php echo $this->get_pending_affiliate_count(); ?></div>
                        </div>
                        <div class="stat-card">
                            <h3><?php esc_html_e('Total Commissions', 'flexpress'); ?></h3>
                            <div class="stat-number">$<?php echo number_format($this->get_total_commissions(), 2); ?></div>
                        </div>
                        <div class="stat-card">
                            <h3><?php esc_html_e('Pending Payouts', 'flexpress'); ?></h3>
                            <div class="stat-number">$<?php echo number_format($this->get_pending_commissions(), 2); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Navigation Tabs -->
                <div class="affiliate-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#settings" class="nav-tab nav-tab-active"><?php esc_html_e('Settings', 'flexpress'); ?></a>
                        <?php if ($is_enabled): ?>
                        <a href="#affiliates" class="nav-tab"><?php esc_html_e('Affiliate Management', 'flexpress'); ?></a>
                        <a href="#promo-codes" class="nav-tab"><?php esc_html_e('Promo Codes', 'flexpress'); ?></a>
                        <a href="#transactions" class="nav-tab"><?php esc_html_e('Transactions', 'flexpress'); ?></a>
                        <a href="#payouts" class="nav-tab"><?php esc_html_e('Payouts', 'flexpress'); ?></a>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- Settings Tab -->
                <div id="settings" class="tab-content active">
                    <h2><?php esc_html_e('Affiliate System Settings', 'flexpress'); ?></h2>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('flexpress_affiliate_settings'); ?>
                        
                        <?php do_settings_sections('flexpress_affiliate_settings'); ?>
                        
                        <?php submit_button(); ?>
                    </form>
                </div>

                <?php if ($is_enabled): ?>
                <!-- Affiliate Management Tab -->
                <div id="affiliates" class="tab-content">
                    <div class="affiliates-header">
                        <h2><?php esc_html_e('Affiliate Management', 'flexpress'); ?></h2>
                        <button type="button" class="button button-primary" id="add-new-affiliate">
                            <?php esc_html_e('Add New Affiliate', 'flexpress'); ?>
                        </button>
                    </div>
                    <p><?php esc_html_e('Manage affiliate applications and accounts.', 'flexpress'); ?></p>
                    
                    <div class="affiliates-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Affiliate', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Email', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Status', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Commission Rate', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Total Revenue', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Actions', 'flexpress'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="affiliates-list">
                                <?php $this->render_affiliates_table(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Promo Codes Tab -->
                <div id="promo-codes" class="tab-content">
                    <h2><?php esc_html_e('Promo Codes', 'flexpress'); ?></h2>
                    <p><?php esc_html_e('Manage promotional codes and their usage.', 'flexpress'); ?></p>
                    <div class="promo-codes-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Code', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Affiliate', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Status', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Usage Count', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Actions', 'flexpress'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5"><?php esc_html_e('No promo codes found.', 'flexpress'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Transactions Tab -->
                <div id="transactions" class="tab-content">
                    <h2><?php esc_html_e('Transactions', 'flexpress'); ?></h2>
                    <p><?php esc_html_e('View affiliate transaction history and commissions.', 'flexpress'); ?></p>
                    <div class="transactions-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Date', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Affiliate', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Type', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Amount', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Commission', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Status', 'flexpress'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6"><?php esc_html_e('No transactions found.', 'flexpress'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payouts Tab -->
                <div id="payouts" class="tab-content">
                    <div class="payouts-header">
                        <h2><?php esc_html_e('Payouts', 'flexpress'); ?></h2>
                        <button type="button" class="button button-primary" id="create-payout">
                            <?php esc_html_e('Create Payout', 'flexpress'); ?>
                        </button>
                    </div>
                    <p><?php esc_html_e('Manage affiliate payouts and payment processing.', 'flexpress'); ?></p>
                    
                    <!-- Payout Filters -->
                    <div class="payout-filters">
                        <select id="payout-status-filter">
                            <option value=""><?php esc_html_e('All Statuses', 'flexpress'); ?></option>
                            <option value="pending"><?php esc_html_e('Pending', 'flexpress'); ?></option>
                            <option value="processing"><?php esc_html_e('Processing', 'flexpress'); ?></option>
                            <option value="completed"><?php esc_html_e('Completed', 'flexpress'); ?></option>
                            <option value="failed"><?php esc_html_e('Failed', 'flexpress'); ?></option>
                        </select>
                        <button type="button" class="button" id="refresh-payouts">
                            <?php esc_html_e('Refresh', 'flexpress'); ?>
                        </button>
                    </div>
                    
                    <div class="payouts-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Affiliate', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Period', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Amount', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Method', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Status', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Created', 'flexpress'); ?></th>
                                    <th><?php esc_html_e('Actions', 'flexpress'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="payouts-list">
                                <tr>
                                    <td colspan="7"><?php esc_html_e('Loading payouts...', 'flexpress'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="payout-pagination">
                        <button type="button" class="button" id="prev-page" disabled><?php esc_html_e('Previous', 'flexpress'); ?></button>
                        <span id="page-info"><?php esc_html_e('Page 1 of 1', 'flexpress'); ?></span>
                        <button type="button" class="button" id="next-page" disabled><?php esc_html_e('Next', 'flexpress'); ?></button>
                    </div>
                </div>
                <?php endif; ?>





            </div>
                        <div class="modal-header">
                            <h2><?php esc_html_e('Add New Affiliate', 'flexpress'); ?></h2>
                            <span class="modal-close">&times;</span>
                        </div>
                        <div class="modal-body">
                            <form id="add-affiliate-form">
                                <div class="form-field">
                                    <label for="add-affiliate-name"><?php esc_html_e('Affiliate Name', 'flexpress'); ?> *</label>
                                    <input type="text" id="add-affiliate-name" name="display_name" required>
                                </div>
                                <div class="form-field">
                                    <label for="affiliate-email"><?php esc_html_e('Email Address', 'flexpress'); ?> *</label>
                                    <input type="email" id="affiliate-email" name="email" required>
                                </div>
                                <div class="form-field">
                                    <label for="affiliate-website"><?php esc_html_e('Website/Social Media', 'flexpress'); ?></label>
                                    <input type="url" id="affiliate-website" name="website" placeholder="https://example.com">
                                </div>
                                <div class="form-field">
                                    <label for="payout-method"><?php esc_html_e('Payout Method', 'flexpress'); ?> *</label>
                                    <select id="payout-method" name="payout_method" required>
                                        <option value=""><?php esc_html_e('Select payout method', 'flexpress'); ?></option>
                                        <option value="paypal"><?php esc_html_e('PayPal', 'flexpress'); ?></option>
                                        <option value="bank_transfer"><?php esc_html_e('Bank Transfer', 'flexpress'); ?></option>
                                        <option value="check"><?php esc_html_e('Check', 'flexpress'); ?></option>
                                        <option value="crypto"><?php esc_html_e('Cryptocurrency', 'flexpress'); ?></option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="payout-details"><?php esc_html_e('Payout Details', 'flexpress'); ?> *</label>
                                    <input type="text" id="payout-details" name="payout_details" required placeholder="<?php esc_attr_e('PayPal email, bank account, etc.', 'flexpress'); ?>">
                                </div>
                                <div class="form-field">
                                    <label for="commission-initial"><?php esc_html_e('Initial Commission Rate (%)', 'flexpress'); ?></label>
                                    <input type="number" id="commission-initial" name="commission_initial" min="0" max="100" step="0.1" value="25">
                                </div>
                                <div class="form-field">
                                    <label for="commission-rebill"><?php esc_html_e('Rebill Commission Rate (%)', 'flexpress'); ?></label>
                                    <input type="number" id="commission-rebill" name="commission_rebill" min="0" max="100" step="0.1" value="10">
                                </div>
                                <div class="form-field">
                                    <label for="commission-unlock"><?php esc_html_e('Unlock Commission Rate (%)', 'flexpress'); ?></label>
                                    <input type="number" id="commission-unlock" name="commission_unlock" min="0" max="100" step="0.1" value="15">
                                </div>
                                <div class="form-field">
                                    <label for="payout-threshold"><?php esc_html_e('Payout Threshold ($)', 'flexpress'); ?></label>
                                    <input type="number" id="payout-threshold" name="payout_threshold" min="0" step="0.01" value="100">
                                </div>
                                <div class="form-field">
                                    <label for="affiliate-status"><?php esc_html_e('Status', 'flexpress'); ?></label>
                                    <select id="affiliate-status" name="status">
                                        <option value="pending"><?php esc_html_e('Pending', 'flexpress'); ?></option>
                                        <option value="active" selected><?php esc_html_e('Active', 'flexpress'); ?></option>
                                        <option value="suspended"><?php esc_html_e('Suspended', 'flexpress'); ?></option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="affiliate-notes"><?php esc_html_e('Notes', 'flexpress'); ?></label>
                                    <textarea id="affiliate-notes" name="notes" rows="3" placeholder="<?php esc_attr_e('Additional notes about this affiliate...', 'flexpress'); ?>"></textarea>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="button button-primary"><?php esc_html_e('Add Affiliate', 'flexpress'); ?></button>
                                    <button type="button" class="button modal-close"><?php esc_html_e('Cancel', 'flexpress'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Create Payout Modal -->
                <div id="create-payout-modal" class="affiliate-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><?php esc_html_e('Create New Payout', 'flexpress'); ?></h2>
                            <span class="modal-close">&times;</span>
                        </div>
                        <div class="modal-body">
                            <form id="create-payout-form">
                                <div class="form-field">
                                    <label for="payout-affiliate"><?php esc_html_e('Affiliate', 'flexpress'); ?> *</label>
                                    <select id="payout-affiliate" name="affiliate_id" required>
                                        <option value=""><?php esc_html_e('Select affiliate', 'flexpress'); ?></option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="payout-period-start"><?php esc_html_e('Period Start', 'flexpress'); ?> *</label>
                                    <input type="date" id="payout-period-start" name="period_start" required>
                                </div>
                                <div class="form-field">
                                    <label for="payout-period-end"><?php esc_html_e('Period End', 'flexpress'); ?> *</label>
                                    <input type="date" id="payout-period-end" name="period_end" required>
                                </div>
                                <div class="form-field">
                                    <label for="payout-notes"><?php esc_html_e('Notes', 'flexpress'); ?></label>
                                    <textarea id="payout-notes" name="notes" rows="3" placeholder="<?php esc_attr_e('Additional notes about this payout...', 'flexpress'); ?>"></textarea>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="button button-primary"><?php esc_html_e('Create Payout', 'flexpress'); ?></button>
                                    <button type="button" class="button modal-close"><?php esc_html_e('Cancel', 'flexpress'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Payout Details Modal -->
                <div id="payout-details-modal" class="affiliate-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><?php esc_html_e('Payout Details', 'flexpress'); ?></h2>
                            <span class="modal-close">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div id="payout-details-content">
                                <!-- Content will be loaded dynamically -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Payout Status Modal -->
                <div id="update-payout-modal" class="affiliate-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><?php esc_html_e('Update Payout Status', 'flexpress'); ?></h2>
                            <span class="modal-close">&times;</span>
                        </div>
                        <div class="modal-body">
                            <form id="update-payout-form">
                                <input type="hidden" id="update-payout-id" name="payout_id">
                                <div class="form-field">
                                    <label for="update-payout-status"><?php esc_html_e('Status', 'flexpress'); ?> *</label>
                                    <select id="update-payout-status" name="status" required>
                                        <option value="pending"><?php esc_html_e('Pending', 'flexpress'); ?></option>
                                        <option value="processing"><?php esc_html_e('Processing', 'flexpress'); ?></option>
                                        <option value="completed"><?php esc_html_e('Completed', 'flexpress'); ?></option>
                                        <option value="failed"><?php esc_html_e('Failed', 'flexpress'); ?></option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="update-payout-reference"><?php esc_html_e('Reference ID', 'flexpress'); ?></label>
                                    <input type="text" id="update-payout-reference" name="reference_id" placeholder="<?php esc_attr_e('Transaction ID, check number, etc.', 'flexpress'); ?>">
                                </div>
                                <div class="form-field">
                                    <label for="update-payout-notes"><?php esc_html_e('Notes', 'flexpress'); ?></label>
                                    <textarea id="update-payout-notes" name="notes" rows="3" placeholder="<?php esc_attr_e('Additional notes...', 'flexpress'); ?>"></textarea>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="button button-primary"><?php esc_html_e('Update Status', 'flexpress'); ?></button>
                                    <button type="button" class="button modal-close"><?php esc_html_e('Cancel', 'flexpress'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <style>
            .affiliate-dashboard {
                margin-top: 20px;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .stat-card {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                text-align: center;
            }
            .stat-card h3 {
                margin: 0 0 10px 0;
                color: #666;
                font-size: 14px;
            }
            .stat-number {
                font-size: 32px;
                font-weight: bold;
                color: #007cba;
            }
            .tab-content {
                display: none;
                margin-top: 20px;
            }
            .tab-content.active {
                display: block;
            }
            .affiliate-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                z-index: 100000;
                display: none;
            }
            .modal-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 20px;
                border-radius: 3px;
                max-width: 600px;
                width: 90%;
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            }
            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 1px solid #ddd;
            }
            .modal-header h2 {
                margin: 0;
                font-size: 1.3em;
                line-height: 1.4;
            }
            .modal-close {
                cursor: pointer;
                border: none;
                background: none;
                padding: 0;
                color: #666;
            }
            .modal-close:hover {
                color: #dc3232;
            }
            .modal-body {
                max-height: calc(100vh - 200px);
                overflow-y: auto;
            }
            .submit-wrapper {
                margin-top: 20px;
                padding-top: 15px;
                border-top: 1px solid #ddd;
                text-align: right;
            }
            .submit-wrapper .button {
                margin-left: 10px;
            }
            .analytics-filters {
                margin-bottom: 20px;
            }
            .chart-container {
                margin-bottom: 30px;
            }
            .analytics-details {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
            .affiliates-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            .affiliates-header h2 {
                margin: 0;
            }
            .status {
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .status-active {
                background-color: #d4edda;
                color: #155724;
            }
            .status-pending {
                background-color: #fff3cd;
                color: #856404;
            }
            .status-suspended {
                background-color: #f8d7da;
                color: #721c24;
            }
            .form-field {
                margin-bottom: 15px;
            }
            .form-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
            .form-field input,
            .form-field select,
            .form-field textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .form-field small {
                color: #666;
                font-style: italic;
            }
            .form-actions {
                margin-top: 20px;
                text-align: right;
            }
            .form-actions .button {
                margin-left: 10px;
            }
            .affiliate-details-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-bottom: 20px;
            }
            .detail-section {
                background: #f9f9f9;
                padding: 15px;
                border-radius: 5px;
                border-left: 4px solid #007cba;
            }
            .detail-section h3 {
                margin: 0 0 10px 0;
                color: #007cba;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .detail-section p {
                margin: 5px 0;
                font-size: 13px;
            }
            .detail-section code {
                background: #e1e1e1;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: monospace;
                font-size: 12px;
            }
            .detail-section a {
                color: #007cba;
                text-decoration: none;
            }
            .detail-section a:hover {
                text-decoration: underline;
            }
            .modal-actions {
                margin-top: 20px;
                text-align: right;
                border-top: 1px solid #ddd;
                padding-top: 15px;
            }
            .modal-actions .button {
                margin-left: 10px;
            }
            </style>
        </div>
        <?php
    }

    /**
     * Render promotional codes table
     */
    private function render_promo_codes_table() {
        $promo_codes = get_option('flexpress_promo_codes', array());
        
        if (empty($promo_codes)) {
            echo '<tr><td colspan="7">' . esc_html__('No promotional codes found.', 'flexpress') . '</td></tr>';
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_promo_usage';
        
        // Get all pricing plans once to avoid multiple calls
        $all_plans = flexpress_get_pricing_plans(false);
        
        // Track displayed codes to prevent duplicates
        $displayed_codes = array();
        
        foreach ($promo_codes as $code => $data) {
            // Skip if we've already displayed this code
            if (in_array(strtolower($code), $displayed_codes)) {
                continue;
            }
            
            // Add to displayed codes list
            $displayed_codes[] = strtolower($code);
            
            // Get usage stats
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_uses,
                    MIN(used_at) as first_used,
                    MAX(used_at) as last_used,
                    SUM(amount) as total_revenue
                FROM {$table_name}
                WHERE promo_code = %s",
                $code
            ));
            
            // Get plan information
            $plans = array();
            if (!empty($data['target_plans']) && is_array($data['target_plans'])) {
                foreach ($data['target_plans'] as $plan_id) {
                    $plan = isset($all_plans[$plan_id]) ? $all_plans[$plan_id] : null;
                    if ($plan && !empty($plan['name']) && isset($plan['price'])) {
                        $plans[] = sprintf(
                            '%s (%s%.2f)',
                            esc_html($plan['name']),
                            esc_html($plan['currency'] === 'USD' ? '$' : $plan['currency']),
                            floatval($plan['price'])
                        );
                    }
                }
            }
            
            // If no plans are found, check if this is a legacy promo code
            if (empty($plans)) {
                foreach ($all_plans as $plan_id => $plan) {
                    if (!empty($plan['promo_codes'])) {
                        $promo_codes_list = array_map('trim', explode(',', strtolower($plan['promo_codes'])));
                        if (in_array(strtolower($code), $promo_codes_list)) {
                            $plans[] = sprintf(
                                '%s (%s%.2f)',
                                esc_html($plan['name']),
                                esc_html($plan['currency'] === 'USD' ? '$' : $plan['currency']),
                                floatval($plan['price'])
                            );
                            
                            // If this is a legacy code, update it to the new format
                            if (!isset($promo_codes[$code]['target_plans'])) {
                                $promo_codes[$code] = array(
                                    'target_plans' => array($plan_id),
                                    'affiliate_name' => $data['affiliate_name'] ?? '',
                                    'commission_rate' => $data['commission_rate'] ?? 10,
                                    'created_at' => $data['created_at'] ?? current_time('mysql'),
                                    'status' => 'active'
                                );
                                update_option('flexpress_promo_codes', $promo_codes);
                            }
                        }
                    }
                }
            }
            
            $plans_display = !empty($plans) ? implode(', ', $plans) : '<em>' . esc_html__('No plans assigned', 'flexpress') . '</em>';
            ?>
            <tr>
                <td>
                    <code><?php echo esc_html($code); ?></code>
                    <?php if (!empty($data['affiliate_name'])): ?>
                        <br><small><?php echo esc_html($data['affiliate_name']); ?></small>
                    <?php endif; ?>
                </td>
                <td><?php echo wp_kses($plans_display, array('em' => array())); ?></td>
                <td><?php echo (int)($stats->total_uses ?? 0); ?></td>
                <td><?php echo !empty($stats->first_used) ? esc_html(date_i18n(get_option('date_format'), strtotime($stats->first_used))) : '-'; ?></td>
                <td><?php echo !empty($stats->last_used) ? esc_html(date_i18n(get_option('date_format'), strtotime($stats->last_used))) : '-'; ?></td>
                <td>$<?php echo number_format(floatval($stats->total_revenue ?? 0), 2); ?></td>
                <td>
                    <button type="button" class="button button-small view-details" data-code="<?php echo esc_attr($code); ?>">
                        <?php esc_html_e('Details', 'flexpress'); ?>
                    </button>
                    <button type="button" class="button button-small delete-code" data-code="<?php echo esc_attr($code); ?>">
                        <?php esc_html_e('Delete', 'flexpress'); ?>
                    </button>
                </td>
            </tr>
            <?php
        }
    }

    /**
     * Render affiliates table
     */
    private function render_affiliates_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_affiliates';
        
        $affiliates = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY created_at DESC"
        );
        
        if (empty($affiliates)) {
            echo '<tr><td colspan="6">' . esc_html__('No affiliates found.', 'flexpress') . '</td></tr>';
            return;
        }
        
        foreach ($affiliates as $affiliate) {
            $status_class = 'status-' . $affiliate->status;
            $status_label = ucfirst($affiliate->status);
            
            // Format commission rates
            $commission_display = $affiliate->commission_initial . '% / ' . $affiliate->commission_rebill . '% / ' . $affiliate->commission_unlock . '%';
            
            // Format revenue
            $revenue_display = '$' . number_format($affiliate->total_revenue, 2);
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($affiliate->display_name) . '</strong><br><small>' . esc_html($affiliate->affiliate_code) . '</small></td>';
            echo '<td>' . esc_html($affiliate->email) . '</td>';
            echo '<td><span class="status ' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span></td>';
            echo '<td>' . esc_html($commission_display) . '</td>';
            echo '<td>' . esc_html($revenue_display) . '</td>';
            echo '<td>';
            echo '<button type="button" class="button button-small view-affiliate" data-id="' . esc_attr($affiliate->id) . '">' . esc_html__('View', 'flexpress') . '</button> ';
            echo '<button type="button" class="button button-small edit-affiliate" data-id="' . esc_attr($affiliate->id) . '">' . esc_html__('Edit', 'flexpress') . '</button>';
            echo '</td>';
            echo '</tr>';
        }
    }

    /**
     * Render pricing plan options for the promo code form
     */
    private function render_plan_options() {
        $pricing_plans = flexpress_get_pricing_plans(false);
        
        if (empty($pricing_plans)) {
            echo '<option value="" disabled>' . esc_html__('No active pricing plans found', 'flexpress') . '</option>';
            return;
        }
        
        foreach ($pricing_plans as $plan_id => $plan) {
            printf(
                '<option value="%s">%s - %s%s%.2f</option>',
                esc_attr($plan_id),
                esc_html($plan['name']),
                esc_html($plan['currency']),
                esc_html($plan['currency'] === 'USD' ? '$' : ''),
                floatval($plan['price'])
            );
        }
    }

    /**
     * Get plans associated with a promo code
     */
    private function get_plans_for_code($promo_code) {
        $plans = get_option('flexpress_pricing_plans', array());
        $associated_plans = array();
        
        foreach ($plans as $plan_id => $plan) {
            if (!empty($plan['promo_codes'])) {
                $codes = array_map('trim', explode(',', strtolower($plan['promo_codes'])));
                if (in_array(strtolower($promo_code), $codes)) {
                    $associated_plans[] = $plan['name'];
                }
            }
        }
        
        return $associated_plans;
    }

    /**
     * Calculate revenue for a promo code
     */
    private function calculate_code_revenue($promo_code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_promo_usage';
        
        $usage_records = $wpdb->get_results($wpdb->prepare(
            "SELECT plan_id FROM {$table_name} WHERE promo_code = %s",
            $promo_code
        ));
        
        $total_revenue = 0;
        $plans = get_option('flexpress_pricing_plans', array());
        
        foreach ($usage_records as $record) {
            if (isset($plans[$record->plan_id])) {
                $plan = $plans[$record->plan_id];
                $price = $plan['trial_enabled'] ? $plan['trial_price'] : $plan['price'];
                $total_revenue += $price;
            }
        }
        
        return $total_revenue;
    }

    /**
     * Get active affiliate count
     */
    private function get_active_affiliate_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}flexpress_affiliates WHERE status = 'active'");
    }
    
    /**
     * Get pending affiliate count
     */
    private function get_pending_affiliate_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}flexpress_affiliates WHERE status = 'pending'");
    }
    
    /**
     * Get total commissions
     */
    private function get_total_commissions() {
        global $wpdb;
        $result = $wpdb->get_var("SELECT SUM(commission_amount) FROM {$wpdb->prefix}flexpress_affiliate_transactions WHERE status IN ('approved', 'paid')");
        return $result ? floatval($result) : 0.00;
    }

    /**
     * Get pending commissions total
     */
    private function get_pending_commissions() {
        global $wpdb;
        $result = $wpdb->get_var("SELECT SUM(approved_commission) FROM {$wpdb->prefix}flexpress_affiliates");
        return $result ? floatval($result) : 0.00;
    }
    
    /**
     * Get total promo codes count
     */
    private function get_total_promo_codes() {
        global $wpdb;
        $result = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}flexpress_affiliate_promo_codes");
        return $result ? intval($result) : 0;
    }
    
    /**
     * Get total promo code uses
     */
    private function get_total_promo_uses() {
        global $wpdb;
        $result = $wpdb->get_var("SELECT SUM(usage_count) FROM {$wpdb->prefix}flexpress_affiliate_promo_codes");
        return $result ? intval($result) : 0;
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Check if we're on the affiliate settings page
        if (strpos($hook, 'page_flexpress-affiliate-settings') === false) {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'flexpress-affiliate-admin',
            get_template_directory_uri() . '/assets/css/affiliate-admin.css',
            [],
            FLEXPRESS_VERSION
        );
        
        // Enqueue Chart.js from CDN
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '3.7.0',
            true
        );
        
        // Enqueue our admin script with jQuery dependency
        wp_enqueue_script(
            'flexpress-affiliate-admin',
            get_template_directory_uri() . '/assets/js/affiliate-admin.js',
            ['jquery', 'chart-js'],
            FLEXPRESS_VERSION,
            true
        );
        
        // Add error handling for Chart.js
        wp_add_inline_script(
            'flexpress-affiliate-admin',
            'window.addEventListener("error", function(e) { if (e.message.includes("Chart")) { console.warn("Chart.js error:", e.message); } });',
            'before'
        );
        
        // Localize script
        wp_localize_script(
            'flexpress-affiliate-admin',
            'flexpressAffiliate',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('flexpress_affiliate_nonce'),
                'i18n' => [
                    'confirmDelete' => __('Are you sure you want to delete this promo code? This action cannot be undone.', 'flexpress'),
                    'success' => __('Success', 'flexpress'),
                    'error' => __('Error', 'flexpress'),
                ]
            ]
        );
    }

    /**
     * Create a new affiliate promo code
     */
    public function create_affiliate_code() {
        check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }
        
        $code = sanitize_text_field($_POST['code'] ?? '');
        $affiliate_name = sanitize_text_field($_POST['affiliate_name'] ?? '');
        $target_plans = array_map('sanitize_text_field', (array) ($_POST['target_plans'] ?? []));
        $commission_rate = floatval($_POST['commission_rate'] ?? 10);
        
        // Validate promo code format
        if (!preg_match('/^[a-zA-Z0-9-]{3,20}$/', $code)) {
            wp_send_json_error(['message' => __('Promo code must be 3-20 characters and contain only letters, numbers, and hyphens.', 'flexpress')]);
        }
        
        // Validate affiliate name
        if (strlen($affiliate_name) < 2) {
            wp_send_json_error(['message' => __('Affiliate name must be at least 2 characters.', 'flexpress')]);
        }
        
        // Validate target plans
        if (empty($target_plans)) {
            wp_send_json_error(['message' => __('Please select at least one target plan.', 'flexpress')]);
        }
        
        // Validate commission rate
        if ($commission_rate < 0 || $commission_rate > 100) {
            wp_send_json_error(['message' => __('Commission rate must be between 0 and 100.', 'flexpress')]);
        }
        
        // Validate that all target plans exist
        $pricing_plans = flexpress_get_pricing_plans(false);
        foreach ($target_plans as $plan_id) {
            if (!isset($pricing_plans[$plan_id])) {
                wp_send_json_error(['message' => sprintf(__('Invalid plan ID: %s', 'flexpress'), esc_html($plan_id))]);
            }
        }
        
        // Check if code already exists
        $existing_codes = get_option('flexpress_promo_codes', []);
        if (isset($existing_codes[$code])) {
            wp_send_json_error(['message' => __('This promotional code already exists.', 'flexpress')]);
        }
        
        // Add new code
        $existing_codes[$code] = [
            'affiliate_name' => $affiliate_name,
            'target_plans' => $target_plans,
            'commission_rate' => $commission_rate,
            'created_at' => current_time('mysql'),
            'status' => 'active'
        ];
        
        // Try to update option
        $updated = update_option('flexpress_promo_codes', $existing_codes);
        
        if ($updated) {
            wp_send_json_success([
                'message' => __('Promotional code created successfully.', 'flexpress'),
                'code' => $code,
                'data' => $existing_codes[$code]
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to create promotional code. Please try again.', 'flexpress')]);
        }
    }

    /**
     * Delete an affiliate promo code
     */
    public function delete_affiliate_code() {
        check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }
        
        $code = sanitize_text_field($_POST['code'] ?? '');
        
        if (empty($code)) {
            wp_send_json_error(['message' => __('No code specified.', 'flexpress')]);
        }
        
        $existing_codes = get_option('flexpress_promo_codes', []);
        
        if (!isset($existing_codes[$code])) {
            wp_send_json_error(['message' => __('Code not found.', 'flexpress')]);
        }
        
        unset($existing_codes[$code]);
        update_option('flexpress_promo_codes', $existing_codes);
        
        wp_send_json_success(['message' => __('Promotional code deleted successfully.', 'flexpress')]);
    }

    /**
     * Get affiliate statistics
     */
    public function get_affiliate_stats() {
        check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_promo_usage';
        
        $code = sanitize_text_field($_POST['code'] ?? '');
        $period = intval($_POST['period'] ?? 30);
        
        if (empty($code)) {
            wp_send_json_error(['message' => __('No code specified.', 'flexpress')]);
        }
        
        // Get promo code data
        $promo_codes = get_option('flexpress_promo_codes', []);
        $code_data = $promo_codes[$code] ?? [];
        
        // Get usage stats
        $stats = [
            'code' => $code,
            'affiliate_name' => $code_data['affiliate_name'] ?? '',
            'target_plans' => $code_data['target_plans'] ?? [],
            'commission_rate' => $code_data['commission_rate'] ?? 0,
            'total_uses' => 0,
            'revenue' => 0,
            'conversion_rate' => 0,
            'recent_uses' => 0,
            'timeline' => []
        ];
        
        // Total uses
        $stats['total_uses'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE promo_code = %s",
            $code
        ));
        
        // Revenue
        $stats['revenue'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table_name WHERE promo_code = %s",
            $code
        ));
        
        // Recent uses (last 30 days)
        $stats['recent_uses'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE promo_code = %s AND used_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $code,
            $period
        ));
        
        // Usage timeline
        $timeline = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(used_at) as date, COUNT(*) as count 
             FROM $table_name 
             WHERE promo_code = %s AND used_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(used_at)
             ORDER BY date ASC",
            $code,
            $period
        ));
        
        $stats['timeline'] = array_map(function($row) {
            return [
                'date' => $row->date,
                'count' => intval($row->count)
            ];
        }, $timeline);
        
        wp_send_json_success($stats);
    }
    
    /**
     * Update affiliate code via AJAX
     */
    public function update_affiliate_code() {
        check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }
        
        $code = sanitize_text_field($_POST['code'] ?? '');
        $affiliate_name = sanitize_text_field($_POST['affiliate_name'] ?? '');
        $target_plans = array_map('sanitize_text_field', $_POST['target_plans'] ?? []);
        $commission_rate = floatval($_POST['commission_rate'] ?? 0);
        
        if (empty($code) || empty($affiliate_name) || empty($target_plans) || $commission_rate < 0 || $commission_rate > 100) {
            wp_send_json_error(['message' => __('Invalid data provided.', 'flexpress')]);
        }
        
        $promo_codes = get_option('flexpress_promo_codes', []);
        
        if (!isset($promo_codes[$code])) {
            wp_send_json_error(['message' => __('Promo code not found.', 'flexpress')]);
        }
        
        // Update the promo code data
        $promo_codes[$code] = [
            'affiliate_name' => $affiliate_name,
            'target_plans' => $target_plans,
            'commission_rate' => $commission_rate,
            'created_at' => $promo_codes[$code]['created_at'] ?? current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $updated = update_option('flexpress_promo_codes', $promo_codes);
        
        if ($updated) {
            wp_send_json_success(['message' => __('Promo code updated successfully.', 'flexpress')]);
        } else {
            wp_send_json_error(['message' => __('Failed to update promo code.', 'flexpress')]);
        }
    }
    
    /**
     * Get pricing plans for AJAX
     */
    public function get_pricing_plans() {
        check_ajax_referer('flexpress_affiliate_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }
        
        $pricing_settings = get_option('flexpress_pricing_settings', []);
        $plans = $pricing_settings['plans'] ?? [];
        
        $plan_options = [];
        foreach ($plans as $plan) {
            if (!empty($plan['name'])) {
                $plan_options[] = [
                    'name' => $plan['name'],
                    'price' => $plan['price'] ?? 0
                ];
            }
        }
        
        wp_send_json_success($plan_options);
    }

    /**
     * Add new affiliate via AJAX
     */
    public function add_affiliate() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flexpress_affiliate_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'flexpress')]);
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }

        // Sanitize input data
        $display_name = sanitize_text_field($_POST['display_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $website = esc_url_raw($_POST['website'] ?? '');
        $payout_method = sanitize_text_field($_POST['payout_method'] ?? '');
        $payout_details = sanitize_text_field($_POST['payout_details'] ?? '');
        $commission_initial = floatval($_POST['commission_initial'] ?? 25);
        $commission_rebill = floatval($_POST['commission_rebill'] ?? 10);
        $commission_unlock = floatval($_POST['commission_unlock'] ?? 15);
        $payout_threshold = floatval($_POST['payout_threshold'] ?? 100);
        $status = sanitize_text_field($_POST['status'] ?? 'active');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        // Validate required fields
        if (empty($display_name) || empty($email) || empty($payout_method) || empty($payout_details)) {
            wp_send_json_error(['message' => __('Please fill in all required fields.', 'flexpress')]);
        }

        // Validate email format
        if (!is_email($email)) {
            wp_send_json_error(['message' => __('Please enter a valid email address.', 'flexpress')]);
        }

        // Validate status
        if (!in_array($status, ['pending', 'active', 'suspended'])) {
            $status = 'active';
        }

        // Validate payout method
        if (!in_array($payout_method, ['paypal', 'bank_transfer', 'check', 'crypto'])) {
            wp_send_json_error(['message' => __('Please select a valid payout method.', 'flexpress')]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_affiliates';

        // Check if email already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s",
            $email
        ));

        if ($existing) {
            wp_send_json_error(['message' => __('An affiliate with this email address already exists.', 'flexpress')]);
        }

        // Generate unique affiliate code
        $affiliate_code = $this->generate_affiliate_code($display_name);

        // Insert new affiliate
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => 0, // No WordPress user account
                'affiliate_code' => $affiliate_code,
                'display_name' => $display_name,
                'email' => $email,
                'website' => $website,
                'payout_method' => $payout_method,
                'payout_details' => $payout_details,
                'commission_initial' => $commission_initial,
                'commission_rebill' => $commission_rebill,
                'commission_unlock' => $commission_unlock,
                'payout_threshold' => $payout_threshold,
                'status' => $status,
                'notes' => $notes,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            [
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%s'
            ]
        );

        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to add affiliate. Please try again.', 'flexpress')]);
        }

        $affiliate_id = $wpdb->insert_id;

        // Generate referral URL
        $referral_url = home_url('/?affiliate=' . $affiliate_code);
        $wpdb->update(
            $table_name,
            ['referral_url' => $referral_url],
            ['id' => $affiliate_id],
            ['%s'],
            ['%d']
        );

        wp_send_json_success([
            'message' => sprintf(__('Affiliate "%s" added successfully!', 'flexpress'), $display_name),
            'affiliate_id' => $affiliate_id,
            'affiliate_code' => $affiliate_code,
            'referral_url' => $referral_url
        ]);
    }

    /**
     * Generate unique affiliate code
     */
    private function generate_affiliate_code($display_name) {
        // Create base code from display name
        $base_code = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $display_name));
        $base_code = substr($base_code, 0, 8); // Limit to 8 characters
        
        if (empty($base_code)) {
            $base_code = 'AFF';
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_affiliates';
        
        $counter = 1;
        $affiliate_code = $base_code;
        
        // Check if code exists and increment counter if needed
        while ($wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE affiliate_code = %s",
            $affiliate_code
        ))) {
            $affiliate_code = $base_code . $counter;
            $counter++;
        }

        return $affiliate_code;
    }

    /**
     * Get affiliate details via AJAX
     */
    public function get_affiliate_details() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flexpress_affiliate_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'flexpress')]);
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }

        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);

        if (!$affiliate_id) {
            wp_send_json_error(['message' => __('Invalid affiliate ID.', 'flexpress')]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_affiliates';

        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $affiliate_id
        ));

        if (!$affiliate) {
            wp_send_json_error(['message' => __('Affiliate not found.', 'flexpress')]);
        }

        // Convert to array and format data
        $affiliate_data = (array) $affiliate;
        
        // Format numeric values
        $affiliate_data['commission_initial'] = floatval($affiliate_data['commission_initial']);
        $affiliate_data['commission_rebill'] = floatval($affiliate_data['commission_rebill']);
        $affiliate_data['commission_unlock'] = floatval($affiliate_data['commission_unlock']);
        $affiliate_data['payout_threshold'] = floatval($affiliate_data['payout_threshold']);
        $affiliate_data['total_revenue'] = floatval($affiliate_data['total_revenue']);
        $affiliate_data['total_commission'] = floatval($affiliate_data['total_commission']);
        $affiliate_data['pending_commission'] = floatval($affiliate_data['pending_commission']);
        $affiliate_data['approved_commission'] = floatval($affiliate_data['approved_commission']);
        $affiliate_data['paid_commission'] = floatval($affiliate_data['paid_commission']);

        wp_send_json_success($affiliate_data);
    }

    /**
     * Update affiliate via AJAX
     */
    public function update_affiliate() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flexpress_affiliate_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'flexpress')]);
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'flexpress')]);
        }

        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);

        if (!$affiliate_id) {
            wp_send_json_error(['message' => __('Invalid affiliate ID.', 'flexpress')]);
        }

        // Sanitize input data
        $display_name = sanitize_text_field($_POST['display_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $website = esc_url_raw($_POST['website'] ?? '');
        $payout_method = sanitize_text_field($_POST['payout_method'] ?? '');
        $payout_details = sanitize_text_field($_POST['payout_details'] ?? '');
        $commission_initial = floatval($_POST['commission_initial'] ?? 25);
        $commission_rebill = floatval($_POST['commission_rebill'] ?? 10);
        $commission_unlock = floatval($_POST['commission_unlock'] ?? 15);
        $payout_threshold = floatval($_POST['payout_threshold'] ?? 100);
        $status = sanitize_text_field($_POST['status'] ?? 'active');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        // Validate required fields
        if (empty($display_name) || empty($email) || empty($payout_method) || empty($payout_details)) {
            wp_send_json_error(['message' => __('Please fill in all required fields.', 'flexpress')]);
        }

        // Validate email format
        if (!is_email($email)) {
            wp_send_json_error(['message' => __('Please enter a valid email address.', 'flexpress')]);
        }

        // Validate status
        if (!in_array($status, ['pending', 'active', 'suspended'])) {
            $status = 'active';
        }

        // Validate payout method
        if (!in_array($payout_method, ['paypal', 'bank_transfer', 'check', 'crypto'])) {
            wp_send_json_error(['message' => __('Please select a valid payout method.', 'flexpress')]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_affiliates';

        // Check if affiliate exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $affiliate_id
        ));

        if (!$existing) {
            wp_send_json_error(['message' => __('Affiliate not found.', 'flexpress')]);
        }

        // Check if email already exists for a different affiliate
        $email_conflict = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s AND id != %d",
            $email,
            $affiliate_id
        ));

        if ($email_conflict) {
            wp_send_json_error(['message' => __('An affiliate with this email address already exists.', 'flexpress')]);
        }

        // Update affiliate
        $result = $wpdb->update(
            $table_name,
            [
                'display_name' => $display_name,
                'email' => $email,
                'website' => $website,
                'payout_method' => $payout_method,
                'payout_details' => $payout_details,
                'commission_initial' => $commission_initial,
                'commission_rebill' => $commission_rebill,
                'commission_unlock' => $commission_unlock,
                'payout_threshold' => $payout_threshold,
                'status' => $status,
                'notes' => $notes,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $affiliate_id],
            [
                '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%s', '%s'
            ],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error(['message' => __('Failed to update affiliate. Please try again.', 'flexpress')]);
        }

        wp_send_json_success([
            'message' => sprintf(__('Affiliate "%s" updated successfully!', 'flexpress'), $display_name)
        ]);
    }

    /**
     * Get payouts list (delegate to FlexPress_Affiliate_Payouts)
     */
    public function get_payouts_list() {
        $payouts = new FlexPress_Affiliate_Payouts();
        $payouts->get_payouts_list();
    }

    /**
     * Create payout (delegate to FlexPress_Affiliate_Payouts)
     */
    public function create_payout() {
        $payouts = new FlexPress_Affiliate_Payouts();
        $payouts->create_payout();
    }

    /**
     * Update payout status (delegate to FlexPress_Affiliate_Payouts)
     */
    public function update_payout_status() {
        $payouts = new FlexPress_Affiliate_Payouts();
        $payouts->update_payout_status();
    }

    /**
     * Get payout details (delegate to FlexPress_Affiliate_Payouts)
     */
    public function get_payout_details() {
        $payouts = new FlexPress_Affiliate_Payouts();
        $payouts->get_payout_details();
    }

    /**
     * Process payout (delegate to FlexPress_Affiliate_Payouts)
     */
    public function process_payout() {
        $payouts = new FlexPress_Affiliate_Payouts();
        $payouts->process_payout();
    }

    /**
     * Get eligible affiliates (delegate to FlexPress_Affiliate_Payouts)
     */
    public function get_eligible_affiliates() {
        $payouts = new FlexPress_Affiliate_Payouts();
        $payouts->get_eligible_affiliates();
    }
}

// Initialize the affiliate settings
new FlexPress_Affiliate_Settings(); 