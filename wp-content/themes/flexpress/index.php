<?php

/**
 * The main template file
 */

get_header();

// Check if upcoming episode should be shown at the top
$upcoming_settings = get_option('flexpress_upcoming_episode_settings', array());
$show_upcoming_top = !empty($upcoming_settings['enabled']) && $upcoming_settings['position'] === 'homepage_top';

if ($show_upcoming_top) {
    get_template_part('template-parts/upcoming-episode');
}

// Get the most recent episode for the hero section
$hero_args = array(
    'post_type' => 'episode',
    'posts_per_page' => 1,
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

$hero_episode = new WP_Query($hero_args);
?>

<main class="site-main">
    <!-- Hero Section with video -->
    <?php if ($hero_episode->have_posts()):
        $hero_episode->the_post();
        $preview_video = get_field('preview_video');

        // Get the BunnyCDN video settings from the options
        $video_settings = get_option('flexpress_video_settings', array());
        $library_id = isset($video_settings['bunnycdn_library_id']) ? $video_settings['bunnycdn_library_id'] : '';
        $bunnycdn_url = isset($video_settings['bunnycdn_url']) ? $video_settings['bunnycdn_url'] : '';
        $token_key = isset($video_settings['bunnycdn_token_key']) ? $video_settings['bunnycdn_token_key'] : '';
    ?>
        <div class="hero-section">
            <?php if ($preview_video && $library_id): ?>
                <div class="hero-video-container">
                    <?php
                    // Generate a direct embed with no controls for muted autoplay
                    $expires = time() + 3600; // 1 hour expiry
                    $token = '';

                    // Only generate token if we have the key
                    if (!empty($token_key)) {
                        $token = hash('sha256', $token_key . $preview_video . $expires);
                    }

                    // Embed iframe with autoplay and muted
                    ?>
                    <iframe src="https://iframe.mediadelivery.net/embed/<?php echo esc_attr($library_id); ?>/<?php echo esc_attr($preview_video); ?>?autoplay=true&loop=true&muted=true&controls=false<?php echo (!empty($token) ? '&token=' . esc_attr($token) . '&expires=' . esc_attr($expires) : ''); ?>"
                        allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;"
                        frameborder="0"
                        allowfullscreen="true">
                    </iframe>
                </div>
            <?php endif; ?>

            <div class="hero-overlay"></div>

            <div class="hero-content">
                <h1 class="hero-title"><?php the_title(); ?></h1>
                <p class="hero-description"><?php echo wp_trim_words(get_the_excerpt(), 30); ?></p>
                <a href="<?php the_permalink(); ?>" class="btn btn-primary btn-lg"><?php esc_html_e('WATCH NOW', 'flexpress'); ?></a>

                <?php if (!is_user_logged_in()): ?>
                    <a href="<?php echo esc_url(home_url('/join')); ?>" class="btn btn-outline-light btn-lg ms-3"><?php esc_html_e('JOIN NOW', 'flexpress'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    <?php
        wp_reset_postdata();
    endif;
    ?>

    <div class="container py-5">
        <!-- Featured Episodes -->
        <div class="featured-episodes mb-5">
            <h2 class="section-title"><?php esc_html_e('Featured Episodes', 'flexpress'); ?></h2>
            <?php
            $featured_args = array(
                'post_type' => 'episode',
                'posts_per_page' => 4,
                'meta_query' => array(
                    array(
                        'key' => 'is_featured',
                        'value' => '1',
                        'compare' => '='
                    ),
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

            $featured_episodes = new WP_Query($featured_args);

            if ($featured_episodes->have_posts()):
            ?>
                <div class="row g-4">
                    <?php
                    while ($featured_episodes->have_posts()): $featured_episodes->the_post();
                        get_template_part('template-parts/content', 'featured-episode');
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <?php esc_html_e('No featured episodes available.', 'flexpress'); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- All Episodes -->
        <div class="all-episodes">
            <h2 class="section-title"><?php esc_html_e('ALL EPISODES', 'flexpress'); ?></h2>
            <?php get_template_part('template-parts/episode-grid'); ?>
        </div>
    </div>
</main>

<!-- Recent Extras Section -->
<?php get_template_part('template-parts/recent-extras'); ?>

<!-- Upcoming Episode Before Footer -->
<?php
$show_upcoming_bottom = !empty($upcoming_settings['enabled']) && $upcoming_settings['position'] === 'homepage_bottom';
if ($show_upcoming_bottom) {
    get_template_part('template-parts/upcoming-episode');
}
?>

<?php
get_footer();
