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
            <?php the_post_thumbnail('model-card', array('class' => 'model-image')); ?>
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