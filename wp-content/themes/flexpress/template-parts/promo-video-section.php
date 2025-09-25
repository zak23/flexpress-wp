<?php

/**
 * Template part for displaying the promo video section
 *
 * @package FlexPress
 */

// Get ACF fields
$promo_video_id = get_field('home_promo_video_id');
$promo_title = get_field('home_promo_video_title') ?: 'Welcome to Our Platform';
$promo_subtitle = get_field('home_promo_video_subtitle') ?: 'Experience premium content like never before';
$button_text = get_field('home_promo_video_button_text') ?: 'Get Started Now';
$button_url = get_field('home_promo_video_button_url') ?: '/register';

// Only display if we have a video ID
if (empty($promo_video_id)) {
    return;
}

// Hide promo video for users with active or cancelled memberships
if (function_exists('flexpress_has_active_membership') && flexpress_has_active_membership()) {
    return;
}

// Get BunnyCDN settings for direct video
$video_settings = get_option('flexpress_video_settings', array());
$library_id = isset($video_settings['bunnycdn_library_id']) ? $video_settings['bunnycdn_library_id'] : '';
$bunnycdn_url = isset($video_settings['bunnycdn_url']) ? $video_settings['bunnycdn_url'] : '';
$token_key = isset($video_settings['bunnycdn_token_key']) ? $video_settings['bunnycdn_token_key'] : '';

// Generate token and expiration for BunnyCDN video
$expires = time() + 3600; // 1 hour expiry
$token = '';

if (!empty($token_key)) {
    // BunnyCDN token generation - format: hash('sha256', $token_key . $video_id . $expires)
    $token = hash('sha256', $token_key . $promo_video_id . $expires);
}

// Get thumbnail for poster from BunnyCDN Stream
$poster_url = '';
if (function_exists('flexpress_get_bunnycdn_thumbnail_url')) {
    $poster_url = flexpress_get_bunnycdn_thumbnail_url($promo_video_id);
}

// Fallback poster if no thumbnail available
if (empty($poster_url)) {
    $poster_url = get_template_directory_uri() . '/assets/images/video-placeholder.svg';
}
?>



<div class="row promo-video-section mb-5">
    <div class="col-12">
        <?php if (!empty($promo_title)): ?>
            <!-- <div class="promo-video-header text-center mb-4">
                        <h2 class="promo-video-title"><?php echo esc_html($promo_title); ?></h2>
                    </div> -->
        <?php endif; ?>

        <a href="<?php echo esc_url($button_url); ?>" class="promo-video-link">
            <div class="promo-video-wrapper">
                <div class="promo-video-container" id="promoVideo">
                    <?php if ($poster_url): ?>
                        <!-- Initial thumbnail -->
                        <div class="promo-thumbnail" style="background-image: url('<?php echo esc_url($poster_url); ?>')"></div>
                    <?php endif; ?>

                    <!-- Video element (hidden initially) -->
                    <?php if ($promo_video_id && $bunnycdn_url && $token): ?>
                        <video class="promo-video"
                            muted
                            loop
                            playsinline
                            preload="metadata"
                            style="display: none;">
                            <source src="https://<?php echo esc_attr($bunnycdn_url); ?>/<?php echo esc_attr($promo_video_id); ?>/play_720p.mp4?token=<?php echo esc_attr($token); ?>&expires=<?php echo esc_attr($expires); ?>" type="video/mp4">
                        </video>
                    <?php endif; ?>

                    <!-- Play button (shows on hover) -->
                    <div class="promo-play-button">
                        <i class="fa-solid fa-play"></i>
                    </div>
                </div>
            </div>
        </a>

        <?php if (!empty($promo_subtitle)): ?>
            <!-- <div class="promo-video-subtitle text-center mt-4 mb-4">
                        <p class="lead"><?php echo esc_html($promo_subtitle); ?></p>
                    </div> -->
        <?php endif; ?>

        <?php if (!empty($button_text) && !empty($button_url)): ?>
            <div class="promo-video-cta text-center">
                <a href="<?php echo esc_url($button_url); ?>" class="btn btn-primary btn-lg">
                    <?php echo esc_html($button_text); ?> &gt;
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>