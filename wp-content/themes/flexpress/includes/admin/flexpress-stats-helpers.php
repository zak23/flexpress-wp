<?php
/**
 * FlexPress Stats Helpers
 *
 * Database query functions for admin stats dashboard
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Build date WHERE clause for time range filtering
 *
 * @param string $time_range Time range: 'today', 'this_week', 'this_month', 'this_year', 'all_time', 'custom'
 * @param string $custom_from Custom date from (Y-m-d format) - used when time_range is 'custom'
 * @param string $custom_to Custom date to (Y-m-d format) - used when time_range is 'custom'
 * @param string $date_column Column name to filter on (default: 'created_at')
 * @return string SQL WHERE clause
 */
function flexpress_stats_build_date_clause($time_range, $custom_from = '', $custom_to = '', $date_column = 'created_at')
{
    global $wpdb;

    // Sanitize column name to prevent SQL injection
    $date_column = esc_sql($date_column);

    $where = '';

    switch ($time_range) {
        case 'today':
            $where = "DATE($date_column) = CURDATE()";
            break;

        case 'this_week':
            $where = "$date_column >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;

        case 'this_month':
            $where = "$date_column >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;

        case 'this_year':
            $where = "YEAR($date_column) = YEAR(NOW())";
            break;

        case 'custom':
            if (!empty($custom_from) && !empty($custom_to)) {
                // Sanitize dates (should be Y-m-d format)
                $custom_from = esc_sql($custom_from);
                $custom_to = esc_sql($custom_to);
                $where = "DATE($date_column) BETWEEN '$custom_from' AND '$custom_to'";
            } else {
                $where = '1=1';
            }
            break;

        case 'all_time':
        default:
            // No date filter
            $where = '1=1';
            break;
    }

    return $where;
}

/**
 * Get sales statistics
 *
 * @param string $time_range Time range: 'today', 'this_week', 'this_month', 'this_year', 'all_time', 'custom'
 * @param string $custom_from Custom date from (Y-m-d format)
 * @param string $custom_to Custom date to (Y-m-d format)
 * @return array Statistics array
 */
function flexpress_get_sales_stats($time_range = 'all_time', $custom_from = '', $custom_to = '')
{
    global $wpdb;

    $cache_key = 'flexpress_sales_stats_' . md5($time_range . $custom_from . $custom_to);
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    $transactions_table = $wpdb->prefix . 'flexpress_flowguard_transactions';
    $date_where = flexpress_stats_build_date_clause($time_range, $custom_from, $custom_to, 'created_at');

    // Get total sales stats
    $stats = $wpdb->get_row(
        "SELECT 
            COUNT(*) as total_count,
            SUM(amount) as total_amount,
            AVG(amount) as avg_amount,
            MIN(amount) as min_amount,
            MAX(amount) as max_amount
        FROM $transactions_table
        WHERE status = 'approved' 
        AND $date_where
    ",
        ARRAY_A
    );

    // Get count by order type
    $order_types = $wpdb->get_results(
        "SELECT 
            order_type,
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM $transactions_table
        WHERE status = 'approved'
        AND $date_where
        GROUP BY order_type
    ",
        ARRAY_A
    );

    $subscription_count = 0;
    $subscription_amount = 0;
    $purchase_count = 0;
    $purchase_amount = 0;

    foreach ($order_types as $type) {
        if ($type['order_type'] === 'subscription') {
            $subscription_count = intval($type['count']);
            $subscription_amount = floatval($type['total_amount']);
        } elseif ($type['order_type'] === 'purchase') {
            $purchase_count = intval($type['count']);
            $purchase_amount = floatval($type['total_amount']);
        }
    }

    // Get previous period comparison for this week, month, year
    $previous_comparison = null;
    if (in_array($time_range, ['this_week', 'this_month', 'this_year'])) {
        $prev_date_where = '';
        switch ($time_range) {
            case 'this_week':
                $prev_date_where = "$transactions_table.created_at >= DATE_SUB(DATE_SUB(NOW(), INTERVAL 7 DAY), INTERVAL 7 DAY) 
                     AND $transactions_table.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'this_month':
                $prev_date_where = "$transactions_table.created_at >= DATE_SUB(DATE_SUB(NOW(), INTERVAL 30 DAY), INTERVAL 30 DAY) 
                     AND $transactions_table.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'this_year':
                $prev_date_where = "YEAR($transactions_table.created_at) = YEAR(NOW()) - 1";
                break;
        }

        if (!empty($prev_date_where)) {
            $prev_stats = $wpdb->get_row(
                "SELECT 
                    COUNT(*) as total_count,
                    SUM(amount) as total_amount
                FROM $transactions_table
                WHERE status = 'approved' 
                AND $prev_date_where
            ",
                ARRAY_A
            );

            if ($prev_stats) {
                $current_amount = floatval($stats['total_amount'] ?: 0);
                $prev_amount = floatval($prev_stats['total_amount'] ?: 0);
                $current_count = intval($stats['total_count'] ?: 0);
                $prev_count = intval($prev_stats['total_count'] ?: 0);

                $amount_change = $prev_amount > 0 ? (($current_amount - $prev_amount) / $prev_amount) * 100 : 0;
                $count_change = $prev_count > 0 ? (($current_count - $prev_count) / $prev_count) * 100 : 0;

                $previous_comparison = [
                    'amount_change' => round($amount_change, 2),
                    'count_change' => round($count_change, 2),
                    'prev_amount' => $prev_amount,
                    'prev_count' => $prev_count,
                ];
            }
        }
    }

    $result = [
        'total_amount' => floatval($stats['total_amount'] ?: 0),
        'total_count' => intval($stats['total_count'] ?: 0),
        'avg_amount' => floatval($stats['avg_amount'] ?: 0),
        'min_amount' => floatval($stats['min_amount'] ?: 0),
        'max_amount' => floatval($stats['max_amount'] ?: 0),
        'subscription_count' => $subscription_count,
        'subscription_amount' => $subscription_amount,
        'purchase_count' => $purchase_count,
        'purchase_amount' => $purchase_amount,
        'previous_comparison' => $previous_comparison,
        'time_range' => $time_range,
    ];

    // Cache for 5 minutes
    set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);

    return $result;
}

