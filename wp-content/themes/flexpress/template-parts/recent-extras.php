<?php

/**
 * Template part for displaying recent extras on homepage
 * Shows the 4 most recent extras when extras are enabled
 */

// Check if extras are enabled
if (!flexpress_is_extras_enabled()) {
    return;
}

// Query the 4 most recent extras
$recent_extras_args = array(
    'post_type' => 'extras',
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

// Apply extras visibility filtering
$recent_extras_args = flexpress_add_extras_visibility_to_query($recent_extras_args);

$recent_extras = new WP_Query($recent_extras_args);

// Only display section if we have extras
if (!$recent_extras->have_posts()) {
    wp_reset_postdata();
    return;
}
?>

<section class="recent-extras-section py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2 class="section-title"><?php esc_html_e('Recent Extras', 'flexpress'); ?></h2>

                <div class="extras-grid">
                    <div class="row g-4">
                        <?php while ($recent_extras->have_posts()): $recent_extras->the_post(); ?>
                            <div class="col-lg-3 col-md-6">
                                <?php get_template_part('template-parts/content', 'extras-card'); ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="<?php echo esc_url(get_post_type_archive_link('extras')); ?>" class="btn btn-outline-primary btn-lg">
                        <?php esc_html_e('View All Extras', 'flexpress'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php wp_reset_postdata(); ?>