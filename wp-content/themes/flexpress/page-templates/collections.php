<?php

/**
 * Template Name: Collections
 *
 * Displays all collection tags in a list view with large cards
 */

get_header();

// Check if collections are enabled
if (!flexpress_collections_enabled()) {
    wp_safe_redirect(home_url('/episodes/'), 302);
    exit;
}

// Get settings
$collections_settings = get_option('flexpress_collections_settings', array());
$page_title = isset($collections_settings['collections_page_title']) && !empty($collections_settings['collections_page_title'])
    ? $collections_settings['collections_page_title']
    : 'Collections';
$page_description = isset($collections_settings['collections_page_description']) ? $collections_settings['collections_page_description'] : '';

// Get all collection tags
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
    'number' => 0 // Get all collections
));
?>

<div class="site-main">
    <div class="container py-5">

        <!-- Page Header -->
        <div class="text-center mb-5">
            <h1 class="display-4 mb-4 text-uppercase">
                <?php echo esc_html($page_title); ?>
            </h1>
            <?php if (!empty($page_description)): ?>
                <p class="lead text-muted">
                    <?php echo wp_kses_post($page_description); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Collections List -->
        <?php if (!empty($collection_tags) && !is_wp_error($collection_tags)): ?>
            <div class="collections-grid">
                <div class="row g-4">
                    <?php foreach ($collection_tags as $collection_tag): ?>
                        <?php
                        $collection_meta = flexpress_get_collection_metadata($collection_tag);
                        $episode_count = flexpress_get_collection_count($collection_tag);
                        ?>
                        <div class="col-lg-6 col-md-6">
                            <div class="collection-card card h-100" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px;">
                                <?php if (!empty($collection_meta['featured_image'])): ?>
                                    <a href="<?php echo esc_url(get_term_link($collection_tag)); ?>">
                                        <?php
                                        $image = $collection_meta['featured_image'];
                                        $image_url = is_array($image) && isset($image['sizes']['collection-card'])
                                            ? $image['sizes']['collection-card']
                                            : (is_array($image) ? $image['sizes']['medium'] : $image);
                                        ?>
                                        <img src="<?php echo esc_url($image_url); ?>" class="card-img-top" alt="<?php echo esc_attr($collection_tag->name); ?>">
                                    </a>
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title text-white">
                                        <a href="<?php echo esc_url(get_term_link($collection_tag)); ?>" class="text-decoration-none text-white">
                                            <?php echo esc_html($collection_tag->name); ?>
                                        </a>

                                    </h5>
                                    <?php if (!empty($collection_meta['description'])): ?>
                                        <p class="card-text text-white-50 small">
                                            <?php echo wp_trim_words(wp_kses_post($collection_meta['description']), 15); ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-white-50">
                                                <i class="fas fa-video me-1"></i>
                                                <?php echo esc_html($episode_count); ?> episodes
                                            </small>
                                            <a href="<?php echo esc_url(get_term_link($collection_tag)); ?>" class="btn btn-sm btn-outline-light">
                                                <?php esc_html_e('View Collection', 'flexpress'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Empty State -->
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="bi bi-collection-play display-1 text-muted mb-3"></i>
                    <h3 class="h5 mb-3"><?php esc_html_e('No Collections Available', 'flexpress'); ?></h3>
                    <p class="text-muted mb-4"><?php esc_html_e('There are no collections available at this time.', 'flexpress'); ?></p>
                    <a href="<?php echo esc_url(home_url('/episodes/')); ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>
                        <?php esc_html_e('Browse All Episodes', 'flexpress'); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>