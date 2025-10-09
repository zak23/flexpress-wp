<?php
/**
 * Template part for displaying episode cards - Vixen.com Style
 */

$preview_video = get_field('preview_video');
$trailer_video = get_field('trailer_video');
$full_video = get_field('full_video');
$duration = get_field('episode_duration');
$price = get_field('episode_price');
$release_date = get_field('release_date');
$featured_models = get_field('featured_models');

// Get performer names from the relationship field
$performers = '';
if ($featured_models && !empty($featured_models)) {
    $model_names = array();
    foreach ($featured_models as $model) {
        $model_names[] = $model->post_title;
    }
    $performers = implode(', ', $model_names);
}

// Get video ID using the helper function
$video_id = flexpress_get_primary_video_id(get_the_ID());

// If we have a full video but no duration, try to get it from BunnyCDN
if ($full_video && (empty($duration) || $duration == 0)) {
    $video_details = flexpress_get_bunnycdn_video_details($full_video);
    
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
        // Format duration as MM:SS instead of just minutes
        $minutes = floor($duration_seconds / 60);
        $seconds = $duration_seconds % 60;
        $formatted_duration = sprintf('%d:%02d', $minutes, $seconds);
        
        // Save this back to the ACF field
        update_field('episode_duration', $formatted_duration, get_the_ID());
        $duration = $formatted_duration;
    }
}
?>

<div class="episode-card" data-preview-video="<?php echo esc_attr($preview_video); ?>" data-video-id="<?php echo esc_attr($video_id); ?>">
    <a href="<?php the_permalink(); ?>" class="episode-link">
        <div class="card-img-top">
            <div class="preview-container"></div>
            <?php flexpress_display_episode_thumbnail('medium', 'episode-thumbnail'); ?>
            
            <div class="episode-overlay">
                <div class="episode-play-button">
                    <i class="fa-solid fa-play"></i>
                </div>
            </div>
        </div>
    </a>
    
    <!-- Episode Information Below Thumbnail -->
    <div class="episode-info">
        <div class="episode-info-row">
            <h5 class="episode-title">
                <a href="<?php the_permalink(); ?>" class="episode-title-link"><?php the_title(); ?></a>
            </h5>
            <?php if ($duration): ?>
            <span class="episode-duration"><?php echo esc_html($duration); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="episode-info-row">
            <?php if ($featured_models && !empty($featured_models)): ?>
            <div class="episode-performers">
                <?php 
                $model_links = array();
                foreach ($featured_models as $model) {
                    $model_links[] = '<a href="' . esc_url(get_permalink($model->ID)) . '" class="model-link">' . esc_html($model->post_title) . '</a>';
                }
                echo implode(', ', $model_links);
                ?>
            </div>
            <?php endif; ?>
            
            <?php 
            if ($release_date) {
                // Handle multiple date formats
                $timestamp = false;
                
                // Try different date formats
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $release_date, $matches)) {
                    // UK format: dd/mm/yyyy
                    $timestamp = mktime(0, 0, 0, $matches[2], $matches[1], $matches[3]);
                } else {
                    // Try standard strtotime
                    $timestamp = strtotime($release_date);
                }
                
                if ($timestamp && $timestamp > 0) {
                    $formatted_date = strtoupper(date('F d, Y', $timestamp));
                } else {
                    // Fall back to WordPress post date
                    $formatted_date = strtoupper(get_the_date('F d, Y'));
                }
            } else {
                // Fall back to WordPress post date
                $formatted_date = strtoupper(get_the_date('F d, Y'));
            }
            ?>
            <span class="episode-date"><?php echo esc_html($formatted_date); ?></span>
        </div>
    </div>
</div> 