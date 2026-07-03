<?php

/**
 * Yoursafe ID OpenID Connect age-verification provider.
 *
 * @package FlexPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FLEXPRESS_YOURSAFE_ISSUER', 'https://accounts.yoursafe.com' );
define( 'FLEXPRESS_YOURSAFE_AUTHORIZE_ENDPOINT', 'https://accounts.yoursafe.com/oauth2/authorize' );
define( 'FLEXPRESS_YOURSAFE_TOKEN_ENDPOINT', 'https://accounts.yoursafe.com/oauth2/token' );
define( 'FLEXPRESS_YOURSAFE_USERINFO_ENDPOINT', 'https://accounts.yoursafe.com/userinfo' );
define( 'FLEXPRESS_YOURSAFE_JWKS_ENDPOINT', 'https://accounts.yoursafe.com/oauth2/jwks' );
define( 'FLEXPRESS_YOURSAFE_STATE_COOKIE', 'flexpress_yoursafe_state' );

/**
 * Return sanitized Yoursafe ID settings.
 *
 * @return array<string, mixed>
 */
function flexpress_yoursafe_settings() {
	$settings = get_option( 'flexpress_yoursafe_id_settings', array() );
	return is_array( $settings ) ? $settings : array();
}

/**
 * Whether the provider is ready to use.
 *
 * @return bool
 */
function flexpress_yoursafe_is_enabled() {
	$settings = flexpress_yoursafe_settings();
	return ! empty( $settings['enabled'] ) && ! empty( $settings['client_id'] ) && ! empty( $settings['client_secret'] );
}

/**
 * Record a safe diagnostic for the most recent provider failure.
 *
 * Never pass credentials, authorization codes, or tokens to this function.
 *
 * @param string $stage Provider flow stage.
 * @param int    $status HTTP status, when available.
 * @param string $provider_error OAuth error identifier, when available.
 * @return void
 */
function flexpress_yoursafe_record_failure( $stage, $status = 0, $provider_error = '' ) {
	$diagnostic = array(
		'time'           => gmdate( DATE_ATOM ),
		'stage'          => sanitize_key( $stage ),
		'http_status'    => absint( $status ),
		'provider_error' => sanitize_key( $provider_error ),
	);
	update_option( 'flexpress_yoursafe_id_last_error', $diagnostic, false );
	error_log( '[FlexPress][Yoursafe ID] ' . wp_json_encode( $diagnostic ) );
}

/**
 * Exact callback URL to register at Yoursafe.
 *
 * @return string
 */
function flexpress_yoursafe_callback_url() {
	return home_url( '/age-verification/yoursafe/callback/' );
}

/**
 * Start endpoint URL for the age page.
 *
 * @param string $return_url Same-site destination after verification.
 * @return string
 */
function flexpress_yoursafe_start_url( $return_url ) {
	return add_query_arg(
		array(
			'return_to' => $return_url,
			'request'   => wp_generate_uuid4(),
		),
		home_url( '/age-verification/yoursafe/start/' )
	);
}

/**
 * Set or clear the short-lived OIDC state cookie.
 *
 * @param string $value State value, empty to clear.
 * @param int    $expires Expiry timestamp.
 * @return void
 */
