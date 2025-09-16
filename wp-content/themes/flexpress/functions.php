<?php
/**
 * FlexPress Theme Functions
 */

// Define theme constants
define('FLEXPRESS_VERSION', '1.0.0');
define('FLEXPRESS_PATH', get_template_directory());
define('FLEXPRESS_URL', get_template_directory_uri());

// Include required files
require_once FLEXPRESS_PATH . '/includes/post-types.php';
require_once FLEXPRESS_PATH . '/includes/bunnycdn.php';
require_once FLEXPRESS_PATH . '/includes/gallery-system.php';

// Flowguard Integration (replaces Verotel)
require_once FLEXPRESS_PATH . '/includes/class-flexpress-flowguard-api.php';
require_once FLEXPRESS_PATH . '/includes/flowguard-integration.php';
require_once FLEXPRESS_PATH . '/includes/flowguard-webhook-handler.php';
require_once FLEXPRESS_PATH . '/includes/flowguard-database.php';

// Legacy Verotel files (to be removed after migration)
require_once FLEXPRESS_PATH . '/includes/verotel-integration.php';
require_once FLEXPRESS_PATH . '/includes/class-flexpress-verotel.php';

// Debug postback functionality removed - file no longer exists

// Include Flowguard admin tools (admin only)
if (is_admin()) {
    require_once FLEXPRESS_PATH . '/includes/admin/class-flexpress-flowguard-settings.php';
    
    // Legacy Verotel admin tools (to be removed after migration)
    require_once FLEXPRESS_PATH . '/includes/admin/class-flexpress-verotel-orphaned-webhooks.php';
    require_once FLEXPRESS_PATH . '/includes/admin/class-flexpress-verotel-diagnostics.php';
}
require_once FLEXPRESS_PATH . '/includes/class-wp-bootstrap-navwalker.php';
require_once FLEXPRESS_PATH . '/includes/class-flexpress-registration.php';
require_once FLEXPRESS_PATH . '/includes/class-flexpress-activity-logger.php';
require_once FLEXPRESS_PATH . '/includes/pricing-helpers.php';
require_once FLEXPRESS_PATH . '/includes/affiliate-helpers.php';
require_once FLEXPRESS_PATH . '/includes/contact-helpers.php';

// Load ACF fields after init to prevent translation issues
function flexpress_load_acf_fields() {
    require_once FLEXPRESS_PATH . '/includes/acf-fields.php';
}
add_action('init', 'flexpress_load_acf_fields', 20);

// TEMPORARY: Reset and create menus - removed to fix memory exhaustion issue
// require_once FLEXPRESS_PATH . '/includes/reset-menus.php';

// DEBUG: Check admin access - REMOVED

// Include admin files immediately - they contain their own admin checks
require_once FLEXPRESS_PATH . '/includes/admin/class-flexpress-settings.php';
require_once FLEXPRESS_PATH . '/includes/admin/class-flexpress-general-settings.php';
require_once FLEXPRESS_PATH . '/includes/admin/class-flexpress-video-settings.php';
require_once FLEXPRESS_PATH . '/includes/admin/class-flexpress-membership-settings.php';
require_once FLEXPRESS_PATH . '/includes/admin/class-flexpress-flowguard-settings.php';
// Legacy Verotel settings (to be removed after migration)
require_once FLEXPRESS_PATH . '/includes/admin/class-flexpress-verotel-settings.php';
require_once FLEXPRESS_PATH . '/includes/admin/class-flexpress-pricing-settings.php';
require_once FLEXPRESS_PATH . '/includes/admin/class-flexpress-affiliate-settings.php';
require_once FLEXPRESS_PATH . '/includes/admin/class-flexpress-contact-settings.php';

// FlexPress Settings menus are initialized via admin settings classes above



/**
 * Theme Setup
 */
function flexpress_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));

    // Register navigation menus
    register_nav_menus(array(
        'primary' => esc_html__('Primary Menu', 'flexpress'),
        'footer-1' => esc_html__('Footer Column 1', 'flexpress'),
        'footer-2' => esc_html__('Footer Column 2', 'flexpress'),
        'quick-links' => esc_html__('Quick Links Menu', 'flexpress'),
        'legal-menu' => esc_html__('Legal Menu', 'flexpress'),
        'footer-menu' => esc_html__('Footer Main Menu', 'flexpress'),
        'footer-support-menu' => esc_html__('Footer Support Menu', 'flexpress'),
        'footer-legal-menu' => esc_html__('Footer Legal Menu', 'flexpress'),
        'footer-friends-menu' => esc_html__('Footer Friends Menu', 'flexpress'),
    ));
    
    // Initialize default pricing plans if none exist
    flexpress_maybe_create_default_pricing_plans();
    
    // Create default plans if they don't exist or are incomplete
    flexpress_ensure_pricing_plans_complete();
    
    // Ensure join page exists
    flexpress_create_join_page();
}
add_action('after_setup_theme', 'flexpress_setup');

/**
 * Enqueue scripts and styles
 */
function flexpress_enqueue_scripts_and_styles() {
    // Enqueue Bootstrap CSS
    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
    
    // Enqueue Font Awesome
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', array(), '6.0.0');
    
    // Enqueue theme CSS files
    wp_enqueue_style('flexpress-variables', get_template_directory_uri() . '/assets/css/variables.css', array(), wp_get_theme()->get('Version'));
    wp_enqueue_style('flexpress-main', get_template_directory_uri() . '/assets/css/main.css', array('flexpress-variables'), wp_get_theme()->get('Version'));
    wp_enqueue_style('flexpress-gallery', get_template_directory_uri() . '/assets/css/gallery.css', array('flexpress-main'), wp_get_theme()->get('Version'));
    wp_enqueue_style('flexpress-style', get_stylesheet_uri(), array('flexpress-main'), wp_get_theme()->get('Version'));
    
    // Enqueue hero video CSS on homepage
    if (is_page_template('page-templates/page-home.php')) {
        wp_enqueue_style('flexpress-hero-video', get_template_directory_uri() . '/assets/css/hero-video.css', array('flexpress-main'), wp_get_theme()->get('Version'));
    }
    
    // Enqueue jQuery
    wp_enqueue_script('jquery');
    
    // Enqueue Bootstrap JS
    wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3', true);
    
    // Enqueue main JavaScript
    wp_enqueue_script('flexpress-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), wp_get_theme()->get('Version'), true);
    
    // Enqueue hero video script on homepage
    if (is_page_template('page-templates/page-home.php')) {
        wp_enqueue_script('flexpress-hero-video', get_template_directory_uri() . '/assets/js/hero-video.js', array(), wp_get_theme()->get('Version'), true);
    }
    
    // Get membership status for localized data
    $membership_status = '';
    $is_active_member = false;
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $membership_status = get_user_meta($user_id, 'membership_status', true);
        $is_active_member = in_array($membership_status, ['active', 'cancelled']);
    }
    
    // Localize script with necessary data
    wp_localize_script('flexpress-main', 'FlexPressData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('flexpress_nonce'),
        'isLoggedIn' => is_user_logged_in(),
        'membershipStatus' => $membership_status,
        'isActiveMember' => $is_active_member,
        'bunnycdnUrl' => get_option('flexpress_video_bunnycdn_url', ''),
        'libraryId' => get_option('flexpress_video_bunnycdn_library_id', ''),
        'token' => '', // Will be generated per request
        'expires' => time() + 3600
    ));
    
    // Enqueue login script on login page
    if (is_page_template('page-templates/login.php')) {
        wp_enqueue_script('flexpress-login', get_template_directory_uri() . '/assets/js/login.js', array('jquery'), wp_get_theme()->get('Version'), true);
    }
    
    // Enqueue registration script on registration page
    if (is_page_template('page-templates/register.php')) {
        wp_enqueue_script('flexpress-registration', get_template_directory_uri() . '/assets/js/registration.js', array('jquery'), wp_get_theme()->get('Version'), true);
        
        // Localize registration script with proper data
        wp_localize_script('flexpress-registration', 'flexpressRegistration', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flexpress-registration-nonce'),
            'isJoinPage' => false
        ));
    }
    
    // Enqueue join page specific script
    if (is_page_template('page-templates/join.php')) {
        wp_enqueue_script('flexpress-join', get_template_directory_uri() . '/assets/js/join.js', array(), wp_get_theme()->get('Version'), true);
    }
    
    // Enqueue Flowguard script where needed
    if (is_page_template('page-templates/flowguard-payment.php') || 
        is_page_template('page-templates/payment-success.php') || 
        is_page_template('page-templates/payment-declined.php') ||
        is_page_template('page-templates/join.php')) {
        wp_enqueue_script('flexpress-flowguard', get_template_directory_uri() . '/assets/js/flowguard.js', array('jquery'), wp_get_theme()->get('Version'), true);
        
        // Get Flowguard settings
        $flowguard_settings = get_option('flexpress_flowguard_settings', []);
        
        wp_localize_script('flexpress-flowguard', 'flowguardConfig', array(
            'shopId' => $flowguard_settings['shop_id'] ?? '',
            'environment' => $flowguard_settings['environment'] ?? 'sandbox',
            'nonce' => wp_create_nonce('flowguard_payment'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ));
    }
    
    // Legacy Verotel script (to be removed after migration)
    if (is_page_template('page-templates/membership.php') || is_page_template('page-templates/dashboard.php')) {
        wp_enqueue_script('flexpress-verotel', get_template_directory_uri() . '/assets/js/verotel.js', array('jquery'), wp_get_theme()->get('Version'), true);
        
        wp_localize_script('flexpress-verotel', 'flexpress_verotel', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flexpress_verotel_nonce')
        ));
    }
    
    // Enqueue models lazy loading script on models archive page
    if (is_post_type_archive('model')) {
        wp_enqueue_script(
            'flexpress-models-lazy-load',
            get_template_directory_uri() . '/assets/js/models-lazy-load.js',
            array('jquery'),
            wp_get_theme()->get('Version'),
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('flexpress-models-lazy-load', 'flexpress_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('load_more_models')
        ));
    }
    
    // Enqueue gallery lightbox script on single episode pages
    if (is_singular('episode')) {
        wp_enqueue_script(
            'flexpress-gallery-lightbox',
            get_template_directory_uri() . '/assets/js/gallery-lightbox.js',
            array('jquery'),
            wp_get_theme()->get('Version'),
            true
        );
    }
    
    // Enqueue affiliate signup script on affiliate signup page
    if (is_page_template('page-templates/affiliate-signup.php')) {
        wp_enqueue_style('flexpress-affiliate-styles', get_template_directory_uri() . '/assets/css/affiliate-styles.css', array('flexpress-main'), wp_get_theme()->get('Version'));
        wp_enqueue_script('flexpress-affiliate-signup', get_template_directory_uri() . '/assets/js/affiliate-signup.js', array('jquery'), wp_get_theme()->get('Version'), true);
        
        wp_localize_script('flexpress-affiliate-signup', 'affiliateSignup', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flexpress_affiliate_frontend_nonce')
        ));
    }
    
    // Enqueue affiliate dashboard script on affiliate dashboard page
    if (is_page_template('page-templates/affiliate-dashboard.php')) {
        wp_enqueue_style('flexpress-affiliate-styles', get_template_directory_uri() . '/assets/css/affiliate-styles.css', array('flexpress-main'), wp_get_theme()->get('Version'));
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);
        wp_enqueue_script('flexpress-affiliate-dashboard', get_template_directory_uri() . '/assets/js/affiliate-dashboard.js', array('jquery', 'chart-js'), wp_get_theme()->get('Version'), true);
        
        // Get affiliate data for the dashboard
        $current_user_id = get_current_user_id();
        $affiliate = null;
        $monthly_stats = array();
        $commission_stats = array();
        
        if ($current_user_id) {
            global $wpdb;
            $affiliate = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d OR email = %s",
                $current_user_id,
                wp_get_current_user()->user_email
            ));
            
            if ($affiliate) {
                $dashboard_data = flexpress_get_affiliate_dashboard_data($affiliate->id);
                if ($dashboard_data) {
                    $monthly_stats = $dashboard_data['monthly_stats'];
                    $commission_stats = $dashboard_data['commission_stats'];
                }
            }
        }
        
        wp_localize_script('flexpress-affiliate-dashboard', 'affiliateNonce', wp_create_nonce('flexpress_affiliate_frontend_nonce'));
        wp_localize_script('flexpress-affiliate-dashboard', 'ajaxurl', admin_url('admin-ajax.php'));
        wp_localize_script('flexpress-affiliate-dashboard', 'affiliateMonthlyStats', $monthly_stats);
        wp_localize_script('flexpress-affiliate-dashboard', 'affiliateCommissionStats', $commission_stats);
    }
    
    // Add custom accent color styles
    flexpress_add_accent_color_styles();
}
add_action('wp_enqueue_scripts', 'flexpress_enqueue_scripts_and_styles');

/**
 * Add accent color CSS variables to the theme
 */
function flexpress_add_accent_color_styles() {
    $options = get_option('flexpress_general_settings');
    $accent_color = isset($options['accent_color']) ? $options['accent_color'] : '#ff6b35';
    
    // Generate lighter and darker variants
    $accent_rgb = flexpress_hex_to_rgb($accent_color);
    $accent_hover = flexpress_darken_color($accent_color, 15);
    $accent_light = sprintf('rgba(%d, %d, %d, 0.2)', $accent_rgb['r'], $accent_rgb['g'], $accent_rgb['b']);
    $accent_dark = flexpress_darken_color($accent_color, 25);
    
    $custom_css = "
        /* FlexPress Dynamic Accent Colors - Override all instances */
        :root {
            --color-accent: {$accent_color} !important;
            --color-accent-hover: {$accent_hover} !important;
            --color-accent-light: {$accent_light} !important;
            --color-accent-dark: {$accent_dark} !important;
        }
        
        /* Additional specific overrides to ensure consistency */
        .btn-primary { background-color: {$accent_color} !important; border-color: {$accent_color} !important; }
        .btn-primary:hover { background-color: {$accent_hover} !important; border-color: {$accent_hover} !important; }
        .navbar-nav .nav-link:hover { color: {$accent_color} !important; }
        .navbar-nav .nav-link.active { color: {$accent_color} !important; }
        .section-title:after { background-color: {$accent_color} !important; }
    ";
    
    wp_add_inline_style('flexpress-main', $custom_css);
}

/**
 * Debug function to output accent color in HTML comments
 */
function flexpress_debug_accent_color() {
    if (WP_DEBUG || isset($_GET['debug_colors'])) {
        $options = get_option('flexpress_general_settings');
        $accent_color = isset($options['accent_color']) ? $options['accent_color'] : 'NOT SET';
        echo "<!-- FlexPress Debug: Accent Color = {$accent_color} -->\n";
        echo "<!-- FlexPress Debug: General Settings = " . print_r($options, true) . " -->\n";
    }
}
add_action('wp_head', 'flexpress_debug_accent_color');

/**
 * AJAX function to test settings saving
 */
function flexpress_test_settings_save() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    // Get current settings
    $options = get_option('flexpress_general_settings', array());
    
    // Set a test color
    $options['accent_color'] = '#ff69b4'; // Pink color
    
    // Save the settings
    $result = update_option('flexpress_general_settings', $options);
    
    // Return debug info
    echo "Update result: " . ($result ? 'SUCCESS' : 'FAILED') . "<br>";
    echo "Settings after update: <pre>" . print_r(get_option('flexpress_general_settings'), true) . "</pre>";
    
    wp_die();
}
add_action('wp_ajax_flexpress_test_settings', 'flexpress_test_settings_save');

/**
 * Sanitize general settings - shared function for both classes
 */
function flexpress_sanitize_general_settings($input) {
    $sanitized = array();
    
    // Sanitize site title
    if (isset($input['site_title'])) {
        $sanitized['site_title'] = sanitize_text_field($input['site_title']);
    }
    
    // Sanitize site description
    if (isset($input['site_description'])) {
        $sanitized['site_description'] = sanitize_textarea_field($input['site_description']);
    }
    
    // Sanitize custom logo
    if (isset($input['custom_logo'])) {
        $sanitized['custom_logo'] = absint($input['custom_logo']);
    }
    
    // Sanitize accent color - ensure it's a valid hex color
    if (isset($input['accent_color'])) {
        $color = sanitize_hex_color($input['accent_color']);
        $sanitized['accent_color'] = $color ? $color : '#ff6b35'; // Fallback to default
    }
    
    return $sanitized;
}

/**
 * Convert hex color to RGB array
 */
function flexpress_hex_to_rgb($hex) {
    $hex = str_replace('#', '', $hex);
    
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
    }
    
    return array(
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2))
    );
}

/**
 * Darken a hex color by a percentage
 */
