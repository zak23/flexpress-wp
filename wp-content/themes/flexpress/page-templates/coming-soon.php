<?php

/**
 * Template Name: Coming Soon
 * Description: Coming Soon landing page with logo, video, and custom links
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get settings
$general_settings = get_option('flexpress_general_settings');
$coming_soon_enabled = !empty($general_settings['coming_soon_enabled']);
$coming_soon_logo = !empty($general_settings['coming_soon_logo']) ? $general_settings['coming_soon_logo'] : '';
$coming_soon_video_id = !empty($general_settings['coming_soon_video_url']) ? $general_settings['coming_soon_video_url'] : '';
$coming_soon_fallback_image = !empty($general_settings['coming_soon_fallback_image']) ? $general_settings['coming_soon_fallback_image'] : '';
$coming_soon_text = !empty($general_settings['coming_soon_text']) ? $general_settings['coming_soon_text'] : 'Coming Soon';
$coming_soon_links = !empty($general_settings['coming_soon_links']) ? $general_settings['coming_soon_links'] : array();

// Get BunnyCDN video settings for proper authentication
$video_settings = get_option('flexpress_video_settings', array());
$library_id = isset($video_settings['bunnycdn_library_id']) ? $video_settings['bunnycdn_library_id'] : '';
$bunnycdn_url = isset($video_settings['bunnycdn_url']) ? $video_settings['bunnycdn_url'] : '';
$token_key = isset($video_settings['bunnycdn_token_key']) ? $video_settings['bunnycdn_token_key'] : '';

// Generate token for BunnyCDN video
$expires = time() + 3600; // 1 hour expiry
$token = '';
if (!empty($token_key) && !empty($coming_soon_video_id)) {
    $token = hash('sha256', $token_key . $coming_soon_video_id . $expires);
}

// Get primary logo URL (prioritize FlexPress logo, fall back to coming soon logo)
$logo_url = '';
$primary_logo = flexpress_get_custom_logo('full', 'primary');
if ($primary_logo && !empty($primary_logo['url'])) {
    $logo_url = $primary_logo['url'];
} elseif (!empty($coming_soon_logo)) {
    $logo_url = wp_get_attachment_image_url($coming_soon_logo, 'full');
}

// Get fallback image URL
$fallback_image_url = '';
if (!empty($coming_soon_fallback_image)) {
    $fallback_image_url = wp_get_attachment_image_url($coming_soon_fallback_image, 'full');
}

// Enqueue styles and scripts
wp_enqueue_style('flexpress-coming-soon', get_template_directory_uri() . '/assets/css/coming-soon.css', array(), '1.0.0');
wp_enqueue_script('flexpress-coming-soon', get_template_directory_uri() . '/assets/js/coming-soon.js', array('jquery'), '1.0.0', true);

// Pass data to JavaScript
wp_localize_script('flexpress-coming-soon', 'flexpressComingSoon', array(
    'videoId' => $coming_soon_video_id,
    'fallbackImageUrl' => $fallback_image_url,
    'logoUrl' => $logo_url
));
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#000000">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class('flexpress-coming-soon-page'); ?>>
    <div class="coming-soon-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <!-- Logo Section (centered) -->
                    <?php if ($logo_url) : ?>
                        <div class="coming-soon-logo">
                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>" />
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <!-- Video Section (full width in container) -->
                    <?php if ($coming_soon_video_id && $library_id && $token): ?>
                        <div class="coming-soon-video-container" id="comingSoonVideo">
                            <?php if ($fallback_image_url): ?>
                                <!-- Initial thumbnail -->
                                <div class="coming-soon-thumbnail" style="background-image: url('<?php echo esc_url($fallback_image_url); ?>')"></div>
                            <?php endif; ?>

                            <!-- BunnyCDN iframe embed player -->
                            <div style="position:relative;padding-top:56.25%;">
                                <iframe src="https://iframe.mediadelivery.net/embed/<?php echo esc_attr($library_id); ?>/<?php echo esc_attr($coming_soon_video_id); ?>?token=<?php echo esc_attr($token); ?>&expires=<?php echo esc_attr($expires); ?>&autoplay=true&loop=true&muted=false&controls=false"
                                    loading="lazy"
                                    style="border:0;position:absolute;top:0;height:100%;width:100%;"
                                    allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;"
                                    allowfullscreen="true">
                                </iframe>
                            </div>
                        </div>
                    <?php elseif ($fallback_image_url): ?>
                        <div class="coming-soon-image">
                            <img src="<?php echo esc_url($fallback_image_url); ?>" alt="Coming Soon" />
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <!-- Text Section (centered) -->
                    <div class="coming-soon-text">
                        <h1><?php echo esc_html($coming_soon_text); ?></h1>
                    </div>

                    <!-- Buttons Section -->
                    <?php if (!empty($coming_soon_links)) : ?>
                        <div class="coming-soon-buttons">
                            <?php foreach ($coming_soon_links as $link) : ?>
                                <?php if (!empty($link['title']) && !empty($link['url'])) : ?>
                                    <a href="<?php echo esc_url($link['url']); ?>"
                                        class="btn btn-primary"
                                        <?php echo (isset($link['new_tab']) && $link['new_tab'] == '1') ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                                        <?php echo esc_html($link['title']); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer Section with Social Icons -->
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <div class="coming-soon-footer">
                        <?php if (function_exists('flexpress_has_social_media_links') && flexpress_has_social_media_links()) : ?>
                            <div class="coming-soon-social-icons">
                                <?php
                                flexpress_display_social_media_links(array(
                                    'wrapper' => 'ul',
                                    'item_wrapper' => 'li',
                                    'class' => 'coming-soon-social-list list-unstyled d-flex gap-3 justify-content-center',
                                    'item_class' => '',
                                    'link_class' => 'coming-soon-social-link',
                                    'icon_class' => 'fa-lg',
                                    'platforms' => array('facebook', 'instagram', 'twitter', 'tiktok', 'youtube', 'onlyfans'),
                                    'show_icons' => true,
                                    'show_labels' => false
                                ));
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Newsletter Modal -->
    <div id="newsletter-modal" class="newsletter-modal" style="display: none;">
        <div class="newsletter-modal-content">
            <div class="newsletter-modal-header">
                <h2>Get Notified</h2>
                <button class="newsletter-modal-close">&times;</button>
            </div>
            <div class="newsletter-modal-body">
                <p>Subscribe to our newsletter and be the first to know when new content drops!</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Enter your email address" required>
                    <button type="submit">Subscribe Now</button>
                </form>
                <p class="newsletter-disclaimer">By subscribing, you agree to receive our newsletter. You can unsubscribe at any time.</p>
            </div>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>

</html>