function flexpress_yoursafe_set_state_cookie( $value, $expires ) {
	setcookie(
		FLEXPRESS_YOURSAFE_STATE_COOKIE,
		$value,
		array(
			'expires'  => $expires,
			'path'     => '/',
			'domain'   => '',
			'secure'   => 'https' === wp_parse_url( home_url( '/' ), PHP_URL_SCHEME ),
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);

	if ( '' === $value ) {
		unset( $_COOKIE[ FLEXPRESS_YOURSAFE_STATE_COOKIE ] );
	} else {
		$_COOKIE[ FLEXPRESS_YOURSAFE_STATE_COOKIE ] = $value;
	}
}

/**
 * Redirect back to the age page with a non-sensitive error code.
 *
 * @param string $code Error code.
 * @param string $return_url Intended same-site destination.
 * @return void
 */
function flexpress_yoursafe_fail( $code, $return_url ) {
	$url = add_query_arg(
		array(
			'yoursafe_error' => sanitize_key( $code ),
			'return_to'      => flexpress_age_gate_return_url( $return_url ),
		),
		home_url( '/age-verification/' )
	);
	nocache_headers();
	wp_safe_redirect( $url, 303 );
	exit;
}

/**
 * Begin the authorization-code flow with state, nonce, and PKCE.
 *
 * @return void
 */
function flexpress_yoursafe_start() {
	$return_url = flexpress_age_gate_return_url( isset( $_GET['return_to'] ) ? $_GET['return_to'] : home_url( '/' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect target.
	if ( ! flexpress_yoursafe_is_enabled() ) {
		flexpress_yoursafe_fail( 'not_configured', $return_url );
	}

	$settings      = flexpress_yoursafe_settings();
	$state         = flexpress_age_base64url_encode( random_bytes( 32 ) );
	$nonce         = flexpress_age_base64url_encode( random_bytes( 32 ) );
	$code_verifier = flexpress_age_base64url_encode( random_bytes( 48 ) );
	$expires       = time() + 10 * MINUTE_IN_SECONDS;

	set_transient(
		'flexpress_yoursafe_' . $state,
		array(
			'nonce'         => $nonce,
			'code_verifier' => $code_verifier,
			'return_url'    => $return_url,
			'created_at'    => time(),
		),
		10 * MINUTE_IN_SECONDS
	);
	flexpress_yoursafe_set_state_cookie( $state, $expires );

	$scope = isset( $settings['scope'] ) ? $settings['scope'] : 'openid default';
	$url   = add_query_arg(
		array(
			'response_type'         => 'code',
			'client_id'             => $settings['client_id'],
			'scope'                 => $scope,
			'state'                 => $state,
			'nonce'                 => $nonce,
			'redirect_uri'          => flexpress_yoursafe_callback_url(),
			'code_challenge'        => flexpress_age_base64url_encode( hash( 'sha256', $code_verifier, true ) ),
			'code_challenge_method' => 'S256',
		),
		FLEXPRESS_YOURSAFE_AUTHORIZE_ENDPOINT
	);

	nocache_headers();
	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
	wp_redirect( $url, 302, 'FlexPress Yoursafe ID' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- URL is built from a fixed trusted endpoint.
	exit;
}

/**
 * Fetch authoritative claims from the OIDC userinfo endpoint.
 *
 * @param string $access_token OAuth access token.
 * @return array<string, mixed>|WP_Error
 */
function flexpress_yoursafe_fetch_claims( $access_token ) {
	$response = wp_remote_get(
		FLEXPRESS_YOURSAFE_USERINFO_ENDPOINT,
		array(
			'timeout'     => 15,
			'redirection' => 0,
			'headers'     => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
			),
		)
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'userinfo_failed' );
	}

	$claims = json_decode( wp_remote_retrieve_body( $response ), true );
	return is_array( $claims ) ? $claims : new WP_Error( 'userinfo_invalid' );
}

/**
 * Encode a DER length.
 *
 * @param int $length Length.
 * @return string
 */
function flexpress_yoursafe_der_length( $length ) {
	if ( $length < 128 ) {
		return chr( $length );
	}
	$encoded = '';
	while ( $length > 0 ) {
		$encoded = chr( $length & 0xff ) . $encoded;
		$length >>= 8;
	}
	return chr( 0x80 | strlen( $encoded ) ) . $encoded;
}

/**
 * Wrap data in a DER element.
 *
 * @param int    $tag DER tag.
 * @param string $value Element value.
 * @return string
 */
function flexpress_yoursafe_der( $tag, $value ) {
	return chr( $tag ) . flexpress_yoursafe_der_length( strlen( $value ) ) . $value;
}

/**
 * Convert an RSA JWK to a PEM SubjectPublicKeyInfo key.
 *
 * @param array<string, mixed> $jwk RSA JWK.
 * @return string|false
 */
function flexpress_yoursafe_jwk_to_pem( $jwk ) {
	if ( empty( $jwk['n'] ) || empty( $jwk['e'] ) ) {
		return false;
	}
	$modulus = flexpress_age_base64url_decode( $jwk['n'] );
	$exponent = flexpress_age_base64url_decode( $jwk['e'] );
	if ( false === $modulus || false === $exponent || '' === $modulus || '' === $exponent ) {
		return false;
	}
	if ( ord( $modulus[0] ) & 0x80 ) {
		$modulus = "\x00" . $modulus;
	}
	if ( ord( $exponent[0] ) & 0x80 ) {
		$exponent = "\x00" . $exponent;
	}

	$rsa_key = flexpress_yoursafe_der(
		0x30,
		flexpress_yoursafe_der( 0x02, $modulus ) . flexpress_yoursafe_der( 0x02, $exponent )
	);
	$rsa_algorithm = hex2bin( '300d06092a864886f70d0101010500' );
	$public_key = flexpress_yoursafe_der( 0x30, $rsa_algorithm . flexpress_yoursafe_der( 0x03, "\x00" . $rsa_key ) );

	return "-----BEGIN PUBLIC KEY-----\n" . chunk_split( base64_encode( $public_key ), 64, "\n" ) . "-----END PUBLIC KEY-----\n";
}

/**
 * Load Yoursafe signing keys with a short cache.
 *
 * @param bool $force_refresh Bypass the cache after an unknown key ID.
 * @return array<int, array<string, mixed>>|WP_Error
 */
function flexpress_yoursafe_get_jwks( $force_refresh = false ) {
	$cache_key = 'flexpress_yoursafe_jwks';
	$keys = $force_refresh ? false : get_transient( $cache_key );
	if ( is_array( $keys ) ) {
		return $keys;
	}

	$response = wp_remote_get(
		FLEXPRESS_YOURSAFE_JWKS_ENDPOINT,
		array(
			'timeout'     => 10,
			'redirection' => 0,
			'headers'     => array( 'Accept' => 'application/json' ),
		)
	);
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'jwks_failed' );
	}
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $body ) || empty( $body['keys'] ) || ! is_array( $body['keys'] ) ) {
		return new WP_Error( 'jwks_invalid' );
	}
	set_transient( $cache_key, $body['keys'], HOUR_IN_SECONDS );
	return $body['keys'];
}

