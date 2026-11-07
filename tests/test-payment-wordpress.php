<?php
/** Real WordPress/MySQL verification. Run ONLY against a disposable restored clone.
 * wp eval-file /verification/test-payment-wordpress.php
 * Requires DB_NAME=flexpress_verify, FLEXPRESS_ISOLATED_VERIFICATION=true and blocked external HTTP.
 */
if (!defined('WP_CLI') || !WP_CLI || !defined('FLEXPRESS_ISOLATED_VERIFICATION')
    || !FLEXPRESS_ISOLATED_VERIFICATION || DB_NAME !== 'flexpress_verify') {
    throw new RuntimeException('Refusing to run outside the isolated verification database.');
}
ini_set('error_log', '/tmp/payment-verification.log');
global $wpdb;
$checks = 0;
$assert = static function ($condition, $message) use (&$checks) {
    $checks++;
    if (!$condition) { throw new RuntimeException($message); }
};
// Every request is intercepted. The Docker network must also be --internal.
remove_all_filters('pre_http_request');
$http_calls = [];
$http_fail = false;
add_filter('pre_http_request', static function ($pre, $args, $url) use (&$http_calls, &$http_fail) {
    $http_calls[] = ['method' => $args['method'] ?? 'GET'];
    return ['headers' => [], 'body' => '{}', 'response' => ['code' => $http_fail ? 503 : 200, 'message' => 'Synthetic'], 'cookies' => []];
}, PHP_INT_MAX, 3);
add_filter('pre_wp_mail', '__return_true', PHP_INT_MAX);
update_option('flexpress_discord_settings', ['webhook_url' => 'https://example.invalid/discord', 'notify_subscriptions' => true, 'notify_rebills' => true, 'notify_cancellations' => true, 'notify_expirations' => true, 'notify_extensions' => true, 'notify_ppv' => true, 'notify_refunds' => true]);
update_option('flexpress_plunk_settings', []);
$user_id = wp_insert_user(['user_login' => 'release_check_' . wp_generate_password(8, false), 'user_pass' => wp_generate_password(32), 'user_email' => 'release-' . wp_generate_password(8, false) . '@example.invalid', 'role' => 'subscriber']);
$assert(!is_wp_error($user_id), 'Could not create isolated test user');
$http_calls = [];
$event = ['shopId' => 'isolated', 'orderType' => 'subscription', 'postbackType' => 'approved', 'referenceId' => 'user_' . $user_id . '_plan_monthly', 'saleId' => 'verify-sale-' . $user_id, 'transactionId' => 'verify-approval-' . $user_id, 'priceAmount' => '20.00', 'priceCurrency' => 'USD', 'subscriptionType' => 'recurring', 'subscriptionPhase' => 'normal', 'nextChargeOn' => '2099-01-01'];
$assert(flexpress_flowguard_process_postback($event)['result'] === 'processed', 'Subscription approval failed');
$assert(get_user_meta($user_id, 'membership_status', true) === 'active', 'Membership not active');
$assert(!$http_calls && count(get_user_meta($user_id, 'flowguard_pending_effects', true)) === 1, 'Notification was not deferred');
$assert(flexpress_flowguard_process_postback($event)['result'] === 'duplicate', 'Replay was not suppressed');
$http_fail = true;
flexpress_flowguard_deliver_effects($user_id);
$assert(count(get_user_meta($user_id, 'flowguard_pending_effects', true)) === 1, 'Failed notification lost');
$pending = get_user_meta($user_id, 'flowguard_pending_effects', true);
foreach ($pending as &$job) { $job['next_attempt'] = 0; }
unset($job);
update_user_meta($user_id, 'flowguard_pending_effects', $pending);
$http_fail = false;
flexpress_flowguard_deliver_effects($user_id);
$assert(!get_user_meta($user_id, 'flowguard_pending_effects', true) && count($http_calls) === 2, 'Notification retry failed');
$before = get_user_meta($user_id);
$failed = array_merge($event, ['postbackType' => 'rebill', 'transactionId' => 'verify-rollback-' . $user_id, 'nextChargeOn' => '2099-02-01']);
$trigger = 'flexpress_verify_receipt_failure';
$wpdb->query("CREATE TRIGGER $trigger BEFORE INSERT ON {$wpdb->prefix}flexpress_flowguard_webhooks FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Synthetic receipt failure'");
$assert(!$wpdb->last_error, 'Could not install isolated failure trigger');
$wpdb->suppress_errors(true);
try {
    $assert(flexpress_flowguard_process_postback($failed)['status'] === 503, 'Write failure was acknowledged');
} finally {
    $wpdb->query("DROP TRIGGER $trigger");
    $wpdb->suppress_errors(false);
}
wp_cache_delete($user_id, 'user_meta');
$assert(get_user_meta($user_id) === $before, 'Real MySQL rollback left metadata changed');
$assert(!flexpress_flowguard_get_transaction($failed['transactionId']), 'Real MySQL rollback left payment committed');
$assert(count($http_calls) === 2, 'Rolled-back payment sent an external request');
$assert(flexpress_flowguard_process_postback($failed)['result'] === 'processed', 'Retry after DB failure failed');
foreach (['episode', 'extras'] as $type) {
    $post_id = wp_insert_post(['post_title' => 'Synthetic ' . $type, 'post_type' => $type, 'post_status' => 'publish']);
    $purchase = array_merge($event, ['orderType' => 'purchase', 'referenceId' => 'ppv_' . ($type === 'extras' ? 'ex' : 'ep') . $post_id . '_uid' . $user_id . '_test', 'saleId' => 'verify-' . $type . '-sale', 'transactionId' => 'verify-' . $type . '-' . $user_id]);
    $assert(flexpress_flowguard_process_postback($purchase)['result'] === 'processed', $type . ' purchase failed');
    $assert(in_array($post_id, (array) get_user_meta($user_id, 'ppv_purchases', true)), $type . ' not unlocked');
    $assert(get_user_meta($user_id, 'flowguard_subscriber_id', true) === $event['saleId'], 'PPV changed subscription ID');
}
$cancel = array_merge($event, ['postbackType' => 'cancel', 'expiresOn' => '2099-02-01']);
unset($cancel['transactionId']);
$assert(flexpress_flowguard_process_postback($cancel)['result'] === 'processed', 'Cancellation failed');
$assert(get_user_meta($user_id, 'membership_status', true) === 'cancelled', 'Cancellation status lost');
$refund = array_merge($event, ['postbackType' => 'credit', 'transactionId' => 'verify-refund-' . $user_id, 'saleId' => 'older-sale']);
$assert(flexpress_flowguard_process_postback($refund)['result'] === 'processed', 'Refund failed');
$assert(get_user_meta($user_id, 'flowguard_subscriber_id', true) === $event['saleId'], 'Refund replaced current subscription ID');
flexpress_flowguard_deliver_effects($user_id);
$assert(get_user_meta($user_id, 'membership_status', true) === 'banned', 'Deferred cancellation lifted ban');
echo "Isolated WordPress/MySQL tests passed ($checks assertions). No live HTTP or email sent.\n";
