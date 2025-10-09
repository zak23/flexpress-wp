<?php
/**
 * Enable Extras functionality in FlexPress
 */

// Load WordPress
require_once('wp-config.php');

// Get current settings
$current_settings = get_option('flexpress_general_settings', array());

// Enable Extras
$current_settings['extras_enabled'] = '1';

// Update settings
$result = update_option('flexpress_general_settings', $current_settings);

if ($result) {
    echo "✅ Extras enabled successfully!\n";
    echo "Current settings: " . print_r($current_settings, true) . "\n";
} else {
    echo "❌ Failed to enable Extras\n";
}

// Check if Extras are now enabled
if (function_exists('flexpress_is_extras_enabled')) {
    echo "flexpress_is_extras_enabled(): " . (flexpress_is_extras_enabled() ? 'true' : 'false') . "\n";
} else {
    echo "flexpress_is_extras_enabled() function not found\n";
}
?>
