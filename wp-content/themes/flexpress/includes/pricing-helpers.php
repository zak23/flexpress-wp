<?php
/**
 * Pricing Helper Functions
 *
 * @package FlexPress
 */

// Make sure we're in WordPress
if (!defined('ABSPATH')) {
    exit;
}

// Import WordPress translation functions
if (!function_exists('__')) {
    require_once(ABSPATH . WPINC . '/l10n.php');
}

/**
 * Get all active pricing plans with promo code filtering
 *
 * @param bool $active_only Whether to return only active plans
 * @param string $promo_code Optional promo code to unlock hidden plans
 * @return array Array of pricing plans
 */
function flexpress_get_pricing_plans($active_only = true, $promo_code = '') {
    $all_plans = get_option('flexpress_pricing_plans', array());
    
    if (!$active_only) {
        return $all_plans;
    }
    
    $active_plans = array();
    foreach ($all_plans as $plan_id => $plan) {
        if (!empty($plan['active'])) {
            // Check if plan is promo-only and requires a code
            if (!empty($plan['promo_only'])) {
                if ($promo_code && flexpress_validate_promo_code_for_plan($promo_code, $plan_id)) {
                    $active_plans[$plan_id] = $plan;
                }
                // Skip promo-only plans without valid code
            } else {
                // Regular plans always visible
                $active_plans[$plan_id] = $plan;
            }
        }
    }
    
    // Sort by sort_order
    uasort($active_plans, function($a, $b) {
        return intval($a['sort_order'] ?? 0) - intval($b['sort_order'] ?? 0);
    });
    
    return $active_plans;
}

/**
 * Get a specific pricing plan
 *
 * @param string $plan_id The plan ID
 * @return array|false The plan data or false if not found
 */
function flexpress_get_pricing_plan($plan_id) {
    $plans = get_option('flexpress_pricing_plans', array());
    return isset($plans[$plan_id]) ? $plans[$plan_id] : false;
}

/**
 * Get the featured pricing plan
 *
 * @return array|false The featured plan or false if none found
 */
function flexpress_get_featured_pricing_plan() {
    $plans = flexpress_get_pricing_plans(true);
    
    foreach ($plans as $plan_id => $plan) {
        if (!empty($plan['featured'])) {
            return array('id' => $plan_id, 'data' => $plan);
        }
    }
    
    return false;
}

/**
 * Format plan duration for display
 *
 * @param array $plan The plan data.
 * @return string Formatted duration string.
 */
function flexpress_format_plan_duration($plan) {
    $duration = isset($plan['duration']) ? intval($plan['duration']) : 30;
    $duration_unit = isset($plan['duration_unit']) ? $plan['duration_unit'] : 'days';
    
    if ($duration === 1) {
        switch ($duration_unit) {
            case 'days':
                return __('day', 'flexpress');
            case 'months':
                return __('month', 'flexpress');
            case 'years':
                return __('year', 'flexpress');
            default:
                return __('day', 'flexpress');
        }
    } else {
        switch ($duration_unit) {
            case 'days':
                return sprintf(__('%d days', 'flexpress'), $duration);
            case 'months':
                return sprintf(__('%d months', 'flexpress'), $duration);
            case 'years':
                return sprintf(__('%d years', 'flexpress'), $duration);
            default:
                return sprintf(__('%d days', 'flexpress'), $duration);
        }
    }
}

/**
 * Format plan trial duration for display
 *
 * @param array $plan The plan data.
 * @return string Formatted trial duration string.
 */
function flexpress_format_plan_trial_duration($plan) {
    if (empty($plan['trial_enabled'])) {
        return '';
    }
    
    $duration = isset($plan['trial_duration']) ? intval($plan['trial_duration']) : 0;
    $duration_unit = isset($plan['trial_duration_unit']) ? $plan['trial_duration_unit'] : 'days';
    
    if ($duration === 0) {
        return '';
    }
    
    if ($duration === 1) {
        switch ($duration_unit) {
            case 'days':
                return __('1 day', 'flexpress');
            case 'months':
                return __('1 month', 'flexpress');
            case 'years':
                return __('1 year', 'flexpress');
            default:
                return __('1 day', 'flexpress');
        }
    } else {
        switch ($duration_unit) {
            case 'days':
                return sprintf(__('%d days', 'flexpress'), $duration);
            case 'months':
                return sprintf(__('%d months', 'flexpress'), $duration);
            case 'years':
                return sprintf(__('%d years', 'flexpress'), $duration);
            default:
                return sprintf(__('%d days', 'flexpress'), $duration);
        }
    }
}

