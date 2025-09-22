<?php
/**
 * FlexPress Flowguard Reference Manager
 * 
 * Admin interface for viewing and managing enhanced Flowguard reference data.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FlexPress_Flowguard_Reference_Manager {
    
    /**
     * Initialize the reference manager
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_flexpress_get_reference_data', array($this, 'ajax_get_reference_data'));
        add_action('wp_ajax_flexpress_update_reference_data', array($this, 'ajax_update_reference_data'));
        add_action('wp_ajax_flexpress_test_enhanced_references', array($this, 'ajax_test_enhanced_references'));
        add_action('wp_ajax_flexpress_update_database_schema', array($this, 'ajax_update_database_schema'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flexpress-settings',
            'Flowguard References',
            'Flowguard References',
            'manage_options',
            'flexpress-flowguard-references',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'flexpress-settings_page_flexpress-flowguard-references') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('flexpress-admin-js', get_template_directory_uri() . '/assets/js/admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('flexpress-admin-js', 'flexpress_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flexpress_admin_nonce')
        ));
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Flowguard Reference Manager</h1>
            <p>View and manage enhanced Flowguard reference data for users.</p>
            
            <?php if (!$this->check_enhanced_columns_exist()): ?>
            <div class="notice notice-warning">
                <p><strong>Database Update Required:</strong> The enhanced reference system requires additional database columns. Click "Update Database Schema" below to add the required columns. This is safe and will not affect existing data.</p>
            </div>
            <?php endif; ?>
            
            <div class="flexpress-admin-container">
                <div class="flexpress-admin-sidebar">
                    <div class="flexpress-admin-widget">
                        <h3>Reference Statistics</h3>
                        <?php $this->display_reference_stats(); ?>
                    </div>
                    
                    <div class="flexpress-admin-widget">
                        <h3>Quick Actions</h3>
                        <button type="button" class="button button-primary" onclick="flexpressRefreshReferenceData()">
                            Refresh Data
                        </button>
                        <button type="button" class="button" onclick="flexpressExportReferenceData()">
                            Export CSV
                        </button>
                        <button type="button" class="button button-secondary" onclick="flexpressTestEnhancedReferences()">
                            Test System
                        </button>
                        <?php if (!$this->check_enhanced_columns_exist()): ?>
                        <button type="button" class="button button-primary" onclick="flexpressUpdateDatabaseSchema()" style="background: #d63638; border-color: #d63638;">
                            Update Database Schema
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="flexpress-admin-main">
                    <div class="flexpress-admin-tabs">
                        <button class="tab-button active" onclick="flexpressShowTab('users')">Users</button>
                        <button class="tab-button" onclick="flexpressShowTab('transactions')">Transactions</button>
                        <button class="tab-button" onclick="flexpressShowTab('analytics')">Analytics</button>
                    </div>
                    
                    <div id="users-tab" class="tab-content active">
                        <?php $this->display_users_table(); ?>
                    </div>
                    
                    <div id="transactions-tab" class="tab-content">
                        <?php $this->display_transactions_table(); ?>
                    </div>
                    
                    <div id="analytics-tab" class="tab-content">
                        <?php $this->display_analytics(); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .flexpress-admin-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .flexpress-admin-sidebar {
            width: 300px;
        }
        
        .flexpress-admin-main {
            flex: 1;
        }
        
        .flexpress-admin-widget {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .flexpress-admin-widget h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #23282d;
        }
        
        .flexpress-admin-tabs {
            border-bottom: 1px solid #ccd0d4;
            margin-bottom: 20px;
        }
        
        .tab-button {
            background: none;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        
        .tab-button.active {
            border-bottom-color: #0073aa;
            color: #0073aa;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .reference-data-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border: 1px solid #ccd0d4;
        }
        
        .reference-data-table th,
        .reference-data-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ccd0d4;
        }
        
        .reference-data-table th {
            background: #f1f1f1;
            font-weight: 600;
        }
        
        .reference-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .reference-badge.enhanced {
            background: #00a32a;
            color: #fff;
        }
        
        .reference-badge.legacy {
            background: #dba617;
            color: #fff;
        }
        
        .reference-badge.ppv {
            background: #d63638;
            color: #fff;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 600;
            color: #0073aa;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        </style>
        
        <script>
        function flexpressShowTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        function flexpressRefreshReferenceData() {
            location.reload();
        }
        
        function flexpressExportReferenceData() {
            // TODO: Implement CSV export
            alert('CSV export functionality coming soon!');
        }
        
        function flexpressTestEnhancedReferences() {
            if (!confirm('This will run a comprehensive test of the enhanced reference system. Continue?')) {
                return;
            }
            
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Testing...';
            button.disabled = true;
            
            jQuery.ajax({
                url: flexpress_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'flexpress_test_enhanced_references',
                    nonce: flexpress_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const results = response.data;
                        let message = 'Enhanced Reference System Test Results:\n\n';
                        
                        for (const [test, result] of Object.entries(results)) {
                            message += test.replace(/_/g, ' ').toUpperCase() + ':\n';
                            message += result + '\n\n';
                        }
                        
                        alert(message);
                    } else {
                        alert('Test failed: ' + response.data);
                    }
                },
                error: function() {
                    alert('Test failed: AJAX error');
                },
                complete: function() {
                    button.textContent = originalText;
                    button.disabled = false;
                }
            });
        }
        
        function flexpressUpdateDatabaseSchema() {
            if (!confirm('This will update the database schema to add enhanced reference columns. This is safe and will not affect existing data. Continue?')) {
                return;
            }
            
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Updating...';
            button.disabled = true;
            
            jQuery.ajax({
                url: flexpress_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'flexpress_update_database_schema',
                    nonce: flexpress_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Database schema updated successfully! The page will now reload.');
                        location.reload();
                    } else {
                        alert('Update failed: ' + response.data);
                    }
                },
                error: function() {
                    alert('Update failed: AJAX error');
                },
                complete: function() {
                    button.textContent = originalText;
                    button.disabled = false;
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * Display reference statistics
     */
    private function display_reference_stats() {
        global $wpdb;
        
        $transactions_table = $wpdb->prefix . 'flexpress_flowguard_transactions';
        
        // Get total transactions
        $total_transactions = $wpdb->get_var("SELECT COUNT(*) FROM {$transactions_table}");
        
        // Check if enhanced columns exist
        $columns_exist = $this->check_enhanced_columns_exist();
        
        if ($columns_exist) {
            // Get enhanced references count
            $enhanced_references = $wpdb->get_var("SELECT COUNT(*) FROM {$transactions_table} WHERE (affiliate_code != '' AND affiliate_code != 'none') OR (promo_code != '' AND promo_code != 'none') OR (signup_source != '' AND signup_source != 'none')");
            
            // Get affiliate referrals
            $affiliate_referrals = $wpdb->get_var("SELECT COUNT(*) FROM {$transactions_table} WHERE affiliate_code != '' AND affiliate_code != 'none'");
            
            // Get promo code usage
            $promo_usage = $wpdb->get_var("SELECT COUNT(*) FROM {$transactions_table} WHERE promo_code != '' AND promo_code != 'none'");
        } else {
            // Fallback to 0 if columns don't exist
            $enhanced_references = 0;
            $affiliate_referrals = 0;
            $promo_usage = 0;
        }
        
        ?>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format(intval($total_transactions)); ?></div>
                <div class="stat-label">Total Transactions</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format(intval($enhanced_references)); ?></div>
                <div class="stat-label">Enhanced References</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format(intval($affiliate_referrals)); ?></div>
                <div class="stat-label">Affiliate Referrals</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format(intval($promo_usage)); ?></div>
                <div class="stat-label">Promo Usage</div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Display users table
     */
    private function display_users_table() {
        global $wpdb;
        
        $users = $wpdb->get_results("
            SELECT 
                u.ID,
                u.user_email,
                u.display_name,
                um1.meta_value as enhanced_reference,
                um2.meta_value as reference_data,
                um3.meta_value as affiliate_code,
                um4.meta_value as promo_code,
                um5.meta_value as signup_source
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'flowguard_enhanced_reference'
            LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'flowguard_reference_data'
            LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'affiliate_referred_by'
            LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = 'applied_promo_code'
            LEFT JOIN {$wpdb->usermeta} um5 ON u.ID = um5.user_id AND um5.meta_key = 'signup_source'
            WHERE um1.meta_value IS NOT NULL
            ORDER BY u.ID DESC
            LIMIT 50
        ");
        
        ?>
        <table class="reference-data-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Email</th>
                    <th>Display Name</th>
                    <th>Reference</th>
                    <th>Affiliate</th>
                    <th>Promo</th>
                    <th>Source</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user->ID; ?></td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td><?php echo esc_html($user->display_name); ?></td>
                    <td>
                        <?php if ($user->enhanced_reference): ?>
                            <span class="reference-badge enhanced">Enhanced</span>
                            <br><small><?php echo esc_html($user->enhanced_reference); ?></small>
                        <?php else: ?>
                            <span class="reference-badge legacy">Legacy</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (!empty($user->affiliate_code) && $user->affiliate_code !== 'none') ? esc_html($user->affiliate_code) : '-'; ?></td>
                    <td><?php echo (!empty($user->promo_code) && $user->promo_code !== 'none') ? esc_html($user->promo_code) : '-'; ?></td>
                    <td><?php echo (!empty($user->signup_source) && $user->signup_source !== 'none') ? esc_html($user->signup_source) : '-'; ?></td>
                    <td>
                        <button type="button" class="button button-small" onclick="flexpressViewUserDetails(<?php echo $user->ID; ?>)">
                            View Details
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Display transactions table
     */
    private function display_transactions_table() {
        global $wpdb;
        
        $transactions_table = $wpdb->prefix . 'flexpress_flowguard_transactions';
        
        // Check if enhanced columns exist
        $columns_exist = $this->check_enhanced_columns_exist();
        
        if ($columns_exist) {
            $transactions = $wpdb->get_results("
                SELECT 
                    t.*,
                    u.user_email,
                    u.display_name
                FROM {$transactions_table} t
                LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
                ORDER BY t.created_at DESC
                LIMIT 50
            ");
        } else {
            // Fallback query without enhanced columns
            $transactions = $wpdb->get_results("
                SELECT 
                    t.id,
                    t.user_id,
                    t.transaction_id,
                    t.session_id,
                    t.sale_id,
                    t.amount,
                    t.currency,
                    t.status,
                    t.order_type,
                    t.reference_id,
                    t.created_at,
                    t.updated_at,
                    '' as affiliate_code,
                    '' as promo_code,
                    '' as signup_source,
                    '' as plan_id,
                    u.user_email,
                    u.display_name
                FROM {$transactions_table} t
                LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
                ORDER BY t.created_at DESC
                LIMIT 50
            ");
        }
        
        ?>
        <table class="reference-data-table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Reference</th>
                    <th>Affiliate</th>
                    <th>Promo</th>
                    <th>Source</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo esc_html($transaction->transaction_id); ?></td>
                    <td>
                        <?php echo esc_html($transaction->user_email); ?>
                        <br><small>ID: <?php echo $transaction->user_id; ?></small>
                    </td>
                    <td><?php echo '$' . number_format($transaction->amount, 2); ?></td>
                    <td>
                        <span class="reference-badge <?php echo $transaction->status === 'approved' ? 'enhanced' : 'legacy'; ?>">
                            <?php echo esc_html($transaction->status); ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        $reference_data = flexpress_flowguard_parse_enhanced_reference($transaction->reference_id);
                        if ($reference_data['is_enhanced']): ?>
                            <span class="reference-badge enhanced">Enhanced</span>
                        <?php elseif ($reference_data['is_ppv']): ?>
                            <?php if ($reference_data['is_enhanced']): ?>
                                <span class="reference-badge enhanced">Enhanced PPV</span>
                            <?php else: ?>
                                <span class="reference-badge ppv">Legacy PPV</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="reference-badge legacy">Legacy</span>
                        <?php endif; ?>
                        <br><small><?php echo esc_html($transaction->reference_id); ?></small>
                    </td>
                    <td><?php echo (!empty($transaction->affiliate_code) && $transaction->affiliate_code !== 'none') ? esc_html($transaction->affiliate_code) : '-'; ?></td>
                    <td><?php echo (!empty($transaction->promo_code) && $transaction->promo_code !== 'none') ? esc_html($transaction->promo_code) : '-'; ?></td>
                    <td><?php echo (!empty($transaction->signup_source) && $transaction->signup_source !== 'none') ? esc_html($transaction->signup_source) : '-'; ?></td>
                    <td><?php echo date('M j, Y', strtotime($transaction->created_at)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Display analytics
     */
    private function display_analytics() {
        global $wpdb;
        
        $transactions_table = $wpdb->prefix . 'flexpress_flowguard_transactions';
        
        // Check if enhanced columns exist
        $columns_exist = $this->check_enhanced_columns_exist();
        
        if ($columns_exist) {
            // Get signup sources breakdown
            $signup_sources = $wpdb->get_results("
                SELECT 
                    signup_source,
                    COUNT(*) as count,
                    SUM(amount) as total_revenue
                FROM {$transactions_table}
                WHERE signup_source != '' AND signup_source != 'none' AND status = 'approved'
                GROUP BY signup_source
                ORDER BY count DESC
            ");
            
            // Get affiliate performance
            $affiliate_performance = $wpdb->get_results("
                SELECT 
                    affiliate_code,
                    COUNT(*) as conversions,
                    SUM(amount) as total_revenue
                FROM {$transactions_table}
                WHERE affiliate_code != '' AND affiliate_code != 'none' AND status = 'approved'
                GROUP BY affiliate_code
                ORDER BY conversions DESC
                LIMIT 10
            ");
        } else {
            // Fallback empty arrays if columns don't exist
            $signup_sources = array();
            $affiliate_performance = array();
        }
        
        ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="flexpress-admin-widget">
                <h3>Signup Sources</h3>
                <table class="reference-data-table">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Conversions</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($signup_sources as $source): ?>
                        <tr>
                            <td><?php echo esc_html($source->signup_source); ?></td>
                            <td><?php echo number_format($source->count); ?></td>
                            <td>$<?php echo number_format($source->total_revenue, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="flexpress-admin-widget">
                <h3>Top Affiliates</h3>
                <table class="reference-data-table">
                    <thead>
                        <tr>
                            <th>Affiliate Code</th>
                            <th>Conversions</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($affiliate_performance as $affiliate): ?>
                        <tr>
                            <td><?php echo esc_html($affiliate->affiliate_code); ?></td>
                            <td><?php echo number_format($affiliate->conversions); ?></td>
                            <td>$<?php echo number_format($affiliate->total_revenue, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for getting reference data
     */
    public function ajax_get_reference_data() {
        check_ajax_referer('flexpress_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = intval($_POST['user_id']);
        $reference_data = get_user_meta($user_id, 'flowguard_reference_data', true);
        
        wp_send_json_success($reference_data);
    }
    
    /**
     * AJAX handler for updating reference data
     */
    public function ajax_update_reference_data() {
        check_ajax_referer('flexpress_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $user_id = intval($_POST['user_id']);
        $field = sanitize_text_field($_POST['field']);
        $value = sanitize_text_field($_POST['value']);
        
        update_user_meta($user_id, $field, $value);
        
        wp_send_json_success('Reference data updated');
    }
    
    /**
     * AJAX handler for testing enhanced reference system
     */
    public function ajax_test_enhanced_references() {
        check_ajax_referer('flexpress_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $test_results = array();
        
        // Test 1: Create test user
        $test_user_data = array(
            'user_login' => 'test_enhanced_user_' . time(),
            'user_email' => 'test_enhanced_' . time() . '@example.com',
            'user_pass' => 'testpassword123',
            'first_name' => 'Test',
            'last_name' => 'User',
            'display_name' => 'Test Enhanced User'
        );
        
        $user_id = wp_create_user($test_user_data['user_login'], $test_user_data['user_pass'], $test_user_data['user_email']);
        
        if (is_wp_error($user_id)) {
            $test_results['user_creation'] = 'Failed: ' . $user_id->get_error_message();
        } else {
            $test_results['user_creation'] = 'Success: User ID ' . $user_id;
            
            // Set test data
            update_user_meta($user_id, 'first_name', $test_user_data['first_name']);
            update_user_meta($user_id, 'last_name', $test_user_data['last_name']);
            update_user_meta($user_id, 'display_name', $test_user_data['display_name']);
            update_user_meta($user_id, 'registration_date', current_time('mysql'));
            update_user_meta($user_id, 'registration_ip', '192.168.1.100');
            update_user_meta($user_id, 'signup_source', 'google');
            update_user_meta($user_id, 'affiliate_referred_by', 'AFF123456');
            update_user_meta($user_id, 'applied_promo_code', 'WELCOME50');
            
            // Test 2: Generate enhanced reference
            $plan_id = 'premium_monthly';
            $enhanced_reference = flexpress_flowguard_generate_enhanced_reference($user_id, $plan_id);
            $test_results['reference_generation'] = 'Success: ' . $enhanced_reference;
            
            // Test 2b: Generate reference with empty fields
            $user_id_empty = wp_create_user('test_empty_' . time(), 'testpass123', 'test_empty_' . time() . '@example.com');
            if (!is_wp_error($user_id_empty)) {
                update_user_meta($user_id_empty, 'first_name', 'Empty');
                update_user_meta($user_id_empty, 'last_name', 'Test');
                // Don't set affiliate, promo, or signup source
                $empty_reference = flexpress_flowguard_generate_enhanced_reference($user_id_empty, 'basic_plan');
                $test_results['empty_fields_test'] = 'Success: ' . $empty_reference;
                
                // Parse the empty reference
                $empty_parsed = flexpress_flowguard_parse_enhanced_reference($empty_reference);
                $test_results['empty_parsing'] = 'Success: ' . json_encode($empty_parsed);
                
                wp_delete_user($user_id_empty);
            }
            
            // Test 3: Parse enhanced reference
            $parsed_data = flexpress_flowguard_parse_enhanced_reference($enhanced_reference);
            $test_results['reference_parsing'] = 'Success: ' . json_encode($parsed_data);
            
            // Test 4: Verify data
            $verification = array();
            if ($parsed_data['user_id'] == $user_id) {
                $verification[] = 'User ID: ✓';
            } else {
                $verification[] = 'User ID: ✗';
            }
            
            if ($parsed_data['affiliate_code'] == 'AFF12345') {
                $verification[] = 'Affiliate: ✓';
            } else {
                $verification[] = 'Affiliate: ✗';
            }
            
            if ($parsed_data['promo_code'] == 'WELCOME') {
                $verification[] = 'Promo: ✓';
            } else {
                $verification[] = 'Promo: ✗';
            }
            
            if ($parsed_data['signup_source'] == 'google') {
                $verification[] = 'Source: ✓';
            } else {
                $verification[] = 'Source: ✗';
            }
            
            if ($parsed_data['plan_id'] == $plan_id) {
                $verification[] = 'Plan: ✓';
            } else {
                $verification[] = 'Plan: ✗';
            }
            
            $test_results['verification'] = implode(', ', $verification);
            
            // Test 5: User meta storage
            $stored_reference = get_user_meta($user_id, 'flowguard_enhanced_reference', true);
            $stored_data = get_user_meta($user_id, 'flowguard_reference_data', true);
            
            if ($stored_reference == $enhanced_reference) {
                $test_results['meta_storage'] = 'Success: Reference stored correctly';
            } else {
                $test_results['meta_storage'] = 'Failed: Reference not stored';
            }
            
            // Cleanup
            wp_delete_user($user_id);
            $test_results['cleanup'] = 'Test user deleted';
        }
        
        wp_send_json_success($test_results);
    }
    
    /**
     * Check if enhanced columns exist in the transactions table
     * 
     * @return bool True if columns exist
     */
    private function check_enhanced_columns_exist() {
        global $wpdb;
        
        $transactions_table = $wpdb->prefix . 'flexpress_flowguard_transactions';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$transactions_table}'") == $transactions_table;
        if (!$table_exists) {
            return false;
        }
        
        // Check if enhanced columns exist
        $columns = $wpdb->get_results("DESCRIBE {$transactions_table}");
        $column_names = array_column($columns, 'Field');
        
        $required_columns = ['affiliate_code', 'promo_code', 'signup_source', 'plan_id'];
        $missing_columns = array_diff($required_columns, $column_names);
        
        return empty($missing_columns);
    }
    
    /**
     * AJAX handler for updating database schema
     */
    public function ajax_update_database_schema() {
        check_ajax_referer('flexpress_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Update the database schema
        flexpress_flowguard_create_tables();
        
        wp_send_json_success('Database schema updated successfully');
    }
}

// Initialize the reference manager
new FlexPress_Flowguard_Reference_Manager();
