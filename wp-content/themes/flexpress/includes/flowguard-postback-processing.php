<?php
/** Serialized, transactional processing for authenticated Flowguard postbacks. */
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/flowguard-effects.php';

/**
 * Apply one verified event. Authentication belongs to the HTTP handler.
 *
 * Monetary events are deduplicated durably by event type and transaction ID.
 * Lifecycle notices without transaction IDs are deduplicated against the last
 * notice for that sale, allowing a genuine cancel/uncancel/cancel sequence.
 *
 * @param array $payload Verified provider payload.
 * @return array HTTP status and processing result.
 */
function flexpress_flowguard_process_postback($payload)
{
    global $wpdb;
    $handlers = [
        'approved' => ($payload['orderType'] ?? '') === 'purchase' ? 'flexpress_flowguard_handle_purchase_approved' : 'flexpress_flowguard_handle_subscription_approved',
        'rebill' => 'flexpress_flowguard_handle_subscription_rebill',
        'cancel' => 'flexpress_flowguard_handle_subscription_cancel',
        'expiry' => 'flexpress_flowguard_handle_subscription_expiry',
        'chargeback' => 'flexpress_flowguard_handle_refund',
        'credit' => 'flexpress_flowguard_handle_refund',
        'uncancel' => 'flexpress_flowguard_handle_subscription_uncancel',
        'extend' => 'flexpress_flowguard_handle_subscription_extend'
    ];
    foreach (['postbackType', 'orderType', 'referenceId', 'saleId'] as $field) {
        if (!isset($payload[$field]) || !is_scalar($payload[$field]) || (string) $payload[$field] === '') {
            return ['status' => 422, 'result' => 'invalid_event'];
        }
    }
    $event = (string) $payload['postbackType'];
    $order = (string) $payload['orderType'];
    if (!isset($handlers[$event]) || !in_array($order, ['subscription', 'purchase'], true)
        || ($order === 'purchase' && !in_array($event, ['approved', 'credit', 'chargeback'], true))) {
        return ['status' => 422, 'result' => 'invalid_event'];
    }
    $monetary = in_array($event, ['approved', 'rebill', 'credit', 'chargeback'], true);
    if ($monetary && (empty($payload['transactionId']) || !is_scalar($payload['transactionId'])
        || !isset($payload['priceAmount'], $payload['priceCurrency']) || !is_numeric($payload['priceAmount'])
        || !is_string($payload['priceCurrency']))) {
        return ['status' => 422, 'result' => 'invalid_transaction'];
    }
    $user_id = flexpress_flowguard_get_user_from_reference((string) $payload['referenceId']);
    if (!$user_id || !get_userdata($user_id)) {
        return ['status' => 422, 'result' => 'unknown_customer'];
    }
    $sale_id = (string) $payload['saleId'];
    $sale_key = hash('sha256', $order . ':' . $sale_id);
    $identity = array_intersect_key($payload, array_flip([
        'shopId', 'orderType', 'postbackType', 'saleId', 'transactionId', 'referenceId',
        'subscriptionType', 'subscriptionPhase', 'nextChargeOn', 'expiresOn', 'cancelledBy', 'uncancelledBy'
    ]));
    ksort($identity);
    $event_hash = hash('sha256', wp_json_encode($identity));
    $lock = 'fp:' . substr(hash('sha256', $wpdb->prefix . ':' . $user_id), 0, 55);
    if ((string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 5)', $lock)) !== '1') {
        return ['status' => 503, 'result' => 'busy'];
    }
    $in_transaction = false;
    $GLOBALS['flexpress_flowguard_effect_buffer'] = [];
    try {
        if ($wpdb->query('START TRANSACTION') === false) {
            throw new RuntimeException('Could not begin postback transaction');
        }
        $in_transaction = true;
        // Another request may have updated this customer while we waited for the lock.
        wp_cache_delete($user_id, 'user_meta');
        $states = get_user_meta($user_id, 'flowguard_postback_states', true);
        $states = is_array($states) ? $states : [];
        $state = $states[$sale_key] ?? [];
        $duplicate = false;
        if ($monetary) {
            $duplicate = (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}flexpress_flowguard_webhooks WHERE event_type = %s AND transaction_id = %s AND user_id = %d AND processed = 1 LIMIT 1",
                $event, (string) $payload['transactionId'], $user_id
            ));
            // Older installs recorded the transaction even when webhook logging failed.
            if (!$duplicate) {
                $transaction = flexpress_flowguard_get_transaction((string) $payload['transactionId']);
                $duplicate = $transaction && (int) $transaction['user_id'] === $user_id && $transaction['status'] === $event;
            }
        } else {
            $duplicate = ($state['last_hash'] ?? '') === $event_hash;
        }
        if ($duplicate) {
            $wpdb->query('ROLLBACK');
            $in_transaction = false;
            return ['status' => 200, 'result' => 'duplicate'];
        }

        $status = get_user_meta($user_id, 'membership_status', true);
        $current_sale = (string) get_user_meta($user_id, 'flowguard_subscriber_id', true);
        if ($current_sale === '') {
            $current_sale = (string) get_user_meta($user_id, 'flowguard_sale_id', true);
        }
        $ignore = false;
        if ($order === 'subscription' && !in_array($event, ['credit', 'chargeback'], true)) {
            // Provider callbacks cannot lift an account ban or revive a refunded sale.
            $ignore = $status === 'banned' || !empty($state['revoked']);
            if ($event !== 'approved' && $current_sale !== '' && $sale_id !== $current_sale) {
                $ignore = true; // A delayed event for an earlier subscription.
            }
            if ($event === 'approved') {
                $ignore = $ignore || !empty($state['ended']) || !empty($state['cancelled'])
                    || ($sale_id === $current_sale && in_array($status, ['cancelled', 'expired'], true));
            }
            // Old rebills must not move the paid-through date backwards.
            if ($event === 'rebill') {
                $next = strtotime($payload['nextChargeOn'] ?? '');
                $existing = strtotime(get_user_meta($user_id, 'next_rebill_date', true) ?: '');
                if (!$next || ($existing && $next < $existing) || ($status === 'expired' && $next <= current_time('timestamp'))) {
                    $ignore = true;
                }
            }
            if ($event === 'uncancel' && ($status !== 'cancelled' || !empty($state['ended']))) {
                $ignore = true;
            }
        }
        if (!$ignore) {
            if ($event === 'approved' && $order === 'subscription' && $current_sale !== '' && $current_sale !== $sale_id) {
                $previous_key = hash('sha256', 'subscription:' . $current_sale);
                $states[$previous_key]['ended'] = true;
            }
            call_user_func($handlers[$event], $payload);
            flexpress_process_affiliate_commission_from_webhook($payload);
            if ($monetary && !flexpress_flowguard_get_transaction((string) $payload['transactionId'])) {
                throw new RuntimeException('Payment transaction was not recorded');
            }
            if ($event === 'cancel') {
                $state['cancelled'] = true;
            } elseif (in_array($event, ['approved', 'uncancel'], true)) {
                $state['cancelled'] = false;
                $state['ended'] = false;
            }
            if ($event === 'expiry' && get_user_meta($user_id, 'membership_status', true) === 'expired') {
                $state['ended'] = true;
            }
            if (in_array($event, ['credit', 'chargeback'], true)) {
                $state['revoked'] = true;
                $state['ended'] = true;
            }
        }
        $state['last_hash'] = $event_hash;
        $state['revision'] = ($state['revision'] ?? 0) + 1;
        $states[$sale_key] = $state;
        if (update_user_meta($user_id, 'flowguard_postback_states', $states) === false) {
            throw new RuntimeException('Postback state was not recorded');
        }
        $event_key = $monetary
            ? hash('sha256', $event . ':' . $payload['shopId'] . ':' . $payload['transactionId'])
            : hash('sha256', $sale_key . ':' . $event_hash . ':' . $state['revision']);
        flexpress_flowguard_save_effects($user_id, $event_key);
        if (!flexpress_flowguard_store_webhook($payload, $event_key)) {
            throw new RuntimeException('Postback receipt was not recorded');
        }
        if ($wpdb->query('COMMIT') === false) {
            throw new RuntimeException('Could not commit postback transaction');
        }
        $in_transaction = false;
        flexpress_flowguard_schedule_effects($user_id);
        return ['status' => 200, 'result' => $ignore ? 'ignored' : 'processed'];
    } catch (Throwable $error) {
        error_log('Flowguard: Postback processing failed; provider should retry (' . get_class($error) . ' at ' . basename($error->getFile()) . ':' . $error->getLine() . ').');
        return ['status' => 503, 'result' => 'retry'];
    } finally {
        unset($GLOBALS['flexpress_flowguard_effect_buffer']);
        if ($in_transaction) {
            $wpdb->query('ROLLBACK');
        }
        wp_cache_delete($user_id, 'user_meta');
        $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
    }
}
