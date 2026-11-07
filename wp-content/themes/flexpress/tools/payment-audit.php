<?php
/** Read-only payment audit: wp eval-file wp-content/themes/flexpress/tools/payment-audit.php --skip-plugins --skip-themes */
if (!defined('WP_CLI') || !WP_CLI || !defined('ABSPATH')) {
    http_response_code(403);
    exit;
}
global $wpdb;
$transactions = $wpdb->prefix . 'flexpress_flowguard_transactions';
$webhooks = $wpdb->prefix . 'flexpress_flowguard_webhooks';
$users = $wpdb->get_col("SELECT ID FROM {$wpdb->users}");
$founders = array_map('intval', (array) get_option('flexpress_founder_user_ids', []));
$report = [
    'generated_at_utc' => gmdate('c'),
    'read_only' => true,
    'user_count' => count($users),
    'membership_counts' => [],
    'review' => [],
    'notes' => ['Missing local payment evidence does not prove unpaid access: imported, manually granted, and trial accounts require review.'],
];
$exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($transactions))) === $transactions;
foreach ($users as $user_id) {
    $user_id = (int) $user_id;
    $meta = get_user_meta($user_id);
    $value = static function ($key) use ($meta) { return isset($meta[$key][0]) ? maybe_unserialize($meta[$key][0]) : ''; };
    $status = $value('membership_status') ?: 'none';
    $report['membership_counts'][$status] = ($report['membership_counts'][$status] ?? 0) + 1;
    $issues = [];
    if (count($meta['membership_status'] ?? []) > 1) {
        $issues[] = 'duplicate_membership_status_rows';
    }
    $sale = $value('flowguard_subscriber_id') ?: $value('flowguard_sale_id');
    $paid = $exists ? (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $transactions WHERE user_id = %d AND order_type = 'subscription' AND status IN ('approved','rebill')", $user_id)) : 0;
    if (in_array($status, ['active', 'cancelled'], true) && !in_array($user_id, $founders, true)) {
        if (!$paid && !$value('trial_expires_at')) {
            $issues[] = 'membership_without_local_payment_or_trial';
        }
        foreach (['membership_expires', 'trial_expires_at', 'next_rebill_date'] as $key) {
            $date = $value($key);
            if (!$date) { continue; }
            $timestamp = is_string($date) ? strtotime($date) : false;
            if (!$timestamp) { $issues[] = 'invalid_' . $key; }
        }
        $deadline = $value('membership_expires');
        if ($deadline && strtotime($deadline) && strtotime($deadline) <= current_time('timestamp')) {
            $issues[] = 'past_explicit_expiry';
        }
    }
    if ($exists && $sale && $wpdb->get_var($wpdb->prepare("SELECT id FROM $transactions WHERE user_id = %d AND sale_id = %s AND order_type = 'purchase' LIMIT 1", $user_id, $sale))) {
        $issues[] = 'subscription_id_matches_ppv_sale';
    }
    $purchases = array_unique(array_merge((array) ($value('purchased_episodes') ?: []), (array) ($value('purchased_extras') ?: []), (array) ($value('ppv_purchases') ?: [])));
    if ($purchases && (!$exists || !$wpdb->get_var($wpdb->prepare("SELECT id FROM $transactions WHERE user_id = %d AND order_type = 'purchase' AND status = 'approved' LIMIT 1", $user_id)))) {
        $issues[] = 'ppv_access_without_local_purchase_records';
    }
    if ($issues) { $report['review'][] = ['user_id' => $user_id, 'status' => $status, 'issues' => $issues]; }
}
$report['orphan_membership_meta_rows'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} m LEFT JOIN {$wpdb->users} u ON u.ID=m.user_id WHERE u.ID IS NULL AND m.meta_key='membership_status'");
$report['transaction_counts'] = $exists ? $wpdb->get_results("SELECT order_type,status,COUNT(*) AS count FROM $transactions GROUP BY order_type,status", ARRAY_A) : [];
$report['orphan_transactions'] = $exists ? (int) $wpdb->get_var("SELECT COUNT(*) FROM $transactions t LEFT JOIN {$wpdb->users} u ON u.ID=t.user_id WHERE u.ID IS NULL") : 0;
$report['storage_engines'] = $wpdb->get_results($wpdb->prepare("SELECT TABLE_NAME AS table_name, ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (%s,%s,%s,%s)", $wpdb->usermeta, $wpdb->options, $transactions, $webhooks), ARRAY_A);
$report['database_error'] = $wpdb->last_error ? 'Query failed; investigate locally.' : null;
echo wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
