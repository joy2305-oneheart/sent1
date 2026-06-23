<?php
/**
 * Google OAuth handler.
 *
 * @package Sent_One_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SOSL_Google_Auth
 */
class SOSL_Google_Auth {

	const STATE_TRANSIENT_PREFIX = 'sosl_google_state_';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'handle_routes' ), 1 );
	}

	/**
	 * Current OAuth route from the request.
	 */
	private static function current_route() {
		if ( empty( $_GET['sosl_auth'] ) ) {
			return '';
		}

		return sanitize_key( wp_unslash( $_GET['sosl_auth'] ) );
	}

	/**
	 * Route public OAuth requests.
	 */
	public static function handle_routes() {
		$route = self::current_route();
		if ( ! $route ) {
			return;
		}

		if ( 'google_start' === $route ) {
			self::handle_start();
		}

		if ( 'google_callback' === $route ) {
			self::handle_callback();
		}
	}

	/**
	 * Build a stable public OAuth URL.
	 *
	 * @param string $action Route action.
	 */
	private static function route_url( $action ) {
		return add_query_arg( 'sosl_auth', $action, site_url( '/' ) );
	}

	/**
	 * OAuth start URL.
	 */
	public static function start_url() {
		return self::route_url( 'google_start' );
	}

	/**
	 * OAuth callback URL sent to Google.
	 */
	public static function callback_url() {
		return self::route_url( 'google_callback' );
	}

	/**
	 * Site origin for Google Cloud Console JavaScript origins.
	 */
	public static function site_origin() {
		$parts = wp_parse_url( site_url( '/' ) );
		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return site_url( '/' );
		}

		$origin = $parts['scheme'] . '://' . $parts['host'];
		if ( ! empty( $parts['port'] ) ) {
			$origin .= ':' . $parts['port'];
		}

		return $origin;
	}

	/**
	 * Start Google OAuth.
	 */
	public static function handle_start() {
		if ( ! SOSL_Settings::is_google_enabled() ) {
			self::redirect_with_error( __( 'Google login is not configured.', 'sent-one-social-login' ), 'login' );
		}

		$context = isset( $_GET['context'] ) ? sanitize_key( wp_unslash( $_GET['context'] ) ) : 'login';
		if ( ! in_array( $context, array( 'login', 'register' ), true ) ) {
			$context = 'login';
		}

		check_admin_referer( 'sosl_google_start_' . $context );

		$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
		$ref         = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
		$pu_token    = isset( $_GET['pu_token'] ) ? sanitize_text_field( wp_unslash( $_GET['pu_token'] ) ) : '';

		$state = wp_generate_password( 32, false, false );
		set_transient(
			self::STATE_TRANSIENT_PREFIX . $state,
			array(
				'context'     => $context,
				'redirect_to' => $redirect_to,
				'ref'         => $ref,
				'pu_token'    => $pu_token,
			),
			10 * MINUTE_IN_SECONDS
		);

		$params = array(
			'client_id'     => SOSL_Settings::get_value( 'google_client_id' ),
			'redirect_uri'  => self::callback_url(),
			'response_type' => 'code',
			'scope'         => 'openid email profile',
			'state'         => $state,
			'access_type'   => 'online',
			'prompt'        => 'select_account',
		);

		wp_redirect( add_query_arg( $params, 'https://accounts.google.com/o/oauth2/v2/auth' ) );
		exit;
	}

	/**
	 * Handle Google OAuth callback.
	 */
	public static function handle_callback() {
		if ( ! SOSL_Settings::is_google_enabled() ) {
			self::redirect_with_error( __( 'Google login is not configured.', 'sent-one-social-login' ), 'login' );
		}

		if ( isset( $_GET['error'] ) ) {
			self::redirect_with_error( __( 'Google sign-in was cancelled.', 'sent-one-social-login' ), 'login' );
		}

		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';

		if ( $code === '' || $state === '' ) {
			self::redirect_with_error( __( 'Invalid Google response.', 'sent-one-social-login' ), 'login' );
		}

		$stored = get_transient( self::STATE_TRANSIENT_PREFIX . $state );
		delete_transient( self::STATE_TRANSIENT_PREFIX . $state );

		if ( ! is_array( $stored ) ) {
			self::redirect_with_error( __( 'Your Google sign-in session expired. Please try again.', 'sent-one-social-login' ), 'login' );
		}

		$context     = isset( $stored['context'] ) ? $stored['context'] : 'login';
		$redirect_to = isset( $stored['redirect_to'] ) ? $stored['redirect_to'] : '';
		$ref         = isset( $stored['ref'] ) ? $stored['ref'] : '';
		$pu_token    = isset( $stored['pu_token'] ) ? $stored['pu_token'] : '';

		$token_response = self::exchange_code_for_token( $code );
		if ( is_wp_error( $token_response ) ) {
			self::redirect_with_error( $token_response->get_error_message(), $context, $ref, $pu_token );
		}

		$profile = self::fetch_user_profile( $token_response['access_token'] );
		if ( is_wp_error( $profile ) ) {
			self::redirect_with_error( $profile->get_error_message(), $context, $ref, $pu_token );
		}

		$user = SOSL_User_Manager::resolve_user( $profile, $ref, $context, $pu_token );
		if ( is_wp_error( $user ) ) {
			self::redirect_with_error( $user->get_error_message(), $context, $ref, $pu_token );
		}

		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true, is_ssl() );
		do_action( 'wp_login', $user->user_login, $user );

		if ( $ref !== '' && function_exists( 'one1_apply_invite_ref_for_user' ) ) {
			one1_apply_invite_ref_for_user( (int) $user->ID, $ref );
		}

		$redirect_to = SOSL_Plugin::sanitize_redirect( $redirect_to );
		if ( ! $redirect_to && $ref !== '' && function_exists( 'one1_share_page_url' ) ) {
			$redirect_to = one1_share_page_url();
		}
		if ( ! $redirect_to && user_can( $user, 'manage_options' ) && function_exists( 'one1_dashboard_url' ) ) {
			$redirect_to = one1_dashboard_url();
		}
		if ( ! $redirect_to && function_exists( 'one1_member_default_redirect_url' ) ) {
			$redirect_to = one1_member_default_redirect_url();
		}
		if ( ! $redirect_to ) {
			$redirect_to = home_url( '/' );
		}

		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Exchange authorization code for access token.
	 *
	 * @param string $code Authorization code.
	 */
	private static function exchange_code_for_token( $code ) {
		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body'    => array(
					'code'          => $code,
					'client_id'     => SOSL_Settings::get_value( 'google_client_id' ),
					'client_secret' => SOSL_Settings::get_value( 'google_client_secret' ),
					'redirect_uri'  => self::callback_url(),
					'grant_type'    => 'authorization_code',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['access_token'] ) ) {
			$message = isset( $body['error_description'] ) ? $body['error_description'] : __( 'Could not authenticate with Google.', 'sent-one-social-login' );
			return new WP_Error( 'sosl_google_token', sanitize_text_field( $message ) );
		}

		return $body;
	}

	/**
	 * Fetch Google profile.
	 *
	 * @param string $access_token Access token.
	 */
	private static function fetch_user_profile( $access_token ) {
		$response = wp_remote_get(
			'https://openidconnect.googleapis.com/v1/userinfo',
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['sub'] ) || empty( $body['email'] ) ) {
			return new WP_Error( 'sosl_google_profile', __( 'Google did not return a valid email address.', 'sent-one-social-login' ) );
		}

		if ( empty( $body['email_verified'] ) ) {
			return new WP_Error( 'sosl_google_profile', __( 'Your Google email address is not verified.', 'sent-one-social-login' ) );
		}

		return array(
			'google_id' => sanitize_text_field( $body['sub'] ),
			'email'     => sanitize_email( $body['email'] ),
			'name'      => isset( $body['name'] ) ? sanitize_text_field( $body['name'] ) : '',
			'picture'   => isset( $body['picture'] ) ? esc_url_raw( $body['picture'] ) : '',
		);
	}

	/**
	 * Redirect back to auth page with an error.
	 *
	 * @param string $message Error message.
	 * @param string $context login|register.
	 * @param string $ref     Invite ref.
	 * @param string $pu_token Admin PU token.
	 */
	private static function redirect_with_error( $message, $context = 'login', $ref = '', $pu_token = '' ) {
		$args = array( 'sosl_error' => rawurlencode( $message ) );

		if ( 'register' === $context ) {
			$url = function_exists( 'one1_signup_url' ) ? one1_signup_url( $ref, $pu_token ) : wp_registration_url();
			wp_safe_redirect( add_query_arg( $args, $url ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( $args, SOSL_Plugin::login_page_url() ) );
		exit;
	}
}
