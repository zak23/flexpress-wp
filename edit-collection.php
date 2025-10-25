<?php

/**
 * Edit Collection Description Script
 * Usage: php edit-collection.php "Collection Name" "New Description"
 */

if ($argc < 3) {
    echo "Usage: php edit-collection.php \"Collection Name\" \"New Description\"\n";
    echo "Example: php edit-collection.php \"Halloween Special\" \"Amazing Halloween episodes with spooky themes!\"\n";
    exit(1);
}

$collection_name = $argv[1];
$new_description = $argv[2];

// Include WordPress
require_once('/var/www/html/wp-config.php');

echo "Updating collection: $collection_name\n";

// Find the collection tag
$tag = get_term_by('name', $collection_name, 'post_tag');
if (!$tag || is_wp_error($tag)) {
    echo "Error: Collection '$collection_name' not found\n";
    exit(1);
}

// Check if it's a collection
if (!flexpress_is_collection_tag($tag)) {
    echo "Error: '$collection_name' is not a collection\n";
    exit(1);
}

// Update the description
global $wpdb;
$result = $wpdb->update(
    $wpdb->postmeta,
    array('meta_value' => $new_description),
    array(
        'meta_key' => 'post_tag_' . $tag->term_id . '_collection_description',
        'post_id' => $tag->term_id
    )
);

if ($result === false) {
    echo "Error updating description\n";
    exit(1);
}

echo "✓ Collection description updated successfully!\n";
echo "New description: $new_description\n";
echo "Collection URL: " . get_term_link($tag) . "\n";
