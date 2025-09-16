<?php
/**
 * Join Page Carousel Administration
 * 
 * Adds a metabox to the join page to manage carousel slides
 */

class FlexPress_Join_Carousel {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add the meta box to the join page template
     */
    public function add_meta_box() {
        global $post;
        
        // Only add to pages using the join page template
        if ($post && $post->post_type == 'page') {
            $template = get_post_meta($post->ID, '_wp_page_template', true);
            if ($template == 'page-templates/join.php') {
                add_meta_box(
                    'join_carousel_meta_box',
                    __('Join Page Carousel', 'flexpress'),
                    array($this, 'render_meta_box'),
                    'page',
                    'normal',
                    'high'
                );
            }
        }
    }

    /**
     * Render the meta box content
     */
    public function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('join_carousel_meta_box', 'join_carousel_meta_box_nonce');
        
        // Get saved carousel slides
        $carousel_slides = get_post_meta($post->ID, 'join_carousel_slides', true);
        
        if (empty($carousel_slides) || !is_array($carousel_slides)) {
            $carousel_slides = array(
                array(
                    'image' => '',
                    'heading' => '',
                    'alt' => ''
                )
            );
        }
        
        // Get carousel interval setting
        $carousel_interval = get_post_meta($post->ID, 'join_carousel_interval', true);
        if (empty($carousel_interval) || !is_numeric($carousel_interval)) {
            $carousel_interval = 5; // Default to 5 seconds
        }
        
        // Output form fields
        ?>
        <div id="carousel-slides-container">
            <p><strong><?php _e('Configure carousel slides for the join page', 'flexpress'); ?></strong></p>
            
            <div class="carousel-settings" style="background: #f9f9f9; padding: 15px; margin-bottom: 20px; border: 1px solid #ddd;">
                <h3><?php _e('Carousel Settings', 'flexpress'); ?></h3>
                <p>
                    <label for="carousel_interval">
                        <strong><?php _e('Seconds per slide', 'flexpress'); ?></strong>
                        <br>
                        <input type="number" id="carousel_interval" name="carousel_interval" 
                            value="<?php echo esc_attr($carousel_interval); ?>" 
                            min="1" max="20" step="1" style="width: 80px;">
                    </label>
                    <span class="description"><?php _e('How many seconds each slide displays before transitioning', 'flexpress'); ?></span>
                </p>
            </div>
            
            <p><button type="button" class="button" id="add-slide-btn"><?php _e('Add New Slide', 'flexpress'); ?></button></p>
            
            <div id="carousel-slides">
                <?php foreach ($carousel_slides as $index => $slide) : ?>
                <div class="carousel-slide" data-index="<?php echo $index; ?>">
                    <h4><?php printf(__('Slide %d', 'flexpress'), $index + 1); ?></h4>
                    
                    <div class="slide-preview" style="margin-bottom: 10px;">
                        <?php if (!empty($slide['image'])) : ?>
                            <img src="<?php echo esc_url($slide['image']); ?>" style="max-width: 200px; max-height: 100px; display: block; margin-bottom: 10px;" />
                        <?php endif; ?>
                    </div>
                    
                    <p>
                        <label>
                            <?php _e('Image URL', 'flexpress'); ?><br>
                            <input type="text" class="widefat slide-image" 
                                name="carousel_slides[<?php echo $index; ?>][image]" 
                                value="<?php echo esc_attr($slide['image']); ?>" />
                        </label>
                        <button type="button" class="button upload-image-btn"><?php _e('Select Image', 'flexpress'); ?></button>
                    </p>
                    
                    <p>
                        <label>
                            <?php _e('Heading', 'flexpress'); ?><br>
                            <input type="text" class="widefat" 
                                name="carousel_slides[<?php echo $index; ?>][heading]" 
                                value="<?php echo esc_attr($slide['heading']); ?>" 
                                placeholder="FULL LENGTH EPISODES" />
                        </label>
                    </p>
                    
                    <p>
                        <label>
                            <?php _e('Alt Text', 'flexpress'); ?><br>
                            <input type="text" class="widefat" 
                                name="carousel_slides[<?php echo $index; ?>][alt]" 
                                value="<?php echo esc_attr($slide['alt']); ?>" 
                                placeholder="Image description" />
                        </label>
                    </p>
                    
                    <p>
                        <button type="button" class="button remove-slide-btn"><?php _e('Remove Slide', 'flexpress'); ?></button>
                    </p>
                    <hr>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div id="slide-template" style="display: none;">
                <div class="carousel-slide">
                    <h4><?php _e('New Slide', 'flexpress'); ?></h4>
                    
                    <div class="slide-preview" style="margin-bottom: 10px;"></div>
                    
                    <p>
                        <label>
                            <?php _e('Image URL', 'flexpress'); ?><br>
                            <input type="text" class="widefat slide-image" 
                                name="carousel_slides[INDEX][image]" 
                                value="" />
                        </label>
                        <button type="button" class="button upload-image-btn"><?php _e('Select Image', 'flexpress'); ?></button>
                    </p>
                    
                    <p>
                        <label>
                            <?php _e('Heading', 'flexpress'); ?><br>
                            <input type="text" class="widefat" 
                                name="carousel_slides[INDEX][heading]" 
                                value="" 
                                placeholder="FULL LENGTH EPISODES" />
                        </label>
                    </p>
                    
                    <p>
                        <label>
                            <?php _e('Alt Text', 'flexpress'); ?><br>
                            <input type="text" class="widefat" 
                                name="carousel_slides[INDEX][alt]" 
                                value="" 
                                placeholder="Image description" />
                        </label>
                    </p>
                    
                    <p>
                        <button type="button" class="button remove-slide-btn"><?php _e('Remove Slide', 'flexpress'); ?></button>
                    </p>
                    <hr>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save meta box content
     */
    public function save_meta_box($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['join_carousel_meta_box_nonce'])) {
            return;
        }

        // Verify the nonce
        if (!wp_verify_nonce($_POST['join_carousel_meta_box_nonce'], 'join_carousel_meta_box')) {
            return;
        }

        // If this is an autosave, don't do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if ('page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        // Save carousel interval
        if (isset($_POST['carousel_interval'])) {
            $interval = intval($_POST['carousel_interval']);
            // Ensure interval is between 1 and 20 seconds
            $interval = max(1, min(20, $interval));
            update_post_meta($post_id, 'join_carousel_interval', $interval);
        }

        // Save carousel slides data
        if (isset($_POST['carousel_slides']) && is_array($_POST['carousel_slides'])) {
            $slides = array();
            
            foreach ($_POST['carousel_slides'] as $slide) {
                // Skip empty slides (fixes the extra blank slide issue)
                if (empty($slide['image']) && empty($slide['heading']) && empty($slide['alt'])) {
                    continue;
                }
                
                $slides[] = array(
                    'image' => sanitize_text_field($slide['image']),
                    'heading' => sanitize_text_field($slide['heading']),
                    'alt' => sanitize_text_field($slide['alt']),
                );
            }
            
            update_post_meta($post_id, 'join_carousel_slides', $slides);
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post;
        
        if ($hook == 'post.php' && $post && $post->post_type == 'page') {
            $template = get_post_meta($post->ID, '_wp_page_template', true);
            if ($template == 'page-templates/join.php') {
                wp_enqueue_media();
                wp_enqueue_script('join-carousel-admin', get_template_directory_uri() . '/assets/js/join-carousel-admin.js', array('jquery'), '1.0', true);
            }
        }
    }
}

// Initialize the class
new FlexPress_Join_Carousel(); 