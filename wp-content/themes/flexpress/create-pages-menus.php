<?php
/**
 * Script to manually create the default pages and menus
 */

// Define WordPress path - use dirname multiple times to navigate up to WordPress root
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Check if file exists
if (!file_exists($wp_load_path)) {
    die('WordPress wp-load.php file not found. Path attempted: ' . $wp_load_path);
}

// Load WordPress
define('WP_USE_THEMES', false);
require_once($wp_load_path);

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You do not have sufficient permissions to access this page.');
}

// Delete existing flags to force recreation
delete_option('flexpress_pages_created');
delete_option('flexpress_menus_created');

// Create pages and menus - these functions are defined in functions.php
if (function_exists('flexpress_create_required_pages')) {
    echo '<p>Creating pages and menus...</p>';
    flexpress_create_required_pages();
    echo '<p>Pages and menus created successfully!</p>';
} else {
    echo '<p>ERROR: flexpress_create_required_pages function not found.</p>';
}

// Output result
echo '<h1>Pages and Menus Creation</h1>';
echo '<p>Process completed.</p>';
echo '<p><a href="' . admin_url() . '">Return to admin dashboard</a></p>'; 