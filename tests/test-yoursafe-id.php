<?php

/** Standalone Yoursafe ID claim-validation tests. */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );

class WP_Error {
	private $code;
	public function __construct( $code ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}

function add_action() {}
function get_option() { return array( 'verification_valid_days' => 365 ); }
function absint( $value ) { return abs( (int) $value ); }
function get_transient() { global $test_jwks; return $test_jwks; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }

require_once dirname( __DIR__ ) . '/wp-content/themes/flexpress/includes/age-gate.php';
require_once dirname( __DIR__ ) . '/wp-content/themes/flexpress/includes/class-flexpress-yoursafe-id.php';

$valid_claims = array(
	'eighteenplus'   => true,
	'accountstatus'  => 'active',
	'idverifieddate' => gmdate( DATE_ATOM, time() - DAY_IN_SECONDS ),
	'aliastoken'     => 'abc123.yoursafe.id',
);

if ( true !== flexpress_yoursafe_validate_claims( $valid_claims ) ) {
	fwrite( STDERR, "Valid Yoursafe claims were rejected.\n" );
	exit( 1 );
}

// An old identity-verification date is valid: Yoursafe verifies identity once
// and recomputes eighteenplus at every login.
$old_verification = $valid_claims;
$old_verification['idverifieddate'] = gmdate( DATE_ATOM, time() - 5 * 365 * DAY_IN_SECONDS );
if ( true !== flexpress_yoursafe_validate_claims( $old_verification ) ) {
	fwrite( STDERR, "Yoursafe claims with an old idverifieddate were rejected.\n" );
	exit( 1 );
}

$invalid_cases = array(
	array( 'eighteenplus', false, 'not_over_18' ),
	array( 'accountstatus', 'inactive', 'account_inactive' ),
	array( 'idverifieddate', gmdate( DATE_ATOM, time() + HOUR_IN_SECONDS ), 'verification_date_invalid' ),
	array( 'idverifieddate', '', 'verification_date_missing' ),
	array( 'aliastoken', 'attacker.example.com', 'alias_invalid' ),
);

foreach ( $invalid_cases as $case ) {
	$claims             = $valid_claims;
	$claims[ $case[0] ] = $case[1];
	$result              = flexpress_yoursafe_validate_claims( $claims );
	if ( ! $result instanceof WP_Error || $case[2] !== $result->get_error_code() ) {
		fwrite( STDERR, 'Failed Yoursafe claim case: ' . var_export( $case, true ) . PHP_EOL );
		exit( 1 );
	}
}

$private_key = openssl_pkey_new(
	array(
		'private_key_bits' => 2048,
		'private_key_type' => OPENSSL_KEYTYPE_RSA,
	)
);
$key_details = openssl_pkey_get_details( $private_key );
$test_jwks = array(
	array(
		'kid' => 'test-key',
		'kty' => 'RSA',
		'n'   => flexpress_age_base64url_encode( $key_details['rsa']['n'] ),
		'e'   => flexpress_age_base64url_encode( $key_details['rsa']['e'] ),
	),
);
$header = flexpress_age_base64url_encode( json_encode( array( 'alg' => 'RS256', 'kid' => 'test-key', 'typ' => 'JWT' ) ) );
$payload = flexpress_age_base64url_encode(
	json_encode(
		array_merge(
			$valid_claims,
			array(
				'iss'   => FLEXPRESS_YOURSAFE_ISSUER,
				'aud'   => 'test-client',
				'sub'   => 'abc123.yoursafe.id',
				'nonce' => 'test-nonce',
				'iat'   => time() - 5,
				'exp'   => time() + 300,
			)
		)
	)
);
openssl_sign( $header . '.' . $payload, $signature, $private_key, OPENSSL_ALGO_SHA256 );
$jwt = $header . '.' . $payload . '.' . flexpress_age_base64url_encode( $signature );
$verified_token = flexpress_yoursafe_verify_id_token( $jwt, 'test-client', 'test-nonce' );
if ( ! is_array( $verified_token ) || true !== $verified_token['eighteenplus'] ) {
	fwrite( STDERR, "Valid Yoursafe ID token was rejected.\n" );
	exit( 1 );
}

$bad_token = flexpress_yoursafe_verify_id_token( $jwt . 'x', 'test-client', 'test-nonce' );
if ( ! $bad_token instanceof WP_Error || 'id_token_signature' !== $bad_token->get_error_code() ) {
	fwrite( STDERR, "Tampered Yoursafe ID token was accepted.\n" );
	exit( 1 );
}

echo "Yoursafe ID tests passed.\n";
