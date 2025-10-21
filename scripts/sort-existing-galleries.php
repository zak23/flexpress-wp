<?php

/**
 * Migration script to sort existing episode and extras galleries by filename
 * 
 * This script is safe to run multiple times (idempotent).
 * It will add missing filename data and sort all existing galleries.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress
    require_once(dirname(__FILE__) . '/../wp-load.php');
}

/**
 * Extract filename from URL or attachment data
 */
function extract_filename_from_url($url)
{
    $path = parse_url($url, PHP_URL_PATH);
    return basename($path);
}

/**
 * Get filename from attachment ID
 */
function get_filename_from_attachment($attachment_id)
{
    $attachment = get_post($attachment_id);
    if ($attachment) {
        return basename(get_attached_file($attachment_id));
    }
    return '';
}

/**
 * Sort existing galleries migration
 */
function flexpress_sort_existing_galleries()
{
    global $wpdb;

    echo "Starting gallery sorting migration...\n";

    // Get all episodes and extras posts
    $posts = get_posts(array(
        'post_type' => array('episode', 'extras'),
        'posts_per_page' => -1,
        'post_status' => 'any'
    ));

    $processed_count = 0;
    $sorted_count = 0;

    foreach ($posts as $post) {
        $meta_key = ($post->post_type === 'episode') ? '_episode_gallery_images' : '_extras_gallery_images';
        $gallery_images = get_post_meta($post->ID, $meta_key, true);

        if (!is_array($gallery_images) || empty($gallery_images)) {
            continue;
        }

        $needs_update = false;
        $updated_images = array();

        foreach ($gallery_images as $image) {
            $updated_image = $image;

            // Add filename if missing
            if (!isset($image['filename']) || empty($image['filename'])) {
                $filename = '';

                // Try to get filename from attachment ID
                if (isset($image['id']) && $image['id']) {
                    $filename = get_filename_from_attachment($image['id']);
                }

                // Fallback to extracting from URLs
                if (empty($filename) && isset($image['full'])) {
                    $filename = extract_filename_from_url($image['full']);
                }

                // Fallback to title
                if (empty($filename) && isset($image['title'])) {
                    $filename = sanitize_file_name($image['title']);
                }

                if (!empty($filename)) {
                    $updated_image['filename'] = $filename;
                    $needs_update = true;
                }
            }

            $updated_images[] = $updated_image;
        }

        // Sort the images
        $sorted_images = flexpress_sort_gallery_images_by_filename($updated_images);

        // Check if order changed
        $order_changed = false;
        if (count($sorted_images) === count($updated_images)) {
            for ($i = 0; $i < count($sorted_images); $i++) {
                if ($sorted_images[$i]['id'] !== $updated_images[$i]['id']) {
                    $order_changed = true;
                    break;
                }
            }
        }

        // Update if needed
        if ($needs_update || $order_changed) {
            update_post_meta($post->ID, $meta_key, $sorted_images);
            $sorted_count++;
            echo "Sorted gallery for {$post->post_type} ID {$post->ID}: {$post->post_title}\n";
        }

        $processed_count++;
    }

    echo "Migration complete!\n";
    echo "Processed: {$processed_count} posts\n";
    echo "Sorted: {$sorted_count} galleries\n";
}

// Run the migration
if (function_exists('flexpress_sort_gallery_images_by_filename')) {
    flexpress_sort_existing_galleries();
} else {
    echo "Error: flexpress_sort_gallery_images_by_filename function not found. Make sure the gallery system is loaded.\n";
}