/**
 * Get free trial statistics
 *
 * @param string $time_range Time range: 'today', 'this_week', 'this_month', 'this_year', 'all_time', 'custom'
 * @param string $custom_from Custom date from (Y-m-d format)
 * @param string $custom_to Custom date to (Y-m-d format)
 * @return array Statistics array
 */
function flexpress_get_trial_stats($time_range = 'all_time', $custom_from = '', $custom_to = '')
{
    global $wpdb;

    $cache_key = 'flexpress_trial_stats_' . md5($time_range . $custom_from . $custom_to);
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    $trial_links_table = $wpdb->prefix . 'flexpress_trial_links';
    $date_where = flexpress_stats_build_date_clause($time_range, $custom_from, $custom_to, 'created_at');

    // Get trial links stats
    $links_stats = $wpdb->get_row(
        "SELECT 
            COUNT(*) as total_created,
            SUM(use_count) as total_uses,
            SUM(CASE WHEN use_count > 0 THEN 1 ELSE 0 END) as links_used,
            AVG(use_count) as avg_uses_per_link
        FROM $trial_links_table
        WHERE $date_where
    ",
        ARRAY_A
    );

    // Get active trials (users with trial_expires_at in future)
    $active_trials = $wpdb->get_var(
        "SELECT COUNT(DISTINCT user_id)
        FROM {$wpdb->usermeta}
        WHERE meta_key = 'trial_expires_at'
        AND CAST(meta_value AS DATETIME) > NOW()
    "
    );

    // Get trial conversions (users who had trial and now have active membership)
    $conversions = $wpdb->get_var(
        "SELECT COUNT(DISTINCT u1.user_id)
        FROM {$wpdb->usermeta} u1
        INNER JOIN {$wpdb->usermeta} u2 ON u1.user_id = u2.user_id
        WHERE u1.meta_key = 'trial_expires_at'
        AND u2.meta_key = 'membership_status'
        AND u2.meta_value = 'active'
        AND CAST(u1.meta_value AS DATETIME) <= NOW()
    "
    );

    // Get trial link usage rate
    $total_created = intval($links_stats['total_created'] ?: 0);
    $total_uses = intval($links_stats['total_uses'] ?: 0);
    $links_used = intval($links_stats['links_used'] ?: 0);
    $usage_rate = $total_created > 0 ? ($links_used / $total_created) * 100 : 0;
    $avg_uses_per_link = floatval($links_stats['avg_uses_per_link'] ?: 0);

    $result = [
        'total_created' => $total_created,
        'total_uses' => $total_uses,
        'links_used' => $links_used,
        'links_unused' => $total_created - $links_used,
        'usage_rate' => round($usage_rate, 2),
        'avg_uses_per_link' => round($avg_uses_per_link, 2),
        'active_trials' => intval($active_trials ?: 0),
        'conversions' => intval($conversions ?: 0),
        'time_range' => $time_range,
    ];

    // Cache for 5 minutes
    set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);

    return $result;
}