/**
 * Get plan description with trial information
 *
 * @param array $plan The plan data
 * @return string Full plan description
 */
function flexpress_get_plan_full_description($plan) {
    if (isset($plan['plan_type']) && $plan['plan_type'] === 'one_time') {
        $price = number_format($plan['price'] ?? 0, 2);
        $duration = flexpress_format_plan_duration($plan);
        return sprintf(
            __('One-time payment of %s%s for %s access', 'flexpress'),
            $plan['currency'] ?? '$',
            $price,
            $duration
        );
    }
    
    $price = number_format($plan['price'] ?? 0, 2);
    $duration = flexpress_format_plan_duration($plan);
    
    return sprintf(
        __('%s%s every %s', 'flexpress'),
        $plan['currency'] ?? '$',
        $price,
        $duration
    );
}

/**
 * Check if pricing plans are configured
 *
 * @return bool True if plans exist, false otherwise
 */
function flexpress_has_pricing_plans() {
    $plans = flexpress_get_pricing_plans(true);
    return !empty($plans);
}

/**
 * Get default pricing plans (for initial setup)
 *
 * @return array Default pricing plans
 */
function flexpress_get_default_pricing_plans() {
    return array(
        'five_day_trial' => array(
            'name' => __('5 Day Trial', 'flexpress'),
            'description' => __('One-time payment for 5 days of access', 'flexpress'),
            'price' => 9.95,
            'currency' => '$',
            'duration' => 5,
            'duration_unit' => 'days',
            'plan_type' => 'one_time',
            'trial_enabled' => 0,
            'trial_price' => 0,
            'trial_duration' => 0,
            'trial_duration_unit' => 'days',
            'featured' => 0,
            'active' => 1,
            'sort_order' => 10,
            'verotel_site_id' => '133772',
            'verotel_product_id' => '5day995',
            'verotel_pricepoint_id' => '995'
        ),
        'thirty_day_access' => array(
            'name' => __('30 Day Access', 'flexpress'),
            'description' => __('One-time payment for 30 days of access', 'flexpress'),
            'price' => 19.95,
            'currency' => '$',
            'duration' => 30,
            'duration_unit' => 'days',
            'plan_type' => 'one_time',
            'trial_enabled' => 0,
            'trial_price' => 0,
            'trial_duration' => 0,
            'trial_duration_unit' => 'days',
            'featured' => 0,
            'active' => 1,
            'sort_order' => 15,
            'verotel_site_id' => '133772',
            'verotel_product_id' => '30day1995',
            'verotel_pricepoint_id' => '1995'
        ),
        'lifetime_access' => array(
            'name' => __('Lifetime Access', 'flexpress'),
            'description' => __('One-time payment for lifetime access', 'flexpress'),
            'price' => 99.95,
            'currency' => '$',
            'duration' => 3650,
            'duration_unit' => 'days',
            'plan_type' => 'one_time',
            'trial_enabled' => 0,
            'trial_price' => 0,
            'trial_duration' => 0,
            'trial_duration_unit' => 'days',
            'featured' => 1,
            'active' => 1,
            'sort_order' => 25,
            'verotel_site_id' => '133772',
            'verotel_product_id' => 'lifetime9995',
            'verotel_pricepoint_id' => '9995'
        ),
        'monthly' => array(
            'name' => __('Monthly Access', 'flexpress'),
            'description' => __('Full access to all content, billed monthly', 'flexpress'),
            'price' => 9.95,
            'currency' => '$',
            'duration' => 30,
            'duration_unit' => 'days',
            'plan_type' => 'recurring',
            'trial_enabled' => 0,
            'trial_price' => 0,
            'trial_duration' => 0,
            'trial_duration_unit' => 'days',
            'featured' => 0,
            'active' => 1,
            'sort_order' => 20,
            'verotel_site_id' => '133772',
            'verotel_product_id' => 'monthly995',
            'verotel_pricepoint_id' => '995'
        ),
        'yearly' => array(
            'name' => __('Yearly Access', 'flexpress'),
            'description' => __('Full access to all content, billed yearly (Save 20%)', 'flexpress'),
            'price' => 95.95,
            'currency' => '$',
            'duration' => 365,
            'duration_unit' => 'days',
            'plan_type' => 'recurring',
            'trial_enabled' => 1,
            'trial_price' => 2.95,
            'trial_duration' => 7,
            'trial_duration_unit' => 'days',
            'featured' => 1,
            'active' => 1,
            'sort_order' => 30,
            'verotel_site_id' => '133772',
            'verotel_product_id' => 'yearly9595',
            'verotel_pricepoint_id' => '9595'
        ),
        'weekly_trial' => array(
            'name' => __('7 Day Trial', 'flexpress'),
            'description' => __('One-time payment for 7 days of access', 'flexpress'),
            'price' => 2.95,
            'currency' => '$',
            'duration' => 7,
            'duration_unit' => 'days',
            'plan_type' => 'one_time',
            'trial_enabled' => 0,
            'trial_price' => 0,
            'trial_duration' => 0,
            'trial_duration_unit' => 'days',
            'featured' => 0,
            'active' => 1,
            'sort_order' => 5,
            'verotel_site_id' => '133772',
            'verotel_product_id' => '7day295',
            'verotel_pricepoint_id' => '295'
        ),
        'three_month_access' => array(
            'name' => __('3 Month Access', 'flexpress'),
            'description' => __('One-time payment for 3 months of access', 'flexpress'),
            'price' => 49.95,
            'currency' => '$',
            'duration' => 90,
            'duration_unit' => 'days',
            'plan_type' => 'one_time',
            'trial_enabled' => 0,
            'trial_price' => 0,
            'trial_duration' => 0,
            'trial_duration_unit' => 'days',
            'featured' => 0,
            'active' => 1,
            'sort_order' => 18,
            'verotel_site_id' => '133772',
            'verotel_product_id' => '3month4995',
            'verotel_pricepoint_id' => '4995'
        )
    );
}

