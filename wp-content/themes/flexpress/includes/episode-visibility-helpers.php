<?php

/**
 * FlexPress Episode Visibility Helper Functions
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Check if an episode is hidden from public view
 *
 * @param int $episode_id Episode post ID
 * @return bool True if episode is hidden from public
 */
function flexpress_is_episode_hidden_from_public($episode_id)
{
    $hidden = get_field('hidden_from_public', $episode_id);
    return !empty($hidden);
}

/**
 * Check if current user can view an episode
 *
 * @param int $episode_id Episode post ID
 * @return bool True if user can view the episode
 */
function flexpress_can_user_view_episode($episode_id)
{
    // If episode is not hidden from public, anyone can view it
    if (!flexpress_is_episode_hidden_from_public($episode_id)) {
        return true;
    }

    // If episode is hidden, only logged-in users can view it
    return is_user_logged_in();
}

/**
 * Get meta query array to exclude hidden episodes for non-logged-in users
 *
 * @return array Meta query array for WP_Query
 */
function flexpress_get_episode_visibility_meta_query()
{
    // If user is logged in, show all episodes
    if (is_user_logged_in()) {
        return array();
    }

    // For non-logged-in users, exclude hidden episodes
    return array(
        'relation' => 'OR',
        array(
            'key' => 'hidden_from_public',
            'compare' => 'NOT EXISTS'
        ),
        array(
            'key' => 'hidden_from_public',
            'value' => '0',
            'compare' => '='
        ),
        array(
            'key' => 'hidden_from_public',
            'value' => '',
            'compare' => '='
        )
    );
}

/**
 * Modify episode query args to respect visibility settings
 *
 * @param array $args WP_Query arguments
 * @return array Modified arguments
 */
function flexpress_add_episode_visibility_to_query($args)
{
    // Only apply to episode post type queries
    if (isset($args['post_type']) && $args['post_type'] === 'episode') {
        $visibility_query = flexpress_get_episode_visibility_meta_query();

        if (!empty($visibility_query)) {
            // Merge with existing meta_query if it exists
            if (isset($args['meta_query'])) {
                $args['meta_query'] = array(
                    'relation' => 'AND',
                    $args['meta_query'],
                    $visibility_query
                );
            } else {
                $args['meta_query'] = $visibility_query;
            }
        }

        // For logged-out users, exclude draft episodes (unreleased)
        // For logged-in users, include draft episodes (so they can see coming soon content)
        if (!is_user_logged_in()) {
            // If post_status is not already set, set it to 'publish' only
            if (!isset($args['post_status'])) {
                $args['post_status'] = 'publish';
            }
        } else {
            // For logged-in users, include both published and draft episodes
            if (!isset($args['post_status'])) {
                $args['post_status'] = array('publish', 'draft');
            }
        }
    }

    return $args;
}

/**
 * Check if episode should be displayed in current context
 *
 * @param int $episode_id Episode post ID
 * @return bool True if episode should be displayed
 */
function flexpress_should_display_episode($episode_id)
{
    return flexpress_can_user_view_episode($episode_id);
}

/**
 * Get count of visible episodes for current user
 *
 * @param array $additional_args Additional WP_Query arguments
 * @return int Number of visible episodes
 */
function flexpress_get_visible_episodes_count($additional_args = array())
{
    $args = array_merge(array(
        'post_type' => 'episode',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'release_date',
                'value' => current_time('mysql'),
                'compare' => '<=',
                'type' => 'DATETIME'
            )
        )
    ), $additional_args);

    $args = flexpress_add_episode_visibility_to_query($args);

    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Display episode visibility notice for non-logged-in users
 *
 * @param string $context Context where notice is displayed (homepage, archive, etc.)
 */
function flexpress_display_episode_visibility_notice($context = '')
{
    if (is_user_logged_in()) {
        return; // No notice needed for logged-in users
    }

    $message = __('Some episodes are hidden from public view. Sign up to access all content!', 'flexpress');
    $login_url = wp_login_url(get_permalink());
    $register_url = wp_registration_url();

    echo '<div class="alert alert-info episode-visibility-notice">';
    echo '<i class="fas fa-lock me-2"></i>';
    echo esc_html($message);
    echo ' <a href="' . esc_url($register_url) . '" class="btn btn-sm btn-primary ms-2">' . __('Sign Up', 'flexpress') . '</a>';
    echo ' <a href="' . esc_url($login_url) . '" class="btn btn-sm btn-outline-primary ms-1">' . __('Login', 'flexpress') . '</a>';
    echo '</div>';
}
