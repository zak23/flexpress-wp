<?php
/**
 * Register Custom Post Types
 */

/**
 * Register Episodes Post Type
 */
function flexpress_register_episode_post_type() {
    $labels = array(
        'name'                  => _x('Episodes', 'Post Type General Name', 'flexpress'),
        'singular_name'         => _x('Episode', 'Post Type Singular Name', 'flexpress'),
        'menu_name'            => __('Episodes', 'flexpress'),
        'name_admin_bar'       => __('Episode', 'flexpress'),
        'archives'             => __('Episode Archives', 'flexpress'),
        'attributes'           => __('Episode Attributes', 'flexpress'),
        'parent_item_colon'    => __('Parent Episode:', 'flexpress'),
        'all_items'            => __('All Episodes', 'flexpress'),
        'add_new_item'         => __('Add New Episode', 'flexpress'),
        'add_new'              => __('Add New', 'flexpress'),
        'new_item'             => __('New Episode', 'flexpress'),
        'edit_item'            => __('Edit Episode', 'flexpress'),
        'update_item'          => __('Update Episode', 'flexpress'),
        'view_item'            => __('View Episode', 'flexpress'),
        'view_items'           => __('View Episodes', 'flexpress'),
        'search_items'         => __('Search Episode', 'flexpress'),
        'not_found'            => __('Not found', 'flexpress'),
        'not_found_in_trash'   => __('Not found in Trash', 'flexpress'),
        'featured_image'       => __('Featured Image', 'flexpress'),
        'set_featured_image'   => __('Set featured image', 'flexpress'),
        'remove_featured_image'=> __('Remove featured image', 'flexpress'),
        'use_featured_image'   => __('Use as featured image', 'flexpress'),
        'insert_into_item'     => __('Insert into episode', 'flexpress'),
        'uploaded_to_this_item'=> __('Uploaded to this episode', 'flexpress'),
        'items_list'           => __('Episodes list', 'flexpress'),
        'items_list_navigation'=> __('Episodes list navigation', 'flexpress'),
        'filter_items_list'    => __('Filter episodes list', 'flexpress'),
    );

    $args = array(
        'label'               => __('Episode', 'flexpress'),
        'description'         => __('Video Episodes', 'flexpress'),
        'labels'              => $labels,
        'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
        'taxonomies'          => array('post_tag'),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-video-alt3',
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'show_in_rest'        => true,
        'rewrite'             => array('slug' => 'episodes'),
    );

    register_post_type('episode', $args);

    // Register Access Type Taxonomy (formerly Episode Status)
    $status_labels = array(
        'name'              => _x('Access Type', 'taxonomy general name', 'flexpress'),
        'singular_name'     => _x('Access Type', 'taxonomy singular name', 'flexpress'),
        'search_items'      => __('Search Access Types', 'flexpress'),
        'all_items'         => __('All Access Types', 'flexpress'),
        'parent_item'       => __('Parent Access Type', 'flexpress'),
        'parent_item_colon' => __('Parent Access Type:', 'flexpress'),
        'edit_item'         => __('Edit Access Type', 'flexpress'),
        'update_item'       => __('Update Access Type', 'flexpress'),
        'add_new_item'      => __('Add New Access Type', 'flexpress'),
        'new_item_name'     => __('New Access Type Name', 'flexpress'),
        'menu_name'         => __('Access Type', 'flexpress'),
    );

    $status_args = array(
        'hierarchical'      => true,
        'labels'            => $status_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'status'),
        'show_in_rest'      => true,
    );

    register_taxonomy('episode_status', array('episode'), $status_args);
}
add_action('init', 'flexpress_register_episode_post_type'); 

/**
 * Register Models Post Type
 */
function flexpress_register_model_post_type() {
    $labels = array(
        'name'                  => _x('Models', 'Post Type General Name', 'flexpress'),
        'singular_name'         => _x('Model', 'Post Type Singular Name', 'flexpress'),
        'menu_name'            => __('Models', 'flexpress'),
        'name_admin_bar'       => __('Model', 'flexpress'),
        'archives'             => __('Model Archives', 'flexpress'),
        'attributes'           => __('Model Attributes', 'flexpress'),
        'parent_item_colon'    => __('Parent Model:', 'flexpress'),
        'all_items'            => __('All Models', 'flexpress'),
        'add_new_item'         => __('Add New Model', 'flexpress'),
        'add_new'              => __('Add New', 'flexpress'),
        'new_item'             => __('New Model', 'flexpress'),
        'edit_item'            => __('Edit Model', 'flexpress'),
        'update_item'          => __('Update Model', 'flexpress'),
        'view_item'            => __('View Model', 'flexpress'),
        'view_items'           => __('View Models', 'flexpress'),
        'search_items'         => __('Search Model', 'flexpress'),
        'not_found'            => __('Not found', 'flexpress'),
        'not_found_in_trash'   => __('Not found in Trash', 'flexpress'),
        'featured_image'       => __('Profile Photo', 'flexpress'),
        'set_featured_image'   => __('Set profile photo', 'flexpress'),
        'remove_featured_image'=> __('Remove profile photo', 'flexpress'),
        'use_featured_image'   => __('Use as profile photo', 'flexpress'),
        'insert_into_item'     => __('Insert into model', 'flexpress'),
        'uploaded_to_this_item'=> __('Uploaded to this model', 'flexpress'),
        'items_list'           => __('Models list', 'flexpress'),
        'items_list_navigation'=> __('Models list navigation', 'flexpress'),
        'filter_items_list'    => __('Filter models list', 'flexpress'),
    );

    $args = array(
        'label'               => __('Model', 'flexpress'),
        'description'         => __('Model Profiles', 'flexpress'),
        'labels'              => $labels,
        'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'comments'),
        'taxonomies'          => array('post_tag'),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 6,
        'menu_icon'           => 'dashicons-businessperson',
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'show_in_rest'        => true,
        'rewrite'             => array('slug' => 'models'),
    );

    register_post_type('model', $args);
}
add_action('init', 'flexpress_register_model_post_type');

