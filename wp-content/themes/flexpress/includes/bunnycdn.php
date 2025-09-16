<?php
/**
 * BunnyCDN Stream Integration
 */

/**
 * Get BunnyCDN video URL with token
 */
function flexpress_get_bunnycdn_video_url($video_id, $type = 'preview') {
    $video_settings = get_option('flexpress_video_settings', array());
    $api_key = isset($video_settings['bunnycdn_api_key']) ? $video_settings['bunnycdn_api_key'] : '';
    $library_id = isset($video_settings['bunnycdn_library_id']) ? $video_settings['bunnycdn_library_id'] : '';
    $token_key = isset($video_settings['bunnycdn_token_key']) ? $video_settings['bunnycdn_token_key'] : '';
    $bunnycdn_url = isset($video_settings['bunnycdn_url']) ? $video_settings['bunnycdn_url'] : '';
    
    if (empty($api_key) || empty($library_id) || empty($token_key) || empty($bunnycdn_url)) {
        return '';
    }

    // BunnyCDN token signing
    $expires = time() + 3600; // 1 hour expiry
    
    // Using SHA256_HEX(token_security_key + video_id + expiration)
    $token = hash('sha256', $token_key . $video_id . $expires);
    
    return sprintf(
        'https://%s/play/%s/%s?token=%s&expires=%d&type=%s',
        $bunnycdn_url,
        $library_id,
        $video_id,
        $token,
        $expires,
        $type
    );
}

/**
 * Get BunnyCDN video thumbnail using API
 * 
 * @param string $video_id The BunnyCDN video ID
 * @return string The thumbnail URL or empty string on failure
 */
function flexpress_get_bunnycdn_video_thumbnail($video_id) {
    if (empty($video_id)) {
        return '';
    }
    
    // Get video details from API
    $video_details = flexpress_get_bunnycdn_video_details($video_id);
    
    if (!$video_details || !isset($video_details['thumbnailFileName'])) {
        return '';
    }
    
    $video_settings = get_option('flexpress_video_settings', array());
    $bunnycdn_url = isset($video_settings['bunnycdn_url']) ? $video_settings['bunnycdn_url'] : '';
    $token_key = isset($video_settings['bunnycdn_token_key']) ? $video_settings['bunnycdn_token_key'] : '';
    
    if (empty($bunnycdn_url) || empty($token_key)) {
        return '';
    }
    
    // BunnyCDN token signing
    $expires = time() + 3600; // 1 hour expiry
    $token = hash('sha256', $token_key . $video_id . $expires);
    
    // Construct URL with the actual thumbnailFileName from API
    return sprintf(
        'https://%s/%s/%s?token=%s&expires=%d',
        $bunnycdn_url,
        $video_id,
        $video_details['thumbnailFileName'],
        $token,
        $expires
    );
}

/**
 * Get BunnyCDN thumbnail URL
 */
function flexpress_get_bunnycdn_thumbnail_url($video_id) {
    // Try to get the thumbnail via the API first
    $api_thumbnail = flexpress_get_bunnycdn_video_thumbnail($video_id);
    if (!empty($api_thumbnail)) {
        return $api_thumbnail;
    }
    
    // Fallback to the default method if API fails
    $video_settings = get_option('flexpress_video_settings', array());
    $bunnycdn_url = isset($video_settings['bunnycdn_url']) ? $video_settings['bunnycdn_url'] : '';
    $token_key = isset($video_settings['bunnycdn_token_key']) ? $video_settings['bunnycdn_token_key'] : '';
    
    if (empty($bunnycdn_url) || empty($token_key)) {
        return '';
    }

    // BunnyCDN token signing
    $expires = time() + 3600; // 1 hour expiry
    
    // Using SHA256_HEX(token_security_key + video_id + expiration)
    $token = hash('sha256', $token_key . $video_id . $expires);
    
    return sprintf(
        'https://%s/%s/thumbnail.jpg?token=%s&expires=%d',
        $bunnycdn_url,
        $video_id,
        $token,
        $expires
    );
}

/**
 * Get BunnyCDN poster URL
 */
function flexpress_get_bunnycdn_poster_url($video_id) {
    // Try to get the thumbnail via the API first
    $api_thumbnail = flexpress_get_bunnycdn_video_thumbnail($video_id);
    if (!empty($api_thumbnail)) {
        return $api_thumbnail;
    }
    
    // Fallback to the default method if API fails
    $video_settings = get_option('flexpress_video_settings', array());
    $bunnycdn_url = isset($video_settings['bunnycdn_url']) ? $video_settings['bunnycdn_url'] : '';
    $token_key = isset($video_settings['bunnycdn_token_key']) ? $video_settings['bunnycdn_token_key'] : '';
    
    if (empty($bunnycdn_url) || empty($token_key)) {
        return '';
    }

    // BunnyCDN token signing
    $expires = time() + 3600; // 1 hour expiry
    
    // Using SHA256_HEX(token_security_key + video_id + expiration)
    $token = hash('sha256', $token_key . $video_id . $expires);
    
    return sprintf(
        'https://%s/%s/thumbnail.jpg?token=%s&expires=%d',
        $bunnycdn_url,
        $video_id,
        $token,
        $expires
    );
}

