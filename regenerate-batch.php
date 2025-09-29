<?php
/**
 * Regenerate thumbnails in batches to avoid timeout
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('/var/www/html/wp-config.php');
require_once('/var/www/html/wp-load.php');

// Get all image attachments
$attachments = get_posts(array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'post_status' => 'inherit',
    'posts_per_page' => -1,
    'fields' => 'ids'
));

$total = count($attachments);
$batch_size = 10;
$offset = isset($argv[1]) ? (int)$argv[1] : 0;

echo "Processing batch starting at offset $offset...\n";

$processed = 0;
for ($i = $offset; $i < min($offset + $batch_size, $total); $i++) {
    $id = $attachments[$i];
    $file = get_attached_file($id);
    if ($file && file_exists($file)) {
        $meta = wp_generate_attachment_metadata($id, $file);
        if ($meta) {
            wp_update_attachment_metadata($id, $meta);
            $processed++;
            echo "Regenerated #$id ($i+1/$total)\n";
        }
    }
}

echo "Batch complete: $processed images processed\n";
echo "Next batch: php regenerate-batch.php " . ($offset + $batch_size) . "\n";
?>
