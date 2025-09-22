<?php
/**
 * Advanced Custom Fields Configuration
 */

if (function_exists('acf_add_local_field_group')):

    acf_add_local_field_group(array(
        'key' => 'group_episode_videos',
        'title' => 'Episode Videos',
        'fields' => array(
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
                'label' => 'PPV Price',
                'name' => 'episode_price',
                'type' => 'number',
                'instructions' => 'Enter the price for non-members (leave empty for free episodes)',
                'required' => 0,
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

    // Only register Extras ACF fields if enabled
    if (flexpress_is_extras_enabled()) {
        acf_add_local_field_group(array(
            'key' => 'group_extras_videos',
            'title' => 'Extras Videos',
        'fields' => array(
            array(
                'key' => 'field_extras_preview_video',
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
                'key' => 'field_extras_trailer_video',
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
                'key' => 'field_extras_full_video',
                'label' => 'Full Video',
                'name' => 'full_video',
                'type' => 'text',
                'instructions' => 'Enter the BunnyCDN Stream video ID for the full extra content',
                'required' => 1,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
            ),
            array(
                'key' => 'field_extras_duration',
                'label' => 'Content Duration',
                'name' => 'extras_duration',
                'type' => 'text',
                'instructions' => 'Duration in minutes (automatically retrieved from BunnyCDN if left empty)',
                'required' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
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
                'label' => 'PPV Price',
                'name' => 'extras_price',
                'type' => 'number',
                'instructions' => 'Enter the price for non-members (leave empty for free content)',
                'required' => 0,
                'wrapper' => array(
                    'width' => '',
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
                    'width' => '',
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
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
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
    } // End Extras conditional block

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
                'key' => 'field_model_birthdate',
                'label' => 'Date of Birth',
                'name' => 'model_birthdate',
                'type' => 'date_picker',
                'instructions' => 'Select the model\'s date of birth',
                'required' => 0,
                'wrapper' => array(
                    'width' => '33',
                    'class' => '',
                    'id' => '',
                ),
                'display_format' => 'F j, Y',
                'return_format' => 'Y-m-d',
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
                'key' => 'field_model_measurements',
                'label' => 'Measurements',
                'name' => 'model_measurements',
                'type' => 'text',
                'instructions' => 'Enter the model\'s measurements (e.g., 34-26-36)',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'placeholder' => '34-26-36',
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
                'instructions' => 'Upload a profile image for the half-half section (separate from featured image)',
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
                'key' => 'field_model_show_on_homepage',
                'label' => 'Show on Homepage',
                'name' => 'model_show_on_homepage',
                'type' => 'true_false',
                'instructions' => 'Display this model on the homepage model grid',
                'required' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 1,
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
                'default_value' => '[]',
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

endif; 