<?php
/**
 * Flowguard Database Schema
 * 
 * Creates and manages database tables for Flowguard integration.
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create Flowguard database tables
 */
function flexpress_flowguard_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Flowguard webhooks table
    $webhooks_table = $wpdb->prefix . 'flexpress_flowguard_webhooks';
    $webhooks_sql = "CREATE TABLE $webhooks_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        webhook_id varchar(255) NOT NULL,
        event_type varchar(100) NOT NULL,
        transaction_id varchar(255),
        user_id bigint(20),
        payload longtext NOT NULL,
        processed tinyint(1) DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY webhook_id (webhook_id),
        KEY event_type (event_type),
        KEY processed (processed),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Flowguard transactions table
    $transactions_table = $wpdb->prefix . 'flexpress_flowguard_transactions';
    $transactions_sql = "CREATE TABLE $transactions_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        transaction_id varchar(255) NOT NULL,
        session_id varchar(255) NOT NULL,
        sale_id varchar(255),
        amount decimal(10,2) NOT NULL,
        currency varchar(3) NOT NULL,
        status varchar(50) NOT NULL,
        order_type varchar(50) NOT NULL,
        reference_id varchar(255),
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY transaction_id (transaction_id),
        KEY user_id (user_id),
        KEY status (status),
        KEY order_type (order_type),
        KEY sale_id (sale_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    // Flowguard sessions table (for tracking payment sessions)
    $sessions_table = $wpdb->prefix . 'flexpress_flowguard_sessions';
    $sessions_sql = "CREATE TABLE $sessions_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id varchar(255) NOT NULL,
        user_id bigint(20),
        plan_id bigint(20),
        episode_id bigint(20),
        session_type varchar(50) NOT NULL,
        status varchar(50) NOT NULL DEFAULT 'pending',
        amount decimal(10,2),
        currency varchar(3),
        reference_id varchar(255),
        created_at datetime NOT NULL,
        expires_at datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY session_id (session_id),
        KEY user_id (user_id),
        KEY status (status),
        KEY session_type (session_type),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($webhooks_sql);
    dbDelta($transactions_sql);
    dbDelta($sessions_sql);
    
    // Store database version
    update_option('flexpress_flowguard_db_version', '1.0.0');
    
    return true;
}

/**
 * Drop Flowguard database tables
 */
function flexpress_flowguard_drop_tables() {
    global $wpdb;
    
    $tables = [
        $wpdb->prefix . 'flexpress_flowguard_webhooks',
        $wpdb->prefix . 'flexpress_flowguard_transactions',
        $wpdb->prefix . 'flexpress_flowguard_sessions'
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    delete_option('flexpress_flowguard_db_version');
    
    return true;
}

/**
 * Check if Flowguard tables exist
 * 
 * @return bool True if tables exist
 */
function flexpress_flowguard_tables_exist() {
    global $wpdb;
    
    $tables = [
        $wpdb->prefix . 'flexpress_flowguard_webhooks',
        $wpdb->prefix . 'flexpress_flowguard_transactions',
        $wpdb->prefix . 'flexpress_flowguard_sessions'
    ];
    
    foreach ($tables as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get Flowguard database version
 * 
 * @return string Database version
 */
function flexpress_flowguard_get_db_version() {
    return get_option('flexpress_flowguard_db_version', '0.0.0');
}

/**
 * Store Flowguard session
 * 
 * @param array $session_data Session data
 * @return int|false Session ID or false on error
 */
function flexpress_flowguard_store_session($session_data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_flowguard_sessions';
    
    $result = $wpdb->insert(
        $table_name,
        [
            'session_id' => $session_data['session_id'],
            'user_id' => $session_data['user_id'] ?? null,
            'plan_id' => $session_data['plan_id'] ?? null,
            'episode_id' => $session_data['episode_id'] ?? null,
            'session_type' => $session_data['session_type'],
            'status' => $session_data['status'] ?? 'pending',
            'amount' => $session_data['amount'] ?? null,
            'currency' => $session_data['currency'] ?? null,
            'reference_id' => $session_data['reference_id'] ?? null,
            'created_at' => current_time('mysql'),
            'expires_at' => $session_data['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+1 hour'))
        ],
        [
            '%s', // session_id
            '%d', // user_id
            '%d', // plan_id
            '%d', // episode_id
            '%s', // session_type
            '%s', // status
            '%f', // amount
            '%s', // currency
            '%s', // reference_id
            '%s', // created_at
            '%s'  // expires_at
        ]
    );
    
    if ($result === false) {
        error_log('Flowguard: Failed to store session - ' . $wpdb->last_error);
        return false;
    }
    
    return $wpdb->insert_id;
}

/**
 * Update Flowguard session status
 * 
 * @param string $session_id Session ID
 * @param string $status New status
 * @param array $additional_data Additional data to update
 * @return bool True on success, false on error
 */
function flexpress_flowguard_update_session($session_id, $status, $additional_data = []) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_flowguard_sessions';
    
    $update_data = array_merge([
        'status' => $status
    ], $additional_data);
    
    $result = $wpdb->update(
        $table_name,
        $update_data,
        ['session_id' => $session_id],
        array_fill(0, count($update_data), '%s'),
        ['%s']
    );
    
    if ($result === false) {
        error_log('Flowguard: Failed to update session - ' . $wpdb->last_error);
        return false;
    }
    
    return true;
}

/**
 * Get Flowguard session by ID
 * 
 * @param string $session_id Session ID
 * @return array|false Session data or false if not found
 */
function flexpress_flowguard_get_session($session_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_flowguard_sessions';
    
    $session = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE session_id = %s",
            $session_id
        ),
        ARRAY_A
    );
    
    return $session ?: false;
}

