<?php

/**
 * The template for displaying model archives - Vixen.com Style with Sidebar Filters
 *
 * @package FlexPress
 */

get_header();

// Handle filtering parameters
$filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
$filter_value = isset($_GET['filter_value']) ? sanitize_text_field($_GET['filter_value']) : '';
$sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'newest';

// Build query args
$order = 'DESC';
$orderby = 'date';

switch ($sort) {
    case 'oldest':
        $order = 'ASC';
        break;
    case 'title':
        $orderby = 'title';
        $order = 'ASC';
        break;
    case 'newest':
    default:
        $order = 'DESC';
        break;
}

// Get current page for pagination
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Query models
$models_args = array(
    'post_type' => 'model',
    'posts_per_page' => 16, // 16 models per page (4 per row, 4 rows)
    'paged' => $paged,
    'orderby' => $orderby,
    'order' => $order
);

// Add filters based on filter type
if (!empty($filter_type) && !empty($filter_value)) {
    switch ($filter_type) {
        case 'category':
            $models_args['tax_query'] = array(
                array(
                    'taxonomy' => 'post_tag',
                    'field' => 'slug',
                    'terms' => $filter_value
                )
            );
            break;

        case 'alpha':
            // Filter by starting letter and sort alphabetically
            $models_args['orderby'] = 'title';
            $models_args['order'] = 'ASC';

            // Add a filter to only show posts starting with the selected letter
            add_filter('posts_where', function ($where) use ($filter_value) {
                global $wpdb;
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE %s", $filter_value . '%');
                return $where;
            });
            break;
    }
}

$models_query = new WP_Query($models_args);
?>

