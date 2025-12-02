<?php

/**
 * The template for displaying single blog posts
 */

get_header();

// Ensure cache headers for logged-in users
if (function_exists('flexpress_add_performance_headers')) {
    flexpress_add_performance_headers();
}

while (have_posts()):
    the_post();

    $featured_image_id = get_post_thumbnail_id();
    $tags = get_the_tags();
?>

    <div class="site-main news-single">
        <div class="container py-5">
            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'flexpress'); ?></a>
                    </li>
                    <?php
                    // Try to find news page
                    $news_page = get_page_by_path('news');
                    if ($news_page):
                    ?>
                        <li class="breadcrumb-item">
                            <a href="<?php echo esc_url(get_permalink($news_page)); ?>"><?php echo esc_html(get_the_title($news_page)); ?></a>
                        </li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page"><?php the_title(); ?></li>
                </ol>
            </nav>

            <article id="post-<?php the_ID(); ?>" <?php post_class('news-article'); ?>>
                <!-- Featured Image -->
                <?php if ($featured_image_id): ?>
                    <?php
                    // Use BunnyCDN optimized image if available
                    if (function_exists('flexpress_get_bunnycdn_optimized_image_url')) {
                        $featured_image_url = flexpress_get_bunnycdn_optimized_image_url(
                            $featured_image_id,
                            array(
                                'width' => 1200,
                                'height' => 630,
                                'format' => 'webp',
                                'quality' => 85
                            )
                        );
                    } else {
                        $featured_image = wp_get_attachment_image_src($featured_image_id, 'large');
                        $featured_image_url = $featured_image ? $featured_image[0] : '';
                    }
                    ?>
                    <div class="news-single-hero mb-4">
                        <img src="<?php echo esc_url($featured_image_url); ?>" 
                             alt="<?php echo esc_attr(get_the_title()); ?>" 
                             class="img-fluid rounded"
                             loading="eager">
                    </div>
                <?php endif; ?>

                <!-- Post Header -->
                <header class="news-single-header mb-4">
                    <h1 class="news-single-title mb-3"><?php the_title(); ?></h1>

                    <div class="news-single-meta text-muted mb-4">
                        <i class="far fa-calendar-alt me-2"></i>
                        <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                            <?php echo esc_html(get_the_date('j F Y')); ?>
                        </time>
                        <?php if (get_the_author()): ?>
                            <span class="ms-3">
                                <i class="far fa-user me-2"></i>
                                <?php echo esc_html(get_the_author()); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </header>

                <!-- Post Content -->
                <div class="news-single-content">
                    <?php the_content(); ?>
                </div>

                <!-- Post Footer -->
                <footer class="news-single-footer mt-5 pt-4 border-top">
                    <?php if (!empty($tags)): ?>
                        <div class="news-single-tags mb-4">
                            <h6 class="mb-2">
                                <i class="fas fa-tags me-2"></i>
                                <?php esc_html_e('Tags:', 'flexpress'); ?>
                            </h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($tags as $tag): ?>
                                    <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>" 
                                       class="badge bg-secondary text-decoration-none">
                                        <?php echo esc_html($tag->name); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Share Links -->
                    <div class="news-single-share mb-4">
                        <h6 class="mb-2">
                            <i class="fas fa-share-alt me-2"></i>
                            <?php esc_html_e('Share:', 'flexpress'); ?>
                        </h6>
                        <div class="d-flex gap-2">
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="fab fa-twitter me-1"></i>
                                <?php esc_html_e('Twitter', 'flexpress'); ?>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="fab fa-facebook me-1"></i>
                                <?php esc_html_e('Facebook', 'flexpress'); ?>
                            </a>
                            <button onclick="navigator.clipboard.writeText('<?php echo esc_js(get_permalink()); ?>')" 
                                    class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-link me-1"></i>
                                <?php esc_html_e('Copy Link', 'flexpress'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <nav class="news-single-navigation" aria-label="<?php esc_attr_e('Post navigation', 'flexpress'); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <?php
                                $prev_post = get_previous_post();
                                if ($prev_post):
                                ?>
                                    <a href="<?php echo esc_url(get_permalink($prev_post)); ?>" 
                                       class="btn btn-outline-primary w-100 text-start">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        <div>
                                            <small class="d-block text-muted"><?php esc_html_e('Previous', 'flexpress'); ?></small>
                                            <strong><?php echo esc_html(get_the_title($prev_post)); ?></strong>
                                        </div>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <?php
                                $next_post = get_next_post();
                                if ($next_post):
                                ?>
                                    <a href="<?php echo esc_url(get_permalink($next_post)); ?>" 
                                       class="btn btn-outline-primary w-100 text-end">
                                        <div>
                                            <small class="d-block text-muted"><?php esc_html_e('Next', 'flexpress'); ?></small>
                                            <strong><?php echo esc_html(get_the_title($next_post)); ?></strong>
                                        </div>
                                        <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </nav>
                </footer>
            </article>
        </div>
    </div>

<?php
endwhile;
get_footer();