function flexpress_darken_color($hex, $percent) {
    $rgb = flexpress_hex_to_rgb($hex);
    
    $r = max(0, $rgb['r'] - (255 * $percent / 100));
    $g = max(0, $rgb['g'] - (255 * $percent / 100));
    $b = max(0, $rgb['b'] - (255 * $percent / 100));
    
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/**
 * Register widget areas
 */
function flexpress_widgets_init() {
    register_sidebar(array(
        'name'          => esc_html__('Sidebar', 'flexpress'),
        'id'            => 'sidebar-1',
        'description'   => esc_html__('Add widgets here.', 'flexpress'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
}
add_action('widgets_init', 'flexpress_widgets_init');

/**
 * Enable comments for new model posts by default
 */
function flexpress_enable_model_comments($post_id, $post, $update) {
    // Only for new model posts (not updates)
    if (!$update && $post->post_type === 'model') {
        // Enable comments for this model post
        wp_update_post(array(
            'ID' => $post_id,
            'comment_status' => 'open'
        ));
    }
}
add_action('wp_insert_post', 'flexpress_enable_model_comments', 10, 3);

/**
 * Restrict model comments to logged-in users with active memberships
 */
function flexpress_restrict_model_comments($approved, $commentdata) {
    // Only apply to model posts
    if (get_post_type($commentdata['comment_post_ID']) !== 'model') {
        return $approved;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die(__('You must be logged in to leave comments.', 'flexpress'), 403);
    }
    
    // Check if user has active membership
    if (!flexpress_has_active_membership()) {
        wp_die(__('You must have an active membership to send messages to models.', 'flexpress'), 403);
    }
    
    return $approved;
}
add_filter('pre_comment_approved', 'flexpress_restrict_model_comments', 10, 2);

/**
 * AJAX endpoint for generating BunnyCDN tokens
 */
function flexpress_generate_bunnycdn_token() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'flexpress-nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    // Get video ID from request
    $video_id = isset($_POST['video_id']) ? sanitize_text_field($_POST['video_id']) : '';
    if (empty($video_id)) {
        wp_send_json_error('Invalid video ID');
        return;
    }
    
    // Get video settings
    $video_settings = get_option('flexpress_video_settings', array());
    $library_id = isset($video_settings['bunnycdn_library_id']) ? $video_settings['bunnycdn_library_id'] : '';
    $token_key = isset($video_settings['bunnycdn_token_key']) ? $video_settings['bunnycdn_token_key'] : '';
    
    if (empty($token_key) || empty($library_id)) {
        wp_send_json_error('Missing BunnyCDN configuration');
        return;
    }
    
    // Generate expiration timestamp
    $expires = time() + 3600; // 1 hour expiry
    
    // Generate token
    // BunnyCDN token generation - format: hash('sha256', $token_key . $video_id . $expires)
    $token = hash('sha256', $token_key . $video_id . $expires);
    
    // Return token and expiration
    wp_send_json_success(array(
        'token' => $token,
        'expires' => $expires,
        'libraryId' => $library_id
    ));
}
add_action('wp_ajax_flexpress_generate_bunnycdn_token', 'flexpress_generate_bunnycdn_token');
add_action('wp_ajax_nopriv_flexpress_generate_bunnycdn_token', 'flexpress_generate_bunnycdn_token');

/**
 * AJAX endpoint for clearing BunnyCDN cache
 */
function flexpress_clear_bunnycdn_cache_ajax() {
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'flexpress_clear_cache')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
    
    if ($type === 'all') {
        // Clear all video caches
        if (function_exists('flexpress_clear_all_bunnycdn_video_cache')) {
            $count = flexpress_clear_all_bunnycdn_video_cache();
            wp_send_json_success(sprintf(__('Cleared cache for %d videos', 'flexpress'), $count));
        } else {
            wp_send_json_error('Cache clearing function not available');
        }
    } elseif ($type === 'specific') {
        // Clear specific video cache
        $video_id = isset($_POST['video_id']) ? sanitize_text_field($_POST['video_id']) : '';
        if (empty($video_id)) {
            wp_send_json_error('Video ID is required');
            return;
        }
        
        if (function_exists('flexpress_clear_bunnycdn_video_cache')) {
            $result = flexpress_clear_bunnycdn_video_cache($video_id);
            if ($result) {
                wp_send_json_success(sprintf(__('Cache cleared for video: %s', 'flexpress'), $video_id));
            } else {
                wp_send_json_success(__('No cache found for this video (may already be cleared)', 'flexpress'));
            }
        } else {
            wp_send_json_error('Cache clearing function not available');
        }
    } else {
        wp_send_json_error('Invalid cache type');
    }
}
add_action('wp_ajax_flexpress_clear_bunnycdn_cache', 'flexpress_clear_bunnycdn_cache_ajax');

/**
 * Update episode durations from BunnyCDN
 * 
 * This function can be scheduled to run periodically via WP-Cron
 * or called manually to update all episode durations.
 * 
 * @param bool $force_refresh Whether to force refresh the BunnyCDN data
 * @return int Number of episodes updated
 */
function flexpress_update_episode_durations($force_refresh = true) {
    if (!function_exists('get_field')) {
        return 0;
    }
    
    // Get all episodes with full_video field set
    $episodes = get_posts(array(
        'post_type' => 'episode',
        'posts_per_page' => -1,
        'meta_key' => 'full_video',
        'meta_value' => '',
        'meta_compare' => '!='
    ));
    
    $updated_count = 0;
    $skipped_count = 0; // Initialize counter
    
    foreach ($episodes as $episode) {
        $post_id = $episode->ID;
        $full_video = get_field('full_video', $post_id);
        $current_duration = get_field('episode_duration', $post_id);
        
        if (empty($full_video)) {
            continue;
        }
        
        echo '<div id="episode-' . $post_id . '" class="episode-update">';
        echo '<h3>' . esc_html($episode->post_title) . ' (ID: ' . $post_id . ')</h3>';
        echo '<p>Video ID: ' . esc_html($full_video) . '</p>';
        
        // Get video details from BunnyCDN
        $video_details = flexpress_get_bunnycdn_video_details($full_video, $force_refresh);
        
        $duration_seconds = null;
        $duration_field = null; // Initialize variable
        
        // Check multiple possible properties for duration
        if (isset($video_details['length'])) {
            $duration_seconds = $video_details['length'];
            $duration_field = 'length';
        } elseif (isset($video_details['duration'])) {
            $duration_seconds = $video_details['duration'];
            $duration_field = 'duration';
        } elseif (isset($video_details['lengthSeconds'])) {
            $duration_seconds = $video_details['lengthSeconds'];
            $duration_field = 'lengthSeconds';
        } elseif (isset($video_details['durationSeconds'])) {
            $duration_seconds = $video_details['durationSeconds'];
            $duration_field = 'durationSeconds';
        }
        
        if ($duration_seconds !== null && $duration_field !== null) {
            // Format duration as MM:SS
            $minutes = floor($duration_seconds / 60);
            $seconds = $duration_seconds % 60;
            $duration_formatted = sprintf('%d:%02d', $minutes, $seconds);
            
            // Only update if different from current duration
            if ($current_duration !== $duration_formatted) {
                update_field('episode_duration', $duration_formatted, $post_id);
                echo '<p class="success">Updated duration to ' . $duration_formatted . ' (from ' . $duration_seconds . ' seconds in field "' . $duration_field . '")</p>';
                echo '<p class="meta">Previous duration: ' . ($current_duration ? $current_duration : 'Not set') . '</p>';
                echo '</div>';
                $updated_count++;
                
                // Add updated class with JavaScript
                echo '<script>document.getElementById("episode-' . $post_id . '").className += " updated";</script>';
            } else {
                echo '<p class="meta">Already correct: ' . $duration_formatted . ' (' . $duration_seconds . ' seconds in field "' . $duration_field . '")</p>';
                echo '</div>';
                $skipped_count++;
                
                // Add skipped class with JavaScript
                echo '<script>document.getElementById("episode-' . $post_id . '").className += " skipped";</script>';
            }
        } else {
            echo '<p class="error">Unable to retrieve duration from BunnyCDN API</p>';
            echo '<p class="debug">Response: ' . json_encode($video_details) . '</p>';
            echo '</div>';
            
            // Add error class with JavaScript
            echo '<script>document.getElementById("episode-' . $post_id . '").className += " error";</script>';
        }
        
        // Flush output immediately to show progress
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
    
    return $updated_count;
}

/**
 * Schedule daily episode duration update
 */
function flexpress_schedule_episode_duration_updates() {
    if (!wp_next_scheduled('flexpress_update_episode_durations_hook')) {
        wp_schedule_event(time(), 'daily', 'flexpress_update_episode_durations_hook');
    }
}
add_action('wp', 'flexpress_schedule_episode_duration_updates');

/**
 * Hook for scheduled duration updates
 */
function flexpress_do_scheduled_episode_duration_update() {
    flexpress_update_episode_durations();
}
add_action('flexpress_update_episode_durations_hook', 'flexpress_do_scheduled_episode_duration_update');

// Add admin action to manually update durations
function flexpress_register_admin_actions() {
    add_action('admin_post_update_episode_durations', 'flexpress_handle_duration_update_action');
}
add_action('admin_init', 'flexpress_register_admin_actions');

// Handle the admin action
function flexpress_handle_duration_update_action() {
    // Verify nonce and user capabilities
    if (!isset($_POST['update_durations_nonce']) || !wp_verify_nonce($_POST['update_durations_nonce'], 'update_episode_durations_nonce') || !current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'flexpress'));
    }
    
    $updated = flexpress_update_episode_durations();
    
    // Redirect back with message
    wp_redirect(add_query_arg(array(
        'page' => 'flexpress-settings',
        'tab' => 'video',
        'updated' => '1',
        'count' => $updated
    ), admin_url('admin.php')));
    exit;
}

/**
 * Update episode duration when an episode is saved or updated
 * 
 * @param int $post_id The post ID
 * @param WP_Post $post The post object
 * @param bool $update Whether this is an update or a new post
 */
function flexpress_update_episode_duration_on_save($post_id, $post, $update) {
    // Skip if this is an autosave or not an episode post type
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if ($post->post_type !== 'episode') {
        return;
    }
    
    // Skip revisions
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    // Wait for ACF to save the fields first if using ACF
    // This needs to be delayed to ensure custom fields are saved
    wp_schedule_single_event(time() + 5, 'flexpress_delayed_episode_duration_update', array($post_id));
}
add_action('save_post', 'flexpress_update_episode_duration_on_save', 20, 3);

/**
 * Delayed episode duration update to give ACF time to save fields
 * 
 * @param int $post_id The post ID
 */
function flexpress_delayed_episode_duration_update($post_id) {
    // Get the full video ID from the saved data
    if (!function_exists('get_field')) {
        return;
    }
    
    $full_video = get_field('full_video', $post_id);
    
    if (empty($full_video)) {
        return;
    }
    
    // Force refresh from BunnyCDN (don't use cache)
    $video_details = flexpress_get_bunnycdn_video_details($full_video, true);
    
    $duration_seconds = null;
    
    // Check multiple possible properties for duration
    if (isset($video_details['length'])) {
        $duration_seconds = $video_details['length'];
    } elseif (isset($video_details['duration'])) {
        $duration_seconds = $video_details['duration'];
    } elseif (isset($video_details['lengthSeconds'])) {
        $duration_seconds = $video_details['lengthSeconds'];
    } elseif (isset($video_details['durationSeconds'])) {
        $duration_seconds = $video_details['durationSeconds'];
    }
    
    if ($duration_seconds !== null) {
        // Format duration as MM:SS
        $minutes = floor($duration_seconds / 60);
        $seconds = $duration_seconds % 60;
        $duration_formatted = sprintf('%d:%02d', $minutes, $seconds);
        
        // Update the field
        update_field('episode_duration', $duration_formatted, $post_id);
        
        // Log the update
        error_log('Updated episode duration for post ID ' . $post_id . ' to ' . $duration_formatted);
    } else {
        error_log('Could not retrieve duration for episode ID ' . $post_id . ' with video ID ' . $full_video);
    }
}
add_action('flexpress_delayed_episode_duration_update', 'flexpress_delayed_episode_duration_update');

/**
 * Add admin menu item for episode duration update utility
 */
function flexpress_add_duration_update_menu() {
    add_submenu_page(
        'edit.php?post_type=episode',  // parent slug
        'Update Episode Durations',     // page title
        'Update Durations',             // menu title
        'manage_options',               // capability
        'update-episode-durations',     // menu slug
        'flexpress_render_duration_update_page' // callback function
    );
}
add_action('admin_menu', 'flexpress_add_duration_update_menu');

/**
 * Callback function to render the duration update page
 */
function flexpress_render_duration_update_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    
    // Process form submission - if user clicked "Update All" button
    $updated_count = 0;
    $error_count = 0;
    $processed = false;
    
    if (isset($_POST['update_all_durations']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'update_episode_durations_nonce')) {
        $processed = true;
        $updated_count = flexpress_update_episode_durations(true);
        
        // Count episodes with errors (those that have full_video but no duration after update attempt)
        $error_episodes = get_posts(array(
            'post_type' => 'episode',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'full_video',
                    'value' => '',
                    'compare' => '!='
                ),
                array(
                    'key' => 'episode_duration',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        $error_count = count($error_episodes);
    }
    
    // Count total episodes
    $total_episodes = wp_count_posts('episode')->publish;
    
    // Get episodes with missing durations
    $missing_duration_episodes = get_posts(array(
        'post_type' => 'episode',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'full_video',
                'value' => '',
                'compare' => '!='
            ),
            array(
                'key' => 'episode_duration',
                'compare' => 'NOT EXISTS'
            )
        )
    ));
    
    $missing_duration_count = count($missing_duration_episodes);
    
    // Set up admin page
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php if ($processed): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo sprintf('Processed %d episodes. Updated %d episode durations.', $total_episodes, $updated_count); ?></p>
            </div>
            
            <?php if ($error_count > 0): ?>
                <div class="notice notice-warning is-dismissible">
                    <p><?php echo sprintf('%d episodes could not be updated. They may have invalid BunnyCDN video IDs.', $error_count); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="card">
            <h2 class="title">Update Episode Durations from BunnyCDN</h2>
            <p>This utility will fetch the video duration for all episodes from BunnyCDN and update the episode_duration field.</p>
            
            <p><strong>Statistics:</strong></p>
            <ul>
                <li>Total Episodes: <?php echo $total_episodes; ?></li>
                <li>Episodes Missing Duration: <?php echo $missing_duration_count; ?></li>
            </ul>
            
            <form method="post" action="">
                <?php wp_nonce_field('update_episode_durations_nonce'); ?>
                <input type="submit" name="update_all_durations" class="button button-primary" value="Update All Episode Durations">
            </form>
        </div>
        
        <?php if ($missing_duration_count > 0): ?>
            <div class="card" style="margin-top: 20px;">
                <h2 class="title">Episodes Missing Duration</h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Episode</th>
                            <th>BunnyCDN Video ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($missing_duration_episodes as $episode): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($episode->ID); ?>">
                                        <?php echo get_the_title($episode->ID); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html(get_field('full_video', $episode->ID)); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($episode->ID); ?>" class="button button-small">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Custom Login and Password Reset Functions
 */

// Register AJAX login handler
function flexpress_ajax_login_init() {
    wp_register_script('flexpress-login', get_template_directory_uri() . '/assets/js/login.js', array('jquery'), '1.0', true);
    
    wp_localize_script('flexpress-login', 'ajax_login_object', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'redirecturl' => home_url('/my-account'),
        'loadingmessage' => __('Verifying credentials, please wait...', 'flexpress')
    ));
    
    wp_enqueue_script('flexpress-login');
}
add_action('wp_enqueue_scripts', 'flexpress_ajax_login_init');

// Handle AJAX login request
function flexpress_ajax_login() {
    // First check the nonce, if it fails the function will break
    check_ajax_referer('ajax-login-nonce', 'security');
    
    // Gather user data
    $credentials = array(
        'user_login' => $_POST['username'],
        'user_password' => $_POST['password'],
        'remember' => isset($_POST['remember']) ? true : false
    );
    
    // Attempt to log the user in
    $user = wp_signon($credentials, false);
    
    // Return result
    if (is_wp_error($user)) {
        echo json_encode(array(
            'success' => false,
            'message' => $user->get_error_message()
        ));
    } else {
        echo json_encode(array(
            'success' => true,
            'message' => __('Login successful, redirecting...', 'flexpress')
        ));
    }
    
    wp_die();
}
add_action('wp_ajax_nopriv_flexpress_ajax_login', 'flexpress_ajax_login');

// Handle AJAX password reset request
function flexpress_ajax_password_reset() {
    // First check the nonce, if it fails the function will break
    check_ajax_referer('ajax-forgot-nonce', 'security');
    
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        echo json_encode(array(
            'success' => false,
            'message' => __('Please enter your email address.', 'flexpress')
        ));
        wp_die();
    }
    
    // Check if user exists
    $user_data = get_user_by('email', $email);
    
    if (!$user_data) {
        echo json_encode(array(
            'success' => false,
            'message' => __('No account found with that email address.', 'flexpress')
        ));
        wp_die();
    }
    
    // Get user login
    $user_login = $user_data->user_login;
    
    // Generate reset key
    $key = get_password_reset_key($user_data);
    
    if (is_wp_error($key)) {
        echo json_encode(array(
            'success' => false,
            'message' => __('Error generating password reset link. Please try again later.', 'flexpress')
        ));
        wp_die();
    }
    
    // Build reset link
    $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
    
    // Email subject
    $subject = sprintf(__('[%s] Password Reset', 'flexpress'), get_bloginfo('name'));
    
    // Email message
    $message = __('Someone has requested a password reset for the following account:', 'flexpress') . "\r\n\r\n";
    $message .= network_home_url('/') . "\r\n\r\n";
    $message .= sprintf(__('Username: %s', 'flexpress'), $user_login) . "\r\n\r\n";
    $message .= __('If this was a mistake, just ignore this email and nothing will happen.', 'flexpress') . "\r\n\r\n";
    $message .= __('To reset your password, visit the following address:', 'flexpress') . "\r\n\r\n";
    $message .= $reset_url . "\r\n";
    
    // Send email
    $mail_sent = wp_mail($email, $subject, $message);
    
    if ($mail_sent) {
        echo json_encode(array(
            'success' => true,
            'message' => __('Password reset link has been sent to your email address.', 'flexpress')
        ));
    } else {
        echo json_encode(array(
            'success' => false,
            'message' => __('Error sending password reset email. Please try again later.', 'flexpress')
        ));
    }
    
    wp_die();
}
add_action('wp_ajax_nopriv_flexpress_ajax_password_reset', 'flexpress_ajax_password_reset');

// Custom password reset page
function flexpress_redirect_to_custom_password_reset() {
    if ('GET' == $_SERVER['REQUEST_METHOD']) {
        // Verify key / login combo
        $user = check_password_reset_key($_REQUEST['key'], $_REQUEST['login']);
        
        if (!$user || is_wp_error($user)) {
            if ($user && $user->get_error_code() === 'expired_key') {
                wp_redirect(home_url('/lost-password?error=expiredkey'));
            } else {
                wp_redirect(home_url('/lost-password?error=invalidkey'));
            }
            exit;
        }
        
        $redirect_url = home_url('/reset-password');
        $redirect_url = add_query_arg('login', esc_attr($_REQUEST['login']), $redirect_url);
        $redirect_url = add_query_arg('key', esc_attr($_REQUEST['key']), $redirect_url);
        
        wp_redirect($redirect_url);
        exit;
    }
}
add_action('login_form_rp', 'flexpress_redirect_to_custom_password_reset');
add_action('login_form_resetpass', 'flexpress_redirect_to_custom_password_reset');

// Handle password reset form submission
function flexpress_do_password_reset() {
    if ('POST' == $_SERVER['REQUEST_METHOD']) {
        $rp_key = $_POST['rp_key'];
        $rp_login = $_POST['rp_login'];
        
        $user = check_password_reset_key($rp_key, $rp_login);
        
        if (!$user || is_wp_error($user)) {
            if ($user && $user->get_error_code() === 'expired_key') {
                wp_redirect(home_url('/lost-password?error=expiredkey'));
            } else {
                wp_redirect(home_url('/lost-password?error=invalidkey'));
            }
            exit;
        }
        
        if (isset($_POST['pass1'])) {
            if ($_POST['pass1'] != $_POST['pass2']) {
                // Passwords don't match
                $redirect_url = home_url('/reset-password');
                $redirect_url = add_query_arg('key', $rp_key, $redirect_url);
                $redirect_url = add_query_arg('login', $rp_login, $redirect_url);
                $redirect_url = add_query_arg('error', 'password_mismatch', $redirect_url);
                
                wp_redirect($redirect_url);
                exit;
            }
            
            if (empty($_POST['pass1'])) {
                // Password is empty
                $redirect_url = home_url('/reset-password');
                $redirect_url = add_query_arg('key', $rp_key, $redirect_url);
                $redirect_url = add_query_arg('login', $rp_login, $redirect_url);
                $redirect_url = add_query_arg('error', 'password_empty', $redirect_url);
                
                wp_redirect($redirect_url);
                exit;
            }
            
            // Parameter checks OK, reset password
            reset_password($user, $_POST['pass1']);
            wp_redirect(home_url('/login?password=changed'));
            exit;
        } else {
            echo "Invalid request.";
        }
        
        exit;
    }
}
add_action('login_form_rp', 'flexpress_do_password_reset');
add_action('login_form_resetpass', 'flexpress_do_password_reset');

/**
 * Create Home page with the page-home.php template
 */
function flexpress_create_home_page() {
    // Check if the page already exists
    $home_page = get_page_by_path('home');
    
    if (!$home_page) {
        // Create the page
        $home_page_id = wp_insert_post(array(
            'post_title'     => 'Home',
            'post_content'   => 'Welcome to our premium content platform featuring exclusive episodes and your favorite models.',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        ));
        
        if ($home_page_id && !is_wp_error($home_page_id)) {
            // Set page template
            update_post_meta($home_page_id, '_wp_page_template', 'page-templates/page-home.php');
            
            // Optionally set as front page
            update_option('show_on_front', 'page');
            update_option('page_on_front', $home_page_id);
            
            // Set the flag that we've created the page
            update_option('flexpress_home_page_created', true);
        }
    }
}

// Add action to create home page on theme activation
add_action('after_switch_theme', 'flexpress_create_home_page');

// Also run on init with a check for the option to make sure it only runs once
function flexpress_maybe_create_home_page() {
    if (!get_option('flexpress_home_page_created')) {
        flexpress_create_home_page();
    }
}
add_action('init', 'flexpress_maybe_create_home_page');

/**
 * Handle Contact Form submission
 */
function flexpress_handle_contact_form() {
    // Verify nonce
    if (!isset($_POST['contact_nonce']) || !wp_verify_nonce($_POST['contact_nonce'], 'contact_form')) {
        wp_redirect(add_query_arg('sent', 'failed', wp_get_referer()));
        exit;
    }

    // Get form data
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        wp_redirect(add_query_arg('sent', 'failed', wp_get_referer()));
        exit;
    }

    // Get admin email
    $admin_email = get_option('admin_email');

    // Email headers
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $name . ' <' . $email . '>',
        'Reply-To: ' . $email,
    );

    // Email content
    $email_content = '<p><strong>' . esc_html__('Name:', 'flexpress') . '</strong> ' . esc_html($name) . '</p>';
    $email_content .= '<p><strong>' . esc_html__('Email:', 'flexpress') . '</strong> ' . esc_html($email) . '</p>';
    $email_content .= '<p><strong>' . esc_html__('Subject:', 'flexpress') . '</strong> ' . esc_html($subject) . '</p>';
    $email_content .= '<p><strong>' . esc_html__('Message:', 'flexpress') . '</strong></p>';
    $email_content .= '<p>' . nl2br(esc_html($message)) . '</p>';

    // Send email
    $mail_sent = wp_mail($admin_email, sprintf(__('Contact Form: %s', 'flexpress'), $subject), $email_content, $headers);

    // Redirect based on result
    if ($mail_sent) {
        wp_redirect(add_query_arg('sent', 'success', wp_get_referer()));
    } else {
        wp_redirect(add_query_arg('sent', 'failed', wp_get_referer()));
    }
    exit;
}
add_action('admin_post_contact_form', 'flexpress_handle_contact_form');
add_action('admin_post_nopriv_contact_form', 'flexpress_handle_contact_form');

/**
 * Handle Casting Application Form submission
 */
function flexpress_handle_casting_form() {
    // Verify nonce
    if (!isset($_POST['casting_nonce']) || !wp_verify_nonce($_POST['casting_nonce'], 'casting_form')) {
        wp_redirect(add_query_arg('sent', 'failed', wp_get_referer()));
        exit;
    }

    // Get form data
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $age = isset($_POST['age']) ? intval($_POST['age']) : 0;
    $experience = isset($_POST['experience']) ? sanitize_textarea_field($_POST['experience']) : '';
    $social_media = isset($_POST['social_media']) ? sanitize_textarea_field($_POST['social_media']) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

    // Validate required fields
    if (empty($name) || empty($email) || empty($age) || $age < 18) {
        wp_redirect(add_query_arg('sent', 'failed', wp_get_referer()));
        exit;
    }

    // Get admin email
    $admin_email = get_option('admin_email');

    // Email headers
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $name . ' <' . $email . '>',
        'Reply-To: ' . $email,
    );

    // Email content
    $email_content = '<p><strong>' . esc_html__('Name:', 'flexpress') . '</strong> ' . esc_html($name) . '</p>';
    $email_content .= '<p><strong>' . esc_html__('Email:', 'flexpress') . '</strong> ' . esc_html($email) . '</p>';
    $email_content .= '<p><strong>' . esc_html__('Phone:', 'flexpress') . '</strong> ' . esc_html($phone) . '</p>';
    $email_content .= '<p><strong>' . esc_html__('Age:', 'flexpress') . '</strong> ' . esc_html($age) . '</p>';
    $email_content .= '<p><strong>' . esc_html__('Experience:', 'flexpress') . '</strong></p>';
    $email_content .= '<p>' . nl2br(esc_html($experience)) . '</p>';
    $email_content .= '<p><strong>' . esc_html__('Social Media:', 'flexpress') . '</strong></p>';
    $email_content .= '<p>' . nl2br(esc_html($social_media)) . '</p>';
    $email_content .= '<p><strong>' . esc_html__('Additional Information:', 'flexpress') . '</strong></p>';
    $email_content .= '<p>' . nl2br(esc_html($message)) . '</p>';

    // Send email
    $mail_sent = wp_mail($admin_email, __('New Casting Application', 'flexpress'), $email_content, $headers);

    // Redirect based on result
    if ($mail_sent) {
        wp_redirect(add_query_arg('sent', 'success', wp_get_referer()));
    } else {
        wp_redirect(add_query_arg('sent', 'failed', wp_get_referer()));
    }
    exit;
}
add_action('admin_post_casting_form', 'flexpress_handle_casting_form');
add_action('admin_post_nopriv_casting_form', 'flexpress_handle_casting_form');

/**
 * Handle Content Removal Form submission
 */
function flexpress_handle_content_removal_form() {
    // Verify nonce
    if (!isset($_POST['removal_nonce']) || !wp_verify_nonce($_POST['removal_nonce'], 'removal_form')) {
        wp_redirect(add_query_arg('sent', 'failed', wp_get_referer()));
        exit;
    }

    // Get form data
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $content_url = isset($_POST['content_url']) ? esc_url_raw($_POST['content_url']) : '';
    $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
    $verification = isset($_POST['verification']) ? sanitize_textarea_field($_POST['verification']) : '';

    // Validate required fields
    if (empty($name) || empty($email) || empty($content_url) || empty($reason)) {
        wp_redirect(add_query_arg('sent', 'failed', wp_get_referer()));
        exit;
    }

    // Get admin email
    $admin_email = get_option('admin_email');

    // Email headers
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $name . ' <' . $email . '>',
        'Reply-To: ' . $email,
    );

    // Email content
    $email_content = '<p><strong>' . esc_html__('Name:', 'flexpress') . '</strong> ' . esc_html($name) . '</p>';
    $email_content .= '<p><strong>' . esc_html__('Email:', 'flexpress') . '</strong> ' . esc_html($email) . '</p>';
    $email_content .= '<p><strong>' . esc_html__('Content URL:', 'flexpress') . '</strong> ' . esc_html($content_url) . '</p>';
    $email_content .= '<p><strong>' . esc_html__('Reason for Removal:', 'flexpress') . '</strong></p>';
    $email_content .= '<p>' . nl2br(esc_html($reason)) . '</p>';
    $email_content .= '<p><strong>' . esc_html__('Identity Verification:', 'flexpress') . '</strong></p>';
    $email_content .= '<p>' . nl2br(esc_html($verification)) . '</p>';

    // Send email
    $mail_sent = wp_mail($admin_email, __('Content Removal Request', 'flexpress'), $email_content, $headers);

    // Redirect based on result
    if ($mail_sent) {
        wp_redirect(add_query_arg('sent', 'success', wp_get_referer()));
    } else {
        wp_redirect(add_query_arg('sent', 'failed', wp_get_referer()));
    }
    exit;
}
add_action('admin_post_removal_form', 'flexpress_handle_content_removal_form');
add_action('admin_post_nopriv_removal_form', 'flexpress_handle_content_removal_form');

