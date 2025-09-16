<?php
/**
 * FlexPress Activity Logger
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Activity Logger Class
 */
class FlexPress_Activity_Logger {
    
    /**
     * Database table name
     */
    private static $table_name = 'flexpress_user_activity';
    
    /**
     * Initialize the activity logger
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'create_activity_table'));
    }
    
    /**
     * Create the activity log table
     */
    public static function create_activity_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_description text NOT NULL,
            event_data longtext,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Log a user activity event
     *
     * @param int $user_id User ID
     * @param string $event_type Type of event (e.g., 'subscription_created', 'payment_received', etc.)
     * @param string $event_description Human-readable description of the event
     * @param array $event_data Additional data about the event
     * @return bool|int False on failure, insert ID on success
     */
    public static function log_activity($user_id, $event_type, $event_description, $event_data = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        // Get IP address
        $ip_address = self::get_client_ip();
        
        // Get user agent
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => intval($user_id),
                'event_type' => sanitize_text_field($event_type),
                'event_description' => sanitize_textarea_field($event_description),
                'event_data' => wp_json_encode($event_data),
                'ip_address' => sanitize_text_field($ip_address),
                'user_agent' => sanitize_textarea_field($user_agent),
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('FlexPress Activity Logger: Failed to insert activity log for user ' . $user_id . ': ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get activity logs for a specific user
     *
     * @param int $user_id User ID
     * @param int $limit Number of records to retrieve (default: 50)
     * @param int $offset Offset for pagination (default: 0)
     * @return array Array of activity log records
     */
    public static function get_user_activity($user_id, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $user_id,
            $limit,
            $offset
        ));
        
        // Decode JSON data for each result
        foreach ($results as &$result) {
            $result->event_data = json_decode($result->event_data, true);
        }
        
        return $results;
    }
    
    /**
     * Get all activity logs with optional filtering
     *
     * @param array $args Query arguments
     * @return array Array of activity log records
     */
    public static function get_activity_logs($args = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $defaults = array(
            'user_id' => null,
            'event_type' => null,
            'limit' => 50,
            'offset' => 0,
            'date_from' => null,
            'date_to' => null,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        if (!empty($args['user_id'])) {
            $where_clauses[] = 'user_id = %d';
            $where_values[] = intval($args['user_id']);
        }
        
        if (!empty($args['event_type'])) {
            $where_clauses[] = 'event_type = %s';
            $where_values[] = sanitize_text_field($args['event_type']);
        }
        
        if (!empty($args['date_from'])) {
            $where_clauses[] = 'created_at >= %s';
            $where_values[] = sanitize_text_field($args['date_from']);
        }
        
        if (!empty($args['date_to'])) {
            $where_clauses[] = 'created_at <= %s';
            $where_values[] = sanitize_text_field($args['date_to']);
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'created_at DESC';
        }
        
        $sql = "SELECT * FROM $table_name $where_sql ORDER BY $orderby LIMIT %d OFFSET %d";
        $where_values[] = intval($args['limit']);
        $where_values[] = intval($args['offset']);
        
        if (!empty($where_values)) {
            $results = $wpdb->get_results($wpdb->prepare($sql, $where_values));
        } else {
            $results = $wpdb->get_results($sql);
        }
        
        // Decode JSON data for each result
        foreach ($results as &$result) {
            $result->event_data = json_decode($result->event_data, true);
        }
        
        return $results;
    }
    
    /**
     * Get count of activity logs for a user
     *
     * @param int $user_id User ID
     * @return int Count of activity logs
     */
    public static function get_user_activity_count($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Delete old activity logs (cleanup)
     *
     * @param int $days_old Delete logs older than this many days (default: 365)
     * @return int Number of deleted records
     */
    public static function cleanup_old_logs($days_old = 365) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            $cutoff_date
        ));
        
