<?php

/**
 * Simple Collection Editor
 * Access at: http://192.168.1.238:8085/collection-editor.php
 */

// Include WordPress
require_once('wp-config.php');

// Handle form submission
if ($_POST && isset($_POST['update_collection'])) {
    $tag_id = intval($_POST['tag_id']);
    $description = sanitize_textarea_field($_POST['description']);

    global $wpdb;
    $result = $wpdb->update(
        $wpdb->postmeta,
        array('meta_value' => $description),
        array(
            'meta_key' => 'post_tag_' . $tag_id . '_collection_description',
            'post_id' => $tag_id
        )
    );

    if ($result !== false) {
        $success = "Collection updated successfully!";
    } else {
        $error = "Error updating collection.";
    }
}

// Get collections
global $wpdb;
$collections = $wpdb->get_results("
    SELECT t.term_id, t.name, pm.meta_value as description
    FROM {$wpdb->terms} t
    JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
    JOIN {$wpdb->postmeta} pm ON pm.meta_key = CONCAT('post_tag_', t.term_id, '_is_collection_tag') AND pm.post_id = t.term_id
    LEFT JOIN {$wpdb->postmeta} pm2 ON pm2.meta_key = CONCAT('post_tag_', t.term_id, '_collection_description') AND pm2.post_id = t.term_id
    WHERE tt.taxonomy = 'post_tag' AND pm.meta_value = '1'
    ORDER BY t.name
");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Collection Editor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background: #f1f1f1;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
        }

        .collection {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .collection h3 {
            margin-top: 0;
            color: #0073aa;
        }

        textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        button {
            background: #0073aa;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #005a87;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .links {
            margin-top: 20px;
        }

        .links a {
            display: inline-block;
            margin-right: 15px;
            color: #0073aa;
            text-decoration: none;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🎬 Collection Editor</h1>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($collections)): ?>
            <?php foreach ($collections as $collection): ?>
                <div class="collection">
                    <h3><?php echo esc_html($collection->name); ?></h3>

                    <form method="post">
                        <input type="hidden" name="tag_id" value="<?php echo $collection->term_id; ?>">

                        <label for="description_<?php echo $collection->term_id; ?>">Description:</label><br>
                        <textarea name="description" id="description_<?php echo $collection->term_id; ?>" placeholder="Enter collection description..."><?php echo esc_textarea($collection->description); ?></textarea><br><br>

                        <button type="submit" name="update_collection">Update Collection</button>
                    </form>

                    <div class="links">
                        <a href="<?php echo get_term_link($collection->term_id); ?>" target="_blank">View Collection Page</a>
                        <a href="<?php echo home_url('/episodes/'); ?>" target="_blank">View Episodes Page</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No collections found. Create some collections first.</p>
        <?php endif; ?>

        <div class="links">
            <a href="<?php echo home_url('/wp-admin/'); ?>">WordPress Admin</a>
            <a href="<?php echo home_url('/episodes/'); ?>">Episodes Page</a>
        </div>
    </div>
</body>

</html>