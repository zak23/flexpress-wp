<?php
/**
 * Template Name: Home Page
 */

get_header();

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
    <!-- Recent Video - Hero Section -->
    <?php if ($hero_episode->have_posts()): 
        $hero_episode->the_post();
    ?>
    <div class="hero-section-wrapper">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <?php get_template_part('template-parts/content', 'hero-video'); ?>
                </div>
            </div>
        </div>
    </div>
    <?php 
        wp_reset_postdata();
    endif; 
    ?>

    <div class="container py-5">
        <!-- Featured Videos Grid -->
        <div class="featured-videos-section mb-5">
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
                <div class="video-grid featured-grid">
                    <?php
                    while ($featured_episodes->have_posts()): $featured_episodes->the_post();
                        get_template_part('template-parts/content-episode-card-home');
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

        <!-- Featured Models -->
        <div class="featured-models-section mb-5">
            <h2 class="section-title"><?php esc_html_e('Featured Models', 'flexpress'); ?></h2>
            <?php
            $models_args = array(
                'post_type' => 'model',
                'posts_per_page' => 6,
                'meta_query' => array(
                    array(
                        'key' => 'model_featured',
                        'value' => '1',
                        'compare' => '='
                    )
                ),
                'orderby' => 'title',
                'order' => 'ASC'
            );
            
            $featured_models = new WP_Query($models_args);
            
            if ($featured_models->have_posts()):
            ?>
                <div class="models-grid">
                    <?php
                    while ($featured_models->have_posts()): $featured_models->the_post();
                    ?>
                        <div class="model-grid-item">
                            <?php get_template_part('template-parts/content-model/card'); ?>
                        </div>
                    <?php
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <?php esc_html_e('No featured models available.', 'flexpress'); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Videos Grid -->
        <div class="recent-videos-section mb-5">
            <h2 class="section-title"><?php esc_html_e('Recent Episodes', 'flexpress'); ?></h2>
            <?php
            $recent_args = array(
                'post_type' => 'episode',
                'posts_per_page' => 4,
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
            
            $recent_episodes = new WP_Query($recent_args);
            
            if ($recent_episodes->have_posts()):
            ?>
                <div class="video-grid recent-grid">
                    <?php
                    while ($recent_episodes->have_posts()): $recent_episodes->the_post();
                        get_template_part('template-parts/content-episode-card-home');
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="<?php echo esc_url(get_post_type_archive_link('episode')); ?>" class="btn btn-outline-primary btn-lg"><?php esc_html_e('Show All Episodes', 'flexpress'); ?></a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <?php esc_html_e('No recent episodes available.', 'flexpress'); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- The Models -->
        <div class="all-models-section mb-5">
            <h2 class="section-title"><?php esc_html_e('The Models', 'flexpress'); ?></h2>
            <?php
            $all_models_args = array(
                'post_type' => 'model',
                'posts_per_page' => 12,
                'orderby' => 'date',
                'order' => 'DESC'
            );
            
            $all_models = new WP_Query($all_models_args);
            
            if ($all_models->have_posts()):
            ?>
                <div class="models-grid all-models">
                    <?php
                    while ($all_models->have_posts()): $all_models->the_post();
                    ?>
                        <div class="model-grid-item">
                            <?php get_template_part('template-parts/content-model/card'); ?>
                        </div>
                    <?php
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="<?php echo esc_url(get_post_type_archive_link('model')); ?>" class="btn btn-outline-primary btn-lg"><?php esc_html_e('See More Models', 'flexpress'); ?></a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <?php esc_html_e('No models available.', 'flexpress'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upcoming Episode -->
    <?php
    $upcoming_args = array(
        'post_type' => 'episode',
        'posts_per_page' => 1,
        'post_status' => 'future', // Get scheduled posts
        'orderby' => 'date',
        'order' => 'ASC' // Get the next scheduled post
    );
    
    $upcoming_episode = new WP_Query($upcoming_args);
    
    if ($upcoming_episode->have_posts()): 
        $upcoming_episode->the_post();
        $upcoming_release_date = get_the_date('Y-m-d H:i:s'); // Use WordPress post date
        $upcoming_preview_video = get_field('preview_video');
        
        // Get the BunnyCDN thumbnail URL for upcoming episode
        $upcoming_thumbnail_url = '';
        if (function_exists('flexpress_get_bunnycdn_thumbnail_url')) {
            $upcoming_thumbnail_url = flexpress_get_bunnycdn_thumbnail_url($upcoming_preview_video);
        }

        // Generate token for upcoming episode
        $teaser_expires = time() + 3600; // 1 hour expiry
        $teaser_token = '';
        if (!empty($token_key) && !empty($upcoming_preview_video)) {
            $teaser_token = hash('sha256', $token_key . $upcoming_preview_video . $teaser_expires);
        }
        

        
        // Calculate time until release
        $release_timestamp = strtotime($upcoming_release_date);
        $current_timestamp = current_time('timestamp');
        $time_diff = $release_timestamp - $current_timestamp;
        
        if ($time_diff > 0):
            $days = floor($time_diff / (60 * 60 * 24));
            $hours = floor(($time_diff % (60 * 60 * 24)) / (60 * 60));
            $minutes = floor(($time_diff % (60 * 60)) / 60);
            
            $time_until_release = '';
            if ($days > 0) {
                $time_until_release = $days . ' days';
            } elseif ($hours > 0) {
                $time_until_release = $hours . ' hours';
            } else {
                $time_until_release = $minutes . ' minutes';
            }
        ?>
        <div class="upcoming-episode-section py-5">
            <div class="container">
                <h2 class="section-title"><?php esc_html_e('Upcoming Episode', 'flexpress'); ?></h2>
                
                <div class="hero-section-wrapper">
                    <div class="row">
                        <div class="col-12">
                            <div class="hero-section">
                                <a href="#" class="hero-link upcoming-link">
                                    <div class="hero-video-container" id="upcomingHeroVideo">
                                        <?php if ($upcoming_thumbnail_url): ?>
                                        <!-- Initial thumbnail -->
                                        <div class="hero-thumbnail" style="background-image: url('<?php echo esc_url($upcoming_thumbnail_url); ?>')"></div>
                                        <?php elseif (has_post_thumbnail()): ?>
                                        <div class="hero-thumbnail" style="background-image: url('<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>')"></div>
                                        <?php else: ?>
                                        <div class="hero-thumbnail" style="background: linear-gradient(135deg, #333, #666); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                            <div class="text-center">
                                                <i class="fa-solid fa-video mb-3"></i><br>
                                                <small>No Preview Available</small>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Video element (hidden initially) -->
                                        <?php if ($upcoming_preview_video && $library_id && $teaser_token): ?>
                                        <video class="hero-video" 
                                               muted 
                                               loop 
                                               playsinline 
                                               preload="metadata"
                                               style="display: none;">
                                            <source src="https://<?php echo esc_attr($bunnycdn_url); ?>/<?php echo esc_attr($upcoming_preview_video); ?>/play_720p.mp4?token=<?php echo esc_attr($teaser_token); ?>&expires=<?php echo esc_attr($teaser_expires); ?>" type="video/mp4">
                                        </video>
                                        <?php endif; ?>
                                        
                                        <!-- Play button (shows on hover) -->
                                        <div class="hero-play-button upcoming-play-button">
                                            <i class="fa-solid fa-hourglass-half"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="hero-content-overlay">
                                        <?php 
                                        $upcoming_featured_models = get_field('featured_models');
                                        if ($upcoming_featured_models && !empty($upcoming_featured_models)): 
                                            $upcoming_model_names = array();
                                            foreach ($upcoming_featured_models as $model) {
                                                $upcoming_model_names[] = $model->post_title;
                                            }
                                            $upcoming_performers = implode(', ', $upcoming_model_names);
                                        ?>
                                        <div class="hero-model-name"><?php echo esc_html($upcoming_performers); ?></div>
                                        <?php endif; ?>
                                        
                                        <h1 class="hero-episode-title"><?php the_title(); ?></h1>
                                        
                                        <!-- Live Countdown Timer -->
                                        <div class="upcoming-countdown-hero">
                                            <div class="countdown-label"><?php esc_html_e('', 'flexpress'); ?></div>
                                            <div class="countdown-timer text-uppercase" id="upcomingCountdown" data-release-date="<?php echo esc_attr($upcoming_release_date); ?>">
                                                <span class="countdown-unit">
                                                    <span class="countdown-number" id="upcoming-days">0</span>
                                                    <span class="countdown-text">days</span>
                                                </span>
                                                <span class="countdown-separator">,</span>
                                                <span class="countdown-unit">
                                                    <span class="countdown-number" id="upcoming-hours">0</span>
                                                    <span class="countdown-text">hours</span>
                                                </span>
                                                <span class="countdown-separator">,</span>
                                                <span class="countdown-unit">
                                                    <span class="countdown-number" id="upcoming-minutes">0</span>
                                                    <span class="countdown-text">minutes</span>
                                                </span>
                                                <span class="countdown-separator">,</span>
                                                <span class="countdown-unit">
                                                    <span class="countdown-number" id="upcoming-seconds">0</span>
                                                    <span class="countdown-text">seconds</span>
                                                </span>
                                            </div>
                                            <div class="release-date-display  text-uppercase">
                                                <small><?php echo esc_html(date('l, F jS, g:iA', strtotime($upcoming_release_date))); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Prevent click action for upcoming episode (it's not released yet)
            const upcomingLink = document.querySelector('.upcoming-link');
            if (upcomingLink) {
                upcomingLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    return false;
                });
            }

            // Live Countdown Timer
            const countdownElement = document.getElementById('upcomingCountdown');
            if (countdownElement) {
                const releaseDate = countdownElement.dataset.releaseDate;
                const countDownDate = new Date(releaseDate).getTime();

                const updateCountdown = () => {
                    const now = new Date().getTime();
                    const distance = countDownDate - now;

                    if (distance > 0) {
                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        // Format with leading zeros
                        document.getElementById('upcoming-days').textContent = days.toString().padStart(2, '0');
                        document.getElementById('upcoming-hours').textContent = hours.toString().padStart(2, '0');
                        document.getElementById('upcoming-minutes').textContent = minutes.toString().padStart(2, '0');
                        document.getElementById('upcoming-seconds').textContent = seconds.toString().padStart(2, '0');
                    } else {
                        // Episode is now available
                        const countdownContainer = countdownElement.closest('.upcoming-countdown-hero');
                        if (countdownContainer) {
                            countdownContainer.innerHTML = '<div class="episode-available"><span class="available-badge">EPISODE NOW AVAILABLE</span></div>';
                        }
                        clearInterval(countdownTimer);
                    }
                };

                // Update immediately and then every second
                updateCountdown();
                const countdownTimer = setInterval(updateCountdown, 1000);
            }
        });
        </script>
        <?php 
        endif;
        wp_reset_postdata();
    endif; 
    ?>
</main>

<?php
get_footer(); 