/**
 * Get rebill statistics
 *
 * @param string $time_range Time range: 'today', 'this_week', 'this_month', 'this_year', 'all_time', 'custom'
 * @param string $custom_from Custom date from (Y-m-d format)
 * @param string $custom_to Custom date to (Y-m-d format)
 * @return array Statistics array
 */
function flexpress_get_rebill_stats($time_range = 'all_time', $custom_from = '', $custom_to = '')
{
    global $wpdb;

    $cache_key = 'flexpress_rebill_stats_' . md5($time_range . $custom_from . $custom_to);
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    $transactions_table = $wpdb->prefix . 'flexpress_flowguard_transactions';
    $date_where = flexpress_stats_build_date_clause($time_range, $custom_from, $custom_to, 'created_at');

    // Get rebill stats
    $stats = $wpdb->get_row(
        "SELECT 
            COUNT(*) as total_count,
            SUM(amount) as total_amount,
            AVG(amount) as avg_amount,
            MIN(amount) as min_amount,
            MAX(amount) as max_amount,
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(DISTINCT sale_id) as unique_subscriptions
        FROM $transactions_table
        WHERE status = 'rebill'
        AND $date_where
    ",
        ARRAY_A
    );

    // Get previous period comparison
    $previous_comparison = null;
    if (in_array($time_range, ['this_week', 'this_month', 'this_year'])) {
        $prev_date_where = '';
        switch ($time_range) {
            case 'this_week':
                $prev_date_where = "$transactions_table.created_at >= DATE_SUB(DATE_SUB(NOW(), INTERVAL 7 DAY), INTERVAL 7 DAY) 
                     AND $transactions_table.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'this_month':
                $prev_date_where = "$transactions_table.created_at >= DATE_SUB(DATE_SUB(NOW(), INTERVAL 30 DAY), INTERVAL 30 DAY) 
                     AND $transactions_table.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'this_year':
                $prev_date_where = "YEAR($transactions_table.created_at) = YEAR(NOW()) - 1";
                break;
        }

        if (!empty($prev_date_where)) {
            $prev_stats = $wpdb->get_row(
                "SELECT 
                    COUNT(*) as total_count,
                    SUM(amount) as total_amount
                FROM $transactions_table
                WHERE status = 'rebill'
                AND $prev_date_where
            ",
                ARRAY_A
            );

            if ($prev_stats) {
                $current_amount = floatval($stats['total_amount'] ?: 0);
                $prev_amount = floatval($prev_stats['total_amount'] ?: 0);
                $current_count = intval($stats['total_count'] ?: 0);
                $prev_count = intval($prev_stats['total_count'] ?: 0);

                $amount_change = $prev_amount > 0 ? (($current_amount - $prev_amount) / $prev_amount) * 100 : 0;
                $count_change = $prev_count > 0 ? (($current_count - $prev_count) / $prev_count) * 100 : 0;

                $previous_comparison = [
                    'amount_change' => round($amount_change, 2),
                    'count_change' => round($count_change, 2),
                    'prev_amount' => $prev_amount,
                    'prev_count' => $prev_count,
                ];
            }
        }
    }

    // Calculate rebill success rate (rebills vs expected based on active subscriptions)
    // This is a simplified calculation - in reality, you'd need to know expected rebill schedule
    $rebill_success_rate = null; // Can be calculated separately if needed

    $result = [
        'total_count' => intval($stats['total_count'] ?: 0),
        'total_amount' => floatval($stats['total_amount'] ?: 0),
        'avg_amount' => floatval($stats['avg_amount'] ?: 0),
        'min_amount' => floatval($stats['min_amount'] ?: 0),
        'max_amount' => floatval($stats['max_amount'] ?: 0),
        'unique_users' => intval($stats['unique_users'] ?: 0),
        'unique_subscriptions' => intval($stats['unique_subscriptions'] ?: 0),
        'previous_comparison' => $previous_comparison,
        'time_range' => $time_range,
    ];

    // Cache for 5 minutes
    set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);

    return $result;
}

