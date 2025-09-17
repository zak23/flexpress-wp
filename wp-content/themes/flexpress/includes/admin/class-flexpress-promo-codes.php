<?php
/**
 * FlexPress Promo Codes Management System
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Promo Codes Class
 */
class FlexPress_Promo_Codes {
    
    /**
     * Create promo codes table
     */
    public static function create_promo_codes_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'flexpress_promo_codes';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL UNIQUE,
            name varchar(100) NOT NULL,
            description text,
            discount_type enum('percentage', 'fixed', 'free_trial') NOT NULL DEFAULT 'percentage',
            discount_value decimal(10,2) NOT NULL DEFAULT 0.00,
            minimum_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            maximum_discount decimal(10,2) NOT NULL DEFAULT 0.00,
            usage_limit int(11) NOT NULL DEFAULT 0,
            usage_count int(11) NOT NULL DEFAULT 0,
            user_limit int(11) NOT NULL DEFAULT 0,
            valid_from datetime NULL,
            valid_until datetime NULL,
            applicable_plans text,
            applicable_products text,
            status enum('active', 'inactive', 'expired') NOT NULL DEFAULT 'active',
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY status (status),
            KEY valid_from (valid_from),
            KEY valid_until (valid_until),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create promo code usage tracking table
     */
    public static function create_promo_usage_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'flexpress_promo_usage';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            promo_code_id bigint(20) NOT NULL,
            promo_code varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            order_id varchar(100) NOT NULL,
            plan_id varchar(50) NOT NULL,
            original_amount decimal(10,2) NOT NULL,
            discount_amount decimal(10,2) NOT NULL,
            final_amount decimal(10,2) NOT NULL,
            used_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            PRIMARY KEY (id),
            KEY promo_code_id (promo_code_id),
            KEY promo_code (promo_code),
            KEY user_id (user_id),
            KEY used_at (used_at),
            FOREIGN KEY (promo_code_id) REFERENCES {$wpdb->prefix}flexpress_promo_codes(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_promo_codes_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_create_promo_code', array($this, 'create_promo_code'));
        add_action('wp_ajax_update_promo_code', array($this, 'update_promo_code'));
        add_action('wp_ajax_delete_promo_code', array($this, 'delete_promo_code'));
        add_action('wp_ajax_toggle_promo_status', array($this, 'toggle_promo_status'));
        add_action('wp_ajax_get_promo_details', array($this, 'get_promo_details'));
        add_action('wp_ajax_validate_promo_code', array($this, 'validate_promo_code'));
        
        // Create tables on theme activation
        add_action('after_switch_theme', array(__CLASS__, 'create_promo_codes_table'));
        add_action('after_switch_theme', array(__CLASS__, 'create_promo_usage_table'));
    }
    
    /**
     * Add promo codes page to admin menu
     */
    public function add_promo_codes_page() {
        add_submenu_page(
            'flexpress-settings',
            __('Promo Codes', 'flexpress'),
            __('Promo Codes', 'flexpress'),
            'manage_options',
            'flexpress-promo-codes',
            array($this, 'render_promo_codes_page')
        );
    }
    
    /**
     * Render the promo codes page
     */
    public function render_promo_codes_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Promo Codes Management', 'flexpress'); ?></h1>
            
            <div class="promo-codes-dashboard">
                <!-- Stats Overview -->
                <div class="stats-cards">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3><?php esc_html_e('Active Codes', 'flexpress'); ?></h3>
                            <div class="stat-number"><?php echo $this->get_active_promo_count(); ?></div>
                        </div>
                        <div class="stat-card">
                            <h3><?php esc_html_e('Total Usage', 'flexpress'); ?></h3>
                            <div class="stat-number"><?php echo $this->get_total_usage_count(); ?></div>
                        </div>
                        <div class="stat-card">
                            <h3><?php esc_html_e('Total Discounts', 'flexpress'); ?></h3>
                            <div class="stat-number">$<?php echo number_format($this->get_total_discounts(), 2); ?></div>
                        </div>
                        <div class="stat-card">
                            <h3><?php esc_html_e('Expired Codes', 'flexpress'); ?></h3>
                            <div class="stat-number"><?php echo $this->get_expired_promo_count(); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="promo-actions">
                    <button type="button" class="button button-primary" id="add-new-promo">
                        <?php esc_html_e('Add New Promo Code', 'flexpress'); ?>
                    </button>
                    <button type="button" class="button" id="bulk-actions">
                        <?php esc_html_e('Bulk Actions', 'flexpress'); ?>
                    </button>
                </div>

