<?php
/**
 * Login button renderer.
 *
 * @package Sent_One_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SOSL_Button
 */
class SOSL_Button {

	/**
	 * Shortcode handler.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'context'     => 'login',
				'redirect_to' => '',
				'ref'         => '',
				'pu_token'    => '',
			),
			$atts,
			'sent_one_social_login'
		);

		ob_start();
		self::render(
			array(
				'context'     => $atts['context'],
				'redirect_to' => $atts['redirect_to'],
				'ref'         => $atts['ref'],
				'pu_token'    => $atts['pu_token'],
			)
		);
		return ob_get_clean();
	}

	/**
	 * Render social login buttons.
	 *
	 * @param array $args Render args.
	 */
	public static function render( $args = array() ) {
		if ( is_user_logged_in() || ! SOSL_Settings::is_google_enabled() ) {
			return;
		}

		$args = wp_parse_args(
			$args,
			array(
				'context'     => 'login',
				'redirect_to' => '',
				'ref'         => '',
				'pu_token'    => '',
			)
		);

		$context = in_array( $args['context'], array( 'login', 'register' ), true ) ? $args['context'] : 'login';
		$url     = wp_nonce_url(
			add_query_arg(
				array(
					'context'     => $context,
					'redirect_to' => SOSL_Plugin::sanitize_redirect( $args['redirect_to'] ),
					'ref'         => sanitize_text_field( $args['ref'] ),
					'pu_token'    => sanitize_text_field( $args['pu_token'] ),
				),
				SOSL_Google_Auth::start_url()
			),
			'sosl_google_start_' . $context
		);

		$label = 'register' === $context
			? __( 'Sign up with Google', 'sent-one-social-login' )
			: __( 'Continue with Google', 'sent-one-social-login' );

		include SOSL_PLUGIN_DIR . 'templates/google-button.php';
	}
}