<div class="site-main archive-model">
    <div class="container py-5">
        <!-- Page Header -->
        <div class="text-center mb-5">
            <h1 class="display-4 mb-4 text-uppercase"><?php esc_html_e('Models', 'flexpress'); ?></h1>
            <button id="toggle-filters" class="btn btn-outline-light btn-sm">
                <i class="fas fa-filter me-2"></i>
                <span id="filter-toggle-text"><?php esc_html_e('Hide Filters', 'flexpress'); ?></span>
            </button>
        </div>

        <div class="row" id="main-content-row">
            <!-- Main Content - 8 Columns (Dynamic) -->
            <div class="col-lg-8" id="main-content-col">
                <!-- Models Grid - 3 models wide with filters, 4 models wide without filters -->
                <?php if ($models_query->have_posts()): ?>
                    <div class="model-grid">
                        <div class="row g-4" id="models-grid">
                            <?php while ($models_query->have_posts()): $models_query->the_post(); ?>
                                <div class="col-6 col-lg-4 model-grid-item">
                                    <?php get_template_part('template-parts/content-model/card'); ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Vixen-Style Pagination -->
                    <?php
                    $total_pages = $models_query->max_num_pages;
                    if ($total_pages > 1):
                    ?>
                        <nav class="pagination-nav mt-5">
                            <div class="pagination-wrapper text-center">
                                <?php
                                // Custom pagination similar to Vixen.com
                                $current_page = max(1, $paged);
                                $pagination_args = array(
                                    'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                                    'format' => '?paged=%#%',
                                    'current' => $current_page,
                                    'total' => $total_pages,
                                    'prev_text' => __('Back', 'flexpress'),
                                    'next_text' => __('Next', 'flexpress'),
                                    'type' => 'array',
                                    'show_all' => false,
                                    'end_size' => 1,
                                    'mid_size' => 2,
                                    'before_page_number' => '',
                                    'after_page_number' => ''
                                );

                                $pagination_links = paginate_links($pagination_args);

                                if ($pagination_links):
                                ?>
                                    <ul class="pagination justify-content-center">
                                        <!-- First -->
                                        <?php if ($current_page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo esc_url(get_pagenum_link(1)); ?>">
                                                    <?php esc_html_e('First', 'flexpress'); ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <!-- Previous -->
                                        <?php if ($current_page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo esc_url(get_pagenum_link($current_page - 1)); ?>">
                                                    <?php esc_html_e('Back', 'flexpress'); ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <!-- Page Numbers -->
                                        <?php
                                        foreach ($pagination_links as $link) {
                                            if (strpos($link, 'current') !== false) {
                                                echo '<li class="page-item active">' . str_replace('<span', '<span class="page-link"', $link) . '</li>';
                                            } else {
                                                echo '<li class="page-item">' . str_replace('<a', '<a class="page-link"', $link) . '</li>';
                                            }
                                        }
                                        ?>

                                        <!-- Next -->
                                        <?php if ($current_page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo esc_url(get_pagenum_link($current_page + 1)); ?>">
                                                    <?php esc_html_e('Next', 'flexpress'); ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <!-- Last -->
                                        <?php if ($current_page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="<?php echo esc_url(get_pagenum_link($total_pages)); ?>">
                                                    <?php esc_html_e('Last', 'flexpress'); ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>

                                    <!-- Page info like Vixen -->
                                    <div class="pagination-info mt-3">
                                        <span class="text-muted">
                                            <?php echo esc_html($current_page); ?> of <?php echo esc_html($total_pages); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- No Models Found -->
                    <div class="text-center py-5">
                        <i class="bi bi-person-x display-1 text-muted mb-3"></i>
                        <h3 class="h5 mb-3"><?php esc_html_e('No Models Found', 'flexpress'); ?></h3>
                        <p class="text-muted mb-4"><?php esc_html_e('Try adjusting your filters to find what you\'re looking for.', 'flexpress'); ?></p>
                        <a href="<?php echo esc_url(remove_query_arg(array('filter_type', 'filter_value', 'sort'))); ?>" class="btn btn-primary">
                            <?php esc_html_e('Clear Filters', 'flexpress'); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php wp_reset_postdata(); ?>
            </div>

            <!-- Sidebar Filters - 4 Columns -->
            <div class="col-lg-4" id="sidebar-col">
                <div class="filter-sidebar" id="filter-sidebar">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?php esc_html_e('Filter Models', 'flexpress'); ?></h5>
                        </div>
                        <div class="card-body">
                            <!-- Sort By -->
                            <div class="mb-3">
                                <h6 class="fw-bold text-uppercase mb-2"><?php esc_html_e('Sort By', 'flexpress'); ?></h6>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo esc_url(add_query_arg('sort', 'newest')); ?>"
                                        class="btn btn-sm <?php echo ($sort === 'newest') ? 'btn-primary' : 'btn-outline-secondary'; ?> flex-fill">
                                        <?php esc_html_e('Newest', 'flexpress'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(add_query_arg('sort', 'oldest')); ?>"
                                        class="btn btn-sm <?php echo ($sort === 'oldest') ? 'btn-primary' : 'btn-outline-secondary'; ?> flex-fill">
                                        <?php esc_html_e('Oldest', 'flexpress'); ?>
                                    </a>
                                </div>
                            </div>

                            <!-- Filter By Dropdown -->
                            <div class="mb-4">
                                <h6 class="fw-bold text-uppercase mb-3"><?php esc_html_e('Filter By', 'flexpress'); ?></h6>

                                <div class="mb-3">
                                    <select id="filter-type-select" class="form-select">
                                        <option value="category" <?php echo ($filter_type === 'category' || empty($filter_type)) ? 'selected' : ''; ?>><?php esc_html_e('Category', 'flexpress'); ?></option>
                                    </select>
                                </div>

                                <!-- Category Filter Section -->
                                <div id="category-filters" class="filter-section" style="display: <?php echo ($filter_type === 'category' || empty($filter_type)) ? 'block' : 'none'; ?>;">
                                    <div class="filter-header">
                                        <i class="fas fa-tags me-2"></i>
                                        <?php esc_html_e('Categories', 'flexpress'); ?>
                                    </div>
                                    <?php
                                    // Get tags actually used by model posts and count only models per tag
                                    $model_ids = get_posts(array(
                                        'post_type' => 'model',
                                        'posts_per_page' => -1,
                                        'fields' => 'ids'
                                    ));

                                    $model_tags = get_terms(array(
                                        'taxonomy' => 'post_tag',
                                        'hide_empty' => true,
                                        'orderby' => 'name',
                                        'order' => 'ASC',
                                        'object_ids' => $model_ids,
                                    ));

                                    if (!empty($model_tags) && !is_wp_error($model_tags)):
                                        global $wpdb;
                                        foreach ($model_tags as $tag):
                                            // Count only published model posts for this tag
                                            $tag_count = (int) $wpdb->get_var($wpdb->prepare(
                                                "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
                                                INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                                                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                                                WHERE p.post_type = 'model' AND p.post_status = 'publish' AND tt.term_id = %d",
                                                $tag->term_id
                                            ));

                                            if ($tag_count === 0) {
                                                continue;
                                            }
                                    ?>
                                            <a href="<?php echo esc_url(add_query_arg(array('filter_type' => 'category', 'filter_value' => $tag->slug))); ?>"
                                                class="filter-item <?php echo ($filter_type === 'category' && $filter_value === $tag->slug) ? 'active' : ''; ?>">
                                                <?php echo esc_html($tag->name); ?>
                                                <span class="filter-count">(<?php echo $tag_count; ?>)</span>
                                            </a>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </div>

                                <!-- Alphabet Filter -->
                                <div class="filter-section">
                                    <div class="filter-header">
                                        <i class="fas fa-sort-alpha-down me-2"></i>
                                        <?php esc_html_e('A - Z', 'flexpress'); ?>
                                    </div>
                                    <div class="alphabet-grid">
                                        <?php
                                        $letters = range('A', 'Z');
                                        foreach ($letters as $letter):
                                        ?>
                                            <a href="<?php echo esc_url(add_query_arg(array('filter_type' => 'alpha', 'filter_value' => $letter))); ?>"
                                                class="alphabet-item <?php echo ($filter_type === 'alpha' && $filter_value === $letter) ? 'active' : ''; ?>">
                                                <?php echo esc_html($letter); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Clear Filter -->
                                <?php if (!empty($filter_type)): ?>
                                    <div class="filter-section mt-4">
                                        <a href="<?php echo esc_url(remove_query_arg(array('filter_type', 'filter_value', 'sort'))); ?>"
                                            class="btn btn-outline-secondary w-100">
                                            <i class="fas fa-times me-2"></i>
                                            <?php esc_html_e('Clear All Filters', 'flexpress'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Active Filters Display -->
                            <?php
                            $active_filters = array();

                            if (!empty($filter_type) && !empty($filter_value)) {
                                switch ($filter_type) {
                                    case 'category':
                                        $tag_term = get_term_by('slug', $filter_value, 'post_tag');
                                        if ($tag_term) {
                                            $active_filters[] = 'Tag: ' . $tag_term->name;
                                        }
                                        break;

                                    case 'alpha':
                                        $active_filters[] = 'Letter: ' . strtoupper($filter_value);
                                        break;
                                }
                            }

                            if (!empty($active_filters)):
                            ?>
                                <div class="mt-4 pt-4 border-top">
                                    <h6 class="fw-bold mb-3"><?php esc_html_e('Active Filter', 'flexpress'); ?></h6>
                                    <?php foreach ($active_filters as $filter): ?>
                                        <span class="badge bg-primary me-2 mb-2"><?php echo esc_html($filter); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Join Now CTA -->
        <?php get_template_part('template-parts/join-now-cta'); ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterSelect = document.getElementById('filter-type-select');
        const categoryFilters = document.getElementById('category-filters');
        const toggleButton = document.getElementById('toggle-filters');
        const toggleText = document.getElementById('filter-toggle-text');
        const sidebarCol = document.getElementById('sidebar-col');
        const mainContentCol = document.getElementById('main-content-col');
        const modelGridItems = document.querySelectorAll('.model-grid-item');

        let filtersVisible = true;

        // Filter dropdown functionality
        if (filterSelect) {
            filterSelect.addEventListener('change', function() {
                const selectedValue = this.value;

                // Hide all filter sections
                categoryFilters.style.display = 'none';

                // Show the selected filter section
                if (selectedValue === 'category') {
                    categoryFilters.style.display = 'block';
                }
            });
        }

        // Toggle filters functionality
        if (toggleButton) {
            toggleButton.addEventListener('click', function() {
                filtersVisible = !filtersVisible;

                if (filtersVisible) {
                    // Show filters - 8/4 layout, 3 models per row
                    // Delay sidebar appearance to prevent DOM shifting
                    setTimeout(() => {
                        sidebarCol.style.display = 'block';
                    }, 100);

                    mainContentCol.classList.remove('col-lg-12');
                    mainContentCol.classList.add('col-lg-8');
                    modelGridItems.forEach(item => {
                        item.classList.remove('col-lg-3');
                        item.classList.add('col-6', 'col-lg-4');
                    });
                    toggleText.textContent = '<?php esc_html_e('Hide Filters', 'flexpress'); ?>';
                } else {
                    // Hide filters - 12 column layout, 4 models per row
                    sidebarCol.style.display = 'none';
                    mainContentCol.classList.remove('col-lg-8');
                    mainContentCol.classList.add('col-lg-12');
                    modelGridItems.forEach(item => {
                        item.classList.remove('col-6', 'col-lg-4');
                        item.classList.add('col-lg-3');
                    });
                    toggleText.textContent = '<?php esc_html_e('Show Filters', 'flexpress'); ?>';
                }
            });
        }
    });
</script>

<?php
get_footer();