        return $deleted;
    }
    
    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }
    
    /**
     * Log Verotel webhook events
     *
     * @param int $user_id User ID
     * @param string $event Verotel event type
     * @param array $webhook_data Webhook data
     */
    public static function log_verotel_event($user_id, $event, $webhook_data = array()) {
        $event_descriptions = array(
            'initial' => 'New subscription created',
            'rebill' => 'Subscription renewed (recurring payment)',
            'cancellation' => 'Subscription cancelled',
            'uncancel' => 'Subscription reactivated',
            'extend' => 'Subscription extended',
            'expiration' => 'Subscription expired',
            'downgrade' => 'Subscription downgraded',
            'upgrade' => 'Subscription upgraded',
            'credit' => 'Refund processed',
            'chargeback' => 'Chargeback received'
        );
        
        $description = isset($event_descriptions[$event]) ? $event_descriptions[$event] : "Verotel event: {$event}";
        
        // Add amount and currency to description if available
        if (isset($webhook_data['priceAmount']) && isset($webhook_data['priceCurrency'])) {
            $description .= " - {$webhook_data['priceCurrency']} {$webhook_data['priceAmount']}";
        }
        
        // Add next rebill date if available
        if (isset($webhook_data['nextChargeOn'])) {
            $description .= " (Next rebill: {$webhook_data['nextChargeOn']})";
        }
        
        self::log_activity(
            $user_id,
            'verotel_' . $event,
            $description,
            $webhook_data
        );
    }
    
    /**
     * Log user registration events
     *
     * @param int $user_id User ID
     * @param string $plan_type Plan type
     * @param array $registration_data Registration data
     */
    public static function log_registration($user_id, $plan_type = '', $registration_data = array()) {
        $description = 'User account created';
        if (!empty($plan_type)) {
            $description .= " with {$plan_type} plan";
        }
        
        self::log_activity(
            $user_id,
            'user_registered',
            $description,
            $registration_data
        );
    }
    
    /**
     * Log membership status changes
     *
     * @param int $user_id User ID
     * @param string $old_status Old status
     * @param string $new_status New status
     * @param string $reason Reason for change
     */
    public static function log_membership_change($user_id, $old_status, $new_status, $reason = '') {
        $description = "Membership status changed from '{$old_status}' to '{$new_status}'";
        if (!empty($reason)) {
            $description .= " - {$reason}";
        }
        
        self::log_activity(
            $user_id,
            'membership_status_change',
            $description,
            array(
                'old_status' => $old_status,
                'new_status' => $new_status,
                'reason' => $reason
            )
        );
    }
    
    /**
     * Log PPV episode purchase
     *
     * @param int $user_id User ID
     * @param int $episode_id Episode ID
     * @param array $purchase_data Purchase data
     * @param string $method Purchase method (return, webhook, etc.)
     */
    public static function log_ppv_purchase($user_id, $episode_id, $purchase_data = array(), $method = 'return') {
        $episode_title = get_the_title($episode_id);
        $event_type = ($method === 'webhook') ? 'ppv_purchase_confirmed' : 'ppv_purchase';
        
        // Build description
        $description = ($method === 'webhook') ? "PPV Purchase confirmed: {$episode_title}" : "Purchased episode: {$episode_title}";
        
        // Add discount info if applicable
        if (!empty($purchase_data['discount_applied']) && !empty($purchase_data['member_discount'])) {
            $description .= " (Member discount: {$purchase_data['member_discount']}%)";
        }
        
        // Add price info
        if (!empty($purchase_data['final_price']) || !empty($purchase_data['paid_amount'])) {
            $amount = !empty($purchase_data['paid_amount']) ? $purchase_data['paid_amount'] : $purchase_data['final_price'];
            $currency = !empty($purchase_data['currency']) ? $purchase_data['currency'] : '$';
            $currency_symbol = ($currency === 'USD') ? '$' : $currency . ' ';
            $description .= " - {$currency_symbol}" . number_format($amount, 2);
        }
        
        // Prepare event data
        $event_data = array_merge(array(
            'episode_id' => $episode_id,
            'episode_title' => $episode_title,
            'purchase_method' => $method
        ), $purchase_data);
        
        self::log_activity(
            $user_id,
            $event_type,
            $description,
            $event_data
        );
    }
}

// Initialize the activity logger
FlexPress_Activity_Logger::init(); 