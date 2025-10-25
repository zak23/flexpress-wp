<?php

/**
 * Featured Banner Template Part
 * 
 * Displays a customizable banner on the homepage below the featured episodes section.
 * The banner can be enabled/disabled and configured through FlexPress General Settings.
 */

// Get featured banner settings
$settings = get_option('flexpress_general_settings', array());
$enabled = isset($settings['featured_banner_enabled']) && $settings['featured_banner_enabled'];
$image_id = isset($settings['featured_banner_image']) ? $settings['featured_banner_image'] : '';
$url = isset($settings['featured_banner_url']) ? $settings['featured_banner_url'] : '';

// Only display if banner is enabled and has an image
if ($enabled && $image_id) {
    // Get image details
    $image_url = wp_get_attachment_image_url($image_id, 'full');
    $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);

    // Fallback alt text if none is set
    if (empty($image_alt)) {
        $image_alt = __('Featured Banner', 'flexpress');
    }

    // Ensure we have a valid image URL
    if ($image_url) {
?>
        <div class="featured-banner-section">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <?php if (!empty($url)): ?>
                            <a href="<?php echo esc_url($url); ?>"
                                class="featured-banner-link"
                                aria-label="<?php echo esc_attr($image_alt); ?>">
                                <?php echo wp_get_attachment_image($image_id, 'full', false, array(
                                    'class' => 'featured-banner-image',
                                    'alt' => esc_attr($image_alt),
                                    'loading' => 'lazy'
                                )); ?>
                            </a>
                        <?php else: ?>
                            <div class="featured-banner-image-container">
                                <?php echo wp_get_attachment_image($image_id, 'full', false, array(
                                    'class' => 'featured-banner-image',
                                    'alt' => esc_attr($image_alt),
                                    'loading' => 'lazy'
                                )); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
<?php
    }
}
?>