/**
 * Create default menus and assign them to menu locations
 */
function flexpress_create_default_menus() {
    // Check if menus are already created
    if (get_option('flexpress_menus_created')) {
        return;
    }
    
    // Create Quick Links Menu
    $quick_links_menu_id = wp_create_nav_menu('Quick Links');
    if (is_wp_error($quick_links_menu_id)) {
        error_log('Failed to create Quick Links menu: ' . $quick_links_menu_id->get_error_message());
        return;
    }
    
    // Create Legal Menu
    $legal_menu_id = wp_create_nav_menu('Legal');
    if (is_wp_error($legal_menu_id)) {
        error_log('Failed to create Legal menu: ' . $legal_menu_id->get_error_message());
        return;
    }
    
    // Get all pages
    $pages = get_pages();
    $page_map = array();
    
    // Create a map of page titles to IDs
    foreach ($pages as $page) {
        $page_map[strtolower($page->post_title)] = $page->ID;
    }
    
    // Quick Links pages
    $quick_links_pages = array(
        'Home',
        'Episodes',
        'Models',
        'About',
        'Model for Us / Casting' => 'Casting',
        'Contact',
        'Login',
        'My Account',
        'Membership' => 'Join'
    );
    
    // Legal pages - updated to match your requirements
    $legal_pages = array(
        'Privacy Policy',
        'Customer Terms & Conditions' => 'Terms & Conditions',
        '2257 Compliance',
        'Anti-Slavery and Human Trafficking Policy' => 'Anti-Slavery Policy',
        'Content Removal'
    );
    
    // Add pages to Quick Links menu
    foreach ($quick_links_pages as $key => $value) {
        $title = is_numeric($key) ? $value : $value;
        $slug = is_numeric($key) ? strtolower($value) : strtolower($key);
        
        // Check if page exists either by exact title or slug match
        $page_id = isset($page_map[strtolower($title)]) ? $page_map[strtolower($title)] : (
            isset($page_map[strtolower($slug)]) ? $page_map[strtolower($slug)] : false
        );
        
        if ($page_id) {
            $menu_item_id = wp_update_nav_menu_item($quick_links_menu_id, 0, array(
                'menu-item-title' => $title,
                'menu-item-object' => 'page',
                'menu-item-object-id' => $page_id,
                'menu-item-type' => 'post_type',
                'menu-item-status' => 'publish'
            ));
            
            if (is_wp_error($menu_item_id)) {
                error_log('Failed to add menu item to Quick Links menu: ' . $menu_item_id->get_error_message());
            }
        }
    }
    
    // Add pages to Legal menu
    foreach ($legal_pages as $key => $value) {
        $title = is_numeric($key) ? $value : $value;
        $slug = is_numeric($key) ? strtolower($value) : strtolower($key);
        
        // Check if page exists either by exact title or slug match
        $page_id = isset($page_map[strtolower($title)]) ? $page_map[strtolower($title)] : (
            isset($page_map[strtolower($slug)]) ? $page_map[strtolower($slug)] : false
        );
        
        if ($page_id) {
            $menu_item_id = wp_update_nav_menu_item($legal_menu_id, 0, array(
                'menu-item-title' => $title,
                'menu-item-object' => 'page',
                'menu-item-object-id' => $page_id,
                'menu-item-type' => 'post_type',
                'menu-item-status' => 'publish'
            ));
            
            if (is_wp_error($menu_item_id)) {
                error_log('Failed to add menu item to Legal menu: ' . $menu_item_id->get_error_message());
            }
        }
    }
    
    // Assign menus to locations
    $locations = get_theme_mod('nav_menu_locations', array());
    $locations['quick-links'] = $quick_links_menu_id;
    $locations['legal-menu'] = $legal_menu_id;
    set_theme_mod('nav_menu_locations', $locations);
    
    // Set flag that menus have been created
    update_option('flexpress_menus_created', true);
}

// Run after theme activation
add_action('after_switch_theme', 'flexpress_create_default_menus');

// Also run once on init with a check for the option to make sure it only runs once
function flexpress_maybe_create_default_menus() {
    if (!get_option('flexpress_menus_created')) {
        flexpress_create_default_menus();
    }
}
add_action('init', 'flexpress_maybe_create_default_menus');

/**
 * Create necessary pages for the website if they don't exist
 */
function flexpress_create_required_pages() {
    // Check if pages are already created
    if (get_option('flexpress_pages_created')) {
        return;
    }
    
    // Define pages to create with their templates
    $pages = array(
        'Home' => array(
            'content' => 'Welcome to our premium content platform featuring exclusive episodes and your favorite models.',
            'template' => 'page-templates/page-home.php'
        ),
        'Episodes' => array(
            'content' => 'Browse our exclusive collection of premium episodes.',
            'template' => 'page-templates/episodes.php'
        ),
        'Models' => array(
            'content' => 'Meet our talented models and performers.',
            'template' => ''
        ),
        'About' => array(
            'content' => 'Learn more about our platform and what makes us unique.',
            'template' => 'page-templates/about.php'
        ),
        'Contact' => array(
            'content' => 'Get in touch with our team for any questions or inquiries.',
            'template' => 'page-templates/contact.php'
        ),
        'Login' => array(
            'content' => 'Log in to access your membership and premium content.',
            'template' => 'page-templates/login.php'
        ),
        'My Account' => array(
            'content' => 'Manage your account settings and view your subscription details.',
            'template' => 'page-templates/dashboard.php'
        ),
        'Membership' => array(
            'content' => 'Choose a membership plan and gain access to premium content.',
            'template' => 'page-templates/membership.php'
        ),
        'Model for Us' => array(
            'content' => 'Interested in becoming a model? Apply here to join our team.',
            'template' => 'page-templates/casting.php'
        ),
        'Privacy Policy' => array(
            'content' => 'dynamic_privacy_policy',
            'template' => 'page-templates/privacy.php'
        ),
        'Customer Terms & Conditions' => array(
            'content' => 'dynamic_customer_terms',
            'template' => 'page-templates/terms.php'
        ),
        '2257 Compliance' => array(
            'content' => 'dynamic_2257_compliance',
            'template' => 'page-templates/2257-compliance.php'
        ),
        'Anti-Slavery and Human Trafficking Policy' => array(
            'content' => 'Our commitment to preventing modern slavery and human trafficking in our business and supply chain.',
            'template' => 'page-templates/anti-slavery.php'
        ),
        'Content Removal' => array(
            'content' => 'Request removal of content from our platform.',
            'template' => 'page-templates/content-removal.php'
        ),
        'Lost Password' => array(
            'content' => 'Reset your password to regain access to your account.',
            'template' => 'page-templates/lost-password.php'
        ),
        'Reset Password' => array(
            'content' => 'Set a new password for your account.',
            'template' => 'page-templates/reset-password.php'
        )
    );
    
    // Create pages
    foreach ($pages as $title => $details) {
        // Check if page exists
        $existing_page = new WP_Query(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'title' => $title,
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'fields' => 'ids'
        ));
        
        if (!$existing_page->posts) {
            // Generate dynamic content for special pages
            $content = $details['content'];
            if ($content === 'dynamic_privacy_policy') {
                $content = flexpress_generate_privacy_policy_content();
            } elseif ($content === 'dynamic_terms_conditions') {
                $content = flexpress_generate_terms_conditions_content();
            } elseif ($content === 'dynamic_customer_terms') {
                $content = flexpress_generate_customer_terms_content();
            } elseif ($content === 'dynamic_2257_compliance') {
                $content = flexpress_generate_2257_compliance_content();
            } elseif ($content === 'dynamic_anti_slavery') {
                $content = flexpress_generate_anti_slavery_content();
            } elseif ($content === 'dynamic_content_removal') {
                $content = flexpress_generate_content_removal_content();
            }
            
            // Create page
            $page_id = wp_insert_post(array(
                'post_title'     => $title,
                'post_content'   => $content,
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            ));
            
            if ($page_id && !is_wp_error($page_id) && !empty($details['template'])) {
                // Set page template
                update_post_meta($page_id, '_wp_page_template', $details['template']);
            }
        }
    }
    
    // Set flag that pages have been created
    update_option('flexpress_pages_created', true);
    
    // Now create menus
    if (!get_option('flexpress_menus_created')) {
        flexpress_create_default_menus();
    }
}

// Run after theme activation
add_action('after_switch_theme', 'flexpress_create_required_pages');

// Also run once on init with a check for the option to make sure it only runs once
function flexpress_maybe_create_required_pages() {
    if (!get_option('flexpress_pages_created')) {
        flexpress_create_required_pages();
    }
}
add_action('init', 'flexpress_maybe_create_required_pages', 5); // Priority 5 to run before menu creation

/**
 * Create legal pages and add them to the Legal menu
 * This function creates all required legal pages with proper templates
 * and automatically organizes them in the Legal menu
 */
function flexpress_create_legal_pages_and_menu() {
    // Legal pages configuration
    $legal_pages = array(
        'Privacy Policy' => array(
            'content' => 'dynamic_privacy_policy',
            'template' => 'page-templates/privacy.php',
            'menu_title' => 'Privacy Policy'
        ),
        'Customer Terms & Conditions' => array(
            'content' => 'dynamic_customer_terms',
            'template' => 'page-templates/terms.php',
            'menu_title' => 'Customer Terms & Conditions'
        ),
        '2257 Compliance' => array(
            'content' => 'dynamic_2257_compliance',
            'template' => 'page-templates/2257-compliance.php',
            'menu_title' => '2257 Compliance'
        ),
        'Anti-Slavery and Human Trafficking Policy' => array(
            'content' => 'dynamic_anti_slavery',
            'template' => 'page-templates/anti-slavery.php',
            'menu_title' => 'Anti-Slavery and Human Trafficking Policy'
        ),
        'Content Removal' => array(
            'content' => 'dynamic_content_removal',
            'template' => 'page-templates/content-removal.php',
            'menu_title' => 'Content Removal'
        )
    );

    $created_pages = array();

    // Create legal pages
    foreach ($legal_pages as $title => $details) {
        // Check if page exists
        $existing_page = new WP_Query(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'title' => $title,
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'fields' => 'ids'
        ));

        if (!$existing_page->posts) {
            // Generate dynamic content for special pages
            $content = $details['content'];
            if ($content === 'dynamic_privacy_policy') {
                $content = flexpress_generate_privacy_policy_content();
            } elseif ($content === 'dynamic_terms_conditions') {
                $content = flexpress_generate_terms_conditions_content();
            } elseif ($content === 'dynamic_customer_terms') {
                $content = flexpress_generate_customer_terms_content();
            } elseif ($content === 'dynamic_2257_compliance') {
                $content = flexpress_generate_2257_compliance_content();
            } elseif ($content === 'dynamic_anti_slavery') {
                $content = flexpress_generate_anti_slavery_content();
            } elseif ($content === 'dynamic_content_removal') {
                $content = flexpress_generate_content_removal_content();
            }
            
            // Create page
            $page_id = wp_insert_post(array(
                'post_title'     => $title,
                'post_content'   => $content,
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            ));

            if ($page_id && !is_wp_error($page_id)) {
                // Set page template
                update_post_meta($page_id, '_wp_page_template', $details['template']);
                $created_pages[$title] = array(
                    'id' => $page_id,
                    'menu_title' => $details['menu_title']
                );
            }
        } else {
            // Page exists, add to our array for menu assignment
            $created_pages[$title] = array(
                'id' => $existing_page->posts[0],
                'menu_title' => $details['menu_title']
            );
        }
    }

    // Create or get the Legal menu
    $legal_menu_name = 'Legal';
    $legal_menu = wp_get_nav_menu_object($legal_menu_name);
    
    if (!$legal_menu) {
        $legal_menu_id = wp_create_nav_menu($legal_menu_name);
        if (is_wp_error($legal_menu_id)) {
            error_log('Failed to create Legal menu: ' . $legal_menu_id->get_error_message());
            return false;
        }
    } else {
        $legal_menu_id = $legal_menu->term_id;
        
        // Clear existing menu items
        $menu_items = wp_get_nav_menu_items($legal_menu_id);
        if ($menu_items) {
            foreach ($menu_items as $menu_item) {
                wp_delete_post($menu_item->ID, true);
            }
        }
    }

    // Add pages to Legal menu in specified order
    $menu_order = 1;
    foreach ($created_pages as $page_title => $page_data) {
        $menu_item_id = wp_update_nav_menu_item($legal_menu_id, 0, array(
            'menu-item-title' => $page_data['menu_title'],
            'menu-item-object' => 'page',
            'menu-item-object-id' => $page_data['id'],
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
            'menu-item-position' => $menu_order
        ));

        if (is_wp_error($menu_item_id)) {
            error_log('Failed to add ' . $page_title . ' to Legal menu: ' . $menu_item_id->get_error_message());
        }
        
        $menu_order++;
    }

    // Assign Legal menu to footer-legal-menu location
    $locations = get_theme_mod('nav_menu_locations', array());
    $locations['footer-legal-menu'] = $legal_menu_id;
    set_theme_mod('nav_menu_locations', $locations);

    // Also assign to legal-menu location for backwards compatibility
    $locations['legal-menu'] = $legal_menu_id;
    set_theme_mod('nav_menu_locations', $locations);

    return $created_pages;
}

/**
 * Add admin action to manually create pages and menus
 */
function flexpress_register_admin_page_menu_actions() {
    // Only register for admin users
    if (!current_user_can('manage_options')) {
        return;
    }
    
    add_action('admin_post_create_default_pages_menus', 'flexpress_handle_create_pages_menus_action');
    add_action('admin_post_create_legal_pages_menu', 'flexpress_handle_create_legal_pages_menu_action');
    add_action('admin_post_create_main_footer_pages_menu', 'flexpress_handle_create_main_footer_pages_menu_action');
    add_action('admin_post_create_support_pages_menu', 'flexpress_handle_create_support_pages_menu_action');
}
add_action('admin_init', 'flexpress_register_admin_page_menu_actions');

/**
 * Handle admin action to create pages and menus
 */
function flexpress_handle_create_pages_menus_action() {
    // Verify nonce and user capabilities
    if (!isset($_POST['create_pages_menus_nonce']) || !wp_verify_nonce($_POST['create_pages_menus_nonce'], 'create_pages_menus_nonce') || !current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'flexpress'));
    }
    
    // Delete existing flags to force recreation
    delete_option('flexpress_pages_created');
    delete_option('flexpress_menus_created');
    
    // Create pages and menus
    flexpress_create_required_pages();
    
    // Redirect back with message
    wp_redirect(add_query_arg(array(
        'page' => 'flexpress-settings',
        'tab' => 'general',
        'created' => '1'
    ), admin_url('admin.php')));
    exit;
}

/**
 * Handle admin action to create legal pages and menu
 */
function flexpress_handle_create_legal_pages_menu_action() {
    // Verify nonce and user capabilities
    if (!isset($_POST['create_legal_pages_nonce']) || !wp_verify_nonce($_POST['create_legal_pages_nonce'], 'create_legal_pages_nonce') || !current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'flexpress'));
    }
    
    // Create legal pages and menu
    $created_pages = flexpress_create_legal_pages_and_menu();
    
    // Redirect back with message
    $message = $created_pages ? 'legal_created' : 'legal_failed';
    wp_redirect(add_query_arg(array(
        'page' => 'flexpress-settings',
        'tab' => 'general',
        $message => '1'
    ), admin_url('admin.php')));
    exit;
}

/**
 * Handle admin action to create main footer pages and menu
 */
function flexpress_handle_create_main_footer_pages_menu_action() {
    // Verify nonce and user capabilities
    if (!isset($_POST['create_main_footer_pages_nonce']) || !wp_verify_nonce($_POST['create_main_footer_pages_nonce'], 'create_main_footer_pages_nonce') || !current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'flexpress'));
    }
    
    // Create main footer pages and menu
    $created_pages = flexpress_create_main_footer_pages_and_menu();
    
    // Redirect back with message
    $message = $created_pages ? 'main_footer_created' : 'main_footer_failed';
    wp_redirect(add_query_arg(array(
        'page' => 'flexpress-settings',
        'tab' => 'general',
        $message => '1'
    ), admin_url('admin.php')));
    exit;
}

/**
 * Handle admin action to create support pages and menu
 */
function flexpress_handle_create_support_pages_menu_action() {
    // Verify nonce and user capabilities
    if (!isset($_POST['create_support_pages_nonce']) || !wp_verify_nonce($_POST['create_support_pages_nonce'], 'create_support_pages_nonce') || !current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'flexpress'));
    }
    
    // Create support pages and menu
    $created_pages = flexpress_create_support_pages_and_menu();
    
    // Redirect back with message
    $message = $created_pages ? 'support_created' : 'support_failed';
    wp_redirect(add_query_arg(array(
        'page' => 'flexpress-settings',
        'tab' => 'general',
        $message => '1'
    ), admin_url('admin.php')));
    exit;
}

// Pages & Menus section moved to FlexPress_General_Settings class

/**
 * Synchronize WordPress post date with ACF release_date field
 * 
 * @param int $post_id The post ID
 * @param WP_Post $post The post object
 * @param bool $update Whether this is an update or a new post
 */
function flexpress_sync_post_date_with_release_date($post_id, $post, $update) {
    // Skip if this is an autosave or not an episode post type
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if ($post->post_type !== 'episode') {
        return;
    }
    
    // Skip revisions
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    // Log ACF fields for debugging
    if (!empty($_POST['acf'])) {
        // Log the ACF data
        error_log('ACF POST DATA for Episode ID ' . $post_id . ': ' . print_r($_POST['acf'], true));
    }
    
    // Get the WordPress post date
    $wp_post_date = $post->post_date;
    
    // Get the ACF release date
    $acf_release_date = get_field('release_date', $post_id);
    
    // If this is a form submission from ACF and release_date has been set, update the post date
    if (!empty($_POST['acf'])) {
        // Check for any release_date field in the $_POST['acf'] array
        $new_release_date = null;
        foreach ($_POST['acf'] as $key => $value) {
            if (strpos($key, 'release_date') !== false || $key === 'field_release_date') {
                $new_release_date = $value;
                break;
            }
        }
        
        if ($new_release_date && !empty($new_release_date) && $new_release_date != $wp_post_date) {
            // Update the post date
            wp_update_post(array(
                'ID' => $post_id,
                'post_date' => $new_release_date,
                'post_date_gmt' => get_gmt_from_date($new_release_date)
            ));
        }
    } 
    // If ACF release date is empty, update it with the WordPress post date
    elseif (empty($acf_release_date)) {
        update_field('release_date', $wp_post_date, $post_id);
    }
    // If we're updating from the editor and the post dates differ, update ACF release date
    elseif ($update && !empty($_POST['action']) && $_POST['action'] === 'editpost') {
        update_field('release_date', $wp_post_date, $post_id);
    }
}
add_action('save_post', 'flexpress_sync_post_date_with_release_date', 20, 3);

/**
 * Update WordPress post date when ACF release_date field is updated
 * 
 * @param mixed $value The field value
 * @param int $post_id The post ID
 * @param array $field The field array
 * @return mixed The field value
 */
function flexpress_update_post_date_from_acf($value, $post_id, $field) {
    // Only proceed if this is the release_date field and we have a post ID
    if ($field['name'] === 'release_date' && $post_id && !empty($value)) {
        // Get the post
        $post = get_post($post_id);
        
        // Only proceed if this is an episode post type
        if ($post && $post->post_type === 'episode') {
            // Format the date properly for WordPress
            $formatted_date = date('Y-m-d H:i:s', strtotime($value));
            
            // Only update if the dates differ
            if ($formatted_date != $post->post_date) {
                // Update the post date
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_date' => $formatted_date,
                    'post_date_gmt' => get_gmt_from_date($formatted_date)
                ));
            }
        }
    }
    
    return $value;
}
add_filter('acf/update_value/name=release_date', 'flexpress_update_post_date_from_acf', 10, 3);

/**
 * Start output buffering to prevent headers already sent errors
 */
function flexpress_start_output_buffering() {
    ob_start();
}
add_action('init', 'flexpress_start_output_buffering', 0);

/**
 * End output buffering
 */
function flexpress_end_output_buffering() {
    if (ob_get_level()) {
        ob_end_flush();
    }
}
add_action('shutdown', 'flexpress_end_output_buffering');

/**
 * AJAX handler for processing registration and payment
 */
add_action('wp_ajax_nopriv_flexpress_process_registration_and_payment', 'flexpress_process_registration_and_payment');
add_action('wp_ajax_flexpress_process_registration_and_payment', 'flexpress_process_registration_and_payment');

/**
 * AJAX handler for processing renewal and payment
 */
add_action('wp_ajax_flexpress_process_renewal_and_payment', 'flexpress_process_renewal_and_payment');

/**
 * Handle Verotel payment return
 */
add_action('wp_ajax_nopriv_verotel_payment_return', 'flexpress_handle_verotel_payment_return');
add_action('wp_ajax_verotel_payment_return', 'flexpress_handle_verotel_payment_return');

function flexpress_handle_verotel_payment_return() {
    // Log all parameters for debugging
    error_log('FlexPress: Verotel payment return handler called with parameters: ' . json_encode($_GET));
    
    // Get parameters from URL
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $plan = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : '';
    
    // Validate user ID
    if (!$user_id || !get_userdata($user_id)) {
        error_log('FlexPress: Invalid user ID in payment return: ' . $user_id);
        wp_redirect(home_url('/join?error=invalid_user'));
        exit;
    }
    
    error_log('FlexPress: Processing payment return for user ID: ' . $user_id . ', plan: ' . $plan);
    
    // Log the user in
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    // Update membership status to active (assuming successful payment)
    update_user_meta($user_id, 'membership_status', 'active');
    update_user_meta($user_id, 'subscription_plan', $plan);
    update_user_meta($user_id, 'subscription_type', 'recurring');
    update_user_meta($user_id, 'subscription_start_date', current_time('mysql'));
    update_user_meta($user_id, 'payment_pending', false);
    
    // Placeholder Verotel data will be updated by webhook when payment is processed
    if (!get_user_meta($user_id, 'verotel_transaction_id', true)) {
        update_user_meta($user_id, 'verotel_transaction_id', 'tx_' . $user_id . '_' . time());
    }
    
    // Calculate next rebill date based on plan
    $pricing_plans = flexpress_get_pricing_plans(true);
    if (isset($pricing_plans[$plan])) {
        $plan_data = $pricing_plans[$plan];
        $duration = intval($plan_data['duration']);
        $duration_unit = $plan_data['duration_unit'];
        
        $next_rebill = '';
        switch ($duration_unit) {
            case 'days':
                $next_rebill = date('Y-m-d H:i:s', strtotime('+' . $duration . ' days'));
                break;
            case 'months':
                $next_rebill = date('Y-m-d H:i:s', strtotime('+' . $duration . ' months'));
                break;
            case 'years':
                $next_rebill = date('Y-m-d H:i:s', strtotime('+' . $duration . ' years'));
                break;
        }
        
        if ($next_rebill) {
            update_user_meta($user_id, 'next_rebill_date', $next_rebill);
        }
    }
    
    // Log successful payment return
    error_log(sprintf(
        'FlexPress: User %d returned from successful Verotel payment for plan %s',
        $user_id,
        $plan
    ));
    
    // Redirect to my-account with success message
    wp_redirect(home_url('/my-account?payment=success&plan=' . urlencode($plan)));
    exit;
}

