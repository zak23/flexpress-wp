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
    }

    /**
     * Sanitize affiliate settings data
     */
    public function sanitize_affiliate_settings($input) {
        if (!is_array($input)) {
            return array();
        }

        return array(
            'commission_rate' => floatval($input['commission_rate'] ?? 10),
            'rebill_commission_rate' => floatval($input['rebill_commission_rate'] ?? 5),
            'minimum_payout' => floatval($input['minimum_payout'] ?? 50),
            'payout_schedule' => sanitize_text_field($input['payout_schedule'] ?? 'monthly'),
            'auto_approve_affiliates' => !empty($input['auto_approve_affiliates']),
            'affiliate_terms' => wp_kses_post($input['affiliate_terms'] ?? ''),
        );
    }

    /**
     * Render the affiliate settings page
     */
    public function render_affiliate_settings_page() {
        $affiliate_settings = get_option('flexpress_affiliate_settings', array());
        $promo_stats = flexpress_get_promo_usage_stats();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Affiliate & Promotional Code Management', 'flexpress'); ?></h1>
            
            <div class="notice notice-info">
                <p><?php esc_html_e('Manage promotional codes, track affiliate performance, and monitor commission earnings.', 'flexpress'); ?></p>
            </div>

            <div class="affiliate-dashboard">
                <!-- Stats Overview -->
                <div class="stats-cards">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Total Promo Codes</h3>
                            <div class="stat-number"><?php echo count($promo_stats); ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Total Uses</h3>
                            <div class="stat-number"><?php echo array_sum(array_column($promo_stats, 'usage_count')); ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Active Affiliates</h3>
                            <div class="stat-number"><?php echo $this->get_active_affiliate_count(); ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Pending Commissions</h3>
                            <div class="stat-number">$<?php echo number_format($this->get_pending_commissions(), 2); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="affiliate-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#promo-codes" class="nav-tab nav-tab-active">Promotional Codes</a>
                        <a href="#affiliates" class="nav-tab">Affiliate Management</a>
                        <a href="#analytics" class="nav-tab">Analytics</a>
                        <a href="#settings" class="nav-tab">Settings</a>
                    </nav>
                </div>

                <!-- Promotional Codes Tab -->
                <div id="promo-codes" class="tab-content active">
                    <div class="promo-codes-header">
                        <h2>Promotional Code Management</h2>
                        <button type="button" class="button button-primary" id="create-promo-code">
                            Create New Promo Code
                        </button>
                    </div>

                    <div class="promo-codes-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Plans</th>
                                    <th>Total Uses</th>
                                    <th>First Used</th>
                                    <th>Last Used</th>
                                    <th>Revenue</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="promo-codes-list">
                                <?php $this->render_promo_codes_table(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Affiliates Tab -->
                <div id="affiliates" class="tab-content">
                    <div class="affiliates-header">
                        <h2>Affiliate Management</h2>
                        <button type="button" class="button button-primary" id="invite-affiliate">
                            Invite New Affiliate
                        </button>
                    </div>

                    <div class="affiliates-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Affiliate</th>
                                    <th>Codes</th>
                                    <th>Total Sales</th>
                                    <th>Commission Earned</th>
                                    <th>Payout Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="affiliates-list">
                                <?php $this->render_affiliates_table(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Analytics Tab -->
                <div id="analytics" class="tab-content">
                    <h2>Performance Analytics</h2>
                    
                    <div class="analytics-filters">
                        <select id="analytics-period">
                            <option value="7">Last 7 days</option>
                            <option value="30" selected>Last 30 days</option>
                            <option value="90">Last 90 days</option>
                            <option value="365">Last year</option>
                        </select>
                        <button type="button" class="button" id="export-analytics">Export Data</button>
                    </div>

                    <div class="analytics-charts">
                        <div class="chart-container">
                            <canvas id="usage-chart"></canvas>
                        </div>
                        <div class="analytics-details">
                            <div id="top-codes"></div>
                            <div id="conversion-rates"></div>
                        </div>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div id="settings" class="tab-content">
                    <h2>Affiliate System Settings</h2>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('flexpress_affiliate_settings'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">Commission Rate (%)</th>
                                <td>
                                    <input type="number" name="flexpress_affiliate_settings[commission_rate]" 
                                           value="<?php echo esc_attr($affiliate_settings['commission_rate'] ?? 10); ?>" 
                                           step="0.1" min="0" max="100" />
                                    <p class="description">Default commission percentage for affiliates</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Rebill Commission Rate (%)</th>
                                <td>
                                    <input type="number" name="flexpress_affiliate_settings[rebill_commission_rate]" 
                                           value="<?php echo esc_attr($affiliate_settings['rebill_commission_rate'] ?? 5); ?>" 
                                           step="0.1" min="0" max="100" />
                                    <p class="description">Commission percentage for recurring payments/rebills</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Minimum Payout ($)</th>
                                <td>
                                    <input type="number" name="flexpress_affiliate_settings[minimum_payout]" 
                                           value="<?php echo esc_attr($affiliate_settings['minimum_payout'] ?? 50); ?>" 
                                           step="0.01" min="0" />
                                    <p class="description">Minimum amount before payout is processed</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Payout Schedule</th>
                                <td>
                                    <select name="flexpress_affiliate_settings[payout_schedule]">
                                        <option value="weekly" <?php selected($affiliate_settings['payout_schedule'] ?? '', 'weekly'); ?>>Weekly</option>
                                        <option value="monthly" <?php selected($affiliate_settings['payout_schedule'] ?? '', 'monthly'); ?>>Monthly</option>
                                        <option value="quarterly" <?php selected($affiliate_settings['payout_schedule'] ?? '', 'quarterly'); ?>>Quarterly</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Auto-approve Affiliates</th>
                                <td>
                                    <input type="checkbox" name="flexpress_affiliate_settings[auto_approve_affiliates]" 
                                           value="1" <?php checked(!empty($affiliate_settings['auto_approve_affiliates'])); ?> />
                                    <label>Automatically approve new affiliate applications</label>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(); ?>
                    </form>
                </div>

                <!-- Promo Code Modal -->
                <div id="promo-code-modal" class="affiliate-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Create New Promotional Code</h2>
                            <span class="modal-close">&times;</span>
                        </div>
                        <div class="modal-body">
                            <form id="promo-code-form">
                                <div class="form-field">
                                    <label for="new-promo-code">Promo Code</label>
                                    <input type="text" id="new-promo-code" name="code" required>
                                </div>
                                <div class="form-field">
                                    <label for="affiliate-name">Affiliate Name</label>
                                    <input type="text" id="affiliate-name" name="affiliate_name" required>
                                </div>
                                <div class="form-field">
                                    <label for="target-plans">Target Plans</label>
                                    <select id="target-plans" name="target_plans[]" multiple required>
                                        <?php $this->render_plan_options(); ?>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="commission-rate">Commission Rate (%)</label>
                                    <input type="number" id="commission-rate" name="commission_rate" min="0" max="100" step="0.1" value="10" required>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="button button-primary">Create Promo Code</button>
                                    <button type="button" class="button modal-close">Cancel</button>
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
        // This would be populated with actual affiliate data
        echo '<tr><td colspan="6">No affiliates found. <a href="#" id="invite-first-affiliate">Invite your first affiliate</a></td></tr>';
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
        // This would query actual affiliate records
        return 0;
    }

    /**
     * Get pending commissions total
     */
    private function get_pending_commissions() {
        // This would calculate actual pending commissions
        return 0;
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
        
        // Get usage stats
        $stats = [
            'code' => $code,
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
}

// Initialize the affiliate settings
new FlexPress_Affiliate_Settings(); 