<?php
/**
 * Episode Rating System Template Part
 * Displays rating form, statistics, and ratings list for episodes
 */

if (!defined('ABSPATH')) {
    exit;
}

$episode_id = get_the_ID();
$has_access = get_query_var('has_access', false);
$ratings_system = new FlexPress_Episode_Ratings();

// Get rating statistics
$stats = $ratings_system->get_episode_rating_stats($episode_id);

// Get current user's rating (if logged in)
$user_rating = $ratings_system->get_current_user_rating($episode_id);

// Get recent ratings
$recent_ratings = $ratings_system->get_episode_ratings_paginated($episode_id, 1, 3);
?>

<div class="episode-rating-sidebar">
    <?php if (is_user_logged_in()): ?>
        <h5 class="text-white mb-3 text-center">
            <i class="fas fa-star me-2"></i>
            <?php esc_html_e('Rate This Episode', 'flexpress'); ?>
        </h5>
    <?php endif; ?>
    
    <!-- Rating Statistics -->
    <?php echo $ratings_system->display_rating_stats($episode_id); ?>
    
    <!-- Rating Form -->
    <?php if ($has_access): ?>
        <?php echo $ratings_system->display_rating_form($episode_id); ?>
    <?php elseif (is_user_logged_in()): ?>
        <div class="rating-access-notice p-3 bg-dark border border-secondary rounded text-center">
            <i class="fas fa-lock fa-2x text-secondary mb-2"></i>
            <h6 class="text-white mb-2"><?php esc_html_e('Unlock Episode to Rate', 'flexpress'); ?></h6>
            <p class="text-secondary mb-0 small">
                <?php esc_html_e('You need to unlock this episode before you can rate it.', 'flexpress'); ?>
            </p>
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
