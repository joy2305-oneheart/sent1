<?php
/**
 * WordPress roles: Primary User (pu) and Friend (friend).
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Roles
 */
class SIN_Roles {

	const ROLE_PU     = 'pu';
	const ROLE_FRIEND = 'friend';

	/**
	 * Register custom roles on activation.
	 */
	public static function register_roles() {
		if ( get_role( self::ROLE_PU ) ) {
			return;
		}

		add_role(
			self::ROLE_PU,
			__( 'Primary User', 'social-invite-network' ),
			array(
				'read'                 => true,
				'sin_invite_friends'   => true,
				'sin_create_stories'   => true,
				'sin_manage_profile'   => true,
			)
		);

		add_role(
			self::ROLE_FRIEND,
			__( 'Friend', 'social-invite-network' ),
			array(
				'read'                => true,
				'sin_comment_stories' => true,
			)
		);
	}

	/**
	 * Migrate existing approved subscribers to pu role.
	 */
	public static function migrate_approved_users() {
		if ( get_option( 'sin_roles_migrated', false ) ) {
			return;
		}

		$users = get_users(
			array(
				'meta_key'   => 'sin_account_status',
				'meta_value' => 'approved',
				'fields'     => 'ID',
				'number'     => 5000,
			)
		);

		foreach ( $users as $user_id ) {
			$user_id = (int) $user_id;
			if ( sin_is_staff_user( $user_id ) ) {
				continue;
			}
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}
			$user->set_role( self::ROLE_PU );

			$code = get_user_meta( $user_id, 'sin_invite_code', true );
			if ( ! is_string( $code ) || $code === '' ) {
				$encrypted = SIN_Crypto::encrypt_username( $user->user_login );
				if ( $encrypted !== '' ) {
					update_user_meta( $user_id, 'sin_invite_code', $encrypted );
				}
			}
		}

		update_option( 'sin_roles_migrated', true );
	}

	/**
	 * Assign pu role and generate invite code.
	 *
	 * @param int $user_id User ID.
	 */
	public static function assign_pu_role( $user_id ) {
		$user = get_userdata( (int) $user_id );
		if ( ! $user ) {
			return;
		}
		$user->set_role( self::ROLE_PU );

		$code = get_user_meta( $user_id, 'sin_invite_code', true );
		if ( ! is_string( $code ) || $code === '' ) {
			$encrypted = SIN_Crypto::encrypt_username( $user->user_login );
			if ( $encrypted !== '' ) {
				update_user_meta( $user_id, 'sin_invite_code', $encrypted );
			}
		}
	}

	/**
	 * Assign friend role.
	 *
	 * @param int $user_id User ID.
	 */
	public static function assign_friend_role( $user_id ) {
		$user = get_userdata( (int) $user_id );
		if ( ! $user ) {
			return;
		}
		$user->set_role( self::ROLE_FRIEND );
	}
}
