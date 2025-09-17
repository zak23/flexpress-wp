<?php
/**
 * FlexPress Affiliate Shortcodes
 * 
 * Shortcodes for affiliate application form and dashboard display.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register affiliate shortcodes
 */
function flexpress_register_affiliate_shortcodes() {
    add_shortcode('affiliate_application_form', 'flexpress_affiliate_application_form_shortcode');
    add_shortcode('affiliate_dashboard', 'flexpress_affiliate_dashboard_shortcode');
    add_shortcode('affiliate_stats', 'flexpress_affiliate_stats_shortcode');
    add_shortcode('affiliate_referral_link', 'flexpress_affiliate_referral_link_shortcode');
}
add_action('init', 'flexpress_register_affiliate_shortcodes');

/**
 * Affiliate application form shortcode
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function flexpress_affiliate_application_form_shortcode($atts) {
    // Check if affiliate system is enabled
    if (!flexpress_is_affiliate_system_enabled()) {
        return '<p>' . esc_html__('Affiliate system is currently disabled.', 'flexpress') . '</p>';
    }
    
    $atts = shortcode_atts(array(
        'title' => __('Apply to Become an Affiliate', 'flexpress'),
        'show_title' => 'true',
        'redirect_url' => ''
    ), $atts);
    
    ob_start();
    ?>
    <div class="affiliate-application-form-container">
        <?php if ($atts['show_title'] === 'true'): ?>
            <h2 class="form-title"><?php echo esc_html($atts['title']); ?></h2>
        <?php endif; ?>
        
        <div class="affiliate-form-wrapper">
            <form id="affiliate-application-form" class="affiliate-form" method="post">
                <?php wp_nonce_field('flexpress_affiliate_nonce', 'nonce'); ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="affiliate_name"><?php esc_html_e('Full Name', 'flexpress'); ?> <span class="required">*</span></label>
                        <input type="text" id="affiliate_name" name="affiliate_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="affiliate_email"><?php esc_html_e('Email Address', 'flexpress'); ?> <span class="required">*</span></label>
                        <input type="email" id="affiliate_email" name="affiliate_email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="affiliate_website"><?php esc_html_e('Website/Social Media', 'flexpress'); ?></label>
                    <input type="url" id="affiliate_website" name="affiliate_website" placeholder="https://yourwebsite.com">
                    <small class="form-help"><?php esc_html_e('Your website, blog, or social media profiles where you plan to promote our content.', 'flexpress'); ?></small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="payout_method"><?php esc_html_e('Preferred Payout Method', 'flexpress'); ?> <span class="required">*</span></label>
                        <select id="payout_method" name="payout_method" required>
                            <option value=""><?php esc_html_e('Select payout method', 'flexpress'); ?></option>
                            <option value="paypal"><?php esc_html_e('PayPal (Free)', 'flexpress'); ?></option>
                            <option value="crypto"><?php esc_html_e('Cryptocurrency (Free)', 'flexpress'); ?></option>
                            <option value="aus_bank_transfer"><?php esc_html_e('Australian Bank Transfer (Free)', 'flexpress'); ?></option>
                            <option value="yoursafe"><?php esc_html_e('Yoursafe (Free)', 'flexpress'); ?></option>
                            <option value="ach"><?php esc_html_e('ACH - US Only ($10 USD Fee)', 'flexpress'); ?></option>
                            <option value="swift"><?php esc_html_e('Swift International ($30 USD Fee)', 'flexpress'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payout_details"><?php esc_html_e('Payout Details', 'flexpress'); ?></label>
                        <input type="text" id="payout_details" name="payout_details" placeholder="PayPal email, bank details, etc.">
                        <small class="form-help"><?php esc_html_e('Provide the details for your chosen payout method.', 'flexpress'); ?></small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tax_info"><?php esc_html_e('Tax Information (Optional)', 'flexpress'); ?></label>
                    <textarea id="tax_info" name="tax_info" rows="3" placeholder="Tax ID, business registration, etc."></textarea>
                    <small class="form-help"><?php esc_html_e('Required for tax reporting purposes in some jurisdictions.', 'flexpress'); ?></small>
                </div>
                
                <div class="form-group">
                    <label for="marketing_experience"><?php esc_html_e('Marketing Experience', 'flexpress'); ?></label>
                    <textarea id="marketing_experience" name="marketing_experience" rows="4" placeholder="Describe your marketing experience and strategies"></textarea>
                    <small class="form-help"><?php esc_html_e('Tell us about your marketing experience and how you plan to promote our content.', 'flexpress'); ?></small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms_accepted" required>
                        <span class="checkmark"></span>
                        <?php printf(
                            esc_html__('I agree to the %sAffiliate Terms and Conditions%s', 'flexpress'),
                            '<a href="' . esc_url(home_url('/affiliate-terms')) . '" target="_blank">',
                            '</a>'
                        ); ?>
                        <span class="required">*</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="privacy_accepted" required>
                        <span class="checkmark"></span>
                        <?php printf(
                            esc_html__('I agree to the %sPrivacy Policy%s', 'flexpress'),
                            '<a href="' . esc_url(home_url('/privacy')) . '" target="_blank">',
                            '</a>'
                        ); ?>
                        <span class="required">*</span>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-large">
                        <?php esc_html_e('Submit Application', 'flexpress'); ?>
                    </button>
                </div>
                
                <div class="form-messages"></div>
            </form>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#affiliate-application-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var messages = form.find('.form-messages');
            var submitBtn = form.find('button[type="submit"]');
            
            // Disable submit button
            submitBtn.prop('disabled', true).text('<?php esc_html_e('Submitting...', 'flexpress'); ?>');
            
            // Clear previous messages
            messages.empty();
            
            // Submit form via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: form.serialize() + '&action=affiliate_application',
                success: function(response) {
                    if (response.success) {
                        messages.html('<div class="success-message">' + response.data.message + '</div>');
                        form[0].reset();
                        
                        <?php if (!empty($atts['redirect_url'])): ?>
                        setTimeout(function() {
                            window.location.href = '<?php echo esc_js($atts['redirect_url']); ?>';
                        }, 2000);
                        <?php endif; ?>
                    } else {
                        messages.html('<div class="error-message">' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    messages.html('<div class="error-message"><?php esc_html_e('An error occurred. Please try again.', 'flexpress'); ?></div>');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).text('<?php esc_html_e('Submit Application', 'flexpress'); ?>');
                }
            });
        });
    });
    </script>
    <?php
    
    return ob_get_clean();
}

/**
 * Affiliate dashboard shortcode
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function flexpress_affiliate_dashboard_shortcode($atts) {
    // Check if affiliate system is enabled
    if (!flexpress_is_affiliate_system_enabled()) {
        return '<p>' . esc_html__('Affiliate system is currently disabled.', 'flexpress') . '</p>';
    }
    
    $atts = shortcode_atts(array(
        'title' => __('Affiliate Dashboard', 'flexpress'),
        'show_title' => 'true'
    ), $atts);
    
    ob_start();
    
    $dashboard = FlexPress_Affiliate_Dashboard::get_instance();
    $dashboard->render_dashboard();
    
    return ob_get_clean();
}

/**
 * Affiliate stats shortcode
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function flexpress_affiliate_stats_shortcode($atts) {
    // Check if affiliate system is enabled
    if (!flexpress_is_affiliate_system_enabled()) {
        return '<p>' . esc_html__('Affiliate system is currently disabled.', 'flexpress') . '</p>';
    }
    
    $atts = shortcode_atts(array(
        'affiliate_id' => '',
        'period' => '30d',
        'show_chart' => 'true'
    ), $atts);
    
    // If no affiliate_id specified, try to get current user's affiliate
    if (empty($atts['affiliate_id'])) {
        $user_id = get_current_user_id();
        if ($user_id) {
            global $wpdb;
            $affiliate = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d AND status = 'active'",
                $user_id
            ));
            $atts['affiliate_id'] = $affiliate ? $affiliate->id : '';
        }
    }
    
    if (empty($atts['affiliate_id'])) {
        return '<p>' . esc_html__('No affiliate data available.', 'flexpress') . '</p>';
    }
    
    $tracker = FlexPress_Affiliate_Tracker::get_instance();
    $stats = $tracker->get_affiliate_stats($atts['affiliate_id'], $atts['period']);
    
    ob_start();
    ?>
    <div class="affiliate-stats-widget">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-label"><?php esc_html_e('Clicks', 'flexpress'); ?></div>
                <div class="stat-value"><?php echo number_format($stats['clicks']); ?></div>
            </div>
            
            <div class="stat-item">
                <div class="stat-label"><?php esc_html_e('Conversions', 'flexpress'); ?></div>
                <div class="stat-value"><?php echo number_format($stats['conversions']); ?></div>
            </div>
            
            <div class="stat-item">
                <div class="stat-label"><?php esc_html_e('Conversion Rate', 'flexpress'); ?></div>
                <div class="stat-value"><?php echo esc_html($stats['conversion_rate']); ?>%</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-label"><?php esc_html_e('Revenue', 'flexpress'); ?></div>
                <div class="stat-value">$<?php echo number_format($stats['revenue'], 2); ?></div>
            </div>
            
            <div class="stat-item">
                <div class="stat-label"><?php esc_html_e('Commission', 'flexpress'); ?></div>
                <div class="stat-value">$<?php echo number_format($stats['commission'], 2); ?></div>
            </div>
        </div>
        
        <?php if ($atts['show_chart'] === 'true'): ?>
            <div class="stats-chart">
                <canvas id="affiliate-stats-chart-<?php echo esc_attr($atts['affiliate_id']); ?>"></canvas>
            </div>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                var ctx = document.getElementById('affiliate-stats-chart-<?php echo esc_attr($atts['affiliate_id']); ?>').getContext('2d');
                
                // Get timeline data
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_affiliate_stats',
                        affiliate_id: <?php echo intval($atts['affiliate_id']); ?>,
                        period: '<?php echo esc_js($atts['period']); ?>',
                        nonce: '<?php echo wp_create_nonce('flexpress_affiliate_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.timeline) {
                            var timeline = response.data.timeline;
                            var labels = timeline.map(function(item) { return item.date; });
                            var clicks = timeline.map(function(item) { return item.clicks; });
                            var conversions = timeline.map(function(item) { return item.conversions; });
                            
                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: labels,
                                    datasets: [{
                                        label: '<?php esc_html_e('Clicks', 'flexpress'); ?>',
                                        data: clicks,
                                        borderColor: '#007cba',
                                        backgroundColor: 'rgba(0, 124, 186, 0.1)',
                                        tension: 0.1
                                    }, {
                                        label: '<?php esc_html_e('Conversions', 'flexpress'); ?>',
                                        data: conversions,
                                        borderColor: '#00a32a',
                                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                                        tension: 0.1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    scales: {
                                        y: {
                                            beginAtZero: true
                                        }
                                    }
                                }
                            });
                        }
                    }
                });
            });
            </script>
        <?php endif; ?>
    </div>
    <?php
    
    return ob_get_clean();
}

/**
 * Affiliate referral link shortcode
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function flexpress_affiliate_referral_link_shortcode($atts) {
    // Check if affiliate system is enabled
    if (!flexpress_is_affiliate_system_enabled()) {
        return '<p>' . esc_html__('Affiliate system is currently disabled.', 'flexpress') . '</p>';
    }
    
    $atts = shortcode_atts(array(
        'affiliate_code' => '',
        'promo_code' => '',
        'text' => '',
        'class' => 'affiliate-link'
    ), $atts);
    
    // If no affiliate_code specified, try to get current user's affiliate
    if (empty($atts['affiliate_code'])) {
        $user_id = get_current_user_id();
        if ($user_id) {
            global $wpdb;
            $affiliate = $wpdb->get_row($wpdb->prepare(
                "SELECT affiliate_code FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d AND status = 'active'",
                $user_id
            ));
            $atts['affiliate_code'] = $affiliate ? $affiliate->affiliate_code : '';
        }
    }
    
    if (empty($atts['affiliate_code'])) {
        return '<p>' . esc_html__('No affiliate code available.', 'flexpress') . '</p>';
    }
    
    $referral_url = flexpress_create_affiliate_referral_url($atts['affiliate_code'], $atts['promo_code']);
    $link_text = !empty($atts['text']) ? $atts['text'] : $referral_url;
    
    return sprintf(
        '<a href="%s" class="%s" target="_blank" rel="nofollow">%s</a>',
        esc_url($referral_url),
        esc_attr($atts['class']),
        esc_html($link_text)
    );
}
