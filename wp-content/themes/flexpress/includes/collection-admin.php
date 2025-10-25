<?php

/**
 * Collection Management Admin Page
 * Add this to your theme's functions.php or create as a plugin
 */

// Add admin menu
add_action('admin_menu', 'flexpress_add_collection_admin_menu');

function flexpress_add_collection_admin_menu()
{
    add_submenu_page(
        'edit-tags.php?taxonomy=post_tag',
        'Collection Management',
        'Collection Management',
        'manage_options',
        'collection-management',
        'flexpress_collection_admin_page'
    );
}

// Admin page content
function flexpress_collection_admin_page()
{
    // Handle form submission
    if (isset($_POST['update_collection']) && wp_verify_nonce($_POST['collection_nonce'], 'update_collection')) {
        $tag_id = intval($_POST['tag_id']);
        $description = sanitize_textarea_field($_POST['collection_description']);
        $episode_order = sanitize_text_field($_POST['episode_order']);
        $custom_css = sanitize_text_field($_POST['custom_css']);

        // Update collection metadata
        update_post_meta($tag_id, 'post_tag_' . $tag_id . '_collection_description', $description);
        update_post_meta($tag_id, 'post_tag_' . $tag_id . '_collection_episode_order', $episode_order);
        update_post_meta($tag_id, 'post_tag_' . $tag_id . '_collection_custom_css', $custom_css);

        echo '<div class="notice notice-success"><p>Collection updated successfully!</p></div>';
    }

    // Get all collection tags
    global $wpdb;
    $collections = $wpdb->get_results("
        SELECT t.term_id, t.name, t.slug, pm1.meta_value as description, pm2.meta_value as episode_order, pm3.meta_value as custom_css
        FROM {$wpdb->terms} t
        JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
        JOIN {$wpdb->postmeta} pm ON pm.meta_key = CONCAT('post_tag_', t.term_id, '_is_collection_tag') AND pm.post_id = t.term_id
        LEFT JOIN {$wpdb->postmeta} pm1 ON pm1.meta_key = CONCAT('post_tag_', t.term_id, '_collection_description') AND pm1.post_id = t.term_id
        LEFT JOIN {$wpdb->postmeta} pm2 ON pm2.meta_key = CONCAT('post_tag_', t.term_id, '_collection_episode_order') AND pm2.post_id = t.term_id
        LEFT JOIN {$wpdb->postmeta} pm3 ON pm3.meta_key = CONCAT('post_tag_', t.term_id, '_collection_custom_css') AND pm3.post_id = t.term_id
        WHERE tt.taxonomy = 'post_tag' AND pm.meta_value = '1'
        ORDER BY t.name
    ");
?>

    <div class="wrap">
        <h1>Collection Management</h1>
        <p>Manage your episode collections here.</p>

        <?php if (!empty($collections)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Collection Name</th>
                        <th>Description</th>
                        <th>Episode Order</th>
                        <th>Custom CSS</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($collections as $collection): ?>
                        <tr>
                            <td><strong><?php echo esc_html($collection->name); ?></strong></td>
                            <td><?php echo esc_html($collection->description ?: 'No description'); ?></td>
                            <td><?php echo esc_html($collection->episode_order ?: 'newest'); ?></td>
                            <td><?php echo esc_html($collection->custom_css ?: 'None'); ?></td>
                            <td>
                                <button class="button button-small" onclick="toggleEditForm(<?php echo $collection->term_id; ?>)">
                                    Edit
                                </button>
                                <a href="<?php echo get_term_link($collection->term_id); ?>" class="button button-small" target="_blank">
                                    View
                                </a>
                            </td>
                        </tr>
                        <tr id="edit-form-<?php echo $collection->term_id; ?>" style="display: none;">
                            <td colspan="5">
                                <form method="post" style="background: #f9f9f9; padding: 20px; margin: 10px 0;">
                                    <?php wp_nonce_field('update_collection', 'collection_nonce'); ?>
                                    <input type="hidden" name="tag_id" value="<?php echo $collection->term_id; ?>">

                                    <h3>Edit <?php echo esc_html($collection->name); ?></h3>

                                    <table class="form-table">
                                        <tr>
                                            <th scope="row">Description</th>
                                            <td>
                                                <textarea name="collection_description" rows="3" cols="50" style="width: 100%;"><?php echo esc_textarea($collection->description); ?></textarea>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Episode Order</th>
                                            <td>
                                                <select name="episode_order">
                                                    <option value="newest" <?php selected($collection->episode_order, 'newest'); ?>>Newest First</option>
                                                    <option value="oldest" <?php selected($collection->episode_order, 'oldest'); ?>>Oldest First</option>
                                                    <option value="title" <?php selected($collection->episode_order, 'title'); ?>>Alphabetical</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Custom CSS Class</th>
                                            <td>
                                                <input type="text" name="custom_css" value="<?php echo esc_attr($collection->custom_css); ?>" style="width: 100%;" placeholder="my-custom-class">
                                            </td>
                                        </tr>
                                    </table>

                                    <p class="submit">
                                        <input type="submit" name="update_collection" class="button-primary" value="Update Collection">
                                        <button type="button" class="button" onclick="toggleEditForm(<?php echo $collection->term_id; ?>)">Cancel</button>
                                    </p>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No collections found. <a href="<?php echo admin_url('edit-tags.php?taxonomy=post_tag'); ?>">Create some tags first</a>.</p>
        <?php endif; ?>
    </div>

    <script>
        function toggleEditForm(tagId) {
            var form = document.getElementById('edit-form-' + tagId);
            if (form.style.display === 'none') {
                form.style.display = 'table-row';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
<?php
}
