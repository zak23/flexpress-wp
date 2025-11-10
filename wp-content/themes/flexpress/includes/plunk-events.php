<?php
/**
 * Plunk event emitters for content publishes
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * On publish of episodes or news posts, send Plunk tracking events
 */
function flexpress_plunk_on_transition_post_status($new_status, $old_status, $post)
{
    if ($new_status === 'publish' && $old_status !== 'publish') {
        // Episode published
        if ($post->post_type === 'episode') {
            $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
            // Track globally via a synthetic user_id 0 (service will ignore non-logged users)
            $service = flexpress_plunk_service();
            // Send a broadcast event (Plunk automation targets segment)
            $service->track_user_event(0, 'episode_published', array(
                'episode_id' => $post->ID,
                'title' => get_the_title($post),
                'tags' => $tags,
                'url' => get_permalink($post),
                'timestamp' => date('c')
            ));
        }
        // News published (post with category 'news')
        if ($post->post_type === 'post') {
            $cats = wp_get_post_categories($post->ID, array('fields' => 'names'));
            if (in_array('news', array_map('strtolower', $cats), true)) {
                $service = flexpress_plunk_service();
                $service->track_user_event(0, 'news_published', array(
                    'post_id' => $post->ID,
                    'title' => get_the_title($post),
                    'url' => get_permalink($post),
                    'timestamp' => date('c')
                ));
            }
        }
    }
}
add_action('transition_post_status', 'flexpress_plunk_on_transition_post_status', 10, 3);


