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
class FlexPress_Gallery_System {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init_gallery_system'));
        add_action('add_meta_boxes', array($this, 'add_gallery_meta_box'));
        add_action('save_post', array($this, 'save_gallery_data'));
        add_action('wp_ajax_flexpress_upload_gallery_image', array($this, 'ajax_upload_gallery_image'));
        add_action('wp_ajax_flexpress_delete_gallery_image', array($this, 'ajax_delete_gallery_image'));
        add_action('wp_ajax_flexpress_reorder_gallery_images', array($this, 'ajax_reorder_gallery_images'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Initialize gallery system
     */
    public function init_gallery_system() {
        // Add gallery support to episodes
        add_post_type_support('episode', 'gallery');
        
        // Register gallery image sizes
        $this->register_gallery_image_sizes();
    }
    
    /**
     * Register gallery image sizes
     */
    private function register_gallery_image_sizes() {
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
     * Add gallery meta box to episodes
     */
    public function add_gallery_meta_box() {
        add_meta_box(
            'episode_gallery',
            __('Episode Gallery', 'flexpress'),
            array($this, 'render_gallery_meta_box'),
            'episode',
            'normal',
            'high'
        );
    }
    
    /**
     * Render gallery meta box
     */
    public function render_gallery_meta_box($post) {
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
                <h4><?php _e('Gallery Images', 'flexpress'); ?></h4>
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
                                <option value="2" <?php selected(get_post_meta($post->ID, '_gallery_columns', true), '2'); ?>><?php _e('2 Columns', 'flexpress'); ?></option>
                                <option value="3" <?php selected(get_post_meta($post->ID, '_gallery_columns', true), '3'); ?>><?php _e('3 Columns', 'flexpress'); ?></option>
                                <option value="4" <?php selected(get_post_meta($post->ID, '_gallery_columns', true), '4'); ?>><?php _e('4 Columns', 'flexpress'); ?></option>
                                <option value="5" <?php selected(get_post_meta($post->ID, '_gallery_columns', true), '5'); ?>><?php _e('5 Columns', 'flexpress'); ?></option>
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
    public function save_gallery_data($post_id) {
        // Check nonce
        if (!isset($_POST['episode_gallery_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['episode_gallery_meta_box_nonce'], 'episode_gallery_meta_box')) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save gallery settings
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
    }
    
    /**
     * AJAX upload gallery image
     */
    public function ajax_upload_gallery_image() {
        // Check nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'flexpress_gallery_upload') || 
            !current_user_can('upload_files')) {
            wp_die('Unauthorized');
        }
        
        $post_id = intval($_POST['post_id']);
        if (!$post_id || get_post_type($post_id) !== 'episode') {
            wp_die('Invalid post ID');
        }
        
        // Handle file upload
        if (!isset($_FILES['image'])) {
            wp_die('No file uploaded');
        }
        
        $file = $_FILES['image'];
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_die($upload['error']);
        }
        
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
            wp_die('Failed to create attachment');
        }
        
        // Generate image sizes
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        // Upload to BunnyCDN if configured
        $bunnycdn_url = $this->upload_to_bunnycdn($attachment_id, $upload['file']);
        
        // Get image URLs
        $thumbnail_url = wp_get_attachment_image_url($attachment_id, 'gallery-thumbnail');
        $medium_url = wp_get_attachment_image_url($attachment_id, 'gallery-medium');
        $large_url = wp_get_attachment_image_url($attachment_id, 'gallery-large');
        $full_url = wp_get_attachment_image_url($attachment_id, 'full');
        
        // Add to gallery
        $gallery_images = get_post_meta($post_id, '_episode_gallery_images', true);
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
            'bunnycdn_url' => $bunnycdn_url,
            'upload_date' => current_time('mysql')
        );
        
        $gallery_images[] = $new_image;
        update_post_meta($post_id, '_episode_gallery_images', $gallery_images);
        
        wp_send_json_success(array(
            'message' => 'Image uploaded successfully',
            'image' => $new_image
        ));
    }
    
    /**
     * AJAX delete gallery image
     */
    public function ajax_delete_gallery_image() {
        // Check nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'flexpress_gallery_delete') || 
            !current_user_can('delete_posts')) {
            wp_die('Unauthorized');
        }
        
        $post_id = intval($_POST['post_id']);
        $image_id = intval($_POST['image_id']);
        
        if (!$post_id || !$image_id) {
            wp_die('Invalid IDs');
        }
        
        // Remove from gallery
        $gallery_images = get_post_meta($post_id, '_episode_gallery_images', true);
        if (is_array($gallery_images)) {
            $gallery_images = array_filter($gallery_images, function($img) use ($image_id) {
                return $img['id'] != $image_id;
            });
            update_post_meta($post_id, '_episode_gallery_images', $gallery_images);
        }
        
        // Delete attachment
        wp_delete_attachment($image_id, true);
        
        wp_send_json_success('Image deleted successfully');
    }
    
    /**
     * AJAX reorder gallery images
     */
    public function ajax_reorder_gallery_images() {
        // Check nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'flexpress_gallery_reorder') || 
            !current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        
        $post_id = intval($_POST['post_id']);
        $image_order = array_map('intval', $_POST['image_order']);
        
        if (!$post_id || empty($image_order)) {
            wp_die('Invalid data');
        }
        
        // Reorder gallery
        $gallery_images = get_post_meta($post_id, '_episode_gallery_images', true);
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
            update_post_meta($post_id, '_episode_gallery_images', $reordered);
        }
        
        wp_send_json_success('Gallery reordered successfully');
    }
    
    /**
     * Upload image to BunnyCDN
     */
    private function upload_to_bunnycdn($attachment_id, $file_path) {
        // Get BunnyCDN settings
        $video_settings = get_option('flexpress_video_settings', array());
        $api_key = isset($video_settings['bunnycdn_api_key']) ? $video_settings['bunnycdn_api_key'] : '';
        $storage_zone = isset($video_settings['bunnycdn_storage_zone']) ? $video_settings['bunnycdn_storage_zone'] : '';
        $storage_url = isset($video_settings['bunnycdn_storage_url']) ? $video_settings['bunnycdn_storage_url'] : '';
        
        if (empty($api_key) || empty($storage_zone) || empty($storage_url)) {
            return ''; // BunnyCDN not configured for storage
        }
        
        // Generate unique filename
        $file_info = pathinfo($file_path);
        $filename = uniqid() . '_' . $file_info['basename'];
        $remote_path = 'galleries/' . $filename;
        
        // Upload to BunnyCDN Storage
        $upload_url = 'https://storage.bunnycdn.com/' . $storage_zone . '/' . $remote_path;
        
        $file_content = file_get_contents($file_path);
        if ($file_content === false) {
            return '';
        }
        
        $response = wp_remote_post($upload_url, array(
            'headers' => array(
                'AccessKey' => $api_key,
                'Content-Type' => mime_content_type($file_path)
            ),
            'body' => $file_content,
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return '';
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 201) {
            return '';
        }
        
        // Return CDN URL
        return 'https://' . $storage_url . '/' . $remote_path;
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($hook === 'post.php' && $post_type === 'episode') {
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
                'nonce' => wp_create_nonce('flexpress_gallery_upload'),
                'deleteNonce' => wp_create_nonce('flexpress_gallery_delete'),
                'reorderNonce' => wp_create_nonce('flexpress_gallery_reorder'),
                'postId' => get_the_ID(),
                'strings' => array(
                    'uploading' => __('Uploading...', 'flexpress'),
                    'uploadComplete' => __('Upload complete!', 'flexpress'),
                    'uploadError' => __('Upload failed!', 'flexpress'),
                    'deleteConfirm' => __('Are you sure you want to delete this image?', 'flexpress'),
                    'reorderComplete' => __('Gallery reordered successfully!', 'flexpress')
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
function flexpress_get_episode_gallery($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    return get_post_meta($post_id, '_episode_gallery_images', true);
}

/**
 * Display episode gallery
 */
function flexpress_display_episode_gallery($post_id = null, $columns = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $gallery_images = flexpress_get_episode_gallery($post_id);
    if (empty($gallery_images)) {
        return;
    }
    
    // Get gallery settings
    if ($columns === null) {
        $columns = get_post_meta($post_id, '_gallery_columns', true) ?: 3;
    }
    
    $lightbox = get_post_meta($post_id, '_gallery_lightbox', true);
    $autoplay = get_post_meta($post_id, '_gallery_autoplay', true);
    
    ?>
    <div class="episode-gallery" data-columns="<?php echo esc_attr($columns); ?>" 
         data-lightbox="<?php echo $lightbox ? 'true' : 'false'; ?>"
         data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>">
        <div class="gallery-grid" style="grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);">
            <?php foreach ($gallery_images as $index => $image) : ?>
                <div class="gallery-item" data-index="<?php echo $index; ?>">
                    <a href="<?php echo esc_url($image['large']); ?>" 
                       class="gallery-link" 
                       data-lightbox="episode-gallery-<?php echo $post_id; ?>"
                       data-title="<?php echo esc_attr($image['caption']); ?>">
                        <img src="<?php echo esc_url($image['thumbnail']); ?>" 
                             alt="<?php echo esc_attr($image['alt']); ?>"
                             loading="lazy">
                        <?php if (!empty($image['caption'])) : ?>
                            <div class="gallery-caption">
                                <?php echo esc_html($image['caption']); ?>
                            </div>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
