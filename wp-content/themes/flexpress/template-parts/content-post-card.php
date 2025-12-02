<?php

/**
 * Template part for displaying news/post cards
 */

$featured_image_id = get_post_thumbnail_id();
$featured_image_url = '';
?>

<div class="col-md-6 col-lg-4">
    <article class="news-card card h-100">
        <?php if ($featured_image_id): ?>
            <?php
            // Use BunnyCDN optimized image if available
            if (function_exists('flexpress_get_bunnycdn_optimized_image_url')) {
                $featured_image_url = flexpress_get_bunnycdn_optimized_image_url(
                    $featured_image_id,
                    array(
                        'width' => 600,
                        'height' => 400,
                        'format' => 'webp',
                        'quality' => 80
                    )
                );
            } else {
                $featured_image = wp_get_attachment_image_src($featured_image_id, 'medium_large');
                $featured_image_url = $featured_image ? $featured_image[0] : '';
            }
            ?>
            <a href="<?php the_permalink(); ?>" class="news-card-image-link">
                <img src="<?php echo esc_url($featured_image_url); ?>" 
                     alt="<?php echo esc_attr(get_the_title()); ?>" 
                     class="card-img-top news-card-image"
                     loading="lazy">
            </a>
        <?php else: ?>
            <div class="news-card-placeholder card-img-top">
                <i class="fas fa-newspaper"></i>
            </div>
        <?php endif; ?>

        <div class="card-body d-flex flex-column">
            <h3 class="card-title news-card-title">
                <a href="<?php the_permalink(); ?>" class="text-decoration-none">
                    <?php the_title(); ?>
                </a>
            </h3>

            <div class="news-card-meta text-muted small mb-3">
                <i class="far fa-calendar-alt me-1"></i>
                <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                    <?php echo esc_html(get_the_date('j F Y')); ?>
                </time>
            </div>

            <?php if (has_excerpt()): ?>
                <p class="card-text news-card-excerpt flex-grow-1">
                    <?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?>
                </p>
            <?php elseif (get_the_content()): ?>
                <p class="card-text news-card-excerpt flex-grow-1">
                    <?php echo esc_html(wp_trim_words(get_the_content(), 20)); ?>
                </p>
            <?php endif; ?>

            <a href="<?php the_permalink(); ?>" class="btn btn-outline-primary mt-auto">
                <?php esc_html_e('Read More', 'flexpress'); ?>
                <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </article>
</div>