/**
 * Initialize default pricing plans if none exist
 */
function flexpress_maybe_create_default_pricing_plans() {
    if (!flexpress_has_pricing_plans()) {
        $default_plans = flexpress_get_default_pricing_plans();
        update_option('flexpress_pricing_plans', $default_plans);
    }
}

/**
 * Force create/update default pricing plans (for debugging)
 */
function flexpress_force_create_default_pricing_plans() {
    $default_plans = flexpress_get_default_pricing_plans();
    update_option('flexpress_pricing_plans', $default_plans);
    return $default_plans;
}

/**
 * Add test pricing plans for Flowguard testing
 * Call this function to add additional test plans
 */
function flexpress_add_test_pricing_plans() {
    $current_plans = get_option('flexpress_pricing_plans', array());
    
    // Add test plans if they don't exist
    $test_plans = array(
        'test_recurring_monthly' => array(
            'name' => __('Test Monthly Recurring', 'flexpress'),
            'description' => __('Test recurring monthly subscription', 'flexpress'),
            'price' => 2.95,
            'currency' => '$',
            'duration' => 30,
            'duration_unit' => 'days',
            'plan_type' => 'recurring',
            'trial_enabled' => 0,
            'trial_price' => 0,
            'trial_duration' => 0,
            'trial_duration_unit' => 'days',
            'featured' => 0,
            'active' => 1,
            'sort_order' => 100,
            'verotel_site_id' => '133772',
            'verotel_product_id' => 'test_monthly295',
            'verotel_pricepoint_id' => '295'
        ),
        'test_onetime_week' => array(
            'name' => __('Test 7 Day One-Time', 'flexpress'),
            'description' => __('Test one-time 7 day access', 'flexpress'),
            'price' => 2.95,
            'currency' => '$',
            'duration' => 7,
            'duration_unit' => 'days',
            'plan_type' => 'one_time',
            'trial_enabled' => 0,
            'trial_price' => 0,
            'trial_duration' => 0,
            'trial_duration_unit' => 'days',
            'featured' => 0,
            'active' => 1,
            'sort_order' => 101,
            'verotel_site_id' => '133772',
            'verotel_product_id' => 'test_7day295',
            'verotel_pricepoint_id' => '295'
        )
    );
    
    // Merge test plans with existing plans
    $updated_plans = array_merge($current_plans, $test_plans);
    update_option('flexpress_pricing_plans', $updated_plans);
    
    return $updated_plans;
}

/**
 * Render a pricing plan card
 *
 * @param string $plan_id The ID of the plan
 * @param array $plan The plan data
 * @param bool $is_featured Whether the plan is featured
 */