/**
 * Get rating statistics
 *
 * @param string $time_range Time range: 'today', 'this_week', 'this_month', 'this_year', 'all_time', 'custom'
 * @param string $custom_from Custom date from (Y-m-d format)
 * @param string $custom_to Custom date to (Y-m-d format)
 * @return array Statistics array
 */
function flexpress_get_rating_stats($time_range = 'all_time', $custom_from = '', $custom_to = '')
{
    global $wpdb;

    $cache_key = 'flexpress_rating_stats_' . md5($time_range . $custom_from . $custom_to);
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    $ratings_table = $wpdb->prefix . 'flexpress_episode_ratings';
    $date_where = flexpress_stats_build_date_clause($time_range, $custom_from, $custom_to, 'created_at');

    // Get overall rating stats
    $stats = $wpdb->get_row(
        "SELECT 
            COUNT(*) as total_count,
            AVG(rating) as avg_rating,
            MIN(rating) as min_rating,
            MAX(rating) as max_rating,
            COUNT(DISTINCT episode_id) as episodes_rated,
            COUNT(DISTINCT user_id) as users_rated
        FROM $ratings_table
        WHERE $date_where
    ",
        ARRAY_A
    );

    // Get rating distribution (1-5 stars)
    $distribution = $wpdb->get_results(
        "SELECT 
            rating,
            COUNT(*) as count
        FROM $ratings_table
        WHERE $date_where
        GROUP BY rating
        ORDER BY rating ASC
    ",
        ARRAY_A
    );

    // Build distribution array (ensure all 1-5 are represented)
    $rating_distribution = [
        '1' => 0,
        '2' => 0,
        '3' => 0,
        '4' => 0,
        '5' => 0,
    ];

    foreach ($distribution as $dist) {
        $rating_distribution[strval($dist['rating'])] = intval($dist['count']);
    }

    // Get previous period comparison
    $previous_comparison = null;
    if (in_array($time_range, ['this_week', 'this_month', 'this_year'])) {
        $prev_date_where = '';
        switch ($time_range) {
            case 'this_week':
                $prev_date_where = "$ratings_table.created_at >= DATE_SUB(DATE_SUB(NOW(), INTERVAL 7 DAY), INTERVAL 7 DAY) 
                     AND $ratings_table.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'this_month':
                $prev_date_where = "$ratings_table.created_at >= DATE_SUB(DATE_SUB(NOW(), INTERVAL 30 DAY), INTERVAL 30 DAY) 
                     AND $ratings_table.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'this_year':
                $prev_date_where = "YEAR($ratings_table.created_at) = YEAR(NOW()) - 1";
                break;
        }

        if (!empty($prev_date_where)) {
            $prev_stats = $wpdb->get_row(
                "SELECT 
                    COUNT(*) as total_count,
                    AVG(rating) as avg_rating
                FROM $ratings_table
                WHERE $prev_date_where
            ",
                ARRAY_A
            );

            if ($prev_stats) {
                $current_count = intval($stats['total_count'] ?: 0);
                $prev_count = intval($prev_stats['total_count'] ?: 0);
                $current_avg = floatval($stats['avg_rating'] ?: 0);
                $prev_avg = floatval($prev_stats['avg_rating'] ?: 0);

                $count_change = $prev_count > 0 ? (($current_count - $prev_count) / $prev_count) * 100 : 0;
                $avg_change = $prev_avg > 0 ? (($current_avg - $prev_avg) / $prev_avg) * 100 : 0;

                $previous_comparison = [
                    'count_change' => round($count_change, 2),
                    'avg_change' => round($avg_change, 2),
                    'prev_count' => $prev_count,
                    'prev_avg' => $prev_avg,
                ];
            }
        }
    }

    $result = [
        'total_count' => intval($stats['total_count'] ?: 0),
        'avg_rating' => round(floatval($stats['avg_rating'] ?: 0), 2),
        'min_rating' => intval($stats['min_rating'] ?: 0),
        'max_rating' => intval($stats['max_rating'] ?: 0),
        'episodes_rated' => intval($stats['episodes_rated'] ?: 0),
        'users_rated' => intval($stats['users_rated'] ?: 0),
        'distribution' => $rating_distribution,
        'previous_comparison' => $previous_comparison,
        'time_range' => $time_range,
    ];

    // Cache for 5 minutes
    set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);

    return $result;
}

