<?php

/**
 * FlexPress Gallery System
 * 
 * Integrates with episodes to provide gallery functionality using BunnyCDN Storage
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Gallery System Class
 */
class FlexPress_Gallery_System
{

    /**
     * Constructor
     */
    public function __construct()
    {
        // Check if we're in admin and if init has already been called
        if (is_admin() && did_action('init')) {
            $this->init_gallery_system();
        } else {
            add_action('init', array($this, 'init_gallery_system'));
        }

        // Add meta boxes immediately if we're in admin
        if (is_admin()) {
            add_action('add_meta_boxes', array($this, 'add_gallery_meta_box'));
        }

        add_action('save_post', array($this, 'save_gallery_data'));
        add_action('wp_ajax_flexpress_upload_gallery_image', array($this, 'ajax_upload_gallery_image'));
        add_action('wp_ajax_flexpress_delete_gallery_image', array($this, 'ajax_delete_gallery_image'));
        add_action('wp_ajax_flexpress_delete_all_gallery_images', array($this, 'ajax_delete_all_gallery_images'));
        add_action('wp_ajax_flexpress_reorder_gallery_images', array($this, 'ajax_reorder_gallery_images'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Initialize gallery system
     */
    public function init_gallery_system()
    {
        // Add gallery support to episodes
        add_post_type_support('episode', 'gallery');

        // Add gallery support to extras
        add_post_type_support('extras', 'gallery');

        // Register gallery image sizes
        $this->register_gallery_image_sizes();
    }

    /**
     * Register gallery image sizes
     */
    private function register_gallery_image_sizes()
    {
        // Thumbnail size for gallery grid
        add_image_size('gallery-thumbnail', 300, 300, true);

        // Medium size for gallery preview
        add_image_size('gallery-medium', 600, 600, false);

        // Large size for gallery lightbox
        add_image_size('gallery-large', 1200, 1200, false);

        // Full size for high-resolution viewing
        add_image_size('gallery-full', 1920, 1920, false);
    }

    /**
     * Add gallery meta box to episodes and extras
     */
    public function add_gallery_meta_box()
    {
        add_meta_box(
            'episode_gallery',
            __('Episode Gallery', 'flexpress'),
            array($this, 'render_gallery_meta_box'),
            'episode',
            'normal',
            'high'
        );

        add_meta_box(
            'extras_gallery',
            __('Extras Gallery', 'flexpress'),
            array($this, 'render_extras_gallery_meta_box'),
            'extras',
            'normal',
            'high'
        );
    }

    /**
     * Render gallery meta box
     */
    public function render_gallery_meta_box($post)
    {
        wp_nonce_field('episode_gallery_meta_box', 'episode_gallery_meta_box_nonce');

        $gallery_images = get_post_meta($post->ID, '_episode_gallery_images', true);
        if (!is_array($gallery_images)) {
            $gallery_images = array();
        }

?>
        <div class="flexpress-gallery-manager">
            <div class="gallery-upload-section">
                <h4><?php _e('Upload Gallery Images', 'flexpress'); ?></h4>
                <p class="description">
                    <?php _e('Upload images to create a gallery for this episode. Images will be automatically resized and optimized.', 'flexpress'); ?>
                </p>

                <div class="gallery-upload-area" id="gallery-upload-area">
                    <div class="upload-prompt">
                        <span class="dashicons dashicons-upload"></span>
                        <p><?php _e('Drag & drop images here or click to select', 'flexpress'); ?></p>
                        <button type="button" class="button button-primary" id="select-gallery-images">
                            <?php _e('Select Images', 'flexpress'); ?>
                        </button>
                    </div>
                    <input type="file" id="gallery-file-input" multiple accept="image/*" style="display: none;">
                </div>

                <div class="upload-progress" id="upload-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    <span class="progress-text" id="progress-text">0%</span>
                </div>
            </div>

            <div class="gallery-images-section">
                <div class="gallery-images-header">
                    <h4><?php _e('Gallery Images', 'flexpress'); ?></h4>
                    <?php if (!empty($gallery_images)) : ?>
                        <button type="button" class="button button-secondary" id="delete-all-gallery-images">
                            <?php _e('Delete All Images', 'flexpress'); ?>
                        </button>
                    <?php endif; ?>
                </div>
                <div class="gallery-images-grid" id="gallery-images-grid">
                    <?php if (!empty($gallery_images)) : ?>
                        <?php foreach ($gallery_images as $index => $image) : ?>
                            <div class="gallery-image-item" data-image-id="<?php echo esc_attr($image['id']); ?>">
                                <div class="image-preview">
                                    <img src="<?php echo esc_url($image['thumbnail']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
                                    <div class="image-overlay">
                                        <button type="button" class="button button-small edit-image" title="<?php _e('Edit Image', 'flexpress'); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                        <button type="button" class="button button-small delete-image" title="<?php _e('Delete Image', 'flexpress'); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                </div>
                                <div class="image-info">
                                    <input type="text" class="image-alt" value="<?php echo esc_attr($image['alt']); ?>"
                                        placeholder="<?php _e('Alt text', 'flexpress'); ?>">
                                    <input type="text" class="image-caption" value="<?php echo esc_attr($image['caption']); ?>"
                                        placeholder="<?php _e('Caption', 'flexpress'); ?>">
                                </div>
                                <div class="image-order">
                                    <span class="order-number"><?php echo $index + 1; ?></span>
                                    <div class="order-controls">
                                        <button type="button" class="button button-small move-up" title="<?php _e('Move Up', 'flexpress'); ?>">
                                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                                        </button>
                                        <button type="button" class="button button-small move-down" title="<?php _e('Move Down', 'flexpress'); ?>">
                                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="no-images"><?php _e('No gallery images uploaded yet.', 'flexpress'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="gallery-settings">
                <h4><?php _e('Gallery Settings', 'flexpress'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gallery_columns"><?php _e('Gallery Columns', 'flexpress'); ?></label>
                        </th>
                        <td>
                            <select name="gallery_columns" id="gallery_columns">
                                <?php
                                $current_columns = get_post_meta($post->ID, '_gallery_columns', true) ?: '5';
                                ?>
                                <option value="2" <?php selected($current_columns, '2'); ?>><?php _e('2 Columns', 'flexpress'); ?></option>
                                <option value="3" <?php selected($current_columns, '3'); ?>><?php _e('3 Columns', 'flexpress'); ?></option>
                                <option value="4" <?php selected($current_columns, '4'); ?>><?php _e('4 Columns', 'flexpress'); ?></option>
                                <option value="5" <?php selected($current_columns, '5'); ?>><?php _e('5 Columns', 'flexpress'); ?></option>
                            </select>
                            <p class="description"><?php _e('Number of columns to display in the gallery grid.', 'flexpress'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gallery_lightbox"><?php _e('Enable Lightbox', 'flexpress'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="gallery_lightbox" id="gallery_lightbox" value="1"
                                <?php checked(get_post_meta($post->ID, '_gallery_lightbox', true), '1'); ?>>
                            <label for="gallery_lightbox"><?php _e('Enable lightbox gallery viewer', 'flexpress'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gallery_autoplay"><?php _e('Gallery Autoplay', 'flexpress'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="gallery_autoplay" id="gallery_autoplay" value="1"
                                <?php checked(get_post_meta($post->ID, '_gallery_autoplay', true), '1'); ?>>
                            <label for="gallery_autoplay"><?php _e('Automatically advance through gallery images', 'flexpress'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <style>
            .flexpress-gallery-manager {
                margin: 20px 0;
            }

            .gallery-upload-area {
                border: 2px dashed #ddd;
                border-radius: 8px;
                padding: 40px;
                text-align: center;
                background: #f9f9f9;
                transition: all 0.3s ease;
            }

            .gallery-upload-area.dragover {
                border-color: #0073aa;
                background: #f0f8ff;
            }

            .upload-prompt .dashicons {
                font-size: 48px;
                color: #666;
                margin-bottom: 10px;
            }

            .upload-progress {
                margin: 20px 0;
            }

            .progress-bar {
                width: 100%;
                height: 20px;
                background: #eee;
                border-radius: 10px;
                overflow: hidden;
            }

            .progress-fill {
                height: 100%;
                background: #0073aa;
                width: 0%;
                transition: width 0.3s ease;
            }

            .gallery-images-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .gallery-images-header h4 {
                margin: 0;
            }

            .gallery-images-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }

            .gallery-image-item {
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
                background: white;
            }

            .image-preview {
                position: relative;
                height: 200px;
                overflow: hidden;
            }

            .image-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .image-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .gallery-image-item:hover .image-overlay {
                opacity: 1;
            }

            .image-info {
                padding: 15px;
            }

            .image-info input {
                width: 100%;
                margin-bottom: 10px;
            }

            .image-order {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 15px;
                background: #f9f9f9;
                border-top: 1px solid #eee;
            }

            .order-number {
                font-weight: bold;
                color: #666;
            }

            .order-controls {
                display: flex;
                gap: 5px;
            }

            .no-images {
                text-align: center;
                color: #666;
                font-style: italic;
                padding: 40px;
            }
        </style>
    <?php
    }

    /**
     * Render extras gallery meta box
     */
    public function render_extras_gallery_meta_box($post)
    {
        wp_nonce_field('extras_gallery_meta_box', 'extras_gallery_meta_box_nonce');

        $gallery_images = get_post_meta($post->ID, '_extras_gallery_images', true);
        if (!is_array($gallery_images)) {
            $gallery_images = array();
        }
    ?>
        <div class="flexpress-gallery-manager">
            <div class="gallery-upload-section">
                <h4><?php _e('Upload Gallery Images', 'flexpress'); ?></h4>
                <p class="description">
                    <?php _e('Upload images to create a gallery for this extra content. Images will be automatically resized and optimized.', 'flexpress'); ?>
                </p>

                <div class="gallery-upload-area" id="extras-gallery-upload-area">
                    <div class="upload-prompt">
                        <span class="dashicons dashicons-upload"></span>
                        <p><?php _e('Drag & drop images here or click to select', 'flexpress'); ?></p>
                        <button type="button" class="button button-primary" id="select-extras-gallery-images">
                            <?php _e('Select Images', 'flexpress'); ?>
                        </button>
                    </div>
                    <input type="file" id="extras-gallery-file-input" multiple accept="image/*" style="display: none;">
                </div>

                <div class="upload-progress" id="extras-upload-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <p class="progress-text"><?php _e('Uploading...', 'flexpress'); ?></p>
                </div>
            </div>

            <div class="gallery-images-section">
                <div class="gallery-images-header">
                    <h4><?php _e('Gallery Images', 'flexpress'); ?></h4>
                    <?php if (!empty($gallery_images)): ?>
                        <button type="button" class="button button-secondary" id="delete-all-extras-gallery-images">
                            <?php _e('Delete All Images', 'flexpress'); ?>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="gallery-images-grid" id="extras-gallery-images-grid">
                    <?php if (!empty($gallery_images)) : ?>
                        <?php foreach ($gallery_images as $index => $image) : ?>
                            <div class="gallery-image-item" data-image-id="<?php echo esc_attr($image['id']); ?>">
                                <div class="image-preview">
                                    <img src="<?php echo esc_url($image['thumbnail']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
                                    <div class="image-overlay">
                                        <button type="button" class="button button-small edit-image" title="<?php _e('Edit Image', 'flexpress'); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                        <button type="button" class="button button-small delete-image" title="<?php _e('Delete Image', 'flexpress'); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                </div>
                                <div class="image-info">
                                    <input type="text" class="image-alt" value="<?php echo esc_attr($image['alt']); ?>"
                                        placeholder="<?php _e('Alt text', 'flexpress'); ?>">
                                    <input type="text" class="image-caption" value="<?php echo esc_attr($image['caption']); ?>"
                                        placeholder="<?php _e('Caption', 'flexpress'); ?>">
                                </div>
                                <div class="image-order">
                                    <span class="order-number"><?php echo $index + 1; ?></span>
                                    <div class="order-controls">
                                        <button type="button" class="button button-small move-up" title="<?php _e('Move Up', 'flexpress'); ?>">
                                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                                        </button>
                                        <button type="button" class="button button-small move-down" title="<?php _e('Move Down', 'flexpress'); ?>">
                                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="no-images"><?php _e('No gallery images uploaded yet.', 'flexpress'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="gallery-settings">
                <h4><?php _e('Gallery Settings', 'flexpress'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="extras_gallery_columns"><?php _e('Gallery Columns', 'flexpress'); ?></label>
                        </th>
                        <td>
                            <select name="extras_gallery_columns" id="extras_gallery_columns">
                                <?php
                                $current_columns = get_post_meta($post->ID, '_extras_gallery_columns', true) ?: '3';
                                ?>
                                <option value="2" <?php selected($current_columns, '2'); ?>><?php _e('2 Columns', 'flexpress'); ?></option>
                                <option value="3" <?php selected($current_columns, '3'); ?>><?php _e('3 Columns', 'flexpress'); ?></option>
                                <option value="4" <?php selected($current_columns, '4'); ?>><?php _e('4 Columns', 'flexpress'); ?></option>
                                <option value="5" <?php selected($current_columns, '5'); ?>><?php _e('5 Columns', 'flexpress'); ?></option>
                            </select>
                            <p class="description"><?php _e('Number of columns to display in the gallery grid.', 'flexpress'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="extras_gallery_lightbox"><?php _e('Enable Lightbox', 'flexpress'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="extras_gallery_lightbox" id="extras_gallery_lightbox" value="1"
                                <?php checked(get_post_meta($post->ID, '_extras_gallery_lightbox', true), '1'); ?>>
                            <label for="extras_gallery_lightbox"><?php _e('Enable lightbox gallery viewer', 'flexpress'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <style>
            .flexpress-gallery-manager {
                margin: 20px 0;
            }

            .gallery-upload-area {
                border: 2px dashed #ddd;
                border-radius: 8px;
                padding: 40px;
                text-align: center;
                background: #f9f9f9;
                transition: all 0.3s ease;
            }

            .gallery-upload-area.dragover {
                border-color: #0073aa;
                background: #f0f8ff;
            }

            .upload-prompt .dashicons {
                font-size: 48px;
                color: #666;
                margin-bottom: 10px;
            }

            .upload-progress {
                margin: 20px 0;
            }

            .progress-bar {
                width: 100%;
                height: 20px;
                background: #eee;
                border-radius: 10px;
                overflow: hidden;
            }

            .progress-fill {
                height: 100%;
                background: #0073aa;
                width: 0%;
                transition: width 0.3s ease;
            }

            .gallery-images-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .gallery-images-header h4 {
                margin: 0;
            }

            .gallery-images-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }

            .gallery-image-item {
                border: 1px solid #ddd;
                border-radius: 8px;
                overflow: hidden;
                background: white;
            }

            .image-preview {
                position: relative;
                height: 200px;
                overflow: hidden;
            }

            .image-preview img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .image-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .gallery-image-item:hover .image-overlay {
                opacity: 1;
            }

            .image-info {
                padding: 15px;
            }

            .image-info input {
                width: 100%;
                margin-bottom: 10px;
            }

            .image-order {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 15px;
                background: #f9f9f9;
                border-top: 1px solid #eee;
            }

            .order-number {
                font-weight: bold;
                color: #666;
            }

            .order-controls {
                display: flex;
                gap: 5px;
            }

            .no-images {
                text-align: center;
                color: #666;
                font-style: italic;
                padding: 40px;
            }
        </style>
    <?php
    }

    /**
     * Save gallery data
     */
    public function save_gallery_data($post_id)
    {
        $post_type = get_post_type($post_id);

        // Check nonce based on post type
        if ($post_type === 'episode') {
            if (
                !isset($_POST['episode_gallery_meta_box_nonce']) ||
                !wp_verify_nonce($_POST['episode_gallery_meta_box_nonce'], 'episode_gallery_meta_box')
            ) {
                return;
            }
        } elseif ($post_type === 'extras') {
            if (
                !isset($_POST['extras_gallery_meta_box_nonce']) ||
                !wp_verify_nonce($_POST['extras_gallery_meta_box_nonce'], 'extras_gallery_meta_box')
            ) {
                return;
            }
        } else {
            return; // Not a supported post type
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save gallery settings based on post type
        if ($post_type === 'episode') {
            if (isset($_POST['gallery_columns'])) {
                update_post_meta($post_id, '_gallery_columns', sanitize_text_field($_POST['gallery_columns']));
            }

            if (isset($_POST['gallery_lightbox'])) {
                update_post_meta($post_id, '_gallery_lightbox', '1');
            } else {
                delete_post_meta($post_id, '_gallery_lightbox');
            }

            if (isset($_POST['gallery_autoplay'])) {
                update_post_meta($post_id, '_gallery_autoplay', '1');
            } else {
                delete_post_meta($post_id, '_gallery_autoplay');
            }
        } elseif ($post_type === 'extras') {
            if (isset($_POST['extras_gallery_columns'])) {
                update_post_meta($post_id, '_extras_gallery_columns', sanitize_text_field($_POST['extras_gallery_columns']));
            }

            if (isset($_POST['extras_gallery_lightbox'])) {
                update_post_meta($post_id, '_extras_gallery_lightbox', '1');
            } else {
                delete_post_meta($post_id, '_extras_gallery_lightbox');
            }
        }

        // Save image metadata
        if (isset($_POST['gallery_image_title']) && is_array($_POST['gallery_image_title'])) {
            foreach ($_POST['gallery_image_title'] as $image_id => $title) {
                $image_id = intval($image_id);
                if ($image_id > 0) {
                    wp_update_post(array(
                        'ID' => $image_id,
                        'post_title' => sanitize_text_field($title)
                    ));
                }
            }
        }

        if (isset($_POST['gallery_image_alt']) && is_array($_POST['gallery_image_alt'])) {
            foreach ($_POST['gallery_image_alt'] as $image_id => $alt) {
                $image_id = intval($image_id);
                if ($image_id > 0) {
                    update_post_meta($image_id, '_wp_attachment_image_alt', sanitize_text_field($alt));
                }
            }
        }

        if (isset($_POST['gallery_image_caption']) && is_array($_POST['gallery_image_caption'])) {
            foreach ($_POST['gallery_image_caption'] as $image_id => $caption) {
                $image_id = intval($image_id);
                if ($image_id > 0) {
                    wp_update_post(array(
                        'ID' => $image_id,
                        'post_excerpt' => sanitize_textarea_field($caption)
                    ));
                }
            }
        }
    }

    /**
     * AJAX upload gallery image
     */
    public function ajax_upload_gallery_image()
    {
        error_log("=== FLEXPRESS AJAX GALLERY UPLOAD START ===");
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));

        // Check nonce and permissions
        if (
            !wp_verify_nonce($_POST['nonce'], 'flexpress_gallery_upload') ||
            !current_user_can('upload_files')
        ) {
            error_log("❌ Unauthorized upload attempt");
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $post_type = get_post_type($post_id);

        if (!$post_id || !in_array($post_type, array('episode', 'extras'))) {
            error_log("❌ Invalid post ID: $post_id or post type: $post_type");
            wp_die('Invalid post ID or post type');
        }

        error_log("✅ Valid upload request for $post_type ID: $post_id");

        // Handle file upload
        if (!isset($_FILES['image'])) {
            error_log("❌ No file uploaded");
            wp_die('No file uploaded');
        }

        $file = $_FILES['image'];
        error_log("File details:");
        error_log("  Name: " . $file['name']);
        error_log("  Size: " . $file['size'] . " bytes");
        error_log("  Type: " . $file['type']);
        error_log("  Temp file: " . $file['tmp_name']);

        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error'])) {
            error_log("❌ WordPress upload error: " . $upload['error']);
            wp_die($upload['error']);
        }

        error_log("✅ WordPress upload successful:");
        error_log("  Upload file: " . $upload['file']);
        error_log("  Upload URL: " . $upload['url']);
        error_log("  Upload type: " . $upload['type']);

        // Create attachment
        $attachment_id = wp_insert_attachment(array(
            'post_title' => sanitize_text_field($_POST['title']),
            'post_content' => sanitize_textarea_field($_POST['description']),
            'post_excerpt' => sanitize_text_field($_POST['caption']),
            'post_status' => 'inherit',
            'post_parent' => $post_id,
            'post_mime_type' => $upload['type']
        ), $upload['file'], $post_id);

        if (is_wp_error($attachment_id)) {
            error_log("❌ Failed to create attachment: " . $attachment_id->get_error_message());
            wp_die('Failed to create attachment');
        }

        error_log("✅ Attachment created with ID: $attachment_id");

        // Generate image sizes
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        error_log("✅ Image sizes generated");

        // Upload to BunnyCDN if configured
        error_log("Starting BunnyCDN upload...");
        $bunnycdn_result = $this->upload_to_bunnycdn($attachment_id, $upload['file'], $post_id);
        error_log("BunnyCDN upload result: " . ($bunnycdn_result ?: 'FAILED'));

        // Get image URLs
        $thumbnail_url = wp_get_attachment_image_url($attachment_id, 'gallery-thumbnail');
        $medium_url = wp_get_attachment_image_url($attachment_id, 'gallery-medium');
        $large_url = wp_get_attachment_image_url($attachment_id, 'gallery-large');
        $full_url = wp_get_attachment_image_url($attachment_id, 'full');

        // Add to gallery based on post type
        $meta_key = ($post_type === 'episode') ? '_episode_gallery_images' : '_extras_gallery_images';
        $gallery_images = get_post_meta($post_id, $meta_key, true);
        if (!is_array($gallery_images)) {
            $gallery_images = array();
        }

        $new_image = array(
            'id' => $attachment_id,
            'title' => sanitize_text_field($_POST['title']),
            'alt' => sanitize_text_field($_POST['alt']),
            'caption' => sanitize_text_field($_POST['caption']),
            'thumbnail' => $thumbnail_url,
            'medium' => $medium_url,
            'large' => $large_url,
            'full' => $full_url,
            'bunnycdn_url' => $bunnycdn_result,
            'bunnycdn_thumbnail_url' => $this->get_bunnycdn_thumbnail_url($bunnycdn_result),
            'upload_date' => current_time('mysql')
        );

        error_log("Gallery image data:");
        error_log("  WordPress URLs: thumbnail=$thumbnail_url, medium=$medium_url, large=$large_url, full=$full_url");
        error_log("  BunnyCDN URL: " . ($bunnycdn_url ?: 'NOT SET'));

        $gallery_images[] = $new_image;
        update_post_meta($post_id, $meta_key, $gallery_images);
        error_log("✅ Gallery updated with " . count($gallery_images) . " images");

        error_log("=== FLEXPRESS AJAX GALLERY UPLOAD COMPLETE ===");

        wp_send_json_success(array(
            'message' => 'Image uploaded successfully',
            'image' => $new_image
        ));
    }

    /**
     * AJAX delete gallery image
     */
    public function ajax_delete_gallery_image()
    {
        // Check nonce and permissions
        if (
            !wp_verify_nonce($_POST['nonce'], 'flexpress_gallery_delete') ||
            !current_user_can('delete_posts')
        ) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $image_id = intval($_POST['image_id']);
        $post_type = get_post_type($post_id);

        if (!$post_id || !$image_id || !in_array($post_type, array('episode', 'extras'))) {
            wp_die('Invalid IDs or post type');
        }

        // Remove from gallery based on post type
        $meta_key = ($post_type === 'episode') ? '_episode_gallery_images' : '_extras_gallery_images';
        $gallery_images = get_post_meta($post_id, $meta_key, true);
        if (is_array($gallery_images)) {
            $gallery_images = array_filter($gallery_images, function ($img) use ($image_id) {
                return $img['id'] != $image_id;
            });
            update_post_meta($post_id, $meta_key, $gallery_images);
        }

        // Delete attachment
        wp_delete_attachment($image_id, true);

        wp_send_json_success('Image deleted successfully');
    }

    /**
     * AJAX delete all gallery images
     */
    public function ajax_delete_all_gallery_images()
    {
        // Check nonce and permissions
        if (
            !wp_verify_nonce($_POST['nonce'], 'flexpress_gallery_delete_all') ||
            !current_user_can('delete_posts')
        ) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $post_type = get_post_type($post_id);

        if (!$post_id || !in_array($post_type, array('episode', 'extras'))) {
            wp_die('Invalid post ID or post type');
        }

        // Get all gallery images based on post type
        $meta_key = ($post_type === 'episode') ? '_episode_gallery_images' : '_extras_gallery_images';
        $gallery_images = get_post_meta($post_id, $meta_key, true);
        if (!is_array($gallery_images)) {
            wp_send_json_success('No images to delete');
        }

        // Delete all attachments
        foreach ($gallery_images as $image) {
            if (isset($image['id'])) {
                wp_delete_attachment($image['id'], true);
            }
        }

        // Clear gallery meta
        delete_post_meta($post_id, $meta_key);

        wp_send_json_success('All gallery images deleted successfully');
    }

    /**
     * AJAX reorder gallery images
     */
    public function ajax_reorder_gallery_images()
    {
        // Check nonce and permissions
        if (
            !wp_verify_nonce($_POST['nonce'], 'flexpress_gallery_reorder') ||
            !current_user_can('edit_posts')
        ) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $image_order = array_map('intval', $_POST['image_order']);
        $post_type = get_post_type($post_id);

        if (!$post_id || empty($image_order) || !in_array($post_type, array('episode', 'extras'))) {
            wp_die('Invalid data or post type');
        }

        // Reorder gallery based on post type
        $meta_key = ($post_type === 'episode') ? '_episode_gallery_images' : '_extras_gallery_images';
        $gallery_images = get_post_meta($post_id, $meta_key, true);
        if (is_array($gallery_images)) {
            $reordered = array();
            foreach ($image_order as $image_id) {
                foreach ($gallery_images as $image) {
                    if ($image['id'] == $image_id) {
                        $reordered[] = $image;
                        break;
                    }
                }
            }
            update_post_meta($post_id, $meta_key, $reordered);
        }

        wp_send_json_success('Gallery reordered successfully');
    }

    /**
     * Upload image to BunnyCDN Storage
     */
    private function upload_to_bunnycdn($attachment_id, $file_path, $post_id)
    {
        error_log("=== FLEXPRESS GALLERY UPLOAD START ===");
        error_log("Attachment ID: $attachment_id");
        error_log("Post ID: $post_id");
        error_log("File Path: $file_path");

        // Get post type
        $post_type = get_post_type($post_id);
        error_log("Post Type: $post_type");

        // Get BunnyCDN settings
        $video_settings = get_option('flexpress_video_settings', array());
        $storage_api_key = isset($video_settings['bunnycdn_storage_api_key']) ? $video_settings['bunnycdn_storage_api_key'] : '';
        $storage_zone = isset($video_settings['bunnycdn_storage_zone']) ? $video_settings['bunnycdn_storage_zone'] : '';
        $storage_url = isset($video_settings['bunnycdn_storage_url']) ? $video_settings['bunnycdn_storage_url'] : '';
        $serve_url = isset($video_settings['bunnycdn_serve_url']) ? $video_settings['bunnycdn_serve_url'] : '';

        error_log("BunnyCDN Settings:");
        error_log("  Storage Zone: " . ($storage_zone ?: 'NOT SET'));
        error_log("  Storage URL: " . ($storage_url ?: 'NOT SET'));
        error_log("  Serve URL: " . ($serve_url ?: 'NOT SET'));
        error_log("  API Key: " . ($storage_api_key ? substr($storage_api_key, 0, 8) . '...' : 'NOT SET'));

        if (empty($storage_api_key) || empty($storage_zone) || empty($storage_url)) {
            error_log("❌ BunnyCDN Storage not configured - missing required settings");
            return ''; // BunnyCDN Storage not configured
        }

        // Generate unique filename with post_id folder structure
        $file_info = pathinfo($file_path);
        $original_filename = $file_info['filename'];
        $random_string = wp_generate_password(8, false);
        $unix_timestamp = time();
        $filename = $original_filename . '-' . $random_string . '-' . $unix_timestamp . '.jpg';
        $remote_path = ($post_type === 'episode') ?
            'episodes/galleries/' . $post_id . '/' . $filename :
            'extras/galleries/' . $post_id . '/' . $filename;

        error_log("Filename Generation:");
        error_log("  Original filename: $original_filename");
        error_log("  Random string: $random_string");
        error_log("  Unix timestamp: $unix_timestamp");
        error_log("  Generated filename: $filename");
        error_log("  Remote path: $remote_path");

        // Upload original image to BunnyCDN Storage via HTTP API
        $upload_url = 'https://' . $storage_url . '/' . $storage_zone . '/' . $remote_path;

        error_log("HTTP API Upload Details:");
        error_log("  Upload URL: $upload_url");
        error_log("  Storage Zone: $storage_zone");
        error_log("  API Key: " . substr($storage_api_key, 0, 8) . "...");

        // Read file content
        $file_content = file_get_contents($file_path);
        if ($file_content === false) {
            error_log('❌ Failed to read file content');
            return '';
        }

        error_log("File content read: " . strlen($file_content) . " bytes");

        // Upload original image using HTTP PUT method
        $response = wp_remote_request($upload_url, array(
            'method' => 'PUT',
            'headers' => array(
                'AccessKey' => $storage_api_key,
                'Content-Type' => mime_content_type($file_path)
            ),
            'body' => $file_content,
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            error_log('❌ BunnyCDN HTTP upload error: ' . $response->get_error_message());
            return '';
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        error_log("HTTP Status Code: $status_code");
        error_log("Response Body: " . substr($response_body, 0, 200));

        if ($status_code !== 201) {
            error_log('❌ BunnyCDN HTTP upload failed with status: ' . $status_code);
            return '';
        }

        error_log('✅ Original file uploaded successfully to BunnyCDN Storage via HTTP API');

        // Generate and upload thumbnail
        $thumbnail_url = $this->generate_and_upload_thumbnail($file_path, $filename, $post_id, $post_type, $storage_api_key, $storage_zone, $storage_url, $serve_url);

        // Return CDN URL (use serve URL if available, otherwise storage URL)
        $cdn_url = !empty($serve_url) ? $serve_url : $storage_url;
        $final_url = 'https://' . $cdn_url . '/' . $remote_path;

        error_log("Final CDN URL: $final_url");
        error_log("Thumbnail URL: " . ($thumbnail_url ?: 'NOT GENERATED'));
        error_log("=== FLEXPRESS GALLERY UPLOAD COMPLETE ===");

        // Store thumbnail URL for later retrieval
        update_post_meta($attachment_id, '_bunnycdn_thumbnail_url', $thumbnail_url);

        return $final_url;
    }

    /**
     * Generate and upload thumbnail to BunnyCDN Storage
     */
    private function generate_and_upload_thumbnail($original_file_path, $original_filename, $post_id, $post_type, $storage_api_key, $storage_zone, $storage_url, $serve_url)
    {
        error_log("=== THUMBNAIL GENERATION START ===");

        // Get thumbnail settings
        $video_settings = get_option('flexpress_video_settings', array());
        $thumbnail_size = isset($video_settings['gallery_thumbnail_size']) ? (int)$video_settings['gallery_thumbnail_size'] : 300;

        error_log("Thumbnail Settings:");
        error_log("  Size: {$thumbnail_size}x{$thumbnail_size} pixels");

        // Generate thumbnail filename with correct path based on post type
        $thumbnail_filename = 'thumb_' . $original_filename;
        $thumbnail_remote_path = ($post_type === 'episode') ?
            'episodes/galleries/' . $post_id . '/thumbs/' . $thumbnail_filename :
            'extras/galleries/' . $post_id . '/thumbs/' . $thumbnail_filename;

        error_log("Thumbnail Path: $thumbnail_remote_path");

        // Generate thumbnail using WordPress image editor
        $image_editor = wp_get_image_editor($original_file_path);

        if (is_wp_error($image_editor)) {
            error_log('❌ Failed to create image editor: ' . $image_editor->get_error_message());
            return '';
        }

        // Get original image dimensions
        $original_size = $image_editor->get_size();
        error_log("Original Image Size: {$original_size['width']}x{$original_size['height']}");

        // Calculate crop dimensions (center crop to square)
        $crop_size = min($original_size['width'], $original_size['height']);
        $crop_x = ($original_size['width'] - $crop_size) / 2;
        $crop_y = ($original_size['height'] - $crop_size) / 2;

        error_log("Crop Dimensions: {$crop_size}x{$crop_size} at ({$crop_x}, {$crop_y})");

        // Crop to square
        $crop_result = $image_editor->crop($crop_x, $crop_y, $crop_size, $crop_size);

        if (is_wp_error($crop_result)) {
            error_log('❌ Failed to crop image: ' . $crop_result->get_error_message());
            return '';
        }

        error_log('✅ Image cropped to square successfully');

        // Resize to thumbnail size
        $resize_result = $image_editor->resize($thumbnail_size, $thumbnail_size, true);

        if (is_wp_error($resize_result)) {
            error_log('❌ Failed to resize image: ' . $resize_result->get_error_message());
            return '';
        }

        error_log('✅ Image resized to thumbnail size successfully');

        // Save thumbnail to temporary file
        $temp_dir = wp_upload_dir();
        $temp_file = $temp_dir['path'] . '/temp_thumb_' . $thumbnail_filename;

        $save_result = $image_editor->save($temp_file, 'image/jpeg');

        if (is_wp_error($save_result)) {
            error_log('❌ Failed to save thumbnail: ' . $save_result->get_error_message());
            return '';
        }

        error_log('✅ Thumbnail saved to temporary file: ' . $temp_file);

        // Upload thumbnail to BunnyCDN
        $thumbnail_upload_url = 'https://' . $storage_url . '/' . $storage_zone . '/' . $thumbnail_remote_path;

        error_log("Thumbnail Upload URL: $thumbnail_upload_url");

        // Read thumbnail file content
        $thumbnail_content = file_get_contents($temp_file);
        if ($thumbnail_content === false) {
            error_log('❌ Failed to read thumbnail file content');
            unlink($temp_file); // Clean up temp file
            return '';
        }

        error_log("Thumbnail file content read: " . strlen($thumbnail_content) . " bytes");

        // Upload thumbnail using HTTP PUT method
        $thumbnail_response = wp_remote_request($thumbnail_upload_url, array(
            'method' => 'PUT',
            'headers' => array(
                'AccessKey' => $storage_api_key,
                'Content-Type' => 'image/jpeg'
            ),
            'body' => $thumbnail_content,
            'timeout' => 60
        ));

        // Clean up temporary file
        unlink($temp_file);

        if (is_wp_error($thumbnail_response)) {
            error_log('❌ BunnyCDN thumbnail upload error: ' . $thumbnail_response->get_error_message());
            return '';
        }

        $thumbnail_status_code = wp_remote_retrieve_response_code($thumbnail_response);
        $thumbnail_response_body = wp_remote_retrieve_body($thumbnail_response);

        error_log("Thumbnail HTTP Status Code: $thumbnail_status_code");
        error_log("Thumbnail Response Body: " . substr($thumbnail_response_body, 0, 200));

        if ($thumbnail_status_code !== 201) {
            error_log('❌ BunnyCDN thumbnail upload failed with status: ' . $thumbnail_status_code);
            return '';
        }

        error_log('✅ Thumbnail uploaded successfully to BunnyCDN Storage');

        // Return thumbnail CDN URL
        $cdn_url = !empty($serve_url) ? $serve_url : $storage_url;
        $thumbnail_url = 'https://' . $cdn_url . '/' . $thumbnail_remote_path;

        error_log("Thumbnail CDN URL: $thumbnail_url");
        error_log("=== THUMBNAIL GENERATION COMPLETE ===");

        return $thumbnail_url;
    }

    /**
     * Get BunnyCDN thumbnail URL for an image
     */
    private function get_bunnycdn_thumbnail_url($bunnycdn_url)
    {
        if (empty($bunnycdn_url)) {
            return '';
        }

        // Convert original URL to thumbnail URL
        // Original: https://cdn.example.com/episodes/galleries/123/image.jpg
        // Thumbnail: https://cdn.example.com/episodes/galleries/123/thumbs/thumb_image.jpg

        $url_parts = parse_url($bunnycdn_url);
        if (!$url_parts || !isset($url_parts['path'])) {
            return '';
        }

        $path = $url_parts['path'];
        $path_parts = explode('/', $path);

        // Find the filename and replace with thumbnail
        $filename = end($path_parts);
        $thumbnail_filename = 'thumb_' . $filename;

        // Replace the filename with thumbnail path
        $path_parts[count($path_parts) - 1] = 'thumbs';
        $path_parts[] = $thumbnail_filename;

        $thumbnail_path = implode('/', $path_parts);

        return $url_parts['scheme'] . '://' . $url_parts['host'] . $thumbnail_path;
    }


    /**
     * Generate token-based URL for BunnyCDN image
     */
    public static function generate_bunnycdn_token_url($file_path, $expires_in_hours = 24)
    {
        // Get BunnyCDN settings
        $video_settings = get_option('flexpress_video_settings', array());
        $token_key = isset($video_settings['bunnycdn_storage_token_key']) ? $video_settings['bunnycdn_storage_token_key'] : '';
        $serve_url = isset($video_settings['bunnycdn_serve_url']) ? $video_settings['bunnycdn_serve_url'] : '';

        if (empty($token_key) || empty($serve_url)) {
            // Return the original URL if token auth not configured
            return $file_path;
        }

        // Extract path from URL if it's a full URL
        if (strpos($file_path, 'https://') === 0) {
            // Remove the base URL to get just the path
            $base_url = 'https://' . $serve_url;
            if (strpos($file_path, $base_url) === 0) {
                $path = substr($file_path, strlen($base_url));
            } else {
                // If it's a different URL, extract the path after the domain
                $parsed = parse_url($file_path);
                $path = isset($parsed['path']) ? $parsed['path'] : $file_path;
            }
        } else {
            // It's already just a path
            $path = $file_path;
        }

        // Calculate expiration timestamp
        $expires_timestamp = time() + ($expires_in_hours * 3600);

        // Create the string to sign: TOKEN + PATH + EXPIRATION
        $string_to_sign = $token_key . $path . $expires_timestamp;

        // Generate SHA256 hash
        $hash_object = hash('sha256', $string_to_sign, true);

        // Base64 encode
        $base64_token = base64_encode($hash_object);

        // Replace characters for BunnyCDN compatibility
        $token = str_replace(array('+', '/', '='), array('-', '_', ''), $base64_token);

        // Construct the authenticated URL
        return 'https://' . $serve_url . $path . '?token=' . $token . '&expires=' . $expires_timestamp;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        global $post_type;

        // Debug logging
        error_log("FlexPress Gallery: Hook = $hook, Post Type = $post_type");

        if (($hook === 'post.php' || $hook === 'post-new.php') && in_array($post_type, array('episode', 'extras'))) {
            error_log("FlexPress Gallery: Enqueuing scripts for {$post_type} page");
            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');

            wp_enqueue_script(
                'flexpress-gallery-admin',
                get_template_directory_uri() . '/assets/js/gallery-admin.js',
                array('jquery', 'jquery-ui-sortable'),
                FLEXPRESS_VERSION,
                true
            );

            wp_localize_script('flexpress-gallery-admin', 'flexpressGallery', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'postType' => $post_type,
                'nonce' => wp_create_nonce('flexpress_gallery_upload'),
                'deleteNonce' => wp_create_nonce('flexpress_gallery_delete'),
                'deleteAllNonce' => wp_create_nonce('flexpress_gallery_delete_all'),
                'reorderNonce' => wp_create_nonce('flexpress_gallery_reorder'),
                'postId' => isset($post->ID) ? $post->ID : get_the_ID(),
                'strings' => array(
                    'uploading' => __('Uploading...', 'flexpress'),
                    'uploadComplete' => __('Upload complete!', 'flexpress'),
                    'uploadError' => __('Upload failed!', 'flexpress'),
                    'deleteConfirm' => __('Are you sure you want to delete this image?', 'flexpress'),
                    'deleteAllConfirm' => __('Are you sure you want to delete ALL gallery images? This action cannot be undone.', 'flexpress'),
                    'reorderComplete' => __('Gallery reordered successfully!', 'flexpress'),
                    'deleteAllComplete' => __('All gallery images deleted successfully!', 'flexpress')
                )
            ));
        }
    }
}

// Initialize gallery system
new FlexPress_Gallery_System();

/**
 * Get episode gallery images
 */
function flexpress_get_episode_gallery($post_id = null)
{
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    return get_post_meta($post_id, '_episode_gallery_images', true);
}

/**
 * Check if episode has gallery images
 */
function flexpress_has_episode_gallery($post_id = null)
{
    $gallery_images = flexpress_get_episode_gallery($post_id);
    return !empty($gallery_images) && is_array($gallery_images);
}

/**
 * Display episode gallery
 */
function flexpress_display_episode_gallery($post_id = null, $columns = null, $has_access = null)
{
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $gallery_images = flexpress_get_episode_gallery($post_id);
    if (empty($gallery_images)) {
        return;
    }

    // Check access if not provided
    if ($has_access === null) {
        $access_info = function_exists('flexpress_check_episode_access') ? flexpress_check_episode_access($post_id) : array('has_access' => true);
        $has_access = $access_info['has_access'];
    } else {
        // If has_access was provided, we still need access_info for the 5th image logic
        $access_info = function_exists('flexpress_check_episode_access') ? flexpress_check_episode_access($post_id) : array('has_access' => $has_access);
    }

    // Determine if we should show preview mode (only first 5 images)
    $preview_mode = !$has_access && count($gallery_images) > 5;
    $display_images = $preview_mode ? array_slice($gallery_images, 0, 5) : $gallery_images;
    $remaining_count = $preview_mode ? count($gallery_images) - 5 : 0;

    // Get gallery settings
    if ($columns === null) {
        $columns = get_post_meta($post_id, '_gallery_columns', true) ?: 5;
    }

    $lightbox = get_post_meta($post_id, '_gallery_lightbox', true);
    $autoplay = get_post_meta($post_id, '_gallery_autoplay', true);

    ?>
    <div class="episode-gallery" data-columns="<?php echo esc_attr($columns); ?>"
        data-lightbox="<?php echo $lightbox ? 'true' : 'false'; ?>"
        data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>">
        <div class="gallery-grid" style="grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);">
            <?php foreach ($display_images as $index => $image) : ?>
                <?php
                // Use BunnyCDN thumbnail URL if available, otherwise fallback to WordPress URLs
                $thumbnail_url = !empty($image['bunnycdn_thumbnail_url']) ?
                    FlexPress_Gallery_System::generate_bunnycdn_token_url($image['bunnycdn_thumbnail_url'], 24) : (!empty($image['bunnycdn_url']) ?
                        FlexPress_Gallery_System::generate_bunnycdn_token_url($image['bunnycdn_url'], 24) :
                        $image['thumbnail']);
                $large_url = !empty($image['bunnycdn_url']) ?
                    FlexPress_Gallery_System::generate_bunnycdn_token_url($image['bunnycdn_url'], 24) :
                    $image['large'];

                // Check if this is the 5th image in preview mode
                $is_last_preview = $preview_mode && $index === 4;
                ?>
                <div class="gallery-item" data-index="<?php echo $index; ?>">
                    <?php if ($has_access || !$is_last_preview): ?>
                        <a href="<?php echo esc_url($large_url); ?>"
                            class="gallery-link"
                            data-lightbox="episode-gallery-<?php echo $post_id; ?>"
                            data-title="<?php echo esc_attr($image['caption']); ?>">
                            <img src="<?php echo esc_url($thumbnail_url); ?>"
                                alt="<?php echo esc_attr($image['alt']); ?>"
                                loading="lazy">
                            <?php if (!empty($image['caption'])) : ?>
                                <div class="gallery-caption">
                                    <?php echo esc_html($image['caption']); ?>
                                </div>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <!-- 5th image with remaining count overlay - clickable with proper login/unlock logic -->
                        <?php
                        // Copy the exact logic from the unlock button in single-episode.php
                        if (is_user_logged_in()) {
                            // User is logged in - show purchase button (same as unlock button)
                            $cta_url = '#';
                            $cta_text = __('Click to unlock', 'flexpress');
                            $cta_class = 'gallery-preview-purchase';
                        } else {
                            // User is not logged in - redirect to login (same as unlock button)
                            $cta_url = home_url('/login?redirect_to=' . urlencode(get_permalink($post_id)));
                            $cta_text = __('Login to unlock', 'flexpress');
                            $cta_class = '';
                        }
                        ?>
                        <a href="<?php echo esc_url($cta_url); ?>"
                            class="gallery-preview-last gallery-preview-cta <?php echo $cta_class; ?>"
                            <?php if ($cta_class === 'gallery-preview-purchase'): ?>
                            data-episode-id="<?php echo $post_id; ?>"
                            data-price="<?php echo isset($access_info['final_price']) ? esc_attr($access_info['final_price']) : ''; ?>"
                            data-original-price="<?php echo isset($access_info['price']) ? esc_attr($access_info['price']) : ''; ?>"
                            data-discount="<?php echo isset($access_info['discount']) ? esc_attr($access_info['discount']) : ''; ?>"
                            data-access-type="<?php echo isset($access_info['access_type']) ? esc_attr($access_info['access_type']) : ''; ?>"
                            data-is-active-member="<?php echo isset($access_info['is_member']) && $access_info['is_member'] ? 'true' : 'false'; ?>"
                            <?php endif; ?>>
                            <img src="<?php echo esc_url($thumbnail_url); ?>"
                                alt="<?php echo esc_attr($image['alt']); ?>"
                                loading="lazy">
                            <div class="gallery-preview-overlay">
                                <div class="preview-overlay-content">
                                    <div class="remaining-count">
                                        +<?php echo $remaining_count; ?>
                                    </div>
                                    <div class="cta-hint">
                                        <?php echo esc_html($cta_text); ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php
}

/**
 * Get token-based URL for gallery image
 */
function flexpress_get_gallery_image_url($image_data, $size = 'thumbnail', $expires_hours = 24)
{
    if (empty($image_data)) {
        return '';
    }

    // Use BunnyCDN URL with token if available, otherwise fallback to WordPress URLs
    if (!empty($image_data['bunnycdn_url'])) {
        return FlexPress_Gallery_System::generate_bunnycdn_token_url($image_data['bunnycdn_url'], $expires_hours);
    }

    // Fallback to WordPress URLs
    $size_key = $size === 'thumbnail' ? 'thumbnail' : ($size === 'medium' ? 'medium' : ($size === 'large' ? 'large' : 'full'));

    return isset($image_data[$size_key]) ? $image_data[$size_key] : '';
}

// Function flexpress_has_extras_gallery() is already defined in functions.php

// Function flexpress_display_extras_gallery() is already defined in functions.php
