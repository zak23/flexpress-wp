<?php

/**
 * Advanced Custom Fields Configuration
 */

if (function_exists('acf_add_local_field_group')):

    acf_add_local_field_group(array(
        'key' => 'group_episode_videos',
        'title' => 'Episode Videos',
        'fields' => array(
            // Videos Tab
            array(
                'key' => 'field_episode_tab_videos',
                'label' => 'Videos',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_preview_video',
                'label' => 'Preview Video',
                'name' => 'preview_video',
                'type' => 'text',
                'instructions' => 'Enter the BunnyCDN Stream video ID for the preview (15-30 seconds)',
                'required' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_trailer_video',
                'label' => 'Trailer Video',
                'name' => 'trailer_video',
                'type' => 'text',
                'instructions' => 'Enter the BunnyCDN Stream video ID for the trailer (60-120 seconds)',
                'required' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_full_video',
                'label' => 'Full Video',
                'name' => 'full_video',
                'type' => 'text',
                'instructions' => 'Enter the BunnyCDN Stream video ID for the full episode',
                'required' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_episode_duration',
                'label' => 'Episode Duration',
                'name' => 'episode_duration',
                'type' => 'text',
                'instructions' => 'Duration in minutes (automatically retrieved from BunnyCDN if left empty)',
                'required' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
            // Access & Pricing Tab
            array(
                'key' => 'field_episode_tab_access',
                'label' => 'Access & Pricing',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_access_type',
                'label' => 'Access Type',
                'name' => 'access_type',
                'type' => 'select',
                'instructions' => 'Choose how users can access this episode',
                'required' => 1,
                'default_value' => 'membership',
                'choices' => array(
                    'free' => 'Free for Everyone',
                    'membership_only' => 'Membership Only (No PPV Option)',
                    'ppv_only' => 'Pay-Per-View Only (No Membership Access)',
                    'membership' => 'Membership Access + PPV Option',
                    'mixed' => 'Members Get Discount + PPV for Non-Members',
                ),
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'return_format' => 'value',
                'wrapper' => array(
                    'width' => '',
                    'class' => 'access-type-field',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_episode_price',
                'label' => 'Default PPV Price',
                'name' => 'episode_price',
                'type' => 'select',
                'instructions' => 'Choose the default price for non-members (only applies to episodes with PPV option)',
                'required' => 0,
                'default_value' => '29.95',
                'choices' => array(
                    '29.95' => '$29.95',
                    '39.95' => '$39.95',
                    '49.95' => '$49.95',
                ),
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'return_format' => 'value',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_access_type',
                            'operator' => '!=',
                            'value' => 'membership_only',
                        ),
                    ),
                ),
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_member_discount',
                'label' => 'Member Discount (%)',
                'name' => 'member_discount',
                'type' => 'number',
                'instructions' => 'Discount percentage for active members (only applies to "mixed" access type)',
                'required' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_access_type',
                            'operator' => '==',
                            'value' => 'mixed',
                        ),
                    ),
                ),
                'min' => 0,
                'max' => 100,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
            // Scheduling Tab
            array(
                'key' => 'field_episode_tab_schedule',
                'label' => 'Scheduling',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_release_date',
                'label' => 'Release Date',
                'name' => 'release_date',
                'type' => 'date_time_picker',
                'instructions' => 'Select when this episode should be released',
                'required' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
            // Featured & Models Tab
            array(
                'key' => 'field_episode_tab_featured',
                'label' => 'Featured & Models',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_is_featured',
                'label' => 'Featured Episode',
                'name' => 'is_featured',
                'type' => 'true_false',
                'instructions' => 'Check this box to mark this episode as featured',
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_featured_models',
                'label' => 'Featured Models',
                'name' => 'featured_models',
                'type' => 'relationship',
                'instructions' => 'Select the models featured in this episode',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'post_type' => array(
                    0 => 'model',
                ),
                'taxonomy' => '',
                'filters' => array(
                    0 => 'search',
                ),
                'elements' => array(
                    0 => 'featured_image',
                ),
                'min' => '',
                'max' => '',
                'return_format' => 'object',
            ),
            // Visibility Tab
            array(
                'key' => 'field_episode_tab_visibility',
                'label' => 'Visibility',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_hidden_from_public',
                'label' => 'Hidden from Public',
                'name' => 'hidden_from_public',
                'type' => 'true_false',
                'instructions' => 'Check this box to hide this episode from non-logged-in users. Only registered users will be able to see previews and access this content.',
                'default_value' => 0,
                'ui' => 1,
                'ui_on_text' => 'Hidden',
                'ui_off_text' => 'Public',
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'episode',
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

    // Register Extras ACF fields
    acf_add_local_field_group(array(
        'key' => 'group_extras_content_new',
        'title' => 'Extras Content',
        'fields' => array(
            array(
                'key' => 'field_extras_gallery_tab',
                'label' => 'Content Type',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_extras_content_format',
                'label' => 'Content Format',
                'name' => 'content_format',
                'type' => 'select',
                'instructions' => 'Choose whether this extra contains gallery images or video content',
                'required' => 1,
                'default_value' => 'gallery',
                'choices' => array(
                    'gallery' => 'Gallery Images',
                    'video' => 'Video Content',
                ),
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'return_format' => 'value',
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
            // Gallery Images field removed - using custom meta box instead
            array(
                'key' => 'field_extras_preview_video',
                'label' => 'Preview Video',
                'name' => 'preview_video',
                'type' => 'text',
                'instructions' => 'Enter the BunnyCDN Stream video ID for the preview (15-30 seconds)',
                'required' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_extras_content_format',
                            'operator' => '==',
                            'value' => 'video',
                        ),
                    ),
                ),
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_extras_full_video',
                'label' => 'Full Video',
                'name' => 'full_video',
                'type' => 'text',
                'instructions' => 'Enter the BunnyCDN Stream video ID for the full extra content',
                'required' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_extras_content_format',
                            'operator' => '==',
                            'value' => 'video',
                        ),
                    ),
                ),
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_extras_duration',
                'label' => 'Video Duration',
                'name' => 'extras_duration',
                'type' => 'text',
                'instructions' => 'Duration in MM:SS format (automatically retrieved from BunnyCDN if left empty)',
                'required' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_extras_content_format',
                            'operator' => '==',
                            'value' => 'video',
                        ),
                    ),
                ),
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_extras_gallery_columns',
                'label' => 'Gallery Columns',
                'name' => 'gallery_columns',
                'type' => 'select',
                'instructions' => 'Number of columns to display in the gallery grid',
                'required' => 0,
                'choices' => array(
                    '2' => '2 Columns',
                    '3' => '3 Columns',
                    '4' => '4 Columns',
                    '5' => '5 Columns',
                ),
                'default_value' => '3',
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'return_format' => 'value',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_extras_content_format',
                            'operator' => '==',
                            'value' => 'gallery',
                        ),
                    ),
                ),
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_extras_gallery_lightbox',
                'label' => 'Enable Lightbox',
                'name' => 'gallery_lightbox',
                'type' => 'true_false',
                'instructions' => 'Enable lightbox viewer for gallery images',
                'required' => 0,
                'default_value' => 1,
                'ui' => 1,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_extras_content_format',
                            'operator' => '==',
                            'value' => 'gallery',
                        ),
                    ),
                ),
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_extras_access_tab',
                'label' => 'Access & Pricing',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_extras_access_type',
                'label' => 'Access Type',
                'name' => 'access_type',
                'type' => 'select',
                'instructions' => 'Choose how users can access this extra content',
                'required' => 1,
                'default_value' => 'membership',
                'choices' => array(
                    'free' => 'Free for Everyone',
                    'membership_only' => 'Membership Only (No PPV Option)',
                    'ppv_only' => 'Pay-Per-View Only (No Membership Access)',
                    'membership' => 'Membership Access + PPV Option',
                    'mixed' => 'Members Get Discount + PPV for Non-Members',
                ),
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'return_format' => 'value',
                'wrapper' => array(
                    'width' => '',
                    'class' => 'access-type-field',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_extras_price',
                'label' => 'Default PPV Price',
                'name' => 'extras_price',
                'type' => 'select',
                'instructions' => 'Choose the default price for non-members (only applies to extras with PPV option)',
                'required' => 0,
                'default_value' => '29.95',
                'choices' => array(
                    '29.95' => '$29.95',
                    '39.95' => '$39.95',
                    '49.95' => '$49.95',
                ),
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'return_format' => 'value',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_extras_access_type',
                            'operator' => '!=',
                            'value' => 'membership_only',
                        ),
                    ),
                ),
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_extras_member_discount',
                'label' => 'Member Discount (%)',
                'name' => 'member_discount',
                'type' => 'number',
                'instructions' => 'Discount percentage for active members (only applies to "mixed" access type)',
                'required' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_extras_access_type',
                            'operator' => '==',
                            'value' => 'mixed',
                        ),
                    ),
                ),
                'min' => 0,
                'max' => 100,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_extras_metadata_tab',
                'label' => 'Content Details',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_extras_content_type',
                'label' => 'Content Type',
                'name' => 'content_type',
                'type' => 'select',
                'instructions' => 'Select the type of extra content this is',
                'required' => 0,
                'default_value' => 'behind_scenes',
                'choices' => array(
                    'behind_scenes' => 'Behind the Scenes',
                    'bloopers' => 'Bloopers',
                    'interviews' => 'Interviews',
                    'photo_shoots' => 'Photo Shoots',
                    'making_of' => 'Making Of',
                    'deleted_scenes' => 'Deleted Scenes',
                    'extended_cuts' => 'Extended Cuts',
                    'other' => 'Other',
                ),
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'return_format' => 'value',
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_extras_release_date',
                'label' => 'Release Date',
                'name' => 'release_date',
                'type' => 'date_time_picker',
                'instructions' => 'Select when this extra content should be released',
                'required' => 1,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_extras_featured_models',
                'label' => 'Featured Models',
                'name' => 'featured_models',
                'type' => 'relationship',
                'instructions' => 'Select the models featured in this extra content',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'post_type' => array(
                    0 => 'model',
                ),
                'taxonomy' => '',
                'filters' => array(
                    0 => 'search',
                ),
                'elements' => array(
                    0 => 'featured_image',
                ),
                'min' => '',
                'max' => '',
                'return_format' => 'object',
            ),
            array(
                'key' => 'field_extras_is_featured',
                'label' => 'Featured Extra',
                'name' => 'is_featured',
                'type' => 'true_false',
                'instructions' => 'Check this box to mark this extra content as featured',
                'default_value' => 0,
                'ui' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'extras',
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

    acf_add_local_field_group(array(
        'key' => 'group_model_details',
        'title' => 'Model Information',
        'fields' => array(
            // Basic Information Tab
            array(
                'key' => 'field_model_basic_tab',
                'label' => 'Basic Information',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_model_about',
                'label' => 'About',
                'name' => 'model_about',
                'type' => 'textarea',
                'instructions' => 'Enter a detailed biography for this model',
                'required' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'rows' => 6,
            ),
            array(
                'key' => 'field_model_gender',
                'label' => 'Gender',
                'name' => 'model_gender',
                'type' => 'select',
                'instructions' => 'Select the model\'s gender',
                'required' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'choices' => array(
                    'female' => 'Female',
                    'male' => 'Male',
                    'trans' => 'Trans',
                    'non-binary' => 'Non-Binary',
                    'other' => 'Other',
                ),
                'default_value' => 'female',
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'ajax' => 0,
                'return_format' => 'value',
                'placeholder' => '',
            ),

            array(
                'key' => 'field_model_height',
                'label' => 'Height',
                'name' => 'model_height',
                'type' => 'text',
                'instructions' => 'Enter the model\'s height (e.g., 5\'8")',
                'required' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => '5\'8"',
            ),
            array(
                'key' => 'field_model_published_age',
                'label' => 'Published Age',
                'name' => 'model_published_age',
                'type' => 'number',
                'instructions' => 'Manual age to display; not calculated from DOB',
                'required' => 0,
                'min' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'e.g., 24',
            ),
            array(
                'key' => 'field_model_location',
                'label' => 'Location',
                'name' => 'model_location',
                'type' => 'text',
                'instructions' => 'City, State/Region, or Country',
                'required' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'e.g., Brisbane, AU',
            ),
            array(
                'key' => 'field_model_weight',
                'label' => 'Weight',
                'name' => 'model_weight',
                'type' => 'text',
                'instructions' => 'Enter the model\'s weight with units',
                'required' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'e.g., 55 kg',
            ),
            array(
                'key' => 'field_model_bra_size',
                'label' => 'Bra Size',
                'name' => 'model_bra_size',
                'type' => 'text',
                'instructions' => 'Enter the model\'s bra size',
                'required' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'e.g., 34C',
            ),
            array(
                'key' => 'field_model_bust',
                'label' => 'Bust',
                'name' => 'model_bust',
                'type' => 'number',
                'instructions' => 'Bust measurement',
                'required' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'e.g., 34',
            ),
            array(
                'key' => 'field_model_waist',
                'label' => 'Waist',
                'name' => 'model_waist',
                'type' => 'number',
                'instructions' => 'Waist measurement',
                'required' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'e.g., 26',
            ),
            array(
                'key' => 'field_model_hips',
                'label' => 'Hips',
                'name' => 'model_hips',
                'type' => 'number',
                'instructions' => 'Hips measurement',
                'required' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'e.g., 36',
            ),

            // Images Tab
            array(
                'key' => 'field_model_images_tab',
                'label' => 'Images',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_model_hero_image',
                'label' => 'Hero Landscape Image',
                'name' => 'model_hero_image',
                'type' => 'image',
                'instructions' => 'Upload a wide landscape image for the model hero section (recommended: 1920x600px)',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
            array(
                'key' => 'field_model_profile_image',
                'label' => 'Profile Image',
                'name' => 'model_profile_image',
                'type' => 'image',
                'instructions' => 'Upload a profile image for the half-half section. This will also automatically set as the featured image for the model post.',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ),

            // Social Media Tab
            array(
                'key' => 'field_model_social_tab',
                'label' => 'Social Media',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_model_instagram',
                'label' => 'Instagram',
                'name' => 'model_instagram',
                'type' => 'url',
                'instructions' => 'Enter the Instagram profile URL',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'https://instagram.com/username',
            ),
            array(
                'key' => 'field_model_twitter',
                'label' => 'Twitter/X',
                'name' => 'model_twitter',
                'type' => 'url',
                'instructions' => 'Enter the Twitter/X profile URL',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'https://twitter.com/username',
            ),
            array(
                'key' => 'field_model_tiktok',
                'label' => 'TikTok',
                'name' => 'model_tiktok',
                'type' => 'url',
                'instructions' => 'Enter the TikTok profile URL',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'https://tiktok.com/@username',
            ),
            array(
                'key' => 'field_model_onlyfans',
                'label' => 'OnlyFans',
                'name' => 'model_onlyfans',
                'type' => 'url',
                'instructions' => 'Enter the OnlyFans profile URL',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'https://onlyfans.com/username',
            ),
            array(
                'key' => 'field_model_website',
                'label' => 'Personal Website',
                'name' => 'model_website',
                'type' => 'url',
                'instructions' => 'Enter the personal website URL',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => 'https://example.com',
            ),
            array(
                'key' => 'field_model_website_title',
                'label' => 'Website Title',
                'name' => 'model_website_title',
                'type' => 'text',
                'instructions' => 'Enter a custom title for the website link (e.g., "Official Site", "Personal Blog")',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 'Website',
                'placeholder' => 'Website',
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_model_website',
                            'operator' => '!=',
                            'value' => '',
                        ),
                    ),
                ),
            ),

            // Display Settings Tab
            array(
                'key' => 'field_model_display_tab',
                'label' => 'Display Settings',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            array(
                'key' => 'field_model_hide_on_homepage',
                'label' => 'Hide on Homepage',
                'name' => 'model_hide_on_homepage',
                'type' => 'true_false',
                'instructions' => 'Hide this model from the homepage model sections',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 0,
                'ui' => 1,
                'ui_on_text' => 'Yes',
                'ui_off_text' => 'No',
            ),


            array(
                'key' => 'field_model_featured',
                'label' => 'Featured Model',
                'name' => 'model_featured',
                'type' => 'true_false',
                'instructions' => 'Mark this model as featured for special highlighting',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 0,
                'ui' => 1,
                'ui_on_text' => 'Yes',
                'ui_off_text' => 'No',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'model',
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

    acf_add_local_field_group(array(
        'key' => 'group_coming_soon',
        'title' => 'Coming Soon Settings',
        'fields' => array(
            array(
                'key' => 'field_showreel_video',
                'label' => 'Showreel Video',
                'name' => 'showreel_video',
                'type' => 'text',
                'instructions' => 'Enter the BunnyCDN Stream video ID for the showreel',
                'required' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/coming-soon.php',
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

endif;

/**
 * Legal Pages ACF Fields
 */
if (function_exists('acf_add_local_field_group')):

    acf_add_local_field_group(array(
        'key' => 'group_legal_pages',
        'title' => 'Legal Page Settings',
        'fields' => array(
            array(
                'key' => 'field_legal_contact_form_id',
                'label' => 'Contact Form ID',
                'name' => 'legal_contact_form_id',
                'type' => 'text',
                'instructions' => 'Enter the Contact Form 7 or WPForms shortcode ID to display a contact form on this legal page (optional)',
                'required' => 0,
                'placeholder' => 'e.g., 123 for [contact-form-7 id="123"]',
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_legal_contact_form_title',
                'label' => 'Contact Form Title',
                'name' => 'legal_contact_form_title',
                'type' => 'text',
                'instructions' => 'Enter a custom title for the contact form section (optional)',
                'required' => 0,
                'default_value' => 'Have Questions?',
                'placeholder' => 'Have Questions?',
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_legal_contact_form_id',
                            'operator' => '!=',
                            'value' => '',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_legal_show_last_updated',
                'label' => 'Show Last Updated Date',
                'name' => 'legal_show_last_updated',
                'type' => 'true_false',
                'instructions' => 'Show the last updated date at the bottom of the page',
                'required' => 0,
                'default_value' => 1,
                'ui' => 1,
                'ui_on_text' => 'Yes',
                'ui_off_text' => 'No',
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_legal_custom_last_updated',
                'label' => 'Custom Last Updated Date',
                'name' => 'legal_custom_last_updated',
                'type' => 'date_picker',
                'instructions' => 'Enter a custom last updated date (leave blank to use post modified date)',
                'required' => 0,
                'display_format' => 'F j, Y',
                'return_format' => 'Y-m-d',
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_legal_show_last_updated',
                            'operator' => '==',
                            'value' => '1',
                        ),
                    ),
                ),
            ),
            array(
                'key' => 'field_legal_additional_content',
                'label' => 'Additional Content',
                'name' => 'legal_additional_content',
                'type' => 'wysiwyg',
                'instructions' => 'Optional additional content to display after the main content but before the contact form',
                'required' => 0,
                'toolbar' => 'full',
                'media_upload' => 1,
                'delay' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/privacy.php',
                ),
            ),
            array(
                array(
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/terms.php',
                ),
            ),
            array(
                array(
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/2257-compliance.php',
                ),
            ),
            array(
                array(
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/content-removal.php',
                ),
            ),
            array(
                array(
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/anti-slavery.php',
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
        'description' => 'Configure legal page settings including contact forms and last updated dates',
        'show_in_rest' => 0,
    ));

    // Casting Page Custom Fields
    acf_add_local_field_group(array(
        'key' => 'group_casting_page',
        'title' => 'Casting Page Content',
        'fields' => array(
            // Header Section
            array(
                'key' => 'field_casting_header_title',
                'label' => 'Header Title',
                'name' => 'casting_header_title',
                'type' => 'text',
                'default_value' => 'Join Our Cast',
                'instructions' => 'Main title for the casting page header',
            ),
            array(
                'key' => 'field_casting_header_subtitle',
                'label' => 'Header Subtitle',
                'name' => 'casting_header_subtitle',
                'type' => 'textarea',
                'default_value' => 'Be part of Australia\'s most exciting adult entertainment production',
                'instructions' => 'Subtitle text for the casting page header',
            ),

            // Text Block Section
            array(
                'key' => 'field_casting_text_block',
                'label' => 'Brand Description Text',
                'name' => 'casting_text_block',
                'type' => 'wysiwyg',
                'instructions' => 'Main brand description and mission statement',
                'default_value' => '<p>Indulge in the opulence of our distinctive adult entertainment. We are the polar opposite of the sordid Adult Industry, upholding the worth of sophistication and refinement.</p>
<p>Our mission is to produce Australian High-Class Glamour Porn in a Professional setting. Dolls Downunder is an adult and lifestyle brand, celebrated for its top-tier, diverse, and relevant content in Adult Entertainment.</p>
<p>We strive to be the most inclusive sex-positive brand, creating content that encompasses a wide spectrum of sexualities, genders, races, body types, and ages. To witness our brand style, visit our social media:</p>
<p>Instagram: <a href="https://instagram.com/dollsdownunderofficial" target="_blank" rel="noopener">@dollsdownunderofficial</a></p>
<p>Twitter: <a href="https://x.com/dollsdownunder_" target="_blank" rel="noopener">@dollsdownunder_</a></p>
<p><strong>What we seek</strong></p>
<p>We\'re looking for models with natural allure that commands attention. Models of all shapes, sizes, backgrounds, and ethnicities are welcome. What we value most is AUTHENTICITY.</p>
<p>With numerous casting applications daily, ensure yours stands out! If you\'re interested in collaborating with the best and gaining significant exposure, apply here:</p>',
            ),

            // FAQ Section
            array(
                'key' => 'field_casting_faq_title',
                'label' => 'FAQ Section Title',
                'name' => 'casting_faq_title',
                'type' => 'text',
                'default_value' => 'Frequently Asked Questions',
                'instructions' => 'Title for the FAQ section',
            ),
            array(
                'key' => 'field_casting_faq_items',
                'label' => 'FAQ Items (JSON)',
                'name' => 'casting_faq_items',
                'type' => 'textarea',
                'instructions' => 'Enter FAQ items as JSON. Use the helper function to manage this data.',
                'rows' => 10,
                'default_value' => '[]',
            ),

            // Requirements Section
            array(
                'key' => 'field_casting_requirements_title',
                'label' => 'Requirements Section Title',
                'name' => 'casting_requirements_title',
                'type' => 'text',
                'default_value' => 'Requirements',
                'instructions' => 'Title for the requirements section',
            ),
            array(
                'key' => 'field_casting_requirements_subtitle',
                'label' => 'Requirements Section Subtitle',
                'name' => 'casting_requirements_subtitle',
                'type' => 'text',
                'default_value' => 'What you need to get started',
                'instructions' => 'Subtitle for the requirements section',
            ),
            array(
                'key' => 'field_casting_requirements_cards',
                'label' => 'Requirement Cards (JSON)',
                'name' => 'casting_requirements_cards',
                'type' => 'textarea',
                'instructions' => 'Enter requirement cards as JSON. Use the helper function to manage this data.',
                'rows' => 10,
                'default_value' => '[
    {
        "icon_class": "fas fa-id-card",
        "title": "Legal Requirements",
        "requirements": [
            "Must be 18+ years old",
            "Valid government ID",
            "Right to work in Australia"
        ]
    },
    {
        "icon_class": "fas fa-clipboard-check",
        "title": "Health & Safety",
        "requirements": [
            "Recent health certificates",
            "Professional attitude",
            "Reliable transportation"
        ]
    },
    {
        "icon_class": "fas fa-star",
        "title": "Personal Qualities",
        "requirements": [
            "Positive attitude",
            "Reliable and punctual",
            "Team player mindset"
        ]
    }
]',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/casting.php',
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
        'description' => 'Custom fields for the casting page content management',
    ));

    // Support Page Custom Fields
    acf_add_local_field_group(array(
        'key' => 'group_support_page',
        'title' => 'Support Page Content',
        'fields' => array(
            // FAQ Section
            array(
                'key' => 'field_support_faq_title',
                'label' => 'FAQ Section Title',
                'name' => 'support_faq_title',
                'type' => 'text',
                'default_value' => 'Frequently Asked Questions',
                'instructions' => 'Title for the FAQ section',
            ),
            array(
                'key' => 'field_support_faq_items',
                'label' => 'FAQ Items (JSON)',
                'name' => 'support_faq_items',
                'type' => 'textarea',
                'instructions' => 'Enter FAQ items as JSON. Each item supports question, answer, expanded.',
                'rows' => 10,
                'default_value' => '[]',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/support.php',
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
        'description' => 'Custom fields for the support page content management',
    ));

    // Home Page Custom Fields
    acf_add_local_field_group(array(
        'key' => 'group_home_page',
        'title' => 'Home Page Content',
        'fields' => array(
            // Promo Video Section
            array(
                'key' => 'field_home_promo_video_id',
                'label' => 'Promo Video ID',
                'name' => 'home_promo_video_id',
                'type' => 'text',
                'instructions' => 'Enter the BunnyCDN video ID for the promo/tour video',
                'placeholder' => 'e.g., abc123def456',
            ),
            array(
                'key' => 'field_home_promo_video_title',
                'label' => 'Promo Video Title',
                'name' => 'home_promo_video_title',
                'type' => 'text',
                'default_value' => 'Welcome to Our Platform',
                'instructions' => 'Title displayed above the promo video',
            ),
            array(
                'key' => 'field_home_promo_video_subtitle',
                'label' => 'Promo Video Subtitle',
                'name' => 'home_promo_video_subtitle',
                'type' => 'textarea',
                'default_value' => 'Experience premium content like never before',
                'instructions' => 'Subtitle text displayed below the promo video',
            ),
            array(
                'key' => 'field_home_promo_video_button_text',
                'label' => 'CTA Button Text',
                'name' => 'home_promo_video_button_text',
                'type' => 'text',
                'default_value' => 'Get Started Now',
                'instructions' => 'Text for the call-to-action button below the video',
            ),
            array(
                'key' => 'field_home_promo_video_button_url',
                'label' => 'CTA Button URL',
                'name' => 'home_promo_video_button_url',
                'type' => 'text',
                'default_value' => '/register',
                'instructions' => 'URL for the call-to-action button (can be relative like /register or full URL)',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/page-home.php',
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
        'description' => 'Custom fields for the home page promo video section',
    ));

    // About Page Custom Fields
    acf_add_local_field_group(array(
        'key' => 'group_about_page',
        'title' => 'About Page Content',
        'fields' => array(
            // Hero Section
            array(
                'key' => 'field_about_hero_title',
                'label' => 'Hero Title',
                'name' => 'about_hero_title',
                'type' => 'text',
                'default_value' => 'About Us',
                'instructions' => 'Main title for the about page hero section',
            ),
            array(
                'key' => 'field_about_hero_subtitle',
                'label' => 'Hero Subtitle',
                'name' => 'about_hero_subtitle',
                'type' => 'textarea',
                'default_value' => 'Learn more about our story, mission, and the people behind our success.',
                'instructions' => 'Subtitle text displayed below the hero title',
            ),
            array(
                'key' => 'field_about_hero_image',
                'label' => 'Hero Background Image',
                'name' => 'about_hero_image',
                'type' => 'image',
                'instructions' => 'Optional background image for the hero section',
                'return_format' => 'url',
                'preview_size' => 'medium',
            ),

            // Mission Statement Section
            array(
                'key' => 'field_about_mission_title',
                'label' => 'Mission Title',
                'name' => 'about_mission_title',
                'type' => 'text',
                'default_value' => 'Our Mission',
                'instructions' => 'Title for the mission statement section',
            ),
            array(
                'key' => 'field_about_mission_content',
                'label' => 'Mission Statement',
                'name' => 'about_mission_content',
                'type' => 'textarea',
                'default_value' => 'To provide high-quality, engaging content that inspires and educates our audience while maintaining the highest standards of creativity and professionalism.',
                'instructions' => 'Main mission statement content',
            ),
            array(
                'key' => 'field_about_mission_icon',
                'label' => 'Mission Icon',
                'name' => 'about_mission_icon',
                'type' => 'text',
                'default_value' => 'bullseye',
                'instructions' => 'Bootstrap icon class name (without bi- prefix)',
            ),

            // Story Section
            array(
                'key' => 'field_about_story_title',
                'label' => 'Story Title',
                'name' => 'about_story_title',
                'type' => 'text',
                'default_value' => 'Our Story',
                'instructions' => 'Title for the company story section',
            ),
            array(
                'key' => 'field_about_story_content',
                'label' => 'Story Content',
                'name' => 'about_story_content',
                'type' => 'wysiwyg',
                'instructions' => 'Tell your company story with rich text formatting',
                'toolbar' => 'basic',
                'media_upload' => 0,
            ),
            array(
                'key' => 'field_about_story_image',
                'label' => 'Story Image',
                'name' => 'about_story_image',
                'type' => 'image',
                'instructions' => 'Optional image to accompany the story',
                'return_format' => 'url',
                'preview_size' => 'medium',
            ),

            // Team Section
            array(
                'key' => 'field_about_team_title',
                'label' => 'Team Section Title',
                'name' => 'about_team_title',
                'type' => 'text',
                'default_value' => 'Meet Our Team',
                'instructions' => 'Title for the team section',
            ),
            array(
                'key' => 'field_about_team_subtitle',
                'label' => 'Team Section Subtitle',
                'name' => 'about_team_subtitle',
                'type' => 'text',
                'default_value' => 'The talented individuals behind our success',
                'instructions' => 'Subtitle for the team section',
            ),
            array(
                'key' => 'field_about_team_members',
                'label' => 'Team Members',
                'name' => 'about_team_members',
                'type' => 'repeater',
                'instructions' => 'Add team members to display on the about page',
                'layout' => 'table',
                'button_label' => 'Add Team Member',
                'sub_fields' => array(
                    array(
                        'key' => 'field_team_member_name',
                        'label' => 'Name',
                        'name' => 'name',
                        'type' => 'text',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_team_member_position',
                        'label' => 'Position',
                        'name' => 'position',
                        'type' => 'text',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_team_member_bio',
                        'label' => 'Bio',
                        'name' => 'bio',
                        'type' => 'textarea',
                        'instructions' => 'Brief bio about the team member',
                    ),
                    array(
                        'key' => 'field_team_member_image',
                        'label' => 'Photo',
                        'name' => 'image',
                        'type' => 'image',
                        'return_format' => 'url',
                        'preview_size' => 'thumbnail',
                    ),
                    array(
                        'key' => 'field_team_member_email',
                        'label' => 'Email',
                        'name' => 'email',
                        'type' => 'email',
                    ),
                    array(
                        'key' => 'field_team_member_linkedin',
                        'label' => 'LinkedIn URL',
                        'name' => 'linkedin',
                        'type' => 'url',
                    ),
                    array(
                        'key' => 'field_team_member_twitter',
                        'label' => 'Twitter URL',
                        'name' => 'twitter',
                        'type' => 'url',
                    ),
                    array(
                        'key' => 'field_team_member_instagram',
                        'label' => 'Instagram URL',
                        'name' => 'instagram',
                        'type' => 'url',
                    ),
                ),
            ),

            // Values Section
            array(
                'key' => 'field_about_values_title',
                'label' => 'Values Section Title',
                'name' => 'about_values_title',
                'type' => 'text',
                'default_value' => 'Our Values',
                'instructions' => 'Title for the values section',
            ),
            array(
                'key' => 'field_about_values_subtitle',
                'label' => 'Values Section Subtitle',
                'name' => 'about_values_subtitle',
                'type' => 'text',
                'default_value' => 'The principles that guide everything we do',
                'instructions' => 'Subtitle for the values section',
            ),
            array(
                'key' => 'field_about_values',
                'label' => 'Company Values',
                'name' => 'about_values',
                'type' => 'repeater',
                'instructions' => 'Add company values to display on the about page',
                'layout' => 'table',
                'button_label' => 'Add Value',
                'sub_fields' => array(
                    array(
                        'key' => 'field_value_title',
                        'label' => 'Value Title',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_value_description',
                        'label' => 'Description',
                        'name' => 'description',
                        'type' => 'textarea',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_value_icon',
                        'label' => 'Icon',
                        'name' => 'icon',
                        'type' => 'text',
                        'default_value' => 'heart',
                        'instructions' => 'Bootstrap icon class name (without bi- prefix)',
                    ),
                ),
            ),

            // Statistics Section
            array(
                'key' => 'field_about_stats_title',
                'label' => 'Statistics Section Title',
                'name' => 'about_stats_title',
                'type' => 'text',
                'default_value' => 'By the Numbers',
                'instructions' => 'Title for the statistics section',
            ),
            array(
                'key' => 'field_about_stats',
                'label' => 'Statistics',
                'name' => 'about_stats',
                'type' => 'repeater',
                'instructions' => 'Add key statistics to highlight on the about page',
                'layout' => 'table',
                'button_label' => 'Add Statistic',
                'sub_fields' => array(
                    array(
                        'key' => 'field_stat_number',
                        'label' => 'Number',
                        'name' => 'number',
                        'type' => 'number',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_stat_label',
                        'label' => 'Label',
                        'name' => 'label',
                        'type' => 'text',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_stat_description',
                        'label' => 'Description',
                        'name' => 'description',
                        'type' => 'text',
                        'instructions' => 'Optional additional context',
                    ),
                ),
            ),

            // Call to Action Section
            array(
                'key' => 'field_about_cta_title',
                'label' => 'CTA Title',
                'name' => 'about_cta_title',
                'type' => 'text',
                'default_value' => 'Join Our Community',
                'instructions' => 'Title for the call-to-action section',
            ),
            array(
                'key' => 'field_about_cta_content',
                'label' => 'CTA Content',
                'name' => 'about_cta_content',
                'type' => 'textarea',
                'default_value' => 'Be part of our growing community and get access to exclusive content, special offers, and more.',
                'instructions' => 'Content for the call-to-action section',
            ),
            array(
                'key' => 'field_about_cta_button_text',
                'label' => 'CTA Button Text',
                'name' => 'about_cta_button_text',
                'type' => 'text',
                'default_value' => 'Get Started',
                'instructions' => 'Text for the call-to-action button',
            ),
            array(
                'key' => 'field_about_cta_button_url',
                'label' => 'CTA Button URL',
                'name' => 'about_cta_button_url',
                'type' => 'text',
                'default_value' => '/join',
                'instructions' => 'URL for the call-to-action button',
            ),

            // SEO Fields
            array(
                'key' => 'field_about_meta_description',
                'label' => 'Meta Description',
                'name' => 'about_meta_description',
                'type' => 'textarea',
                'instructions' => 'SEO meta description for the about page',
                'maxlength' => 160,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-templates/about.php',
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
        'description' => 'Custom fields for the about page content management',
    ));

endif;
