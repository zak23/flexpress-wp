<?php

/**
 * Trusted request geolocation supplied by Cloudflare.
 *
 * This module classifies requests only. It deliberately does not gate content.
 *
 * @package FlexPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FLEXPRESS_GEO_AU', 'AU' );
define( 'FLEXPRESS_GEO_NON_AU', 'NON_AU' );
define( 'FLEXPRESS_GEO_UNKNOWN', 'UNKNOWN' );

/**
 * Return all assigned ISO 3166-1 alpha-2 country codes.
 *
 * @return array<string, bool>
 */
function flexpress_iso_country_codes() {
	static $codes = null;

	if ( null === $codes ) {
		$values = explode(
			' ',
			'AD AE AF AG AI AL AM AO AQ AR AS AT AU AW AX AZ BA BB BD BE BF BG BH BI BJ BL BM BN BO BQ BR BS BT BV BW BY BZ CA CC CD CF CG CH CI CK CL CM CN CO CR CU CV CW CX CY CZ DE DJ DK DM DO DZ EC EE EG EH ER ES ET FI FJ FK FM FO FR GA GB GD GE GF GG GH GI GL GM GN GP GQ GR GS GT GU GW GY HK HM HN HR HT HU ID IE IL IM IN IO IQ IR IS IT JE JM JO JP KE KG KH KI KM KN KP KR KW KY KZ LA LB LC LI LK LR LS LT LU LV LY MA MC MD ME MF MG MH MK ML MM MN MO MP MQ MR MS MT MU MV MW MX MY MZ NA NC NE NF NG NI NL NO NP NR NU NZ OM PA PE PF PG PH PK PL PM PN PR PS PT PW PY QA RE RO RS RU RW SA SB SC SD SE SG SH SI SJ SK SL SM SN SO SR SS ST SV SX SY SZ TC TD TF TG TH TJ TK TL TM TN TO TR TT TV TW TZ UA UG UM US UY UZ VA VC VE VG VI VN VU WF WS YE YT ZA ZM ZW'
		);
		$codes  = array_fill_keys( $values, true );
	}

	return $codes;
}

/**
 * Classify a raw Cloudflare country value.
 *
 * @param mixed $raw_country Raw CF-IPCountry header value.
 * @return array{classification:string,country_code:?string,source:string,reason:string}
 */
function flexpress_classify_country( $raw_country ) {
	$result = array(
		'classification' => FLEXPRESS_GEO_UNKNOWN,
		'country_code'   => null,
		'source'         => 'cloudflare',
		'reason'         => 'missing_header',
	);

	if ( ! is_string( $raw_country ) || '' === trim( $raw_country ) ) {
		return $result;
	}

	$country = strtoupper( trim( $raw_country ) );
	$result['country_code'] = $country;

	if ( false !== strpos( $country, ',' ) ) {
		$result['reason'] = 'multiple_values';
		return $result;
	}

	if ( 'XX' === $country ) {
		$result['reason'] = 'cloudflare_unknown';
		return $result;
	}

	if ( 'T1' === $country ) {
		$result['reason'] = 'cloudflare_tor';
		return $result;
	}

	if ( ! isset( flexpress_iso_country_codes()[ $country ] ) ) {
		$result['reason'] = 'invalid_country_code';
		return $result;
	}

	if ( 'AU' === $country ) {
		$result['classification'] = FLEXPRESS_GEO_AU;
		$result['reason']         = 'country_au';
		return $result;
	}

	$result['classification'] = FLEXPRESS_GEO_NON_AU;
	$result['reason']         = 'country_non_au';
	return $result;
}

/**
 * Return a configured local country override when the environment permits it.
 *
 * @param string $environment WordPress environment type.
 * @return mixed|null Configured override, or null when overrides are disabled.
 */
function flexpress_get_country_override( $environment ) {
	if ( ! in_array( $environment, array( 'local', 'development' ), true ) ) {
		return null;
	}

	return defined( 'FLEXPRESS_COUNTRY_OVERRIDE' ) ? FLEXPRESS_COUNTRY_OVERRIDE : null;
}

/**
 * Resolve geolocation for the current request.
 *
 * A local override must be configured as FLEXPRESS_COUNTRY_OVERRIDE in
 * wp-config.php. It is ignored outside local and development environments.
 *
 * @return array{classification:string,country_code:?string,source:string,reason:string}
 */
function flexpress_get_request_geolocation() {
	static $result = null;

	if ( null !== $result ) {
		return $result;
	}

	$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
	$override    = flexpress_get_country_override( $environment );

	if ( null !== $override ) {
		$result           = flexpress_classify_country( $override );
		$result['source'] = 'development_override';
		return $result;
	}

	$header = isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Classified against a strict allowlist.
	$result = flexpress_classify_country( $header );
	return $result;
}

/**
 * Increment one aggregate request counter without retaining visitor data.
 *
 * Counters have bounded classification/reason values and are grouped by UTC
 * day. A single atomic SQL statement avoids lost increments under concurrency.
 *
 * @return void
 */
function flexpress_record_geolocation_aggregate() {
	if ( is_admin() || wp_doing_cron() ) {
		return;
	}

	$geo = flexpress_get_request_geolocation();
	$allowed_reasons = array(
		'missing_header',
		'multiple_values',
		'cloudflare_unknown',
		'cloudflare_tor',
		'invalid_country_code',
		'country_au',
		'country_non_au',
	);
	$reason = in_array( $geo['reason'], $allowed_reasons, true ) ? $geo['reason'] : 'invalid_country_code';
	$key = sprintf(
		'flexpress_geo_%s_%s_%s',
		gmdate( 'Ymd' ),
		strtolower( $geo['classification'] ),
		$reason
	);

	global $wpdb;
	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, '1', 'no') ON DUPLICATE KEY UPDATE option_value = CAST(option_value AS UNSIGNED) + 1",
			$key
		)
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic aggregate counter.
}
add_action( 'init', 'flexpress_record_geolocation_aggregate', 2 );
