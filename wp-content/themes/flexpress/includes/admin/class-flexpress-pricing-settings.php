<?php

/**
 * FlexPress Pricing Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Pricing Settings Class
 */
class FlexPress_Pricing_Settings
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_pricing_settings_page'));
        add_action('admin_init', array($this, 'register_pricing_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_save_pricing_plan', array($this, 'save_pricing_plan'));
        add_action('wp_ajax_delete_pricing_plan', array($this, 'delete_pricing_plan'));
        add_action('wp_ajax_toggle_plan_status', array($this, 'toggle_plan_status'));
        add_action('wp_ajax_get_pricing_plan', array($this, 'get_pricing_plan'));
        add_action('wp_ajax_test_flowguard_connection', array($this, 'test_flowguard_connection'));
        add_action('wp_ajax_validate_pricing_plans', array($this, 'validate_pricing_plans'));
    }

    /**
     * Add the pricing settings page to admin menu
     */
    public function add_pricing_settings_page()
    {
        add_submenu_page(
            'flexpress-settings',
            __('Pricing Plans', 'flexpress'),
            __('Pricing Plans', 'flexpress'),
            'manage_options',
            'flexpress-pricing-settings',
            array($this, 'render_pricing_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_pricing_settings()
    {
        register_setting('flexpress_pricing_settings', 'flexpress_pricing_plans', array(
            'sanitize_callback' => array($this, 'sanitize_pricing_plans')
        ));
    }

    /**
     * Sanitize pricing plans data
     */
    public function sanitize_pricing_plans($input)
    {
        if (!is_array($input)) {
            return array();
        }

        $sanitized = array();
        foreach ($input as $plan_id => $plan) {
            $sanitized[$plan_id] = array(
                'name' => sanitize_text_field($plan['name'] ?? ''),
                'description' => sanitize_textarea_field($plan['description'] ?? ''),
                'price' => floatval($plan['price'] ?? 0),
                'currency' => sanitize_text_field($plan['currency'] ?? '$'),
                'duration' => intval($plan['duration'] ?? 30),
                'duration_unit' => sanitize_text_field($plan['duration_unit'] ?? 'days'),
                'plan_type' => sanitize_text_field($plan['plan_type'] ?? 'recurring'),
                'trial_enabled' => (isset($plan['trial_enabled']) && $plan['trial_enabled'] !== 0 && $plan['trial_enabled'] !== '0') ? 1 : 0,
                'trial_price' => floatval($plan['trial_price'] ?? 0),
                'trial_duration' => intval($plan['trial_duration'] ?? 0),
                'trial_duration_unit' => sanitize_text_field($plan['trial_duration_unit'] ?? 'days'),
                'featured' => (isset($plan['featured']) && $plan['featured'] !== 0 && $plan['featured'] !== '0') ? 1 : 0,
                'active' => (isset($plan['active']) && $plan['active'] !== 0 && $plan['active'] !== '0') ? 1 : 0,
                'promo_only' => (isset($plan['promo_only']) && $plan['promo_only'] !== 0 && $plan['promo_only'] !== '0') ? 1 : 0,
                'promo_codes' => sanitize_textarea_field($plan['promo_codes'] ?? ''),
                'flowguard_shop_id' => sanitize_text_field($plan['flowguard_shop_id'] ?? ''),
                'flowguard_product_id' => sanitize_text_field($plan['flowguard_product_id'] ?? ''),
                'sort_order' => intval($plan['sort_order'] ?? 0),
            );
        }

        return $sanitized;
    }

    /**
     * Render the pricing settings page
     */
    public function render_pricing_settings_page()
    {
        $pricing_plans = get_option('flexpress_pricing_plans', array());
?>
        <div class="wrap">
            <h1><?php echo esc_html__('Pricing Plans Management', 'flexpress'); ?></h1>

            <div class="notice notice-info">
                <p><?php esc_html_e('Manage your subscription pricing plans. Changes will be reflected on the join page and throughout the site.', 'flexpress'); ?></p>
                <p><strong><?php esc_html_e('Flowguard Integration:', 'flexpress'); ?></strong> <?php esc_html_e('Plans are now integrated with Flowguard payment processing. Make sure your Flowguard settings are configured.', 'flexpress'); ?></p>
            </div>

            <div class="pricing-plans-container">
                <div class="pricing-plans-header">
                    <button type="button" class="button button-primary" id="add-new-plan">
                        <?php esc_html_e('Add New Plan', 'flexpress'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="test-flowguard-connection">
                        <?php esc_html_e('Test Flowguard Connection', 'flexpress'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="validate-pricing-plans">
                        <?php esc_html_e('Validate Plans', 'flexpress'); ?>
                    </button>
                </div>

                <div id="pricing-plans-list">
                    <?php if (empty($pricing_plans)): ?>
                        <div class="no-plans-message">
                            <p><?php esc_html_e('No pricing plans configured yet. Click "Add New Plan" to create your first plan.', 'flexpress'); ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pricing_plans as $plan_id => $plan): ?>
                            <?php $this->render_pricing_plan_card($plan_id, $plan); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Plan Edit Modal -->
            <div id="plan-edit-modal" class="pricing-modal" style="display: none;">
                <div class="pricing-modal-content">
                    <div class="pricing-modal-header">
                        <h2 id="modal-title"><?php esc_html_e('Edit Pricing Plan', 'flexpress'); ?></h2>
                        <span class="pricing-modal-close">&times;</span>
                    </div>
                    <div class="pricing-modal-body">
                        <?php $this->render_plan_form(); ?>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .pricing-plans-container {
                margin-top: 20px;
            }

            .pricing-plans-header {
                margin-bottom: 20px;
            }

            .pricing-plan-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                margin-bottom: 15px;
                padding: 20px;
                position: relative;
            }

            .pricing-plan-card.featured {
                border-color: #007cba;
                box-shadow: 0 0 0 1px #007cba;
            }

            .pricing-plan-card.inactive {
                opacity: 0.6;
            }

            .pricing-plan-card.plan-one-time {
                border-color: #28a745;
                box-shadow: 0 0 0 1px #28a745;
            }

            .plan-badge.one-time {
                background: #28a745;
            }

            .plan-badge.featured {
                background: #007cba;
            }

            .plan-badge.inactive {
                background: #666;
            }

            .plan-pricing {
                text-align: right;
            }

            .plan-price {
                font-size: 24px;
                font-weight: bold;
                color: #007cba;
            }

            .plan-duration {
                font-size: 14px;
                color: #666;
                margin-top: 5px;
            }

            .plan-trial {
                font-size: 12px;
                color: #28a745;
                margin-top: 5px;
            }

            .plan-description {
                margin: 10px 0;
                color: #666;
            }

            .plan-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 15px;
            }

            .plan-title {
                font-size: 18px;
                font-weight: 600;
                margin: 0;
            }

            .plan-price {
                font-size: 24px;
                font-weight: bold;
                color: #007cba;
            }

            .plan-details {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 15px;
            }

            .plan-actions {
                display: flex;
                gap: 10px;
            }

            .plan-badge {
                background: #007cba;
                color: white;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 12px;
                margin-left: 10px;
            }

            .plan-badge.inactive {
                background: #666;
            }

            .plan-badge.lifetime {
                background: #28a745;
            }

            .form-row input.error,
            .form-row select.error,
            .form-row textarea.error {
                border-color: #dc3232;
                box-shadow: 0 0 2px rgba(220, 50, 50, 0.8);
            }

            .error-message {
                color: #dc3232;
                font-size: 12px;
                margin-top: 5px;
                display: block;
            }

            .pricing-modal {
                position: fixed;
                z-index: 100000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
            }

            .pricing-modal-content {
                background-color: #fefefe;
                margin: 5% auto;
                padding: 0;
                border: 1px solid #888;
                width: 80%;
                max-width: 800px;
                border-radius: 4px;
                max-height: 90vh;
                overflow-y: auto;
            }

            .pricing-modal-header {
                padding: 20px;
                background: #f1f1f1;
                border-bottom: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .pricing-modal-header h2 {
                margin: 0;
            }

            .pricing-modal-close {
                color: #aaa;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }

            .pricing-modal-close:hover {
                color: #000;
            }

            .pricing-modal-body {
                padding: 20px;
            }

            .form-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
            }

            .form-section {
                background: #f9f9f9;
                padding: 15px;
                border-radius: 4px;
            }

            .form-section h3 {
                margin-top: 0;
                font-size: 16px;
            }

            .form-row {
                margin-bottom: 15px;
            }

            .form-row label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
            }

            .form-row input,
            .form-row select,
            .form-row textarea {
                width: 100%;
            }

            .checkbox-row {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .checkbox-row input[type="checkbox"] {
                width: auto;
            }
        </style>
    <?php
    }

    /**
     * Render a pricing plan card
     */
    private function render_pricing_plan_card($plan_id, $plan)
    {
        $is_featured = !empty($plan['featured']);
        $is_active = !empty($plan['active']);
        $is_one_time = isset($plan['plan_type']) && $plan['plan_type'] === 'one_time';
        $is_lifetime = isset($plan['plan_type']) && $plan['plan_type'] === 'lifetime';
        $plan_type_class = $is_one_time ? 'plan-one-time' : ($is_lifetime ? 'plan-lifetime' : 'plan-recurring');
    ?>
        <div class="pricing-plan-card <?php echo $is_featured ? 'featured' : ''; ?> <?php echo !$is_active ? 'inactive' : ''; ?> <?php echo $plan_type_class; ?>"
            data-plan-id="<?php echo esc_attr($plan_id); ?>"
            data-plan-type="<?php echo esc_attr($plan['plan_type'] ?? 'recurring'); ?>">
            <div class="plan-header">
                <div>
                    <h3 class="plan-title">
                        <?php echo esc_html($plan['name'] ?? 'Unnamed Plan'); ?>
                        <?php if ($is_featured): ?>
                            <span class="plan-badge featured"><?php esc_html_e('Featured', 'flexpress'); ?></span>
                        <?php endif; ?>
                        <?php if (!$is_active): ?>
                            <span class="plan-badge inactive"><?php esc_html_e('Inactive', 'flexpress'); ?></span>
                        <?php endif; ?>
                        <?php if ($is_one_time): ?>
                            <span class="plan-badge one-time"><?php esc_html_e('One-Time', 'flexpress'); ?></span>
                        <?php elseif ($is_lifetime): ?>
                            <span class="plan-badge lifetime"><?php esc_html_e('Lifetime', 'flexpress'); ?></span>
                        <?php endif; ?>
                    </h3>
                    <p class="plan-description"><?php echo esc_html($plan['description'] ?? ''); ?></p>
                </div>

                <div class="plan-pricing">
                    <div class="plan-price">
                        <?php echo esc_html($plan['currency'] ?? '$'); ?><?php echo number_format($plan['price'] ?? 0, 2); ?>
                    </div>
                    <?php if (!$is_one_time): ?>
                        <div class="plan-duration">
                            <?php echo esc_html(sprintf(
                                __('every %s', 'flexpress'),
                                flexpress_format_plan_duration($plan)
                            )); ?>
                        </div>
                        <?php if (!empty($plan['trial_enabled'])): ?>
                            <div class="plan-trial">
                                <?php echo esc_html(sprintf(
                                    __('Trial: %s%s for %s', 'flexpress'),
                                    $plan['currency'] ?? '$',
                                    number_format($plan['trial_price'] ?? 0, 2),
                                    flexpress_format_plan_trial_duration($plan)
                                )); ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="plan-duration">
                            <?php esc_html_e('One-time payment', 'flexpress'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="plan-actions">
                <button type="button" class="button edit-plan" data-plan-id="<?php echo esc_attr($plan_id); ?>">
                    <?php esc_html_e('Edit', 'flexpress'); ?>
                </button>
                <button type="button" class="button toggle-plan-status" data-plan-id="<?php echo esc_attr($plan_id); ?>">
                    <?php echo $is_active ? esc_html__('Deactivate', 'flexpress') : esc_html__('Activate', 'flexpress'); ?>
                </button>
                <button type="button" class="button button-link-delete delete-plan" data-plan-id="<?php echo esc_attr($plan_id); ?>">
                    <?php esc_html_e('Delete', 'flexpress'); ?>
                </button>
            </div>
        </div>
    <?php
    }

    /**
     * Render the plan form
     */
    private function render_plan_form()
    {
    ?>
        <form id="pricing-plan-form">
            <input type="hidden" id="plan-id" name="plan_id" value="">

            <div class="form-grid">
                <div class="form-section">
                    <h3><?php esc_html_e('Basic Information', 'flexpress'); ?></h3>

                    <div class="form-row">
                        <label for="plan-name"><?php esc_html_e('Plan Name', 'flexpress'); ?></label>
                        <input type="text" id="plan-name" name="name" required>
                    </div>

                    <div class="form-row">
                        <label for="plan-description"><?php esc_html_e('Description', 'flexpress'); ?></label>
                        <textarea id="plan-description" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-row">
                        <label for="plan-price"><?php esc_html_e('Price', 'flexpress'); ?></label>
                        <input type="number" id="plan-price" name="price" step="0.01" min="0" required>
                    </div>

                    <div class="form-row">
                        <label for="plan-currency"><?php esc_html_e('Currency', 'flexpress'); ?></label>
                        <select id="plan-currency" name="currency" required>
                            <option value="$">USD ($)</option>
                            <option value="€">EUR (€)</option>
                            <option value="£">GBP (£)</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label for="plan-type"><?php esc_html_e('Plan Type', 'flexpress'); ?></label>
                        <select id="plan-type" name="plan_type" required>
                            <option value="recurring"><?php esc_html_e('Recurring Subscription', 'flexpress'); ?></option>
                            <option value="one_time"><?php esc_html_e('One-Time Payment', 'flexpress'); ?></option>
                            <option value="lifetime"><?php esc_html_e('Lifetime Access', 'flexpress'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-section" id="duration-section">
                    <h3><?php esc_html_e('Duration Settings', 'flexpress'); ?></h3>

                    <div class="form-row">
                        <label for="plan-duration" id="plan-duration-label"><?php esc_html_e('Duration', 'flexpress'); ?></label>
                        <input type="number" id="plan-duration" name="duration" min="1" required>
                    </div>

                    <div class="form-row">
                        <label for="plan-duration-unit"><?php esc_html_e('Duration Unit', 'flexpress'); ?></label>
                        <select id="plan-duration-unit" name="duration_unit" required>
                            <option value="days"><?php esc_html_e('Days', 'flexpress'); ?></option>
                            <option value="months"><?php esc_html_e('Months', 'flexpress'); ?></option>
                            <option value="years"><?php esc_html_e('Years', 'flexpress'); ?></option>
                        </select>
                        <p class="description duration-note" style="display: none;">
                            <?php esc_html_e('For lifetime access plans, duration is automatically set to 999 years.', 'flexpress'); ?>
                        </p>
                    </div>

                    <div class="form-row">
                        <label for="plan-sort-order"><?php esc_html_e('Sort Order', 'flexpress'); ?></label>
                        <input type="number" id="plan-sort-order" name="sort_order" min="0" value="0">
                    </div>
                </div>

                <div class="form-section">
                    <h3><?php esc_html_e('Trial Settings', 'flexpress'); ?></h3>

                    <div class="form-row checkbox-row">
                        <input type="checkbox" id="trial-enabled" name="trial_enabled">
                        <label for="trial-enabled"><?php esc_html_e('Enable Trial Period', 'flexpress'); ?></label>
                    </div>

                    <div class="trial-settings" style="display: none;">
                        <div class="form-row">
                            <label for="trial-price"><?php esc_html_e('Trial Price', 'flexpress'); ?></label>
                            <input type="number" id="trial-price" name="trial_price" step="0.01" min="0">
                        </div>

                        <div class="form-row">
                            <label for="trial-duration"><?php esc_html_e('Trial Duration', 'flexpress'); ?></label>
                            <input type="number" id="trial-duration" name="trial_duration" min="3">
                        </div>

                        <div class="form-row">
                            <label for="trial-duration-unit"><?php esc_html_e('Trial Duration Unit', 'flexpress'); ?></label>
                            <select id="trial-duration-unit" name="trial_duration_unit">
                                <option value="days"><?php esc_html_e('Days', 'flexpress'); ?></option>
                                <option value="months"><?php esc_html_e('Months', 'flexpress'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><?php esc_html_e('Flowguard Integration', 'flexpress'); ?></h3>

                    <div class="form-row">
                        <label for="flowguard-shop-id"><?php esc_html_e('Flowguard Shop ID', 'flexpress'); ?></label>
                        <input type="text" id="flowguard-shop-id" name="flowguard_shop_id" placeholder="134837">
                        <p class="description"><?php esc_html_e('Optional: Override global Shop ID for this plan', 'flexpress'); ?></p>
                    </div>

                    <div class="form-row">
                        <label for="flowguard-product-id"><?php esc_html_e('Flowguard Product ID', 'flexpress'); ?></label>
                        <input type="text" id="flowguard-product-id" name="flowguard_product_id" placeholder="plan_monthly_995">
                        <p class="description"><?php esc_html_e('Optional: Custom product identifier for this plan', 'flexpress'); ?></p>
                    </div>
                </div>

                <div class="form-section">
                    <h3><?php esc_html_e('Display Options', 'flexpress'); ?></h3>

                    <div class="form-row checkbox-row">
                        <input type="checkbox" id="plan-featured" name="featured">
                        <label for="plan-featured"><?php esc_html_e('Featured Plan', 'flexpress'); ?></label>
                    </div>

                    <div class="form-row checkbox-row">
                        <input type="checkbox" id="plan-active" name="active" checked>
                        <label for="plan-active"><?php esc_html_e('Active', 'flexpress'); ?></label>
                    </div>
                </div>

                <div class="form-section">
                    <h3><?php esc_html_e('Promotional Settings', 'flexpress'); ?></h3>
                    <div class="form-row checkbox-row">
                        <input type="checkbox" id="plan-promo-only" name="promo_only">
                        <label for="plan-promo-only"><?php esc_html_e('Promo Only', 'flexpress'); ?></label>
                    </div>
                    <div class="form-row" id="promo-codes-container" style="display: none;">
                        <label for="plan-promo-codes"><?php esc_html_e('Promo Codes', 'flexpress'); ?></label>
                        <textarea id="plan-promo-codes" name="promo_codes" rows="3" placeholder="Enter comma-separated codes"></textarea>
                        <p class="description"><?php esc_html_e('Comma-separated list of codes that unlock this plan.', 'flexpress'); ?></p>
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px; text-align: right;">
                <button type="button" class="button" id="cancel-plan-edit">
                    <?php esc_html_e('Cancel', 'flexpress'); ?>
                </button>
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Save Plan', 'flexpress'); ?>
                </button>
            </div>
        </form>
<?php
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        // Check for multiple possible hook names
        if (
            $hook !== 'flexpress-settings_page_flexpress-pricing-settings' &&
            $hook !== 'flexpress_page_flexpress-pricing-settings'
        ) {
            return;
        }

        wp_enqueue_script(
            'flexpress-pricing-admin',
            get_template_directory_uri() . '/assets/js/pricing-admin.js',
            array('jquery'),
            '1.7.0', // Updated version to force cache refresh
            true
        );

        wp_localize_script('flexpress-pricing-admin', 'flexpressPricing', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flexpress_pricing_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this pricing plan?', 'flexpress'),
                'error' => __('An error occurred. Please try again.', 'flexpress'),
            ),
        ));
    }

    /**
     * AJAX handler to save pricing plan
     */
    public function save_pricing_plan()
    {
        // Debug: Log the incoming request
        error_log('FlexPress Pricing: Save plan request received');
        error_log('FlexPress Pricing: POST data: ' . print_r($_POST, true));

        check_ajax_referer('flexpress_pricing_nonce', 'nonce');

        if (!flexpress_current_user_is_founder()) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $plan_data = array(
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'price' => floatval($_POST['price'] ?? 0),
            'currency' => sanitize_text_field($_POST['currency'] ?? '$'),
            'duration' => intval($_POST['duration'] ?? 30),
            'duration_unit' => sanitize_text_field($_POST['duration_unit'] ?? 'days'),
            'plan_type' => sanitize_text_field($_POST['plan_type'] ?? 'recurring'),
            'trial_enabled' => (isset($_POST['trial_enabled']) && $_POST['trial_enabled'] !== '0') ? 1 : 0,
            'trial_price' => floatval($_POST['trial_price'] ?? 0),
            'trial_duration' => intval($_POST['trial_duration'] ?? 0),
            'trial_duration_unit' => sanitize_text_field($_POST['trial_duration_unit'] ?? 'days'),
            'featured' => (isset($_POST['featured']) && $_POST['featured'] !== '0') ? 1 : 0,
            'active' => (isset($_POST['active']) && $_POST['active'] !== '0') ? 1 : 0,
            'promo_only' => (isset($_POST['promo_only']) && $_POST['promo_only'] !== '0') ? 1 : 0,
            'promo_codes' => sanitize_textarea_field($_POST['promo_codes'] ?? ''),
            'flowguard_shop_id' => sanitize_text_field($_POST['flowguard_shop_id'] ?? ''),
            'flowguard_product_id' => sanitize_text_field($_POST['flowguard_product_id'] ?? ''),
            'sort_order' => intval($_POST['sort_order'] ?? 0),
        );

        // Verotel compliance: Trial periods must be at least 3 days
        if ($plan_data['trial_enabled'] && $plan_data['trial_duration_unit'] === 'days' && $plan_data['trial_duration'] < 3) {
            wp_send_json_error('Trial period must be at least 3 days (Verotel requirement)');
            return;
        }

        $pricing_plans = get_option('flexpress_pricing_plans', array());
        $plan_id = sanitize_text_field($_POST['plan_id'] ?? '');

        if (empty($plan_id)) {
            // Generate new plan ID
            $plan_id = 'plan_' . time();
        }

        $pricing_plans[$plan_id] = $plan_data;
        $update_result = update_option('flexpress_pricing_plans', $pricing_plans);

        // Debug: Log the save result
        error_log('FlexPress Pricing: Plan data prepared: ' . print_r($plan_data, true));
        error_log('FlexPress Pricing: Update result: ' . ($update_result ? 'SUCCESS' : 'FAILED'));
        error_log('FlexPress Pricing: Plan ID: ' . $plan_id);

        // Debug: Verify the data was actually saved
        $saved_plans = get_option('flexpress_pricing_plans', array());
        error_log('FlexPress Pricing: Saved plans count: ' . count($saved_plans));
        if (isset($saved_plans[$plan_id])) {
            error_log('FlexPress Pricing: Plan found in saved data: ' . print_r($saved_plans[$plan_id], true));
        } else {
            error_log('FlexPress Pricing: ERROR - Plan not found in saved data!');
        }

        wp_send_json_success(array(
            'plan_id' => $plan_id,
            'message' => __('Pricing plan saved successfully.', 'flexpress'),
            'debug' => array(
                'plan_data' => $plan_data,
                'update_result' => $update_result,
                'plan_id' => $plan_id
            )
        ));
    }

    /**
     * AJAX handler to delete pricing plan
     */
    public function delete_pricing_plan()
    {
        check_ajax_referer('flexpress_pricing_nonce', 'nonce');

        if (!flexpress_current_user_is_founder()) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $plan_id = sanitize_text_field($_POST['plan_id']);
        $pricing_plans = get_option('flexpress_pricing_plans', array());

        if (isset($pricing_plans[$plan_id])) {
            unset($pricing_plans[$plan_id]);
            update_option('flexpress_pricing_plans', $pricing_plans);
            wp_send_json_success(__('Pricing plan deleted successfully.', 'flexpress'));
        } else {
            wp_send_json_error(__('Pricing plan not found.', 'flexpress'));
        }
    }

    /**
     * AJAX handler to toggle plan status
     */
    public function toggle_plan_status()
    {
        check_ajax_referer('flexpress_pricing_nonce', 'nonce');

        if (!flexpress_current_user_is_founder()) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $plan_id = sanitize_text_field($_POST['plan_id'] ?? '');
        $pricing_plans = get_option('flexpress_pricing_plans', array());

        if (isset($pricing_plans[$plan_id])) {
            $pricing_plans[$plan_id]['active'] = empty($pricing_plans[$plan_id]['active']) ? 1 : 0;
            update_option('flexpress_pricing_plans', $pricing_plans);

            $status = $pricing_plans[$plan_id]['active'] ? 'activated' : 'deactivated';
            wp_send_json_success(sprintf(__('Pricing plan %s successfully.', 'flexpress'), $status));
        } else {
            wp_send_json_error(__('Pricing plan not found.', 'flexpress'));
        }
    }

    /**
     * AJAX handler to get individual pricing plan data for editing
     */
    public function get_pricing_plan()
    {
        check_ajax_referer('flexpress_pricing_nonce', 'nonce');

        if (!flexpress_current_user_is_founder()) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $plan_id = sanitize_text_field($_POST['plan_id'] ?? '');
        $pricing_plans = get_option('flexpress_pricing_plans', array());

        if (isset($pricing_plans[$plan_id])) {
            wp_send_json_success($pricing_plans[$plan_id]);
        } else {
            wp_send_json_error(__('Pricing plan not found.', 'flexpress'));
        }
    }

    /**
     * AJAX handler to test Flowguard connection
     */
    public function test_flowguard_connection()
    {
        check_ajax_referer('flexpress_pricing_nonce', 'nonce');

        if (!flexpress_current_user_is_founder()) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        // Check if Flowguard API is available
        if (!function_exists('flexpress_get_flowguard_api')) {
            wp_send_json_error('Flowguard API not available. Please ensure Flowguard integration is properly installed.');
            return;
        }

        $api = flexpress_get_flowguard_api();
        if (!$api) {
            wp_send_json_error('Flowguard API not configured. Please check your Flowguard settings.');
            return;
        }

        // Test both subscription and purchase endpoints
        $test_subscription_data = [
            'priceAmount' => '2.95',
            'priceCurrency' => 'USD',
            'successUrl' => home_url('/payment-success'),
            'declineUrl' => home_url('/payment-declined'),
            'postbackUrl' => home_url('/wp-admin/admin-ajax.php?action=flowguard_webhook'),
            'email' => 'test@example.com',
            'subscriptionType' => 'one-time',
            'period' => 'P2D',
            'referenceId' => 'test_subscription_' . time()
        ];

        $test_purchase_data = [
            'priceAmount' => '2.95',
            'priceCurrency' => 'USD',
            'successUrl' => home_url('/payment-success'),
            'declineUrl' => home_url('/payment-declined'),
            'postbackUrl' => home_url('/wp-admin/admin-ajax.php?action=flowguard_webhook'),
            'email' => 'test@example.com',
            'referenceId' => 'test_purchase_' . time()
        ];

        // Test subscription endpoint
        $subscription_result = $api->start_subscription($test_subscription_data);
        $purchase_result = $api->start_purchase($test_purchase_data);

        $results = [];
        if ($subscription_result['success']) {
            $results[] = 'Subscription API: ✓ Working (Session: ' . substr($subscription_result['session_id'], 0, 8) . '...)';
        } else {
            $results[] = 'Subscription API: ✗ Failed - ' . $subscription_result['error'];
        }

        if ($purchase_result['success']) {
            $results[] = 'Purchase API: ✓ Working (Session: ' . substr($purchase_result['session_id'], 0, 8) . '...)';
        } else {
            $results[] = 'Purchase API: ✗ Failed - ' . $purchase_result['error'];
        }

        if ($subscription_result['success'] && $purchase_result['success']) {
            wp_send_json_success('Flowguard API connection successful!<br>' . implode('<br>', $results));
        } else {
            wp_send_json_error('Flowguard API connection issues:<br>' . implode('<br>', $results));
        }
    }

    /**
     * AJAX handler to validate pricing plans
     */
    public function validate_pricing_plans()
    {
        check_ajax_referer('flexpress_pricing_nonce', 'nonce');

        if (!flexpress_current_user_is_founder()) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $pricing_plans = get_option('flexpress_pricing_plans', array());
        $validation_results = array();
        $errors = array();
        $warnings = array();

        foreach ($pricing_plans as $plan_id => $plan) {
            $plan_errors = array();
            $plan_warnings = array();

            // Validate required fields
            if (empty($plan['name'])) {
                $plan_errors[] = 'Plan name is required';
            }

            if (empty($plan['price']) || $plan['price'] <= 0) {
                $plan_errors[] = 'Valid price is required';
            }

            if (empty($plan['currency'])) {
                $plan_errors[] = 'Currency is required';
            }

            // Use comprehensive Flowguard validation
            $flowguard_validation = flexpress_validate_plan_for_flowguard($plan);
            $plan_errors = array_merge($plan_errors, $flowguard_validation['errors']);
            $plan_warnings = array_merge($plan_warnings, $flowguard_validation['warnings']);

            // Validate trial settings
            if (!empty($plan['trial_enabled'])) {
                if (empty($plan['trial_price']) || $plan['trial_price'] < 0) {
                    $plan_errors[] = 'Valid trial price required when trial is enabled';
                }
                if (empty($plan['trial_duration']) || $plan['trial_duration'] < 1) {
                    $plan_errors[] = 'Valid trial duration required when trial is enabled';
                }
            }

            $validation_results[$plan_id] = array(
                'name' => $plan['name'],
                'errors' => $plan_errors,
                'warnings' => $plan_warnings
            );

            if (!empty($plan_errors)) {
                $errors = array_merge($errors, $plan_errors);
            }
            if (!empty($plan_warnings)) {
                $warnings = array_merge($warnings, $plan_warnings);
            }
        }

        $response = array(
            'validation_results' => $validation_results,
            'total_errors' => count($errors),
            'total_warnings' => count($warnings),
            'summary' => array(
                'total_plans' => count($pricing_plans),
                'plans_with_errors' => count(array_filter($validation_results, function ($result) {
                    return !empty($result['errors']);
                })),
                'plans_with_warnings' => count(array_filter($validation_results, function ($result) {
                    return !empty($result['warnings']);
                }))
            )
        );

        if (empty($errors)) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error($response);
        }
    }
}

// Initialize the pricing settings class
new FlexPress_Pricing_Settings();
