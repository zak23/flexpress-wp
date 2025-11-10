<?php
// Manual test: newsletter token generation/verification
require_once dirname(__DIR__, 2) . '/wp-content/themes/flexpress/includes/integrations/plunk.php';

function assert_true($cond, $msg) {
    if (!$cond) {
        echo "FAIL: {$msg}\n";
        exit(1);
    }
    echo "OK: {$msg}\n";
}

// Configure a dummy secret if missing (non-persistent)
$settings = get_option('flexpress_plunk_settings', array());
if (empty($settings['secret_api_key'])) {
    $settings['secret_api_key'] = 'test_secret_key_123';
    update_option('flexpress_plunk_settings', $settings);
}

$email = 'tester@example.com';
$token = flexpress_generate_newsletter_token($email);
assert_true(!empty($token), 'Token generated');

$payload = flexpress_verify_newsletter_token($token);
assert_true(!is_wp_error($payload), 'Token verified');
assert_true($payload['email'] === strtolower($email), 'Email roundtrip matches');

// Expiry test: simulate expired
$parts = explode('.', $token);
$payload_json = base64_decode(strtr($parts[0], '-_', '+/'));
$data = json_decode($payload_json, true);
$data['iat'] = 0; // epoch
$new_b64 = rtrim(strtr(base64_encode(wp_json_encode($data)), '+/', '-_'), '=');
$mac_raw = hash_hmac('sha256', $new_b64, $settings['secret_api_key'], true);
$mac_b64 = rtrim(strtr(base64_encode($mac_raw), '+/', '-_'), '=');
$expired = $new_b64 . '.' . $mac_b64;
$expired_check = flexpress_verify_newsletter_token($expired, 10); // 10 seconds window
assert_true(is_wp_error($expired_check), 'Expired token rejected');

echo "All tests passed.\n";

