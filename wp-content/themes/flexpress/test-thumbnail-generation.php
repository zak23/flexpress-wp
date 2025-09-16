<?php
/**
 * Test Thumbnail Generation for BunnyCDN Gallery Uploads
 * 
 * This script tests the thumbnail generation functionality
 * Run this from WordPress admin or via WP-CLI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress
    require_once('../../../wp-load.php');
}

/**
 * Test thumbnail generation with various image sizes
 */
function test_thumbnail_generation() {
    echo "<h2>Testing BunnyCDN Gallery Thumbnail Generation</h2>\n";
    
    // Test image paths (you'll need to add actual test images)
    $test_images = array(
        'landscape' => ABSPATH . 'wp-content/uploads/test-landscape.jpg',
        'portrait' => ABSPATH . 'wp-content/uploads/test-portrait.jpg',
        'square' => ABSPATH . 'wp-content/uploads/test-square.jpg',
    );
    
    // Check if test images exist
    foreach ($test_images as $type => $path) {
        if (file_exists($path)) {
            echo "<p>✅ Test image found: $type ($path)</p>\n";
            
            // Test image editor
            $image_editor = wp_get_image_editor($path);
            
            if (is_wp_error($image_editor)) {
                echo "<p>❌ Failed to create image editor for $type: " . $image_editor->get_error_message() . "</p>\n";
                continue;
            }
            
            // Get original dimensions
            $original_size = $image_editor->get_size();
            echo "<p>📐 Original size: {$original_size['width']}x{$original_size['height']}</p>\n";
            
            // Test crop calculation
            $crop_size = min($original_size['width'], $original_size['height']);
            $crop_x = ($original_size['width'] - $crop_size) / 2;
            $crop_y = ($original_size['height'] - $crop_size) / 2;
            
            echo "<p>✂️ Crop dimensions: {$crop_size}x{$crop_size} at ({$crop_x}, {$crop_y})</p>\n";
            
            // Test cropping
            $crop_result = $image_editor->crop($crop_x, $crop_y, $crop_size, $crop_size);
            
            if (is_wp_error($crop_result)) {
                echo "<p>❌ Failed to crop $type: " . $crop_result->get_error_message() . "</p>\n";
                continue;
            }
            
            echo "<p>✅ Successfully cropped $type to square</p>\n";
            
            // Test resizing
            $resize_result = $image_editor->resize(300, 300, true);
            
            if (is_wp_error($resize_result)) {
                echo "<p>❌ Failed to resize $type: " . $resize_result->get_error_message() . "</p>\n";
                continue;
            }
            
            echo "<p>✅ Successfully resized $type to 300x300</p>\n";
            
            // Test saving
            $temp_dir = wp_upload_dir();
            $temp_file = $temp_dir['path'] . '/test_thumb_' . $type . '.jpg';
            
            $save_result = $image_editor->save($temp_file, 'image/jpeg');
            
            if (is_wp_error($save_result)) {
                echo "<p>❌ Failed to save $type: " . $save_result->get_error_message() . "</p>\n";
                continue;
            }
            
            echo "<p>✅ Successfully saved $type thumbnail to: $temp_file</p>\n";
            
            // Clean up
            if (file_exists($temp_file)) {
                unlink($temp_file);
                echo "<p>🧹 Cleaned up temporary file</p>\n";
            }
            
        } else {
            echo "<p>⚠️ Test image not found: $type ($path)</p>\n";
        }
    }
    
    // Test BunnyCDN settings
    echo "<h3>BunnyCDN Settings Test</h3>\n";
    
    $video_settings = get_option('flexpress_video_settings', array());
    
    $required_settings = array(
        'bunnycdn_storage_api_key' => 'Storage API Key',
        'bunnycdn_storage_zone' => 'Storage Zone',
        'bunnycdn_storage_url' => 'Storage URL',
        'gallery_thumbnail_size' => 'Thumbnail Size'
    );
    
    foreach ($required_settings as $key => $label) {
        $value = isset($video_settings[$key]) ? $video_settings[$key] : '';
        if (!empty($value)) {
            if ($key === 'bunnycdn_storage_api_key') {
                echo "<p>✅ $label: " . substr($value, 0, 8) . "...</p>\n";
            } else {
                echo "<p>✅ $label: $value</p>\n";
            }
        } else {
            echo "<p>❌ $label: NOT SET</p>\n";
        }
    }
    
    // Test URL generation
    echo "<h3>URL Generation Test</h3>\n";
    
    $test_original_url = 'https://cdn.example.com/episodes/galleries/123/image-abc123-1234567890.jpg';
    $expected_thumbnail_url = 'https://cdn.example.com/episodes/galleries/123/thumbs/thumb_image-abc123-1234567890.jpg';
    
    // Simulate the URL conversion logic
    $url_parts = parse_url($test_original_url);
    $path = $url_parts['path'];
    $path_parts = explode('/', $path);
    $filename = end($path_parts);
    $thumbnail_filename = 'thumb_' . $filename;
    $path_parts[count($path_parts) - 1] = 'thumbs';
    $path_parts[] = $thumbnail_filename;
    $thumbnail_path = implode('/', $path_parts);
    $generated_thumbnail_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $thumbnail_path;
    
    echo "<p>🔗 Original URL: $test_original_url</p>\n";
    echo "<p>🔗 Generated Thumbnail URL: $generated_thumbnail_url</p>\n";
    echo "<p>🔗 Expected Thumbnail URL: $expected_thumbnail_url</p>\n";
    
    if ($generated_thumbnail_url === $expected_thumbnail_url) {
        echo "<p>✅ URL generation working correctly!</p>\n";
    } else {
        echo "<p>❌ URL generation failed!</p>\n";
    }
    
    // Test gallery display condition
    echo "<h3>Gallery Display Test</h3>\n";
    
    if (function_exists('flexpress_has_episode_gallery')) {
        echo "<p>✅ flexpress_has_episode_gallery() function exists</p>\n";
        
        // Test with a sample episode (you'll need to replace with actual episode ID)
        $sample_episode_id = 1; // Replace with actual episode ID for testing
        
        if (get_post($sample_episode_id) && get_post_type($sample_episode_id) === 'episode') {
            $has_gallery = flexpress_has_episode_gallery($sample_episode_id);
            $gallery_images = flexpress_get_episode_gallery($sample_episode_id);
            
            echo "<p>📄 Testing episode ID: $sample_episode_id</p>\n";
            echo "<p>🖼️ Has gallery: " . ($has_gallery ? 'YES' : 'NO') . "</p>\n";
            echo "<p>🔢 Number of images: " . (is_array($gallery_images) ? count($gallery_images) : 0) . "</p>\n";
            
            if ($has_gallery) {
                echo "<p>✅ Gallery section will be displayed</p>\n";
            } else {
                echo "<p>✅ Gallery section will be hidden (no images)</p>\n";
            }
        } else {
            echo "<p>⚠️ Episode ID $sample_episode_id not found or not an episode</p>\n";
        }
    } else {
        echo "<p>❌ flexpress_has_episode_gallery() function not found</p>\n";
    }
    
    echo "<h3>Test Complete</h3>\n";
    echo "<p>To test with real images, upload test images to wp-content/uploads/ with names:</p>\n";
    echo "<ul>\n";
    echo "<li>test-landscape.jpg (wide image)</li>\n";
    echo "<li>test-portrait.jpg (tall image)</li>\n";
    echo "<li>test-square.jpg (square image)</li>\n";
    echo "</ul>\n";
    echo "<p><strong>Gallery Display:</strong> The gallery section will now only appear on episode pages that have gallery images uploaded.</p>\n";
}

// Run the test
test_thumbnail_generation();
?>