function flexpress_render_pricing_plan_card($plan_id, $plan, $is_featured = false) {
    // Set defaults to prevent undefined array key warnings
    $plan = array_merge(array(
        'name' => 'Premium Plan',
        'description' => 'Full access to all content',
        'price' => 9.95,
        'currency' => '$',
        'duration' => 30,
        'duration_unit' => 'days',
        'plan_type' => 'recurring',
        'trial_enabled' => 0,
        'trial_price' => 0,
        'trial_duration' => 0,
        'trial_duration_unit' => 'days',
        'featured' => 0,
        'active' => 1,
        'verotel_site_id' => '',
        'verotel_product_id' => '',
        'sort_order' => 0,
        'promo_only' => 0,
        'promo_codes' => ''
    ), $plan);

    $is_one_time = isset($plan['plan_type']) && $plan['plan_type'] === 'one_time';
    $trial_enabled = !empty($plan['trial_enabled']);
    $is_promo_only = !empty($plan['promo_only']);
    
    // Calculate display price (trial price if trial enabled and it's a trial, otherwise regular price)
    $display_price = $trial_enabled ? floatval($plan['trial_price']) : floatval($plan['price']);
    
    $featured_class = $is_featured ? ' plan-featured' : '';
    $promo_class = $is_promo_only ? ' promo-hidden' : '';
    $promo_data = $is_promo_only ? ' data-promo-only="true"' : '';
    ?>
    <div class="col-12 mb-3 plan-option<?php echo $promo_class; ?>"<?php echo $promo_data; ?>>
        <div class="plan-card<?php echo $featured_class; ?>" data-plan-id="<?php echo esc_attr($plan_id); ?>">
            <input type="radio" name="selected_plan" value="<?php echo esc_attr($plan_id); ?>" id="plan-<?php echo esc_attr($plan_id); ?>" class="plan-radio d-none" <?php echo $is_featured ? 'checked' : ''; ?>>
            <label for="plan-<?php echo esc_attr($plan_id); ?>" class="plan-label d-block w-100 h-100 p-0 m-0">
                <div class="plan-content p-4">
                    <?php if ($is_featured): ?>
                        <div class="plan-badge">
                            <span class="badge bg-accent text-dark fw-bold mb-2">
                                <i class="fas fa-star me-1"></i><?php esc_html_e('RECOMMENDED', 'flexpress'); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="plan-details">
                            <h4 class="plan-name h5 mb-2 text-white fw-bold">
                                <?php echo esc_html($plan['name']); ?>
                            </h4>
                            <p class="plan-description text-white-50 mb-0 small">
                                <?php echo esc_html($plan['description']); ?>
                            </p>
                            <?php if ($trial_enabled): ?>
                                <div class="trial-info mt-2">
                                    <small class="text-accent fw-bold">
                                        <i class="fas fa-gift me-1"></i>
                                        <?php printf(
                                            esc_html__('%s trial', 'flexpress'),
                                            flexpress_format_plan_trial_duration($plan)
                                        ); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="plan-pricing text-end">
                            <div class="plan-price h4 mb-0 text-white">
                                <?php echo esc_html($plan['currency']); ?><?php echo number_format($display_price, 2); ?>
                            </div>
                            <?php if (!$is_one_time): ?>
                                <small class="plan-duration text-white-50">
                                    <?php if ($trial_enabled): ?>
                                        then <?php echo esc_html($plan['currency']); ?><?php echo number_format($plan['price'], 2); ?>/
                                    <?php endif; ?>
                                    <?php echo flexpress_format_plan_duration($plan); ?>
                                </small>
                            <?php else: ?>
                                <small class="plan-duration text-white-50">
                                    <?php esc_html_e('One-Time Payment', 'flexpress'); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </label>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Validate promo code for a specific plan
 *
 * @param string $promo_code The promo code to validate
 * @param string $plan_id The plan ID to check against
 * @return bool True if code is valid for this plan
 */
function flexpress_validate_promo_code_for_plan($promo_code, $plan_id) {
    $plan = flexpress_get_pricing_plan($plan_id);
    if (!$plan || empty($plan['promo_codes'])) {
        return false;
    }
    
    $codes = array_map('trim', explode(',', strtolower($plan['promo_codes'])));
    return in_array(strtolower(trim($promo_code)), $codes);
}

/**
 * Get plans unlocked by promo code
 *
 * @param string $promo_code The promo code
 * @return array Array of plan IDs unlocked by this code
 */
function flexpress_get_plans_for_promo_code($promo_code) {
    $all_plans = get_option('flexpress_pricing_plans', array());
    $unlocked_plans = array();
    
    foreach ($all_plans as $plan_id => $plan) {
        if (!empty($plan['promo_only']) && flexpress_validate_promo_code_for_plan($promo_code, $plan_id)) {
            $unlocked_plans[] = $plan_id;
        }
    }
    
    return $unlocked_plans;
}

/**
 * Check if a promo code is valid
 *
 * @param string $promo_code The promo code to validate
 * @return array Result with success status and unlocked plans
 */
function flexpress_validate_promo_code($promo_code) {
    if (empty($promo_code)) {
        return array(
            'success' => false,
            'message' => 'Please enter a promo code',
            'unlocked_plans' => array()
        );
    }
    
    $unlocked_plans = flexpress_get_plans_for_promo_code($promo_code);
    
    if (empty($unlocked_plans)) {
        return array(
            'success' => false,
            'message' => 'Invalid promo code',
            'unlocked_plans' => array()
        );
    }
    
    return array(
        'success' => true,
        'message' => 'Promo code applied! Special offers unlocked.',
        'unlocked_plans' => $unlocked_plans
    );
}

/**
 * Track promo code usage
 *
 * @param string $promo_code The promo code used
 * @param int $user_id The user ID
 * @param string $plan_id The plan purchased
 * @param string $transaction_id The transaction ID
 * @param float $amount The amount paid (optional)
 */
function flexpress_track_promo_usage($promo_code, $user_id, $plan_id, $transaction_id, $amount = 0.00) {
    if (empty($promo_code)) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'flexpress_promo_usage';
    
    $wpdb->insert(
        $table_name,
        array(
            'promo_code' => strtolower(trim($promo_code)),
            'user_id' => $user_id,
            'plan_id' => $plan_id,
            'amount' => floatval($amount),
            'transaction_id' => $transaction_id,
            'used_at' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ),
        array('%s', '%d', '%s', '%f', '%s', '%s', '%s')
    );
}

/**
 * Get promo code usage statistics
 *
 * @param string $promo_code Optional specific promo code
 * @return array Usage statistics
 */
function flexpress_get_promo_usage_stats($promo_code = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'flexpress_promo_usage';
    
    if ($promo_code) {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE promo_code = %s ORDER BY used_at DESC",
            strtolower(trim($promo_code))
        ));
        
        return array(
            'total_uses' => count($results),
            'usage_details' => $results
        );
    } else {
        $results = $wpdb->get_results(
            "SELECT promo_code, COUNT(*) as usage_count, 
             MIN(used_at) as first_used, MAX(used_at) as last_used
             FROM {$table_name} 
             GROUP BY promo_code 
             ORDER BY usage_count DESC"
        );
        
        return $results;
    }
}

