<?php

/**
 * Join Now CTA Section Template
 * 
 * Displays a compelling call-to-action section to encourage user registration
 * with features list and promotional offer.
 */

// Hide join now CTA for users with active or cancelled memberships
if (function_exists('flexpress_has_active_membership') && flexpress_has_active_membership()) {
    return;
}

// Get CTA image URL - try to use FlexPress settings first, then fallback to optimized default
$flexpress_options = get_option('flexpress_general_settings', array());
$join_cta_image_id = isset($flexpress_options['join_cta_image']) ? $flexpress_options['join_cta_image'] : '';


if (!empty($join_cta_image_id)) {
    // Use the uploaded image with proper sizing
    $cta_image_url = wp_get_attachment_image_url($join_cta_image_id, 'large');
    if (!$cta_image_url) {
        // Fallback to full size if large doesn't exist
        $cta_image_url = wp_get_attachment_image_url($join_cta_image_id, 'full');
    }
} else {
    // Legacy fallback: support existing Customizer setting if present
    $legacy_cta_url = get_theme_mod('join_cta_image_url', '');
    if (!empty($legacy_cta_url)) {
        $cta_image_url = $legacy_cta_url;
    } else {
        // Fallback to default image
        $cta_image_url = home_url('/wp-content/uploads/2025/06/002-Zak_Mercedes-Green-scaled-800x1200.jpg');
    }
}

// Ensure we have a valid image URL
if (empty($cta_image_url)) {
    $cta_image_url = home_url('/wp-content/uploads/2025/06/002-Zak_Mercedes-Green-scaled-800x1200.jpg');
}

// Get site name for dynamic content
$site_name = get_bloginfo('name');
if (empty($site_name)) {
    $site_name = get_bloginfo('name') ?: 'Our Site';
}

// Pull customizable Join CTA content with safe fallbacks
$cta_headline = isset($flexpress_options['join_cta_headline']) && $flexpress_options['join_cta_headline'] !== ''
    ? $flexpress_options['join_cta_headline']
    : sprintf(__('Experience Pure Pleasure with %s', 'flexpress'), $site_name);

$cta_subtitle = isset($flexpress_options['join_cta_subtitle']) && $flexpress_options['join_cta_subtitle'] !== ''
    ? $flexpress_options['join_cta_subtitle']
    : __('Unlock exclusive access to premium adult content and intimate experiences', 'flexpress');

$cta_features = array();
if (isset($flexpress_options['join_cta_features']) && is_array($flexpress_options['join_cta_features'])) {
    foreach ($flexpress_options['join_cta_features'] as $feature_text) {
        $feature_text = trim((string) $feature_text);
        if ($feature_text !== '') {
            $cta_features[] = $feature_text;
        }
    }
}

$cta_offer_text = isset($flexpress_options['join_cta_offer_text']) && $flexpress_options['join_cta_offer_text'] !== ''
    ? $flexpress_options['join_cta_offer_text']
    : __('Use promo code WELCOME50 and Save 50% Off Your First Month!', 'flexpress');

$cta_button_text = isset($flexpress_options['join_cta_button_text']) && $flexpress_options['join_cta_button_text'] !== ''
    ? $flexpress_options['join_cta_button_text']
    : __('Get Instant Access', 'flexpress');

$cta_button_url = isset($flexpress_options['join_cta_button_url']) && $flexpress_options['join_cta_button_url'] !== ''
    ? $flexpress_options['join_cta_button_url']
    : home_url('/join?promo=welcome50');

$cta_security_text = isset($flexpress_options['join_cta_security_text']) && $flexpress_options['join_cta_security_text'] !== ''
    ? $flexpress_options['join_cta_security_text']
    : __('Secure payment • Complete privacy • Cancel anytime', 'flexpress');

$cta_login_prompt = isset($flexpress_options['join_cta_login_prompt']) && $flexpress_options['join_cta_login_prompt'] !== ''
    ? $flexpress_options['join_cta_login_prompt']
    : __('ALREADY HAVE AN ACCOUNT?', 'flexpress');

$cta_login_text = isset($flexpress_options['join_cta_login_text']) && $flexpress_options['join_cta_login_text'] !== ''
    ? $flexpress_options['join_cta_login_text']
    : __('LOG IN NOW', 'flexpress');

