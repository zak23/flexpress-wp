<?php
/**
 * Template Name: Search Results
 */

get_header();
?>

<div class="site-main">
    <div class="container py-5">
        <!-- Search Header -->
        <div class="text-center mb-5">
            <h1 class="display-4 mb-4">
                <?php
                printf(
                    esc_html__('Search Results for "%s"', 'flexpress'),
                    '<span class="text-primary">' . get_search_query() . '</span>'
                );
                ?>
            </h1>
            <p class="lead mb-4">
                <?php
                printf(
                    esc_html__('Found %d results', 'flexpress'),
                    $wp_query->found_posts
                );
                ?>
            </p>
        </div>

        <!-- Search Form -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-8">
                <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                    <div class="input-group input-group-lg">
                        <input type="search" class="form-control" placeholder="<?php esc_attr_e('Search episodes...', 'flexpress'); ?>" value="<?php echo get_search_query(); ?>" name="s" title="<?php esc_attr_e('Search for:', 'flexpress'); ?>" />
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results -->
        <?php if (have_posts()): ?>
            <div class="row g-4">
                <?php
                while (have_posts()): the_post();
                    // Check if this is an episode and if user can view it
                    if (get_post_type() === 'episode') {
                        if (flexpress_can_user_view_episode(get_the_ID())) {
                            get_template_part('template-parts/content', 'episode-card');
                        }
                        // Skip hidden episodes for non-logged-in users
                    } else {
                        get_template_part('template-parts/content', 'search');
                    }
                endwhile;
                ?>
            </div>

            <!-- Pagination -->
            <?php if ($wp_query->max_num_pages > 1): ?>
                <div class="mt-5">
                    <?php
                    echo paginate_links(array(
                        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                        'format' => '?paged=%#%',
                        'current' => max(1, get_query_var('paged')),
                        'total' => $wp_query->max_num_pages,
                        'prev_text' => '<i class="bi bi-chevron-left"></i>',
                        'next_text' => '<i class="bi bi-chevron-right"></i>',
                        'type' => 'list',
                        'class' => 'pagination justify-content-center'
                    ));
                    ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-search display-1 text-muted mb-3"></i>
                <h3 class="h5 mb-3"><?php esc_html_e('No Results Found', 'flexpress'); ?></h3>
                <p class="text-muted mb-4"><?php esc_html_e('Try different keywords or check out all episodes.', 'flexpress'); ?></p>
                <a href="<?php echo esc_url(home_url('/episodes')); ?>" class="btn btn-primary">
                    <?php esc_html_e('Browse Episodes', 'flexpress'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer(); 