/**
 * Clean up expired sessions
 * 
 * @return int Number of sessions cleaned up
 */
function flexpress_flowguard_cleanup_expired_sessions() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_flowguard_sessions';
    
    $result = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$table_name} WHERE expires_at < %s AND status = 'pending'",
            current_time('mysql')
        )
    );
    
    return $result;
}

/**
 * Get Flowguard transaction statistics
 * 
 * @param string $period Period (today, week, month, year)
 * @return array Statistics data
 */
function flexpress_flowguard_get_transaction_stats($period = 'month') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_flowguard_transactions';
    
    // Calculate date range based on period
    $date_format = '%Y-%m-%d';
    switch ($period) {
        case 'today':
            $date_condition = "DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'year':
            $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        default:
            $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    }
    
    // Get total transactions
    $total_transactions = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$table_name} WHERE {$date_condition}"
    );
    
    // Get successful transactions
    $successful_transactions = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$table_name} WHERE {$date_condition} AND status = 'approved'"
    );
    
    // Get total revenue
    $total_revenue = $wpdb->get_var(
        "SELECT SUM(amount) FROM {$table_name} WHERE {$date_condition} AND status = 'approved'"
    );
    
    // Get transactions by type
    $transactions_by_type = $wpdb->get_results(
        "SELECT order_type, COUNT(*) as count FROM {$table_name} WHERE {$date_condition} GROUP BY order_type",
        ARRAY_A
    );
    
    // Get transactions by status
    $transactions_by_status = $wpdb->get_results(
        "SELECT status, COUNT(*) as count FROM {$table_name} WHERE {$date_condition} GROUP BY status",
        ARRAY_A
    );
    
    return [
        'total_transactions' => intval($total_transactions),
        'successful_transactions' => intval($successful_transactions),
        'total_revenue' => floatval($total_revenue ?: 0),
        'success_rate' => $total_transactions > 0 ? round(($successful_transactions / $total_transactions) * 100, 2) : 0,
        'transactions_by_type' => $transactions_by_type,
        'transactions_by_status' => $transactions_by_status,
        'period' => $period
    ];
}

/**
 * Get Flowguard webhook statistics
 * 
 * @param string $period Period (today, week, month, year)
 * @return array Statistics data
 */
function flexpress_flowguard_get_webhook_stats($period = 'month') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'flexpress_flowguard_webhooks';
    
    // Calculate date range based on period
    switch ($period) {
        case 'today':
            $date_condition = "DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'year':
            $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        default:
            $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    }
    
    // Get total webhooks
    $total_webhooks = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$table_name} WHERE {$date_condition}"
    );
    
    // Get processed webhooks
    $processed_webhooks = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$table_name} WHERE {$date_condition} AND processed = 1"
    );
    
    // Get webhooks by event type
    $webhooks_by_event = $wpdb->get_results(
        "SELECT event_type, COUNT(*) as count FROM {$table_name} WHERE {$date_condition} GROUP BY event_type",
        ARRAY_A
    );
    
    return [
        'total_webhooks' => intval($total_webhooks),
        'processed_webhooks' => intval($processed_webhooks),
        'processing_rate' => $total_webhooks > 0 ? round(($processed_webhooks / $total_webhooks) * 100, 2) : 0,
        'webhooks_by_event' => $webhooks_by_event,
        'period' => $period
    ];
}

/**
 * Initialize Flowguard database on theme activation
 */
function flexpress_flowguard_init_database() {
    if (!flexpress_flowguard_tables_exist()) {
        flexpress_flowguard_create_tables();
    }
}

// Hook to create tables on theme activation
add_action('after_switch_theme', 'flexpress_flowguard_init_database');

// Schedule cleanup of expired sessions
if (!wp_next_scheduled('flexpress_flowguard_cleanup_sessions')) {
    wp_schedule_event(time(), 'hourly', 'flexpress_flowguard_cleanup_sessions');
}

add_action('flexpress_flowguard_cleanup_sessions', 'flexpress_flowguard_cleanup_expired_sessions');
