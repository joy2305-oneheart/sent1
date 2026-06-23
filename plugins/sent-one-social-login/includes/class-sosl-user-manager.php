<?php
/**
 * User lookup, creation, and linking.
 *
 * @package Sent_One_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SOSL_User_Manager
 */
class SOSL_User_Manager {

	const META_GOOGLE_ID = 'sosl_google_id';

	/**
	 * Resolve or create a user from Google profile data.
	 *
	 * @param array  $profile  Google profile.
	 * @param string $ref      Invite ref code.
	 * @param string $context  login|register.
	 * @param string $pu_token Admin PU invite token.
	 */
	public static function resolve_user( $profile, $ref = '', $context = 'login', $pu_token = '' ) {
		$user = self::find_by_google_id( $profile['google_id'] );
		if ( $user ) {
			self::maybe_update_profile( $user, $profile );
			return $user;
		}

		$user = get_user_by( 'email', $profile['email'] );
		if ( $user ) {
			update_user_meta( $user->ID, self::META_GOOGLE_ID, $profile['google_id'] );
			self::maybe_update_profile( $user, $profile );
			return $user;
		}

		if ( 'register' === $context ) {
			return self::create_user( $profile, $ref, $pu_token );
		}

		return new WP_Error( 'sosl_no_account', __( 'No account found for this email. Registration requires an invitation.', 'sent-one-social-login' ) );
	}

	/**
	 * Find user by Google ID.
	 *
	 * @param string $google_id Google subject ID.
	 */
	private static function find_by_google_id( $google_id ) {
		$query = new WP_User_Query(
			array(
				'number'     => 1,
				'fields'     => 'all',
				'meta_key'   => self::META_GOOGLE_ID,
				'meta_value' => $google_id,
			)
		);

		$users = $query->get_results();
		return ! empty( $users[0] ) ? $users[0] : null;
	}

	/**
	 * Create a new WordPress user from Google profile.
	 *
	 * @param array  $profile  Google profile.
	 * @param string $ref      Invite ref code.
	 * @param string $pu_token Admin PU invite token.
	 */
	private static function create_user( $profile, $ref, $pu_token = '' ) {
		if ( ! class_exists( 'SIN_Registration' ) ) {
			return new WP_Error( 'sosl_register', __( 'Registration is not available.', 'sent-one-social-login' ) );
		}

		$ctx = SIN_Registration::resolve_signup_context( $pu_token, $ref, $profile['email'] );

		if ( 'pu' === $ctx['type'] ) {
			$valid = SIN_Admin_PU_Invites::validate_for_registration( $pu_token, $profile['email'] );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
			$role = SIN_Roles::ROLE_PU;
		} elseif ( 'friend' === $ctx['type'] ) {
			if ( ! sin_is_pu( (int) $ctx['inviter_id'] ) ) {
				return new WP_Error( 'sosl_register', __( 'Invitation is not valid.', 'sent-one-social-login' ) );
			}
			$role = SIN_Roles::ROLE_FRIEND;
		} else {
			return new WP_Error( 'sosl_register', __( 'Registration requires an invitation.', 'sent-one-social-login' ) );
		}

		$username = self::unique_username( $profile['email'], $profile['name'] );
		$password = wp_generate_password( 24, true, true );

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $profile['email'],
				'user_pass'    => $password,
				'display_name' => $profile['name'] !== '' ? $profile['name'] : $username,
				'role'         => $role,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		update_user_meta( $user_id, self::META_GOOGLE_ID, $profile['google_id'] );
		update_user_meta( $user_id, 'sin_account_status', 'approved' );

		if ( SIN_Roles::ROLE_PU === $role ) {
			SIN_Roles::assign_pu_role( $user_id );
			SIN_Admin_PU_Invites::mark_accepted( $pu_token );
		} else {
			SIN_Registration::approve_invited_friend( $user_id, (int) $ctx['inviter_id'], $profile['email'] );
		}

		return get_user_by( 'id', $user_id );
	}

	/**
	 * Generate a unique username.
	 *
	 * @param string $email Email address.
	 * @param string $name  Display name.
	 */
	private static function unique_username( $email, $name ) {
		$base = sanitize_user( current( explode( '@', $email ) ), true );
		if ( $base === '' && $name !== '' ) {
			$base = sanitize_user( strtolower( str_replace( ' ', '', $name ) ), true );
		}
		if ( $base === '' ) {
			$base = 'member';
		}

		$username = $base;
		$counter  = 1;
		while ( username_exists( $username ) ) {
			$username = $base . $counter;
			++$counter;
		}

		return $username;
	}

	/**
	 * Update display name or avatar meta when available.
	 *
	 * @param WP_User $user    User object.
	 * @param array   $profile Google profile.
	 */
	private static function maybe_update_profile( $user, $profile ) {
		if ( $profile['name'] !== '' && $user->display_name === $user->user_login ) {
			wp_update_user(
				array(
					'ID'           => $user->ID,
					'display_name' => $profile['name'],
				)
			);
		}
	}
}