                <!-- Promo Codes Table -->
                <div class="promo-codes-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all-promos"></th>
                                <th><?php esc_html_e('Code', 'flexpress'); ?></th>
                                <th><?php esc_html_e('Name', 'flexpress'); ?></th>
                                <th><?php esc_html_e('Discount', 'flexpress'); ?></th>
                                <th><?php esc_html_e('Usage', 'flexpress'); ?></th>
                                <th><?php esc_html_e('Status', 'flexpress'); ?></th>
                                <th><?php esc_html_e('Valid Until', 'flexpress'); ?></th>
                                <th><?php esc_html_e('Actions', 'flexpress'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="promo-codes-list">
                            <?php $this->render_promo_codes_table(); ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add/Edit Promo Code Modal -->
            <div id="promo-modal" class="promo-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modal-title"><?php esc_html_e('Add New Promo Code', 'flexpress'); ?></h2>
                        <span class="modal-close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="promo-form">
                            <input type="hidden" id="promo-id" name="promo_id">
                            
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="promo-code"><?php esc_html_e('Promo Code', 'flexpress'); ?> *</label>
                                    <input type="text" id="promo-code" name="code" required 
                                           placeholder="<?php esc_attr_e('e.g., SAVE20, WELCOME10', 'flexpress'); ?>">
                                    <p class="description"><?php esc_html_e('Unique code that customers will enter', 'flexpress'); ?></p>
                                </div>
                                <div class="form-field">
                                    <label for="promo-name"><?php esc_html_e('Name', 'flexpress'); ?> *</label>
                                    <input type="text" id="promo-name" name="name" required 
                                           placeholder="<?php esc_attr_e('e.g., Summer Sale 20% Off', 'flexpress'); ?>">
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label for="promo-description"><?php esc_html_e('Description', 'flexpress'); ?></label>
                                <textarea id="promo-description" name="description" rows="3" 
                                          placeholder="<?php esc_attr_e('Describe this promo code...', 'flexpress'); ?>"></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="discount-type"><?php esc_html_e('Discount Type', 'flexpress'); ?> *</label>
                                    <select id="discount-type" name="discount_type" required>
                                        <option value="percentage"><?php esc_html_e('Percentage', 'flexpress'); ?></option>
                                        <option value="fixed"><?php esc_html_e('Fixed Amount', 'flexpress'); ?></option>
                                        <option value="free_trial"><?php esc_html_e('Free Trial', 'flexpress'); ?></option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="discount-value"><?php esc_html_e('Discount Value', 'flexpress'); ?> *</label>
                                    <input type="number" id="discount-value" name="discount_value" required 
                                           step="0.01" min="0" placeholder="0.00">
                                    <p class="description" id="discount-description"><?php esc_html_e('Percentage or fixed amount', 'flexpress'); ?></p>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="minimum-amount"><?php esc_html_e('Minimum Order Amount', 'flexpress'); ?></label>
                                    <input type="number" id="minimum-amount" name="minimum_amount" 
                                           step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="form-field">
                                    <label for="maximum-discount"><?php esc_html_e('Maximum Discount', 'flexpress'); ?></label>
                                    <input type="number" id="maximum-discount" name="maximum_discount" 
                                           step="0.01" min="0" placeholder="0.00">
                                    <p class="description"><?php esc_html_e('Maximum discount amount (for percentage discounts)', 'flexpress'); ?></p>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="usage-limit"><?php esc_html_e('Usage Limit', 'flexpress'); ?></label>
                                    <input type="number" id="usage-limit" name="usage_limit" 
                                           min="0" placeholder="0">
                                    <p class="description"><?php esc_html_e('Total number of times this code can be used (0 = unlimited)', 'flexpress'); ?></p>
                                </div>
                                <div class="form-field">
                                    <label for="user-limit"><?php esc_html_e('Per User Limit', 'flexpress'); ?></label>
                                    <input type="number" id="user-limit" name="user_limit" 
                                           min="0" placeholder="0">
                                    <p class="description"><?php esc_html_e('How many times each user can use this code (0 = unlimited)', 'flexpress'); ?></p>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-field">
                                    <label for="valid-from"><?php esc_html_e('Valid From', 'flexpress'); ?></label>
                                    <input type="datetime-local" id="valid-from" name="valid_from">
                                </div>
                                <div class="form-field">
                                    <label for="valid-until"><?php esc_html_e('Valid Until', 'flexpress'); ?></label>
                                    <input type="datetime-local" id="valid-until" name="valid_until">
                                </div>
                            </div>
                            
                            <div class="form-field">
                                <label for="applicable-plans"><?php esc_html_e('Applicable Plans', 'flexpress'); ?></label>
                                <select id="applicable-plans" name="applicable_plans[]" multiple>
                                    <option value="all"><?php esc_html_e('All Plans', 'flexpress'); ?></option>
                                    <option value="monthly"><?php esc_html_e('Monthly Plans', 'flexpress'); ?></option>
                                    <option value="yearly"><?php esc_html_e('Yearly Plans', 'flexpress'); ?></option>
                                    <option value="ppv"><?php esc_html_e('PPV Content', 'flexpress'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Hold Ctrl/Cmd to select multiple options', 'flexpress'); ?></p>
                            </div>
                            
                            <div class="form-field">
                                <label for="promo-status"><?php esc_html_e('Status', 'flexpress'); ?> *</label>
                                <select id="promo-status" name="status" required>
                                    <option value="active"><?php esc_html_e('Active', 'flexpress'); ?></option>
                                    <option value="inactive"><?php esc_html_e('Inactive', 'flexpress'); ?></option>
                                </select>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="button button-primary"><?php esc_html_e('Save Promo Code', 'flexpress'); ?></button>
                                <button type="button" class="button modal-close"><?php esc_html_e('Cancel', 'flexpress'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- View Promo Details Modal -->
            <div id="view-promo-modal" class="promo-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><?php esc_html_e('Promo Code Details', 'flexpress'); ?></h2>
                        <span class="modal-close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div id="promo-details-content">
                            <!-- Content will be loaded dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .promo-codes-dashboard {
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
        .promo-actions {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .promo-modal {
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
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
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
        .form-field select[multiple] {
            height: 100px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .status-active { color: #46b450; }
        .status-inactive { color: #dc3232; }
        .status-expired { color: #ffb900; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Modal functionality
            $('.modal-close').on('click', function() {
                $(this).closest('.promo-modal').fadeOut();
            });
            
            // Close modal when clicking outside
            $('.promo-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).fadeOut();
                }
            });
            
            // Add new promo button
            $('#add-new-promo').on('click', function() {
                $('#modal-title').text('<?php esc_html_e('Add New Promo Code', 'flexpress'); ?>');
                $('#promo-form')[0].reset();
                $('#promo-id').val('');
                $('#promo-modal').fadeIn(300);
            });
            
            // Discount type change handler
            $('#discount-type').on('change', function() {
                var type = $(this).val();
                var description = $('#discount-description');
                
                switch(type) {
                    case 'percentage':
                        description.text('<?php esc_html_e('Percentage discount (e.g., 20 for 20%)', 'flexpress'); ?>');
                        break;
                    case 'fixed':
                        description.text('<?php esc_html_e('Fixed amount discount (e.g., 10.00)', 'flexpress'); ?>');
                        break;
                    case 'free_trial':
                        description.text('<?php esc_html_e('Number of free trial days', 'flexpress'); ?>');
                        break;
                }
            });
            
            // Form submission
            $('#promo-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'create_promo_code');
                formData.append('nonce', '<?php echo wp_create_nonce('flexpress_promo_nonce'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('<?php esc_html_e('An error occurred. Please try again.', 'flexpress'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'flexpress_page_flexpress-promo-codes') {
            return;
        }

        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'ajaxurl', admin_url('admin-ajax.php'));
    }
    
    /**
     * Get active promo count
     */
    public function get_active_promo_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_promo_codes';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'active'");
        return intval($count ?: 0);
    }
    
    /**
     * Get total usage count
     */
    public function get_total_usage_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_promo_usage';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        return intval($count ?: 0);
    }
    
    /**
     * Get total discounts given
     */
    public function get_total_discounts() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_promo_usage';
        
        $total = $wpdb->get_var("SELECT SUM(discount_amount) FROM {$table_name}");
        return floatval($total ?: 0);
    }
    
    /**
     * Get expired promo count
     */
    public function get_expired_promo_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_promo_codes';
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'expired' OR (valid_until IS NOT NULL AND valid_until < NOW())");
        return intval($count ?: 0);
    }
    
    /**
     * Render promo codes table
     */
    private function render_promo_codes_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_promo_codes';
        
        $promo_codes = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 50"
        );
        
        if (empty($promo_codes)) {
            echo '<tr><td colspan="8">' . esc_html__('No promo codes found.', 'flexpress') . '</td></tr>';
            return;
        }
        
        foreach ($promo_codes as $promo) {
            $status_class = 'status-' . $promo->status;
            $status_label = ucfirst($promo->status);
            
            // Format discount
            $discount_display = '';
            if ($promo->discount_type === 'percentage') {
                $discount_display = $promo->discount_value . '%';
            } elseif ($promo->discount_type === 'fixed') {
                $discount_display = '$' . number_format($promo->discount_value, 2);
            } elseif ($promo->discount_type === 'free_trial') {
                $discount_display = $promo->discount_value . ' days free';
            }
            
            // Format usage
            $usage_display = $promo->usage_count;
            if ($promo->usage_limit > 0) {
                $usage_display .= ' / ' . $promo->usage_limit;
            }
            
            // Format valid until
            $valid_until_display = 'Never';
            if ($promo->valid_until) {
                $valid_until_display = date('M j, Y', strtotime($promo->valid_until));
            }
            
            echo '<tr>';
            echo '<td><input type="checkbox" class="promo-checkbox" value="' . esc_attr($promo->id) . '"></td>';
            echo '<td><strong>' . esc_html($promo->code) . '</strong></td>';
            echo '<td>' . esc_html($promo->name) . '</td>';
            echo '<td>' . esc_html($discount_display) . '</td>';
            echo '<td>' . esc_html($usage_display) . '</td>';
            echo '<td><span class="status ' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span></td>';
            echo '<td>' . esc_html($valid_until_display) . '</td>';
            echo '<td>';
            echo '<button type="button" class="button button-small view-promo" data-id="' . esc_attr($promo->id) . '">' . esc_html__('View', 'flexpress') . '</button> ';
            echo '<button type="button" class="button button-small edit-promo" data-id="' . esc_attr($promo->id) . '">' . esc_html__('Edit', 'flexpress') . '</button> ';
            echo '<button type="button" class="button button-small delete-promo" data-id="' . esc_attr($promo->id) . '">' . esc_html__('Delete', 'flexpress') . '</button>';
            echo '</td>';
            echo '</tr>';
        }
    }
    
    /**
     * Create promo code via AJAX
     */
    public function create_promo_code() {
        check_ajax_referer('flexpress_promo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $code = sanitize_text_field($_POST['code']);
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $discount_type = sanitize_text_field($_POST['discount_type']);
        $discount_value = floatval($_POST['discount_value']);
        $minimum_amount = floatval($_POST['minimum_amount'] ?? 0);
        $maximum_discount = floatval($_POST['maximum_discount'] ?? 0);
        $usage_limit = intval($_POST['usage_limit'] ?? 0);
        $user_limit = intval($_POST['user_limit'] ?? 0);
        $valid_from = sanitize_text_field($_POST['valid_from'] ?? '');
        $valid_until = sanitize_text_field($_POST['valid_until'] ?? '');
        $applicable_plans = sanitize_text_field($_POST['applicable_plans'] ?? '');
        $status = sanitize_text_field($_POST['status']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_promo_codes';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'discount_type' => $discount_type,
                'discount_value' => $discount_value,
                'minimum_amount' => $minimum_amount,
                'maximum_discount' => $maximum_discount,
                'usage_limit' => $usage_limit,
                'user_limit' => $user_limit,
                'valid_from' => $valid_from ?: null,
                'valid_until' => $valid_until ?: null,
                'applicable_plans' => $applicable_plans,
                'status' => $status,
                'created_by' => get_current_user_id()
            ),
            array('%s', '%s', '%s', '%s', '%f', '%f', '%f', '%d', '%d', '%s', '%s', '%s', '%s', '%d')
        );
        
        if ($result) {
            wp_send_json_success(array('message' => 'Promo code created successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to create promo code: ' . $wpdb->last_error));
        }
    }
    
    /**
     * Validate promo code
     */
    public function validate_promo_code() {
        $code = sanitize_text_field($_POST['code']);
        $user_id = get_current_user_id();
        $plan_id = sanitize_text_field($_POST['plan_id'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        
        $validation = $this->validate_promo_code_logic($code, $user_id, $plan_id, $amount);
        
        if ($validation['valid']) {
            wp_send_json_success($validation);
        } else {
            wp_send_json_error($validation);
        }
    }
    
    /**
     * Validate promo code logic
     */
    public function validate_promo_code_logic($code, $user_id, $plan_id, $amount) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_promo_codes';
        
        // Get promo code
        $promo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE code = %s AND status = 'active'",
            $code
        ));
        
        if (!$promo) {
            return array('valid' => false, 'message' => 'Invalid promo code');
        }
        
        // Check if code is expired
        if ($promo->valid_until && strtotime($promo->valid_until) < time()) {
            return array('valid' => false, 'message' => 'Promo code has expired');
        }
        
        // Check if code is not yet valid
        if ($promo->valid_from && strtotime($promo->valid_from) > time()) {
            return array('valid' => false, 'message' => 'Promo code is not yet valid');
        }
        
        // Check usage limit
        if ($promo->usage_limit > 0 && $promo->usage_count >= $promo->usage_limit) {
            return array('valid' => false, 'message' => 'Promo code usage limit reached');
        }
        
        // Check user limit
        if ($promo->user_limit > 0 && $user_id) {
            $user_usage = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}flexpress_promo_usage WHERE promo_code_id = %d AND user_id = %d",
                $promo->id, $user_id
            ));
            
