<?php

/**
 * Yoursafe ID admin settings.
 *
 * @package FlexPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FlexPress_Yoursafe_ID_Settings {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings() {
		register_setting(
			'flexpress_yoursafe_id_settings',
			'flexpress_yoursafe_id_settings',
			array( 'sanitize_callback' => array( $this, 'sanitize_settings' ) )
		);

		add_settings_section(
			'flexpress_yoursafe_id_config',
			__( 'Yoursafe ID OpenID Connect', 'flexpress' ),
			array( $this, 'render_section' ),
			'flexpress_yoursafe_id_settings'
		);

		$fields = array(
			'enabled'                 => __( 'Enable Yoursafe ID', 'flexpress' ),
			'client_id'               => __( 'Client ID', 'flexpress' ),
			'client_secret'           => __( 'Client Secret', 'flexpress' ),
			'scope'                   => __( 'OIDC Scope', 'flexpress' ),
			'verification_valid_days' => __( 'Account Proof Lifetime', 'flexpress' ),
		);

		foreach ( $fields as $field => $label ) {
			add_settings_field(
				$field,
				$label,
				array( $this, 'render_field' ),
				'flexpress_yoursafe_id_settings',
				'flexpress_yoursafe_id_config',
				array( 'field' => $field )
			);
		}
	}

	public function sanitize_settings( $input ) {
		$current = get_option( 'flexpress_yoursafe_id_settings', array() );
		$input   = is_array( $input ) ? $input : array();
		$secret  = isset( $input['client_secret'] ) ? trim( (string) $input['client_secret'] ) : '';
		if ( strlen( $secret ) > 512 || preg_match( '/[\x00-\x1F\x7F]/', $secret ) ) {
			$secret = '';
		}

		return array(
			'enabled'                 => empty( $input['enabled'] ) ? 0 : 1,
			'client_id'               => isset( $input['client_id'] ) ? sanitize_text_field( $input['client_id'] ) : '',
			'client_secret'           => '' !== $secret ? $secret : ( isset( $current['client_secret'] ) ? $current['client_secret'] : '' ),
			'scope'                   => isset( $input['scope'] ) && preg_match( '/^[a-zA-Z0-9 _-]+$/', $input['scope'] ) ? sanitize_text_field( $input['scope'] ) : 'openid default',
			'verification_valid_days' => min( 365, max( 1, absint( isset( $input['verification_valid_days'] ) ? $input['verification_valid_days'] : 365 ) ) ),
		);
	}

	public function render_section() {
		echo '<p>' . esc_html__( 'Create a Client Configuration in the Yoursafe Business portal. Use the exact callback URL shown below. The integration remains disabled until valid credentials are saved.', 'flexpress' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Callback URL:', 'flexpress' ) . '</strong> <code>' . esc_html( flexpress_yoursafe_callback_url() ) . '</code></p>';
	}

	public function render_field( $args ) {
		$field    = $args['field'];
		$settings = get_option( 'flexpress_yoursafe_id_settings', array() );
		$value    = isset( $settings[ $field ] ) ? $settings[ $field ] : '';

		if ( 'enabled' === $field ) {
			?>
			<label><input type="checkbox" name="flexpress_yoursafe_id_settings[enabled]" value="1" <?php checked( $value, 1 ); ?>> <?php esc_html_e( 'Offer Yoursafe ID on the Australian age-verification page', 'flexpress' ); ?></label>
			<?php
			return;
		}

		if ( 'client_secret' === $field ) {
			?>
			<input type="password" name="flexpress_yoursafe_id_settings[client_secret]" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo $value ? esc_attr__( 'Saved — leave blank to keep', 'flexpress' ) : ''; ?>">
			<?php
			return;
		}

		if ( 'verification_valid_days' === $field ) {
			?>
			<input type="number" name="flexpress_yoursafe_id_settings[verification_valid_days]" value="<?php echo esc_attr( $value ? $value : 365 ); ?>" min="1" max="365"> <?php esc_html_e( 'days', 'flexpress' ); ?>
			<?php
			return;
		}

		$default = 'scope' === $field ? 'openid default' : '';
		?>
		<input type="text" name="flexpress_yoursafe_id_settings[<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( $value ? $value : $default ); ?>" class="regular-text" autocomplete="off">
		<?php
	}

	public function render_settings_page() {
		$last_error = get_option( 'flexpress_yoursafe_id_last_error', array() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Yoursafe ID Age Verification', 'flexpress' ); ?></h1>
			<p><?php esc_html_e( 'Yoursafe ID authenticates existing Yoursafe YOU account holders and returns an 18+ claim. It does not verify ordinary FlowGuard card purchasers automatically.', 'flexpress' ); ?></p>
			<?php if ( is_array( $last_error ) && ! empty( $last_error['time'] ) ) : ?>
				<div class="notice notice-warning inline"><p>
					<strong><?php esc_html_e( 'Last Yoursafe failure:', 'flexpress' ); ?></strong>
					<?php
					echo esc_html(
						sprintf(
							'%s — stage: %s; HTTP: %d; provider error: %s',
							(string) $last_error['time'],
							isset( $last_error['stage'] ) ? (string) $last_error['stage'] : 'unknown',
							isset( $last_error['http_status'] ) ? (int) $last_error['http_status'] : 0,
							! empty( $last_error['provider_error'] ) ? (string) $last_error['provider_error'] : 'not supplied'
						)
					);
					?>
				</p></div>
			<?php endif; ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'flexpress_yoursafe_id_settings' );
				do_settings_sections( 'flexpress_yoursafe_id_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}

if ( is_admin() ) {
	new FlexPress_Yoursafe_ID_Settings();
}
