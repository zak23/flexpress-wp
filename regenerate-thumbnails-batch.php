<?php
/**
 * Batch Thumbnail Regeneration Script
 * Processes images in smaller batches to avoid timeouts
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('/var/www/html/wp-load.php');

function regenerate_batch($offset = 0, $limit = 10) {
    // Get image attachments in batches
    $attachments = get_posts(array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'post_status' => 'inherit',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'fields' => 'ids'
    ));

    $count = 0;
    foreach ($attachments as $id) {
        $file = get_attached_file($id);
        if ($file && file_exists($file)) {
            $meta = wp_generate_attachment_metadata($id, $file);
            if ($meta) {
                wp_update_attachment_metadata($id, $meta);
                $count++;
                echo "Regenerated #$id\n";
            }
        }
    }

    return count($attachments); // Return number processed in this batch
}

$total_processed = 0;
$batch_size = 10;
$batch = 0;

// Process in batches until we get less than batch_size back
while (true) {
    echo "Processing batch " . ($batch + 1) . "...\n";
    $processed = regenerate_batch($total_processed, $batch_size);

    if ($processed == 0) {
        break; // No more images to process
    }

    $total_processed += $processed;
    $batch++;

    // Small delay between batches to prevent overwhelming the server
    if ($processed > 0) {
        sleep(1);
    }

    // Safety check - if we've been running too long, suggest continuing later
    if ($batch > 50) {
        echo "Processed $total_processed images so far. Run script again to continue.\n";
        break;
    }
}

echo "✅ Thumbnail regeneration complete! Processed $total_processed images total.\n";
echo "Your new image sizes (casting-image: 500px wide, model-card-alt: 250px wide) are now available.\n";
?>




