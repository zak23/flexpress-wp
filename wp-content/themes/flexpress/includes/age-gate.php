<?php

/**
 * Australian age-verification gate.
 *
 * @package FlexPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FLEXPRESS_AGE_COOKIE', 'flexpress_age_verified' );
define( 'FLEXPRESS_AGE_COOKIE_VERSION', 1 );
define( 'FLEXPRESS_AGE_COOKIE_LIFETIME', 90 * DAY_IN_SECONDS );

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

/**
 * Return whether the current route may bypass the gate.
 *
 * @return bool
 */
function flexpress_age_gate_is_exempt_request() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return true;
	}

	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/', PHP_URL_PATH );
	$path = '/' . trim( (string) $path, '/' ) . '/';
	$exempt_paths = array(
		'/age-verification/',
		'/privacy/',
		'/privacy-policy/',
		'/terms/',
		'/support/',
		'/contact/',
		'/content-removal/',
		'/2257-compliance/',
		'/anti-slavery-and-human-trafficking-policy/',
		'/login/',
		'/wp-login.php/',
	);

	return in_array( $path, $exempt_paths, true );
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
 * Determine whether a geolocation decision is subject to this gate.
 *
 * Only confirmed Australian requests are gated. NON_AU and UNKNOWN requests
 * remain accessible while the temporary development policy is in effect.
 *
 * @param mixed $geolocation Geolocation decision.
 * @return bool
 */
function flexpress_age_gate_applies_to_geolocation( $geolocation ) {
	return is_array( $geolocation )
		&& isset( $geolocation['classification'] )
		&& FLEXPRESS_GEO_AU === $geolocation['classification'];
}

/**
 * Render and process the neutral age-verification page.
 *
 * @return void
 */
