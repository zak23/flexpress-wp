<?php

/**
 * Age proof storage and compatibility redirects for the current /_age/ flow.
 *
 * @package FlexPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FLEXPRESS_AGE_COOKIE', 'flexpress_age_verified' );
define( 'FLEXPRESS_AGE_COOKIE_VERSION', 1 );
define( 'FLEXPRESS_AGE_COOKIE_LIFETIME', 90 * DAY_IN_SECONDS );

/** Minimal member result. The signature binds it to this site and this member. */
function flexpress_save_member_age_result($user_id, $provider, $verified_at, $expires_at) {
    if ($user_id < 1 || !in_array($provider, array('didit', 'yoursafe_id'), true) ||
        $verified_at > time() || $expires_at <= time()) { throw new RuntimeException('Invalid age result.'); }
    $result = array('user_id' => (int) $user_id, 'provider' => $provider,
        'verified_at' => (int) $verified_at, 'expires_at' => (int) $expires_at);
    $payload = wp_json_encode($result);
    update_user_meta($user_id, 'flexpress_age_verified', 1);
    update_user_meta($user_id, 'flexpress_age_verified_provider', $provider);
    update_user_meta($user_id, 'flexpress_age_verified_at', gmdate(DATE_ATOM, $verified_at));
    update_user_meta($user_id, 'flexpress_age_verified_expires', gmdate(DATE_ATOM, $expires_at));
    update_user_meta($user_id, '_flexpress_member_age_result', array('payload' => $payload,
        'signature' => hash_hmac('sha256', 'member-age:' . $payload, wp_salt('auth'))));
}

function flexpress_member_age_result() {
    $user_id = get_current_user_id();
    if (!$user_id || (int) get_user_meta($user_id, 'flexpress_age_verified', true) !== 1) { return null; }
    $saved = get_user_meta($user_id, '_flexpress_member_age_result', true);
    if (!is_array($saved) || !is_string($saved['payload'] ?? null) || !is_string($saved['signature'] ?? null) ||
        !hash_equals(hash_hmac('sha256', 'member-age:' . $saved['payload'], wp_salt('auth')), $saved['signature'])) { return null; }
    $result = json_decode($saved['payload'], true);
    if (!is_array($result) || ($result['user_id'] ?? null) !== $user_id ||
        !is_int($result['expires_at'] ?? null) || $result['expires_at'] <= time() ||
        !is_int($result['verified_at'] ?? null) || $result['verified_at'] > time()) { return null; }
    return $result;
}

/**
 * Legacy provider switch retained for existing Yoursafe configurations.
 * Visitor enforcement belongs to the configured Exclusv adapter; this switch
 * no longer activates the retired Australian gate or its page.
 *
 * @return bool
 */
function flexpress_is_age_gate_enabled() {
	if ( defined( 'FLEXPRESS_AGE_GATE_ENABLED' ) ) {
		return (bool) FLEXPRESS_AGE_GATE_ENABLED;
	}

	/**
	 * Filter for legacy Yoursafe enablement without a wp-config constant.
	 *
	 * @param bool $enabled Default false for legacy provider enablement.
	 */
	return (bool) apply_filters( 'flexpress_age_gate_enabled', false );
}

/**
 * Encode binary-safe data for a cookie.
 *
 * @param string $value Value to encode.
 * @return string
 */
function flexpress_age_base64url_encode( $value ) {
	return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
}

/**
 * Decode a base64url cookie component.
 *
 * @param string $value Value to decode.
 * @return string|false
 */
function flexpress_age_base64url_decode( $value ) {
	if ( ! is_string( $value ) || ! preg_match( '/^[A-Za-z0-9_-]+$/', $value ) ) {
		return false;
	}

	$padding = strlen( $value ) % 4;
	if ( $padding ) {
		$value .= str_repeat( '=', 4 - $padding );
	}

	return base64_decode( strtr( $value, '-_', '+/' ), true );
}

/**
 * Create a signed age-verification cookie value.
 *
 * @param int|null $issued_at Unix timestamp, primarily for deterministic tests.
 * @return string
 */
function flexpress_create_age_cookie_value( $issued_at = null ) {
	$issued_at = null === $issued_at ? time() : (int) $issued_at;
	$payload   = wp_json_encode(
		array(
			'iat' => $issued_at,
			'exp' => $issued_at + FLEXPRESS_AGE_COOKIE_LIFETIME,
			'v'   => FLEXPRESS_AGE_COOKIE_VERSION,
		)
	);
	$encoded   = flexpress_age_base64url_encode( $payload );
	$signature = hash_hmac( 'sha256', $encoded, wp_salt( 'auth' ) );

	return $encoded . '.' . $signature;
}

/**
 * Validate an age-verification cookie.
 *
 * @param mixed    $cookie Cookie value.
 * @param int|null $now    Current Unix timestamp.
 * @return bool
 */
