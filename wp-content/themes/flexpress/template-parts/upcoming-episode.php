<?php
/**
 * Template part for displaying upcoming episode
 * 
 * @package FlexPress
 */

// Get the next upcoming episode (scheduled posts)
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
    $upcoming_release_date = get_the_date('Y-m-d H:i:s');
    $upcoming_preview_video = get_field('preview_video');
    $upcoming_thumbnail_url = '';
    
    // Get BunnyCDN thumbnail if available
    if (function_exists('flexpress_get_bunnycdn_thumbnail_url') && $upcoming_preview_video) {
        $upcoming_thumbnail_url = flexpress_get_bunnycdn_thumbnail_url($upcoming_preview_video);
    }
    
    // Get BunnyCDN settings for video
    $video_settings = get_option('flexpress_video_settings', array());
    $library_id = isset($video_settings['bunnycdn_library_id']) ? $video_settings['bunnycdn_library_id'] : '';
    $bunnycdn_url = isset($video_settings['bunnycdn_url']) ? $video_settings['bunnycdn_url'] : '';
    $token_key = isset($video_settings['bunnycdn_token_key']) ? $video_settings['bunnycdn_token_key'] : '';
    
    // Generate teaser token
    $teaser_expires = time() + 3600;
    $teaser_token = '';
    if (!empty($token_key) && !empty($upcoming_preview_video)) {
        $teaser_token = hash('sha256', $token_key . $upcoming_preview_video . $teaser_expires);
    }
    
    // Get featured models
    $upcoming_featured_models = get_field('featured_models');
    $upcoming_performers = 'No models found.';
    if ($upcoming_featured_models && !empty($upcoming_featured_models)) {
        $upcoming_model_names = array();
        foreach ($upcoming_featured_models as $model) {
            $upcoming_model_names[] = $model->post_title;
        }
        $upcoming_performers = implode(', ', $upcoming_model_names);
    }
    ?>
    <div class="upcoming-episode-section py-5">
        <div class="container">
            <h2 class="section-title"><?php esc_html_e('Upcoming Episode', 'flexpress'); ?></h2>
            
            <div class="hero-section-wrapper">
                <div class="row">
                    <div class="col-12">
                        <div class="hero-section">
                            <?php
                            // Determine the correct URL based on login status
                            if ( is_user_logged_in() ) {
                                $hero_link_url = site_url('/episodes/');
                            } else {
                                $hero_link_url = site_url('/join/');
                            }
                            ?>
                            <a href="<?php echo esc_url($hero_link_url); ?>" class="hero-link">
                                <div class="hero-video-container" id="upcomingHeroVideo">
                                    <?php if ($upcoming_thumbnail_url): ?>
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
                                    <div class="hero-model-name"><?php echo esc_html($upcoming_performers); ?></div>
                                    <h1 class="hero-episode-title"><?php the_title(); ?></h1>
                                    
                                    <!-- Live Countdown Timer -->
                                    <div class="upcoming-countdown-hero">
                                        <div class="countdown-timer text-uppercase" id="upcomingCountdown" data-release-date="<?php echo esc_attr($upcoming_release_date); ?>">
                                            <span class="countdown-unit">
                                                <span class="countdown-number" id="upcoming-days">0</span>
                                                <span class="countdown-text">days</span>
                                            </span>
                                            <span class="countdown-unit">
                                                <span class="countdown-number" id="upcoming-hours">0</span>
                                                <span class="countdown-text">hours</span>
                                            </span>
                                            <span class="countdown-unit">
                                                <span class="countdown-number" id="upcoming-minutes">0</span>
                                                <span class="countdown-text">minutes</span>
                                            </span>
                                            <span class="countdown-unit">
                                                <span class="countdown-number" id="upcoming-seconds">0</span>
                                                <span class="countdown-text">seconds</span>
                                            </span>
                                        </div>
                                        <div class="release-date-display text-uppercase">
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

        // Video autoplay functionality
        const upcomingHeroVideo = document.getElementById('upcomingHeroVideo');
        const heroThumbnail = upcomingHeroVideo.querySelector('.hero-thumbnail');
        const heroVideo = upcomingHeroVideo.querySelector('.hero-video');
        
        if (heroVideo && heroThumbnail) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        heroVideo.load();
                        heroVideo.addEventListener('loadeddata', () => {
                            setTimeout(() => {
                                heroVideo.style.display = 'block';
                                heroVideo.style.transition = 'opacity 0.3s ease-in';
                                heroVideo.style.opacity = '1';
                                heroVideo.play().catch(() => {
                                    console.log('Autoplay prevented');
                                });
                            }, 3000);
                        });
                        observer.unobserve(upcomingHeroVideo);
                    }
                });
            }, {
                rootMargin: '100% 0px',
                threshold: 0.1
            });
            observer.observe(upcomingHeroVideo);
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

                    document.getElementById('upcoming-days').textContent = days.toString().padStart(2, '0');
                    document.getElementById('upcoming-hours').textContent = hours.toString().padStart(2, '0');
                    document.getElementById('upcoming-minutes').textContent = minutes.toString().padStart(2, '0');
                    document.getElementById('upcoming-seconds').textContent = seconds.toString().padStart(2, '0');
                } else {
                    const countdownContainer = countdownElement.closest('.upcoming-countdown-hero');
                    if (countdownContainer) {
                        countdownContainer.innerHTML = '<div class="episode-available"><span class="available-badge">EPISODE NOW AVAILABLE</span></div>';
                    }
                    clearInterval(countdownTimer);
                }
            };

            updateCountdown();
            const countdownTimer = setInterval(updateCountdown, 1000);
        }
    });
    </script>
    <?php 
    wp_reset_postdata();
endif; 
?>
