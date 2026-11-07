<?php
/** Durable post-commit work for Flowguard. Stored with the payment in usermeta. */
if (!defined('ABSPATH')) {
    exit;
}

function flexpress_flowguard_defer_effect($kind, $args)
{
    if (!isset($GLOBALS['flexpress_flowguard_effect_buffer'])) {
        return false;
    }
    $GLOBALS['flexpress_flowguard_effect_buffer'][] = ['kind' => $kind, 'args' => $args];
    return true;
}

function flexpress_flowguard_save_effects($user_id, $event_key)
{
    $effects = $GLOBALS['flexpress_flowguard_effect_buffer'] ?? [];
    if (!$effects) {
        return;
    }
    $pending = get_user_meta($user_id, 'flowguard_pending_effects', true) ?: [];
    foreach ($effects as $index => $effect) {
        $key = hash('sha256', $event_key . ':' . $index);
        $pending[$key] = $effect + ['attempts' => 0, 'next_attempt' => 0];
    }
    if (update_user_meta($user_id, 'flowguard_pending_effects', $pending) === false) {
        throw new RuntimeException('Could not persist payment effects');
    }
}

function flexpress_flowguard_schedule_effects($user_id)
{
    if (get_user_meta($user_id, 'flowguard_pending_effects', true)
        && !wp_next_scheduled('flexpress_flowguard_deliver_effects', [(int) $user_id])) {
        wp_schedule_single_event(time() + 5, 'flexpress_flowguard_deliver_effects', [(int) $user_id]);
    }
}

/** Only these operations can be replayed; never execute arbitrary stored callables. */
function flexpress_flowguard_deliver_effect($effect)
{
    $args = $effect['args'];
    switch ($effect['kind']) {
        case 'discord':
            $settings = get_option('flexpress_discord_settings', []);
            if (empty($settings['webhook_url']) && empty($settings['webhook_url_financial'])) {
                return true; // Integration has been disabled since this was queued.
            }
            return (new FlexPress_Discord_Notifications())->send_notification(
                $args['embed'], $args['content'], $args['channel'], $args['suppress_notifications']
            );
        case 'plunk':
            if (!(new FlexPress_Plunk_API())->is_configured()) {
                return true;
            }
            if (!in_array($args['method'], ['identify_user', 'track_user_event', 'set_newsletter_status', 'update_membership_traits'], true)) {
                return false;
            }
            return call_user_func_array([flexpress_plunk_service(), $args['method']], $args['args']) === true;
        case 'cancel_subscription':
            // A later manually restored/new membership must never be cancelled by an old job.
            $status = get_user_meta($args['user_id'], 'membership_status', true);
            $sale = get_user_meta($args['user_id'], 'flowguard_subscriber_id', true)
                ?: get_user_meta($args['user_id'], 'flowguard_sale_id', true);
            if ($status !== 'banned' || (string) $sale !== $args['sale_id']) {
                return true;
            }
            $result = flexpress_get_flowguard_api()->cancel_subscription($args['sale_id']);
            return !empty($result['success']);
    }
    return false;
}

/** Retry individual failed operations; successful ones are removed independently. */
function flexpress_flowguard_deliver_effects($user_id)
{
    global $wpdb;
    $lock = 'fp:' . substr(hash('sha256', $wpdb->prefix . ':' . $user_id), 0, 55);
    if ((string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 0)', $lock)) !== '1') {
        return; // The periodic sweep recovers lock contention or missed cron dispatch.
    }
    try {
        wp_cache_delete($user_id, 'user_meta');
        $pending = get_user_meta($user_id, 'flowguard_pending_effects', true) ?: [];
        $blocked = [];
        $processed = 0;
        foreach ($pending as $key => $effect) {
            if ($processed >= 20) { break; }
            if (isset($blocked[$effect['kind']])) { continue; }
            if ($effect['next_attempt'] > time()) {
                $blocked[$effect['kind']] = true;
                continue;
            }
            $processed++;
            try {
                $success = flexpress_flowguard_deliver_effect($effect);
            } catch (Throwable $error) {
                $success = false;
            }
            if ($success) {
                unset($pending[$key]);
            } else {
                $blocked[$effect['kind']] = true;
                $pending[$key]['attempts']++;
                $pending[$key]['next_attempt'] = time() + min(HOUR_IN_SECONDS, 60 * (2 ** min(6, $pending[$key]['attempts'])));
                error_log('Flowguard: Deferred ' . $effect['kind'] . ' failed; queued for retry.');
            }
            if (update_user_meta($user_id, 'flowguard_pending_effects', $pending) === false) {
                // Do not continue sending if progress cannot be saved.
                error_log('Flowguard: Could not save deferred delivery progress.');
                return;
            }
        }
        if (!$pending) {
            delete_user_meta($user_id, 'flowguard_pending_effects');
        } elseif (!wp_next_scheduled('flexpress_flowguard_deliver_effects', [(int) $user_id])) {
            $next = min(array_column($pending, 'next_attempt'));
            wp_schedule_single_event(max(time() + 60, $next), 'flexpress_flowguard_deliver_effects', [(int) $user_id]);
        }
    } finally {
        wp_cache_delete($user_id, 'user_meta');
        $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
    }
}
add_action('flexpress_flowguard_deliver_effects', 'flexpress_flowguard_deliver_effects');

function flexpress_flowguard_effects_sweep()
{
    global $wpdb;
    $users = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s", 'flowguard_pending_effects'));
    foreach ($users as $user_id) {
        flexpress_flowguard_schedule_effects((int) $user_id);
    }
}
add_action('flexpress_flowguard_effects_sweep', 'flexpress_flowguard_effects_sweep');
add_action('init', function () {
    if (!wp_next_scheduled('flexpress_flowguard_effects_sweep')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'flexpress_flowguard_effects_sweep');
    }
});