/**
 * Register Extras Post Type
 */
function flexpress_register_extras_post_type() {
    // Check if Extras are enabled in settings
    if (!flexpress_is_extras_enabled()) {
        return;
    }
    $labels = array(
        'name'                  => _x('Extras', 'Post Type General Name', 'flexpress'),
        'singular_name'         => _x('Extra', 'Post Type Singular Name', 'flexpress'),
        'menu_name'            => __('Extras', 'flexpress'),
        'name_admin_bar'       => __('Extra', 'flexpress'),
        'archives'             => __('Extra Archives', 'flexpress'),
        'attributes'           => __('Extra Attributes', 'flexpress'),
        'parent_item_colon'    => __('Parent Extra:', 'flexpress'),
        'all_items'            => __('All Extras', 'flexpress'),
        'add_new_item'         => __('Add New Extra', 'flexpress'),
        'add_new'              => __('Add New', 'flexpress'),
        'new_item'             => __('New Extra', 'flexpress'),
        'edit_item'            => __('Edit Extra', 'flexpress'),
        'update_item'          => __('Update Extra', 'flexpress'),
        'view_item'            => __('View Extra', 'flexpress'),
        'view_items'           => __('View Extras', 'flexpress'),
        'search_items'         => __('Search Extra', 'flexpress'),
        'not_found'            => __('Not found', 'flexpress'),
        'not_found_in_trash'   => __('Not found in Trash', 'flexpress'),
        'featured_image'       => __('Featured Image', 'flexpress'),
        'set_featured_image'   => __('Set featured image', 'flexpress'),
        'remove_featured_image'=> __('Remove featured image', 'flexpress'),
        'use_featured_image'   => __('Use as featured image', 'flexpress'),
        'insert_into_item'     => __('Insert into extra', 'flexpress'),
        'uploaded_to_this_item'=> __('Uploaded to this extra', 'flexpress'),
        'items_list'           => __('Extras list', 'flexpress'),
        'items_list_navigation'=> __('Extras list navigation', 'flexpress'),
        'filter_items_list'    => __('Filter extras list', 'flexpress'),
    );

    $args = array(
        'label'               => __('Extra', 'flexpress'),
        'description'         => __('Behind-the-Scenes and Extra Content', 'flexpress'),
        'labels'              => $labels,
        'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'taxonomies'          => array('post_tag'),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 7,
        'menu_icon'           => 'dashicons-camera-alt',
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search'  => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'show_in_rest'        => true,
        'rewrite'             => array('slug' => 'extras'),
    );

    register_post_type('extras', $args);

    // Add gallery support to extras
    add_post_type_support('extras', 'gallery');

    // Register Extras Status Taxonomy
    $status_labels = array(
        'name'              => _x('Extras Status', 'taxonomy general name', 'flexpress'),
        'singular_name'     => _x('Status', 'taxonomy singular name', 'flexpress'),
        'search_items'      => __('Search Statuses', 'flexpress'),
        'all_items'         => __('All Statuses', 'flexpress'),
        'parent_item'       => __('Parent Status', 'flexpress'),
        'parent_item_colon' => __('Parent Status:', 'flexpress'),
        'edit_item'         => __('Edit Status', 'flexpress'),
        'update_item'       => __('Update Status', 'flexpress'),
        'add_new_item'      => __('Add New Status', 'flexpress'),
        'new_item_name'     => __('New Status Name', 'flexpress'),
        'menu_name'         => __('Status', 'flexpress'),
    );

    $status_args = array(
        'hierarchical'      => true,
        'labels'            => $status_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'extras-status'),
        'show_in_rest'      => true,
    );

    register_taxonomy('extras_status', array('extras'), $status_args);
}
add_action('init', 'flexpress_register_extras_post_type'); 