<?php
/**
 * Registration form, admin approval/rejection, emails.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Registration
 */
class SIN_Registration {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'handle_register_post' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_admin_actions' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_pending_notice' ) );
		add_action( 'updated_user_meta', array( __CLASS__, 'maybe_sync_on_status_meta' ), 10, 4 );
		add_action( 'added_user_meta', array( __CLASS__, 'maybe_sync_on_status_meta' ), 10, 4 );
		add_action( 'wp_login', array( __CLASS__, 'maybe_auto_approve_invited_user' ), 10, 2 );
	}

	/**
	 * Resolve signup context from URL params (for page render and POST validation).
	 *
	 * @param string $pu_token  Admin PU token.
	 * @param string $ref_code  PU friend invite ref.
	 * @param string $email     Optional email for invite-table lookup.
	 * @return array{type: string, inviter_id?: int, pu_token?: string, inviter?: WP_User, pu_invite_row?: array}
	 */
	public static function resolve_signup_context( $pu_token = '', $ref_code = '', $email = '' ) {
		$pu_token = is_string( $pu_token ) ? sanitize_text_field( $pu_token ) : '';
		$ref_code = is_string( $ref_code ) ? sanitize_text_field( $ref_code ) : '';

		if ( $pu_token !== '' ) {
			$row = SIN_Admin_PU_Invites::get_valid_token_row( $pu_token );
			if ( $row ) {
				return array(
					'type'          => 'pu',
					'pu_token'      => $pu_token,
					'pu_invite_row' => $row,
				);
			}
			return array( 'type' => 'invalid_pu_token' );
		}

		$inviter_id = 0;
		$inviter    = null;

		if ( $ref_code !== '' ) {
			$login = SIN_Crypto::decrypt_username( $ref_code );
			if ( $login !== '' ) {
				$inviter = get_user_by( 'login', $login );
				if ( $inviter && sin_is_pu( (int) $inviter->ID ) ) {
					$inviter_id = (int) $inviter->ID;
				}
			}
		}

		if ( $inviter_id <= 0 && is_email( $email ) ) {
			$inviter_id = self::resolve_inviter_id_from_invites_table( $email );
			if ( $inviter_id > 0 ) {
				$inviter = get_userdata( $inviter_id );
			}
		}

		if ( $inviter_id > 0 && $inviter instanceof WP_User ) {
			return array(
				'type'        => 'friend',
				'inviter_id'  => $inviter_id,
				'inviter'     => $inviter,
				'ref_code'    => $ref_code,
			);
		}

		if ( $ref_code !== '' ) {
			return array( 'type' => 'invalid_ref' );
		}

		return array( 'type' => 'none' );
	}

	/**
	 * Whether signup form may be shown.
	 *
	 * @param string $pu_token PU token.
	 * @param string $ref_code Friend ref.
	 */
	public static function signup_is_allowed( $pu_token = '', $ref_code = '' ) {
		$ctx = self::resolve_signup_context( $pu_token, $ref_code );
		return in_array( $ctx['type'], array( 'pu', 'friend' ), true );
	}

	/**
	 * Dashboard notice for pending users count.
	 */
	public static function admin_pending_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$count = self::count_pending_users();
		if ( $count < 1 ) {
			return;
		}
		$url = admin_url( 'users.php?page=sin-pending-approvals' );
		echo '<div class="notice notice-warning"><p>';
		printf(
			/* translators: %1$d: count, %2$s: URL */
			esc_html__( 'Social Invite Network: %1$d legacy user(s) awaiting approval. %2$s', 'social-invite-network' ),
			(int) $count,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Review pending approvals', 'social-invite-network' ) . '</a>'
		);
		echo '</p></div>';
	}

	/**
	 * Count pending users.
	 */
	public static function count_pending_users() {
		$query = new WP_User_Query(
			array(
				'meta_key'   => 'sin_account_status',
				'meta_value' => 'pending',
				'fields'     => 'ID',
			)
		);
		return count( $query->get_results() );
	}

	/**
	 * Handle registration POST from front-end form.
	 */
	public static function handle_register_post() {
		if ( ! isset( $_POST['sin_register_submit'] ) ) {
			return;
		}
		if ( ! isset( $_POST['sin_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sin_register_nonce'] ) ), 'sin_register' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'social-invite-network' ) );
		}

		$name     = isset( $_POST['sin_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sin_name'] ) ) : '';
		$email    = isset( $_POST['sin_email'] ) ? sanitize_email( wp_unslash( $_POST['sin_email'] ) ) : '';
		$pass     = isset( $_POST['sin_password'] ) ? (string) wp_unslash( $_POST['sin_password'] ) : '';
		$pass2    = isset( $_POST['sin_password_confirm'] ) ? (string) wp_unslash( $_POST['sin_password_confirm'] ) : '';
		$ref_code = isset( $_POST['invite_code'] ) ? sanitize_text_field( wp_unslash( $_POST['invite_code'] ) ) : '';
		$pu_token = isset( $_POST['pu_token'] ) ? sanitize_text_field( wp_unslash( $_POST['pu_token'] ) ) : '';

		$errors = array();
		if ( $name === '' ) {
			$errors[] = __( 'Please enter your name.', 'social-invite-network' );
		}
		if ( ! is_email( $email ) ) {
			$errors[] = __( 'Please enter a valid email address.', 'social-invite-network' );
		}
		if ( strlen( $pass ) < 8 ) {
			$errors[] = __( 'Password must be at least 8 characters.', 'social-invite-network' );
		}
		if ( $pass !== $pass2 ) {
			$errors[] = __( 'Passwords do not match.', 'social-invite-network' );
		}
		if ( email_exists( $email ) ) {
			$errors[] = __( 'That email is already registered.', 'social-invite-network' );
		}

		$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : wp_get_referer();

		$ctx = self::resolve_signup_context( $pu_token, $ref_code, $email );

		if ( 'pu' === $ctx['type'] ) {
			$valid = SIN_Admin_PU_Invites::validate_for_registration( $pu_token, $email );
			if ( is_wp_error( $valid ) ) {
				$errors[] = $valid->get_error_message();
			}
		} elseif ( 'friend' === $ctx['type'] ) {
			$inviter_id = (int) $ctx['inviter_id'];
			if ( ! sin_is_pu( $inviter_id ) ) {
				$errors[] = __( 'Invitation is not valid.', 'social-invite-network' );
			}
		} elseif ( 'invalid_pu_token' === $ctx['type'] ) {
			$errors[] = __( 'This Primary User invitation is invalid or has expired.', 'social-invite-network' );
		} elseif ( 'invalid_ref' === $ctx['type'] ) {
			$errors[] = __( 'Invalid invitation link.', 'social-invite-network' );
		} else {
			$errors[] = __( 'Registration requires an invitation.', 'social-invite-network' );
		}

		if ( ! empty( $errors ) ) {
			$query = array( 'sin_reg_error' => rawurlencode( implode( ' ', $errors ) ) );
			wp_safe_redirect( add_query_arg( $query, $redirect ? $redirect : home_url( '/' ) ) );
			exit;
		}

		$username_base = sanitize_user( current( explode( '@', $email ) ), true );
		if ( $username_base === '' ) {
			$username_base = 'member';
		}
		$username = self::unique_username( $username_base );

		$role = 'pu' === $ctx['type'] ? SIN_Roles::ROLE_PU : SIN_Roles::ROLE_FRIEND;

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => $pass,
				'display_name' => $name,
				'role'         => $role,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			$query = array( 'sin_reg_error' => rawurlencode( $user_id->get_error_message() ) );
			wp_safe_redirect( add_query_arg( $query, $redirect ? $redirect : home_url( '/' ) ) );
			exit;
		}

		update_user_meta( $user_id, 'sin_account_status', 'approved' );

		if ( 'pu' === $ctx['type'] ) {
			SIN_Roles::assign_pu_role( $user_id );
			SIN_Admin_PU_Invites::mark_accepted( $pu_token );
		} else {
			self::approve_invited_friend( $user_id, (int) $ctx['inviter_id'], $email );
		}

		$login_target = function_exists( 'one1_login_url' )
			? one1_login_url( home_url( '/' ) )
			: wp_login_url( $redirect ? $redirect : home_url( '/' ) );
		wp_safe_redirect( add_query_arg( 'sin_reg_success', '1', $login_target ) );
		exit;
	}

	/**
	 * Approve and connect a user invited by a Primary User.
	 *
	 * @param int    $user_id    User ID.
	 * @param int    $inviter_id PU user ID.
	 * @param string $email      Optional email for invitation row sync.
	 * @return bool
	 */
	public static function approve_invited_friend( $user_id, $inviter_id, $email = '' ) {
		$user_id    = (int) $user_id;
		$inviter_id = (int) $inviter_id;

		if ( $user_id <= 0 || $inviter_id <= 0 || ! sin_is_pu( $inviter_id ) ) {
			return false;
		}
		if ( sin_is_staff_user( $user_id ) ) {
			return false;
		}

		update_user_meta( $user_id, 'sin_account_status', 'approved' );

		if ( ! sin_is_pu( $user_id ) ) {
			SIN_Roles::assign_friend_role( $user_id );
		}

		update_user_meta( $user_id, 'sin_invited_by', $inviter_id );
		SIN_Invitations::add_relationship( $inviter_id, $user_id );

		if ( $email === '' ) {
			$user = get_userdata( $user_id );
			$email = $user ? $user->user_email : '';
		}
		if ( is_email( $email ) ) {
			SIN_Invitations::mark_invitation_approved_for_registration( $email, $inviter_id );
		}

		sin_sync_connection_meta( $inviter_id );
		sin_sync_connection_meta( $user_id );

		return true;
	}

	/**
	 * Auto-approve invited users on login when a valid PU invitation exists.
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user       User object.
	 */
	public static function maybe_auto_approve_invited_user( $user_login, $user ) {
		unset( $user_login );
		if ( ! $user instanceof WP_User ) {
			return;
		}
		if ( sin_is_staff_user( $user->ID ) || sin_is_network_approved( $user->ID ) ) {
			return;
		}

		$inviter_id = (int) get_user_meta( $user->ID, 'sin_invited_by', true );
		if ( $inviter_id <= 0 ) {
			$inviter_id = self::resolve_inviter_id_from_invites_table( $user->user_email );
		}
		if ( $inviter_id <= 0 || ! sin_is_pu( $inviter_id ) ) {
			return;
		}

		self::approve_invited_friend( $user->ID, $inviter_id, $user->user_email );
	}

	/**
	 * Find inviter for this email from an open invitation (no ref link on registration).
	 *
	 * @param string $email Invitee email.
	 * @return int Inviter user ID or 0.
	 */
	public static function resolve_inviter_id_from_invites_table( $email ) {
		global $wpdb;
		$table = SIN_Database::invitations_table();
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return 0;
		}
		$id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT inviter_user_id FROM {$table} WHERE LOWER(invitee_email) = LOWER(%s) AND status IN ('pending_registration','pending_approval') ORDER BY id DESC LIMIT 1",
				$email
			)
		);
		if ( $id > 0 && sin_is_pu( $id ) ) {
			return $id;
		}
		return 0;
	}

	/**
	 * When a user becomes approved, ensure sin_invited_by + sin_relationships exist so the graph works.
	 *
	 * @param int $user_id User ID.
	 */
	public static function sync_relationship_for_user( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 || get_user_meta( $user_id, 'sin_account_status', true ) !== 'approved' ) {
			return;
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		// Legacy pending approval: assign pu role.
		if ( ! sin_is_member( $user_id ) && ! sin_is_staff_user( $user_id ) ) {
			SIN_Roles::assign_pu_role( $user_id );
		}

		$inviter_id = (int) get_user_meta( $user_id, 'sin_invited_by', true );
		if ( $inviter_id <= 0 ) {
			$inviter_id = self::resolve_inviter_id_from_invites_table( $user->user_email );
			if ( $inviter_id > 0 ) {
				update_user_meta( $user_id, 'sin_invited_by', $inviter_id );
			}
		}
		if ( $inviter_id > 0 ) {
			SIN_Invitations::add_relationship( $inviter_id, $user_id );
			sin_sync_connection_meta( $inviter_id );
		}
		sin_sync_connection_meta( $user_id );
	}

	/**
	 * Heal connections when status is set to approved (e.g. WP-CLI or manual meta edit).
	 *
	 * @param int    $meta_id   Meta row ID.
	 * @param int    $user_id   User ID.
	 * @param string $meta_key  Meta key.
	 * @param mixed  $meta_value New value.
	 */
	public static function maybe_sync_on_status_meta( $meta_id, $user_id, $meta_key, $meta_value ) {
		if ( 'sin_account_status' !== $meta_key || 'approved' !== (string) $meta_value ) {
			return;
		}
		self::sync_relationship_for_user( (int) $user_id );
	}

	/**
	 * Generate unique username from base.
	 *
	 * @param string $base Base slug.
	 */
	private static function unique_username( $base ) {
		$base = substr( $base, 0, 60 );
		$cand  = $base;
		$i     = 1;
		while ( username_exists( $cand ) ) {
			$cand = $base . $i;
			++$i;
		}
		return $cand;
	}

	/**
	 * Approve or reject from admin UI.
	 */
	public static function handle_admin_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_GET['page'] ) || 'sin-pending-approvals' !== $_GET['page'] ) {
			return;
		}
		if ( ! isset( $_GET['sin_action'], $_GET['user_id'], $_GET['_wpnonce'] ) ) {
			return;
		}
		$action = sanitize_text_field( wp_unslash( $_GET['sin_action'] ) );
		$uid      = (int) $_GET['user_id'];
		if ( $uid <= 0 || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sin_approve_' . $uid ) ) {
			return;
		}
		if ( 'approve' === $action ) {
			self::approve_user( $uid );
		} elseif ( 'reject' === $action ) {
			self::reject_user( $uid );
		}
		wp_safe_redirect( admin_url( 'users.php?page=sin-pending-approvals&updated=1' ) );
		exit;
	}

	/**
	 * Approve legacy pending user as Primary User.
	 *
	 * @param int $user_id User ID.
	 */
	public static function approve_user( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		update_user_meta( $user_id, 'sin_account_status', 'approved' );
		SIN_Roles::assign_pu_role( $user_id );
		self::sync_relationship_for_user( $user_id );
		$inviter_id = (int) get_user_meta( $user_id, 'sin_invited_by', true );
		SIN_Invitations::mark_invitation_approved_for_registration( $user->user_email, $inviter_id );

		$subj = sprintf(
			/* translators: %s: site name */
			__( 'Your account is active on %s', 'social-invite-network' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);
		$body  = __( 'Good news — your account has been approved. You can sign in and use the network.', 'social-invite-network' ) . "\n\n";
		$body .= wp_login_url() . "\n";
		wp_mail( $user->user_email, $subj, $body );
	}

	/**
	 * Reject user.
	 *
	 * @param int $user_id User ID.
	 */
	public static function reject_user( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		update_user_meta( $user_id, 'sin_account_status', 'rejected' );
		$inviter_id = (int) get_user_meta( $user_id, 'sin_invited_by', true );
		SIN_Invitations::mark_invitation_rejected_for_registration( $user->user_email, $inviter_id );

		$subj = sprintf(
			/* translators: %s: site name */
			__( 'Your registration on %s', 'social-invite-network' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);
		$body = __( 'Your registration was not approved. Please contact the site administrator if you have questions.', 'social-invite-network' );
		wp_mail( $user->user_email, $subj, $body );
	}
}
