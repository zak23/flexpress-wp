<?php
/**
 * Test script for Tag Collection System
 * This script tests the collection helper functions
 */

// Include WordPress
require_once('/var/www/html/wp-config.php');

echo "=== Tag Collection System Test ===\n\n";

// Test 1: Check if Halloween Special is a collection
$halloween_tag = get_term_by('slug', 'halloween-special', 'post_tag');
if ($halloween_tag) {
    echo "1. Halloween Special Tag Found:\n";
    echo "   - ID: " . $halloween_tag->term_id . "\n";
    echo "   - Name: " . $halloween_tag->name . "\n";
    echo "   - Slug: " . $halloween_tag->slug . "\n";
    
    // Test collection detection
    $is_collection = flexpress_is_collection_tag($halloween_tag);
    echo "   - Is Collection: " . ($is_collection ? 'YES' : 'NO') . "\n";
    
    // Debug: Check database directly
    global $wpdb;
    $debug_result = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} 
         WHERE meta_key = %s AND post_id = %d",
        'post_tag_' . $halloween_tag->term_id . '_is_collection_tag',
        $halloween_tag->term_id
    ));
    echo "   - Debug DB Result: " . ($debug_result ?: 'NULL') . "\n";
    
    if ($is_collection) {
        // Test collection metadata
        $metadata = flexpress_get_collection_metadata($halloween_tag);
        echo "   - Description: " . ($metadata['description'] ?: 'None') . "\n";
        echo "   - Episode Order: " . ($metadata['episode_order'] ?: 'None') . "\n";
        
        // Test collection count
        $count = flexpress_get_collection_count($halloween_tag);
        echo "   - Episode Count: " . $count . "\n";
        
        // Test collection URL
        $url = flexpress_get_collection_url($halloween_tag);
        echo "   - Collection URL: " . $url . "\n";
    }
} else {
    echo "1. Halloween Special Tag NOT Found\n";
}

echo "\n";

// Test 2: Check regular tags
$regular_tags = array('big-cock', 'anal', 'threesome');
foreach ($regular_tags as $slug) {
    $tag = get_term_by('slug', $slug, 'post_tag');
    if ($tag) {
        echo "2. " . ucfirst($slug) . " Tag:\n";
        echo "   - Is Collection: " . (flexpress_is_collection_tag($tag) ? 'YES' : 'NO') . "\n";
        echo "   - Episode Count: " . flexpress_get_collection_count($tag) . "\n";
    }
}

echo "\n";

// Test 3: Test collection episodes query
if (isset($halloween_tag)) {
    echo "3. Halloween Collection Episodes:\n";
    $episodes_query = flexpress_get_collection_episodes($halloween_tag, 5, 1);
    if ($episodes_query->have_posts()) {
        while ($episodes_query->have_posts()) {
            $episodes_query->the_post();
            echo "   - " . get_the_title() . "\n";
        }
        wp_reset_postdata();
    } else {
        echo "   - No episodes found\n";
    }
}

echo "\n=== Test Complete ===\n";
