<?php
/**
 * Plunk integration helpers (service accessor, newsletter tokens)
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get Plunk settings array
 *
 * @return array
 */
function flexpress_plunk_get_settings()
{
    return get_option('flexpress_plunk_settings', array());
}

/**
 * Singleton accessor for FlexPress_Plunk_Service
 *
 * @return FlexPress_Plunk_Service
 */
function flexpress_plunk_service()
{
    static $service = null;
    if ($service === null) {
        if (!class_exists('FlexPress_Plunk_Service')) {
            require_once FLEXPRESS_PATH . '/includes/integrations/class-flexpress-plunk-service.php';
        }
        $service = new FlexPress_Plunk_Service();
    }
    return $service;
}

/**
 * Generate a signed newsletter token for an email
 *
 * Token format: base64url(payload).'.'.base64url(hmac)
 * payload = {"email":"...","iat":timestamp}
 *
 * @param string $email
 * @return string
 */
function flexpress_generate_newsletter_token($email)
{
    $email = strtolower(trim($email));
    $settings = flexpress_plunk_get_settings();
    $secret = $settings['secret_api_key'] ?? '';
    if (empty($secret) || empty($email)) {
        return '';
    }
    $payload = array(
        'email' => $email,
        'iat' => time(),
        'v' => 1
    );
    $payload_json = wp_json_encode($payload);
    $payload_b64 = rtrim(strtr(base64_encode($payload_json), '+/', '-_'), '=');
    $mac_raw = hash_hmac('sha256', $payload_b64, $secret, true);
    $mac_b64 = rtrim(strtr(base64_encode($mac_raw), '+/', '-_'), '=');
    return $payload_b64 . '.' . $mac_b64;
}

/**
 * Verify a newsletter token and return payload on success
 *
 * @param string $token
 * @param int $max_age_seconds Defaults to 14 days
 * @return array|WP_Error
 */
function flexpress_verify_newsletter_token($token, $max_age_seconds = 14 * DAY_IN_SECONDS)
{
    $settings = flexpress_plunk_get_settings();
    $secret = $settings['secret_api_key'] ?? '';
    if (empty($secret) || empty($token)) {
        return new WP_Error('invalid', 'Invalid token or configuration');
    }
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return new WP_Error('invalid_format', 'Invalid token format');
    }
    list($payload_b64, $mac_b64) = $parts;
    $expected_mac_raw = hash_hmac('sha256', $payload_b64, $secret, true);
    $expected_mac_b64 = rtrim(strtr(base64_encode($expected_mac_raw), '+/', '-_'), '=');
    if (!hash_equals($expected_mac_b64, $mac_b64)) {
        return new WP_Error('bad_sig', 'Invalid signature');
    }
    $payload_json = base64_decode(strtr($payload_b64, '-_', '+/'));
    $payload = json_decode($payload_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($payload['email']) || empty($payload['iat'])) {
        return new WP_Error('bad_payload', 'Invalid payload');
    }
    if ((time() - intval($payload['iat'])) > $max_age_seconds) {
        return new WP_Error('expired', 'Token expired');
    }
    $payload['email'] = strtolower(trim($payload['email']));
    return $payload;
}


