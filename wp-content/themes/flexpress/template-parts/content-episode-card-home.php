<?php

/**
 * Template part for displaying episode cards on the homepage - Original Style
 * Features: Rectangular thumbnails with title overlay and hover preview
 */

$preview_video = get_field('preview_video');
$trailer_video = get_field('trailer_video');
$full_video = get_field('full_video');
$duration = get_field('episode_duration');
$price = get_field('episode_price');
$release_date = get_field('release_date');
$featured_models = get_field('featured_models');

// Parse release date properly to handle European format (dd/mm/yyyy)
if ($release_date) {
    // Check if it's in European format (dd/mm/yyyy hh:mm am/pm)
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{1,2}):(\d{2})\s+(am|pm)$/i', $release_date, $matches)) {
        // European format: dd/mm/yyyy
        $day = $matches[1];
        $month = $matches[2];
        $year = $matches[3];
        $hour = $matches[4];
        $minute = $matches[5];
        $ampm = strtolower($matches[6]);

        // Convert to 24-hour format
        if ($ampm === 'pm' && $hour != 12) {
            $hour += 12;
        } elseif ($ampm === 'am' && $hour == 12) {
            $hour = 0;
        }

        // Create ISO format that strtotime can handle unambiguously
        $release_date = sprintf('%04d-%02d-%02d %02d:%02d:00', $year, $month, $day, $hour, $minute);
    }
}

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

<div class="episode-card homepage-card" data-preview-video="<?php echo esc_attr($preview_video); ?>" data-video-id="<?php echo esc_attr($video_id); ?>">
    <a href="<?php the_permalink(); ?>">
        <div class="card-img-top position-relative">
            <div class="preview-container position-absolute top-0 start-0 w-100 h-100"></div>
            <?php flexpress_display_episode_thumbnail('episode-card', 'img-fluid'); ?>

            <?php if ($duration): ?>
                <div class="episode-duration">
                    <?php echo esc_html($duration); ?>
                </div>
            <?php endif; ?>

            <!-- Center overlay for play button - hidden by default, shows on hover -->
            <div class="episode-center-overlay">
                <div class="episode-play-button">
                    <i class="fa-solid fa-play"></i>
                </div>
            </div>

            <!-- Bottom text overlay - visible by default, hides on hover -->
            <div class="episode-text-overlay">
                <h3 class="episode-title-home">
                    <?php the_title(); ?>
                </h3>
                <?php if ($performers): ?>
                    <div class="episode-performers-home">
                        <?php echo esc_html($performers); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </a>
</div>