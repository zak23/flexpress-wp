<?php
/**
 * Template part for displaying extras cards - Gallery-focused design
 */

$price = get_field('extras_price');
$release_date = get_field('release_date');
$featured_models = get_field('featured_models');
$content_type = get_field('content_type');
$content_format = get_field('content_format') ?: 'gallery';

// Get performer names from the relationship field
$performers = '';
if ($featured_models && !empty($featured_models)) {
    $model_names = array();
    foreach ($featured_models as $model) {
        $model_names[] = $model->post_title;
    }
    $performers = implode(', ', $model_names);
}

// Get thumbnail based on content format
$thumbnail = flexpress_get_extras_thumbnail(get_the_ID(), 'medium');
?>

<div class="extras-card">
    <a href="<?php the_permalink(); ?>" class="extras-link">
        <div class="card-img-top">
            <div class="preview-container"></div>
            <?php if ($thumbnail): ?>
                <img src="<?php echo esc_url($thumbnail['url']); ?>" 
                     alt="<?php echo esc_attr($thumbnail['alt']); ?>" 
                     class="extras-thumbnail">
            <?php else: ?>
                <?php flexpress_display_extras_thumbnail('medium', 'extras-thumbnail'); ?>
            <?php endif; ?>
            
            <div class="extras-overlay">
                <div class="extras-play-button">
                    <?php if ($content_format === 'video'): ?>
                        <i class="fa-solid fa-play"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-images"></i>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Content Format Badge -->
            <?php if ($content_format === 'gallery'): 
                $gallery = flexpress_get_extras_gallery(get_the_ID());
                if (!empty($gallery)): ?>
                    <div class="gallery-count-badge">
                        <i class="fas fa-images me-1"></i>
                        <?php echo count($gallery); ?>
                    </div>
                <?php endif; ?>
            <?php elseif ($content_format === 'video' && $content_type): ?>
                <div class="video-duration-badge">
                    <i class="fas fa-tag me-1"></i>
                    <?php echo esc_html(ucwords(str_replace('_', ' ', $content_type))); ?>
                </div>
            <?php endif; ?>
        </div>
    </a>
    
    <!-- Extras Information Below Thumbnail -->
    <div class="extras-info">
        <div class="extras-info-row">
            <h5 class="extras-title">
                <a href="<?php the_permalink(); ?>" class="extras-title-link"><?php the_title(); ?></a>
            </h5>
        </div>
        
        <div class="extras-info-row">
            <?php if ($featured_models && !empty($featured_models)): ?>
            <div class="extras-performers">
                <?php 
                $model_links = array();
                foreach ($featured_models as $model) {
                    $model_links[] = '<a href="' . esc_url(get_permalink($model->ID)) . '" class="model-link">' . esc_html($model->post_title) . '</a>';
                }
                echo implode(', ', $model_links);
                ?>
            </div>
            <?php endif; ?>
            
            <?php 
            if ($release_date) {
                // Handle multiple date formats
                $timestamp = false;
                
                // Try different date formats
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $release_date, $matches)) {
                    // UK format: dd/mm/yyyy
                    $timestamp = mktime(0, 0, 0, $matches[2], $matches[1], $matches[3]);
                } else {
                    // Try standard strtotime
                    $timestamp = strtotime($release_date);
                }
                
                if ($timestamp && $timestamp > 0) {
                    $formatted_date = strtoupper(date('F d, Y', $timestamp));
                } else {
                    // Fall back to WordPress post date
                    $formatted_date = strtoupper(get_the_date('F d, Y'));
                }
            } else {
                // Fall back to WordPress post date
                $formatted_date = strtoupper(get_the_date('F d, Y'));
            }
            ?>
            <span class="extras-date"><?php echo esc_html($formatted_date); ?></span>
        </div>
        
        <!-- Price Display -->
        <?php if ($price): ?>
        <div class="extras-info-row">
            <span class="extras-price">
                <i class="fas fa-tag me-1"></i>
                $<?php echo number_format($price, 2); ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
</div>