<?php

/**
 * Standalone regression tests for request geolocation.
 *
 * Run: php tests/test-geolocation.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'FLEXPRESS_COUNTRY_OVERRIDE', 'nz' );

$test_environment = 'local';

function wp_get_environment_type() {
	global $test_environment;
	return $test_environment;
}

function add_action() {}
function is_admin() { return false; }
function wp_doing_cron() { return false; }

require_once dirname( __DIR__ ) . '/wp-content/themes/flexpress/includes/geolocation.php';

$cases = array(
	array( 'AU', 'AU', 'AU', 'country_au' ),
	array( 'us', 'NON_AU', 'US', 'country_non_au' ),
	array( ' nz ', 'NON_AU', 'NZ', 'country_non_au' ),
	array( 'XX', 'UNKNOWN', 'XX', 'cloudflare_unknown' ),
	array( 'T1', 'UNKNOWN', 'T1', 'cloudflare_tor' ),
	array( null, 'UNKNOWN', null, 'missing_header' ),
	array( '', 'UNKNOWN', null, 'missing_header' ),
	array( 'AU,US', 'UNKNOWN', 'AU,US', 'multiple_values' ),
	array( 'ZZ', 'UNKNOWN', 'ZZ', 'invalid_country_code' ),
	array( '<script>', 'UNKNOWN', '<SCRIPT>', 'invalid_country_code' ),
);

foreach ( $cases as $case ) {
	$result = flexpress_classify_country( $case[0] );
	if ( $case[1] !== $result['classification'] || $case[2] !== $result['country_code'] || $case[3] !== $result['reason'] ) {
		fwrite( STDERR, 'Failed case: ' . var_export( $case, true ) . PHP_EOL );
		exit( 1 );
	}
}

if ( 'nz' !== flexpress_get_country_override( 'local' ) || 'nz' !== flexpress_get_country_override( 'development' ) ) {
	fwrite( STDERR, "Development override was not enabled.\n" );
	exit( 1 );
}

if ( null !== flexpress_get_country_override( 'staging' ) || null !== flexpress_get_country_override( 'production' ) ) {
	fwrite( STDERR, "Country override was accepted outside development.\n" );
	exit( 1 );
}

$_SERVER['HTTP_CF_IPCOUNTRY'] = 'AU';
$override_result = flexpress_get_request_geolocation();
if ( 'NON_AU' !== $override_result['classification'] || 'development_override' !== $override_result['source'] ) {
	fwrite( STDERR, "Local override was not applied.\n" );
	exit( 1 );
}

echo "Geolocation tests passed.\n";
