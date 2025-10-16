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
            echo wp_get_attachment_image($thumbnail_id, 'model-card-alt', false, array(
                'class' => 'model-image',
                'sizes' => '(max-width: 768px) 184px, 368px',
                'srcset' => wp_get_attachment_image_srcset($thumbnail_id, 'model-card-alt')
            )); 
            ?>
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