/**
 * Verify a Yoursafe RS256 ID token and its OIDC claims.
 *
 * @param string $jwt ID token.
 * @param string $client_id Expected audience.
 * @param string $nonce Expected nonce.
 * @return array<string, mixed>|WP_Error
 */
function flexpress_yoursafe_verify_id_token( $jwt, $client_id, $nonce ) {
	$parts = is_string( $jwt ) ? explode( '.', $jwt ) : array();
	if ( 3 !== count( $parts ) ) {
		return new WP_Error( 'id_token_format' );
	}
	$header_json  = flexpress_age_base64url_decode( $parts[0] );
	$payload_json = flexpress_age_base64url_decode( $parts[1] );
	$signature    = flexpress_age_base64url_decode( $parts[2] );
	$header       = false === $header_json ? null : json_decode( $header_json, true );
	$claims       = false === $payload_json ? null : json_decode( $payload_json, true );
	if ( ! is_array( $header ) || ! is_array( $claims ) || false === $signature ) {
		return new WP_Error( 'id_token_format' );
	}
	if ( 'RS256' !== ( isset( $header['alg'] ) ? $header['alg'] : '' ) || empty( $header['kid'] ) ) {
		return new WP_Error( 'id_token_algorithm' );
	}

	$keys = flexpress_yoursafe_get_jwks();
	if ( is_wp_error( $keys ) ) {
		return $keys;
	}
	$matching_key = null;
	foreach ( $keys as $key ) {
		if ( isset( $key['kid'], $key['kty'] ) && hash_equals( (string) $header['kid'], (string) $key['kid'] ) && 'RSA' === $key['kty'] ) {
			$matching_key = $key;
			break;
		}
	}
	if ( null === $matching_key ) {
		$keys = flexpress_yoursafe_get_jwks( true );
		if ( is_wp_error( $keys ) ) {
			return $keys;
		}
		foreach ( $keys as $key ) {
			if ( isset( $key['kid'], $key['kty'] ) && hash_equals( (string) $header['kid'], (string) $key['kid'] ) && 'RSA' === $key['kty'] ) {
				$matching_key = $key;
				break;
			}
		}
	}
	$pem = null === $matching_key ? false : flexpress_yoursafe_jwk_to_pem( $matching_key );
	if ( ! $pem || 1 !== openssl_verify( $parts[0] . '.' . $parts[1], $signature, $pem, OPENSSL_ALGO_SHA256 ) ) {
		return new WP_Error( 'id_token_signature' );
	}

	$now      = time();
	$audience = isset( $claims['aud'] ) ? $claims['aud'] : null;
	$aud_valid = is_string( $audience ) ? hash_equals( $client_id, $audience ) : is_array( $audience ) && in_array( $client_id, $audience, true );
	if (
		FLEXPRESS_YOURSAFE_ISSUER !== ( isset( $claims['iss'] ) ? $claims['iss'] : '' ) ||
		! $aud_valid ||
		empty( $claims['exp'] ) || (int) $claims['exp'] <= $now ||
		! isset( $claims['iat'] ) || (int) $claims['iat'] > $now + 300 ||
		empty( $claims['nonce'] ) || ! hash_equals( $nonce, (string) $claims['nonce'] ) ||
		empty( $claims['sub'] )
	) {
		return new WP_Error( 'id_token_claims' );
	}

	return $claims;
}