function flexpress_process_registration_and_payment() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'flexpress_join_form')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }
    
    // Get form data
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $selected_plan = sanitize_text_field($_POST['selected_plan']);
    $applied_promo_code = sanitize_text_field($_POST['applied_promo_code'] ?? '');
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($selected_plan)) {
        wp_send_json_error(array('message' => 'Please fill in all required fields.'));
        return;
    }
    
    // Validate email
    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Please enter a valid email address.'));
        return;
    }
    
    // Check if email already exists
    if (email_exists($email)) {
        wp_send_json_error(array('message' => 'This email address is already registered.'));
        return;
    }
    
    // Get the selected pricing plan (include promo code to unlock promo-only plans)
    $pricing_plans = flexpress_get_pricing_plans(true, $applied_promo_code);
    if (!isset($pricing_plans[$selected_plan])) {
        wp_send_json_error(array('message' => 'Invalid pricing plan selected.'));
        return;
    }
    
    $plan = $pricing_plans[$selected_plan];
    
    // Create user account
    $user_id = wp_create_user($email, $password, $email);
    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => $user_id->get_error_message()));
        return;
    }
    
    // Update user meta
    update_user_meta($user_id, 'first_name', $first_name);
    update_user_meta($user_id, 'last_name', $last_name);
    update_user_meta($user_id, 'display_name', $first_name . ' ' . $last_name);
    
    // Set custom display name for FlexPress
    $default_display_name = trim($first_name . ' ' . $last_name);
    if (!empty($default_display_name)) {
        update_user_meta($user_id, 'flexpress_display_name', $default_display_name);
    }
    
    update_user_meta($user_id, 'selected_pricing_plan', $selected_plan);
    
    // Track promo code usage if applied
    if (!empty($applied_promo_code)) {
        update_user_meta($user_id, 'applied_promo_code', $applied_promo_code);
        flexpress_track_promo_usage($applied_promo_code, $user_id, $selected_plan, 'registration_' . $user_id);
    }
    
    // Set user role
    $user = new WP_User($user_id);
    $user->set_role('subscriber');
    
    // Check if Verotel is configured
    $verotel_settings = get_option('flexpress_verotel_settings', array());
    $verotel_configured = !empty($verotel_settings['verotel_shop_id']) && 
                         !empty($verotel_settings['verotel_signature_key']) &&
                         !empty($verotel_settings['verotel_merchant_id']);
    
    if (!$verotel_configured) {
        // For testing: simulate successful payment and log the user in
        update_user_meta($user_id, 'membership_status', 'active');
        update_user_meta($user_id, 'subscription_plan', $selected_plan);
        update_user_meta($user_id, 'subscription_start', current_time('mysql'));
        
        // Log the user in
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        wp_send_json_success(array(
            'message' => 'Registration successful! (Verotel not configured - test mode)',
            'payment_url' => home_url('/my-account?payment=test_success&plan=' . $selected_plan),
            'user_id' => $user_id,
            'plan_name' => $plan['name'],
            'amount' => $plan['price'],
            'test_mode' => true
        ));
        return;
    }
    
    // Initialize Verotel
    try {
        $verotel = new FlexPress_Verotel();
        
        // Prepare payment arguments
        $payment_args = array(
            'successURL' => home_url('/wp-admin/admin-ajax.php?action=verotel_payment_return&user_id=' . $user_id . '&plan=' . $selected_plan),
            'declineURL' => home_url('/join?payment=cancelled'),
            'ipnUrl' => home_url('/wp-admin/admin-ajax.php?action=verotel_webhook'),
            'email' => $email, // Pre-fill email in Verotel checkout
            'custom1' => $user_id, // Pass User ID for webhook identification
            'custom2' => $selected_plan, // Pass plan ID
            'custom3' => $first_name . ' ' . $last_name, // Pass full name
            'saleID' => $user_id, // Use user ID as saleID for easier mapping
            'productDescription' => $plan['name'] . ' - ' . $plan['description']
        );
        
        // Add trial information if applicable
        if (!empty($plan['trial_enabled'])) {
            $payment_args['trial_amount'] = $plan['trial_price'];
            $payment_args['trial_period'] = 'P' . $plan['trial_duration'] . 'D'; // ISO 8601 duration format
        }
        
        // Log payment arguments for debugging
        error_log('FlexPress: Creating Verotel payment URL with args: ' . json_encode($payment_args));
        
        // Debug: Check if Verotel client is initialized
        $reflection = new ReflectionObject($verotel);
        $client_property = $reflection->getProperty('client');
        $client_property->setAccessible(true);
        $client = $client_property->getValue($verotel);
        error_log('FlexPress: Verotel client initialized: ' . ($client ? 'YES' : 'NO'));
        
        // Get Verotel subscription URL for recurring membership
        $payment_url = $verotel->get_subscription_url(
            $plan['price'],
            'USD', // Assuming USD for now, could be made dynamic
            $plan['name'],
            $payment_args
        );
        
        // Log the generated URL for debugging (without sensitive data)
        if ($payment_url) {
            error_log('FlexPress: Generated Verotel payment URL successfully');
        } else {
            error_log('FlexPress: Failed to generate Verotel payment URL - checking client status and recent errors');
        }
        
        if (empty($payment_url)) {
            // If Verotel URL generation fails, fall back to test mode
            update_user_meta($user_id, 'membership_status', 'active');
            update_user_meta($user_id, 'subscription_plan', $selected_plan);
            update_user_meta($user_id, 'subscription_start', current_time('mysql'));
            
            // Log the user in
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            wp_send_json_success(array(
                'message' => 'Registration successful! (Verotel URL generation failed - test mode)',
                'payment_url' => home_url('/my-account?payment=verotel_fallback&plan=' . $selected_plan),
                'user_id' => $user_id,
                'plan_name' => $plan['name'],
                'amount' => $plan['price'],
                'test_mode' => true
            ));
            return;
        }
        
        // Store temporary user session for verification after payment
        update_user_meta($user_id, 'payment_pending', true);
        update_user_meta($user_id, 'payment_plan', $selected_plan);
        update_user_meta($user_id, 'payment_amount', $plan['price']);
        update_user_meta($user_id, 'registration_timestamp', current_time('timestamp'));
        
        wp_send_json_success(array(
            'message' => 'Redirecting to payment processing...',
            'payment_url' => $payment_url,
            'user_id' => $user_id,
            'plan_name' => $plan['name'],
            'amount' => $plan['price'],
            'test_mode' => false
        ));
        
    } catch (Exception $e) {
        // Log the error
        error_log('Verotel Error: ' . $e->getMessage());
        
        // Fall back to test mode
        update_user_meta($user_id, 'membership_status', 'active');
        update_user_meta($user_id, 'subscription_plan', $selected_plan);
        update_user_meta($user_id, 'subscription_start', current_time('mysql'));
        
        // Log the user in
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        wp_send_json_success(array(
            'message' => 'Registration successful! (Verotel error - test mode)',
            'payment_url' => home_url('/my-account?payment=error_fallback&plan=' . $selected_plan),
            'user_id' => $user_id,
            'plan_name' => $plan['name'],
            'amount' => $plan['price'],
            'test_mode' => true,
            'error' => $e->getMessage()
        ));
    }
}

/**
 * Process renewal and payment for existing users
 */
function flexpress_process_renewal_and_payment() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'flexpress_join_form')) {
        wp_send_json_error(array('message' => 'Security verification failed. Please try again.'));
        return;
    }
    
    // Ensure user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in to renew your membership.'));
        return;
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Validate membership status (should be expired, cancelled, or none)
    $membership_status = flexpress_get_membership_status($user_id);
    if ($membership_status === 'active') {
        wp_send_json_error(array('message' => 'You already have an active membership.'));
        return;
    }
    
    if (!in_array($membership_status, ['expired', 'cancelled', 'none', ''])) {
        wp_send_json_error(array('message' => 'Invalid membership status for renewal.'));
        return;
    }
    
    // Get form data
    $selected_plan = isset($_POST['selected_plan']) ? sanitize_text_field($_POST['selected_plan']) : '';
    $applied_promo_code = isset($_POST['applied_promo_code']) ? sanitize_text_field($_POST['applied_promo_code']) : '';
    
    if (empty($selected_plan)) {
        wp_send_json_error(array('message' => 'Please select a pricing plan.'));
        return;
    }
    
    // Get the selected pricing plan (include promo code to unlock promo-only plans)
    $pricing_plans = flexpress_get_pricing_plans(true, $applied_promo_code);
    if (!isset($pricing_plans[$selected_plan])) {
        wp_send_json_error(array('message' => 'Invalid pricing plan selected.'));
        return;
    }
    
    $plan = $pricing_plans[$selected_plan];
    
    // Update user meta for the selected plan
    update_user_meta($user_id, 'selected_pricing_plan', $selected_plan);
    
    // Track promo code usage if applied
    if (!empty($applied_promo_code)) {
        update_user_meta($user_id, 'applied_promo_code', $applied_promo_code);
        flexpress_track_promo_usage($applied_promo_code, $user_id, $selected_plan, 'renewal_' . $user_id);
    }
    
    // Check if Verotel is configured
    $verotel_settings = get_option('flexpress_verotel_settings', array());
    $verotel_configured = !empty($verotel_settings['verotel_shop_id']) && 
                         !empty($verotel_settings['verotel_signature_key']) &&
                         !empty($verotel_settings['verotel_merchant_id']);
    
    if (!$verotel_configured) {
        // For testing: simulate successful payment 
        update_user_meta($user_id, 'membership_status', 'active');
        update_user_meta($user_id, 'subscription_plan', $selected_plan);
        update_user_meta($user_id, 'subscription_start', current_time('mysql'));
        
        wp_send_json_success(array(
            'message' => 'Renewal successful! (Verotel not configured - test mode)',
            'payment_url' => home_url('/dashboard?payment=renewal_success&plan=' . $selected_plan),
            'user_id' => $user_id,
            'plan_name' => $plan['name'],
            'amount' => $plan['price'],
            'test_mode' => true
        ));
        return;
    }
    
    // Use renewal URL generation function
    $renewal_result = flexpress_generate_renewal_url($user_id, $selected_plan);
    
    if (is_wp_error($renewal_result)) {
        wp_send_json_error(array('message' => $renewal_result->get_error_message()));
        return;
    }
    
    // Log renewal attempt
    if (class_exists('FlexPress_Activity_Logger')) {
        FlexPress_Activity_Logger::log_activity(
            $user_id,
            'membership_renewal_initiated',
            'User initiated membership renewal via join page',
            array(
                'plan_id' => $selected_plan,
                'plan_name' => $plan['name'],
                'amount' => $plan['price'],
                'currency' => $plan['currency'],
                'previous_status' => $membership_status,
                'promo_code' => $applied_promo_code
            )
        );
    }
    
    wp_send_json_success(array(
        'message' => 'Redirecting to payment...',
        'payment_url' => $renewal_result['payment_url'],
        'user_id' => $user_id,
        'plan_name' => $plan['name'],
        'amount' => $plan['price']
    ));
}

/**
 * Ensure pricing plans are complete with both recurring and one-time options
 */
function flexpress_ensure_pricing_plans_complete() {
    $existing_plans = get_option('flexpress_pricing_plans', array());
    
    // Check if we have any one-time plans
    $has_onetime_plans = false;
    foreach ($existing_plans as $plan) {
        if (isset($plan['plan_type']) && $plan['plan_type'] === 'one_time') {
            $has_onetime_plans = true;
            break;
        }
    }
    
    // If no one-time plans exist, force create the complete set
    if (!$has_onetime_plans) {
        if (function_exists('flexpress_get_default_pricing_plans')) {
            $default_plans = flexpress_get_default_pricing_plans();
            update_option('flexpress_pricing_plans', $default_plans);
        }
    }
}

/**
 * Create join page if it doesn't exist
 */
function flexpress_create_join_page() {
    // Check if join page already exists
    $join_page = get_page_by_path('join');
    
    if (!$join_page) {
        $page_data = array(
            'post_title'     => 'Join',
            'post_content'   => '[Join page content will be generated by the template]',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_name'      => 'join'
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            // Set the page template
            update_post_meta($page_id, '_wp_page_template', 'page-templates/join.php');
        }
    }
}

/**
 * AJAX handler for creating PPV episode purchase
 */
add_action('wp_ajax_flexpress_create_ppv_purchase', 'flexpress_create_ppv_purchase');
add_action('wp_ajax_nopriv_flexpress_create_ppv_purchase', 'flexpress_create_ppv_purchase');

function flexpress_create_ppv_purchase() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'flexpress_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'flexpress')));
        return;
    }
    
    // Check if user is logged in - REQUIRED for PPV purchases
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to purchase episodes', 'flexpress')));
        return;
    }
    
    // Get and validate parameters
    $episode_id = isset($_POST['episode_id']) ? intval($_POST['episode_id']) : 0;
    $final_price = isset($_POST['final_price']) ? floatval($_POST['final_price']) : 0;
    $base_price = isset($_POST['base_price']) ? floatval($_POST['base_price']) : 0;
    $member_discount = isset($_POST['member_discount']) ? floatval($_POST['member_discount']) : 0;
    
    if (!$episode_id || !$final_price) {
        wp_send_json_error(array('message' => __('Invalid episode or price data', 'flexpress')));
        return;
    }
    
    // Verify episode exists and has a price
    $episode = get_post($episode_id);
    if (!$episode || $episode->post_type !== 'episode') {
        wp_send_json_error(array('message' => __('Episode not found', 'flexpress')));
        return;
    }
    
    $episode_price = get_field('episode_price', $episode_id);
    if (!$episode_price || floatval($episode_price) !== $base_price) {
        wp_send_json_error(array('message' => __('Price mismatch. Please refresh and try again.', 'flexpress')));
        return;
    }
    
    // Check if user already owns this episode
    $user_id = get_current_user_id();
    if ($user_id) {
        $already_purchased = get_user_meta($user_id, 'purchased_episode_' . $episode_id, true);
        if ($already_purchased) {
            wp_send_json_error(array('message' => __('You already own this episode', 'flexpress')));
            return;
        }
        
        // Validate member discount eligibility (active or cancelled members only)
        if ($member_discount > 0) {
            $membership_status = get_user_meta($user_id, 'membership_status', true);
            $is_active_member = in_array($membership_status, ['active', 'cancelled']);
            
            if (!$is_active_member) {
                // Recalculate final price without discount for non-active members
                $final_price = $base_price;
                error_log('FlexPress PPV: User ' . $user_id . ' not eligible for member discount. Status: ' . $membership_status);
            }
        }
    }
    
    // Create unique transaction reference
    $transaction_ref = 'ppv_' . $episode_id . '_' . ($user_id ?: 'guest') . '_' . time();
    
    // Initialize Verotel for one-time payment
    try {
        $verotel = new FlexPress_Verotel();
        
        // Prepare payment arguments for one-time purchase
        $payment_args = array(
            'successURL' => home_url('/wp-admin/admin-ajax.php?action=flexpress_ppv_payment_return&episode_id=' . $episode_id . '&user_id=' . $user_id . '&ref=' . $transaction_ref),
            'declineURL' => get_permalink($episode_id) . '?payment=cancelled',
            'ipnUrl' => home_url('/wp-admin/admin-ajax.php?action=flexpress_ppv_webhook'),
            'referenceID' => $transaction_ref, // Unique reference for this purchase
            'custom1' => $episode_id, // Episode ID
            'custom2' => $user_id, // User ID  
            'custom3' => $transaction_ref, // Transaction reference
            'productDescription' => 'Episode: ' . get_the_title($episode_id)
        );
        
        // Add user email if logged in
        if ($user_id) {
            $user = get_userdata($user_id);
            if ($user && $user->user_email) {
                $payment_args['email'] = $user->user_email;
            }
        }
        
        // Log payment creation for debugging
        error_log('FlexPress PPV: Creating payment for episode ' . $episode_id . ', user ' . $user_id . ', price $' . $final_price);
        
        // Get Verotel payment URL for one-time purchase
        $payment_url = $verotel->get_purchase_url(
            $final_price,
            'USD', // Currency - could be made configurable
            'Episode: ' . get_the_title($episode_id),
            $payment_args
        );
        
        if (!$payment_url) {
            wp_send_json_error(array('message' => __('Unable to create payment. Please try again.', 'flexpress')));
            return;
        }
        
        // Store transaction reference for validation
        if ($user_id) {
            update_user_meta($user_id, 'pending_ppv_' . $transaction_ref, array(
                'episode_id' => $episode_id,
                'price' => $final_price,
                'created' => current_time('mysql')
            ));
        } else {
            // Store in session for guest users
            if (!session_id()) {
                session_start();
            }
            $_SESSION['pending_ppv_' . $transaction_ref] = array(
                'episode_id' => $episode_id,
                'price' => $final_price,
                'created' => current_time('mysql')
            );
        }
        
        wp_send_json_success(array(
            'payment_url' => $payment_url,
            'episode_title' => get_the_title($episode_id),
            'final_price' => $final_price
        ));
        
    } catch (Exception $e) {
        error_log('FlexPress PPV Error: ' . $e->getMessage());
        wp_send_json_error(array('message' => __('Payment system error. Please try again.', 'flexpress')));
    }
}

/**
 * Handle PPV payment return from Verotel
 */
add_action('wp_ajax_nopriv_flexpress_ppv_payment_return', 'flexpress_handle_ppv_payment_return');
add_action('wp_ajax_flexpress_ppv_payment_return', 'flexpress_handle_ppv_payment_return');

function flexpress_handle_ppv_payment_return() {
    // Get parameters from URL
    $episode_id = isset($_GET['episode_id']) ? intval($_GET['episode_id']) : 0;
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $transaction_ref = isset($_GET['ref']) ? sanitize_text_field($_GET['ref']) : '';
    
    error_log('FlexPress PPV: Payment return for episode ' . $episode_id . ', user ' . $user_id . ', ref ' . $transaction_ref);
    
    // Validate parameters
    if (!$episode_id || !$transaction_ref) {
        wp_redirect(home_url('/episodes?error=invalid_return'));
        exit;
    }
    
    // Validate episode exists
    $episode = get_post($episode_id);
    if (!$episode || $episode->post_type !== 'episode') {
        wp_redirect(home_url('/episodes?error=episode_not_found'));
        exit;
    }
    
    // Grant access to episode
    if ($user_id && get_userdata($user_id)) {
        // Mark episode as purchased
        update_user_meta($user_id, 'purchased_episode_' . $episode_id, current_time('mysql'));
        
        // Add to purchased episodes list
        $purchased_episodes = get_user_meta($user_id, 'purchased_episodes', true);
        if (!is_array($purchased_episodes)) {
            $purchased_episodes = array();
        }
        if (!in_array($episode_id, $purchased_episodes)) {
            $purchased_episodes[] = $episode_id;
            update_user_meta($user_id, 'purchased_episodes', $purchased_episodes);
        }
        
        // Log PPV purchase activity
        if (class_exists('FlexPress_Activity_Logger')) {
            $episode_price = get_field('episode_price', $episode_id);
            $access_type = get_field('access_type', $episode_id);
            $member_discount = get_field('member_discount', $episode_id);
            
            // Check if user got member discount
            $membership_status = get_user_meta($user_id, 'membership_status', true);
            $is_active_member = in_array($membership_status, ['active', 'cancelled']);
            $discount_applied = ($access_type === 'mixed' && $is_active_member && $member_discount > 0);
            $final_price = $discount_applied ? ($episode_price * (1 - ($member_discount / 100))) : $episode_price;
            
            FlexPress_Activity_Logger::log_ppv_purchase(
                $user_id,
                $episode_id,
                array(
                    'access_type' => $access_type,
                    'original_price' => $episode_price,
                    'final_price' => $final_price,
                    'member_discount' => $member_discount,
                    'discount_applied' => $discount_applied,
                    'membership_status' => $membership_status,
                    'transaction_ref' => $transaction_ref,
                    'currency' => 'USD'
                ),
                'return'
            );
        }
        
        // Clean up pending transaction
        delete_user_meta($user_id, 'pending_ppv_' . $transaction_ref);
        
        // Log the user in if not already logged in
        if (!is_user_logged_in()) {
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
        }
        
        error_log('FlexPress PPV: Granted access to episode ' . $episode_id . ' for user ' . $user_id);
    }
    
    // Redirect to episode with success message
    wp_redirect(get_permalink($episode_id) . '?ppv=unlocked');
    exit;
}

/**
 * Handle PPV webhook from Verotel
 */
add_action('wp_ajax_nopriv_flexpress_ppv_webhook', 'flexpress_handle_ppv_webhook');
add_action('wp_ajax_flexpress_ppv_webhook', 'flexpress_handle_ppv_webhook');

