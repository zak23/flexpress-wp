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
                    <h1 class="cta-title"><?php echo esc_html(sprintf(__('Experience Pure Pleasure with %s', 'flexpress'), $site_name)); ?></h1>
                    <p class="cta-subtitle"><?php esc_html_e('Unlock exclusive access to premium adult content and intimate experiences', 'flexpress'); ?></p>
                </div>

                <!-- Features List -->
                <div class="cta-features">
                    <ul class="features-list">
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
                    </ul>
                </div>

                <!-- CTA Section -->
                <div class="cta-actions">
                    <h2 class="cta-offer"><?php esc_html_e('Join Now and Save 50% Off Your First Month!', 'flexpress'); ?></h2>
                    <a href="<?php echo esc_url(home_url('/join')); ?>"
                        class="btn btn-cta-primary btn-lg"
                        role="button">
                        <?php esc_html_e('Get Instant Access', 'flexpress'); ?> <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                    <p class="cta-security">
                        <small><?php esc_html_e('Secure payment • Complete privacy • Cancel anytime', 'flexpress'); ?></small>
                    </p>
                    <p class="cta-login">
                        <?php esc_html_e('ALREADY HAVE AN ACCOUNT?', 'flexpress'); ?><br>
                        <a href="<?php echo esc_url(home_url('/login')); ?>" class="cta-login-link">
                            <?php esc_html_e('LOG IN NOW', 'flexpress'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>