<?php
// Manual test: plunk service event stubs (skips if not configured)
require_once dirname(__DIR__, 2) . '/wp-content/themes/flexpress/includes/integrations/plunk.php';

function log_line($msg) { echo $msg . "\n"; }

$service = flexpress_plunk_service();
$reflect = new ReflectionClass($service);
$apiProp = $reflect->getProperty('api');
$apiProp->setAccessible(true);
$api = $apiProp->getValue($service);

if (!$api->is_configured()) {
    log_line('SKIP: Plunk not configured. Set Public/Secret/Install URL in admin to run this test.');
    exit(0);
}

// Use current user if logged in; otherwise create a fake email
$user_id = get_current_user_id();
if ($user_id) {
    $ok = $service->track_user_event($user_id, 'test_event', array('hello' => 'world', 'timestamp' => date('c')));
    log_line($ok ? 'OK: Tracked test_event for current user' : 'FAIL: Could not track event');
} else {
    // Ensure a contact via email only
    $email = 'test+' . time() . '@example.com';
    $cid = $service->ensure_contact($email, array('source' => 'manual-test'));
    log_line(!is_wp_error($cid) ? 'OK: Ensured contact by email' : 'FAIL: Could not ensure contact');
}

log_line('Done.');

