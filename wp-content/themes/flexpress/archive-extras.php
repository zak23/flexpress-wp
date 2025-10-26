<?php

/**
 * The template for displaying extras archives - Vixen.com Style with Sidebar Filters
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
$orderby = 'meta_value';
$meta_key = 'release_date';

switch ($sort) {
    case 'oldest':
        $order = 'ASC';
        break;
    case 'title':
        $orderby = 'title';
        $meta_key = '';
        $order = 'ASC';
        break;
    case 'newest':
    default:
        $order = 'DESC';
        break;
}

// Get current page for pagination
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Query extras
$extras_args = array(
    'post_type' => 'extras',
    'posts_per_page' => 16, // 8 extras per page (2 per row, 4 rows)
    'paged' => $paged,
    'meta_query' => array(
        array(
            'key' => 'release_date',
            'value' => current_time('mysql'),
            'compare' => '<=',
            'type' => 'DATETIME'
        )
    ),
    'orderby' => $orderby,
    'order' => $order
);

if (!empty($meta_key)) {
    $extras_args['meta_key'] = $meta_key;
}

// Add filters based on filter type
if (!empty($filter_type) && !empty($filter_value)) {
    switch ($filter_type) {
        case 'category':
            $extras_args['tax_query'] = array(
                array(
                    'taxonomy' => 'post_tag',
                    'field' => 'slug',
                    'terms' => $filter_value
                )
            );
            break;

        case 'model':
            $extras_args['meta_query'][] = array(
                'key' => 'featured_models',
                'value' => '"' . intval($filter_value) . '"',
                'compare' => 'LIKE'
            );
            break;

        case 'content_type':
            $extras_args['meta_query'][] = array(
                'key' => 'content_type',
                'value' => $filter_value,
                'compare' => '='
            );
            break;

        case 'alpha':
            // Filter by starting letter and sort alphabetically
            $extras_args['orderby'] = 'title';
            $extras_args['order'] = 'ASC';
            unset($extras_args['meta_key']);

            // Add a filter to only show posts starting with the selected letter
            add_filter('posts_where', function ($where) use ($filter_value) {
                global $wpdb;
                $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE %s", $filter_value . '%');
                return $where;
            });
            break;
    }
}

// Apply extras visibility filtering
$extras_args = flexpress_add_extras_visibility_to_query($extras_args);

$extras_query = new WP_Query($extras_args);
?>

<div class="site-main">
    <div class="container py-5">
        <!-- Page Header -->
        <div class="text-center mb-5">
            <h1 class="display-4 mb-4 text-uppercase"><?php esc_html_e('Extras', 'flexpress'); ?></h1>
            <button id="toggle-filters" class="btn btn-outline-light btn-sm">
                <i class="fas fa-filter me-2"></i>
                <span id="filter-toggle-text"><?php esc_html_e('Hide Filters', 'flexpress'); ?></span>
            </button>
        </div>

        <div class="row" id="main-content-row">
            <!-- Main Content - 8 Columns (Dynamic) -->
            <div class="col-lg-8" id="main-content-col">
                <!-- Extras Grid - 2 videos wide (Dynamic) -->
                <?php if ($extras_query->have_posts()): ?>
                    <div class="extras-grid">
                        <div class="row g-4" id="extras-grid">
                            <?php while ($extras_query->have_posts()): $extras_query->the_post(); ?>
                                <div class="col-6 extras-grid-item">
                                    <?php get_template_part('template-parts/content', 'extras-card'); ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>


                    <?php
                    $total_pages = $extras_query->max_num_pages;
                    if ($total_pages > 1):
                    ?>
                        <nav class="pagination-nav mt-5">
                            <div class="pagination-wrapper text-center">
                                <?php

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
                    <!-- No Extras Found -->
                    <div class="text-center py-5">
                        <i class="bi bi-collection-play display-1 text-muted mb-3"></i>
                        <h3 class="h5 mb-3"><?php esc_html_e('No Extras Found', 'flexpress'); ?></h3>
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
                            <h5 class="mb-0"><?php esc_html_e('Filter Extras', 'flexpress'); ?></h5>
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
                                        <option value="content_type" <?php echo ($filter_type === 'content_type') ? 'selected' : ''; ?>><?php esc_html_e('Content Type', 'flexpress'); ?></option>
                                        <option value="model" <?php echo ($filter_type === 'model') ? 'selected' : ''; ?>><?php esc_html_e('Models', 'flexpress'); ?></option>
                                    </select>
                                </div>

                                <!-- Category Filter Section -->
                                <div id="category-filters" class="filter-section" style="display: <?php echo ($filter_type === 'category' || empty($filter_type)) ? 'block' : 'none'; ?>;">
                                    <div class="filter-header">
                                        <i class="fas fa-tags me-2"></i>
                                        <?php esc_html_e('Categories', 'flexpress'); ?>
                                    </div>
                                    <?php
                                    // Get tags that are actually used on extras posts
                                    $extras_tags = get_terms(array(
                                        'taxonomy' => 'post_tag',
                                        'hide_empty' => true,
                                        'orderby' => 'name',
                                        'order' => 'ASC',
                                        'object_ids' => get_posts(array(
                                            'post_type' => 'extras',
                                            'posts_per_page' => -1,
                                            'fields' => 'ids'
                                        ))
                                    ));

                                    if (!empty($extras_tags) && !is_wp_error($extras_tags)):
                                        foreach ($extras_tags as $tag):
                                            // Count only extras with this tag
                                            $tag_count = $wpdb->get_var($wpdb->prepare(
                                                "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
                                                INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                                                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                                                WHERE p.post_type = 'extras' AND p.post_status = 'publish' AND tt.term_id = %d",
                                                $tag->term_id
                                            ));
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

                                <!-- Content Type Filter Section -->
                                <div id="content-type-filters" class="filter-section" style="display: <?php echo ($filter_type === 'content_type') ? 'block' : 'none'; ?>;">
                                    <div class="filter-header">
                                        <i class="fas fa-video me-2"></i>
                                        <?php esc_html_e('Content Type', 'flexpress'); ?>
                                    </div>
                                    <?php
                                    $content_types = array(
                                        'behind_scenes' => 'Behind the Scenes',
                                        'bloopers' => 'Bloopers',
                                        'interviews' => 'Interviews',
                                        'photo_shoots' => 'Photo Shoots',
                                        'making_of' => 'Making Of',
                                        'deleted_scenes' => 'Deleted Scenes',
                                        'extended_cuts' => 'Extended Cuts',
                                        'other' => 'Other'
                                    );

                                    foreach ($content_types as $type_key => $type_label):
                                    ?>
                                        <a href="<?php echo esc_url(add_query_arg(array('filter_type' => 'content_type', 'filter_value' => $type_key))); ?>"
                                            class="filter-item <?php echo ($filter_type === 'content_type' && $filter_value === $type_key) ? 'active' : ''; ?>">
                                            <?php echo esc_html($type_label); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Models Filter Section -->
                                <?php
                                // Get only models that are featured in extras posts
                                global $wpdb;
                                $model_ids_in_extras = $wpdb->get_col(
                                    "SELECT DISTINCT meta_value 
                                    FROM {$wpdb->postmeta} pm
                                    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                                    WHERE p.post_type = 'extras' 
                                    AND p.post_status = 'publish'
                                    AND pm.meta_key = 'featured_models'
                                    AND pm.meta_value != ''"
                                );

                                // Extract model IDs from serialized arrays
                                $model_ids = array();
                                foreach ($model_ids_in_extras as $serialized) {
                                    $unserialized = maybe_unserialize($serialized);
                                    if (is_array($unserialized)) {
                                        $model_ids = array_merge($model_ids, $unserialized);
                                    }
                                }
                                $model_ids = array_unique(array_filter($model_ids));

                                $models = array();
                                if (!empty($model_ids)) {
                                    $models = get_posts(array(
                                        'post_type' => 'model',
                                        'posts_per_page' => -1,
                                        'orderby' => 'title',
                                        'order' => 'ASC',
                                        'post__in' => $model_ids
                                    ));
                                }
                                ?>
                                <div id="model-filters" class="filter-section" style="display: <?php echo ($filter_type === 'model') ? 'block' : 'none'; ?>;">
                                    <div class="filter-header">
                                        <i class="fas fa-user me-2"></i>
                                        <?php esc_html_e('Models', 'flexpress'); ?>
                                    </div>
                                    <?php if (!empty($models)): ?>
                                        <?php foreach ($models as $model): ?>
                                            <a href="<?php echo esc_url(add_query_arg(array('filter_type' => 'model', 'filter_value' => $model->ID))); ?>"
                                                class="filter-item <?php echo ($filter_type === 'model' && $filter_value == $model->ID) ? 'active' : ''; ?>">
                                                <?php echo esc_html($model->post_title); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-muted small px-3 py-2">
                                            <?php esc_html_e('No models found', 'flexpress'); ?>
                                        </div>
                                    <?php endif; ?>
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

                                    case 'content_type':
                                        $content_types = array(
                                            'behind_scenes' => 'Behind the Scenes',
                                            'bloopers' => 'Bloopers',
                                            'interviews' => 'Interviews',
                                            'photo_shoots' => 'Photo Shoots',
                                            'making_of' => 'Making Of',
                                            'deleted_scenes' => 'Deleted Scenes',
                                            'extended_cuts' => 'Extended Cuts',
                                            'other' => 'Other'
                                        );
                                        if (isset($content_types[$filter_value])) {
                                            $active_filters[] = 'Type: ' . $content_types[$filter_value];
                                        }
                                        break;

                                    case 'model':
                                        $model_post = get_post($filter_value);
                                        if ($model_post) {
                                            $active_filters[] = 'Model: ' . $model_post->post_title;
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
        const contentTypeFilters = document.getElementById('content-type-filters');
        const modelFilters = document.getElementById('model-filters');
        const toggleButton = document.getElementById('toggle-filters');
        const toggleText = document.getElementById('filter-toggle-text');
        const sidebarCol = document.getElementById('sidebar-col');
        const mainContentCol = document.getElementById('main-content-col');
        const extrasGridItems = document.querySelectorAll('.extras-grid-item');

        let filtersVisible = true;

        // Filter dropdown functionality
        if (filterSelect) {
            filterSelect.addEventListener('change', function() {
                const selectedValue = this.value;

                // Hide all filter sections
                categoryFilters.style.display = 'none';
                contentTypeFilters.style.display = 'none';
                modelFilters.style.display = 'none';

                // Show the selected filter section
                if (selectedValue === 'category') {
                    categoryFilters.style.display = 'block';
                } else if (selectedValue === 'content_type') {
                    contentTypeFilters.style.display = 'block';
                } else if (selectedValue === 'model') {
                    modelFilters.style.display = 'block';
                }
            });
        }

        // Toggle filters functionality
        if (toggleButton) {
            toggleButton.addEventListener('click', function() {
                filtersVisible = !filtersVisible;

                if (filtersVisible) {
                    // Show filters - 8/4 layout, 2 videos per row
                    sidebarCol.style.display = 'block';
                    mainContentCol.classList.remove('col-lg-12');
                    mainContentCol.classList.add('col-lg-8');
                    extrasGridItems.forEach(item => {
                        item.classList.remove('col-lg-4');
                        item.classList.add('col-6');
                    });
                    toggleText.textContent = '<?php esc_html_e('Hide Filters', 'flexpress'); ?>';
                } else {
                    // Hide filters - 12 column layout, 3 videos per row
                    sidebarCol.style.display = 'none';
                    mainContentCol.classList.remove('col-lg-8');
                    mainContentCol.classList.add('col-lg-12');
                    extrasGridItems.forEach(item => {
                        item.classList.remove('col-6');
                        item.classList.add('col-lg-4');
                    });
                    toggleText.textContent = '<?php esc_html_e('Show Filters', 'flexpress'); ?>';
                }
            });
        }
    });
</script>

<?php
get_footer();