function flexpress_handle_ppv_webhook() {
    // Log webhook data
    error_log('FlexPress PPV Webhook: ' . json_encode($_REQUEST));
    
    // Get webhook data
    $webhook_data = array_merge($_GET, $_POST);
    unset($webhook_data['action']);
    
    // Extract custom data
    $episode_id = isset($webhook_data['custom1']) ? intval($webhook_data['custom1']) : 0;
    $user_id = isset($webhook_data['custom2']) ? intval($webhook_data['custom2']) : 0;
    $transaction_ref = isset($webhook_data['custom3']) ? sanitize_text_field($webhook_data['custom3']) : '';
    
    if (!$episode_id || !$transaction_ref) {
        error_log('FlexPress PPV Webhook: Missing required data');
        wp_send_json_error('Missing required data');
        return;
    }
    
    // Process based on event type
    $event_type = isset($webhook_data['event']) ? sanitize_text_field($webhook_data['event']) : 'purchase';
    
    switch ($event_type) {
        case 'initial':
        case 'purchase':
        default:
            // Grant access to episode
            if ($user_id && get_userdata($user_id)) {
                update_user_meta($user_id, 'purchased_episode_' . $episode_id, current_time('mysql'));
                
                // Add to purchased episodes list
                $purchased_episodes = get_user_meta($user_id, 'purchased_episodes', true);
                if (!is_array($purchased_episodes)) {
                    $purchased_episodes = array();
                }
                if (!in_array($episode_id, $purchased_episodes)) {
                    $purchased_episodes[] = $episode_id;
                    update_user_meta($user_id, 'purchased_episodes', $purchased_episodes);
                }
                
                // Store transaction details
                update_user_meta($user_id, 'ppv_transaction_' . $episode_id, array(
                    'transaction_id' => isset($webhook_data['transactionID']) ? $webhook_data['transactionID'] : '',
                    'amount' => isset($webhook_data['priceAmount']) ? $webhook_data['priceAmount'] : '',
                    'currency' => isset($webhook_data['priceCurrency']) ? $webhook_data['priceCurrency'] : 'USD',
                    'date' => current_time('mysql'),
                    'reference' => $transaction_ref
                ));
                
                // Log PPV purchase activity via webhook
                if (class_exists('FlexPress_Activity_Logger')) {
                    $episode_price = get_field('episode_price', $episode_id);
                    $access_type = get_field('access_type', $episode_id);
                    $member_discount = get_field('member_discount', $episode_id);
                    
                    // Check if user got member discount
                    $membership_status = get_user_meta($user_id, 'membership_status', true);
                    $is_active_member = in_array($membership_status, ['active', 'cancelled']);
                    $discount_applied = ($access_type === 'mixed' && $is_active_member && $member_discount > 0);
                    
                    $amount = isset($webhook_data['priceAmount']) ? floatval($webhook_data['priceAmount']) : $episode_price;
                    $currency = isset($webhook_data['priceCurrency']) ? $webhook_data['priceCurrency'] : 'USD';
                    
                    FlexPress_Activity_Logger::log_ppv_purchase(
                        $user_id,
                        $episode_id,
                        array(
                            'access_type' => $access_type,
                            'original_price' => $episode_price,
                            'paid_amount' => $amount,
                            'currency' => $currency,
                            'member_discount' => $member_discount,
                            'discount_applied' => $discount_applied,
                            'membership_status' => $membership_status,
                            'transaction_id' => isset($webhook_data['transactionID']) ? $webhook_data['transactionID'] : '',
                            'transaction_ref' => $transaction_ref,
                            'webhook_data' => $webhook_data
                        ),
                        'webhook'
                    );
                }
                
                error_log('FlexPress PPV Webhook: Processed purchase for episode ' . $episode_id . ', user ' . $user_id);
            }
            break;
    }
    
    wp_send_json_success('OK');
}

/**
 * Episode Access Control System
 * Comprehensive functions to handle different access models
 */

/**
 * Check if user has access to an episode based on access type
 * 
 * @param int $episode_id The episode ID
 * @param int $user_id The user ID (optional, defaults to current user)
 * @return array Access information array
 */
function flexpress_check_episode_access($episode_id = null, $user_id = null) {
    if (!$episode_id) {
        $episode_id = get_the_ID();
    }
    
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // Get episode fields
    $access_type = get_field('access_type', $episode_id) ?: 'membership';
    $price = get_field('episode_price', $episode_id);
    $member_discount = get_field('member_discount', $episode_id) ?: 0;
    
    // Initialize access info
    $access_info = array(
        'has_access' => false,
        'access_type' => $access_type,
        'price' => $price,
        'final_price' => $price,
        'discount' => 0,
        'is_member' => false,
        'is_purchased' => false,
        'show_purchase_button' => false,
        'purchase_reason' => '',
        'membership_notice' => ''
    );
    
    // Check if user is logged in
    $is_logged_in = $user_id > 0;
    
    // Check membership status
    $membership_status = '';
    $is_active_member = false;
    if ($is_logged_in) {
        $membership_status = get_user_meta($user_id, 'membership_status', true);
        $is_active_member = in_array($membership_status, ['active', 'cancelled']);
        $access_info['is_member'] = $is_active_member;
        
        // Check if user has purchased this episode
        $access_info['is_purchased'] = (bool) get_user_meta($user_id, 'purchased_episode_' . $episode_id, true);
    }
    
    // Handle different access types
    switch ($access_type) {
        case 'free':
            // Free for everyone
            $access_info['has_access'] = true;
            break;
            
        case 'ppv_only':
            // Only purchasable, no membership access
            if ($access_info['is_purchased']) {
                $access_info['has_access'] = true;
            } else {
                $access_info['show_purchase_button'] = true;
                $access_info['purchase_reason'] = 'This episode is available for individual purchase only.';
                if ($is_active_member) {
                    $access_info['membership_notice'] = 'This episode is not included in your membership and must be purchased separately.';
                }
            }
            break;
            
        case 'membership':
            // Members get free access, non-members can purchase
            if ($is_active_member || $access_info['is_purchased']) {
                $access_info['has_access'] = true;
            } else {
                $access_info['show_purchase_button'] = true;
                if ($is_logged_in) {
                    $access_info['purchase_reason'] = 'Join our membership for unlimited access to all episodes, or purchase this episode individually.';
                } else {
                    $access_info['purchase_reason'] = 'Login to access with membership, or purchase this episode individually.';
                }
            }
            break;
            
        case 'mixed':
            // Members get discount, everyone can purchase
            if ($access_info['is_purchased']) {
                $access_info['has_access'] = true;
            } else {
                $access_info['show_purchase_button'] = true;
                if ($is_active_member && $member_discount > 0) {
                    $access_info['discount'] = $member_discount;
                    $access_info['final_price'] = $price * (1 - ($member_discount / 100));
                    $access_info['purchase_reason'] = sprintf('Get %d%% member discount on this episode!', $member_discount);
                } else if ($is_logged_in && !$is_active_member && $member_discount > 0) {
                    $access_info['purchase_reason'] = sprintf('Active members save %d%% on this episode. Join our membership to unlock the discount!', $member_discount);
                } else {
                    $access_info['purchase_reason'] = 'Purchase this premium episode to watch instantly.';
                }
            }
            break;
    }
    
    return $access_info;
}

/**
 * Get episode access summary for display
 * 
 * @param int $episode_id The episode ID
 * @return string Human-readable access summary
 */
function flexpress_get_episode_access_summary($episode_id = null) {
    if (!$episode_id) {
        $episode_id = get_the_ID();
    }
    
    $access_type = get_field('access_type', $episode_id) ?: 'membership';
    $price = get_field('episode_price', $episode_id);
    $member_discount = get_field('member_discount', $episode_id) ?: 0;
    
    switch ($access_type) {
        case 'free':
            return 'Free';
            
        case 'ppv_only':
            return $price ? '$' . number_format($price, 2) . ' (PPV Only)' : 'PPV Only';
            
        case 'membership':
            if ($price) {
                return 'Included in Membership<br />' .
                    '$' . number_format($price, 2) . ' for Non-Members';
            } else {
                return 'Members Only';
            }
        case 'mixed':
            if ($price && $member_discount > 0) {
                $discounted_price = $price * (1 - ($member_discount / 100));
                return '$' . number_format($discounted_price, 2) . ' for Members • $' . number_format($price, 2) . ' for Non-Members';
            } else if ($price) {
                return '$' . number_format($price, 2) . ' for Everyone';
            } else {
                return 'Available for Purchase';
            }
            
        default:
            return 'Unknown';
    }
}

/**
 * Check if episode should show purchase button
 * 
 * @param int $episode_id The episode ID
 * @param int $user_id The user ID (optional)
 * @return bool Whether to show purchase button
 */
function flexpress_should_show_purchase_button($episode_id = null, $user_id = null) {
    $access_info = flexpress_check_episode_access($episode_id, $user_id);
    return $access_info['show_purchase_button'];
}

/**
 * Get video to display based on access level
 * 
 * @param int $episode_id The episode ID
 * @param int $user_id The user ID (optional)
 * @return string Video ID to display (full, trailer, or preview)
 */
function flexpress_get_episode_video_for_access($episode_id = null, $user_id = null) {
    if (!$episode_id) {
        $episode_id = get_the_ID();
    }
    
    $access_info = flexpress_check_episode_access($episode_id, $user_id);
    
    $full_video = get_field('full_video', $episode_id);
    $trailer_video = get_field('trailer_video', $episode_id);
    $preview_video = get_field('preview_video', $episode_id);
    
    if ($access_info['has_access'] && $full_video) {
        return $full_video;
    } elseif ($trailer_video) {
        return $trailer_video;
    } elseif ($preview_video) {
        return $preview_video;
    }
    
    return null;
}

/**
 * Enqueue admin scripts and styles
 */
function flexpress_admin_enqueue_scripts($hook) {
    // Only load on post edit screens for episodes
    if (($hook == 'post.php' || $hook == 'post-new.php') && 
        (get_post_type() == 'episode' || (isset($_GET['post_type']) && $_GET['post_type'] == 'episode'))) {
        
        wp_enqueue_script(
            'flexpress-admin-acf',
            get_template_directory_uri() . '/assets/js/admin-acf.js',
            array('jquery', 'acf-input'),
            '1.0.0',
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'flexpress_admin_enqueue_scripts');

/**
 * AJAX handler for membership renewal
 */
add_action('wp_ajax_renew_membership', 'flexpress_ajax_renew_membership');
add_action('wp_ajax_nopriv_renew_membership', 'flexpress_ajax_renew_membership');

/**
 * AJAX handler for subscription cancellation
 */
add_action('wp_ajax_cancel_subscription', 'flexpress_ajax_cancel_subscription');
add_action('wp_ajax_nopriv_cancel_subscription', 'flexpress_ajax_cancel_subscription');

function flexpress_ajax_cancel_subscription() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'flexpress_verotel_nonce')) {
        wp_send_json_error('Invalid security token. Please refresh the page and try again.');
        return;
    }
    
    // Get current user
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in.');
        return;
    }
    
    // Call the cancellation function
    $result = flexpress_cancel_verotel_subscription($user_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
        return;
    }
    
    if (isset($result['success']) && $result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error('Unknown error occurred during cancellation.');
    }
}

/**
 * Debug function to create activity table and test data
 */
function flexpress_debug_create_activity_table() {
    // Force create the activity table
    FlexPress_Activity_Logger::create_activity_table();
    
    // Get current user ID
    $user_id = get_current_user_id();
    if (!$user_id) {
        return false;
    }
    
    // Create some test activity entries
    FlexPress_Activity_Logger::log_activity(
        $user_id,
        'verotel_initial',
        'Test initial subscription payment',
        array(
            'priceAmount' => '29.95',
            'priceCurrency' => 'USD',
            'transactionID' => 'test_tx_' . time(),
            'billing_type' => 'subscription_initial'
        )
    );
    
    FlexPress_Activity_Logger::log_activity(
        $user_id,
        'billing_transaction',
        'Test billing transaction',
        array(
            'amount' => '29.95',
            'currency' => 'USD',
            'transaction_id' => 'test_billing_' . time(),
            'billing_type' => 'subscription_rebill'
        )
    );
    
    error_log('FlexPress Debug: Created activity table and test entries for user ' . $user_id);
    return true;
}

// Add admin action to trigger debug function
add_action('wp_ajax_flexpress_debug_activity', 'flexpress_debug_create_activity_table');

/**
 * Legal Pages Helper Functions
 */

/**
 * Get the last updated date for legal pages
 *
 * @param int $post_id Post ID
 * @return string Formatted date
 */
function flexpress_get_legal_last_updated_date($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Check if custom date is set
    $custom_date = get_field('legal_custom_last_updated', $post_id);
    if ($custom_date) {
        return date_i18n(get_option('date_format'), strtotime($custom_date));
    }
    
    // Fall back to post modified date
    return get_the_modified_date(get_option('date_format'), $post_id);
}

/**
 * Display the contact form section for legal pages
 *
 * @param int $post_id Post ID
 */
function flexpress_display_legal_contact_form($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $form_id = get_field('legal_contact_form_id', $post_id);
    if (!$form_id) {
        return;
    }
    
    $form_title = get_field('legal_contact_form_title', $post_id);
    if (!$form_title) {
        $form_title = __('Have Questions?', 'flexpress');
    }
    
    ?>
    <div class="mt-5 pt-4 border-top">
        <div class="text-center mb-4">
            <h2 class="h4 mb-3"><?php echo esc_html($form_title); ?></h2>
            <p class="text-muted"><?php esc_html_e('If you have any questions about this page, please contact us.', 'flexpress'); ?></p>
        </div>
        
        <?php
        // Support both Contact Form 7 and WPForms
        if (class_exists('WPCF7')) {
            echo do_shortcode('[contact-form-7 id="' . esc_attr($form_id) . '"]');
        } elseif (function_exists('wpforms')) {
            echo do_shortcode('[wpforms id="' . esc_attr($form_id) . '"]');
        } else {
            echo '<div class="alert alert-warning">';
            echo '<p>' . esc_html__('Contact form plugin not found. Please install Contact Form 7 or WPForms.', 'flexpress') . '</p>';
            echo '</div>';
        }
        ?>
    </div>
    <?php
}

/**
 * Display additional content for legal pages
 *
 * @param int $post_id Post ID
 */
function flexpress_display_legal_additional_content($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $additional_content = get_field('legal_additional_content', $post_id);
    if ($additional_content) {
        ?>
        <div class="mt-5 pt-4 border-top">
            <?php echo wp_kses_post($additional_content); ?>
        </div>
        <?php
    }
}

/**
 * Check if last updated date should be shown
 *
 * @param int $post_id Post ID
 * @return bool
 */
function flexpress_should_show_legal_last_updated($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $show_last_updated = get_field('legal_show_last_updated', $post_id);
    return $show_last_updated !== false; // Default to true if not set
}

/**
 * Get social media handles from theme options
 * Returns array of social media handles/usernames
 */
function get_social_handles() {
    return array(
        'instagram' => get_theme_mod('social_instagram_handle', ''),
        'twitter' => get_theme_mod('social_twitter_handle', ''),
        'facebook' => get_theme_mod('social_facebook_handle', ''),
        'tiktok' => get_theme_mod('social_tiktok_handle', ''),
        'exclusv' => get_theme_mod('social_exclusv_handle', ''),
    );
}



/**
 * Add social media customizer settings
 */
