<?php
/**
 * Script to reset and recreate menus
 */

// Load WordPress
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You do not have sufficient permissions to access this page.');
}

// Delete existing flags
delete_option('flexpress_menus_created');
delete_option('flexpress_pages_created');

// Delete existing menus
$menus = wp_get_nav_menus();
foreach ($menus as $menu) {
    wp_delete_nav_menu($menu->term_id);
}

// Create pages and menus
if (function_exists('flexpress_create_required_pages')) {
    flexpress_create_required_pages();
    echo '<p>Pages and menus have been recreated successfully!</p>';
} else {
    echo '<p>ERROR: flexpress_create_required_pages function not found.</p>';
}

echo '<p><a href="' . admin_url() . '">Return to admin dashboard</a></p>'; 