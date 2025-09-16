<?php
/**
 * Template part for displaying the hero video section
 *
 * @package FlexPress
 */

// Get the BunnyCDN video settings from the options
$video_settings = get_option('flexpress_video_settings', array());
$library_id = isset($video_settings['bunnycdn_library_id']) ? $video_settings['bunnycdn_library_id'] : '';
$bunnycdn_url = isset($video_settings['bunnycdn_url']) ? $video_settings['bunnycdn_url'] : '';
$token_key = isset($video_settings['bunnycdn_token_key']) ? $video_settings['bunnycdn_token_key'] : '';

// Get the preview video ID
$preview_video = get_field('preview_video');

// Get the thumbnail URL first
$thumbnail_url = '';
if (function_exists('flexpress_get_bunnycdn_thumbnail_url')) {
    $thumbnail_url = flexpress_get_bunnycdn_thumbnail_url($preview_video);
}

// Generate token
$expires = time() + 3600; // 1 hour expiry
$token = '';
if (!empty($token_key) && !empty($preview_video)) {
    $token = hash('sha256', $token_key . $preview_video . $expires);
}
?>

<div class="hero-section">
    <a href="<?php echo get_permalink(); ?>" class="hero-link">
        <div class="hero-video-container" id="heroVideo">
            <?php if ($thumbnail_url): ?>
            <!-- Initial thumbnail -->
            <div class="hero-thumbnail" style="background-image: url('<?php echo esc_url($thumbnail_url); ?>')"></div>
            <?php endif; ?>
            
            <!-- Video element (hidden initially) -->
            <?php if ($preview_video && $library_id && $token): ?>
            <video class="hero-video" 
                   muted 
                   loop 
                   playsinline 
                   preload="metadata"
                   style="display: none;">
                <source src="https://<?php echo esc_attr($bunnycdn_url); ?>/<?php echo esc_attr($preview_video); ?>/play_720p.mp4?token=<?php echo esc_attr($token); ?>&expires=<?php echo esc_attr($expires); ?>" type="video/mp4">
            </video>
            <?php endif; ?>
            
            <!-- Play button (shows on hover) -->
            <div class="hero-play-button">
                <i class="fa-solid fa-play"></i>
            </div>
        </div>
        
        <div class="hero-content-overlay">
            <?php 
            $featured_models = get_field('featured_models');
            if ($featured_models && !empty($featured_models)): 
                $model_names = array();
                foreach ($featured_models as $model) {
                    $model_names[] = $model->post_title;
                }
                $hero_performers = implode(', ', $model_names);
            ?>
            <div class="hero-model-name"><?php echo esc_html($hero_performers); ?></div>
            <?php endif; ?>
            <h1 class="hero-episode-title"><?php the_title(); ?></h1>
        </div>
    </a>
</div> 