function flexpress_customize_register($wp_customize) {
    // Add Social Media Section
    $wp_customize->add_section('flexpress_social_media', array(
        'title' => __('Social Media Handles', 'flexpress'),
        'priority' => 30,
    ));

    // Instagram Handle
    $wp_customize->add_setting('social_instagram_handle', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('social_instagram_handle', array(
        'label' => __('Instagram Handle (without @)', 'flexpress'),
        'section' => 'flexpress_social_media',
        'type' => 'text',
    ));

    // Twitter Handle
    $wp_customize->add_setting('social_twitter_handle', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('social_twitter_handle', array(
        'label' => __('Twitter Handle (without @)', 'flexpress'),
        'section' => 'flexpress_social_media',
        'type' => 'text',
    ));

    // Facebook Handle
    $wp_customize->add_setting('social_facebook_handle', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('social_facebook_handle', array(
        'label' => __('Facebook Handle', 'flexpress'),
        'section' => 'flexpress_social_media',
        'type' => 'text',
    ));

    // TikTok Handle
    $wp_customize->add_setting('social_tiktok_handle', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('social_tiktok_handle', array(
        'label' => __('TikTok Handle (without @)', 'flexpress'),
        'section' => 'flexpress_social_media',
        'type' => 'text',
    ));

    // ExclusV Handle
    $wp_customize->add_setting('social_exclusv_handle', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('social_exclusv_handle', array(
        'label' => __('ExclusV Handle', 'flexpress'),
        'section' => 'flexpress_social_media',
        'type' => 'text',
    ));
}
add_action('customize_register', 'flexpress_customize_register');

/**
 * Create main footer menu pages and organize them in footer menu
 * This function creates all required main pages with proper templates
 * and automatically organizes them in the Footer Menu
 */
function flexpress_create_main_footer_pages_and_menu() {
    // Main footer pages configuration
    $main_pages = array(
        'Home' => array(
            'content' => 'Welcome to our exclusive content platform.',
            'template' => 'page-templates/page-home.php',
            'menu_title' => 'Home'
        ),
        'Episodes' => array(
            'content' => 'Browse our extensive collection of premium episodes.',
            'template' => 'page-templates/episodes.php',
            'menu_title' => 'Episodes'
        ),
        'Models' => array(
            'content' => 'Meet our talented models and performers.',
            'template' => '', // Uses default page template or archive-model.php
            'menu_title' => 'Models'
        ),
        'Extras' => array(
            'content' => 'Discover additional exclusive content and behind-the-scenes material.',
            'template' => '', // Uses default page template
            'menu_title' => 'Extras'
        ),
        'Livestream' => array(
            'content' => 'Join our live streaming sessions and interactive content.',
            'template' => '', // Uses default page template
            'menu_title' => 'Livestream'
        ),
        'About' => array(
            'content' => 'Learn more about our platform and mission.',
            'template' => 'page-templates/about.php',
            'menu_title' => 'About'
        ),
        'Casting' => array(
            'content' => 'Interested in modeling for us? Apply here.',
            'template' => 'page-templates/casting.php',
            'menu_title' => 'Model for Us / Casting'
        ),
        'Contact' => array(
            'content' => 'Get in touch with us for any questions or inquiries.',
            'template' => 'page-templates/contact.php',
            'menu_title' => 'Contact'
        )
    );

    $created_pages = array();

    // Create main pages
    foreach ($main_pages as $title => $details) {
        // Check if page exists
        $existing_page = new WP_Query(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'title' => $title,
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'fields' => 'ids'
        ));

        if (!$existing_page->posts) {
            // Create page
            $page_id = wp_insert_post(array(
                'post_title'     => $title,
                'post_content'   => $details['content'],
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            ));

            if ($page_id && !is_wp_error($page_id)) {
                // Set page template if specified
                if (!empty($details['template'])) {
                    update_post_meta($page_id, '_wp_page_template', $details['template']);
                }
                
                // Set Home page as front page
                if ($title === 'Home') {
                    update_option('page_on_front', $page_id);
                    update_option('show_on_front', 'page');
                }
                
                $created_pages[$title] = array(
                    'id' => $page_id,
                    'menu_title' => $details['menu_title']
                );
            }
        } else {
            // Page exists, add to our array for menu assignment
            $created_pages[$title] = array(
                'id' => $existing_page->posts[0],
                'menu_title' => $details['menu_title']
            );
        }
    }

    // Create or get the Footer Menu
    $footer_menu_name = 'Footer Menu';
    $footer_menu = wp_get_nav_menu_object($footer_menu_name);
    
    if (!$footer_menu) {
        $footer_menu_id = wp_create_nav_menu($footer_menu_name);
        if (is_wp_error($footer_menu_id)) {
            error_log('Failed to create Footer Menu: ' . $footer_menu_id->get_error_message());
            return false;
        }
    } else {
        $footer_menu_id = $footer_menu->term_id;
        
        // Clear existing menu items
        $menu_items = wp_get_nav_menu_items($footer_menu_id);
        if ($menu_items) {
            foreach ($menu_items as $menu_item) {
                wp_delete_post($menu_item->ID, true);
            }
        }
    }

    // Add pages to Footer Menu in specified order
    $menu_order = 1;
    foreach ($created_pages as $page_title => $page_data) {
        $menu_item_id = wp_update_nav_menu_item($footer_menu_id, 0, array(
            'menu-item-title' => $page_data['menu_title'],
            'menu-item-object' => 'page',
            'menu-item-object-id' => $page_data['id'],
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
            'menu-item-position' => $menu_order
        ));

        if (is_wp_error($menu_item_id)) {
            error_log('Failed to add ' . $page_title . ' to Footer Menu: ' . $menu_item_id->get_error_message());
        }
        
        $menu_order++;
    }

    // Assign Footer Menu to footer-menu location
    $locations = get_theme_mod('nav_menu_locations', array());
    $locations['footer-menu'] = $footer_menu_id;
    set_theme_mod('nav_menu_locations', $locations);

    return $created_pages;
}

/**
 * Quick helper function to create legal pages and menu
 * Can be called from anywhere to automatically set up legal pages
 * 
 * @return array|false Array of created pages on success, false on failure
 */
function flexpress_setup_legal_pages() {
    return flexpress_create_legal_pages_and_menu();
}

/**
 * Create support menu pages and organize them in footer support menu
 * This function creates all required support pages with proper templates
 * and automatically organizes them in the Footer Support Menu
 */
function flexpress_create_support_pages_and_menu() {
    // Support pages configuration
    $support_pages = array(
        'Join' => array(
            'content' => 'Join our exclusive membership platform and get access to premium content.',
            'template' => 'page-templates/join.php',
            'menu_title' => 'Join'
        ),
        'Login' => array(
            'content' => 'Login to your account to access premium content.',
            'template' => 'page-templates/login.php',
            'menu_title' => 'Login'
        ),
        'My Account' => array(
            'content' => 'Manage your account settings, subscription, and preferences.',
            'template' => 'page-templates/dashboard.php',
            'menu_title' => 'My Account'
        ),
        'Reset Password' => array(
            'content' => 'Reset your password to regain access to your account.',
            'template' => 'page-templates/reset-password.php',
            'menu_title' => 'Reset Password'
        ),
        'Cancel Membership' => array(
            'content' => 'Cancel your membership subscription and manage your account status.',
            'template' => '', // Uses default page template
            'menu_title' => 'Cancel'
        ),
        'Affiliates' => array(
            'content' => 'Join our affiliate program and earn commissions by promoting our platform.',
            'template' => '', // Uses default page template
            'menu_title' => 'Affiliates / Webmasters'
        )
    );

    $created_pages = array();

    // Create support pages
    foreach ($support_pages as $title => $details) {
        // Check if page exists
        $existing_page = new WP_Query(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'title' => $title,
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'fields' => 'ids'
        ));

        if (!$existing_page->posts) {
            // Create page
            $page_id = wp_insert_post(array(
                'post_title'     => $title,
                'post_content'   => $details['content'],
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            ));

            if ($page_id && !is_wp_error($page_id)) {
                // Set page template if specified
                if (!empty($details['template'])) {
                    update_post_meta($page_id, '_wp_page_template', $details['template']);
                }
                
                $created_pages[$title] = array(
                    'id' => $page_id,
                    'menu_title' => $details['menu_title']
                );
            }
        } else {
            // Page exists, add to our array for menu assignment
            $created_pages[$title] = array(
                'id' => $existing_page->posts[0],
                'menu_title' => $details['menu_title']
            );
        }
    }

    // Create or get the Support Menu
    $support_menu_name = 'Support';
    $support_menu = wp_get_nav_menu_object($support_menu_name);
    
    if (!$support_menu) {
        $support_menu_id = wp_create_nav_menu($support_menu_name);
        if (is_wp_error($support_menu_id)) {
            error_log('Failed to create Support Menu: ' . $support_menu_id->get_error_message());
            return false;
        }
    } else {
        $support_menu_id = $support_menu->term_id;
        
        // Clear existing menu items
        $menu_items = wp_get_nav_menu_items($support_menu_id);
        if ($menu_items) {
            foreach ($menu_items as $menu_item) {
                wp_delete_post($menu_item->ID, true);
            }
        }
    }

    // Add pages to Support Menu in specified order
    $menu_order = 1;
    foreach ($created_pages as $page_title => $page_data) {
        $menu_item_id = wp_update_nav_menu_item($support_menu_id, 0, array(
            'menu-item-title' => $page_data['menu_title'],
            'menu-item-object' => 'page',
            'menu-item-object-id' => $page_data['id'],
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
            'menu-item-position' => $menu_order
        ));

        if (is_wp_error($menu_item_id)) {
            error_log('Failed to add ' . $page_title . ' to Support Menu: ' . $menu_item_id->get_error_message());
        }
        
        $menu_order++;
    }

    // Add Log Out as a custom link
    $logout_item_id = wp_update_nav_menu_item($support_menu_id, 0, array(
        'menu-item-title' => 'Log Out',
        'menu-item-url' => wp_logout_url(home_url('/')),
        'menu-item-type' => 'custom',
        'menu-item-status' => 'publish',
        'menu-item-position' => $menu_order
    ));

    if (is_wp_error($logout_item_id)) {
        error_log('Failed to add Log Out to Support Menu: ' . $logout_item_id->get_error_message());
    }

    // Assign Support Menu to footer-support-menu location
    $locations = get_theme_mod('nav_menu_locations', array());
    $locations['footer-support-menu'] = $support_menu_id;
    set_theme_mod('nav_menu_locations', $locations);

    return $created_pages;
}

/**
 * Quick helper function to create main footer pages and menu
 * Can be called from anywhere to automatically set up main pages
 * 
 * @return array|false Array of created pages on success, false on failure
 */
function flexpress_setup_main_footer_pages() {
    return flexpress_create_main_footer_pages_and_menu();
}

/**
 * Quick helper function to create support pages and menu
 * Can be called from anywhere to automatically set up support pages
 * 
 * @return array|false Array of created pages on success, false on failure
 */
function flexpress_setup_support_pages() {
    return flexpress_create_support_pages_and_menu();
}

/**
 * COMPLETE AUTO-SETUP FOR FLEXPRESS THEME
 * Creates all required pages and menus for a turnkey paysite solution
 * Runs automatically on theme activation
 */
function flexpress_complete_auto_setup() {
    // Prevent multiple runs during same request
    if (get_transient('flexpress_auto_setup_running')) {
        return;
    }
    set_transient('flexpress_auto_setup_running', true, 300); // 5 minutes
    
    error_log('FlexPress: Starting complete auto-setup...');
    
    $setup_results = array(
        'main_footer' => false,
        'support' => false,
        'legal' => false,
        'existing_pages' => 0
    );
    
    // Count existing pages to determine if this is a fresh install
    $existing_pages = get_pages(array('post_status' => 'publish'));
    $setup_results['existing_pages'] = count($existing_pages);
    
    // Always create if less than 10 pages exist (fresh install)
    $is_fresh_install = count($existing_pages) < 10;
    
    if ($is_fresh_install || !get_option('flexpress_auto_setup_completed')) {
        error_log('FlexPress: Fresh install detected or setup not completed. Creating all pages and menus...');
        
        // 1. Create Main Footer Pages & Menu (Home, Episodes, Models, etc.)
        $main_footer_result = flexpress_create_main_footer_pages_and_menu();
        $setup_results['main_footer'] = !empty($main_footer_result);
        
        // Small delay to prevent conflicts
        usleep(500000); // 0.5 seconds
        
        // 2. Create Support Pages & Menu (Join, Login, My Account, etc.)
        $support_result = flexpress_create_support_pages_and_menu();
        $setup_results['support'] = !empty($support_result);
        
        // Small delay to prevent conflicts
        usleep(500000); // 0.5 seconds
        
        // 3. Create Legal Pages & Menu (Privacy, Terms, 2257, etc.)
        $legal_result = flexpress_create_legal_pages_and_menu();
        $setup_results['legal'] = !empty($legal_result);
        
        // 4. Create additional default pages if needed
        flexpress_create_required_pages();
        
        // 5. Set up WordPress reading settings for a proper paysite
        flexpress_configure_paysite_settings();
        
        // Mark auto-setup as completed
        update_option('flexpress_auto_setup_completed', true);
        update_option('flexpress_auto_setup_date', current_time('mysql'));
        update_option('flexpress_auto_setup_results', $setup_results);
        
        error_log('FlexPress: Auto-setup completed successfully');
        error_log('FlexPress: Setup results: ' . print_r($setup_results, true));
        
        // Set admin notice for next admin page load
        set_transient('flexpress_setup_success_notice', true, 300);
        
    } else {
        error_log('FlexPress: Auto-setup skipped - site already has content or setup completed');
    }
    
    delete_transient('flexpress_auto_setup_running');
}

/**
 * Configure WordPress settings for optimal paysite operation
 */
function flexpress_configure_paysite_settings() {
    // Set reading settings for better paysite operation
    update_option('blog_public', 0); // Discourage search engines
    update_option('default_comment_status', 'closed'); // Disable comments by default
    update_option('default_ping_status', 'closed'); // Disable trackbacks/pingbacks
    
    // Set timezone to a reasonable default if not set
    if (get_option('gmt_offset') == 0 && get_option('timezone_string') == '') {
        update_option('gmt_offset', -5); // EST as default
    }
    
    // Set date format
    update_option('date_format', 'F j, Y');
    update_option('time_format', 'g:i a');
    
    // Set permalink structure for better SEO
    if (get_option('permalink_structure') == '') {
        update_option('rewrite_rules', '');
        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules();
    }
    
    error_log('FlexPress: Paysite settings configured');
}

/**
 * Run complete auto-setup on theme activation and after theme setup
 */
function flexpress_run_auto_setup() {
    // Check if auto-setup is disabled
    if (get_option('flexpress_disable_auto_setup', false)) {
        return;
    }
    
    // Only run if auto-setup hasn't been completed yet
    if (get_option('flexpress_auto_setup_completed')) {
        return;
    }
    
    // Run setup in background to avoid timeout issues
    wp_schedule_single_event(time() + 5, 'flexpress_complete_auto_setup_hook');
}

// Run on theme activation and setup
add_action('after_switch_theme', 'flexpress_run_auto_setup');
add_action('after_setup_theme', 'flexpress_run_auto_setup', 25);

// Hook for scheduled auto-setup
add_action('flexpress_complete_auto_setup_hook', 'flexpress_complete_auto_setup');

/**
 * Force manual auto-setup (for testing or manual trigger)
 */
function flexpress_force_auto_setup() {
    delete_option('flexpress_auto_setup_completed');
    delete_transient('flexpress_auto_setup_running');
    flexpress_complete_auto_setup();
}

/**
 * Manual auto-setup trigger function (for admin button)
 */
function flexpress_manual_auto_setup() {
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        return array('success' => false, 'message' => 'Insufficient permissions');
    }
    
    // Force run auto-setup
    flexpress_force_auto_setup();
    
    return array('success' => true, 'message' => 'Auto-setup completed successfully');
}

/**
 * Show admin notice when auto-setup completes
 */
function flexpress_show_auto_setup_notice() {
    if (get_transient('flexpress_setup_success_notice')) {
        delete_transient('flexpress_setup_success_notice');
        
        $setup_results = get_option('flexpress_auto_setup_results', array());
        ?>
        <div class="notice notice-success is-dismissible">
            <h3>🎉 FlexPress Auto-Setup Complete!</h3>
            <p><strong>Your adult content paysite is ready!</strong> All required pages and menus have been created automatically:</p>
            <ul style="margin-left: 20px;">
                <li>✅ <strong>Main Navigation:</strong> Home, Episodes, Models, Extras, Livestream, About, Casting, Contact</li>
                <li>✅ <strong>Support Menu:</strong> Join, Login, My Account, Reset Password, Cancel, Affiliates, Log Out</li>
                <li>✅ <strong>Legal Pages:</strong> Privacy Policy, Terms & Conditions, 2257 Compliance, Anti-Slavery Policy, Content Removal</li>
                <li>✅ <strong>WordPress Settings:</strong> Optimized for adult content sites</li>
            </ul>
            <p><strong>Next Steps:</strong></p>
            <ul style="margin-left: 20px;">
                <li>📋 Configure Verotel settings in <a href="<?php echo admin_url('admin.php?page=flexpress-verotel-settings'); ?>">FlexPress → Verotel</a></li>
                <li>🎥 Configure BunnyCDN settings in <a href="<?php echo admin_url('admin.php?page=flexpress-video-settings'); ?>">FlexPress → BunnyCDN</a></li>
                <li>💰 Set up pricing plans in <a href="<?php echo admin_url('admin.php?page=flexpress-pricing-settings'); ?>">FlexPress → Pricing</a></li>
                <li>🎨 Upload your logo in <a href="<?php echo admin_url('admin.php?page=flexpress-settings'); ?>">FlexPress → General</a></li>
            </ul>
            <p style="color: #666; font-style: italic;">This notice will only appear once. Your site is now a complete turnkey paysite solution!</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'flexpress_show_auto_setup_notice');

/**
 * Add auto-setup status to FlexPress Settings dashboard
 */
function flexpress_add_auto_setup_status_section() {
    $setup_completed = get_option('flexpress_auto_setup_completed', false);
    $setup_date = get_option('flexpress_auto_setup_date', '');
    $setup_results = get_option('flexpress_auto_setup_results', array());
    
    ?>
    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2 class="title">🚀 FlexPress Auto-Setup Status</h2>
        
        <?php if ($setup_completed): ?>
            <div class="notice notice-success inline">
                <p>
                    <strong>✅ Auto-Setup Completed!</strong><br>
                    Setup Date: <?php echo esc_html($setup_date ? date('F j, Y g:i a', strtotime($setup_date)) : 'Unknown'); ?>
                </p>
            </div>
            
            <?php if (!empty($setup_results)): ?>
                <h3>Setup Results:</h3>
                <ul style="margin-left: 20px;">
                    <li><?php echo $setup_results['main_footer'] ? '✅' : '❌'; ?> Main Footer Pages & Menu</li>
                    <li><?php echo $setup_results['support'] ? '✅' : '❌'; ?> Support Pages & Menu</li>
                    <li><?php echo $setup_results['legal'] ? '✅' : '❌'; ?> Legal Pages & Menu</li>
                    <li>📊 Found <?php echo esc_html($setup_results['existing_pages'] ?? 0); ?> existing pages during setup</li>
                </ul>
            <?php endif; ?>
            
            <p>
                <strong>Manual Override:</strong> 
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=flexpress_force_auto_setup'), 'flexpress_force_setup'); ?>" 
                   class="button button-secondary"
                   onclick="return confirm('This will recreate all pages and menus. Are you sure?');">
                    Force Re-Run Auto-Setup
                </a>
            </p>
        <?php else: ?>
            <div class="notice notice-warning inline">
                <p>
                    <strong>⚠️ Auto-Setup Not Completed</strong><br>
                    The automatic setup may not have run yet or failed. 
                </p>
            </div>
            
            <p>
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=flexpress_force_auto_setup'), 'flexpress_force_setup'); ?>" 
                   class="button button-primary">
                    Run Auto-Setup Now
                </a>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Handle manual auto-setup trigger from admin
 */
function flexpress_handle_force_auto_setup() {
    // Verify nonce and permissions
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'flexpress_force_setup') || !current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    // Force run auto-setup
    flexpress_force_auto_setup();
    
    // Redirect back to settings with success message
    wp_redirect(add_query_arg(array(
        'page' => 'flexpress-settings',
        'setup' => 'forced'
    ), admin_url('admin.php')));
    exit;
}
add_action('admin_post_flexpress_force_auto_setup', 'flexpress_handle_force_auto_setup');

/**
 * Get the FlexPress custom logo
 * Checks FlexPress settings first, then falls back to WordPress customizer
 * 
 * @param string $size Image size (default: 'full')
 * @return array|false Array with logo data or false if no logo
 */
function flexpress_get_custom_logo($size = 'full') {
    // Check for FlexPress custom logo first
    $flexpress_settings = get_option('flexpress_general_settings', array());
    $flexpress_logo_id = isset($flexpress_settings['custom_logo']) ? $flexpress_settings['custom_logo'] : '';
    
    if (!empty($flexpress_logo_id)) {
        $logo = wp_get_attachment_image_src($flexpress_logo_id, $size);
        if ($logo) {
            return array(
                'url' => $logo[0],
                'width' => $logo[1],
                'height' => $logo[2],
                'source' => 'flexpress'
            );
        }
    }
    
    // Fallback to WordPress customizer logo
    if (has_custom_logo()) {
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo = wp_get_attachment_image_src($custom_logo_id, $size);
        if ($logo) {
            return array(
                'url' => $logo[0],
                'width' => $logo[1],
                'height' => $logo[2],
                'source' => 'wordpress'
            );
        }
    }
    
    return false;
}

/**
 * Display the FlexPress custom logo
 * Shows logo if available, otherwise shows site title
 * 
 * @param array $args Display arguments
 */
function flexpress_display_logo($args = array()) {
    $defaults = array(
        'class' => 'img-fluid pt-4',
        'alt' => get_bloginfo('name'),
        'title_class' => 'text-white pt-4',
        'title_tag' => 'h2'
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $logo = flexpress_get_custom_logo();
    
    if ($logo) {
        printf(
            '<img src="%s" class="%s" alt="%s">',
            esc_url($logo['url']),
            esc_attr($args['class']),
            esc_attr($args['alt'])
        );
    } else {
        printf(
            '<%s class="%s">%s</%s>',
            esc_attr($args['title_tag']),
            esc_attr($args['title_class']),
            esc_html(get_bloginfo('name')),
            esc_attr($args['title_tag'])
        );
    }
}

/**
 * Generate dynamic privacy policy content with site-specific variables
 *
 * @return string Complete privacy policy content with dynamic variables
 */
function flexpress_generate_privacy_policy_content() {
    // Get dynamic variables
    $site_name = get_bloginfo('name');
    $site_url = get_bloginfo('url');
    $site_domain = parse_url(home_url(), PHP_URL_HOST);
    
    // Get contact information
    $contact_email = flexpress_get_contact_email('contact');
    $support_email = flexpress_get_support_email();
    $billing_email = flexpress_get_billing_email();
    
    // Get business information
    $parent_company = flexpress_get_parent_company();
    $business_number = flexpress_get_business_number();
    $business_address = flexpress_get_business_address();
    
    // Use defaults if not set
    if (empty($contact_email)) {
        $contact_email = 'privacy@' . $site_domain;
    }
    if (empty($support_email)) {
        $support_email = 'support@' . $site_domain;
    }
    if (empty($billing_email)) {
        $billing_email = 'billing@' . $site_domain;
    }
    if (empty($parent_company)) {
        $parent_company = $site_name;
    }
    
    // Current date for last updated
    $current_date = date('F j, Y');
    
    // Generate privacy policy content based on provided template
    $content = "<p><strong>PRIVACY POLICY</strong></p>
<p><strong>Last Updated: {$current_date}</strong></p>

<p>At {$site_name} (the \"Site\"), protecting your privacy is our priority. This Privacy Policy outlines how we collect, use, store, and protect your personal information. By accessing or using the Site, you agree to the terms of this Privacy Policy. If you do not agree, please discontinue using the Site immediately.</p>

<hr />

<h3><strong>1. Information We Collect</strong></h3>
<p><strong>a. Personal Information:</strong><br>
When you purchase a membership, access content, or interact with our services, we may collect personal information, including:</p>
<ul>
<li>Name</li>
<li>Email address</li>
<li>Payment information (processed by our billing provider, Verotel)</li>
<li>Billing address</li>
<li>IP address</li>
</ul>

<p><strong>b. Non-Personal Information:</strong><br>
We collect non-personal information automatically, such as:</p>
<ul>
<li>Browser type and version</li>
<li>Device information</li>
<li>Operating system</li>
<li>Geographic location</li>
</ul>

<p><strong>c. Cookies and Tracking:</strong><br>
We use cookies, web beacons, and similar technologies to enhance your user experience and gather analytics. Cookies may store information such as your preferences or browsing activity.</p>

<hr />

<h3><strong>2. How We Use Your Information</strong></h3>
<p>We use the information collected for the following purposes:</p>
<ul>
<li>To process transactions and manage your membership</li>
<li>To deliver customer support and respond to inquiries</li>
<li>To improve the Site's functionality and user experience</li>
<li>To comply with legal obligations and prevent fraud</li>
<li>To send updates, notifications, or promotional content (you can opt out at any time)</li>
</ul>

<hr />

<h3><strong>3. How We Protect Your Information</strong></h3>
<p>We implement industry-standard security measures to protect your data, including encryption, secure servers, and restricted access. While we strive to safeguard your information, no method of transmission over the internet or electronic storage is entirely secure.</p>

<hr />

<h3><strong>4. Sharing Your Information</strong></h3>
<p>We do not sell or rent your personal information. However, we may share your information in the following circumstances:</p>

<p><strong>a. Billing Providers:</strong><br>
Your payment information is securely processed by Verotel. We do not store your credit card details on our servers.</p>";

    // Add parent company information if available
    if (!empty($parent_company)) {
        $content .= "\n\n<p><strong>b. Parent Company:</strong><br>";
        $content .= "Your information may be shared with {$parent_company}";
        
        if (!empty($business_number) || !empty($business_address)) {
            $content .= ", a duly registered company";
            
            if (!empty($business_number)) {
                $content .= " bearing {$business_number}";
            }
            
            if (!empty($business_address)) {
                $content .= " and registered place of business located at {$business_address}";
            }
        }
        
        $content .= ", for administrative purposes.</p>";
    }

    $content .= "\n\n<p><strong>c. Legal Compliance:</strong><br>
We may disclose your information if required to do so by law, to enforce our terms of use, or to protect the rights, property, or safety of our users, the Site, or others.</p>

<p><strong>d. Service Providers:</strong><br>
We may share data with trusted third-party service providers for hosting, analytics, or communication purposes.</p>

<hr />

<h3><strong>5. Your Rights</strong></h3>
<p>You have the right to:</p>
<ul>
<li>Access your personal information</li>
<li>Correct inaccuracies in your data</li>
<li>Request deletion of your information (subject to legal or contractual obligations)</li>
<li>Object to the processing of your data in certain cases</li>
<li>Withdraw consent for marketing communications at any time</li>
</ul>
<p>To exercise your rights, contact us at <strong><a href=\"mailto:{$contact_email}\">{$contact_email}</a></strong>.</p>

<hr />

<h3><strong>6. Data Retention</strong></h3>
<p>We retain your personal information for as long as necessary to provide our services, comply with legal obligations, resolve disputes, and enforce agreements.</p>

<hr />

<h3><strong>7. Third-Party Links</strong></h3>
<p>The Site may contain links to external websites. We are not responsible for the privacy practices of those websites and encourage you to review their policies before sharing any personal information.</p>

<hr />

<h3><strong>8. Age Restrictions</strong></h3>
<p>This Site is intended for individuals who are at least 18 years old or the age of majority in their jurisdiction. We do not knowingly collect personal information from minors.</p>

<hr />

<h3><strong>9. International Users</strong></h3>
<p>If you access the Site from outside your country, your information may be transferred and processed in other countries where our servers are located. By using the Site, you consent to this transfer.</p>

<hr />

<h3><strong>10. Changes to This Privacy Policy</strong></h3>
<p>We may update this Privacy Policy periodically. Changes will be posted on this page with the updated date. Continued use of the Site after changes are posted constitutes your acceptance of the revised policy.</p>

<hr />

<h3><strong>11. Governing Law</strong></h3>
<p>This Privacy Policy is governed by and construed in accordance with applicable laws. Any disputes arising from this Privacy Policy shall be subject to the exclusive jurisdiction of the appropriate courts.</p>

<hr />

<h3><strong>12. Contact Us</strong></h3>
<p>If you have any questions, concerns, or requests regarding this Privacy Policy, please contact us:</p>
<p><strong>{$site_name}</strong><br>
Email: <strong><a href=\"mailto:{$contact_email}\">{$contact_email}</a></strong>";


    return $content;
}

/**
 * Generate dynamic terms and conditions content with site-specific variables
 *
 * @return string Complete terms and conditions content with dynamic variables
 */
function flexpress_generate_terms_conditions_content() {
    // Get dynamic variables
    $site_name = get_bloginfo('name');
    $site_url = get_bloginfo('url');
    $site_domain = parse_url(home_url(), PHP_URL_HOST);
    
    // Get contact information
    $contact_email = flexpress_get_contact_email('contact');
    $support_email = flexpress_get_support_email();
    $billing_email = flexpress_get_billing_email();
    
    // Get business information
    $parent_company = flexpress_get_parent_company();
    $business_number = flexpress_get_business_number();
    $business_address = flexpress_get_business_address();
    
    // Use defaults if not set
    if (empty($contact_email)) {
        $contact_email = 'legal@' . $site_domain;
    }
    if (empty($support_email)) {
        $support_email = 'support@' . $site_domain;
    }
    if (empty($billing_email)) {
        $billing_email = 'billing@' . $site_domain;
    }
    if (empty($parent_company)) {
        $parent_company = $site_name;
    }
    
    // Current date for last updated
    $current_date = date('F j, Y');
    
    // Generate comprehensive terms and conditions content
    $content = "<p><strong>Effective Date:</strong> {$current_date}</p>

<p>Welcome to <strong>{$site_name}</strong> (\"{$site_url}\"). These Terms and Conditions (\"Terms\") govern your use of our website and services. By accessing or using our services, you agree to be bound by these Terms.</p>

<h2>1. Acceptance of Terms</h2>

<p>By accessing and using {$site_name}, you accept and agree to be bound by these Terms and our Privacy Policy. If you do not agree to these Terms, please do not use our services.</p>

<h2>2. Age Verification and Restrictions</h2>

<p><strong>You must be at least 18 years of age to use our services.</strong> Our content is intended for adults only. By using our services, you represent and warrant that:</p>
<ul>
<li>You are at least 18 years of age</li>
<li>You have the legal capacity to enter into these Terms</li>
<li>You are accessing our content for personal, non-commercial use</li>
<li>You understand that our content may include adult material</li>
</ul>

<h2>3. Account Registration and Security</h2>

<p>To access certain features of our services, you may need to create an account. You agree to:</p>
<ul>
<li>Provide accurate, current, and complete information</li>
<li>Maintain and update your account information</li>
<li>Keep your login credentials secure and confidential</li>
<li>Notify us immediately of any unauthorized use of your account</li>
<li>Accept responsibility for all activities under your account</li>
</ul>

<h2>4. Membership and Billing</h2>

<h3>Subscription Services</h3>
<p>Our services may be offered through various subscription plans. By subscribing, you agree to:</p>
<ul>
<li>Pay all applicable fees and charges</li>
<li>Provide valid payment information</li>
<li>Allow automatic renewal unless cancelled</li>
<li>Accept that subscription fees are non-refundable unless otherwise stated</li>
</ul>

<h3>Payment Processing</h3>
<p>All payments are processed securely through our third-party payment processors. You authorize us to charge your payment method for all applicable fees and charges.</p>

<h3>Cancellation</h3>
<p>You may cancel your subscription at any time through your account settings or by contacting our support team at <a href=\"mailto:{$support_email}\">{$support_email}</a>.</p>

<h2>5. Content and Intellectual Property</h2>

<h3>Our Content</h3>
<p>All content on {$site_name}, including videos, images, text, graphics, logos, and software, is owned by {$parent_company} or our licensors and is protected by copyright, trademark, and other intellectual property laws.</p>

<h3>License to Use</h3>
<p>We grant you a limited, non-exclusive, non-transferable license to access and use our content for personal, non-commercial purposes, subject to these Terms.</p>

<h3>Restrictions</h3>
<p>You may not:</p>
<ul>
<li>Download, copy, reproduce, or distribute our content</li>
<li>Create derivative works based on our content</li>
<li>Use our content for commercial purposes</li>
<li>Remove or alter any copyright or proprietary notices</li>
<li>Share your account credentials with others</li>
<li>Use automated tools to access our services</li>
</ul>

<h2>6. User Conduct</h2>

<p>You agree not to:</p>
<ul>
<li>Use our services for any illegal or unauthorized purpose</li>
<li>Violate any applicable laws or regulations</li>
<li>Infringe on the rights of others</li>
<li>Upload or transmit viruses or malicious code</li>
<li>Attempt to gain unauthorized access to our systems</li>
<li>Interfere with the proper functioning of our services</li>
<li>Harass, threaten, or abuse other users or our staff</li>
</ul>

<h2>7. Privacy and Data Protection</h2>

<p>Your privacy is important to us. Please review our Privacy Policy to understand how we collect, use, and protect your personal information.</p>

<h2>8. Disclaimers and Limitations of Liability</h2>

<h3>Service Availability</h3>
<p>We strive to maintain continuous service availability but cannot guarantee uninterrupted access. Our services may be temporarily unavailable due to maintenance, updates, or technical issues.</p>

<h3>Content Accuracy</h3>
<p>While we make efforts to ensure content accuracy, we do not warrant that all information on our website is current, complete, or error-free.</p>

<h3>Limitation of Liability</h3>
<p>To the maximum extent permitted by law, {$parent_company} and its affiliates shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of our services.</p>

<h2>9. Indemnification</h2>

<p>You agree to indemnify and hold harmless {$parent_company}, its officers, directors, employees, and agents from any claims, damages, losses, or expenses arising from your use of our services or violation of these Terms.</p>

<h2>10. Termination</h2>

<p>We may terminate or suspend your account and access to our services at any time, with or without notice, for violation of these Terms or for any other reason we deem appropriate.</p>

<h2>11. Changes to Terms</h2>

<p>We reserve the right to modify these Terms at any time. We will notify you of material changes by posting the updated Terms on our website. Your continued use of our services after such changes constitutes acceptance of the new Terms.</p>

<h2>12. Governing Law</h2>

<p>These Terms shall be governed by and construed in accordance with applicable laws. Any disputes arising from these Terms or your use of our services shall be resolved through binding arbitration or in the courts of competent jurisdiction.</p>

<h2>13. Contact Information</h2>

<p>If you have any questions about these Terms, please contact us:</p>

<ul>
<li><strong>Email:</strong> <a href=\"mailto:{$contact_email}\">{$contact_email}</a></li>";

    if (!empty($support_email) && $support_email !== $contact_email) {
        $content .= "\n<li><strong>Support:</strong> <a href=\"mailto:{$support_email}\">{$support_email}</a></li>";
    }

    if (!empty($billing_email) && $billing_email !== $contact_email) {
        $content .= "\n<li><strong>Billing Questions:</strong> <a href=\"mailto:{$billing_email}\">{$billing_email}</a></li>";
    }

    if (!empty($business_address)) {
        $content .= "\n<li><strong>Mailing Address:</strong> " . esc_html($business_address) . "</li>";
    }

    $content .= "\n</ul>";

    if (!empty($parent_company) || !empty($business_number)) {
        $content .= "\n<p>";
        if (!empty($parent_company)) {
            $content .= "<strong>Company:</strong> " . esc_html($parent_company);
            if (!empty($business_number)) {
                $content .= "<br>";
            }
        }
        if (!empty($business_number)) {
            $content .= "<strong>Business Registration:</strong> " . esc_html($business_number);
        }
        $content .= "</p>";
    }

    $content .= "\n\n<p><em>These Terms and Conditions were last updated on {$current_date}.</em></p>";

    return $content;
}

/**
 * Generate dynamic 2257 compliance content with site-specific variables
 *
 * @return string Complete 2257 compliance content with dynamic variables
 */
function flexpress_generate_2257_compliance_content() {
    // Get dynamic variables
    $site_name = get_bloginfo('name');
    $site_domain = parse_url(home_url(), PHP_URL_HOST);
    
    // Get contact information
    $contact_email = flexpress_get_contact_email('contact');
    
    // Get business information
    $parent_company = flexpress_get_parent_company();
    $business_address = flexpress_get_business_address();
    
    // Use defaults if not set
    if (empty($contact_email)) {
        $contact_email = 'compliance@' . $site_domain;
    }
    if (empty($parent_company)) {
        $parent_company = $site_name;
    }
    
    // Current date for effective date
    $current_date = date('F j, Y');
    
    // Generate 2257 compliance content
    $content = "<h3>18 U.S.C. §2257 Compliance Statement</h3>
<p>In accordance with the U.S. Federal Labeling and Record-Keeping Law (18 U.S.C. §2257), all models, actors, actresses, and other persons depicted in any visual representation of actual sexually explicit conduct on this website were over the age of 18 at the time the content was created.</p>

<p>Proof of age for all models is maintained by the Custodian of Records listed below. All content and images comply fully with the requirements set forth by 18 U.S.C. §2257 and related regulations.</p>

<h4>Custodian of Records:</h4>
<p><strong>{$parent_company}</strong><br>
<a href=\"mailto:{$contact_email}\">{$contact_email}</a></p>";

    // Add business address if available
    if (!empty($business_address)) {
        // Format address with line breaks
        $formatted_address = nl2br(esc_html($business_address));
        $content .= "\n\n<p>{$formatted_address}</p>";
    }

    $content .= "\n\n<p><strong>Effective Date:</strong> {$current_date}</p>";

    return $content;
}

/**
 * Generate dynamic anti-slavery and human trafficking policy content with site-specific variables
 *
 * @return string Complete anti-slavery policy content with dynamic variables
 */
function flexpress_generate_anti_slavery_content() {
    // Get dynamic variables
    $site_name = get_bloginfo('name');
    $site_domain = parse_url(home_url(), PHP_URL_HOST);
    
    // Get contact information
    $contact_email = flexpress_get_contact_email('contact');
    
    // Get business information
    $parent_company = flexpress_get_parent_company();
    $business_address = flexpress_get_business_address();
    
    // Use defaults if not set
    if (empty($contact_email)) {
        $contact_email = 'legal@' . $site_domain;
    }
    if (empty($parent_company)) {
        $parent_company = $site_name;
    }
    
    // Current date for last updated
    $current_date = date('F j, Y');
    
    // Generate anti-slavery policy content
    $content = "<p><strong>Last Updated: {$current_date}</strong></p>

<h4>1. Policy Statement</h4>
<p>This policy applies to all persons working for us or on our behalf in any capacity, including employees at all levels, directors, officers, agency workers, seconded workers, volunteers, agents, contractors, and suppliers.</p>

<p>{$parent_company} strictly prohibits the use of modern slavery and human trafficking in our operations and productions. We are committed to implementing systems and controls to ensure that modern slavery is not taking place within our organization or in any of our productions. We expect our producers and partners to hold their own staff and contractors to the same high standards.</p>

<h4>2. Commitments</h4>
<p><strong>Modern Slavery and Human Trafficking</strong></p>
<p>Modern slavery encompasses slavery, servitude, forced and compulsory labor, bonded and child labor, and human trafficking. Human trafficking involves arranging or facilitating the travel of another person with the intent of exploitation. Modern slavery is a crime and a violation of fundamental human rights.</p>

<p><strong>Our Commitments</strong></p>
<p>We are dedicated to upholding the following measures to safeguard against modern slavery:</p>
<ul>
<li>We maintain a zero-tolerance approach to modern slavery within our organization and supply chains.</li>
<li>The prevention, detection, and reporting of modern slavery in any part of our organization or productions is the responsibility of all individuals working for us or on our behalf. Workers and contractors must not engage in, facilitate, or fail to report any activity that might lead to or suggest a breach of this policy.</li>
<li>We are committed to engaging with stakeholders and producers to address risks of modern slavery in our operations and productions.</li>
<li>We take a risk-based approach to contracting processes and keep them under review. Where appropriate, we assess whether circumstances warrant specific prohibitions against modern slavery in contracts with third parties. We may also require producers to comply with our Code of Conduct, which outlines minimum standards to combat modern slavery and trafficking.</li>
<li>Consistent with our risk-based approach, we may require:
<ul>
<li>Employment and recruitment agencies and other third parties supplying workers to our organization to confirm compliance with our Code of Conduct.</li>
<li>Suppliers engaging workers through a third party to ensure those third parties adhere to the Code of Conduct.</li>
</ul>
</li>
<li>As part of our ongoing risk assessment and due diligence processes, we consider whether circumstances warrant supplier audits to verify compliance with our Code of Conduct.</li>
<li>If we find that individuals or organizations working on our behalf have breached this policy, we will take appropriate action. This may range from seeking remediation for impacted individuals to terminating relationships with those in breach.</li>
</ul>";

    return $content;
}

/**
 * Generate dynamic customer terms and conditions content with site-specific variables
 *
 * @return string Complete customer terms content with dynamic variables
 */
function flexpress_generate_customer_terms_content() {
    // Get dynamic variables
    $site_name = get_bloginfo('name');
    $site_domain = parse_url(home_url(), PHP_URL_HOST);
    
    // Get contact information
    $contact_email = flexpress_get_contact_email('contact');
    $support_email = flexpress_get_contact_email('support');
    $billing_email = flexpress_get_contact_email('billing');
    
    // Get business information
    $parent_company = flexpress_get_parent_company();
    $business_number = flexpress_get_business_number();
    $business_address = flexpress_get_business_address();
    
    // Use defaults if not set
    if (empty($contact_email)) {
        $contact_email = 'support@' . $site_domain;
    }
    if (empty($parent_company)) {
        $parent_company = $site_name;
    }
    
    // Generate customer terms content
    $content = "<p><strong>CUSTOMER TERMS &amp; CONDITIONS</strong></p>
<p>Before proceeding with a transaction, you must read and agree to these terms and conditions (\"Agreement\"). By subscribing to or using services from this website, you agree to be legally bound by this Agreement. These terms may be updated at any time, with changes becoming effective immediately upon being posted on the site.</p>

<p><strong>PREAMBLE</strong></p>
<ul>
<li>Subscriber's credit card will be charged immediately upon purchase.</li>
<li>After purchase, an email confirmation will be sent with payment details.</li>
<li>The contract is finalized upon submission of the order.</li>
<li>Any inquiries will receive responses within six business days.</li>
<li>Access to any content on this site is strictly prohibited for anyone below the legal age in their respective jurisdiction, with a minimum age requirement of 18 years.</li>
</ul>

<p><strong>DEFINITIONS</strong></p>
<ul>
<li>\"Member\" or \"Membership\": Refers to a user with a valid username and password for the site during their membership period.</li>
<li>\"Site\": Refers to this website, operated by {$parent_company}";

    // Add business details if available
    if (!empty($business_number) || !empty($business_address)) {
        if (!empty($business_number)) {
            $content .= ", bearing business registration number " . esc_html($business_number);
        }
        if (!empty($business_address)) {
            $content .= " with registered place of business located at " . esc_html($business_address);
        }
    }
    
    $content .= ", for which a subscription is purchased.</li>
<li>\"Subscriber\": Refers to a user who holds a valid username and password for the site.</li>
<li>\"Access Right\": The unique username and password combination used to access the site, functioning as a license to use the site during the specified membership period.</li>
</ul>

<p><strong>DESCRIPTION OF SERVICES</strong></p>
<p>{$parent_company}, using Verotel as a billing provider, grants access to the site and its materials upon purchase of a membership.</p>

<p><strong>BILLING</strong></p>
<ul>
<li>Billing is processed by Verotel. Subscribers will be notified of the billing descriptor that will appear on their credit card or bank statement.</li>
<li>If additional services are purchased, the statement will reflect those charges.</li>
</ul>

<p><strong>PAYMENT / FEE</strong></p>
<ul>
<li>Subscriptions may include recurring fees starting from the initial membership enrollment.</li>
<li>Subscribers are responsible for these fees as outlined in this Agreement.</li>
</ul>

<p><strong>AUTOMATIC RECURRING BILLING (If Selected)</strong></p>
<ul>
<li>Subscriptions may automatically renew at the end of the initial term unless canceled by the Subscriber.</li>
<li>By subscribing, you authorize Verotel to charge your payment method for ongoing subscription fees and additional purchases.</li>
<li>In case of failed payment, retries will occur for up to one month, with an administration fee of up to \$3.00 applied.</li>
</ul>

<p><strong>ELECTRONIC RECEIPT</strong></p>
<ul>
<li>Subscribers will receive an email receipt upon initial subscription.</li>
<li>Requests for charge records must be made directly through customer support.</li>
</ul>

<p><strong>CANCELLATION</strong></p>
<ul>
<li>Either the site or the Subscriber may cancel the subscription at any time.</li>
<li>Subscribers remain liable for any charges incurred up to the date of cancellation.</li>
<li>For cancellations or billing inquiries, contact Verotel.</li>
</ul>

<p><strong>REFUNDS</strong></p>
<ul>
<li>Refunds may be requested through customer support.</li>
<li>Refunds or credits for partially used memberships are not guaranteed.</li>
<li>Refunds will only be issued to the original payment method.</li>
</ul>

<p><strong>CARDHOLDER DISPUTES/CHARGEBACKS</strong></p>
<ul>
<li>Chargebacks will be reviewed thoroughly. Accounts with unwarranted chargebacks may face restrictions.</li>
<li>Fraudulent claims will be reported to protect against future unauthorized charges.</li>
</ul>

<p><strong>AUTHORIZATION OF USE</strong></p>
<ul>
<li>Subscriptions are for personal use only and may not be transferred or shared.</li>
<li>Unauthorized access or sharing of login credentials constitutes a breach of this Agreement and may result in termination.</li>
</ul>

<p><strong>SANCTION AND APPROVAL OF ADULT MATERIAL</strong></p>
<p>This site contains adult material. Access is restricted to individuals over 18 years of age or the legal age of majority in their jurisdiction.</p>

<p><strong>ABUSIVE OR ILLEGAL CONTENT REMOVAL REQUEST</strong></p>
<p>To report content that is abusive or illegal, complete the form provided on the site.</p>
<ul>
<li>Non-consensual use of an image and/or illegal content will be addressed within 24 hours.</li>
<li>All other requests will be resolved within 7 business days from your initial submission. Once your request has been placed, our legal and compliance team will carefully review it and get back to you if additional details are required. Appeals to our decision will be handled promptly through the same channel.</p>

<p>In the event the request is valid, we will immediately remove the flagged content from our site and report it to our processing partner(s).</p>

<p>For content removal requests related to:</p>
<ul>
<li><strong>Copyright infringement</strong>: Please email us at <a href=\"mailto:{$contact_email}\">{$contact_email}</a></li>
<li><strong>Feedback or information</strong>: Please use <a href=\"mailto:{$support_email}\">{$support_email}</a></li>
</ul>";

    return $content;
}

/**
 * Generate dynamic content removal page content with site-specific variables
 *
 * @return string Complete content removal page content with dynamic variables
 */
function flexpress_generate_content_removal_content() {
    // Get dynamic variables
    $site_name = get_bloginfo('name');
    $site_domain = parse_url(home_url(), PHP_URL_HOST);
    
    // Get contact information
    $contact_email = flexpress_get_contact_email('contact');
    $support_email = flexpress_get_contact_email('support');
    
    // Use defaults if not set
    if (empty($contact_email)) {
        $contact_email = 'legal@' . $site_domain;
    }
    if (empty($support_email)) {
        $support_email = 'support@' . $site_domain;
    }
    
    // Generate content removal page content
    $content = "<p>We take all of your requests very seriously, especially when you request content removal. Our support team is dedicated and works diligently on the removal of content that violates our Terms of Service.</p>

<p>We will treat your report confidentially and may only share it anonymously with credit card processors and acquirers in cases where we are legally obliged to do so.</p>

<p><strong>ARE YOU DEPICTED IN A VIDEO? PLEASE USE THIS FORM AS WELL.</strong></p>

<p>Please complete the form below should you be the victim, or come across content that you have personal knowledge of, constituting:</p>
<ul>
<li>Non-consensual production and/or distribution of your image (including but not limited to: revenge porn, blackmail, exploitation)</li>
<li>Content that reveals personally identifiable information (such as name, email address, phone number, IP address)</li>
<li>Otherwise abusive and/or illegal content</li>
</ul>

<p>All requests will be reviewed and resolved within the following timeframes: non-consensual use of an image and/or illegal content will be addressed within 24 hours, while all other requests can be resolved within 7 business days from your initial submission. Once your request has been placed, our legal and compliance team will carefully review it and get back to you if additional details are required. Appeals to our decision will be handled promptly through the same channel.</p>

<p>In the event the request is valid, we will immediately remove the flagged content from our site and report it to our processing partner(s).</p>

<p>For content removal requests related to:</p>
<ul>
<li><strong>Copyright infringement</strong>: Please email us at <a href=\"mailto:{$contact_email}\">{$contact_email}</a></li>
<li><strong>Feedback or information</strong>: Please use <a href=\"mailto:{$support_email}\">{$support_email}</a></li>
</ul>";

    return $content;
}

/**
 * Regenerate legal page content with updated settings
 *
 * @param string $page_type Either 'privacy' or 'terms'
 * @return bool True if successful, false otherwise
 */
function flexpress_regenerate_legal_page_content($page_type = 'both') {
    $pages_to_update = array();
    
    if ($page_type === 'privacy' || $page_type === 'both') {
        $privacy_page = get_page_by_title('Privacy Policy');
        if ($privacy_page) {
            $pages_to_update['privacy'] = array(
                'id' => $privacy_page->ID,
                'content' => flexpress_generate_privacy_policy_content()
            );
        }
    }
    
    if ($page_type === 'terms' || $page_type === 'both') {
        $terms_page = get_page_by_title('Customer Terms & Conditions');
        if ($terms_page) {
            $pages_to_update['terms'] = array(
                'id' => $terms_page->ID,
                'content' => flexpress_generate_terms_conditions_content()
            );
        }
    }
    
    if ($page_type === '2257' || $page_type === 'both') {
        $compliance_page = get_page_by_title('2257 Compliance');
        if ($compliance_page) {
            $pages_to_update['2257'] = array(
                'id' => $compliance_page->ID,
                'content' => flexpress_generate_2257_compliance_content()
            );
        }
    }
    
    if ($page_type === 'customer_terms' || $page_type === 'both') {
        $customer_terms_page = get_page_by_title('Customer Terms & Conditions');
        if ($customer_terms_page) {
            $pages_to_update['customer_terms'] = array(
                'id' => $customer_terms_page->ID,
                'content' => flexpress_generate_customer_terms_content()
            );
        }
    }
    
    if ($page_type === 'anti_slavery' || $page_type === 'both') {
        $anti_slavery_page = get_page_by_title('Anti-Slavery and Human Trafficking Policy');
        if ($anti_slavery_page) {
            $pages_to_update['anti_slavery'] = array(
                'id' => $anti_slavery_page->ID,
                'content' => flexpress_generate_anti_slavery_content()
            );
        }
    }
    
    if ($page_type === 'content_removal' || $page_type === 'both') {
        $content_removal_page = get_page_by_title('Content Removal');
        if ($content_removal_page) {
            $pages_to_update['content_removal'] = array(
                'id' => $content_removal_page->ID,
                'content' => flexpress_generate_content_removal_content()
            );
        }
    }
    
    $success = true;
    foreach ($pages_to_update as $type => $page_data) {
        $result = wp_update_post(array(
            'ID' => $page_data['id'],
            'post_content' => $page_data['content']
        ));
        
        if (is_wp_error($result) || $result === 0) {
            $success = false;
            error_log("Failed to update {$type} page content: " . ($result ? $result->get_error_message() : 'Unknown error'));
        }
    }
    
    return $success;
}



/**
 * Add admin action to regenerate legal page content
 */
function flexpress_add_regenerate_legal_content_action() {
    add_action('admin_post_regenerate_legal_content', 'flexpress_handle_regenerate_legal_content');
}
add_action('admin_init', 'flexpress_add_regenerate_legal_content_action');

/**
 * Handle regeneration of legal page content
 */
function flexpress_handle_regenerate_legal_content() {
    // Verify nonce
    if (!isset($_POST['regenerate_legal_nonce']) || !wp_verify_nonce($_POST['regenerate_legal_nonce'], 'regenerate_legal_content')) {
        wp_die(__('Security check failed', 'flexpress'));
    }
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions', 'flexpress'));
    }
    
    $page_type = isset($_POST['page_type']) ? sanitize_text_field($_POST['page_type']) : 'both';
    $success = flexpress_regenerate_legal_page_content($page_type);
    
    $redirect_url = add_query_arg(array(
        'page' => 'flexpress-contact-settings',
        'regenerated' => $success ? 'success' : 'error'
    ), admin_url('admin.php'));
    
    wp_redirect($redirect_url);
    exit;
}

/**
 * Set models archive to show 12 models per page
 */
function flexpress_modify_models_per_page($query) {
    if (!is_admin() && $query->is_main_query()) {
        if (is_post_type_archive('model')) {
            $query->set('posts_per_page', 12);
        }
    }
}
add_action('pre_get_posts', 'flexpress_modify_models_per_page');

/**
 * AJAX handler for loading more models
 */
function flexpress_load_more_models() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'load_more_models')) {
        wp_die('Security check failed');
    }
    
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    
    $args = array(
        'post_type' => 'model',
        'posts_per_page' => 12,
        'paged' => $page,
        'post_status' => 'publish'
    );
    
    $models_query = new WP_Query($args);
    
    if ($models_query->have_posts()) {
        $html = '';
        while ($models_query->have_posts()) {
            $models_query->the_post();
            
            $html .= '<div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">';
            ob_start();
            get_template_part('template-parts/content-model/card');
            $html .= ob_get_clean();
            $html .= '</div>';
        }
        
        wp_reset_postdata();
        
        $response = array(
            'success' => true,
            'html' => $html,
            'has_more' => $page < $models_query->max_num_pages
        );
    } else {
        $response = array(
            'success' => false,
            'html' => '',
            'has_more' => false
        );
    }
    
    wp_send_json($response);
}
add_action('wp_ajax_load_more_models', 'flexpress_load_more_models');
add_action('wp_ajax_nopriv_load_more_models', 'flexpress_load_more_models');

/**
 * Get models for homepage display
 * 
 * @param int $count Number of models to retrieve
 * @param bool $featured_only Whether to only get featured models
 * @return WP_Query Query object with models
 */
function flexpress_get_homepage_models($count = 8, $featured_only = false) {
    $meta_query = array(
        array(
            'key' => 'model_show_on_homepage',
            'value' => '1',
            'compare' => '='
        )
    );
    
    if ($featured_only) {
        $meta_query[] = array(
            'key' => 'model_featured',
            'value' => '1',
            'compare' => '='
        );
    }
    
    $args = array(
        'post_type' => 'model',
        'posts_per_page' => $count,
        'post_status' => 'publish',
        'meta_query' => $meta_query,
        'orderby' => 'menu_order title',
        'order' => 'ASC'
    );
    
    return new WP_Query($args);
}

/**
 * Get models by gender
 * 
 * @param string $gender Gender to filter by (female, male, trans, non-binary, other)
 * @param int $count Number of models to retrieve
 * @return WP_Query Query object with models
 */
function flexpress_get_models_by_gender($gender, $count = -1) {
    $args = array(
        'post_type' => 'model',
        'posts_per_page' => $count,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'model_gender',
                'value' => $gender,
                'compare' => '='
            ),
            array(
                'key' => 'model_show_on_homepage',
                'value' => '1',
                'compare' => '='
            )
        ),
        'orderby' => 'menu_order title',
        'order' => 'ASC'
    );
    
    return new WP_Query($args);
}

/**
 * Get model social media links as array
 * 
 * @param int $model_id Model post ID (optional, uses current post if not provided)
 * @return array Array of social media links
 */
function flexpress_get_model_social_links($model_id = null) {
    if (!$model_id) {
        $model_id = get_the_ID();
    }
    
    $social_links = array();
    
    // Instagram
    if ($instagram = get_field('model_instagram', $model_id)) {
        $social_links['instagram'] = array(
            'url' => $instagram,
            'label' => 'Instagram',
            'icon' => 'fab fa-instagram'
        );
    }
    
    // Twitter/X
    if ($twitter = get_field('model_twitter', $model_id)) {
        $social_links['twitter'] = array(
            'url' => $twitter,
            'label' => 'Twitter/X',
            'icon' => 'fab fa-x-twitter'
        );
    }
    
    // TikTok
    if ($tiktok = get_field('model_tiktok', $model_id)) {
        $social_links['tiktok'] = array(
            'url' => $tiktok,
            'label' => 'TikTok',
            'icon' => 'fab fa-tiktok'
        );
    }
    
    // OnlyFans
    if ($onlyfans = get_field('model_onlyfans', $model_id)) {
        $social_links['onlyfans'] = array(
            'url' => $onlyfans,
            'label' => 'OnlyFans',
            'icon' => 'fas fa-heart'
        );
    }
    
    // Website
    if ($website = get_field('model_website', $model_id)) {
        $website_title = get_field('model_website_title', $model_id);
        $social_links['website'] = array(
            'url' => $website,
            'label' => $website_title ? $website_title : 'Website',
            'icon' => 'fas fa-globe'
        );
    }
    
    return $social_links;
}

/**
 * Custom comment callback for model messages
 * 
 * @param object $comment Comment object
 * @param array $args Comment arguments
 * @param int $depth Comment depth
 */
function flexpress_model_message_callback($comment, $args, $depth) {
    if ('div' === $args['style']) {
        $tag       = 'div';
        $add_below = 'comment';
    } else {
        $tag       = 'li';
        $add_below = 'div-comment';
    }
    ?>
    <<?php echo $tag; ?> <?php comment_class(empty($args['has_children']) ? '' : 'parent'); ?> id="comment-<?php comment_ID(); ?>">
        <?php if ('div' !== $args['style']) : ?>
            <div id="div-comment-<?php comment_ID(); ?>" class="comment-body model-message">
        <?php endif; ?>
        
        <div class="comment-author model-message-author">
            <div class="comment-meta commentmetadata">
                <cite class="fn"><?php 
                    $comment_user_id = get_comment(get_comment_ID())->user_id;
                    if ($comment_user_id && function_exists('flexpress_get_user_display_name')) {
                        echo esc_html(flexpress_get_user_display_name($comment_user_id));
                    } else {
                        echo get_comment_author();
                    }
                ?></cite>
                <time datetime="<?php echo esc_attr(get_comment_date('c')); ?>" class="comment-date">
                    <?php echo get_comment_date(); ?> at <?php echo get_comment_time(); ?>
                </time>
                <?php comment_reply_link(array_merge($args, array(
                    'add_below' => $add_below,
                    'depth'     => $depth,
                    'max_depth' => $args['max_depth'],
                    'reply_text' => 'Reply'
                ))); ?>
            </div>
        </div>

        <div class="comment-content model-message-content">
            <?php if ($comment->comment_approved == '0') : ?>
                <em class="comment-awaiting-moderation">Your message is awaiting moderation.</em>
            <?php endif; ?>
            <?php comment_text(); ?>
        </div>

        <?php if ('div' !== $args['style']) : ?>
            </div>
        <?php endif; ?>
    <?php
}

/**
 * Get user display name with fallback options
 * 
 * @param int $user_id User ID (optional, uses current user if not provided)
 * @return string Display name
 */
function flexpress_get_user_display_name($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return '';
    }
    
    // Try custom display name first
    $display_name = get_user_meta($user_id, 'flexpress_display_name', true);
    
    if (!empty($display_name)) {
        return $display_name;
    }
    
    // Fallback to first + last name
    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name = get_user_meta($user_id, 'last_name', true);
    
    if (!empty($first_name) && !empty($last_name)) {
        return $first_name . ' ' . $last_name;
    }
    
    if (!empty($first_name)) {
        return $first_name;
    }
    
    // Final fallback to WordPress display name
    $user = get_user_by('id', $user_id);
    if ($user) {
        return $user->display_name;
    }
    
    return 'Member';
}

