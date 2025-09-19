<?php
/**
 * Cheeky ACF Repeater Mod for Free Version
 * Adds repeater-like functionality using regular fields and JavaScript
 */

// Custom field types disabled to prevent loading errors
// Using JSON textarea fields instead for better compatibility
/*
add_action('acf/init', 'flexpress_include_custom_field_types');

function flexpress_include_custom_field_types() {
    if (function_exists('acf_register_field_type')) {
        include_once FLEXPRESS_PATH . '/includes/acf-fake-repeater-field.php';
    }
}
*/

// Enqueue admin scripts for repeater functionality
add_action('admin_enqueue_scripts', 'flexpress_repeater_admin_scripts');

function flexpress_repeater_admin_scripts($hook) {
    if ($hook == 'post.php' || $hook == 'post-new.php') {
        wp_enqueue_script('flexpress-repeater-mod', FLEXPRESS_URL . '/assets/js/acf-repeater-mod.js', array('jquery'), FLEXPRESS_VERSION, true);
        wp_enqueue_style('flexpress-repeater-mod', FLEXPRESS_URL . '/assets/css/acf-repeater-mod.css', array(), FLEXPRESS_VERSION);
    }
}

// Custom field registration is now handled in the included file

function flexpress_faq_repeater_callback($post) {
    // Get existing FAQ data
    $faq_data = get_post_meta($post->ID, 'flexpress_faq_items', true);
    if (!$faq_data) {
        $faq_data = array();
    }
    
    wp_nonce_field('flexpress_faq_repeater', 'flexpress_faq_repeater_nonce');
    ?>
    <div class="flexpress-repeater" data-field-name="flexpress_faq_items">
        <div class="repeater-items">
            <?php foreach ($faq_data as $index => $item): ?>
            <div class="repeater-item" data-index="<?php echo $index; ?>">
                <div class="repeater-item-header">
                    <span class="repeater-item-title">FAQ Item <?php echo $index + 1; ?></span>
                    <button type="button" class="button remove-repeater-item">Remove</button>
                </div>
                <div class="repeater-item-content">
                    <p>
                        <label>Question:</label><br>
                        <input type="text" name="flexpress_faq_items[<?php echo $index; ?>][question]" value="<?php echo esc_attr($item['question']); ?>" class="widefat">
                    </p>
                    <p>
                        <label>Answer:</label><br>
                        <textarea name="flexpress_faq_items[<?php echo $index; ?>][answer]" class="widefat" rows="4"><?php echo esc_textarea($item['answer']); ?></textarea>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" name="flexpress_faq_items[<?php echo $index; ?>][expanded]" value="1" <?php checked($item['expanded'], 1); ?>>
                            Expanded by default
                        </label>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button add-repeater-item">Add FAQ Item</button>
    </div>
    <?php
}

function flexpress_requirements_repeater_callback($post) {
    // Get existing requirements data
    $req_data = get_post_meta($post->ID, 'flexpress_requirements_items', true);
    if (!$req_data) {
        $req_data = array();
    }
    
    wp_nonce_field('flexpress_requirements_repeater', 'flexpress_requirements_repeater_nonce');
    ?>
    <div class="flexpress-repeater" data-field-name="flexpress_requirements_items">
        <div class="repeater-items">
            <?php foreach ($req_data as $index => $item): ?>
            <div class="repeater-item" data-index="<?php echo $index; ?>">
                <div class="repeater-item-header">
                    <span class="repeater-item-title">Requirement Card <?php echo $index + 1; ?></span>
                    <button type="button" class="button remove-repeater-item">Remove</button>
                </div>
                <div class="repeater-item-content">
                    <p>
                        <label>Icon Class:</label><br>
                        <input type="text" name="flexpress_requirements_items[<?php echo $index; ?>][icon_class]" value="<?php echo esc_attr($item['icon_class']); ?>" class="widefat" placeholder="fas fa-star">
                    </p>
                    <p>
                        <label>Title:</label><br>
                        <input type="text" name="flexpress_requirements_items[<?php echo $index; ?>][title]" value="<?php echo esc_attr($item['title']); ?>" class="widefat">
                    </p>
                    <p>
                        <label>Requirements (one per line):</label><br>
                        <textarea name="flexpress_requirements_items[<?php echo $index; ?>][requirements]" class="widefat" rows="4" placeholder="Must be 18+ years old&#10;Valid government ID&#10;Right to work in Australia"><?php echo esc_textarea(implode("\n", $item['requirements'])); ?></textarea>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button add-repeater-item">Add Requirement Card</button>
    </div>
    <?php
}

// Save repeater data
add_action('save_post', 'flexpress_save_repeater_data');

function flexpress_save_repeater_data($post_id) {
    // Save FAQ items
    if (isset($_POST['flexpress_faq_repeater_nonce']) && wp_verify_nonce($_POST['flexpress_faq_repeater_nonce'], 'flexpress_faq_repeater')) {
        if (isset($_POST['flexpress_faq_items'])) {
            update_post_meta($post_id, 'flexpress_faq_items', $_POST['flexpress_faq_items']);
        }
    }
    
    // Save requirements items
    if (isset($_POST['flexpress_requirements_repeater_nonce']) && wp_verify_nonce($_POST['flexpress_requirements_repeater_nonce'], 'flexpress_requirements_repeater')) {
        if (isset($_POST['flexpress_requirements_items'])) {
            // Convert requirements textarea to array
            $req_data = $_POST['flexpress_requirements_items'];
            foreach ($req_data as &$item) {
                if (isset($item['requirements'])) {
                    $item['requirements'] = array_filter(array_map('trim', explode("\n", $item['requirements'])));
                }
            }
            update_post_meta($post_id, 'flexpress_requirements_items', $req_data);
        }
    }
}

// Helper function to get FAQ items
function flexpress_get_faq_items($post_id = null) {
    if (!$post_id) {
        global $post;
        $post_id = $post->ID;
    }
    return get_post_meta($post_id, 'flexpress_faq_items', true) ?: array();
}

// Helper function to get requirements items
function flexpress_get_requirements_items($post_id = null) {
    if (!$post_id) {
        global $post;
        $post_id = $post->ID;
    }
    return get_post_meta($post_id, 'flexpress_requirements_items', true) ?: array();
}
?>
