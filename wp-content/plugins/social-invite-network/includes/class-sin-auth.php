<?php
/**
 * Login restrictions via authenticate filter.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Auth
 */
class SIN_Auth {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'authenticate', array( __CLASS__, 'authenticate' ), 30, 3 );
		add_filter( 'wp_send_new_user_notification_to_user', array( __CLASS__, 'block_default_user_email' ), 10, 2 );
	}

	/**
	 * Suppress default new user email to registrant (we notify admin instead).
	 *
	 * @param bool $send Whether to send.
	 */
	public static function block_default_user_email( $send ) {
		return false;
	}

	/**
	 * Block rejected users; allow pending and approved.
	 *
	 * @param WP_User|WP_Error|null $user     User or error.
	 * @param string                $username Username.
	 * @param string                $password Password.
	 * @return WP_User|WP_Error|null
	 */
	public static function authenticate( $user, $username, $password ) {
		if ( $user instanceof WP_User ) {
			if ( sin_is_staff_user( $user->ID ) ) {
				return $user;
			}
			$status = sin_get_account_status( $user->ID );
			if ( 'rejected' === $status ) {
				return new WP_Error(
					'sin_rejected',
					__( 'Your registration was not approved. Please contact the site administrator.', 'social-invite-network' )
				);
			}
		}
		return $user;
	}
}