/**
 * Update user display name
 * 
 * @param int $user_id User ID
 * @param string $display_name Display name
 * @return bool Success status
 */
function flexpress_update_user_display_name($user_id, $display_name) {
    $display_name = sanitize_text_field(trim($display_name));
    
    if (empty($display_name)) {
        // If empty, remove custom display name (will fall back to first/last name)
        return delete_user_meta($user_id, 'flexpress_display_name');
    }
    
    return update_user_meta($user_id, 'flexpress_display_name', $display_name);
}

/**
 * AJAX handler for profile updates
 */
function flexpress_ajax_update_profile() {
    // Security check
    if (!check_ajax_referer('flexpress_dashboard_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => __('Security check failed.', 'flexpress')));
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to update your profile.', 'flexpress')));
        return;
    }
    
    $user_id = get_current_user_id();
    $errors = array();
    $success_messages = array();
    
    // Validate and update first name
    if (isset($_POST['first_name'])) {
        $first_name = sanitize_text_field($_POST['first_name']);
        if (strlen($first_name) > 50) {
            $errors[] = __('First name must be 50 characters or less.', 'flexpress');
        } else {
            update_user_meta($user_id, 'first_name', $first_name);
            $success_messages[] = __('First name updated.', 'flexpress');
        }
    }
    
    // Validate and update last name
    if (isset($_POST['last_name'])) {
        $last_name = sanitize_text_field($_POST['last_name']);
        if (strlen($last_name) > 50) {
            $errors[] = __('Last name must be 50 characters or less.', 'flexpress');
        } else {
            update_user_meta($user_id, 'last_name', $last_name);
            $success_messages[] = __('Last name updated.', 'flexpress');
        }
    }
    
    // Validate and update display name
    if (isset($_POST['display_name'])) {
        $display_name = sanitize_text_field($_POST['display_name']);
        if (strlen($display_name) > 100) {
            $errors[] = __('Display name must be 100 characters or less.', 'flexpress');
        } else {
            flexpress_update_user_display_name($user_id, $display_name);
            $success_messages[] = __('Display name updated.', 'flexpress');
        }
    }
    
    // Validate and update email
    if (isset($_POST['email'])) {
        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            $errors[] = __('Please enter a valid email address.', 'flexpress');
        } else {
            $user = get_user_by('id', $user_id);
            if ($user && $user->user_email !== $email) {
                // Check if email is already in use
                if (email_exists($email)) {
                    $errors[] = __('This email address is already in use by another account.', 'flexpress');
                } else {
                    $result = wp_update_user(array(
                        'ID' => $user_id,
                        'user_email' => $email
                    ));
                    
                    if (is_wp_error($result)) {
                        $errors[] = $result->get_error_message();
                    } else {
                        $success_messages[] = __('Email address updated.', 'flexpress');
                    }
                }
            }
        }
    }
    
    if (!empty($errors)) {
        wp_send_json_error(array(
            'message' => implode(' ', $errors),
            'errors' => $errors
        ));
    } else {
        wp_send_json_success(array(
            'message' => !empty($success_messages) ? implode(' ', $success_messages) : __('Profile updated successfully.', 'flexpress'),
            'success_messages' => $success_messages
        ));
    }
}
add_action('wp_ajax_flexpress_update_profile', 'flexpress_ajax_update_profile');

