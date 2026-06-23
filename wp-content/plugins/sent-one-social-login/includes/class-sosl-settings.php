<?php
/**
 * Admin settings.
 *
 * @package Sent_One_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SOSL_Settings
 */
class SOSL_Settings {

	const OPTION_KEY = 'sosl_settings';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Default settings.
	 */
	public static function defaults() {
		return array(
			'google_enabled'  => 0,
			'google_client_id' => '',
			'google_client_secret' => '',
		);
	}

	/**
	 * Get settings.
	 */
	public static function get() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key Setting key.
	 */
	public static function get_value( $key ) {
		$settings = self::get();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : '';
	}

	/**
	 * Whether Google login is configured and enabled.
	 */
	public static function is_google_enabled() {
		$settings = self::get();

		return ! empty( $settings['google_enabled'] )
			&& ! empty( $settings['google_client_id'] )
			&& ! empty( $settings['google_client_secret'] );
	}

	/**
	 * Register settings page.
	 */
	public static function register_menu() {
		add_options_page(
			__( 'Sent One Social Login', 'sent-one-social-login' ),
			__( 'Social Login', 'sent-one-social-login' ),
			'manage_options',
			'sent-one-social-login',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register settings fields.
	 */
	public static function register_settings() {
		register_setting(
			'sosl_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Raw input.
	 */
	public static function sanitize( $input ) {
		$output = self::defaults();

		if ( ! is_array( $input ) ) {
			return $output;
		}

		$output['google_enabled']       = ! empty( $input['google_enabled'] ) ? 1 : 0;
		$output['google_client_id']     = isset( $input['google_client_id'] ) ? sanitize_text_field( $input['google_client_id'] ) : '';
		$output['google_client_secret'] = isset( $input['google_client_secret'] ) ? sanitize_text_field( $input['google_client_secret'] ) : '';

		return $output;
	}

	/**
	 * Render settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings     = self::get();
		$callback_url = SOSL_Google_Auth::callback_url();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Sent One Social Login', 'sent-one-social-login' ); ?></h1>
			<p><?php esc_html_e( 'Connect Google sign-in to your custom login and signup pages.', 'sent-one-social-login' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'sosl_settings_group' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Google login', 'sent-one-social-login' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[google_enabled]" value="1" <?php checked( 1, (int) $settings['google_enabled'] ); ?> />
								<?php esc_html_e( 'Show the Google button on login and signup pages', 'sent-one-social-login' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sosl-google-client-id"><?php esc_html_e( 'Google Client ID', 'sent-one-social-login' ); ?></label></th>
						<td>
							<input type="text" class="regular-text code" id="sosl-google-client-id" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[google_client_id]" value="<?php echo esc_attr( $settings['google_client_id'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sosl-google-client-secret"><?php esc_html_e( 'Google Client Secret', 'sent-one-social-login' ); ?></label></th>
						<td>
							<input type="password" class="regular-text code" id="sosl-google-client-secret" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[google_client_secret]" value="<?php echo esc_attr( $settings['google_client_secret'] ); ?>" autocomplete="new-password" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Authorized redirect URI', 'sent-one-social-login' ); ?></th>
						<td>
							<input type="text" class="large-text code" readonly value="<?php echo esc_attr( $callback_url ); ?>" onclick="this.select();" />
							<p class="description">
								<?php esc_html_e( 'Copy this exact URL into Google Cloud Console → APIs & Services → Credentials → your OAuth client → Authorized redirect URIs.', 'sent-one-social-login' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Authorized JavaScript origin', 'sent-one-social-login' ); ?></th>
						<td>
							<input type="text" class="regular-text code" readonly value="<?php echo esc_attr( SOSL_Google_Auth::site_origin() ); ?>" onclick="this.select();" />
							<p class="description">
								<?php esc_html_e( 'Add this exact origin in the same Google OAuth client under Authorized JavaScript origins.', 'sent-one-social-login' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