/**
 * Validate the minimum claims required for an over-18 decision.
 *
 * @param array<string, mixed> $claims Userinfo claims.
 * @return true|WP_Error
 */
function flexpress_yoursafe_validate_claims( $claims ) {
	if ( ! isset( $claims['eighteenplus'] ) || true !== $claims['eighteenplus'] ) {
		return new WP_Error( 'not_over_18' );
	}

	if ( ! isset( $claims['accountstatus'] ) || 'active' !== strtolower( (string) $claims['accountstatus'] ) ) {
		return new WP_Error( 'account_inactive' );
	}

	if ( empty( $claims['idverifieddate'] ) ) {
		return new WP_Error( 'verification_date_missing' );
	}

	try {
		$verified_at = new DateTimeImmutable( (string) $claims['idverifieddate'] );
		$now         = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
	} catch ( Exception $exception ) {
		return new WP_Error( 'verification_date_invalid' );
	}

	// idverifieddate marks Yoursafe's one-time identity verification and may be
	// years old; the eighteenplus claim is recomputed at every login, so only a
	// future-dated value is rejected. verification_valid_days governs how long
	// the local proof (cookie/user meta) lasts, not the age of this claim.
	if ( $verified_at > $now->modify( '+5 minutes' ) ) {
		return new WP_Error( 'verification_date_invalid' );
	}

	if ( empty( $claims['aliastoken'] ) || ! preg_match( '/^[a-z0-9-]+\.yoursafe\.id\.?$/i', (string) $claims['aliastoken'] ) ) {
		return new WP_Error( 'alias_invalid' );
	}

	return true;
}

/**
 * Complete the OIDC flow and grant age-verified access.
 *
 * @return void
 */
