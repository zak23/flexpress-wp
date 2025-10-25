<?php

/**
 * Test Collection Save Function
 */

require_once('/var/www/html/wp-config.php');

echo "Testing collection save function...\n";

// Simulate form submission
$_POST['is_collection_tag'] = '1';
$_POST['collection_description'] = 'TEST: This is a test description to see if saving works!';
$_POST['collection_episode_order'] = 'oldest';
$_POST['collection_custom_css'] = 'test-class';

$term_id = 112090; // Halloween Special

echo "Calling save function for term_id: $term_id\n";

// Call our save function
flexpress_save_collection_fields($term_id);

echo "Save function completed. Checking results...\n";

// Check what was saved
$is_collection = get_term_meta($term_id, 'is_collection_tag', true);
$description = get_term_meta($term_id, 'collection_description', true);
$episode_order = get_term_meta($term_id, 'collection_episode_order', true);
$custom_css = get_term_meta($term_id, 'collection_custom_css', true);

echo "Results:\n";
echo "- is_collection_tag: $is_collection\n";
echo "- collection_description: $description\n";
echo "- collection_episode_order: $episode_order\n";
echo "- collection_custom_css: $custom_css\n";

// Check error log
echo "\nChecking error log...\n";
$error_log = file_get_contents('/var/www/html/wp-content/debug.log');
$recent_errors = array_slice(explode("\n", $error_log), -10);
foreach ($recent_errors as $error) {
    if (strpos($error, 'collection') !== false) {
        echo $error . "\n";
    }
}