function flexpress_render_age_verification_page() {
	$error      = '';
	$return_url = flexpress_age_gate_return_url( isset( $_REQUEST['return_to'] ) ? $_REQUEST['return_to'] : home_url( '/' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Used only as a validated redirect target.
	$yoursafe_errors = array(
		'not_configured'       => __( 'Yoursafe ID is not configured yet.', 'flexpress' ),
		'state_invalid'        => __( 'The Yoursafe verification session expired. Please try again.', 'flexpress' ),
		'cancelled'            => __( 'Yoursafe verification was cancelled.', 'flexpress' ),
		'code_missing'         => __( 'Yoursafe did not return a valid authorization code.', 'flexpress' ),
		'token_exchange_failed'=> __( 'Yoursafe could not complete verification. Please try again.', 'flexpress' ),
		'token_invalid'        => __( 'Yoursafe returned an invalid verification response.', 'flexpress' ),
		'userinfo_failed'      => __( 'Yoursafe could not provide the age result. Please try again.', 'flexpress' ),
		'userinfo_invalid'     => __( 'Yoursafe returned an invalid age result.', 'flexpress' ),
		'jwks_failed'          => __( 'Yoursafe signing keys could not be loaded. Please try again.', 'flexpress' ),
		'jwks_invalid'         => __( 'Yoursafe returned invalid signing keys.', 'flexpress' ),
		'id_token_format'      => __( 'Yoursafe returned a malformed identity token.', 'flexpress' ),
		'id_token_algorithm'   => __( 'Yoursafe returned an unsupported identity token.', 'flexpress' ),
		'id_token_signature'   => __( 'The Yoursafe identity token could not be authenticated.', 'flexpress' ),
		'id_token_claims'      => __( 'The Yoursafe identity token did not match this verification session.', 'flexpress' ),
		'not_over_18'          => __( 'Yoursafe could not confirm that you are over 18.', 'flexpress' ),
		'account_inactive'     => __( 'Your Yoursafe account is not active.', 'flexpress' ),
		'verification_date_missing' => __( 'Yoursafe did not provide an identity-verification date.', 'flexpress' ),
		'verification_date_invalid' => __( 'Yoursafe returned an invalid identity-verification date.', 'flexpress' ),
		'verification_expired' => __( 'Your Yoursafe identity verification must be renewed.', 'flexpress' ),
		'alias_invalid'        => __( 'Yoursafe did not provide a valid account reference.', 'flexpress' ),
	);
	$yoursafe_error = isset( $_GET['yoursafe_error'] ) ? sanitize_key( wp_unslash( $_GET['yoursafe_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only error code.
	if ( isset( $yoursafe_errors[ $yoursafe_error ] ) ) {
		$error = $yoursafe_errors[ $yoursafe_error ];
	}

	nocache_headers();
	status_header( 200 );
	header( 'X-Robots-Tag: noindex, nofollow', true );
	?>
	<!doctype html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php esc_html_e( 'Age verification', 'flexpress' ); ?></title>
		<style>
			body{margin:0;background:#111;color:#fff;font:16px/1.5 system-ui,sans-serif;text-align:center}main{max-width:30rem;margin:7vh auto;padding:2rem}.age-logo{margin:0 auto 2rem}.age-logo img{display:inline-block;max-width:min(300px,80vw);max-height:110px;width:auto;height:auto}.login-button,.yoursafe-button{box-sizing:border-box;width:100%;padding:.8rem;font:inherit;text-align:center;border-radius:.25rem;cursor:pointer;font-weight:700;border:0;text-decoration:none}.yoursafe-option{display:grid;gap:.75rem;margin:1rem 0}.yoursafe-button{display:block;background:#1976d2;color:#fff}.yoursafe-button:hover,.yoursafe-button:focus{background:#1565c0}.login-option{display:grid;gap:.75rem;margin:1rem 0}.login-separator{color:#aaa}.login-button{display:block;background:#fff;color:#111}.login-button:hover,.login-button:focus{background:#e7e7e7;color:#111}.error{padding:1rem;background:#641c1c;border-radius:.25rem}.note{color:#bbb;font-size:.9rem}.rules-note{margin-top:2rem;padding-top:1.25rem;border-top:1px solid #333;color:#999;font-size:.75rem;line-height:1.5}a{color:#fff}
		</style>
	</head>
	<body>
		<main>
			<div class="age-logo">
				<?php
				if ( function_exists( 'flexpress_display_logo' ) ) {
					flexpress_display_logo(
						array(
							'class'       => 'age-verification-logo',
							'title_class' => 'age-verification-site-title',
							'title_tag'   => 'div',
							'force_type'  => 'primary',
						)
					);
				} else {
					echo esc_html( get_bloginfo( 'name' ) );
				}
				?>
			</div>
			<h1><?php esc_html_e( 'Age verification', 'flexpress' ); ?></h1>
			<p><?php esc_html_e( 'You must be 18 or older to access this website.', 'flexpress' ); ?></p>
			<?php if ( $error ) : ?>
				<p class="error" role="alert"><?php echo esc_html( $error ); ?></p>
			<?php endif; ?>
			<?php if ( function_exists( 'flexpress_yoursafe_is_enabled' ) && flexpress_yoursafe_is_enabled() ) : ?>
				<div class="yoursafe-option">
					<a class="yoursafe-button" href="<?php echo esc_url( flexpress_yoursafe_start_url( $return_url ) ); ?>"><?php esc_html_e( 'Verify with Yoursafe ID', 'flexpress' ); ?></a>
				</div>
			<?php else : ?>
				<p class="error" role="alert"><?php esc_html_e( 'Age verification is temporarily unavailable.', 'flexpress' ); ?></p>
			<?php endif; ?>
			<div class="login-option">
				<span class="login-separator"><?php esc_html_e( '-or-', 'flexpress' ); ?></span>
				<a class="login-button" href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php esc_html_e( 'Login', 'flexpress' ); ?></a>
			</div>
			<p class="note"><a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>"><?php esc_html_e( 'Privacy policy', 'flexpress' ); ?></a></p>
			<div class="rules-note">
				<p><?php esc_html_e( 'Why are you seeing this? New Australian online-safety rules require services carrying adult material to take steps to prevent people under 18 from accessing it. The result is that adults visiting from Australia now have to get through an age check before viewing lawful adult content.', 'flexpress' ); ?></p>
				<p><?php esc_html_e( 'We know this adds friction and creates understandable privacy concerns. Yoursafe ID confirms that you are over 18 without sharing or storing your date of birth with this site.', 'flexpress' ); ?></p>
			</div>
		</main>
	</body>
	</html>
	<?php
	exit;
}

/**
 * Route Australian visitors through the age gate.
 *
 * @return void
 */
function flexpress_enforce_temporary_age_gate() {
	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/', PHP_URL_PATH );
	if ( '/age-verification/' === '/' . trim( (string) $path, '/' ) . '/' ) {
		flexpress_render_age_verification_page();
	}

	if ( flexpress_age_gate_is_exempt_request() || is_user_logged_in() || flexpress_is_age_verified() ) {
		return;
	}

	$geo = flexpress_get_request_geolocation();
	if ( ! flexpress_age_gate_applies_to_geolocation( $geo ) ) {
		return;
	}

	$return_url = home_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );
	$gate_url   = add_query_arg( 'return_to', $return_url, home_url( '/age-verification/' ) );
	nocache_headers();
	wp_safe_redirect( $gate_url, 302 );
	exit;
}
add_action( 'template_redirect', 'flexpress_enforce_temporary_age_gate', 0 );
