<?php
/**
 * FlexPress Promo Codes Shortcodes
 * Provides shortcodes for displaying promo codes on frontend
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Promo Codes Shortcodes Class
 */
class FlexPress_Promo_Codes_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('flexpress_promo_codes', array($this, 'display_promo_codes'));
        add_shortcode('flexpress_promo_form', array($this, 'display_promo_form'));
        add_shortcode('flexpress_promo_banner', array($this, 'display_promo_banner'));
    }
    
    /**
     * Display active promo codes
     * Usage: [flexpress_promo_codes limit="5" show_expiry="true"]
     */
    public function display_promo_codes($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'show_expiry' => 'true',
            'show_usage' => 'false',
            'style' => 'cards' // cards, list, table
        ), $atts);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'flexpress_promo_codes';
        
        $limit = intval($atts['limit']);
        $show_expiry = $atts['show_expiry'] === 'true';
        $show_usage = $atts['show_usage'] === 'true';
        $style = $atts['style'];
        
        $promo_codes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE status = 'active' AND (valid_until IS NULL OR valid_until > NOW()) ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
        
        if (empty($promo_codes)) {
            return '<div class="flexpress-no-promos">' . esc_html__('No active promo codes available.', 'flexpress') . '</div>';
        }
        
        ob_start();
        
        if ($style === 'cards') {
            $this->render_promo_cards($promo_codes, $show_expiry, $show_usage);
        } elseif ($style === 'list') {
            $this->render_promo_list($promo_codes, $show_expiry, $show_usage);
        } elseif ($style === 'table') {
            $this->render_promo_table($promo_codes, $show_expiry, $show_usage);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Display promo code application form
     * Usage: [flexpress_promo_form plan_id="monthly" amount="29.99"]
     */
    public function display_promo_form($atts) {
        $atts = shortcode_atts(array(
            'plan_id' => '',
            'amount' => '0',
            'button_text' => 'Apply Promo Code',
            'placeholder' => 'Enter promo code'
        ), $atts);
        
        ob_start();
        ?>
        <div class="flexpress-promo-form">
            <form id="flexpress-promo-form" class="promo-form">
                <input type="hidden" name="plan_id" value="<?php echo esc_attr($atts['plan_id']); ?>">
                <input type="hidden" name="amount" value="<?php echo esc_attr($atts['amount']); ?>">
                
                <div class="promo-form-group">
                    <label for="promo-code-input"><?php esc_html_e('Promo Code', 'flexpress'); ?></label>
                    <div class="promo-input-group">
                        <input type="text" id="promo-code-input" name="promo_code" 
                               placeholder="<?php echo esc_attr($atts['placeholder']); ?>" required>
                        <button type="submit" class="promo-submit-btn">
                            <?php echo esc_html($atts['button_text']); ?>
                        </button>
                    </div>
                </div>
                
                <div id="promo-form-message" class="promo-message"></div>
            </form>
        </div>
        
        <style>
        .flexpress-promo-form {
            max-width: 400px;
            margin: 20px 0;
        }
        .promo-form-group {
            margin-bottom: 15px;
        }
        .promo-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .promo-input-group {
            display: flex;
            gap: 10px;
        }
        .promo-input-group input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .promo-submit-btn {
            padding: 10px 20px;
            background: #007cba;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .promo-submit-btn:hover {
            background: #005a87;
        }
        .promo-message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            display: none;
        }
        .promo-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .promo-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#flexpress-promo-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                formData += '&action=apply_promo_code';
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        var messageDiv = $('#promo-form-message');
                        messageDiv.removeClass('success error');
                        
                        if (response.success) {
                            messageDiv.addClass('success');
                            messageDiv.html('<strong>Success!</strong> ' + response.data.message + 
                                          '<br>Discount: $' + response.data.discount_amount.toFixed(2) +
                                          '<br>Final Amount: $' + response.data.final_amount.toFixed(2));
                        } else {
                            messageDiv.addClass('error');
                            messageDiv.html('<strong>Error:</strong> ' + response.data.message);
                        }
                        
                        messageDiv.show();
                        
                        setTimeout(function() {
                            messageDiv.fadeOut();
                        }, 10000);
                    },
                    error: function() {
                        $('#promo-form-message').addClass('error')
                            .html('<strong>Error:</strong> An error occurred. Please try again.')
                            .show();
                    }
                });
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Display promo banner
     * Usage: [flexpress_promo_banner code="WELCOME20" title="Welcome Offer" description="Get 20% off your first month"]
     */
    public function display_promo_banner($atts) {
        $atts = shortcode_atts(array(
            'code' => '',
            'title' => '',
            'description' => '',
            'background_color' => '#007cba',
            'text_color' => '#ffffff',
            'show_code' => 'true',
            'expiry_date' => ''
        ), $atts);
        
        if (empty($atts['code'])) {
            return '';
        }
        
        // Check if promo code is valid
        $promo_codes = new FlexPress_Promo_Codes();
        $validation = $promo_codes->validate_promo_code_logic($atts['code'], 0, '', 0);
        
        if (!$validation['valid']) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="flexpress-promo-banner" 
             style="background-color: <?php echo esc_attr($atts['background_color']); ?>; 
                    color: <?php echo esc_attr($atts['text_color']); ?>;">
            <div class="promo-banner-content">
                <?php if (!empty($atts['title'])): ?>
                    <h3 class="promo-title"><?php echo esc_html($atts['title']); ?></h3>
                <?php endif; ?>
                
                <?php if (!empty($atts['description'])): ?>
                    <p class="promo-description"><?php echo esc_html($atts['description']); ?></p>
                <?php endif; ?>
                
                <?php if ($atts['show_code'] === 'true'): ?>
                    <div class="promo-code-display">
                        <strong><?php esc_html_e('Code:', 'flexpress'); ?></strong> 
                        <span class="promo-code"><?php echo esc_html($atts['code']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($atts['expiry_date'])): ?>
                    <div class="promo-expiry">
                        <small><?php esc_html_e('Expires:', 'flexpress'); ?> <?php echo esc_html($atts['expiry_date']); ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .flexpress-promo-banner {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .promo-banner-content h3 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .promo-banner-content p {
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        .promo-code-display {
            margin: 15px 0;
            font-size: 18px;
        }
        .promo-code {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 4px;
            font-family: monospace;
        }
        .promo-expiry {
            margin-top: 10px;
            opacity: 0.8;
        }
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render promo codes as cards
     */
    private function render_promo_cards($promo_codes, $show_expiry, $show_usage) {
        ?>
        <div class="flexpress-promo-cards">
            <?php foreach ($promo_codes as $promo): ?>
                <div class="promo-card">
                    <div class="promo-header">
                        <h3 class="promo-name"><?php echo esc_html($promo->name); ?></h3>
                        <div class="promo-code"><?php echo esc_html($promo->code); ?></div>
                    </div>
                    
                    <?php if (!empty($promo->description)): ?>
                        <div class="promo-description">
                            <?php echo esc_html($promo->description); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="promo-discount">
                        <?php echo esc_html($this->format_discount($promo)); ?>
                    </div>
                    
                    <?php if ($show_expiry && $promo->valid_until): ?>
                        <div class="promo-expiry">
                            <small><?php esc_html_e('Expires:', 'flexpress'); ?> <?php echo date('M j, Y', strtotime($promo->valid_until)); ?></small>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($show_usage && $promo->usage_limit > 0): ?>
                        <div class="promo-usage">
                            <small><?php esc_html_e('Usage:', 'flexpress'); ?> <?php echo $promo->usage_count; ?> / <?php echo $promo->usage_limit; ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <style>
        .flexpress-promo-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .promo-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .promo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .promo-name {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .promo-code {
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
        }
        .promo-description {
            margin-bottom: 15px;
            color: #666;
        }
        .promo-discount {
            font-size: 20px;
            font-weight: bold;
            color: #007cba;
            margin-bottom: 10px;
        }
        .promo-expiry,
        .promo-usage {
            color: #999;
            font-size: 14px;
        }
        </style>
        <?php
    }
    
    /**
     * Render promo codes as list
     */
    private function render_promo_list($promo_codes, $show_expiry, $show_usage) {
        ?>
        <div class="flexpress-promo-list">
            <?php foreach ($promo_codes as $promo): ?>
                <div class="promo-item">
                    <div class="promo-info">
                        <div class="promo-name"><?php echo esc_html($promo->name); ?></div>
                        <div class="promo-code"><?php echo esc_html($promo->code); ?></div>
                        <div class="promo-discount"><?php echo esc_html($this->format_discount($promo)); ?></div>
                    </div>
                    
                    <?php if (!empty($promo->description)): ?>
                        <div class="promo-description"><?php echo esc_html($promo->description); ?></div>
                    <?php endif; ?>
                    
                    <div class="promo-meta">
                        <?php if ($show_expiry && $promo->valid_until): ?>
                            <span class="promo-expiry"><?php esc_html_e('Expires:', 'flexpress'); ?> <?php echo date('M j, Y', strtotime($promo->valid_until)); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($show_usage && $promo->usage_limit > 0): ?>
                            <span class="promo-usage"><?php esc_html_e('Usage:', 'flexpress'); ?> <?php echo $promo->usage_count; ?> / <?php echo $promo->usage_limit; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <style>
        .flexpress-promo-list {
            margin: 20px 0;
        }
        .promo-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .promo-item:last-child {
            border-bottom: none;
        }
        .promo-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .promo-name {
            font-weight: bold;
            font-size: 16px;
        }
        .promo-code {
            background: #f0f0f0;
            padding: 3px 8px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 14px;
        }
        .promo-discount {
            color: #007cba;
            font-weight: bold;
        }
        .promo-description {
            color: #666;
            margin-bottom: 10px;
        }
        .promo-meta {
            font-size: 14px;
            color: #999;
        }
        .promo-meta span {
            margin-right: 15px;
        }
        </style>
        <?php
    }
    
    /**
     * Render promo codes as table
     */
    private function render_promo_table($promo_codes, $show_expiry, $show_usage) {
        ?>
        <div class="flexpress-promo-table">
            <table class="promo-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Code', 'flexpress'); ?></th>
                        <th><?php esc_html_e('Name', 'flexpress'); ?></th>
                        <th><?php esc_html_e('Discount', 'flexpress'); ?></th>
                        <?php if ($show_expiry): ?>
                            <th><?php esc_html_e('Expires', 'flexpress'); ?></th>
                        <?php endif; ?>
                        <?php if ($show_usage): ?>
                            <th><?php esc_html_e('Usage', 'flexpress'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promo_codes as $promo): ?>
                        <tr>
                            <td class="promo-code-cell">
                                <span class="promo-code"><?php echo esc_html($promo->code); ?></span>
                            </td>
                            <td class="promo-name-cell">
                                <div class="promo-name"><?php echo esc_html($promo->name); ?></div>
                                <?php if (!empty($promo->description)): ?>
                                    <div class="promo-description"><?php echo esc_html($promo->description); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="promo-discount-cell">
                                <?php echo esc_html($this->format_discount($promo)); ?>
                            </td>
                            <?php if ($show_expiry): ?>
                                <td class="promo-expiry-cell">
                                    <?php echo $promo->valid_until ? date('M j, Y', strtotime($promo->valid_until)) : 'Never'; ?>
                                </td>
                            <?php endif; ?>
                            <?php if ($show_usage): ?>
                                <td class="promo-usage-cell">
                                    <?php echo $promo->usage_limit > 0 ? $promo->usage_count . ' / ' . $promo->usage_limit : $promo->usage_count; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .flexpress-promo-table {
            margin: 20px 0;
            overflow-x: auto;
        }
        .promo-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        .promo-table th,
        .promo-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .promo-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .promo-code {
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 3px;
            font-family: monospace;
            font-weight: bold;
        }
        .promo-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .promo-description {
            color: #666;
            font-size: 14px;
        }
        .promo-discount-cell {
            color: #007cba;
            font-weight: bold;
        }
        </style>
        <?php
    }
    
    /**
     * Format discount for display
     */
    private function format_discount($promo) {
        switch ($promo->discount_type) {
            case 'percentage':
                return $promo->discount_value . '% off';
            case 'fixed':
                return '$' . number_format($promo->discount_value, 2) . ' off';
            case 'free_trial':
                return $promo->discount_value . ' days free';
            default:
                return '';
        }
    }
}

// Initialize the promo codes shortcodes
new FlexPress_Promo_Codes_Shortcodes();
