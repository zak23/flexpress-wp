<?php
/**
 * Debug BunnyCDN video details
 */

// Load WordPress
require_once(dirname(dirname(dirname(__FILE__))) . '/wp-load.php');

// Basic security check
$allowed_ips = array('127.0.0.1', '::1');
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) && !current_user_can('manage_options')) {
    die('Access denied');
}

echo '<h1>BunnyCDN Debug Tool</h1>';

// Check BunnyCDN settings
$video_settings = get_option('flexpress_video_settings', array());
echo '<h2>BunnyCDN Settings</h2>';
echo '<ul>';
echo '<li>Library ID: ' . (isset($video_settings['bunnycdn_library_id']) ? substr($video_settings['bunnycdn_library_id'], 0, 3) . '...' : 'Not set') . '</li>';
echo '<li>API Key: ' . (isset($video_settings['bunnycdn_api_key']) && !empty($video_settings['bunnycdn_api_key']) ? 'Set (hidden)' : 'Not set') . '</li>';
echo '<li>Token Key: ' . (isset($video_settings['bunnycdn_token_key']) && !empty($video_settings['bunnycdn_token_key']) ? 'Set (hidden)' : 'Not set') . '</li>';
echo '<li>BunnyCDN URL: ' . (isset($video_settings['bunnycdn_url']) ? $video_settings['bunnycdn_url'] : 'Not set') . '</li>';
echo '</ul>';

// Get video ID from URL parameters or allow manual input
$video_id = isset($_GET['video_id']) ? $_GET['video_id'] : '';

echo '<h2>Test Video API</h2>';
echo '<form method="get">';
echo '<input type="text" name="video_id" value="' . esc_attr($video_id) . '" placeholder="Enter BunnyCDN Video ID">';
echo '<input type="submit" value="Test Video">';
echo '</form>';

// If we have a video ID, test the API
if (!empty($video_id) && function_exists('flexpress_get_bunnycdn_video_details')) {
    echo '<h3>Video Test Results for ID: ' . esc_html($video_id) . '</h3>';
    
    // Force refresh to get latest data
    $video_details = flexpress_get_bunnycdn_video_details($video_id, true);
    
    if ($video_details) {
        echo '<div style="background: #f0f0f0; padding: 10px; border-radius: 5px;">';
        echo '<h4>API Response:</h4>';
        echo '<pre style="background: #fff; padding: 10px; overflow: auto; max-height: 400px;">';
        print_r($video_details);
        echo '</pre>';
        
        // Check for thumbnailFileName property
        if (isset($video_details['thumbnailFileName'])) {
            echo '<p style="color: green;"><strong>Found thumbnail file name:</strong> ' . $video_details['thumbnailFileName'] . '</p>';
            
            // Display the thumbnail
            if (function_exists('flexpress_get_bunnycdn_video_thumbnail')) {
                $thumbnail_url = flexpress_get_bunnycdn_video_thumbnail($video_id);
                echo '<p><strong>API-based Thumbnail URL:</strong> <a href="' . esc_url($thumbnail_url) . '" target="_blank">' . esc_html($thumbnail_url) . '</a></p>';
                echo '<p><img src="' . esc_url($thumbnail_url) . '" style="max-width: 300px; border: 1px solid #ccc; padding: 5px;"></p>';
            }
        } else {
            echo '<p style="color: red;"><strong>No thumbnailFileName field found!</strong></p>';
        }
        
        // Check for duration properties
        $duration_found = false;
        $duration_fields = array('length', 'duration', 'lengthSeconds', 'durationSeconds');
        
        foreach ($duration_fields as $field) {
            if (isset($video_details[$field])) {
                $seconds = $video_details[$field];
                $minutes = ceil($seconds / 60);
                echo '<p style="color: green;"><strong>Found duration in field "' . $field . '":</strong> ' . $seconds . ' seconds (' . $minutes . ' minutes)</p>';
                $duration_found = true;
            }
        }
        
        if (!$duration_found) {
            echo '<p style="color: red;"><strong>No duration field found!</strong> Available fields: ' . implode(', ', array_keys($video_details)) . '</p>';
        }
    } else {
        echo '<p style="color: red;"><strong>Error:</strong> Could not retrieve video details. Check PHP error log for more information.</p>';
    }
}

// Link to check debug log
echo '<h2>Debug Log</h2>';
echo '<p>The debug log should be located at: <code>/var/www/html/wp-content/debug.log</code></p>';

// Try to display last 20 lines of debug log related to BunnyCDN
$debug_log_path = ABSPATH . 'wp-content/debug.log';
if (file_exists($debug_log_path)) {
    echo '<h3>Recent BunnyCDN Log Entries</h3>';
    $log_content = file_get_contents($debug_log_path);
    
    if ($log_content) {
        $lines = explode("\n", $log_content);
        $bunny_lines = array();
        
        foreach ($lines as $line) {
            if (stripos($line, 'bunny') !== false) {
                $bunny_lines[] = $line;
            }
        }
        
        $bunny_lines = array_slice($bunny_lines, -20); // Get last 20 BunnyCDN lines
        
        echo '<pre style="background: #fff; padding: 10px; overflow: auto; max-height: 400px;">';
        echo implode("\n", $bunny_lines);
        echo '</pre>';
    } else {
        echo '<p>Debug log exists but could not be read.</p>';
    }
} else {
    echo '<p>Debug log does not exist yet. Try saving an episode or viewing an episode page to generate log entries.</p>';
} 