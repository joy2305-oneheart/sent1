<?php
/**
 * Custom login, signup, and admin dashboard pages (Sent One theme).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page slug for login.
 */
function one1_login_slug() {
	return 'login';
}

/**
 * Page slug for registration (signup).
 */
function one1_signup_slug() {
	return 'signup';
}

/**
 * Page slug for admin dashboard shell.
 */
function one1_dashboard_slug() {
	return 'dashboard';
}

/**
 * Page slug for forgot password.
 */
function one1_forgot_password_slug() {
	return 'forgot-password';
}

/**
 * Page slug for reset password (email link destination).
 */
function one1_reset_password_slug() {
	return 'reset-password';
}

/**
 * Whether the current user is a site administrator.
 */
function one1_is_admin_user() {
	return is_user_logged_in() && current_user_can( 'manage_options' );
}

/**
 * Login page URL.
 *
 * @param string $redirect Optional redirect after login.
 * @param string $ref      Optional invite ref code.
 */
function one1_login_url( $redirect = '', $ref = '' ) {
	$url = one1_page_url_by_slug( one1_login_slug() );
	if ( $redirect ) {
		$url = add_query_arg( 'redirect_to', rawurlencode( esc_url_raw( $redirect ) ), $url );
	}
	if ( $ref ) {
		$url = add_query_arg( 'ref', rawurlencode( $ref ), $url );
	}
	return $url;
}

/**
 * Signup page URL (invite-only registration on the join page).
 *
 * @param string $ref      Optional friend invite ref.
 * @param string $pu_token Optional admin PU invite token.
 */
function one1_signup_url( $ref = '', $pu_token = '' ) {
	$url = add_query_arg( 'register', '1', one1_join_page_url() );
	if ( $ref ) {
		$url = add_query_arg( 'ref', rawurlencode( $ref ), $url );
	}
	if ( $pu_token ) {
		$url = add_query_arg( 'pu_token', rawurlencode( $pu_token ), $url );
	}
	return $url;
}

/**
 * Dashboard page URL (admin only).
 */
function one1_dashboard_url() {
	return one1_page_url_by_slug( one1_dashboard_slug() );
}

/**
 * Forgot password page URL.
 */
function one1_forgot_password_url() {
	return one1_page_url_by_slug( one1_forgot_password_slug() );
}

/**
 * Reset password page URL.
 */
function one1_reset_password_url() {
	return one1_page_url_by_slug( one1_reset_password_slug() );
}

/**
 * Resolve permalink for a page slug.
 *
 * @param string $slug Page slug.
 */
function one1_page_url_by_slug( $slug ) {
	$page = get_page_by_path( $slug );
	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}
	return home_url( '/' . $slug . '/' );
}

/**
 * Current auth view: login|register|dashboard|false.
 */
function one1_auth_page_type() {
	if ( is_page( one1_login_slug() ) ) {
		return 'login';
	}
	if ( is_page( one1_dashboard_slug() ) ) {
		return 'dashboard';
	}
	if ( is_page( one1_forgot_password_slug() ) ) {
		return 'forgot';
	}
	if ( is_page( one1_reset_password_slug() ) ) {
		return 'reset';
	}
	return false;
}

/**
 * Whether the request is on a themed auth page.
 */
function one1_is_auth_page() {
	return (bool) one1_auth_page_type();
}

/**
 * Ensure login, signup, and dashboard pages exist.
 */
function one1_maybe_create_auth_pages() {
	static $running = false;
	if ( $running ) {
		return;
	}
	$running = true;
	$pages = array(
		one1_login_slug()     => array(
			'title'   => __( 'Log in', 'one' ),
			'content' => '',
		),
		one1_dashboard_slug()      => array(
			'title'   => __( 'Dashboard', 'one' ),
			'content' => '',
		),
		one1_forgot_password_slug() => array(
			'title'   => __( 'Forgot password', 'one' ),
			'content' => '',
		),
		one1_reset_password_slug()  => array(
			'title'   => __( 'Reset password', 'one' ),
			'content' => '',
		),
	);

	foreach ( $pages as $slug => $data ) {
		if ( get_page_by_path( $slug ) ) {
			continue;
		}
		wp_insert_post(
			array(
				'post_title'   => $data['title'],
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $data['content'],
			)
		);
	}

	$running = false;
}

