<?php
/**
 * Template Name: Episodes
 */

get_header();
?>

<div class="site-main">
    <div class="container py-5">
        <!-- Hero Section -->
        <div class="text-center mb-5">
            <h1 class="display-4 mb-4"><?php the_title(); ?></h1>
            <p class="lead mb-4"><?php the_content(); ?></p>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label"><?php esc_html_e('Status', 'flexpress'); ?></label>
                        <select class="form-select" id="status" name="status">
                            <option value=""><?php esc_html_e('All', 'flexpress'); ?></option>
                            <?php
                            $statuses = get_terms(array(
                                'taxonomy' => 'episode_status',
                                'hide_empty' => true
                            ));
                            
                            foreach ($statuses as $status) {
                                $selected = isset($_GET['status']) && $_GET['status'] === $status->slug ? 'selected' : '';
                                echo '<option value="' . esc_attr($status->slug) . '" ' . $selected . '>' . esc_html($status->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="sort" class="form-label"><?php esc_html_e('Sort By', 'flexpress'); ?></label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="newest" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'newest'); ?>>
                                <?php esc_html_e('Newest First', 'flexpress'); ?>
                            </option>
                            <option value="oldest" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'oldest'); ?>>
                                <?php esc_html_e('Oldest First', 'flexpress'); ?>
                            </option>
                            <option value="price_asc" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'price_asc'); ?>>
                                <?php esc_html_e('Price: Low to High', 'flexpress'); ?>
                            </option>
                            <option value="price_desc" <?php selected(isset($_GET['sort']) && $_GET['sort'] === 'price_desc'); ?>>
                                <?php esc_html_e('Price: High to Low', 'flexpress'); ?>
                            </option>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <?php esc_html_e('Apply Filters', 'flexpress'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Episodes Grid -->
        <div class="row g-4">
            <?php
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $args = array(
                'post_type' => 'episode',
                'posts_per_page' => 12,
                'paged' => $paged,
                'orderby' => 'meta_value',
                'meta_key' => 'release_date',
                'order' => 'DESC'
            );

            // Add status filter
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'episode_status',
                        'field' => 'slug',
                        'terms' => sanitize_text_field($_GET['status'])
                    )
                );
            }

            // Add sorting
            if (isset($_GET['sort'])) {
                switch ($_GET['sort']) {
                    case 'oldest':
                        $args['order'] = 'ASC';
                        break;
                    case 'price_asc':
                        $args['orderby'] = 'meta_value_num';
                        $args['meta_key'] = 'episode_price';
                        $args['order'] = 'ASC';
                        break;
                    case 'price_desc':
                        $args['orderby'] = 'meta_value_num';
                        $args['meta_key'] = 'episode_price';
                        $args['order'] = 'DESC';
                        break;
                }
            }

            $query = new WP_Query($args);

            if ($query->have_posts()):
                while ($query->have_posts()): $query->the_post();
                    get_template_part('template-parts/content', 'episode-card');
                endwhile;
                wp_reset_postdata();
            else:
            ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="bi bi-collection-play display-1 text-muted mb-3"></i>
                        <h3 class="h5 mb-3"><?php esc_html_e('No Episodes Found', 'flexpress'); ?></h3>
                        <p class="text-muted mb-4"><?php esc_html_e('Try adjusting your filters to find what you\'re looking for.', 'flexpress'); ?></p>
                        <a href="<?php echo esc_url(home_url('/episodes')); ?>" class="btn btn-primary">
                            <?php esc_html_e('Clear Filters', 'flexpress'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($query->max_num_pages > 1): ?>
            <div class="mt-5">
                <?php
                echo paginate_links(array(
                    'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                    'format' => '?paged=%#%',
                    'current' => max(1, get_query_var('paged')),
                    'total' => $query->max_num_pages,
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