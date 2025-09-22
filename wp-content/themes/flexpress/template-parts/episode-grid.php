<?php
/**
 * Template part for displaying episode grid
 */

$args = array(
    'post_type' => 'episode',
    'posts_per_page' => 12,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
    'meta_query' => array(
        array(
            'key' => 'release_date',
            'value' => current_time('mysql'),
            'compare' => '<=',
            'type' => 'DATETIME'
        )
    ),
    'orderby' => 'meta_value',
    'meta_key' => 'release_date',
    'order' => 'DESC'
);

// Apply episode visibility filtering
$args = flexpress_add_episode_visibility_to_query($args);

$episodes = new WP_Query($args);
?>

<div class="episode-grid">
    <?php if ($episodes->have_posts()): ?>
        <div class="row g-4">
            <?php while ($episodes->have_posts()): $episodes->the_post(); ?>
                <?php
                $preview_video = get_field('preview_video');
                $trailer_video = get_field('trailer_video');
                $full_video = get_field('full_video');
                $duration = get_field('episode_duration');
                $featured_models = get_field('featured_models');
                $release_date = get_field('release_date');
                
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
                
                <div class="col-md-4 col-lg-3">
                    <div class="episode-card" data-preview-video="<?php echo esc_attr($preview_video); ?>" data-video-id="<?php echo esc_attr($video_id); ?>">
                        <div class="card-img-top position-relative">
                            <a href="<?php the_permalink(); ?>">
                                <div class="preview-container position-absolute top-0 start-0 w-100 h-100"></div>
                                <?php flexpress_display_episode_thumbnail('medium', 'img-fluid'); ?>
                                
                                <div class="episode-overlay">
                                    <?php if ($duration): ?>
                                    <div class="episode-duration">
                                        <?php echo esc_html($duration); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h5>
                            
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
            <?php endwhile; ?>
            
            <div class="col-12">
                <?php
                echo paginate_links(array(
                    'total' => $episodes->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'prev_text' => '<i class="fas fa-chevron-left"></i> ' . __('Previous', 'flexpress'),
                    'next_text' => __('Next', 'flexpress') . ' <i class="fas fa-chevron-right"></i>',
                    'type' => 'list',
                    'class' => 'pagination justify-content-center'
                ));
                ?>
            </div>
            
            <?php wp_reset_postdata(); ?>
        </div>
    <?php else: ?>
        <p class="text-center"><?php esc_html_e('No episodes found.', 'flexpress'); ?></p>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Episode preview hover functionality can be added here if needed
});
</script> 