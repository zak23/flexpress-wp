<?php
/**
 * FlexPress Trial Links Helper Functions
 * 
 * Helper functions for managing trial links.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate a unique secure trial token
 * 
 * @return string Unique token
 */
function flexpress_generate_trial_token() {
    return bin2hex(random_bytes(32)); // 64 character hex string
}

/**
 * Get trial link by token
 * 
 * @param string $token Trial link token
 * @return object|false Trial link object or false if not found
 */
function flexpress_get_trial_link($token) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_trial_links';
    
    $token = sanitize_text_field($token);
    
    $trial_link = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE token = %s",
        $token
    ));
    
    return $trial_link;
}

/**
 * Check if trial token is valid
 * 
 * @param string $token Trial link token
 * @return array Validation result with 'valid' boolean and 'message' string
 */
function flexpress_validate_trial_token($token) {
    $trial_link = flexpress_get_trial_link($token);
    
    if (!$trial_link) {
        return array(
            'valid' => false,
            'message' => 'Invalid trial link token.'
        );
    }
    
    if (!$trial_link->is_active) {
        return array(
            'valid' => false,
            'message' => 'This trial link has been deactivated.'
        );
    }
    
    if (!empty($trial_link->expires_at)) {
        $expires_at = strtotime($trial_link->expires_at);
        if ($expires_at < current_time('timestamp')) {
            return array(
                'valid' => false,
                'message' => 'This trial link has expired.'
            );
        }
    }
    
    if ($trial_link->use_count >= $trial_link->max_uses) {
        return array(
            'valid' => false,
            'message' => 'This trial link has reached its maximum uses.'
        );
    }
    
    return array(
        'valid' => true,
        'trial_link' => $trial_link
    );
}

/**
 * Mark trial link as used
 * 
 * @param int $trial_link_id Trial link ID
 * @return bool True on success
 */
function flexpress_mark_trial_link_used($trial_link_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_trial_links';
    
    $result = $wpdb->query($wpdb->prepare(
        "UPDATE $table_name SET use_count = use_count + 1 WHERE id = %d",
        $trial_link_id
    ));
    
    return $result !== false;
}

/**
 * Check if current registration is via trial link
 * 
 * @return bool True if trial registration
 */
function flexpress_is_trial_registration() {
    // Check for trial token in POST data
    if (isset($_POST['trial_token']) && !empty($_POST['trial_token'])) {
        return true;
    }
    
    // Check for trial token in session
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['trial_token'])) {
        return true;
    }
    
    // Check for trial token in cookie
    if (isset($_COOKIE['flexpress_trial_token'])) {
        return true;
    }
    
    return false;
}

/**
 * Get trial link URL
 * 
 * @param string $token Trial link token
 * @return string Full trial link URL
 */
function flexpress_get_trial_link_url($token) {
    return home_url('/join?trial=' . urlencode($token));
}

/**
 * Create a new trial link
 * 
 * @param array $args Trial link arguments
 * @return int|false Trial link ID on success, false on failure
 */
function flexpress_create_trial_link($args) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_trial_links';
    
    // Check if table exists, if not create it
    if (!flexpress_trial_links_table_exists()) {
        flexpress_trial_links_init_database();
    }
    
    $defaults = array(
        'duration' => 7,
        'created_by' => get_current_user_id(),
        'expires_at' => null,
        'max_uses' => 1,
        'notes' => ''
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Generate unique token
    $token = flexpress_generate_trial_token();
    
    // Ensure token is unique
    while (flexpress_get_trial_link($token)) {
        $token = flexpress_generate_trial_token();
    }
    
    $data = array(
        'token' => $token,
        'duration' => intval($args['duration']),
        'created_by' => intval($args['created_by']),
        'expires_at' => $args['expires_at'] ? date('Y-m-d H:i:s', strtotime($args['expires_at'])) : null,
        'max_uses' => intval($args['max_uses']),
        'notes' => sanitize_textarea_field($args['notes']),
        'is_active' => 1
    );
    
    $result = $wpdb->insert($table_name, $data);
    
    if ($result === false) {
        error_log('FlexPress Trial Links: Failed to insert trial link. Error: ' . $wpdb->last_error);
        error_log('FlexPress Trial Links: Query: ' . $wpdb->last_query);
        return false;
    }
    
    if ($result) {
        return $wpdb->insert_id;
    }
    
    return false;
}

/**
 * Get all trial links
 * 
 * @param array $args Query arguments
 * @return array Array of trial link objects
 */
function flexpress_get_all_trial_links($args = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_trial_links';
    
    $defaults = array(
        'orderby' => 'created_at',
        'order' => 'DESC',
        'limit' => -1,
        'offset' => 0
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
    if (!$orderby) {
        $orderby = 'created_at DESC';
    }
    
    $sql = "SELECT * FROM $table_name";
    
    if (!empty($args['is_active'])) {
        $sql .= $wpdb->prepare(" WHERE is_active = %d", $args['is_active']);
    }
    
    $sql .= " ORDER BY $orderby";
    
    if ($args['limit'] > 0) {
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
    }
    
    return $wpdb->get_results($sql);
}

/**
 * Delete trial link
 * 
 * @param int $trial_link_id Trial link ID
 * @return bool True on success
 */
function flexpress_delete_trial_link($trial_link_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_trial_links';
    
    $result = $wpdb->delete($table_name, array('id' => $trial_link_id), array('%d'));
    
    return $result !== false;
}

/**
 * Update trial link
 * 
 * @param int $trial_link_id Trial link ID
 * @param array $data Data to update
 * @return bool True on success
 */
function flexpress_update_trial_link($trial_link_id, $data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_trial_links';
    
    $allowed_fields = array('duration', 'expires_at', 'max_uses', 'is_active', 'notes');
    $update_data = array();
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            if ($field === 'expires_at' && !empty($data[$field])) {
                $update_data[$field] = date('Y-m-d H:i:s', strtotime($data[$field]));
            } elseif ($field === 'is_active') {
                $update_data[$field] = intval($data[$field]);
            } elseif ($field === 'duration' || $field === 'max_uses') {
                $update_data[$field] = intval($data[$field]);
            } elseif ($field === 'notes') {
                $update_data[$field] = sanitize_textarea_field($data[$field]);
            } else {
                $update_data[$field] = sanitize_text_field($data[$field]);
            }
        }
    }
    
    if (empty($update_data)) {
        return false;
    }
    
    $result = $wpdb->update(
        $table_name,
        $update_data,
        array('id' => $trial_link_id),
        null,
        array('%d')
    );
    
    return $result !== false;
}

