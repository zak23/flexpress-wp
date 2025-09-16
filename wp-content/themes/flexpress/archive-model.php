<?php
/**
 * The template for displaying model archives
 *
 * @package FlexPress
 */

get_header();

// Get pagination info for AJAX
global $wp_query;
$max_pages = $wp_query->max_num_pages;
$current_page = get_query_var('paged') ? get_query_var('paged') : 1;
?>

<div class="site-main">
    <div class="container py-5">
        <!-- Archive Header -->
        <div class="text-center mb-5">
            <h1 class="display-4 mb-4"><?php esc_html_e('Models', 'flexpress'); ?></h1>
            <p class="lead text-muted mb-4"><?php esc_html_e('Meet our featured models', 'flexpress'); ?></p>
        </div>

        <!-- Models Grid - 6 per row (2 cols each) with AJAX support -->
        <?php if (have_posts()) : ?>
            <div id="models-container" class="row g-4" data-page="<?php echo esc_attr($current_page); ?>" data-max-pages="<?php echo esc_attr($max_pages); ?>">
                <?php while (have_posts()) : the_post(); ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
                        <?php get_template_part('template-parts/content-model/card'); ?>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Loading indicator -->
            <div id="models-loading" class="text-center mt-4" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading more models...</span>
                </div>
                <p class="mt-2">Loading more models...</p>
            </div>

            <!-- Load more button (fallback for manual loading) -->
            <?php if ($max_pages > 1) : ?>
                <div id="load-more-container" class="text-center mt-5">
                    <button id="load-more-models" class="btn btn-primary btn-lg" style="display: none;">
                        Load More Models
                    </button>
                </div>
            <?php endif; ?>

            <!-- No more models message -->
            <div id="no-more-models" class="text-center mt-4" style="display: none;">
                <p class="text-muted">You've seen all our beautiful models!</p>
            </div>

        <?php else : ?>
            <div class="text-center py-5">
                <i class="fas fa-user-slash display-1 text-muted mb-3"></i>
                <h3 class="h5 mb-3"><?php esc_html_e('No Models Found', 'flexpress'); ?></h3>
                <p class="text-muted mb-4"><?php esc_html_e('Check back soon for new models.', 'flexpress'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer(); 