/**
 * Trash legacy signup/register pages and point SIN registration to join.
 */
function one1_retire_signup_page() {
	if ( get_option( 'one1_signup_page_retired', false ) ) {
		return;
	}

	foreach ( array( one1_signup_slug(), 'register' ) as $slug ) {
		$page = get_page_by_path( $slug, OBJECT, 'any' );
		if ( $page instanceof WP_Post && 'trash' !== $page->post_status ) {
			wp_trash_post( (int) $page->ID );
		}
	}

	if ( function_exists( 'sin_get_settings' ) && function_exists( 'one1_join_page_id' ) ) {
		$join_id = one1_join_page_id();
		if ( $join_id > 0 ) {
			$settings                     = sin_get_settings();
			$settings['register_page_id'] = $join_id;
			update_option( 'sin_settings', $settings );
		}
	}

	update_option( 'one1_signup_page_retired', 1, false );
}
add_action( 'after_setup_theme', 'one1_retire_signup_page', 21 );

add_action( 'template_redirect', 'one1_redirect_legacy_signup_page', 2 );
/**
 * Redirect old signup/register URLs to invite-only join registration.
 */
function one1_redirect_legacy_signup_page() {
	if ( ! is_page( array( one1_signup_slug(), 'register' ) ) ) {
		return;
	}

	$args = array( 'register' => '1' );
	foreach ( array( 'ref', 'pu_token' ) as $key ) {
		if ( ! empty( $_GET[ $key ] ) ) {
			$args[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
		}
	}

	wp_safe_redirect( add_query_arg( $args, one1_join_page_url() ) );
	exit;
}

add_action( 'wp_enqueue_scripts', 'one1_enqueue_auth_assets', 25 );
/**
 * Styles for auth pages.
 */
function one1_enqueue_auth_assets() {
	if ( ! one1_is_auth_page() || is_admin() ) {
		return;
	}

	$ver  = '1.0.3';
	$base = get_stylesheet_directory_uri() . '/assets/homie';

	wp_enqueue_style(
		'one-homie-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'one-homie-home',
		$base . '/homie-homepage.css',
		array( 'one-homie-fonts' ),
		$ver
	);

	wp_enqueue_style(
		'one-homie-auth',
		$base . '/homie-auth.css',
		array( 'one-homie-home' ),
		$ver
	);

	wp_enqueue_style(
		'one-share-material-icons',
		'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
		array(),
		null
	);

	if ( function_exists( 'one1_enqueue_button_assets' ) ) {
		one1_enqueue_button_assets();
	}
	if ( function_exists( 'one1_enqueue_user_menu_assets' ) ) {
		one1_enqueue_user_menu_assets();
	}
}

add_filter( 'template_include', 'one1_auth_template_include', 98 );
/**
 * Load full-page auth template.
 *
 * @param string $template Template path.
 */
function one1_auth_template_include( $template ) {
	if ( one1_is_auth_page() && ! is_admin() ) {
		$custom = get_stylesheet_directory() . '/auth-page.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}

add_filter( 'body_class', 'one1_auth_body_class' );
/**
 * Body classes for auth pages.
 *
 * @param string[] $classes Classes.
 */
function one1_auth_body_class( $classes ) {
	if ( one1_is_auth_page() && ! is_admin() ) {
		$classes[] = 'homie-landing-body';
		$classes[] = 'homie-auth-body';
		$classes[] = 'sent-app-body';
		$type = one1_auth_page_type();
		if ( $type ) {
			$classes[] = 'homie-auth-body--' . $type;
		}
	}
	return $classes;
}

add_action( 'template_redirect', 'one1_auth_access_rules', 1 );
/**
 * Redirect rules for auth pages.
 */
function one1_auth_access_rules() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	$type = one1_auth_page_type();
	if ( ! $type ) {
		return;
	}

	if ( 'dashboard' === $type ) {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( one1_login_url( one1_dashboard_url() ) );
			exit;
		}
		if ( ! one1_is_admin_user() ) {
			wp_safe_redirect( one1_member_default_redirect_url() );
			exit;
		}
		return;
	}

	if ( in_array( $type, array( 'forgot', 'reset' ), true ) ) {
		if ( is_user_logged_in() ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}
		return;
	}

	if ( in_array( $type, array( 'login' ), true ) && is_user_logged_in() ) {
		// Allow Nextend Social Login verify/OAuth flows on auth pages.
		if ( ! empty( $_REQUEST['loginSocial'] ) || ! empty( $_REQUEST['test'] ) ) {
			return;
		}
		wp_safe_redirect( one1_member_default_redirect_url() );
		exit;
	}
}

/**
 * Default landing URL for logged-in members (stories feed).
 */
function one1_member_default_redirect_url() {
	if ( function_exists( 'one1_share_page_url' ) ) {
		return one1_share_page_url();
	}
	return home_url( '/' );
}

add_action( 'init', 'one1_handle_login_post' );
/**
 * Process custom login form.
 */
function one1_handle_login_post() {
	if ( ! isset( $_POST['one1_login_submit'] ) ) {
		return;
	}

	if ( ! isset( $_POST['one1_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['one1_login_nonce'] ) ), 'one1_login' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'one' ) );
	}

	$login    = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '';
	$password = isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '';
	$remember = ! empty( $_POST['rememberme'] );
	$ref      = isset( $_POST['invite_code'] ) ? sanitize_text_field( wp_unslash( $_POST['invite_code'] ) ) : '';

	$user = wp_signon(
		array(
			'user_login'    => $login,
			'user_password' => $password,
			'remember'      => $remember,
		),
		is_ssl()
	);

	$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
	$redirect = one1_sanitize_login_redirect( $redirect );

	if ( is_wp_error( $user ) ) {
		$args = array(
			'login_error' => rawurlencode( $user->get_error_message() ),
		);
		if ( $redirect ) {
			$args['redirect_to'] = rawurlencode( $redirect );
		}
		$login_url = one1_login_url( '', $ref );
		wp_safe_redirect( add_query_arg( $args, $login_url ) );
		exit;
	}

	if ( $ref !== '' && $user instanceof WP_User && function_exists( 'one1_apply_invite_ref_for_user' ) ) {
		one1_apply_invite_ref_for_user( (int) $user->ID, $ref );
	}

	if ( ! $redirect && $ref !== '' && function_exists( 'one1_share_page_url' ) ) {
		$redirect = one1_share_page_url();
	}

	wp_safe_redirect( $redirect ? $redirect : one1_member_default_redirect_url() );
	exit;
}

/**
 * Allowed redirect targets after login (share feed by default for members).
 *
 * @param string $url Requested redirect.
 */
function one1_sanitize_login_redirect( $url ) {
	if ( ! $url ) {
		return '';
	}

	$allowed = array(
		home_url( '/' ),
		one1_login_url(),
		one1_forgot_password_url(),
		one1_reset_password_url(),
	);

	if ( function_exists( 'one1_join_page_url' ) ) {
		$allowed[] = one1_join_page_url();
	}

	if ( function_exists( 'one1_share_page_url' ) ) {
		$allowed[] = one1_share_page_url();
	}

	if ( current_user_can( 'manage_options' ) ) {
		$allowed[] = one1_dashboard_url();
		$allowed[] = admin_url();
	}

	$url = wp_validate_redirect( $url, '' );
	if ( ! $url ) {
		return '';
	}

	foreach ( $allowed as $candidate ) {
		if ( 0 === strpos( $url, $candidate ) ) {
			return $url;
		}
	}

	$home = trailingslashit( home_url() );
	if ( 0 === strpos( $url, $home ) ) {
		return $url;
	}

	return '';
}

add_filter( 'login_redirect', 'one1_login_redirect', 10, 3 );
/**
 * Send members to the homepage after wp-login.php sign-in.
 *
 * @param string  $redirect_to           Redirect URL.
 * @param string  $requested_redirect_to Requested URL.
 * @param WP_User $user                  User.
 */
function one1_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
	if ( is_wp_error( $user ) ) {
		return $redirect_to;
	}

	if ( user_can( $user, 'manage_options' ) && $requested_redirect_to && one1_sanitize_login_redirect( $requested_redirect_to ) ) {
		return $requested_redirect_to;
	}

	if ( user_can( $user, 'manage_options' ) && ( ! $requested_redirect_to || admin_url() === $requested_redirect_to ) ) {
		return one1_dashboard_url();
	}

	if ( $requested_redirect_to && one1_sanitize_login_redirect( $requested_redirect_to ) ) {
		return $requested_redirect_to;
	}

	return one1_member_default_redirect_url();
}

add_filter( 'login_url', 'one1_filter_login_url', 10, 3 );
/**
 * Point login links to the themed login page.
 *
 * @param string $login_url Login URL.
 * @param string $redirect  Redirect after login.
 * @param bool   $force     Force reauth.
 */
function one1_filter_login_url( $login_url, $redirect, $force ) {
	$url = one1_login_url( $redirect );
	if ( $force ) {
		$url = add_query_arg( 'reauth', '1', $url );
	}
	return $url;
}

add_action( 'login_init', 'one1_redirect_wp_login_get' );
/**
 * Redirect wp-login.php GET requests to the custom login page (keep POST/actions).
 */
function one1_redirect_wp_login_get() {
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
		return;
	}

	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

	if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) {
		wp_safe_redirect( one1_forgot_password_url() );
		exit;
	}

	if ( in_array( $action, array( 'rp', 'resetpass' ), true ) ) {
		$args = array();
		if ( ! empty( $_GET['key'] ) ) {
			$args['key'] = sanitize_text_field( wp_unslash( $_GET['key'] ) );
		}
		if ( ! empty( $_GET['login'] ) ) {
			$args['login'] = sanitize_text_field( wp_unslash( $_GET['login'] ) );
		}
		wp_safe_redirect( add_query_arg( $args, one1_reset_password_url() ) );
		exit;
	}

	$allowed = array( 'logout', 'postpass', 'confirm_admin_email' );
	if ( in_array( $action, $allowed, true ) ) {
		return;
	}

	wp_safe_redirect( one1_login_url( isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '' ) );
	exit;
}

