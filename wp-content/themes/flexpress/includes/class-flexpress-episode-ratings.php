<?php
/**
 * Episode Ratings System
 * 
 * Handles episode rating functionality for logged-in users
 */

if (!defined('ABSPATH')) {
    exit;
}

class FlexPress_Episode_Ratings {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'flexpress_episode_ratings';
        
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_submit_episode_rating', array($this, 'handle_rating_submission'));
        add_action('wp_ajax_nopriv_submit_episode_rating', array($this, 'handle_rating_submission'));
        add_action('wp_ajax_get_episode_ratings', array($this, 'get_episode_ratings'));
        add_action('wp_ajax_nopriv_get_episode_ratings', array($this, 'get_episode_ratings'));
        add_action('wp_ajax_remove_episode_rating', array($this, 'handle_rating_removal'));
        add_action('wp_ajax_nopriv_remove_episode_rating', array($this, 'handle_rating_removal'));
    }
    
    public function init() {
        $this->create_ratings_table();
    }
    
    /**
     * Create the episode ratings table
     */
    private function create_ratings_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            episode_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            rating tinyint(1) unsigned NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comment text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_episode (user_id, episode_id),
            KEY episode_id (episode_id),
            KEY user_id (user_id),
            KEY rating (rating),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Handle rating submission via AJAX
     */
    public function handle_rating_submission() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to rate episodes.', 'flexpress')
            ));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'episode_rating_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'flexpress')
            ));
        }
        
        $episode_id = intval($_POST['episode_id']);
        $rating = intval($_POST['rating']);
        $comment = sanitize_textarea_field($_POST['comment'] ?? '');
        
        // Validate episode exists
        if (!get_post($episode_id) || get_post_type($episode_id) !== 'episode') {
            wp_send_json_error(array(
                'message' => __('Invalid episode.', 'flexpress')
            ));
        }
        
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            wp_send_json_error(array(
                'message' => __('Rating must be between 1 and 5 stars.', 'flexpress')
            ));
        }
        
        $user_id = get_current_user_id();
        
        // Check if user already rated this episode
        $existing_rating = $this->get_user_rating($episode_id, $user_id);
        
        if ($existing_rating) {
            // Update existing rating
            $result = $this->update_rating($episode_id, $user_id, $rating, $comment);
            $action = 'updated';
        } else {
            // Create new rating
            $result = $this->create_rating($episode_id, $user_id, $rating, $comment);
            $action = 'created';
        }
        
        if ($result) {
            // Get updated rating statistics
            $stats = $this->get_episode_rating_stats($episode_id);
            
            wp_send_json_success(array(
                'message' => sprintf(__('Rating %s!', 'flexpress'), $action === 'submitted' ? 'submitted' : 'updated'),
                'stats' => $stats,
                'user_rating' => $rating,
                'action' => $action
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save rating.', 'flexpress')
            ));
        }
    }
    
    /**
     * Get episode ratings via AJAX
     */
    public function get_episode_ratings() {
        $episode_id = intval($_GET['episode_id']);
        $page = intval($_GET['page'] ?? 1);
        $per_page = intval($_GET['per_page'] ?? 10);
        
        // Validate episode exists
        if (!get_post($episode_id) || get_post_type($episode_id) !== 'episode') {
            wp_send_json_error(array(
                'message' => __('Invalid episode.', 'flexpress')
            ));
        }
        
        $ratings = $this->get_episode_ratings_paginated($episode_id, $page, $per_page);
        $stats = $this->get_episode_rating_stats($episode_id);
        
        wp_send_json_success(array(
            'ratings' => $ratings,
            'stats' => $stats,
            'pagination' => array(
                'current_page' => $page,
                'per_page' => $per_page,
                'total_ratings' => $stats['total_ratings']
            )
        ));
    }
    
    /**
     * Handle rating removal via AJAX
     */
    public function handle_rating_removal() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to remove ratings.', 'flexpress')
            ));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'episode_rating_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'flexpress')
            ));
        }
        
        $episode_id = intval($_POST['episode_id']);
        $user_id = get_current_user_id();
        
        // Validate episode exists
        if (!get_post($episode_id) || get_post_type($episode_id) !== 'episode') {
            wp_send_json_error(array(
                'message' => __('Invalid episode.', 'flexpress')
            ));
        }
        
        // Check if user has a rating for this episode
        $existing_rating = $this->get_user_rating($episode_id, $user_id);
        if (!$existing_rating) {
            wp_send_json_error(array(
                'message' => __('You have not rated this episode.', 'flexpress')
            ));
        }
        
        // Remove the rating
        $result = $this->remove_rating($episode_id, $user_id);
        
        if ($result) {
            // Get updated rating statistics
            $stats = $this->get_episode_rating_stats($episode_id);
            
            wp_send_json_success(array(
                'message' => __('Rating removed successfully.', 'flexpress'),
                'stats' => $stats
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to remove rating.', 'flexpress')
            ));
        }
    }
    
    /**
     * Create a new rating
     */
    private function create_rating($episode_id, $user_id, $rating, $comment = '') {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'episode_id' => $episode_id,
                'user_id' => $user_id,
                'rating' => $rating,
                'comment' => $comment,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Update an existing rating
     */
    private function update_rating($episode_id, $user_id, $rating, $comment = '') {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'rating' => $rating,
                'comment' => $comment,
                'updated_at' => current_time('mysql')
            ),
            array(
                'episode_id' => $episode_id,
                'user_id' => $user_id
            ),
            array('%d', '%s', '%s'),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Remove a rating
     */
    private function remove_rating($episode_id, $user_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array(
                'episode_id' => $episode_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get user's rating for an episode
     */
    public function get_user_rating($episode_id, $user_id) {
        global $wpdb;
        
        $rating = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE episode_id = %d AND user_id = %d",
            $episode_id,
            $user_id
        ));
        
        return $rating;
    }
    
    /**
     * Get paginated ratings for an episode
     */
    public function get_episode_ratings_paginated($episode_id, $page = 1, $per_page = 10) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        $ratings = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.display_name, u.user_login 
             FROM {$this->table_name} r 
             LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID 
             WHERE r.episode_id = %d 
             ORDER BY r.created_at DESC 
             LIMIT %d OFFSET %d",
            $episode_id,
            $per_page,
            $offset
        ));
        
        // Format the results
        $formatted_ratings = array();
        foreach ($ratings as $rating) {
            $formatted_ratings[] = array(
                'id' => $rating->id,
                'rating' => $rating->rating,
                'comment' => $rating->comment,
                'user_name' => $rating->display_name ?: $rating->user_login,
                'created_at' => $rating->created_at,
                'updated_at' => $rating->updated_at
            );
        }
        
        return $formatted_ratings;
    }
    
    /**
     * Get rating statistics for an episode
     */
    public function get_episode_rating_stats($episode_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_ratings,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
             FROM {$this->table_name} 
             WHERE episode_id = %d",
            $episode_id
        ));
        
        return array(
            'total_ratings' => intval($stats->total_ratings),
            'average_rating' => round(floatval($stats->average_rating), 2),
            'rating_distribution' => array(
                5 => intval($stats->five_star),
                4 => intval($stats->four_star),
                3 => intval($stats->three_star),
                2 => intval($stats->two_star),
                1 => intval($stats->one_star)
            )
        );
    }
    
    /**
     * Get user's rating for current episode (if logged in)
     */
    public function get_current_user_rating($episode_id) {
        if (!is_user_logged_in()) {
            return null;
        }
        
        return $this->get_user_rating($episode_id, get_current_user_id());
    }
    
    /**
     * Display rating stars
     */
    public function display_rating_stars($rating, $interactive = false, $episode_id = null) {
        $stars = '';
        $rating = floatval($rating);
        
        for ($i = 1; $i <= 5; $i++) {
            $class = 'star';
            if ($i <= $rating) {
                $class .= ' filled';
            } elseif ($i - 0.5 <= $rating) {
                $class .= ' half-filled';
            }

            // Inline style ensures visibility regardless of stylesheet cache
            $inline_style = 'width:20px;height:20px;display:inline-block;position:relative;cursor:pointer;font-size:20px;line-height:20px;';
            // Default grey; gold when filled
            if (strpos($class, 'filled') !== false) {
                $inline_style .= 'color:#ffc107;';
            } else {
                $inline_style .= 'color:#6c757d;';
            }

            if ($interactive && $episode_id) {
                $stars .= sprintf(
                    '<span class="%s" data-rating="%d" data-episode-id="%d" style="%s">&#9733;</span>',
                    esc_attr($class),
                    $i,
                    $episode_id,
                    esc_attr($inline_style)
                );
            } else {
                // Non-interactive stars rely on CSS (::before) to render and size
                $stars .= sprintf('<span class="%s"></span>', esc_attr($class));
            }
        }
        
        return $stars;
    }
    
    /**
     * Display rating form
     */
    public function display_rating_form($episode_id) {
        if (!is_user_logged_in()) {
            // Do not show any login prompt; hide the rating UI for logged-out users
            return '';
        }
        
        $user_rating = $this->get_current_user_rating($episode_id);
        $current_rating = $user_rating ? $user_rating->rating : 0;
        
        ob_start();
        ?>
        <div class="episode-rating-form" data-episode-id="<?php echo esc_attr($episode_id); ?>">
            <div class="rating-stars-interactive mb-2" style="justify-content:center;">
                <?php echo $this->display_rating_stars($current_rating, true, $episode_id); ?>
            </div>
            <div class="rating-text text-center mb-3">
                <?php if ($current_rating > 0): ?>
                    <?php printf(__('Your rating: %d star%s', 'flexpress'), $current_rating, $current_rating > 1 ? 's' : ''); ?>
                <?php else: ?>
                    <?php esc_html_e('Click to rate', 'flexpress'); ?>
                <?php endif; ?>
            </div>
            
            <div class="rating-actions text-center">
                <a href="#" class="submit-rating small" data-episode-id="<?php echo esc_attr($episode_id); ?>" style="text-decoration:underline;">
                    <?php echo $user_rating ? esc_html__('Update Rating', 'flexpress') : esc_html__('Submit Rating', 'flexpress'); ?>
                </a>
                <?php if ($user_rating): ?>
                    <span class="mx-1 text-muted">·</span>
                    <a href="#" class="remove-rating small text-muted" data-episode-id="<?php echo esc_attr($episode_id); ?>" style="text-decoration:underline;">
                        <?php esc_html_e('Remove rating', 'flexpress'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Display rating statistics
     */
    public function display_rating_stats($episode_id) {
        $stats = $this->get_episode_rating_stats($episode_id);
        
        if ($stats['total_ratings'] === 0) {
            // Do not render stats when there are no ratings
            return '';
        }

        ob_start();
        ?>
        <div class="episode-rating-stats">
            <div class="rating-summary mb-2">
                <div class="average-rating d-flex align-items-center justify-content-center mb-0">
                    <div class="rating-stars me-2">
                        <?php echo $this->display_rating_stars($stats['average_rating']); ?>
                    </div>
                    <span class="rating-number fw-bold">
                        <?php echo esc_html($stats['average_rating']); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the episode ratings system
new FlexPress_Episode_Ratings();

/**
 * Helper function to get episode rating statistics
 */
function flexpress_get_episode_rating_stats($episode_id) {
    $ratings_system = new FlexPress_Episode_Ratings();
    return $ratings_system->get_episode_rating_stats($episode_id);
}

/**
 * Helper function to get user's rating for an episode
 */
function flexpress_get_user_episode_rating($episode_id, $user_id = null) {
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return null;
    }
    
    $ratings_system = new FlexPress_Episode_Ratings();
    return $ratings_system->get_user_rating($episode_id, $user_id);
}

/**
 * Helper function to display episode rating stars
 */
function flexpress_display_episode_rating_stars($rating, $interactive = false, $episode_id = null) {
    $ratings_system = new FlexPress_Episode_Ratings();
    return $ratings_system->display_rating_stars($rating, $interactive, $episode_id);
}

/**
 * Helper function to display episode rating form
 */
function flexpress_display_episode_rating_form($episode_id) {
    $ratings_system = new FlexPress_Episode_Ratings();
    return $ratings_system->display_rating_form($episode_id);
}

/**
 * Helper function to display episode rating statistics
 */
function flexpress_display_episode_rating_stats($episode_id) {
    $ratings_system = new FlexPress_Episode_Ratings();
    return $ratings_system->display_rating_stats($episode_id);
}

/**
 * Helper function to check if episode has ratings
 */
function flexpress_episode_has_ratings($episode_id) {
    $stats = flexpress_get_episode_rating_stats($episode_id);
    return $stats['total_ratings'] > 0;
}

/**
 * Helper function to get episode average rating
 */
function flexpress_get_episode_average_rating($episode_id) {
    $stats = flexpress_get_episode_rating_stats($episode_id);
    return $stats['average_rating'];
}
