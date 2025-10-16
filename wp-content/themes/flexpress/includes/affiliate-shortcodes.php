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
    
    // Enqueue validation styles
    wp_enqueue_style('flexpress-affiliate-validation', get_template_directory_uri() . '/assets/css/affiliate-validation.css', array(), '1.0.0');
    
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
            <form id="affiliate-application-form" class="affiliate-form" method="post" novalidate>
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
                
                <div class="form-group">
                    <label for="desired_affiliate_id"><?php esc_html_e('Desired Affiliate ID', 'flexpress'); ?> <span class="required">*</span></label>
                    <input type="text" id="desired_affiliate_id" name="desired_affiliate_id" required pattern="[a-zA-Z0-9]{3,20}" placeholder="your-affiliate-id">
                    <small class="form-help"><?php esc_html_e('Choose a unique ID (3-20 characters, letters and numbers only). This will be used in your referral links.', 'flexpress'); ?></small>
                    <div id="affiliate-id-status" class="validation-status"></div>
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
                    
                    <div class="form-group payout-details-container">
                        <label><?php esc_html_e('Payout Details', 'flexpress'); ?></label>
                        
                        <!-- PayPal Fields -->
                        <div class="payout-fields paypal-fields" style="display: none;">
                            <input type="email" name="paypal_email" placeholder="<?php esc_attr_e('PayPal Email Address', 'flexpress'); ?>" class="payout-detail-field">
                        </div>
                        
                        <!-- Cryptocurrency Fields -->
                        <div class="payout-fields crypto-fields" style="display: none;">
                            <select name="crypto_type" class="payout-detail-field">
                                <option value=""><?php esc_html_e('Select Cryptocurrency', 'flexpress'); ?></option>
                                <option value="bitcoin">Bitcoin (BTC)</option>
                                <option value="ethereum">Ethereum (ETH)</option>
                                <option value="litecoin">Litecoin (LTC)</option>
                                <option value="other">Other</option>
                            </select>
                            <input type="text" name="crypto_address" placeholder="<?php esc_attr_e('Wallet Address', 'flexpress'); ?>" class="payout-detail-field">
                            <input type="text" name="crypto_other" placeholder="<?php esc_attr_e('Specify other cryptocurrency', 'flexpress'); ?>" class="payout-detail-field" style="display: none;">
                        </div>
                        
                        <!-- Australian Bank Transfer Fields -->
                        <div class="payout-fields aus_bank_transfer-fields" style="display: none;">
                            <input type="text" name="aus_bank_name" placeholder="<?php esc_attr_e('Bank Name', 'flexpress'); ?>" class="payout-detail-field">
                            <input type="text" name="aus_bsb" placeholder="<?php esc_attr_e('BSB Number (6 digits)', 'flexpress'); ?>" pattern="[0-9]{6}" class="payout-detail-field">
                            <input type="text" name="aus_account_number" placeholder="<?php esc_attr_e('Account Number', 'flexpress'); ?>" class="payout-detail-field">
                            <input type="text" name="aus_account_holder" placeholder="<?php esc_attr_e('Account Holder Name', 'flexpress'); ?>" class="payout-detail-field">
                        </div>
                        
                        <!-- Yoursafe Fields -->
                        <div class="payout-fields yoursafe-fields" style="display: none;">
                            <input type="text" name="yoursafe_iban" placeholder="<?php esc_attr_e('Yoursafe IBAN', 'flexpress'); ?>" class="payout-detail-field">
                        </div>
                        
                        <!-- ACH Fields -->
                        <div class="payout-fields ach-fields" style="display: none;">
                            <input type="text" name="ach_account_number" placeholder="<?php esc_attr_e('Account Number', 'flexpress'); ?>" class="payout-detail-field">
                            <input type="text" name="ach_aba" placeholder="<?php esc_attr_e('ABA Routing Number (9 digits)', 'flexpress'); ?>" pattern="[0-9]{9}" class="payout-detail-field">
                            <input type="text" name="ach_account_holder" placeholder="<?php esc_attr_e('Account Holder Name', 'flexpress'); ?>" class="payout-detail-field">
                            <input type="text" name="ach_bank_name" placeholder="<?php esc_attr_e('Bank Name', 'flexpress'); ?>" class="payout-detail-field">
                        </div>
                        
                        <!-- Swift Fields -->
                        <div class="payout-fields swift-fields" style="display: none;">
                            <input type="text" name="swift_bank_name" placeholder="<?php esc_attr_e('Bank Name', 'flexpress'); ?>" class="payout-detail-field">
                            <input type="text" name="swift_code" placeholder="<?php esc_attr_e('SWIFT/BIC Code', 'flexpress'); ?>" class="payout-detail-field">
                            <input type="text" name="swift_iban_account" placeholder="<?php esc_attr_e('IBAN or Account Number', 'flexpress'); ?>" class="payout-detail-field">
                            <input type="text" name="swift_account_holder" placeholder="<?php esc_attr_e('Account Holder Name', 'flexpress'); ?>" class="payout-detail-field">
                            <textarea name="swift_bank_address" placeholder="<?php esc_attr_e('Bank Address', 'flexpress'); ?>" rows="2" class="payout-detail-field"></textarea>
                            <textarea name="swift_beneficiary_address" placeholder="<?php esc_attr_e('Beneficiary Address', 'flexpress'); ?>" rows="2" class="payout-detail-field"></textarea>
                            <input type="text" name="swift_intermediary_swift" placeholder="<?php esc_attr_e('Secondary/Intermediary SWIFT Code (if required)', 'flexpress'); ?>" class="payout-detail-field">
                            <input type="text" name="swift_intermediary_iban" placeholder="<?php esc_attr_e('Intermediary IBAN or Account (if required)', 'flexpress'); ?>" class="payout-detail-field">
                        </div>
                        
                        <!-- Hidden field to store consolidated payout details -->
                        <input type="hidden" id="payout_details" name="payout_details" disabled>
                        
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
        // Affiliate ID validation
        $('#desired_affiliate_id').on('blur', function() {
            var affiliateId = $(this).val();
            var statusDiv = $('#affiliate-id-status');
            
            if (affiliateId.length < 3 || affiliateId.length > 20) {
                statusDiv.html('<span class="error"><?php esc_html_e('ID must be 3-20 characters', 'flexpress'); ?></span>');
                return;
            }
            
            if (!/^[a-zA-Z0-9]+$/.test(affiliateId)) {
                statusDiv.html('<span class="error"><?php esc_html_e('ID can only contain letters and numbers', 'flexpress'); ?></span>');
                return;
            }
            
            // Check availability via REST API
            $.ajax({
                url: '<?php echo esc_url_raw(rest_url('flexpress/v1/admin/affiliates/check-id')); ?>',
                type: 'POST',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                data: {
                    affiliate_id: affiliateId
                },
                success: function(response) {
                    if (response.available) {
                        statusDiv.html('<span class="success"><?php esc_html_e('✓ Available', 'flexpress'); ?></span>');
                    } else {
                        statusDiv.html('<span class="error"><?php esc_html_e('✗ Already taken', 'flexpress'); ?></span>');
                    }
                },
                error: function() {
                    statusDiv.html('<span class="error"><?php esc_html_e('Unable to check availability', 'flexpress'); ?></span>');
                }
            });
        });
        
        $('#affiliate-application-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var messages = form.find('.form-messages');
            var submitBtn = form.find('button[type="submit"]');
            
            // Disable submit button
            submitBtn.prop('disabled', true).text('<?php esc_html_e('Submitting...', 'flexpress'); ?>');
            
            // Clear previous messages
            messages.empty();
            
            // Build payout details object based on selected method
            var payoutDetails = {};
            var selectedMethod = $('#payout_method').val();

            switch(selectedMethod) {
                case 'paypal':
                    payoutDetails = { paypal_email: $('input[name="paypal_email"]').val() };
                    break;
                case 'crypto':
                    payoutDetails = {
                        crypto_type: $('select[name="crypto_type"]').val(),
                        crypto_address: $('input[name="crypto_address"]').val(),
                        crypto_other: $('input[name="crypto_other"]').val()
                    };
                    break;
                case 'aus_bank_transfer':
                    payoutDetails = {
                        aus_bank_name: $('input[name="aus_bank_name"]').val(),
                        aus_bsb: $('input[name="aus_bsb"]').val(),
                        aus_account_number: $('input[name="aus_account_number"]').val(),
                        aus_account_holder: $('input[name="aus_account_holder"]').val()
                    };
                    break;
                case 'yoursafe':
                    payoutDetails = { yoursafe_iban: $('input[name="yoursafe_iban"]').val() };
                    break;
                case 'ach':
                    payoutDetails = {
                        ach_account_number: $('input[name="ach_account_number"]').val(),
                        ach_aba: $('input[name="ach_aba"]').val(),
                        ach_account_holder: $('input[name="ach_account_holder"]').val(),
                        ach_bank_name: $('input[name="ach_bank_name"]').val()
                    };
                    break;
                case 'swift':
                    payoutDetails = {
                        swift_bank_name: $('input[name="swift_bank_name"]').val(),
                        swift_code: $('input[name="swift_code"]').val(),
                        swift_iban_account: $('input[name="swift_iban_account"]').val(),
                        swift_account_holder: $('input[name="swift_account_holder"]').val(),
                        swift_bank_address: $('textarea[name="swift_bank_address"]').val(),
                        swift_beneficiary_address: $('textarea[name="swift_beneficiary_address"]').val(),
                        swift_intermediary_swift: $('input[name="swift_intermediary_swift"]').val(),
                        swift_intermediary_iban: $('input[name="swift_intermediary_iban"]').val()
                    };
                    break;
            }

            // Set payout details and temporarily enable for serialization
            $('#payout_details').val(JSON.stringify(payoutDetails)).prop('disabled', false);

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
                    // Re-disable hidden field to avoid HTML5 validation issues
                    $('#payout_details').prop('disabled', true);
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
