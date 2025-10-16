<?php
/**
 * Regenerate thumbnails for all images
 */

// Load WordPress
define('WP_USE_THEMES', false);
// Try multiple possible locations for wp-config.php
$wp_config_paths = [
    '/var/www/html/wp-config.php',
    __DIR__ . '/wp-config.php',
    __DIR__ . '/../wp-config.php',
    __DIR__ . '/wordpress/wp-config.php'
];

$wp_config_loaded = false;
foreach ($wp_config_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_config_loaded = true;
        break;
    }
}

if (!$wp_config_loaded) {
    die('Could not find wp-config.php in any of the expected locations.');
}

require_once(ABSPATH . 'wp-load.php');

// Get all image attachments
$attachments = get_posts(array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'post_status' => 'inherit',
    'posts_per_page' => -1,
    'fields' => 'ids'
));

$count = 0;
$total = count($attachments);

echo "Found $total images to process...\n";

foreach ($attachments as $id) {
    $file = get_attached_file($id);
    if ($file && file_exists($file)) {
        $meta = wp_generate_attachment_metadata($id, $file);
        if ($meta) {
            wp_update_attachment_metadata($id, $meta);
            $count++;
            echo "Regenerated #$id ($count/$total)\n";
        }
    }
}

echo "Total regenerated: $count\n";
echo "Done!\n";
?>
