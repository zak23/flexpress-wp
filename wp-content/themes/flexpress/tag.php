<?php

/**
 * The template for displaying tag archives
 * Handles both regular tags and collection tags
 *
 * @package FlexPress
 */

get_header();

// Get current tag
$current_tag = get_queried_object();

// Check if this is a collection tag
$is_collection = flexpress_is_collection_tag($current_tag);
$collection_meta = $is_collection ? flexpress_get_collection_metadata($current_tag) : array();

// Get pagination
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Get episodes for this tag
if ($is_collection) {
    $episodes_query = flexpress_get_collection_episodes($current_tag, 12, $paged);
} else {
    // Regular tag query
    $episodes_args = array(
        'post_type' => 'episode',
        'posts_per_page' => 12,
        'paged' => $paged,
        'tax_query' => array(
            array(
                'taxonomy' => 'post_tag',
                'field' => 'slug',
                'terms' => $current_tag->slug
            )
        ),
        'orderby' => 'meta_value',
        'meta_key' => 'release_date',
        'order' => 'DESC'
    );

    // Apply episode visibility filtering
    $episodes_args = flexpress_add_episode_visibility_to_query($episodes_args);
    $episodes_query = new WP_Query($episodes_args);
}
?>

<div class="site-main">
    <div class="container py-5">

        <?php if ($is_collection): ?>
            <!-- Collection Header -->
            <div class="collection-header mb-5">
                <?php if (!empty($collection_meta['featured_image'])): ?>
                    <div class="collection-featured-image-full mb-4">
                        <?php
                        $image = $collection_meta['featured_image'];
                        if (is_array($image)) {
                            echo '<img src="' . esc_url($image['sizes']['large']) . '" alt="' . esc_attr($current_tag->name) . '" class="img-fluid w-100">';
                        } else {
                            echo '<img src="' . esc_url($image) . '" alt="' . esc_attr($current_tag->name) . '" class="img-fluid w-100">';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <div class="text-left">
                    <h1 class="section-title mb-3">
                        <?php echo esc_html($current_tag->name); ?>
                    </h1>

                    <?php if (!empty($collection_meta['description'])): ?>
                        <div class="collection-description lead mb-4 text-left">
                            <?php echo wp_kses_post(nl2br($collection_meta['description'])); ?>
                        </div>
                    <?php endif; ?>

                    <div class="collection-stats text-left">
                        <span class="badge bg-secondary me-2">
                            <i class="fas fa-video me-1"></i>
                            <?php echo flexpress_get_collection_count($current_tag); ?> Episodes
                        </span>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Regular Tag Header -->
            <div class="tag-header text-center mb-5">
                <h1 class="display-4 mb-3">
                    <i class="fas fa-tag me-2"></i>
                    <?php echo esc_html($current_tag->name); ?>
                </h1>
                <p class="lead text-muted">
                    <?php echo flexpress_get_collection_count($current_tag); ?> episodes tagged with "<?php echo esc_html($current_tag->name); ?>"
                </p>
            </div>
        <?php endif; ?>

        <!-- Episodes Grid -->
        <?php if ($episodes_query->have_posts()): ?>
            <div class="episodes-grid">
                <div class="row g-4">
                    <?php while ($episodes_query->have_posts()): $episodes_query->the_post(); ?>
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <?php get_template_part('template-parts/content', 'episode-card'); ?>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($episodes_query->max_num_pages > 1): ?>
                    <div class="pagination-wrapper mt-5">
                        <nav aria-label="Episode pagination">
                            <?php
                            echo paginate_links(array(
                                'total' => $episodes_query->max_num_pages,
                                'current' => $paged,
                                'format' => '?paged=%#%',
                                'show_all' => false,
                                'type' => 'list',
                                'end_size' => 2,
                                'mid_size' => 1,
                                'prev_text' => '<i class="fas fa-chevron-left"></i> Previous',
                                'next_text' => 'Next <i class="fas fa-chevron-right"></i>',
                                'add_args' => false,
                                'add_fragment' => '',
                            ));
                            ?>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- No Episodes Found -->
            <div class="no-episodes text-center py-5">
                <div class="card">
                    <div class="card-body">
                        <i class="fas fa-video-slash fa-3x text-muted mb-3"></i>
                        <h3 class="card-title">No Episodes Found</h3>
                        <p class="card-text text-muted">
                            <?php if ($is_collection): ?>
                                This collection doesn't have any episodes yet.
                            <?php else: ?>
                                No episodes are tagged with "<?php echo esc_html($current_tag->name); ?>".
                            <?php endif; ?>
                        </p>
                        <a href="<?php echo esc_url(home_url('/episodes/')); ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Browse All Episodes
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>

        <!-- Related Collections (if this is a regular tag) -->
        <?php if (!$is_collection): ?>
            <?php
            // Get other collection tags for related collections
            $collection_tags = get_terms(array(
                'taxonomy' => 'post_tag',
                'hide_empty' => true,
                'meta_query' => array(
                    array(
                        'key' => 'is_collection_tag',
                        'value' => '1',
                        'compare' => '='
                    )
                ),
                'number' => 3
            ));

            if (!empty($collection_tags) && !is_wp_error($collection_tags)):
            ?>
                <div class="related-collections mt-5">
                    <h3 class="section-title mb-4">
                        <i class="fas fa-layer-group me-2"></i>
                        Explore Collections
                    </h3>
                    <div class="row g-4">
                        <?php foreach ($collection_tags as $collection_tag): ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="card collection-card h-100">
                                    <?php
                                    $collection_meta = flexpress_get_collection_metadata($collection_tag);
                                    if (!empty($collection_meta['featured_image'])):
                                        $image = $collection_meta['featured_image'];
                                        $image_url = is_array($image) ? $image['sizes']['medium'] : $image;
                                    ?>
                                        <img src="<?php echo esc_url($image_url); ?>" class="card-img-top" alt="<?php echo esc_attr($collection_tag->name); ?>">
                                    <?php endif; ?>
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title">
                                            <?php echo esc_html($collection_tag->name); ?>
                                            <span class="badge bg-primary ms-1">Collection</span>
                                        </h5>
                                        <?php if (!empty($collection_meta['description'])): ?>
                                            <p class="card-text text-muted small">
                                                <?php echo wp_trim_words($collection_meta['description'], 15); ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-video me-1"></i>
                                                    <?php echo flexpress_get_collection_count($collection_tag); ?> episodes
                                                </small>
                                                <a href="<?php echo esc_url(get_term_link($collection_tag)); ?>" class="btn btn-sm btn-outline-primary">
                                                    View Collection
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>