add_filter( 'lostpassword_url', 'one1_filter_lostpassword_url', 10, 2 );
/**
 * Themed forgot-password URL.
 *
 * @param string $lostpassword_url Lost password URL.
 * @param string $redirect         Redirect URL.
 */
function one1_filter_lostpassword_url( $lostpassword_url, $redirect ) {
	unset( $redirect );
	return one1_forgot_password_url();
}

add_filter( 'retrieve_password_message', 'one1_filter_retrieve_password_message', 10, 4 );
/**
 * Point password-reset emails to the themed reset page.
 *
 * @param string  $message    Email message.
 * @param string  $key        Reset key.
 * @param string  $user_login User login.
 * @param WP_User $user_data  User object.
 */
function one1_filter_retrieve_password_message( $message, $key, $user_login, $user_data ) {
	unset( $user_data );
	$reset_url = add_query_arg(
		array(
			'key'   => $key,
			'login' => rawurlencode( $user_login ),
		),
		one1_reset_password_url()
	);

	$old_url = network_site_url( 'wp-login.php?action=rp', 'login' );
	if ( false !== strpos( $message, $old_url ) ) {
		return str_replace( $old_url, $reset_url, $message );
	}

	$legacy = network_site_url( "wp-login.php?action=rp&key={$key}&login=" . rawurlencode( $user_login ), 'login' );
	if ( false !== strpos( $message, $legacy ) ) {
		return str_replace( $legacy, $reset_url, $message );
	}

	return $message . "\r\n\r\n" . $reset_url . "\r\n";
}