            if ($user_usage >= $promo->user_limit) {
                return array('valid' => false, 'message' => 'You have already used this promo code');
            }
        }
        
        // Check minimum amount
        if ($promo->minimum_amount > 0 && $amount < $promo->minimum_amount) {
            return array('valid' => false, 'message' => 'Minimum order amount not met');
        }
        
        // Calculate discount
        $discount_amount = 0;
        if ($promo->discount_type === 'percentage') {
            $discount_amount = ($amount * $promo->discount_value) / 100;
            if ($promo->maximum_discount > 0) {
                $discount_amount = min($discount_amount, $promo->maximum_discount);
            }
        } elseif ($promo->discount_type === 'fixed') {
            $discount_amount = min($promo->discount_value, $amount);
        } elseif ($promo->discount_type === 'free_trial') {
            $discount_amount = $amount; // Full discount for free trial
        }
        
        $final_amount = max(0, $amount - $discount_amount);
        
        return array(
            'valid' => true,
            'promo_id' => $promo->id,
            'discount_amount' => $discount_amount,
            'final_amount' => $final_amount,
            'message' => 'Promo code applied successfully'
        );
    }
    
    // Placeholder methods for other AJAX actions
    public function update_promo_code() {
        // Implementation needed
    }
    
    public function delete_promo_code() {
        // Implementation needed
    }
    
    public function toggle_promo_status() {
        // Implementation needed
    }
    
    public function get_promo_details() {
        // Implementation needed
    }
}

// Initialize the promo codes system
new FlexPress_Promo_Codes();
