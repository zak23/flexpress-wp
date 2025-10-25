<?php

/**
 * Migrate Collection Data from postmeta to termmeta
 * This script moves collection data from the old postmeta format to the new termmeta format
 */

require_once('/var/www/html/wp-config.php');

echo "Migrating collection data from postmeta to termmeta...\n";

global $wpdb;

// Get all collection tags
$collections = $wpdb->get_results("
    SELECT t.term_id, t.name
    FROM {$wpdb->terms} t
    JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
    JOIN {$wpdb->postmeta} pm ON pm.meta_key = CONCAT('post_tag_', t.term_id, '_is_collection_tag') AND pm.post_id = t.term_id
    WHERE tt.taxonomy = 'post_tag' AND pm.meta_value = '1'
");

foreach ($collections as $collection) {
    $term_id = $collection->term_id;
    echo "Migrating collection: {$collection->name} (ID: $term_id)\n";

    // Migrate description
    $description = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} 
         WHERE meta_key = %s AND post_id = %d",
        'post_tag_' . $term_id . '_collection_description',
        $term_id
    ));

    if ($description) {
        update_term_meta($term_id, 'collection_description', $description);
        echo "  ✓ Description migrated\n";
    }

    // Migrate episode order
    $episode_order = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} 
         WHERE meta_key = %s AND post_id = %d",
        'post_tag_' . $term_id . '_collection_episode_order',
        $term_id
    ));

    if ($episode_order) {
        update_term_meta($term_id, 'collection_episode_order', $episode_order);
        echo "  ✓ Episode order migrated\n";
    }

    // Migrate custom CSS
    $custom_css = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} 
         WHERE meta_key = %s AND post_id = %d",
        'post_tag_' . $term_id . '_collection_custom_css',
        $term_id
    ));

    if ($custom_css) {
        update_term_meta($term_id, 'collection_custom_css', $custom_css);
        echo "  ✓ Custom CSS migrated\n";
    }

    // Ensure collection flag is set
    update_term_meta($term_id, 'is_collection_tag', '1');
    echo "  ✓ Collection flag set\n";
}

echo "\nMigration complete!\n";
echo "You can now edit collections in WordPress admin:\n";
echo "1. Go to Posts → Tags\n";
echo "2. Edit any collection tag\n";
echo "3. You'll see the Collection fields at the bottom\n";
