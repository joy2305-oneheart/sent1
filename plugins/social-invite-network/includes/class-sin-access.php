<?php
/**
 * Template redirect: login/signup public; rest approved-only.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Access
 */
class SIN_Access {

	/**
	 * Bootstrap hooks.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ), 0 );
		add_action( 'wp', array( __CLASS__, 'maybe_pending_notice' ) );
	}

	/**
	 * Show persistent notice on login/signup for pending accounts.
	 */
	public static function maybe_pending_notice() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$on_auth_shell = ( function_exists( 'sin_is_public_login_page' ) && sin_is_public_login_page() )
			|| ( function_exists( 'sin_is_public_register_page' ) && sin_is_public_register_page() );
		if ( ! $on_auth_shell ) {
			return;
		}
		$uid = get_current_user_id();
		if ( sin_get_account_status( $uid ) !== 'pending' || sin_is_staff_user( $uid ) ) {
			return;
		}
		add_action(
			'wp_footer',
			static function () {
				echo '<div class="sin-notice sin-notice--pending" role="status">' . esc_html__( "Your account is pending admin approval. You'll be notified by email once approved.", 'social-invite-network' ) . '</div>';
			},
			5
		);
		add_action(
			'wp_head',
			static function () {
				sin_enqueue_frontend_styles();
			},
			5
		);
	}

	/**
	 * Core access gate.
	 */
	public static function template_redirect() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		if ( is_feed() ) {
			if ( ! is_user_logged_in() ) {
				$login_url = function_exists( 'one1_login_url' ) ? one1_login_url() : wp_login_url();
				wp_safe_redirect( $login_url );
				exit;
			}
			$fid = get_current_user_id();
			if ( ! sin_is_staff_user( $fid ) && ! sin_is_network_approved( $fid ) ) {
				status_header( 403 );
				wp_die( esc_html__( 'Access restricted.', 'social-invite-network' ), esc_html__( 'Access Restricted', 'social-invite-network' ), array( 'response' => 403 ) );
			}
		}

		// Custom login/register pages may live on site; allow login/register URLs.
		if ( self::is_login_or_register_context() ) {
			return;
		}

		$is_public_shell = sin_is_public_shell_request();

		if ( ! is_user_logged_in() ) {
			if ( ! $is_public_shell ) {
				$login_url = function_exists( 'one1_login_url' )
					? one1_login_url( self::current_url() )
					: wp_login_url( self::current_url() );
				wp_safe_redirect( $login_url );
				exit;
			}
			return;
		}

		$user_id = get_current_user_id();
		if ( sin_is_staff_user( $user_id ) ) {
			return;
		}

		$status = sin_get_account_status( $user_id );

		if ( 'rejected' === $status ) {
			if ( $is_public_shell ) {
				return;
			}
			self::render_restricted( $status );
			return;
		}

		if ( 'pending' === $status ) {
			self::try_auto_approve_invited_user( $user_id );
			if ( sin_is_network_approved( $user_id ) ) {
				return;
			}
			if ( $is_public_shell ) {
				return;
			}
			self::render_restricted( $status );
			return;
		}

		if ( sin_is_member( $user_id ) && sin_is_network_approved( $user_id ) ) {
			return;
		}

		self::try_auto_approve_invited_user( $user_id );
		if ( sin_is_network_approved( $user_id ) ) {
			return;
		}

		if ( ! $is_public_shell ) {
			self::render_restricted( 'pending' );
		}
	}

	/**
	 * Attempt to auto-approve a user with a valid PU invitation.
	 *
	 * @param int $user_id User ID.
	 */
	private static function try_auto_approve_invited_user( $user_id ) {
		if ( ! class_exists( 'SIN_Registration' ) ) {
			return;
		}
		$user = get_userdata( (int) $user_id );
		if ( ! $user ) {
			return;
		}
		SIN_Registration::maybe_auto_approve_invited_user( $user->user_login, $user );
	}

	/**
	 * Allow wp-login.php and themed login / signup pages.
	 */
	private static function is_login_or_register_context() {
		global $pagenow;
		if ( isset( $pagenow ) && 'wp-login.php' === $pagenow ) {
			return true;
		}
		if ( function_exists( 'sin_is_public_login_page' ) && sin_is_public_login_page() ) {
			return true;
		}
		if ( function_exists( 'sin_is_public_register_page' ) && sin_is_public_register_page() ) {
			return true;
		}
		if ( function_exists( 'sin_is_public_password_page' ) && sin_is_public_password_page() ) {
			return true;
		}
		return false;
	}

	/**
	 * Current URL for login redirect.
	 */
	private static function current_url() {
		if ( empty( $_SERVER['HTTP_HOST'] ) ) {
			return '';
		}
		$scheme = is_ssl() ? 'https://' : 'http://';
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		return esc_url_raw( $scheme . $_SERVER['HTTP_HOST'] . $uri );
	}

	/**
	 * Output restricted page (no theme nav).
	 *
	 * @param string $status pending|rejected.
	 */
	private static function render_restricted( $status ) {
		nocache_headers();
		status_header( 403 );
		sin_enqueue_frontend_styles();

		if ( 'rejected' === $status ) {
			$message = __( 'Your registration was not approved. Please contact the site administrator.', 'social-invite-network' );
		} else {
			$message = __( 'Your account is pending admin approval. You will be notified by email once your account is activated.', 'social-invite-network' );
		}

		$login_url  = function_exists( 'one1_login_url' ) ? one1_login_url() : wp_login_url();
		$logout_url = wp_logout_url( $login_url );

		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
		echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html__( 'Access Restricted', 'social-invite-network' ) . '</title>';
		wp_print_styles( 'sin-frontend' );
		echo '</head><body class="sin-restricted-body"><div class="sin-restricted-card"><h1>' . esc_html__( 'Access Restricted', 'social-invite-network' ) . '</h1><p>' . esc_html( $message ) . '</p>';
		echo '<p class="sin-restricted-actions"><a class="sin-button sin-button--secondary" href="' . esc_url( $logout_url ) . '">' . esc_html__( 'Log out', 'social-invite-network' ) . '</a></p>';
		echo '</div></body></html>';
		exit;
	}
}
