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

                <!-- Add New Affiliate Modal -->
                <div id="add-affiliate-modal" class="affiliate-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><?php esc_html_e('Add New Affiliate', 'flexpress'); ?></h2>
                            <span class="modal-close">&times;</span>
                        </div>
                        <div class="modal-body">
                            <form id="add-affiliate-form">
                                <div class="form-field">
                                    <label for="add-affiliate-name"><?php esc_html_e('Display Name', 'flexpress'); ?> *</label>
                                    <input type="text" id="add-affiliate-name" name="display_name" required>
                                </div>
                                <div class="form-field">
                                    <label for="add-affiliate-email"><?php esc_html_e('Email', 'flexpress'); ?> *</label>
                                    <input type="email" id="add-affiliate-email" name="email" required>
                                </div>
                                <div class="form-field">
                                    <label for="add-affiliate-website"><?php esc_html_e('Website/Social Media', 'flexpress'); ?></label>
                                    <input type="url" id="add-affiliate-website" name="website">
                                </div>
                                <div class="form-field">
                                    <label for="add-affiliate-payout-method"><?php esc_html_e('Preferred Payout Method', 'flexpress'); ?> *</label>
                                    <select id="add-affiliate-payout-method" name="payout_method" required>
                                        <option value="paypal"><?php esc_html_e('PayPal', 'flexpress'); ?></option>
                                        <option value="bank_transfer"><?php esc_html_e('Bank Transfer', 'flexpress'); ?></option>
                                        <option value="check"><?php esc_html_e('Check', 'flexpress'); ?></option>
                                        <option value="crypto"><?php esc_html_e('Cryptocurrency', 'flexpress'); ?></option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="add-affiliate-payout-details"><?php esc_html_e('Payout Details', 'flexpress'); ?> *</label>
                                    <textarea id="add-affiliate-payout-details" name="payout_details" rows="3" required placeholder="<?php esc_attr_e('PayPal email, bank account details, etc.', 'flexpress'); ?>"></textarea>
                                </div>
                                <div class="form-field">
                                    <label for="add-affiliate-tax-info"><?php esc_html_e('Tax Information', 'flexpress'); ?></label>
                                    <textarea id="add-affiliate-tax-info" name="tax_info" rows="2" placeholder="<?php esc_attr_e('Tax ID, business name, etc.', 'flexpress'); ?>"></textarea>
                                </div>
                                <div class="form-field">
                                    <label for="add-affiliate-commission-initial"><?php esc_html_e('Initial Commission Rate (%)', 'flexpress'); ?></label>
                                    <input type="number" id="add-affiliate-commission-initial" name="commission_initial" min="0" max="100" step="0.1" value="10">
                                </div>
                                <div class="form-field">
                                    <label for="add-affiliate-commission-rebill"><?php esc_html_e('Rebill Commission Rate (%)', 'flexpress'); ?></label>
                                    <input type="number" id="add-affiliate-commission-rebill" name="commission_rebill" min="0" max="100" step="0.1" value="5">
                                </div>
                                <div class="form-field">
                                    <label for="add-affiliate-commission-unlock"><?php esc_html_e('Unlock Commission Rate (%)', 'flexpress'); ?></label>
                                    <input type="number" id="add-affiliate-commission-unlock" name="commission_unlock" min="0" max="100" step="0.1" value="3">
                                </div>
                                <div class="form-field">
                                    <label for="add-affiliate-payout-threshold"><?php esc_html_e('Payout Threshold ($)', 'flexpress'); ?></label>
                                    <input type="number" id="add-affiliate-payout-threshold" name="payout_threshold" min="0" step="0.01" value="100">
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="button button-primary"><?php esc_html_e('Add Affiliate', 'flexpress'); ?></button>
                                    <button type="button" class="button modal-close"><?php esc_html_e('Cancel', 'flexpress'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- View Affiliate Modal -->
                <div id="view-affiliate-modal" class="affiliate-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><?php esc_html_e('Affiliate Details', 'flexpress'); ?></h2>
                            <span class="modal-close">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div id="affiliate-details-content">
                                <!-- Content will be loaded dynamically -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Affiliate Modal -->
                <div id="edit-affiliate-modal" class="affiliate-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><?php esc_html_e('Edit Affiliate', 'flexpress'); ?></h2>
                            <span class="modal-close">&times;</span>
                        </div>
                        <div class="modal-body">
                            <form id="edit-affiliate-form">
                                <input type="hidden" id="edit-affiliate-id" name="affiliate_id">
                                <div class="form-field">
                                    <label for="edit-affiliate-name"><?php esc_html_e('Display Name', 'flexpress'); ?> *</label>
                                    <input type="text" id="edit-affiliate-name" name="display_name" required>
                                </div>
                                <div class="form-field">
                                    <label for="edit-affiliate-email"><?php esc_html_e('Email', 'flexpress'); ?> *</label>
                                    <input type="email" id="edit-affiliate-email" name="email" required>
                                </div>
                                <div class="form-field">
                                    <label for="edit-affiliate-website"><?php esc_html_e('Website/Social Media', 'flexpress'); ?></label>
                                    <input type="url" id="edit-affiliate-website" name="website">
                                </div>
                                <div class="form-field">
                                    <label for="edit-affiliate-status"><?php esc_html_e('Status', 'flexpress'); ?> *</label>
                                    <select id="edit-affiliate-status" name="status" required>
                                        <option value="pending"><?php esc_html_e('Pending', 'flexpress'); ?></option>
                                        <option value="active"><?php esc_html_e('Active', 'flexpress'); ?></option>
                                        <option value="suspended"><?php esc_html_e('Suspended', 'flexpress'); ?></option>
                                        <option value="inactive"><?php esc_html_e('Inactive', 'flexpress'); ?></option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="edit-affiliate-payout-method"><?php esc_html_e('Preferred Payout Method', 'flexpress'); ?> *</label>
                                    <select id="edit-affiliate-payout-method" name="payout_method" required>
                                        <option value="paypal"><?php esc_html_e('PayPal', 'flexpress'); ?></option>
                                        <option value="bank_transfer"><?php esc_html_e('Bank Transfer', 'flexpress'); ?></option>
                                        <option value="check"><?php esc_html_e('Check', 'flexpress'); ?></option>
                                        <option value="crypto"><?php esc_html_e('Cryptocurrency', 'flexpress'); ?></option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="edit-affiliate-payout-details"><?php esc_html_e('Payout Details', 'flexpress'); ?> *</label>
                                    <textarea id="edit-affiliate-payout-details" name="payout_details" rows="3" required placeholder="<?php esc_attr_e('PayPal email, bank account details, etc.', 'flexpress'); ?>"></textarea>
                                </div>
                                <div class="form-field">
                                    <label for="edit-affiliate-tax-info"><?php esc_html_e('Tax Information', 'flexpress'); ?></label>
                                    <textarea id="edit-affiliate-tax-info" name="tax_info" rows="2" placeholder="<?php esc_attr_e('Tax ID, business name, etc.', 'flexpress'); ?>"></textarea>
                                </div>
                                <div class="form-field">
                                    <label for="edit-affiliate-commission-initial"><?php esc_html_e('Initial Commission Rate (%)', 'flexpress'); ?></label>
                                    <input type="number" id="edit-affiliate-commission-initial" name="commission_initial" min="0" max="100" step="0.1">
                                </div>
                                <div class="form-field">
                                    <label for="edit-affiliate-commission-rebill"><?php esc_html_e('Rebill Commission Rate (%)', 'flexpress'); ?></label>
                                    <input type="number" id="edit-affiliate-commission-rebill" name="commission_rebill" min="0" max="100" step="0.1">
                                </div>
                                <div class="form-field">
                                    <label for="edit-affiliate-commission-unlock"><?php esc_html_e('Unlock Commission Rate (%)', 'flexpress'); ?></label>
                                    <input type="number" id="edit-affiliate-commission-unlock" name="commission_unlock" min="0" max="100" step="0.1">
                                </div>
                                <div class="form-field">
                                    <label for="edit-affiliate-payout-threshold"><?php esc_html_e('Payout Threshold ($)', 'flexpress'); ?></label>
                                    <input type="number" id="edit-affiliate-payout-threshold" name="payout_threshold" min="0" step="0.01">
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="button button-primary"><?php esc_html_e('Update Affiliate', 'flexpress'); ?></button>
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
            .nav-tab-wrapper {
                margin-bottom: 20px;
            }
            .tab-content {
                display: none;
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .tab-content.active {
                display: block;
            }
            .affiliates-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            .payouts-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            .payout-filters {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
                align-items: center;
            }
            .payout-pagination {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 15px;
                margin-top: 20px;
                padding: 15px 0;
            }
            .affiliate-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .modal-content {
                background: white;
                padding: 20px;
                border-radius: 8px;
                max-width: 600px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
            }
            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .modal-close {
                cursor: pointer;
                font-size: 24px;
                line-height: 1;
                color: #666;
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
            .form-actions {
                display: flex;
                gap: 10px;
                justify-content: flex-end;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #eee;
            }
            </style>

            <script>
            jQuery(document).ready(function($) {
                // Tab switching
                $('.nav-tab').on('click', function(e) {
                    e.preventDefault();
                    
                    var target = $(this).attr('href');
                    
                    // Update active tab
                    $('.nav-tab').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
                    
                    // Show target content
                    $('.tab-content').removeClass('active');
                    $(target).addClass('active');
                    
                    // Update URL hash
                    window.location.hash = target;
                });
                
                // Handle URL hash on page load
                if (window.location.hash) {
                    var hash = window.location.hash;
                    $('.nav-tab[href="' + hash + '"]').trigger('click');
                }
                
                // Modal functionality
                $('.modal-close').on('click', function() {
                    $(this).closest('.affiliate-modal').fadeOut();
                });
                
                // Close modal when clicking outside
                $('.affiliate-modal').on('click', function(e) {
                    if (e.target === this) {
                        $(this).fadeOut();
                    }
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'flexpress-settings_page_flexpress-affiliate-settings') {
            return;
        }

        wp_enqueue_script(
            'flexpress-affiliate-admin',
            get_template_directory_uri() . '/assets/js/affiliate-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('flexpress-affiliate-admin', 'flexpressAffiliate', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flexpress_affiliate_nonce'),
        ));

        wp_enqueue_style(
            'flexpress-affiliate-admin',
            get_template_directory_uri() . '/assets/css/affiliate-system.css',
            array(),
            '1.0.0'
        );
    }

    /**
     * Get active affiliate count
     */
    public function get_active_affiliate_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_affiliates';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'active'");
        return intval($count ?: 0);
    }

    /**
     * Get pending affiliate count
     */
    public function get_pending_affiliate_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_affiliates';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'pending'");
        return intval($count ?: 0);
    }

    /**
     * Get total commissions
     */
    public function get_total_commissions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_affiliate_transactions';
        
        $total = $wpdb->get_var("SELECT SUM(commission_amount) FROM {$table_name} WHERE status IN ('approved', 'paid')");
        return floatval($total ?: 0);
    }

    /**
     * Get pending commissions
     */
    public function get_pending_commissions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_affiliates';
        
        $total = $wpdb->get_var("SELECT SUM(approved_commission) FROM {$table_name}");
        return floatval($total ?: 0);
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
            $commission_display = $affiliate->commission_signup . '% / ' . $affiliate->commission_rebill . '% / ' . $affiliate->commission_unlock . '%';
            
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
     * Delegate payout methods to FlexPress_Affiliate_Payouts class
     */
    public function get_payouts_list() {
        $payouts = new FlexPress_Affiliate_Payouts();
        $payouts->get_payouts_list();
    }

    public function create_payout() {
        $payouts = new FlexPress_Affiliate_Payouts();
        $payouts->create_payout();
    }

    public function update_payout_status() {
        $payouts = new FlexPress_Affiliate_Payouts();
        $payouts->update_payout_status();
    }

    public function get_payout_details() {
        $payouts = new FlexPress_Affiliate_Payouts();
        $payouts->get_payout_details();
    }

    public function process_payout() {
        $payouts = new FlexPress_Affiliate_Payouts();
        $payouts->process_payout();
    }

    public function get_eligible_affiliates() {
        $payouts = new FlexPress_Affiliate_Payouts();
        $payouts->get_eligible_affiliates();
    }

    // TODO: Implement remaining AJAX methods
    public function create_affiliate_code() {
        // Implementation needed
    }

    public function delete_affiliate_code() {
        // Implementation needed
    }

    public function toggle_affiliate_status() {
        // Implementation needed
    }

    public function get_affiliate_stats() {
        // Implementation needed
    }

    public function update_affiliate_code() {
        // Implementation needed
    }

    public function get_pricing_plans() {
        // Implementation needed
    }

    public function add_affiliate() {
        // Implementation needed
    }

    public function get_affiliate_details() {
        // Implementation needed
    }

    public function update_affiliate() {
        // Implementation needed
    }
}

// Initialize the affiliate settings
new FlexPress_Affiliate_Settings();
