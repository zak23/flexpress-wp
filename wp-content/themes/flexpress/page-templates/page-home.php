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

          <!-- Upcoming Episode -->
    <?php get_template_part('template-parts/join-now-cta'); ?>

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
            <h2 class="section-title"><?php esc_html_e('Models', 'flexpress'); ?></h2>
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
    <?php get_template_part('template-parts/upcoming-episode'); ?>

    <!-- Awards and Nominations -->
    <?php get_template_part('template-parts/awards-nominations'); ?>

    <!-- Featured On Section -->
    <?php get_template_part('template-parts/featured-on'); ?>
</main>

<?php
get_footer();