/**
 * Render a single pricing plan card HTML
 *
 * @param string $plan_id The ID of the plan
 * @param array $plan The plan data
 * @param bool $is_featured Whether the plan is featured
 * @return string The HTML for the plan card
 */
function flexpress_get_pricing_plan_card_html($plan_id, $plan, $is_featured = false) {
    ob_start();
    flexpress_render_pricing_plan_card($plan_id, $plan, $is_featured);
    return ob_get_clean();
}

/**
 * AJAX handler to get a rendered plan card.
 */
function flexpress_ajax_get_plan_card() {
    check_ajax_referer('flexpress_promo_code', 'nonce');

    $plan_id = sanitize_text_field($_POST['plan_id'] ?? '');

    if (empty($plan_id)) {
        wp_send_json_error(['message' => 'Plan ID is missing.']);
    }

    $plan = flexpress_get_pricing_plan($plan_id);

    if (!$plan) {
        wp_send_json_error(['message' => 'Plan not found.']);
    }

    // A promo plan can't be featured, so this is safe.
    $is_featured = !empty($plan['featured']); 
    $html = flexpress_get_pricing_plan_card_html($plan_id, $plan, $is_featured);

    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_nopriv_flexpress_get_plan_card', 'flexpress_ajax_get_plan_card');
add_action('wp_ajax_flexpress_get_plan_card', 'flexpress_ajax_get_plan_card'); 