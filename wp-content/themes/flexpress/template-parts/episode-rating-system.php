<?php
/**
 * Episode Rating System Template Part
 * Displays rating form, statistics, and ratings list for episodes
 */

if (!defined('ABSPATH')) {
    exit;
}

$episode_id = get_the_ID();
$ratings_system = new FlexPress_Episode_Ratings();

// Get rating statistics
$stats = $ratings_system->get_episode_rating_stats($episode_id);

// Get current user's rating (if logged in)
$user_rating = $ratings_system->get_current_user_rating($episode_id);

// Get recent ratings
$recent_ratings = $ratings_system->get_episode_ratings_paginated($episode_id, 1, 3);
?>

<div class="episode-rating-sidebar">
    <h5 class="text-white mb-3 text-center">
        <i class="fas fa-star me-2"></i>
        <?php esc_html_e('Rate This Episode', 'flexpress'); ?>
    </h5>
    
    <!-- Rating Statistics -->
    <?php echo $ratings_system->display_rating_stats($episode_id); ?>
    
    <!-- Rating Form -->
    <?php echo $ratings_system->display_rating_form($episode_id); ?>
    
    <!-- Recent Ratings -->
    <?php if (!empty($recent_ratings)): ?>
        <div class="recent-ratings-sidebar mt-3">
            <h6 class="text-white mb-2"><?php esc_html_e('Recent Ratings', 'flexpress'); ?></h6>
            
            <div class="ratings-container-sidebar">
                <?php foreach ($recent_ratings as $rating): ?>
                    <div class="rating-item-sidebar border-bottom pb-2 mb-2">
                        <div class="rating-header-sidebar d-flex justify-content-between align-items-center mb-1">
                            <div class="rating-stars-small">
                                <?php echo $ratings_system->display_rating_stars($rating['rating']); ?>
                            </div>
                            <small class="text-muted">
                                <?php echo esc_html(date_i18n('M j', strtotime($rating['created_at']))); ?>
                            </small>
                        </div>
                        <div class="rating-user-sidebar mb-1">
                            <small class="text-white"><?php echo esc_html($rating['user_name']); ?></small>
                        </div>
                        <?php if (!empty($rating['comment']) && strlen($rating['comment']) <= 100): ?>
                            <div class="rating-comment-sidebar">
                                <small class="text-muted"><?php echo esc_html($rating['comment']); ?></small>
                            </div>
                        <?php elseif (!empty($rating['comment'])): ?>
                            <div class="rating-comment-sidebar">
                                <small class="text-muted"><?php echo esc_html(substr($rating['comment'], 0, 100)) . '...'; ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($stats['total_ratings'] > 3): ?>
                <div class="text-center mt-2">
                    <button class="btn btn-outline-primary btn-sm load-more-ratings" 
                            data-episode-id="<?php echo esc_attr($episode_id); ?>" 
                            data-current-page="1">
                        <?php esc_html_e('Load More', 'flexpress'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize rating form if it exists
    if ($('.episode-rating-form').length) {
        // The rating form JavaScript is handled by episode-ratings.js
        console.log('Episode rating form initialized for episode <?php echo esc_js($episode_id); ?>');
    }
});
</script>
