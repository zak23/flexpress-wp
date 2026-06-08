<?php
/**
 * Manual smoke test for the FlexPress affiliate v1 lifecycle.
 *
 * Run from the project root on a configured WordPress install:
 * php tests/manual/test-affiliate-v1.php
 */

$wp_load = dirname(__DIR__, 2) . '/wp-load.php';
if (!file_exists($wp_load)) {
    echo "SKIP: wp-load.php not found. Run this inside a configured WordPress install.\n";
    exit(0);
}

require_once $wp_load;

function affiliate_v1_log($message) {
    echo $message . "\n";
}

function affiliate_v1_assert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    affiliate_v1_log('OK: ' . $message);
}

global $wpdb;

if (!function_exists('flexpress_register_affiliate')) {
    throw new RuntimeException('Affiliate helpers are not loaded.');
}

update_option('flexpress_affiliate_settings', array(
    'module_enabled' => 1,
    'commission_rate' => 25,
    'rebill_commission_rate' => 10,
    'unlock_commission_rate' => 15,
    'minimum_payout' => 10,
));

flexpress_affiliate_create_tables();

$suffix = time();
$email = "affiliate-smoke-{$suffix}@example.test";
$code = "SMOKE{$suffix}";
$affiliate_id = 0;
$user_id = 0;
$payout_id = 0;

try {
    $created = flexpress_register_affiliate(array(
        'display_name' => 'Affiliate Smoke',
        'email' => $email,
        'affiliate_code' => $code,
        'payout_method' => 'paypal',
        'payout_details' => wp_json_encode(array('paypal_email' => $email)),
    ));
    affiliate_v1_assert(!is_wp_error($created), 'Affiliate application can be created');
    $affiliate_id = intval($created['id']);

    $affiliate = flexpress_get_affiliate_by_id($affiliate_id);
    $user_id = flexpress_link_or_create_affiliate_user($affiliate);
    affiliate_v1_assert(!is_wp_error($user_id) && $user_id > 0, 'Approved affiliate can be linked to a WP user');

    $wpdb->update(
        $wpdb->prefix . 'flexpress_affiliates',
        array('status' => 'active', 'user_id' => intval($user_id)),
        array('id' => $affiliate_id),
        array('%s', '%d'),
        array('%d')
    );

    $commission_created = flexpress_process_affiliate_commission(
        $affiliate_id,
        intval($user_id),
        'initial',
        'smoke-tx-' . $suffix,
        'smoke-plan',
        100.00
    );
    affiliate_v1_assert($commission_created, 'Initial Flowguard commission can be created');

    $duplicate_created = flexpress_process_affiliate_commission(
        $affiliate_id,
        intval($user_id),
        'initial',
        'smoke-tx-' . $suffix,
        'smoke-plan',
        100.00
    );
    affiliate_v1_assert(!$duplicate_created, 'Duplicate Flowguard commission is rejected');

    $transaction_id = intval($wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}flexpress_affiliate_transactions WHERE affiliate_id = %d",
        $affiliate_id
    )));
    affiliate_v1_assert(flexpress_approve_affiliate_commission($transaction_id), 'Pending commission can be approved');

    $payout_id = flexpress_process_affiliate_payout($affiliate_id, array());
    affiliate_v1_assert($payout_id > 0, 'Payout request creates a pending payout');

    $status = $wpdb->get_var($wpdb->prepare(
        "SELECT status FROM {$wpdb->prefix}flexpress_affiliate_transactions WHERE id = %d",
        $transaction_id
    ));
    affiliate_v1_assert($status === 'approved', 'Payout request does not mark transaction paid');

    affiliate_v1_assert(flexpress_complete_affiliate_payout($payout_id, 'smoke-ref'), 'Admin completion marks payout complete');

    $status = $wpdb->get_var($wpdb->prepare(
        "SELECT status FROM {$wpdb->prefix}flexpress_affiliate_transactions WHERE id = %d",
        $transaction_id
    ));
    affiliate_v1_assert($status === 'paid', 'Completed payout marks covered transaction paid');
} finally {
    if ($payout_id) {
        $wpdb->delete($wpdb->prefix . 'flexpress_affiliate_payouts', array('id' => $payout_id), array('%d'));
    }
    if ($affiliate_id) {
        $wpdb->delete($wpdb->prefix . 'flexpress_affiliate_transactions', array('affiliate_id' => $affiliate_id), array('%d'));
        $wpdb->delete($wpdb->prefix . 'flexpress_affiliates', array('id' => $affiliate_id), array('%d'));
    }
    if ($user_id) {
        wp_delete_user($user_id);
    }
}

affiliate_v1_log('Done.');
