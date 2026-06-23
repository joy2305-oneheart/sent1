<?php
/**
 * Plugin bootstrap.
 *
 * @package Sent_One_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SOSL_Plugin
 */
class SOSL_Plugin {

	/**
	 * Init hooks.
	 */
	public static function init() {
		SOSL_Settings::init();
		SOSL_Google_Auth::init();

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_shortcode( 'sent_one_social_login', array( 'SOSL_Button', 'shortcode' ) );
	}

	/**
	 * Enqueue front-end styles on auth pages.
	 */
	public static function enqueue_assets() {
		if ( ! self::is_auth_context() ) {
			return;
		}

		wp_enqueue_style(
			'sosl-social-login',
			SOSL_PLUGIN_URL . 'assets/css/social-login.css',
			array(),
			SOSL_VERSION
		);
	}

	/**
	 * Whether current request is a themed auth page.
	 */
	public static function is_auth_context() {
		if ( function_exists( 'one1_is_auth_page' ) && one1_is_auth_page() ) {
			return true;
		}

		return is_page( array( 'login', 'join' ) ) || ( function_exists( 'one1_is_join_page' ) && one1_is_join_page() && ( ! empty( $_GET['register'] ) || ! empty( $_GET['pu_token'] ) ) );
	}

	/**
	 * Build a safe redirect target after login.
	 *
	 * @param string $redirect_to Requested redirect.
	 */
	public static function sanitize_redirect( $redirect_to ) {
		if ( function_exists( 'one1_sanitize_login_redirect' ) ) {
			return one1_sanitize_login_redirect( $redirect_to );
		}

		return wp_validate_redirect( $redirect_to, home_url( '/' ) );
	}

	/**
	 * Login page URL with optional query args.
	 *
	 * @param array $args Query args.
	 */
	public static function login_page_url( $args = array() ) {
		$url = function_exists( 'one1_login_url' ) ? one1_login_url() : wp_login_url();

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	/**
	 * Signup page URL with optional query args.
	 *
	 * @param array $args Query args.
	 */
	public static function signup_page_url( $args = array() ) {
		$url = function_exists( 'one1_signup_url' ) ? one1_signup_url() : wp_registration_url();

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}
}
