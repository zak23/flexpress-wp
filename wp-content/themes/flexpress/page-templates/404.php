<?php
/**
 * The template for displaying 404 pages (not found)
 * 
 * Enhanced 404 page with FlexPress dark theme styling
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="site-main error-404-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 text-center">
                <!-- Main Error Section -->
                <div class="error-404-main mb-5">
                    <div class="error-number">
                        <span class="error-404-text">404</span>
                        <div class="error-decoration"></div>
                    </div>
                    <h1 class="error-title mb-4"><?php esc_html_e('Page Not Found', 'flexpress'); ?></h1>
                    <p class="error-description mb-5">
                        <?php esc_html_e('The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.', 'flexpress'); ?>
                    </p>
                </div>

                <!-- Search Form -->
                <div class="row justify-content-center mb-5">
                    <div class="col-md-8">
                        <div class="search-section">
                            <h3 class="search-title mb-4"><?php esc_html_e('Search Our Content', 'flexpress'); ?></h3>
                            <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                                <div class="input-group input-group-lg">
                                    <input type="search" 
                                           class="form-control search-input" 
                                           placeholder="<?php esc_attr_e('Search episodes, models, and more...', 'flexpress'); ?>" 
                                           value="<?php echo get_search_query(); ?>" 
                                           name="s" 
                                           title="<?php esc_attr_e('Search for:', 'flexpress'); ?>" />
                                    <button class="btn btn-primary search-btn" type="submit">
                                        <i class="bi bi-search"></i>
                                        <?php esc_html_e('Search', 'flexpress'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Quick Navigation Cards -->
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="error-card h-100">
                            <div class="error-card-icon">
                                <i class="bi bi-collection-play"></i>
                            </div>
                            <div class="error-card-body">
                                <h3 class="error-card-title"><?php esc_html_e('Browse Episodes', 'flexpress'); ?></h3>
                                <p class="error-card-text"><?php esc_html_e('Check out our latest episodes and exclusive content.', 'flexpress'); ?></p>
                                <a href="<?php echo esc_url(home_url('/episodes')); ?>" class="btn btn-outline-primary error-card-btn">
                                    <?php esc_html_e('View Episodes', 'flexpress'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="error-card h-100">
                            <div class="error-card-icon">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <div class="error-card-body">
                                <h3 class="error-card-title"><?php esc_html_e('Join Now', 'flexpress'); ?></h3>
                                <p class="error-card-text"><?php esc_html_e('Create an account to access exclusive content and member benefits.', 'flexpress'); ?></p>
                                <a href="<?php echo esc_url(home_url('/join')); ?>" class="btn btn-outline-primary error-card-btn">
                                    <?php esc_html_e('Sign Up', 'flexpress'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="error-card h-100">
                            <div class="error-card-icon">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="error-card-body">
                                <h3 class="error-card-title"><?php esc_html_e('Meet Our Models', 'flexpress'); ?></h3>
                                <p class="error-card-text"><?php esc_html_e('Discover our talented performers and their exclusive content.', 'flexpress'); ?></p>
                                <a href="<?php echo esc_url(get_post_type_archive_link('model')); ?>" class="btn btn-outline-primary error-card-btn">
                                    <?php esc_html_e('View Models', 'flexpress'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Help Section -->
                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <div class="error-card h-100">
                            <div class="error-card-icon">
                                <i class="bi bi-question-circle"></i>
                            </div>
                            <div class="error-card-body">
                                <h3 class="error-card-title"><?php esc_html_e('Need Help?', 'flexpress'); ?></h3>
                                <p class="error-card-text"><?php esc_html_e('Contact our support team for assistance.', 'flexpress'); ?></p>
                                <a href="<?php echo esc_url(home_url('/contact')); ?>" class="btn btn-outline-primary error-card-btn">
                                    <?php esc_html_e('Contact Us', 'flexpress'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="error-card h-100">
                            <div class="error-card-icon">
                                <i class="bi bi-house"></i>
                            </div>
                            <div class="error-card-body">
                                <h3 class="error-card-title"><?php esc_html_e('Go Home', 'flexpress'); ?></h3>
                                <p class="error-card-text"><?php esc_html_e('Return to our homepage to explore all available content.', 'flexpress'); ?></p>
                                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary error-card-btn">
                                    <?php esc_html_e('Homepage', 'flexpress'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Episodes Preview -->
                <?php
                $recent_episodes = new WP_Query(array(
                    'post_type' => 'episode',
                    'posts_per_page' => 3,
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
                ));

                if ($recent_episodes->have_posts()):
                ?>
                <div class="recent-episodes-section">
                    <h3 class="recent-episodes-title mb-4"><?php esc_html_e('Recent Episodes', 'flexpress'); ?></h3>
                    <div class="row g-4">
                        <?php while ($recent_episodes->have_posts()): $recent_episodes->the_post(); ?>
                        <div class="col-md-4">
                            <div class="recent-episode-card">
                                <a href="<?php the_permalink(); ?>" class="recent-episode-link">
                                    <?php if (has_post_thumbnail()): ?>
                                        <div class="recent-episode-thumbnail">
                                            <?php the_post_thumbnail('medium', array('class' => 'img-fluid')); ?>
                                            <div class="recent-episode-overlay">
                                                <i class="bi bi-play-circle"></i>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="recent-episode-content">
                                        <h4 class="recent-episode-title"><?php the_title(); ?></h4>
                                        <p class="recent-episode-date">
                                            <?php echo date_i18n(get_option('date_format'), strtotime(get_field('release_date'))); ?>
                                        </p>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php 
                wp_reset_postdata();
                endif; 
                ?>
            </div>
        </div>
    </div>
</div>

<?php
get_footer(); 