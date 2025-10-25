<?php

/**
 * Collection Tag Helper Script
 * Usage: php create-collection.php "Collection Name" "Collection Description"
 */

if ($argc < 3) {
    echo "Usage: php create-collection.php \"Collection Name\" \"Collection Description\"\n";
    echo "Example: php create-collection.php \"Summer Special\" \"Hot summer episodes with beach themes\"\n";
    exit(1);
}

$collection_name = $argv[1];
$collection_description = $argv[2];
$collection_slug = strtolower(str_replace(' ', '-', $collection_name));

// Include WordPress
require_once('/var/www/html/wp-config.php');

echo "Creating collection: $collection_name\n";

// 1. Create the tag
$term_result = wp_insert_term($collection_name, 'post_tag', array(
    'slug' => $collection_slug,
    'description' => ''
));

if (is_wp_error($term_result)) {
    echo "Error creating tag: " . $term_result->get_error_message() . "\n";
    exit(1);
}

$term_id = $term_result['term_id'];
echo "✓ Tag created with ID: $term_id\n";

// 2. Add collection metadata
global $wpdb;

$metadata = array(
    'post_tag_' . $term_id . '_is_collection_tag' => '1',
    'post_tag_' . $term_id . '_collection_description' => $collection_description,
    'post_tag_' . $term_id . '_collection_episode_order' => 'newest'
);

foreach ($metadata as $meta_key => $meta_value) {
    $result = $wpdb->insert(
        $wpdb->postmeta,
        array(
            'post_id' => $term_id,
            'meta_key' => $meta_key,
            'meta_value' => $meta_value
        )
    );

    if ($result === false) {
        echo "Error adding metadata: $meta_key\n";
        exit(1);
    }
}

echo "✓ Collection metadata added\n";

// 3. Test the collection
$tag = get_term($term_id, 'post_tag');
if (flexpress_is_collection_tag($tag)) {
    echo "✓ Collection successfully created and verified!\n";
    echo "Collection URL: " . get_term_link($tag) . "\n";
    echo "Collection Description: " . flexpress_get_collection_metadata($tag)['description'] . "\n";
} else {
    echo "✗ Collection verification failed\n";
    exit(1);
}

echo "\nNext steps:\n";
echo "1. Assign episodes to this tag in WordPress admin\n";
echo "2. Visit the collection page: " . get_term_link($tag) . "\n";
echo "3. The collection will appear with a badge on the episodes page\n";
