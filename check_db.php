<?php
require_once '/var/www/html/wp-load.php';

global $wpdb;

echo "=== FlexPress Database Verification ===\n";
echo "Database prefix: " . $wpdb->prefix . "\n\n";

// List all FlexPress tables that should exist
$expected_tables = [
    $wpdb->prefix . 'flexpress_flowguard_webhooks',
    $wpdb->prefix . 'flexpress_flowguard_transactions',
    $wpdb->prefix . 'flexpress_flowguard_sessions',
    $wpdb->prefix . 'flexpress_affiliates',
    $wpdb->prefix . 'flexpress_affiliate_promo_codes',
    $wpdb->prefix . 'flexpress_affiliate_clicks',
    $wpdb->prefix . 'flexpress_affiliate_transactions',
    $wpdb->prefix . 'flexpress_affiliate_payouts',
    $wpdb->prefix . 'flexpress_user_activity',
    $wpdb->prefix . 'flexpress_talent_applications',
    $wpdb->prefix . 'flexpress_promo_usage',
    $wpdb->prefix . 'flexpress_episode_ratings'
];

echo "=== TABLE CHECK ===\n";
foreach ($expected_tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    $status = ($exists === $table) ? '✓ EXISTS' : '✗ MISSING';
    echo "$status: $table\n";
}

// Check important options
$expected_options = [
    'flexpress_db_version',
    'flexpress_flowguard_db_version',
    'flexpress_affiliate_db_version',
    'flexpress_pages_created',
    'flexpress_auto_setup_completed',
    'flexpress_menus_created'
];

echo "\n=== OPTIONS CHECK ===\n";
foreach ($expected_options as $option) {
    $value = get_option($option);
    if ($value !== false) {
        echo "✓ SET: $option = " . (is_array($value) ? json_encode($value) : $value) . "\n";
    } else {
        echo "✗ NOT SET: $option\n";
    }
}

// Check if theme is active
$active_theme = wp_get_theme();
echo "\n=== THEME STATUS ===\n";
echo "Active theme: " . $active_theme->get('Name') . " (" . $active_theme->get('TextDomain') . ")\n";
echo "Theme version: " . $active_theme->get('Version') . "\n";

// Check cron events
$cron_events = get_option('cron', []);
$flexpress_crons = [];
foreach ($cron_events as $timestamp => $events) {
    foreach ($events as $hook => $details) {
        if (strpos($hook, 'flexpress') !== false) {
            $flexpress_crons[] = $hook;
        }
    }
}

echo "\n=== CRON EVENTS ===\n";
if (empty($flexpress_crons)) {
    echo "✗ No FlexPress cron events found\n";
} else {
    foreach ($flexpress_crons as $cron) {
        echo "✓ CRON: $cron\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "This script checks if FlexPress database setup is complete.\n";
echo "If any tables or options are missing, the theme may not have been activated yet.\n";
?>