function flexpress_validate_age_cookie( $cookie, $now = null ) {
	if ( ! is_string( $cookie ) || 1 !== substr_count( $cookie, '.' ) ) {
		return false;
	}

	list( $encoded, $signature ) = explode( '.', $cookie, 2 );
	$expected = hash_hmac( 'sha256', $encoded, wp_salt( 'auth' ) );
	if ( ! hash_equals( $expected, $signature ) ) {
		return false;
	}

	$decoded = flexpress_age_base64url_decode( $encoded );
	$payload = false === $decoded ? null : json_decode( $decoded, true );
	if ( ! is_array( $payload ) || ! isset( $payload['iat'], $payload['exp'], $payload['v'] ) ) {
		return false;
	}

	$now = null === $now ? time() : (int) $now;
	return FLEXPRESS_AGE_COOKIE_VERSION === (int) $payload['v']
		&& is_int( $payload['iat'] )
		&& is_int( $payload['exp'] )
		&& $payload['iat'] <= $now + 300
		&& $payload['exp'] > $now
		&& FLEXPRESS_AGE_COOKIE_LIFETIME === $payload['exp'] - $payload['iat'];
}

/**
 * Check the current request's verification cookie.
 *
 * @return bool
 */
function flexpress_is_age_verified() {
	$cookie = isset( $_COOKIE[ FLEXPRESS_AGE_COOKIE ] ) ? wp_unslash( $_COOKIE[ FLEXPRESS_AGE_COOKIE ] ) : '';
	return flexpress_validate_age_cookie( $cookie );
}

/**
 * Set the temporary verification cookie.
 *
 * @return void
 */
function flexpress_set_age_verified_cookie() {
	$value   = flexpress_create_age_cookie_value();
	$expires = time() + FLEXPRESS_AGE_COOKIE_LIFETIME;
	$domain  = defined( 'COOKIE_DOMAIN' ) && is_string( COOKIE_DOMAIN ) ? COOKIE_DOMAIN : '';

	setcookie(
		FLEXPRESS_AGE_COOKIE,
		$value,
		array(
			'expires'  => $expires,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => $domain,
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
	$_COOKIE[ FLEXPRESS_AGE_COOKIE ] = $value;
}

/** Clear the existing Yoursafe proof using the same cookie scope used to create it. */
function flexpress_clear_age_verified_cookie() {
    setcookie(FLEXPRESS_AGE_COOKIE, '', array(
        'expires' => time() - 3600,
        'path' => COOKIEPATH ? COOKIEPATH : '/',
        'domain' => defined('COOKIE_DOMAIN') && is_string(COOKIE_DOMAIN) ? COOKIE_DOMAIN : '',
        'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax',
    ));
    unset($_COOKIE[FLEXPRESS_AGE_COOKIE]);
}

/**
 * Validate a same-site return path.
 *
 * @param mixed $requested Requested return URL/path.
 * @return string
 */
function flexpress_age_gate_return_url( $requested ) {
	$requested = is_string( $requested ) ? wp_unslash( $requested ) : '';
	$fallback  = home_url( '/' );
	return wp_validate_redirect( $requested, $fallback );
}

/**
 * Build a link to the current panel using its path-only return contract.
 * Legacy absolute return_to values are converted to local paths; gate routes
 * fall back to the homepage so completing verification cannot create a loop.
 */
function flexpress_age_access_url( $requested = '' ) {
    $return = wp_make_link_relative( flexpress_age_gate_return_url( $requested ) );
    if ( ! preg_match( '#^/(?!/)[A-Za-z0-9_./~-]*$#D', $return ) ||
        strpos( $return, '..' ) !== false ||
        preg_match( '#^/(?:_age|age-verification)(?:/|$)#', $return ) ) {
        $return = '/';
    }
    return add_query_arg( 'return', $return, home_url( '/_age/' ) );
}

/** Replace the retired page even while general visitor enforcement is disabled. */
function flexpress_redirect_legacy_age_page() {
    $path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
    if ( ! in_array( $path, array( '/age-verification', '/age-verification/' ), true ) ) {
        return;
    }
    nocache_headers();
    header( 'Cache-Control: private, no-store, max-age=0' );
    header( 'Referrer-Policy: no-referrer' );
    if ( ! in_array( $_SERVER['REQUEST_METHOD'] ?? '', array( 'GET', 'HEAD' ), true ) ) {
        header( 'Allow: GET, HEAD' );
        status_header( 405 );
        exit;
    }
    $url = flexpress_age_access_url( $_GET['return_to'] ?? $_GET['return'] ?? '' );
    if ( isset( $_GET['yoursafe_error'] ) && is_string( $_GET['yoursafe_error'] ) ) {
        $url = add_query_arg( 'yoursafe_error', sanitize_key( wp_unslash( $_GET['yoursafe_error'] ) ), $url );
    }
    wp_safe_redirect( $url, 302 );
    exit;
}
// Only the exact retired page is aliased; registered Yoursafe endpoints stay intact.
add_action( 'init', 'flexpress_redirect_legacy_age_page', -1001 );
