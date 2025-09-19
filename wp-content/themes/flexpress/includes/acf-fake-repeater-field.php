<?php
/**
 * Custom ACF Field Types for Cheeky Repeater Mod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('acf_field_flexpress_faq_repeater') && class_exists('acf_field')):

class acf_field_flexpress_faq_repeater extends acf_field {
    
    function __construct() {
        $this->name = 'flexpress_faq_repeater';
        $this->label = 'FAQ Repeater';
        $this->category = 'layout';
        $this->defaults = array(
            'sub_fields' => array(),
            'min' => 0,
            'max' => 0,
            'layout' => 'table',
            'button_label' => 'Add FAQ Item',
        );
        
        parent::__construct();
    }
    
    function render_field($field) {
        $value = $field['value'];
        if (!$value) {
            $value = array();
        }
        
        echo '<div class="flexpress-repeater" data-field-name="' . $field['name'] . '">';
        echo '<div class="repeater-items">';
        
        foreach ($value as $index => $item) {
            $this->render_item($field, $item, $index);
        }
        
        echo '</div>';
        echo '<button type="button" class="button add-repeater-item">' . $field['button_label'] . '</button>';
        echo '</div>';
    }
    
    function render_item($field, $item, $index) {
        $item = wp_parse_args($item, array(
            'question' => '',
            'answer' => '',
            'expanded' => false
        ));
        
        echo '<div class="repeater-item" data-index="' . $index . '">';
        echo '<div class="repeater-item-header">';
        echo '<span class="repeater-item-title">FAQ Item ' . ($index + 1) . '</span>';
        echo '<button type="button" class="button remove-repeater-item">Remove</button>';
        echo '</div>';
        echo '<div class="repeater-item-content">';
        
        echo '<p>';
        echo '<label>Question:</label><br>';
        echo '<input type="text" name="' . $field['name'] . '[' . $index . '][question]" value="' . esc_attr($item['question']) . '" class="widefat">';
        echo '</p>';
        
        echo '<p>';
        echo '<label>Answer:</label><br>';
        echo '<textarea name="' . $field['name'] . '[' . $index . '][answer]" class="widefat" rows="4">' . esc_textarea($item['answer']) . '</textarea>';
        echo '</p>';
        
        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="' . $field['name'] . '[' . $index . '][expanded]" value="1"' . checked($item['expanded'], true, false) . '>';
        echo 'Expanded by default';
        echo '</label>';
        echo '</p>';
        
        echo '</div>';
        echo '</div>';
    }
    
    function load_value($value, $post_id, $field) {
        return get_post_meta($post_id, $field['name'], true) ?: array();
    }
    
    function update_value($value, $post_id, $field) {
        update_post_meta($post_id, $field['name'], $value);
        return $value;
    }
}

// Initialize and register immediately
if (function_exists('acf_register_field_type')) {
    acf_register_field_type('acf_field_flexpress_faq_repeater');
}

endif;

if (!class_exists('acf_field_flexpress_requirements_repeater') && class_exists('acf_field')):

class acf_field_flexpress_requirements_repeater extends acf_field {
    
    function __construct() {
        $this->name = 'flexpress_requirements_repeater';
        $this->label = 'Requirements Repeater';
        $this->category = 'layout';
        $this->defaults = array(
            'sub_fields' => array(),
            'min' => 0,
            'max' => 0,
            'layout' => 'table',
            'button_label' => 'Add Requirement Card',
        );
        
        parent::__construct();
    }
    
    function render_field($field) {
        $value = $field['value'];
        if (!$value) {
            $value = array();
        }
        
        echo '<div class="flexpress-repeater" data-field-name="' . $field['name'] . '">';
        echo '<div class="repeater-items">';
        
        foreach ($value as $index => $item) {
            $this->render_item($field, $item, $index);
        }
        
        echo '</div>';
        echo '<button type="button" class="button add-repeater-item">' . $field['button_label'] . '</button>';
        echo '</div>';
    }
    
    function render_item($field, $item, $index) {
        $item = wp_parse_args($item, array(
            'icon_class' => '',
            'title' => '',
            'requirements' => array()
        ));
        
        echo '<div class="repeater-item" data-index="' . $index . '">';
        echo '<div class="repeater-item-header">';
        echo '<span class="repeater-item-title">Requirement Card ' . ($index + 1) . '</span>';
        echo '<button type="button" class="button remove-repeater-item">Remove</button>';
        echo '</div>';
        echo '<div class="repeater-item-content">';
        
        echo '<p>';
        echo '<label>Icon Class:</label><br>';
        echo '<input type="text" name="' . $field['name'] . '[' . $index . '][icon_class]" value="' . esc_attr($item['icon_class']) . '" class="widefat" placeholder="fas fa-star">';
        echo '</p>';
        
        echo '<p>';
        echo '<label>Title:</label><br>';
        echo '<input type="text" name="' . $field['name'] . '[' . $index . '][title]" value="' . esc_attr($item['title']) . '" class="widefat">';
        echo '</p>';
        
        echo '<p>';
        echo '<label>Requirements (one per line):</label><br>';
        echo '<textarea name="' . $field['name'] . '[' . $index . '][requirements]" class="widefat" rows="4" placeholder="Must be 18+ years old&#10;Valid government ID&#10;Right to work in Australia">' . esc_textarea(implode("\n", $item['requirements'])) . '</textarea>';
        echo '</p>';
        
        echo '</div>';
        echo '</div>';
    }
    
    function load_value($value, $post_id, $field) {
        return get_post_meta($post_id, $field['name'], true) ?: array();
    }
    
    function update_value($value, $post_id, $field) {
        // Convert requirements textarea to array
        if (is_array($value)) {
            foreach ($value as &$item) {
                if (isset($item['requirements'])) {
                    if (is_string($item['requirements'])) {
                        $item['requirements'] = array_filter(array_map('trim', explode("\n", $item['requirements'])));
                    }
                    // If it's already an array, leave it as is
                }
            }
        }
        update_post_meta($post_id, $field['name'], $value);
        return $value;
    }
}

// Initialize and register immediately
if (function_exists('acf_register_field_type')) {
    acf_register_field_type('acf_field_flexpress_requirements_repeater');
}

endif;
?>
