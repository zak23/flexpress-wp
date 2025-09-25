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

// Get BunnyCDN video URL
$video_url = '';
if (function_exists('flexpress_get_bunnycdn_video_url')) {
    $video_url = flexpress_get_bunnycdn_video_url($promo_video_id, 'preview');
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

<div class="promo-video-section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <?php if (!empty($promo_title)): ?>
                    <div class="promo-video-header text-center mb-4">
                        <h2 class="promo-video-title"><?php echo esc_html($promo_title); ?></h2>
                    </div>
                <?php endif; ?>
                
                <div class="promo-video-wrapper">
                    <div id="promoVideoCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <?php if (!empty($video_url)): ?>
                                    <video class="img-fluid promo-video" autoplay loop muted poster="<?php echo esc_url($poster_url); ?>">
                                        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                <?php else: ?>
                                    <div class="promo-video-placeholder">
                                        <div class="placeholder-content">
                                            <i class="fas fa-video fa-3x mb-3"></i>
                                            <p><?php esc_html_e('Video not available', 'flexpress'); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($promo_subtitle)): ?>
                    <div class="promo-video-subtitle text-center mt-4 mb-4">
                        <p class="lead"><?php echo esc_html($promo_subtitle); ?></p>
                    </div>
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
    </div>
</div>