/**
 * Get BunnyCDN preview URL
 */
function flexpress_get_bunnycdn_preview_url($video_id) {
    $video_settings = get_option('flexpress_video_settings', array());
    $bunnycdn_url = isset($video_settings['bunnycdn_url']) ? $video_settings['bunnycdn_url'] : '';
    $token_key = isset($video_settings['bunnycdn_token_key']) ? $video_settings['bunnycdn_token_key'] : '';
    
    if (empty($bunnycdn_url) || empty($token_key)) {
        return '';
    }

    // BunnyCDN token signing
    $expires = time() + 3600; // 1 hour expiry
    
    // Using SHA256_HEX(token_security_key + video_id + expiration)
    $token = hash('sha256', $token_key . $video_id . $expires);
    
    return sprintf(
        'https://%s/%s/preview.webp?token=%s&expires=%d',
        $bunnycdn_url,
        $video_id,
        $token,
        $expires
    );
}

/**
 * Get BunnyCDN cache duration in seconds
 * 
 * @return int Cache duration in seconds
 */
function flexpress_get_bunnycdn_cache_duration() {
    $video_settings = get_option('flexpress_video_settings', array());
    $duration_hours = isset($video_settings['bunnycdn_cache_duration']) ? (float)$video_settings['bunnycdn_cache_duration'] : 12;
    
    // Convert hours to seconds
    return (int)($duration_hours * HOUR_IN_SECONDS);
}

/**
 * Get BunnyCDN video details
 * 
 * @param string $video_id The BunnyCDN video ID
 * @param bool $force_refresh Whether to bypass cache and force a fresh API call
 * @return array|false Video details array or false on failure
 */
function flexpress_get_bunnycdn_video_details($video_id, $force_refresh = false) {
    if (empty($video_id)) {
        return false;
    }
    
    // Check for cached data first if not forcing refresh
    if (!$force_refresh) {
        $cache_key = 'bunnycdn_video_' . $video_id;
        $cached_data = get_transient($cache_key);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
    }
    
    $video_settings = get_option('flexpress_video_settings', array());
    $api_key = isset($video_settings['bunnycdn_api_key']) ? $video_settings['bunnycdn_api_key'] : '';
    $library_id = isset($video_settings['bunnycdn_library_id']) ? $video_settings['bunnycdn_library_id'] : '';
    
    if (empty($api_key) || empty($library_id)) {
        error_log('BunnyCDN API Error: Missing API key or library ID');
        return false;
    }

    // Updated API URL from api.bunny.net to video.bunnycdn.com based on working curl command
    $api_url = sprintf(
        'https://video.bunnycdn.com/library/%s/videos/%s',
        $library_id,
        $video_id
    );
    
    error_log('BunnyCDN API Request: ' . $api_url);

    $response = wp_remote_get(
        $api_url,
        array(
            'headers' => array(
                'Accept' => 'application/json',
                'AccessKey' => $api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15, // Increase timeout to prevent issues
        )
    );

    if (is_wp_error($response)) {
        error_log('BunnyCDN API Error: ' . $response->get_error_message());
        return false;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        error_log('BunnyCDN API Error: Got status code ' . $status_code);
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    // Debug log the actual response
    error_log('BunnyCDN API Response for video ' . $video_id . ': ' . print_r($data, true));
    
    if (empty($data)) {
        error_log('BunnyCDN API Error: Empty or invalid JSON response');
        return false;
    }
    
    // Check if length property exists and log it
    if (isset($data['length'])) {
        error_log('BunnyCDN Video Length (seconds): ' . $data['length'] . ' for video ' . $video_id);
    } else {
        // Check if there's a different property for duration
        error_log('BunnyCDN Response does not contain "length" property. Available properties: ' . implode(', ', array_keys($data)));
    }
    
    // Cache the result for the configured duration
    $cache_duration = flexpress_get_bunnycdn_cache_duration();
    set_transient('bunnycdn_video_' . $video_id, $data, $cache_duration);
    
    return $data;
}

/**
 * Clear BunnyCDN video cache for a specific video
 * 
 * @param string $video_id The BunnyCDN video ID
 * @return bool True if cache was cleared
 */
function flexpress_clear_bunnycdn_video_cache($video_id) {
    if (empty($video_id)) {
        return false;
    }
    
    $cache_key = 'bunnycdn_video_' . $video_id;
    $result = delete_transient($cache_key);
    
    if ($result) {
        error_log('BunnyCDN Cache cleared for video: ' . $video_id);
    }
    
    return $result;
}

/**
 * Clear all BunnyCDN video caches
 * 
 * @return int Number of caches cleared
 */
function flexpress_clear_all_bunnycdn_video_cache() {
    global $wpdb;
    
    // Delete all transients that start with 'bunnycdn_video_'
    $result = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like('_transient_bunnycdn_video_') . '%'
        )
    );
    
    // Also delete the timeout transients
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like('_transient_timeout_bunnycdn_video_') . '%'
        )
    );
    
    error_log('BunnyCDN Cache cleared for all videos. Deleted: ' . $result . ' cache entries');
    
    return $result;
}

