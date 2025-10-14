<?php
/**
 * FlexPress JWT Utility (HS256)
 *
 * Lightweight issuer/validator for REST auth.
 */

if (!defined('ABSPATH')) {
    exit;
}

class FlexPress_JWT {
    public static function issue($payload, $ttl_seconds = 900) {
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttl_seconds;
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [
            self::b64(json_encode($header)),
            self::b64(json_encode($payload))
        ];
        $signature = hash_hmac('sha256', implode('.', $segments), AUTH_KEY, true);
        $segments[] = self::b64($signature);
        return implode('.', $segments);
    }

    public static function validate($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return [false, 'invalid_format'];
        }
        list($h, $p, $s) = $parts;
        $expected = self::b64(hash_hmac('sha256', $h . '.' . $p, AUTH_KEY, true));
        if (!hash_equals($expected, $s)) {
            return [false, 'bad_signature'];
        }
        $payload = json_decode(self::unb64($p), true);
        if (!$payload || empty($payload['exp']) || time() >= intval($payload['exp'])) {
            return [false, 'expired'];
        }
        return [true, $payload];
    }

    private static function b64($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function unb64($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}


