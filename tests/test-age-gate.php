<?php

/** Standalone age-gate regression tests. */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'COOKIEPATH', '/' );
define( 'COOKIE_DOMAIN', '' );
define( 'FLEXPRESS_GEO_AU', 'AU' );

function add_action() {}
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_salt() { return 'test-secret'; }

require_once dirname( __DIR__ ) . '/wp-content/themes/flexpress/includes/age-gate.php';

$geolocation_cases = array(
	array( array( 'classification' => 'AU' ), true ),
	array( array( 'classification' => 'NON_AU' ), false ),
	array( array( 'classification' => 'UNKNOWN' ), false ),
	array( array(), false ),
	array( null, false ),
);

foreach ( $geolocation_cases as $case ) {
	if ( $case[1] !== flexpress_age_gate_applies_to_geolocation( $case[0] ) ) {
		fwrite( STDERR, 'Failed geolocation gate case: ' . var_export( $case, true ) . PHP_EOL );
		exit( 1 );
	}
}

$issued = 1700000000;
$cookie = flexpress_create_age_cookie_value( $issued );
if ( ! flexpress_validate_age_cookie( $cookie, $issued + 1 ) ) {
	fwrite( STDERR, "Valid cookie was rejected.\n" );
	exit( 1 );
}
if ( flexpress_validate_age_cookie( $cookie . 'x', $issued + 1 ) ) {
	fwrite( STDERR, "Tampered cookie was accepted.\n" );
	exit( 1 );
}
if ( flexpress_validate_age_cookie( $cookie, $issued + FLEXPRESS_AGE_COOKIE_LIFETIME ) ) {
	fwrite( STDERR, "Expired cookie was accepted.\n" );
	exit( 1 );
}

echo "Age-gate tests passed.\n";
