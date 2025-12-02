<?php

/**
 * Template Name: News
 */

get_header();

// Ensure cache headers for logged-in users
if (function_exists('flexpress_add_performance_headers')) {
    flexpress_add_performance_headers();
}
?>

<div class="site-main news-archive">
    <div class="container py-5">
        <!-- Hero Section -->
        <div class="text-center mb-5">
            <h1 class="display-4 mb-4"><?php the_title(); ?></h1>
            <?php if (get_the_content()): ?>
                <p class="lead mb-4"><?php the_content(); ?></p>
            <?php endif; ?>
        </div>

        <!-- News Posts Grid -->
        <div class="row g-4">
            <?php
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $args = array(
                'post_type' => 'post',
                'posts_per_page' => 12,
                'paged' => $paged,
                'orderby' => 'date',
                'order' => 'DESC',
                'post_status' => 'publish'
            );

            $news_query = new WP_Query($args);

            if ($news_query->have_posts()):
                while ($news_query->have_posts()): $news_query->the_post();
                    get_template_part('template-parts/content', 'post-card');
                endwhile;
                wp_reset_postdata();
            else:
            ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-newspaper display-1 text-muted mb-3"></i>
                        <h3 class="h5 mb-3"><?php esc_html_e('No News Posts Found', 'flexpress'); ?></h3>
                        <p class="text-muted mb-4"><?php esc_html_e('Check back soon for updates and announcements.', 'flexpress'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($news_query->max_num_pages > 1): ?>
            <div class="mt-5">
                <?php
                echo paginate_links(array(
                    'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                    'format' => '?paged=%#%',
                    'current' => max(1, get_query_var('paged')),
                    'total' => $news_query->max_num_pages,
                    'prev_text' => '<i class="bi bi-chevron-left"></i>',
                    'next_text' => '<i class="bi bi-chevron-right"></i>',
                    'type' => 'list',
                    'class' => 'pagination justify-content-center'
                ));
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