/**
 * Add video preview column to episode list
 */
function flexpress_add_video_preview_column($columns) {
    $columns['video_preview'] = __('Video Preview', 'flexpress');
    return $columns;
}
add_filter('manage_episode_posts_columns', 'flexpress_add_video_preview_column');

/**
 * Display video preview in column
 */
function flexpress_display_video_preview_column($column, $post_id) {
    if ($column === 'video_preview') {
        $video_id = get_post_meta($post_id, 'video_id', true);
        if ($video_id) {
            $thumbnail_url = flexpress_get_bunnycdn_thumbnail_url($video_id);
            if ($thumbnail_url) {
                echo '<img src="' . esc_url($thumbnail_url) . '" alt="Video thumbnail" style="max-width: 100px;">';
            }
        }
    }
}
add_action('manage_episode_posts_custom_column', 'flexpress_display_video_preview_column', 10, 2);

/**
 * Display the episode thumbnail
 * 
 * @param string $size The thumbnail size
 * @param string $class Additional classes for the image
 * @return void
 */
function flexpress_display_episode_thumbnail($size = 'medium', $class = '') {
    $post_id = get_the_ID();
    $video_id = flexpress_get_primary_video_id($post_id);
    
    // If we have a video ID, display the thumbnail
    if (!empty($video_id)) {
        $thumbnail_url = flexpress_get_bunnycdn_thumbnail_url($video_id);
        $preview_url = flexpress_get_bunnycdn_preview_url($video_id);
        
        $classes = 'episode-thumbnail';
        if (!empty($class)) {
            $classes .= ' ' . $class;
        }
        
        printf(
            '<img src="%s" alt="%s" class="%s" data-video-id="%s" data-preview-url="%s" data-original-src="%s">',
            esc_url($thumbnail_url),
            esc_attr(get_the_title()),
            esc_attr($classes),
            esc_attr($video_id),
            esc_url($preview_url),
            esc_url($thumbnail_url)
        );
    } else {
        // Fallback to featured image if no video ID
        if (has_post_thumbnail()) {
            the_post_thumbnail($size, array('class' => $class));
        } else {
            // Default placeholder image if no featured image
            echo '<img src="' . esc_url(get_template_directory_uri()) . '/assets/images/placeholder.jpg" alt="' . esc_attr(get_the_title()) . '" class="' . esc_attr($class) . '" />';
        }
    }
}

/**
 * Get all available video IDs for an episode
 * 
 * @param int $post_id The post ID
 * @return array Array of video IDs with their types
 */
function flexpress_get_episode_video_ids($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $result = array();
    
    // Check post meta
    $video_id = get_post_meta($post_id, 'video_id', true);
    if (!empty($video_id)) {
        $result['default'] = $video_id;
    }
    
    // Check ACF fields
    if (function_exists('get_field')) {
        $preview_video = get_field('preview_video', $post_id);
        if (!empty($preview_video)) {
            $result['preview'] = $preview_video;
        }
        
        $trailer_video = get_field('trailer_video', $post_id);
        if (!empty($trailer_video)) {
            $result['trailer'] = $trailer_video;
        }
        
        $full_video = get_field('full_video', $post_id);
        if (!empty($full_video)) {
            $result['full'] = $full_video;
        }
    }
    
    return $result;
}

/**
 * Get primary video ID for an episode
 * 
 * @param int $post_id The post ID
 * @return string|null The primary video ID or null if none found
 */
function flexpress_get_primary_video_id($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $video_ids = flexpress_get_episode_video_ids($post_id);
    
    // Order of preference: default, preview, trailer, full
    if (isset($video_ids['default'])) {
        return $video_ids['default'];
    } elseif (isset($video_ids['preview'])) {
        return $video_ids['preview'];
    } elseif (isset($video_ids['trailer'])) {
        return $video_ids['trailer'];
    } elseif (isset($video_ids['full'])) {
        return $video_ids['full'];
    }
    
    return null;
} 