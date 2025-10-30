<?php
/**
 * FlexPress Trial Links Database Schema
 * 
 * Creates and manages database tables for the trial links system.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create trial links database table
 */
function flexpress_trial_links_create_table() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $table_name = $wpdb->prefix . 'flexpress_trial_links';
    
    // dbDelta requires specific formatting - two spaces between CREATE TABLE and table name
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        token varchar(64) NOT NULL,
        duration int(11) NOT NULL,
        created_by bigint(20) NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at datetime NULL,
        max_uses int(11) NOT NULL DEFAULT 1,
        use_count int(11) NOT NULL DEFAULT 0,
        is_active tinyint(1) NOT NULL DEFAULT 1,
        notes text NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY token (token),
        KEY created_by (created_by),
        KEY is_active (is_active),
        KEY expires_at (expires_at),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Verify table was created, if not try direct query
    if (!flexpress_trial_links_table_exists()) {
        // Try direct query as fallback
        $direct_sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            token varchar(64) NOT NULL,
            duration int(11) NOT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NULL,
            max_uses int(11) NOT NULL DEFAULT 1,
            use_count int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            notes text NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY created_by (created_by),
            KEY is_active (is_active),
            KEY expires_at (expires_at),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        $wpdb->query($direct_sql);
        
        // Log if still failed
        if (!flexpress_trial_links_table_exists()) {
            error_log('FlexPress Trial Links: Failed to create table after dbDelta and direct query.');
            error_log('FlexPress Trial Links: Last error: ' . $wpdb->last_error);
        }
    }
}

/**
 * Check if trial links table exists
 * 
 * @return bool True if table exists
 */
function flexpress_trial_links_table_exists() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_trial_links';
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    return $exists === $table_name;
}

/**
 * Initialize trial links database on theme activation
 */
function flexpress_trial_links_init_database() {
    if (!flexpress_trial_links_table_exists()) {
        flexpress_trial_links_create_table();
        update_option('flexpress_trial_links_db_version', '1.0.0');
    }
}

