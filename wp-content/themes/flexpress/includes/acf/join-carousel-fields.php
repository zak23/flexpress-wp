<?php
/**
 * ACF Field Group: Join Page Carousel
 * 
 * This file is disabled in favor of the custom metabox implementation in class-flexpress-join-carousel.php
 */

// ACF implementation commented out to avoid duplicate fields
/*
if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group(array(
        'key' => 'group_join_carousel',
        'title' => 'Join Page Carousel',
        'fields' => array(
            array(
                'key' => 'field_join_carousel',
                'label' => 'Carousel Slides',
                'name' => 'join_carousel',
                'type' => 'repeater',
                'instructions' => 'Add slides to the carousel on the join page',
                'required' => 0,
                'min' => 0,
                'max' => 0,
                'layout' => 'block',
                'button_label' => 'Add Slide',
                'sub_fields' => array(
                    array(
                        'key' => 'field_carousel_image',
                        'label' => 'Slide Image',
                        'name' => 'carousel_image',
                        'type' => 'image',
                        'instructions' => 'Choose an image for this slide (recommended size: 1200x400px)',
                        'required' => 1,
                        'return_format' => 'array',
                        'preview_size' => 'medium',
                        'library' => 'all',
                    ),
                    array(
                        'key' => 'field_carousel_heading',
                        'label' => 'Heading',
                        'name' => 'carousel_heading',
                        'type' => 'text',
                        'instructions' => 'Enter a heading to display on this slide',
                        'required' => 0,
                        'maxlength' => '',
                        'placeholder' => 'FULL LENGTH EPISODES',
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/join.php',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ));
}
*/ 