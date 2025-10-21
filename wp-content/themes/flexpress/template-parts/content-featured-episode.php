<?php

/**
 * Template part for displaying featured episodes
 */

$preview_video = get_field('preview_video');
$episode_duration = get_field('episode_duration');
$featured_models = get_field('featured_models');
$release_date = get_field('release_date');
$full_video = get_field('full_video');

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

// If we have a full video but no duration, try to get it from BunnyCDN
if ($full_video && (empty($episode_duration) || $episode_duration == 0)) {
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
        $episode_duration = $formatted_duration;
    }
}
?>

<div class="col-md-6 col-lg-4">
    <div class="episode-card" data-preview-video="<?php echo esc_attr($preview_video); ?>">
        <div class="card-img-top position-relative">
            <a href="<?php the_permalink(); ?>">
                <div class="preview-container position-absolute top-0 start-0 w-100 h-100"></div>
                <?php flexpress_display_episode_thumbnail('episode-card', 'img-fluid'); ?>

                <div class="episode-overlay">
                    <?php if ($episode_duration): ?>
                        <div class="episode-duration">
                            <?php echo esc_html($episode_duration); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </a>
        </div>

        <div class="card-body">
            <h3 class="card-title">
                <a href="<?php the_permalink(); ?>">
                    <?php the_title(); ?>
                </a>
            </h3>

            <?php if ($performers): ?>
                <div class="episode-performers mb-2">
                    <?php echo esc_html($performers); ?>
                </div>
            <?php endif; ?>

            <?php if ($release_date): ?>
                <div class="episode-meta mb-3">
                    <?php
                    // More robust date parsing
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2} [ap]m$/i', $release_date)) {
                        // Format like "23/03/2025 12:00 am"
                        $date_parts = explode(' ', $release_date);
                        $date_numbers = explode('/', $date_parts[0]);
                        echo $date_numbers[0] . ' ' . date('F', mktime(0, 0, 0, $date_numbers[1], 1)) . ' ' . $date_numbers[2];
                    } else {
                        // Try with standard strtotime
                        $timestamp = strtotime($release_date);
                        if ($timestamp && $timestamp > 0) {
                            echo date('j F Y', $timestamp);
                        } else {
                            // Fallback to just showing the raw date
                            echo esc_html($release_date);
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <a href="<?php the_permalink(); ?>" class="btn btn-primary flex-grow-1">
                    <?php esc_html_e('WATCH NOW', 'flexpress'); ?>
                </a>
            </div>
        </div>
    </div>
</div>