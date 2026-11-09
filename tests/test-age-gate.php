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

// Signed results belong to one member, not merely to whoever has the metadata.
$GLOBALS['age_meta'] = []; $GLOBALS['age_user'] = 7;
function get_current_user_id() { return $GLOBALS['age_user']; }
function update_user_meta($id, $key, $value) { $GLOBALS['age_meta'][$id][$key] = $value; }
function get_user_meta($id, $key, $single) { return $GLOBALS['age_meta'][$id][$key] ?? ''; }
function is_user_logged_in() { return get_current_user_id() > 0; }
function member_check($value) { if (!$value) { throw new RuntimeException('Member age validation failed'); } }
flexpress_save_member_age_result(7, 'yoursafe_id', time() - 10, time() + 86400);
member_check(flexpress_member_age_result()['provider'] === 'yoursafe_id');
$GLOBALS['age_meta'][8] = $GLOBALS['age_meta'][7]; $GLOBALS['age_user'] = 8;
member_check(flexpress_member_age_result() === null);
$GLOBALS['age_user'] = 7;
$GLOBALS['age_meta'][7]['_flexpress_member_age_result']['payload'] .= ' ';
member_check(flexpress_member_age_result() === null);
$expired = json_encode(['user_id' => 7, 'provider' => 'didit', 'verified_at' => time() - 100, 'expires_at' => time() - 1]);
$GLOBALS['age_meta'][7]['_flexpress_member_age_result'] = ['payload' => $expired, 'signature' => hash_hmac('sha256', 'member-age:' . $expired, wp_salt('auth'))];
member_check(flexpress_member_age_result() === null);
$GLOBALS['age_user'] = 0;
member_check(flexpress_member_age_result() === null);
echo "Member signature, account binding and expiry tests passed.\n";