$cta_login_url = isset($flexpress_options['join_cta_login_url']) && $flexpress_options['join_cta_login_url'] !== ''
    ? $flexpress_options['join_cta_login_url']
    : home_url('/login');

// Example Image Optimizer parameters
$optimizer_params = [
    'width'   => 650,       // resize to 800px wide
    'format'  => 'webp',    // convert to webp for modern browsers
    'quality' => 75,        // compression quality
];

// Convert params to query string
$query_string = http_build_query($optimizer_params);

// Append Bunny CDN Image Optimizer query only if $cta_image_url is not empty
if (!empty($cta_image_url)) {
    // Use Static CDN Hostname from settings if provided
    $video_settings = get_option('flexpress_video_settings', array());
    $cdn_host = !empty($video_settings['bunnycdn_static_host']) ? $video_settings['bunnycdn_static_host'] : 'static.zakspov.com';
    // Remove any protocol part, just in case
    $cdn_host = preg_replace('#^https?://#', '', $cdn_host);
    $cta_image_url = str_replace(parse_url(home_url(), PHP_URL_HOST), $cdn_host, $cta_image_url);
    // Append query params
    $cta_image_url .= (strpos($cta_image_url, '?') === false ? '?' : '&') . $query_string;
}


?>


<div class="row g-0 join-now-cta">
    <!-- Image Column -->
    <div class="col-md-12 col-lg-6">
        <div class="cta-image-container">
            <img src="<?php echo esc_url($cta_image_url); ?>"
                alt="<?php echo esc_attr(sprintf(__('Join %s for Exclusive Content', 'flexpress'), $site_name)); ?>"
                class="cta-image">
        </div>
    </div>

    <!-- Content Column -->
    <div class="col-md-12 col-lg-6">
        <div class="cta-content-container">
            <div class="cta-content-wrapper">
                <!-- Main Headline -->
                <div class="cta-header">
                    <h1 class="cta-title"><?php echo esc_html($cta_headline); ?></h1>
                    <p class="cta-subtitle"><?php echo esc_html($cta_subtitle); ?></p>
                </div>

                <!-- Features List -->
                <div class="cta-features">
                    <ul class="features-list">
                        <?php if (!empty($cta_features)) : ?>
                            <?php foreach ($cta_features as $feature_text) : ?>
                                <li class="feature-item">
                                    <i class="fas fa-check feature-icon" aria-hidden="true"></i>
                                    <span class="feature-text"><?php echo esc_html($feature_text); ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <li class="feature-item">
                                <i class="fas fa-check feature-icon" aria-hidden="true"></i>
                                <span class="feature-text"><?php esc_html_e('Stunning HD videos and photo galleries updated weekly', 'flexpress'); ?></span>
                            </li>
                            <li class="feature-item">
                                <i class="fas fa-check feature-icon" aria-hidden="true"></i>
                                <span class="feature-text"><?php esc_html_e('Exclusive content you won\'t find anywhere else', 'flexpress'); ?></span>
                            </li>
                            <li class="feature-item">
                                <i class="fas fa-check feature-icon" aria-hidden="true"></i>
                                <span class="feature-text"><?php esc_html_e('Intimate behind-the-scenes moments', 'flexpress'); ?></span>
                            </li>
                            <li class="feature-item">
                                <i class="fas fa-check feature-icon" aria-hidden="true"></i>
                                <span class="feature-text"><?php esc_html_e('Professional performers and models', 'flexpress'); ?></span>
                            </li>
                            <li class="feature-item">
                                <i class="fas fa-check feature-icon" aria-hidden="true"></i>
                                <span class="feature-text"><?php esc_html_e('Secure payment and complete privacy protection', 'flexpress'); ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- CTA Section -->
                <div class="cta-actions">
                    <h2 class="cta-offer"><?php echo esc_html($cta_offer_text); ?></h2>
                    <a href="<?php echo esc_url($cta_button_url); ?>"
                        class="btn btn-cta-primary btn-lg"
                        role="button">
                        <?php echo esc_html($cta_button_text); ?> <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                    <p class="cta-security">
                        <small><?php echo esc_html($cta_security_text); ?></small>
                    </p>
                    <p class="cta-login">
                        <?php echo esc_html($cta_login_prompt); ?><br>
                        <a href="<?php echo esc_url($cta_login_url); ?>" class="cta-login-link">
                            <?php echo esc_html($cta_login_text); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>