function flexpress_yoursafe_callback() {
	$state  = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OIDC state is the CSRF control.
	$cookie = isset( $_COOKIE[ FLEXPRESS_YOURSAFE_STATE_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ FLEXPRESS_YOURSAFE_STATE_COOKIE ] ) ) : '';
	$data   = preg_match( '/^[A-Za-z0-9_-]{40,100}$/', $state ) ? get_transient( 'flexpress_yoursafe_' . $state ) : false;
	$return_url = is_array( $data ) && isset( $data['return_url'] ) ? $data['return_url'] : home_url( '/' );

	if ( ! $state ) {
		flexpress_yoursafe_record_failure( 'state_missing' );
		flexpress_yoursafe_fail( 'state_invalid', $return_url );
	}
	if ( ! $cookie ) {
		flexpress_yoursafe_record_failure( 'state_cookie_missing' );
		flexpress_yoursafe_fail( 'state_invalid', $return_url );
	}
	if ( ! hash_equals( $state, $cookie ) ) {
		flexpress_yoursafe_record_failure( 'state_cookie_mismatch' );
		flexpress_yoursafe_fail( 'state_invalid', $return_url );
	}
	if ( ! is_array( $data ) ) {
		flexpress_yoursafe_record_failure( 'state_session_missing' );
		flexpress_yoursafe_fail( 'state_invalid', $return_url );
	}

	delete_transient( 'flexpress_yoursafe_' . $state );
	flexpress_yoursafe_set_state_cookie( '', time() - HOUR_IN_SECONDS );

	if ( isset( $_GET['error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Provider error after state validation.
		flexpress_yoursafe_fail( 'cancelled', $return_url );
	}

	$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- One-time authorization code.
	if ( ! $code || empty( $data['code_verifier'] ) ) {
		flexpress_yoursafe_fail( 'code_missing', $return_url );
	}

	$settings = flexpress_yoursafe_settings();
	$token_body = http_build_query(
		array(
			'grant_type'    => 'authorization_code',
			'client_id'     => $settings['client_id'],
			'client_secret' => $settings['client_secret'],
			'scope'         => isset( $settings['scope'] ) ? $settings['scope'] : 'openid default',
			'code'          => $code,
			'redirect_uri'  => flexpress_yoursafe_callback_url(),
			'code_verifier' => $data['code_verifier'],
		),
		'',
		'&',
		PHP_QUERY_RFC3986
	);
	// Yoursafe docs show PUT, but the live endpoint returns a bare Apache 403
	// for PUT; only the standard OAuth POST is accepted.
	$response = wp_remote_post(
		FLEXPRESS_YOURSAFE_TOKEN_ENDPOINT,
		array(
			'timeout'     => 15,
			'redirection' => 0,
			'headers'     => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'body'        => $token_body,
		)
	);

	if ( is_wp_error( $response ) ) {
		flexpress_yoursafe_record_failure( 'token_exchange_transport' );
		flexpress_yoursafe_fail( 'token_exchange_failed', $return_url );
	}

	$status = wp_remote_retrieve_response_code( $response );
	if ( in_array( $status, array( 401, 403 ), true ) ) {
		$basic_body = http_build_query(
			array(
				'grant_type'    => 'authorization_code',
				'scope'         => isset( $settings['scope'] ) ? $settings['scope'] : 'openid default',
				'code'          => $code,
				'redirect_uri'  => flexpress_yoursafe_callback_url(),
				'code_verifier' => $data['code_verifier'],
			),
			'',
			'&',
			PHP_QUERY_RFC3986
		);
		$response = wp_remote_post(
			FLEXPRESS_YOURSAFE_TOKEN_ENDPOINT,
			array(
				'timeout'     => 15,
				'redirection' => 0,
				'headers'     => array(
					'Accept'        => 'application/json',
					'Content-Type'  => 'application/x-www-form-urlencoded',
					'Authorization' => 'Basic ' . base64_encode( $settings['client_id'] . ':' . $settings['client_secret'] ),
				),
				'body'        => $basic_body,
			)
		);
		if ( is_wp_error( $response ) ) {
			flexpress_yoursafe_record_failure( 'token_exchange_basic_transport' );
			flexpress_yoursafe_fail( 'token_exchange_failed', $return_url );
		}
		$status = wp_remote_retrieve_response_code( $response );
	}
	if ( 200 !== $status ) {
		$error_body     = json_decode( wp_remote_retrieve_body( $response ), true );
		$provider_error = is_array( $error_body ) && isset( $error_body['error'] ) ? (string) $error_body['error'] : '';
		flexpress_yoursafe_record_failure( 'token_exchange_basic', $status, $provider_error );
		flexpress_yoursafe_fail( 'token_exchange_failed', $return_url );
	}

	$tokens = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $tokens ) || empty( $tokens['id_token'] ) ) {
		flexpress_yoursafe_record_failure( 'token_response', $status, 'missing_id_token' );
		flexpress_yoursafe_fail( 'token_invalid', $return_url );
	}

	$claims = flexpress_yoursafe_verify_id_token( $tokens['id_token'], $settings['client_id'], $data['nonce'] );
	if ( is_wp_error( $claims ) ) {
		flexpress_yoursafe_fail( $claims->get_error_code(), $return_url );
	}

	$valid = flexpress_yoursafe_validate_claims( $claims );
	if ( is_wp_error( $valid ) ) {
		flexpress_yoursafe_fail( $valid->get_error_code(), $return_url );
	}

	$alias       = strtolower( rtrim( (string) $claims['aliastoken'], '.' ) );
	$verified_at = new DateTimeImmutable( (string) $claims['idverifieddate'] );
	$settings    = flexpress_yoursafe_settings();
	$valid_days  = min( 365, max( 1, absint( isset( $settings['verification_valid_days'] ) ? $settings['verification_valid_days'] : 365 ) ) );

	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
		update_user_meta( $user_id, 'flexpress_age_verified', 1 );
		update_user_meta( $user_id, 'flexpress_age_verified_provider', 'yoursafe_id' );
		update_user_meta( $user_id, 'flexpress_age_verified_reference', $alias );
		update_user_meta( $user_id, 'flexpress_age_verified_at', $verified_at->setTimezone( new DateTimeZone( 'UTC' ) )->format( DATE_ATOM ) );
		update_user_meta( $user_id, 'flexpress_age_verified_expires', gmdate( DATE_ATOM, time() + $valid_days * DAY_IN_SECONDS ) );
	}

	nocache_headers();
	flexpress_set_age_verified_cookie();
	wp_safe_redirect( flexpress_age_gate_return_url( $return_url ), 303 );
	exit;
}

/**
 * Handle virtual Yoursafe ID routes before the general age gate.
 *
 * @return void
 */
function flexpress_yoursafe_route_request() {
	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/', PHP_URL_PATH );
	$path = '/' . trim( (string) $path, '/' ) . '/';

	if ( '/age-verification/yoursafe/start/' === $path ) {
		flexpress_yoursafe_start();
	}

	if ( '/age-verification/yoursafe/callback/' === $path ) {
		flexpress_yoursafe_callback();
	}
}
add_action( 'template_redirect', 'flexpress_yoursafe_route_request', -1 );
