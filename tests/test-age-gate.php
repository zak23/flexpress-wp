<?php

/** Standalone age-gate regression tests. */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'COOKIEPATH', '/' );
define( 'COOKIE_DOMAIN', '' );

function add_action() {}
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_salt() { return 'test-secret'; }

require_once dirname( __DIR__ ) . '/wp-content/themes/flexpress/includes/age-gate.php';

$age_cases = array(
	array( '2008-07-03', '2026-07-03', true ),
	array( '2008-07-04', '2026-07-03', false ),
	array( '2008-02-29', '2026-02-28', false ),
	array( '2008-02-29', '2026-03-01', true ),
	array( '2027-01-01', '2026-07-03', false ),
	array( '1900-01-01', '2026-07-03', false ),
	array( '2020-02-30', '2026-07-03', false ),
	array( 'not-a-date', '2026-07-03', false ),
);

foreach ( $age_cases as $case ) {
	if ( $case[2] !== flexpress_is_at_least_18( $case[0], $case[1] ) ) {
		fwrite( STDERR, 'Failed DOB case: ' . var_export( $case, true ) . PHP_EOL );
		exit( 1 );
	}
}

$normalization_cases = array(
	array( '31/12/1990', '1990-12-31' ),
	array( ' 03/07/2000 ', '2000-07-03' ),
	array( '3/7/2000', '' ),
	array( '2000-07-03', '' ),
	array( 'not-a-date', '' ),
);

foreach ( $normalization_cases as $case ) {
	if ( $case[1] !== flexpress_normalize_australian_dob( $case[0] ) ) {
		fwrite( STDERR, 'Failed DOB normalization case: ' . var_export( $case, true ) . PHP_EOL );
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