/**
 * Filter comment form logged in text to show display name instead of email
 */
function flexpress_comment_form_logged_in_text($args, $post_id) {
    if (!is_user_logged_in()) {
        return $args;
    }
    
    $user = wp_get_current_user();
    
    // Safe display name retrieval with fallbacks
    $display_name = '';
    if (function_exists('flexpress_get_user_display_name')) {
        $display_name = flexpress_get_user_display_name($user->ID);
    } else {
        // Fallback if function doesn't exist yet
        $custom_name = get_user_meta($user->ID, 'flexpress_display_name', true);
        if (!empty($custom_name)) {
            $display_name = $custom_name;
        } else {
            $first_name = get_user_meta($user->ID, 'first_name', true);
            $last_name = get_user_meta($user->ID, 'last_name', true);
            if (!empty($first_name) && !empty($last_name)) {
                $display_name = $first_name . ' ' . $last_name;
            } else {
                $display_name = $user->display_name ?: 'Member';
            }
        }
    }
    
    // Create custom logged in text with display name and edit profile link
    $edit_profile_url = home_url('/dashboard/');
    
    $args['logged_in_as'] = sprintf(
        '<p class="logged-in-as">%s <a href="%s" aria-label="%s">%s</a>. <a href="%s">%s</a></p>',
        sprintf(__('Logged in as %s.'), '<strong>' . esc_html($display_name) . '</strong>'),
        esc_url($edit_profile_url),
        esc_attr(__('Edit your profile')),
        __('Edit your profile'),
        esc_url(wp_logout_url(apply_filters('the_permalink', get_permalink($post_id), $post_id))),
        __('Log out?')
    );
    
    return $args;
}
// Temporarily disabled - uncomment when ready to test
// add_filter('comment_form_defaults', 'flexpress_comment_form_logged_in_text', 10, 2);

/**
 * Filter comment author name to use display name for logged in users
 */
function flexpress_comment_author_display_name($comment_author, $comment_id) {
    $comment = get_comment($comment_id);
    
    if ($comment && $comment->user_id) {
        // Safe display name retrieval
        if (function_exists('flexpress_get_user_display_name')) {
            $display_name = flexpress_get_user_display_name($comment->user_id);
            if (!empty($display_name)) {
                return $display_name;
            }
        } else {
            // Fallback logic
            $custom_name = get_user_meta($comment->user_id, 'flexpress_display_name', true);
            if (!empty($custom_name)) {
                return $custom_name;
            }
        }
    }
    
    return $comment_author;
}
// Temporarily disabled - uncomment when ready to test
// add_filter('get_comment_author', 'flexpress_comment_author_display_name', 10, 2);

/**
 * Set comment author to display name when comment is submitted
 */
function flexpress_set_comment_author_name($commentdata) {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        
        // Safe display name retrieval
        $display_name = '';
        if (function_exists('flexpress_get_user_display_name')) {
            $display_name = flexpress_get_user_display_name($user_id);
        } else {
            // Fallback logic
            $custom_name = get_user_meta($user_id, 'flexpress_display_name', true);
            if (!empty($custom_name)) {
                $display_name = $custom_name;
            }
        }
        
        if (!empty($display_name)) {
            $commentdata['comment_author'] = $display_name;
        }
    }
    
    return $commentdata;
}
// Temporarily disabled - uncomment when ready to test
// add_filter('preprocess_comment', 'flexpress_set_comment_author_name');

/**
 * Redirect WordPress admin profile page to custom dashboard
 */
function flexpress_redirect_admin_profile() {
    global $pagenow;
    
    // Check if we're on the profile page in admin
    if ($pagenow === 'profile.php' && !current_user_can('administrator')) {
        wp_redirect(home_url('/dashboard/'));
        exit;
    }
}
add_action('admin_init', 'flexpress_redirect_admin_profile');

/**
 * Add rewrite rules for promo code URLs
 */
function flexpress_add_promo_url_rewrites() {
    // Add rewrite rule for /join/{promo_code}
    add_rewrite_rule(
        '^join/([^/]+)/?$',
        'index.php?pagename=join&promo=$matches[1]',
        'top'
    );
}
add_action('init', 'flexpress_add_promo_url_rewrites');

/**
 * Add query vars for promo codes
 */
function flexpress_add_promo_query_vars($vars) {
    $vars[] = 'promo';
    return $vars;
}
add_filter('query_vars', 'flexpress_add_promo_query_vars');

/**
 * AJAX handler for promo code validation
 */
function flexpress_ajax_validate_promo_code() {
    check_ajax_referer('flexpress_promo_code', 'nonce');
    
    $promo_code = sanitize_text_field($_POST['promo_code'] ?? '');
    $result = flexpress_validate_promo_code($promo_code);
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_flexpress_validate_promo_code', 'flexpress_ajax_validate_promo_code');
add_action('wp_ajax_nopriv_flexpress_validate_promo_code', 'flexpress_ajax_validate_promo_code');

/**
 * Create promo usage tracking table
 */
function flexpress_create_promo_usage_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_promo_usage';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        promo_code varchar(50) NOT NULL,
        user_id int(11) NOT NULL,
        plan_id varchar(50) NOT NULL,
        transaction_id varchar(100) NOT NULL,
        used_at datetime NOT NULL,
        ip_address varchar(45) NOT NULL,
        PRIMARY KEY (id),
        KEY promo_code_idx (promo_code),
        KEY user_id_idx (user_id),
        KEY used_at_idx (used_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Initialize promo system on theme activation
 */
function flexpress_init_promo_system() {
    flexpress_create_promo_usage_table();
    
    // Create default promo plans if they don't exist
    $plans = get_option('flexpress_pricing_plans', array());
    $has_promo_plans = false;
    
    foreach ($plans as $plan) {
        if (!empty($plan['promo_only'])) {
            $has_promo_plans = true;
            break;
        }
    }
    
    if (!$has_promo_plans) {
        flexpress_create_default_promo_plans();
    }
}
add_action('after_switch_theme', 'flexpress_init_promo_system');

/**
 * Create default promotional plans
 */
function flexpress_create_default_promo_plans() {
    $existing_plans = get_option('flexpress_pricing_plans', array());
    
    $promo_plans = array(
        'promo_model_monthly' => array(
            'name' => 'Model Special - Monthly',
            'description' => 'Exclusive model discount - Monthly access',
            'price' => 19.95,
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
            'promo_only' => 1,
            'promo_codes' => 'model1,model2,model3', // Example codes
            'verotel_site_id' => '',
            'verotel_product_id' => 'promo_monthly',
            'sort_order' => 10,
        ),
        'promo_model_annual' => array(
            'name' => 'Model Special - Annual',
            'description' => 'Exclusive model discount - Annual access (60% off!)',
            'price' => 99.95,
            'currency' => '$',
            'duration' => 365,
            'duration_unit' => 'days',
            'plan_type' => 'recurring',
            'trial_enabled' => 0,
            'trial_price' => 0,
            'trial_duration' => 0,
            'trial_duration_unit' => 'days',
            'featured' => 0,
            'active' => 1,
            'promo_only' => 1,
            'promo_codes' => 'model1,model2,model3,annual50', // Example codes
            'verotel_site_id' => '',
            'verotel_product_id' => 'promo_annual',
            'sort_order' => 11,
        ),
    );
    
    $updated_plans = array_merge($existing_plans, $promo_plans);
    update_option('flexpress_pricing_plans', $updated_plans);
}

/**
 * AJAX: Check affiliate code availability
 */
function flexpress_check_affiliate_code_availability() {
    check_ajax_referer('flexpress_affiliate_frontend_nonce', 'nonce');
    $code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';
    if (!$code) {
        wp_send_json_error(['message' => 'No code provided.']);
    }
    $affiliate = flexpress_get_affiliate_by_code($code);
    if ($affiliate) {
        wp_send_json_success(['available' => false]);
    } else {
        wp_send_json_success(['available' => true]);
    }
}
add_action('wp_ajax_flexpress_check_affiliate_code_availability', 'flexpress_check_affiliate_code_availability');
add_action('wp_ajax_nopriv_flexpress_check_affiliate_code_availability', 'flexpress_check_affiliate_code_availability');

/**
 * AJAX: Generate affiliate code suggestion
 */
function flexpress_generate_affiliate_code_suggestion() {
    check_ajax_referer('flexpress_affiliate_frontend_nonce', 'nonce');
    $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
    if (!$display_name) {
        wp_send_json_error(['message' => 'No display name provided.']);
    }
    $suggested = flexpress_generate_affiliate_code($display_name);
    wp_send_json_success(['suggested_code' => $suggested]);
}
add_action('wp_ajax_flexpress_generate_affiliate_code_suggestion', 'flexpress_generate_affiliate_code_suggestion');
add_action('wp_ajax_nopriv_flexpress_generate_affiliate_code_suggestion', 'flexpress_generate_affiliate_code_suggestion');

/**
 * AJAX: Get affiliate dashboard data (refresh)
 */
function flexpress_get_affiliate_dashboard_data_ajax() {
    check_ajax_referer('flexpress_affiliate_frontend_nonce', 'nonce');
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'Not logged in.']);
    }
    global $wpdb;
    $affiliate = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}flexpress_affiliates WHERE user_id = %d OR email = %s",
        $user_id,
        wp_get_current_user()->user_email
    ));
    if (!$affiliate) {
        wp_send_json_error(['message' => 'Affiliate not found.']);
    }
    $dashboard_data = flexpress_get_affiliate_dashboard_data($affiliate->id);
    wp_send_json_success($dashboard_data);
}
add_action('wp_ajax_flexpress_get_affiliate_dashboard_data', 'flexpress_get_affiliate_dashboard_data_ajax');
// No nopriv version: dashboard is for logged-in users only

/**
 * Flowguard AJAX Handlers
 */

/**
 * AJAX: Create Flowguard subscription
 */
function flexpress_create_flowguard_subscription_ajax() {
    check_ajax_referer('flowguard_payment', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }
    
    $plan_id = intval($_POST['plan_id'] ?? 0);
    if (!$plan_id) {
        wp_send_json_error('Invalid plan ID');
    }
    
    $user_id = get_current_user_id();
    $result = flexpress_flowguard_create_subscription($user_id, $plan_id);
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result['error']);
    }
}
add_action('wp_ajax_create_flowguard_subscription', 'flexpress_create_flowguard_subscription_ajax');
add_action('wp_ajax_nopriv_create_flowguard_subscription', 'flexpress_create_flowguard_subscription_ajax');

/**
 * AJAX: Create Flowguard PPV purchase
 */
function flexpress_create_flowguard_ppv_purchase_ajax() {
    check_ajax_referer('flowguard_payment', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }
    
    $episode_id = intval($_POST['episode_id'] ?? 0);
    if (!$episode_id) {
        wp_send_json_error('Invalid episode ID');
    }
    
    $user_id = get_current_user_id();
    $result = flexpress_flowguard_create_ppv_purchase($user_id, $episode_id);
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result['error']);
    }
}
add_action('wp_ajax_create_flowguard_ppv_purchase', 'flexpress_create_flowguard_ppv_purchase_ajax');
add_action('wp_ajax_nopriv_create_flowguard_ppv_purchase', 'flexpress_create_flowguard_ppv_purchase_ajax');

/**
 * AJAX: Apply promo code
 */
function flexpress_apply_promo_code_ajax() {
    check_ajax_referer('flowguard_payment', 'nonce');
    
    $promo_code = sanitize_text_field($_POST['promo_code'] ?? '');
    if (!$promo_code) {
        wp_send_json_error('No promo code provided');
    }
    
    // Get pricing plans
    $pricing_plans = flexpress_get_pricing_plans();
    
    // Check if promo code matches any plan
    foreach ($pricing_plans as $plan) {
        if (strtolower($plan['name']) === strtolower($promo_code)) {
            wp_send_json_success([
                'plan_id' => $plan['id'],
                'plan_name' => $plan['name'],
                'price' => $plan['price'],
                'currency' => $plan['currency'],
                'description' => $plan['description']
            ]);
        }
    }
    
    wp_send_json_error('Invalid promo code');
}
add_action('wp_ajax_apply_promo_code', 'flexpress_apply_promo_code_ajax');
add_action('wp_ajax_nopriv_apply_promo_code', 'flexpress_apply_promo_code_ajax');

/**
 * Include ACF field groups
 */
// Include existing ACF field groups

// Include Join Page Carousel ACF fields
require_once get_template_directory() . '/includes/acf/join-carousel-fields.php';

/**
 * Include the Join Page Carousel functionality
 */
require_once get_template_directory() . '/includes/class-flexpress-join-carousel.php';

// Temporary: Force create default pricing plans on next admin page load
add_action('admin_init', function() {
    if (isset($_GET['create_default_plans']) && current_user_can('manage_options')) {
        if (function_exists('flexpress_force_create_default_pricing_plans')) {
            flexpress_force_create_default_pricing_plans();
            wp_redirect(admin_url('admin.php?page=flexpress-pricing-settings&created=1'));
            exit;
        }
    }
    
    if (isset($_GET['created']) && $_GET['created'] == '1') {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Default pricing plans created successfully!</p></div>';
        });
    }
});

/**
 * AJAX handler for manual auto-setup
 */
function flexpress_ajax_manual_auto_setup() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'flexpress_manual_setup')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $result = flexpress_manual_auto_setup();
    
    if ($result['success']) {
        wp_send_json_success($result['message']);
    } else {
        wp_send_json_error($result['message']);
    }
}
add_action('wp_ajax_flexpress_manual_auto_setup', 'flexpress_ajax_manual_auto_setup');

/**
 * AJAX handler for resetting setup status
 */
function flexpress_ajax_reset_setup_status() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'flexpress_reset_status')) {
        wp_send_json_error('Invalid nonce');
    }
    
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    // Reset setup status
    delete_option('flexpress_auto_setup_completed');
    delete_option('flexpress_auto_setup_date');
    delete_option('flexpress_auto_setup_results');
    delete_transient('flexpress_auto_setup_running');
    delete_transient('flexpress_last_skip_log');
    
    wp_send_json_success('Setup status reset successfully. Auto-setup can now run again.');
}
