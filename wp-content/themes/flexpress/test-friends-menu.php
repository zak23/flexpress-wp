<?php
/**
 * Test script for Friends Menu functionality
 * Run this via WP-CLI: ./wp-cli.sh eval-file wp-content/themes/flexpress/test-friends-menu.php
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "Testing Friends Menu Creation...\n";

// Test the friends menu creation function
$result = flexpress_create_friends_menu();

if ($result) {
    echo "✅ Friends menu created successfully!\n";
    
    // Check if menu exists
    $friends_menu = wp_get_nav_menu_object('Footer Friends Menu');
    if ($friends_menu) {
        echo "✅ Menu found with ID: " . $friends_menu->term_id . "\n";
        
        // Get menu items
        $menu_items = wp_get_nav_menu_items($friends_menu->term_id);
        if ($menu_items) {
            echo "✅ Menu items found: " . count($menu_items) . " items\n";
            foreach ($menu_items as $item) {
                echo "  - " . $item->title . " (" . $item->url . ")\n";
            }
        } else {
            echo "❌ No menu items found\n";
        }
    } else {
        echo "❌ Menu not found after creation\n";
    }
    
    // Check menu location assignment
    $locations = get_theme_mod('nav_menu_locations', array());
    if (isset($locations['footer-friends-menu'])) {
        echo "✅ Menu assigned to footer-friends-menu location\n";
    } else {
        echo "❌ Menu not assigned to footer-friends-menu location\n";
    }
    
} else {
    echo "❌ Friends menu creation failed\n";
}

echo "Test completed.\n";