add_filter( 'retrieve_password_notification_email', 'one1_filter_retrieve_password_notification_email', 10, 4 );
/**
 * Themed reset URL in notification email (WordPress 5.4+).
 *
 * @param array   $defaults   Email arguments.
 * @param string  $key        Reset key.
 * @param string  $user_login User login.
 * @param WP_User $user_data  User object.
 */
function one1_filter_retrieve_password_notification_email( $defaults, $key, $user_login, $user_data ) {
	unset( $user_data );
	if ( ! is_array( $defaults ) || empty( $defaults['message'] ) ) {
		return $defaults;
	}

	$reset_url = add_query_arg(
		array(
			'key'   => $key,
			'login' => rawurlencode( $user_login ),
		),
		one1_reset_password_url()
	);

	$defaults['message'] = one1_filter_retrieve_password_message( $defaults['message'], $key, $user_login, null );
	if ( false === strpos( $defaults['message'], $reset_url ) ) {
		$defaults['message'] .= "\r\n\r\n" . $reset_url . "\r\n";
	}

	return $defaults;
}

add_action( 'init', 'one1_handle_forgot_password_post' );
/**
 * Process forgot-password form.
 */
function one1_handle_forgot_password_post() {
	if ( ! isset( $_POST['one1_forgot_submit'] ) ) {
		return;
	}

	if ( ! isset( $_POST['one1_forgot_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['one1_forgot_nonce'] ) ), 'one1_forgot_password' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'one' ) );
	}

	$login = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '';
	$result = retrieve_password( $login );

	if ( is_wp_error( $result ) ) {
		wp_safe_redirect(
			add_query_arg(
				array( 'forgot_error' => rawurlencode( $result->get_error_message() ) ),
				one1_forgot_password_url()
			)
		);
		exit;
	}

	wp_safe_redirect( add_query_arg( 'checkemail', 'confirm', one1_forgot_password_url() ) );
	exit;
}

add_action( 'init', 'one1_handle_reset_password_post' );
/**
 * Process reset-password form.
 */
function one1_handle_reset_password_post() {
	if ( ! isset( $_POST['one1_reset_submit'] ) ) {
		return;
	}

	if ( ! isset( $_POST['one1_reset_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['one1_reset_nonce'] ) ), 'one1_reset_password' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'one' ) );
	}

	$key   = isset( $_POST['rp_key'] ) ? sanitize_text_field( wp_unslash( $_POST['rp_key'] ) ) : '';
	$login = isset( $_POST['rp_login'] ) ? sanitize_text_field( wp_unslash( $_POST['rp_login'] ) ) : '';
	$pass1 = isset( $_POST['pass1'] ) ? (string) wp_unslash( $_POST['pass1'] ) : '';
	$pass2 = isset( $_POST['pass2'] ) ? (string) wp_unslash( $_POST['pass2'] ) : '';

	$reset_url = add_query_arg(
		array(
			'key'   => rawurlencode( $key ),
			'login' => rawurlencode( $login ),
		),
		one1_reset_password_url()
	);

	if ( $pass1 !== $pass2 ) {
		wp_safe_redirect( add_query_arg( 'reset_error', rawurlencode( __( 'Passwords do not match.', 'one' ) ), $reset_url ) );
		exit;
	}

	if ( strlen( $pass1 ) < 8 ) {
		wp_safe_redirect( add_query_arg( 'reset_error', rawurlencode( __( 'Password must be at least 8 characters.', 'one' ) ), $reset_url ) );
		exit;
	}

	$user = check_password_reset_key( $key, $login );
	if ( is_wp_error( $user ) ) {
		wp_safe_redirect( add_query_arg( 'reset_error', rawurlencode( $user->get_error_message() ), $reset_url ) );
		exit;
	}

	reset_password( $user, $pass1 );

	wp_safe_redirect( add_query_arg( 'reset', 'success', one1_login_url() ) );
	exit;
}

add_filter( 'register_url', 'one1_filter_register_url' );
/**
 * Signup URL for wp_register_url() etc.
 */
function one1_filter_register_url() {
	return one1_signup_url();
}

/**
 * Auth alert message from query string.
 *
 * @param string $key Query arg key.
 */
function one1_auth_query_message( $key ) {
	if ( ! isset( $_GET[ $key ] ) ) {
		return '';
	}
	return rawurldecode( sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) );
}
