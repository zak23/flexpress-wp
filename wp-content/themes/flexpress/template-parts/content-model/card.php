<?php

/**
 * Template part for displaying model cards - Vixen.com Style
 *
 * @package FlexPress
 */
?>

<a href="<?php the_permalink(); ?>" class="model-card-link">
    <div class="card model-card">
        <?php if (has_post_thumbnail()) : ?>
            <?php
            $thumbnail_id = get_post_thumbnail_id();
            $original_url = wp_get_attachment_image_url($thumbnail_id, 'full');
            $alt_text = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
            if (empty($alt_text)) {
                $alt_text = get_the_title();
            }

            // Generate optimized URL using Bunny Image Optimizer
            // Match CSS aspect-ratio: 3/4 (248:331)
            if (function_exists('flexpress_get_bunnycdn_optimized_image_url')) {
                $optimized_url = flexpress_get_bunnycdn_optimized_image_url($original_url, array(
                    'width' => 248,
                    'height' => 331,
                    'format' => 'webp',
                    'quality' => 60
                ));
            } else {
                $optimized_url = $original_url;
            }
            ?>
            <img src="<?php echo esc_url($optimized_url); ?>"
                alt="<?php echo esc_attr($alt_text); ?>"
                class="model-image"
                sizes="(max-width: 768px) 184px, 368px"
                loading="lazy"
                decoding="async">
        <?php else: ?>
            <div class="model-placeholder">
                <i class="fa-solid fa-user model-placeholder-icon"></i>
            </div>
        <?php endif; ?>

        <!-- Center overlay for magnifying glass button -->
        <div class="model-center-overlay">
            <div class="magnifying-button">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
        </div>

        <!-- Bottom overlay for text -->
        <div class="model-text-overlay">
            <h5 class="card-title"><?php the_title(); ?></h5>
        </div>
    </div>
</a>