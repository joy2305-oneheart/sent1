<?php
/**
 * Invitations: DB operations, emails, acceptance.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Invitations
 */
class SIN_Invitations {

	const META_INBOX = 'sin_invitation_inbox';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'admin_post_sin_accept_invite', array( __CLASS__, 'handle_accept_invite' ) );
		add_action( 'admin_post_nopriv_sin_accept_invite', array( __CLASS__, 'handle_accept_invite' ) );
	}

	/**
	 * Pending invitation rows for an email (any inviter).
	 *
	 * @param string $email Email.
	 */
	public static function has_open_invitation_for_email( $email ) {
		global $wpdb;
		$table = SIN_Database::invitations_table();
		$email = strtolower( sanitize_email( $email ) );
		if ( ! is_email( $email ) ) {
			return false;
		}
		$sql = $wpdb->prepare(
			"SELECT id FROM {$table} WHERE LOWER(invitee_email) = %s AND status IN ('pending_registration','pending_approval') LIMIT 1",
			$email
		);
		return (bool) $wpdb->get_var( $sql );
	}

	/**
	 * Rate limit check.
	 *
	 * @param int $user_id User ID.
	 */
	public static function is_rate_limited( $user_id ) {
		$settings = sin_get_settings();
		$max       = isset( $settings['max_invites_per_day'] ) ? max( 1, (int) $settings['max_invites_per_day'] ) : 10;
		$key       = 'sin_invite_rate_' . (int) $user_id . '_' . gmdate( 'Ymd' );
		$count     = (int) get_transient( $key );
		return $count >= $max;
	}

	/**
	 * Increment rate limit counter.
	 *
	 * @param int $user_id User ID.
	 */
	public static function bump_rate_limit( $user_id ) {
		$key   = 'sin_invite_rate_' . (int) $user_id . '_' . gmdate( 'Ymd' );
		$count = (int) get_transient( $key );
		++$count;
		set_transient( $key, $count, DAY_IN_SECONDS );
	}

	/**
	 * Front-end invite page URL (theme) or home.
	 */
	public static function invite_page_url() {
		if ( function_exists( 'one1_invite_page_url' ) ) {
			return one1_invite_page_url();
		}
		return home_url( '/' );
	}

	/**
	 * Store a one-time notice and redirect to the invite page (legacy shortcode POST).
	 *
	 * @param int             $user_id User ID.
	 * @param string|WP_Error $result  Success message or WP_Error.
	 */
	public static function set_flash_and_redirect( $user_id, $result ) {
		$user_id = (int) $user_id;
		$msg     = is_wp_error( $result ) ? $result->get_error_message() : (string) $result;
		$type    = is_wp_error( $result ) ? 'error' : 'success';
		set_transient(
			'sin_invite_flash_' . $user_id,
			array(
				'message' => $msg,
				'type'    => $type,
			),
			60
		);
		wp_safe_redirect( self::invite_page_url() );
		exit;
	}

	/**
	 * Read and clear a one-time invite-page notice.
	 *
	 * @param int $user_id User ID.
	 * @return array|null Message array with message + type, or null.
	 */
	public static function get_and_clear_flash( $user_id ) {
		$key   = 'sin_invite_flash_' . (int) $user_id;
		$flash = get_transient( $key );
		if ( is_array( $flash ) && isset( $flash['message'] ) ) {
			delete_transient( $key );
			return $flash;
		}
		// Legacy transient from removed Network Hub.
		$legacy = get_transient( 'sin_hub_flash_' . (int) $user_id );
		if ( is_array( $legacy ) && isset( $legacy['message'] ) ) {
			delete_transient( 'sin_hub_flash_' . (int) $user_id );
			return $legacy;
		}
		return null;
	}

	/**
	 * Whether two users already share a connection (either direction).
	 *
	 * @param int $user_a User ID.
	 * @param int $user_b User ID.
	 */
	public static function users_are_connected( $user_a, $user_b ) {
		$user_a = (int) $user_a;
		$user_b = (int) $user_b;
		if ( $user_a <= 0 || $user_b <= 0 || $user_a === $user_b ) {
			return false;
		}

		global $wpdb;
		$table = SIN_Database::relationships_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$table} WHERE (inviter_id = %d AND invitee_id = %d) OR (inviter_id = %d AND invitee_id = %d) LIMIT 1",
				$user_a,
				$user_b,
				$user_b,
				$user_a
			)
		);

		return (bool) $found;
	}

	/**
	 * User meta key for manually disconnected user IDs.
	 */
	const META_DISCONNECTED = 'sin_disconnected_users';

	/**
	 * Disconnect two users in both directions and record the block.
	 *
	 * @param int $user_a User ID.
	 * @param int $user_b User ID.
	 */
	public static function disconnect_users( $user_a, $user_b ) {
		global $wpdb;

		$user_a = (int) $user_a;
		$user_b = (int) $user_b;
		if ( $user_a <= 0 || $user_b <= 0 || $user_a === $user_b ) {
			return;
		}

		$table = SIN_Database::relationships_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$table,
			array(
				'inviter_id' => $user_a,
				'invitee_id' => $user_b,
			),
			array( '%d', '%d' )
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$table,
			array(
				'inviter_id' => $user_b,
				'invitee_id' => $user_a,
			),
			array( '%d', '%d' )
		);

		self::add_disconnected_user( $user_a, $user_b );
		self::add_disconnected_user( $user_b, $user_a );

		if ( function_exists( 'sin_sync_connection_meta' ) ) {
			sin_sync_connection_meta( $user_a );
			sin_sync_connection_meta( $user_b );
		}
	}

	/**
	 * Record that user_a has disconnected from user_b.
	 *
	 * @param int $user_a User ID.
	 * @param int $user_b Disconnected user ID.
	 */
	public static function add_disconnected_user( $user_a, $user_b ) {
		$user_a = (int) $user_a;
		$user_b = (int) $user_b;
		if ( $user_a <= 0 || $user_b <= 0 ) {
			return;
		}
		$list = get_user_meta( $user_a, self::META_DISCONNECTED, true );
		if ( ! is_array( $list ) ) {
			$list = array();
		}
		$list[] = $user_b;
		$list   = array_values( array_unique( array_filter( array_map( 'intval', $list ) ) ) );
		update_user_meta( $user_a, self::META_DISCONNECTED, $list );
	}

	/**
	 * Whether user_a has disconnected from user_b.
	 *
	 * @param int $user_a User ID.
	 * @param int $user_b Other user ID.
	 */
	public static function is_disconnected_from( $user_a, $user_b ) {
		$list = get_user_meta( (int) $user_a, self::META_DISCONNECTED, true );
		if ( ! is_array( $list ) ) {
			return false;
		}
		return in_array( (int) $user_b, array_map( 'intval', $list ), true );
	}

	/**
	 * Query invitations for admin list (filtered, paginated).
	 *
	 * @param array<string, mixed> $args Filter args: status, inviter_id, email, paged, per_page.
	 * @return array{rows: array<int, array<string, mixed>>, total: int}
	 */
	public static function query_for_admin( $args = array() ) {
		global $wpdb;
		$table = SIN_Database::invitations_table();

		$status     = isset( $args['status'] ) ? sanitize_text_field( (string) $args['status'] ) : '';
		$inviter_id = isset( $args['inviter_id'] ) ? (int) $args['inviter_id'] : 0;
		$email      = isset( $args['email'] ) ? sanitize_text_field( (string) $args['email'] ) : '';
		$paged      = max( 1, (int) ( $args['paged'] ?? 1 ) );
		$per_page   = max( 1, min( 100, (int) ( $args['per_page'] ?? 25 ) ) );
		$offset     = ( $paged - 1 ) * $per_page;

		$where  = array( '1=1' );
		$params = array();

		if ( 'pending' === $status ) {
			$where[] = "status IN ('pending_registration','pending_approval')";
		} elseif ( 'accepted' === $status ) {
			$where[] = 'status = %s';
			$params[] = 'approved';
		} elseif ( $status !== '' ) {
			$where[] = 'status = %s';
			$params[] = $status;
		}

		if ( $inviter_id > 0 ) {
			$where[]  = 'inviter_user_id = %d';
			$params[] = $inviter_id;
		}

		if ( $email !== '' ) {
			$where[]  = 'invitee_email LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $email ) . '%';
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $count_sql );
		}

		$list_params   = $params;
		$list_params[] = $per_page;
		$list_params[] = $offset;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$list_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_params ), ARRAY_A );

		return array(
			'rows'  => is_array( $rows ) ? $rows : array(),
			'total' => $total,
		);
	}

	public static function get_sent_by_user( $inviter_id ) {
		$inviter_id = (int) $inviter_id;
		if ( $inviter_id <= 0 ) {
			return array();
		}

		global $wpdb;
		$table = SIN_Database::invitations_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE inviter_user_id = %d ORDER BY created_at DESC",
				$inviter_id
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Friendly status label for UI.
	 *
	 * @param string $status DB status.
	 */
	public static function get_display_status( $status ) {
		if ( 'approved' === $status ) {
			return __( 'Accepted', 'social-invite-network' );
		}
		if ( in_array( $status, array( 'pending_registration', 'pending_approval' ), true ) ) {
			return __( 'Pending', 'social-invite-network' );
		}
		if ( 'cancelled' === $status ) {
			return __( 'Removed', 'social-invite-network' );
		}
		return ucfirst( str_replace( '_', ' ', (string) $status ) );
	}

	/**
	 * Pending inbox rows for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_inbox( $user_id ) {
		$inbox = get_user_meta( (int) $user_id, self::META_INBOX, true );
		return is_array( $inbox ) ? $inbox : array();
	}

	/**
	 * Send an invitation as an approved network member.
	 *
	 * @param int    $inviter_id Inviter user ID.
	 * @param string $email      Invitee email address.
	 * @return string|WP_Error Success message string, or error.
	 */
	public static function submit_invite_for_user( $inviter_id, $email ) {
		$inviter_id = (int) $inviter_id;
		if ( $inviter_id <= 0 ) {
			return new WP_Error( 'sin_invite', __( 'Invalid user.', 'social-invite-network' ) );
		}
		if ( ! sin_is_pu( $inviter_id ) && ! sin_is_staff_user( $inviter_id ) ) {
			return new WP_Error( 'sin_invite', __( 'You are not allowed to send invitations.', 'social-invite-network' ) );
		}
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'sin_invite', __( 'Please enter a valid email address.', 'social-invite-network' ) );
		}
		$inviter = get_userdata( $inviter_id );
		if ( $inviter && strtolower( $email ) === strtolower( $inviter->user_email ) ) {
			return new WP_Error( 'sin_invite', __( 'You cannot invite yourself.', 'social-invite-network' ) );
		}
		if ( self::has_open_invitation_for_email( $email ) ) {
			return new WP_Error( 'sin_invite', __( 'An invitation for this email is already pending.', 'social-invite-network' ) );
		}
		if ( self::is_rate_limited( $inviter_id ) ) {
			return new WP_Error( 'sin_invite', __( 'You have reached the daily invitation limit.', 'social-invite-network' ) );
		}

		$existing = get_user_by( 'email', $email );
		if ( $existing ) {
			$invitee_id = (int) $existing->ID;
			if ( self::users_are_connected( $inviter_id, $invitee_id ) ) {
				return new WP_Error( 'sin_invite', __( 'That person is already in your circle.', 'social-invite-network' ) );
			}

			if ( class_exists( 'SIN_Registration' ) ) {
				SIN_Registration::approve_invited_friend( $invitee_id, $inviter_id, $email );
			}

			$id = self::insert_row( $inviter_id, $email, 'approved', '' );
			if ( ! $id ) {
				return new WP_Error( 'sin_invite', __( 'Could not create invitation.', 'social-invite-network' ) );
			}

			$login_link = function_exists( 'one1_login_url' ) ? one1_login_url() : wp_login_url();
			self::send_circle_invite_email( $email, $inviter_id, $login_link );
			self::bump_rate_limit( $inviter_id );
			return __( 'They were added to your circle and notified by email.', 'social-invite-network' );
		}

		$code = get_user_meta( $inviter_id, 'sin_invite_code', true );
		if ( ! is_string( $code ) || $code === '' ) {
			$u = get_userdata( $inviter_id );
			if ( $u ) {
				$code = SIN_Crypto::encrypt_username( $u->user_login );
				if ( $code !== '' ) {
					update_user_meta( $inviter_id, 'sin_invite_code', $code );
				}
			}
		}
		$id = self::insert_row( $inviter_id, $email, 'pending_registration', is_string( $code ) ? $code : '' );
		if ( ! $id ) {
			return new WP_Error( 'sin_invite', __( 'Could not create invitation.', 'social-invite-network' ) );
		}
		$link = function_exists( 'one1_build_timed_invite_link' )
			? one1_build_timed_invite_link( $inviter_id, $email )
			: self::build_invite_link( $inviter_id );
		$sent = self::send_invite_email( $email, $inviter_id, $link );
		if ( ! $sent ) {
			return new WP_Error( 'sin_invite', __( 'Could not send the invitation email. Please check your mail settings and try again.', 'social-invite-network' ) );
		}
		self::bump_rate_limit( $inviter_id );
		return __( 'Invitation email sent.', 'social-invite-network' );
	}

	/**
	 * Queue pending approval invite for existing WP user.
	 *
	 * @param int    $invitee_id Invitee user ID.
	 * @param int    $invitation_id Row ID.
	 * @param int    $inviter_id Inviter ID.
	 */
	public static function push_inbox( $invitee_id, $invitation_id, $inviter_id ) {
		$inbox = get_user_meta( (int) $invitee_id, self::META_INBOX, true );
		if ( ! is_array( $inbox ) ) {
			$inbox = array();
		}
		foreach ( $inbox as $row ) {
			if ( isset( $row['invitation_id'] ) && (int) $row['invitation_id'] === (int) $invitation_id ) {
				return;
			}
		}
		$inbox[] = array(
			'invitation_id' => (int) $invitation_id,
			'inviter_id'    => (int) $inviter_id,
		);
		update_user_meta( (int) $invitee_id, self::META_INBOX, $inbox );
	}

	/**
	 * Remove inbox entry after accept/dismiss.
	 *
	 * @param int $invitee_id User ID.
	 * @param int $invitation_id Invitation row ID.
	 */
	public static function remove_inbox( $invitee_id, $invitation_id ) {
		$inbox = get_user_meta( (int) $invitee_id, self::META_INBOX, true );
		if ( ! is_array( $inbox ) ) {
			return;
		}
		$new = array();
		foreach ( $inbox as $row ) {
			if ( isset( $row['invitation_id'] ) && (int) $row['invitation_id'] === (int) $invitation_id ) {
				continue;
			}
			$new[] = $row;
		}
		update_user_meta( (int) $invitee_id, self::META_INBOX, $new );
	}

	/**
	 * Insert invitation row.
	 *
	 * @param int    $inviter_id Inviter.
	 * @param string $email Email.
	 * @param string $status Status.
	 * @param string $code Encrypted invite code or empty.
	 * @return int|false Insert ID.
	 */
	public static function insert_row( $inviter_id, $email, $status, $code ) {
		global $wpdb;
		$table = SIN_Database::invitations_table();
		$now   = current_time( 'mysql', true );
		$res   = $wpdb->insert(
			$table,
			array(
				'inviter_user_id' => (int) $inviter_id,
				'invitee_email'   => sanitize_email( $email ),
				'invite_code'     => $code,
				'status'          => $status,
				'created_at'      => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);
		if ( ! $res ) {
			return false;
		}
		return (int) $wpdb->insert_id;
	}

	/**
	 * Build invite link for inviter.
	 *
	 * @param int $inviter_id Inviter user ID.
	 */
	public static function build_invite_link( $inviter_id ) {
		if ( function_exists( 'one1_build_timed_invite_link' ) ) {
			return one1_build_timed_invite_link( (int) $inviter_id );
		}

		$inviter = get_userdata( (int) $inviter_id );
		if ( ! $inviter ) {
			return '';
		}
		$code = get_user_meta( (int) $inviter_id, 'sin_invite_code', true );
		if ( ! is_string( $code ) || $code === '' ) {
			$code = SIN_Crypto::encrypt_username( $inviter->user_login );
			if ( $code !== '' ) {
				update_user_meta( (int) $inviter_id, 'sin_invite_code', $code );
			}
		}
		$register = self::get_register_url();
		return add_query_arg( 'ref', $code, $register );
	}

	/**
	 * Registration page URL.
	 */
	public static function get_register_url() {
		if ( function_exists( 'one1_join_page_url' ) ) {
			return add_query_arg( 'register', '1', one1_join_page_url() );
		}

		$settings = sin_get_settings();
		$page_id  = isset( $settings['register_page_id'] ) ? (int) $settings['register_page_id'] : 0;
		if ( $page_id > 0 ) {
			return get_permalink( $page_id );
		}

		return home_url( '/join/' );
	}

	/**
	 * Send invite email to new address.
	 *
	 * @param string $to Email.
	 * @param int    $inviter_id Inviter ID.
	 * @param string $invite_link Link.
	 */
	public static function send_invite_email( $to, $inviter_id, $invite_link ) {
		$settings = sin_get_settings();
		$inviter  = get_userdata( (int) $inviter_id );
		$subj     = self::replace_placeholders( (string) $settings['invite_email_subject'], $invite_link, $inviter ? $inviter->display_name : '', '' );
		$body     = self::replace_placeholders( (string) $settings['invite_email_body'], $invite_link, $inviter ? $inviter->display_name : '', '' );
		return wp_mail( $to, $subj, $body );
	}

	/**
	 * Build accept-invite URL (login redirect when logged out).
	 *
	 * @param int $invitation_id Invitation row ID.
	 */
	public static function build_accept_invite_link( $invitation_id ) {
		$invitation_id = (int) $invitation_id;
		$target        = add_query_arg( 'sin_invite', (string) $invitation_id, self::invite_page_url() );
		if ( is_user_logged_in() ) {
			return $target;
		}
		return wp_login_url( $target );
	}

	/**
	 * Send "Join this Circle" email to an existing account.
	 *
	 * @param string $to Email.
	 * @param int    $inviter_id Inviter ID.
	 * @param string $accept_link Accept URL.
	 */
	public static function send_circle_invite_email( $to, $inviter_id, $accept_link ) {
		$settings = sin_get_settings();
		$inviter  = get_userdata( (int) $inviter_id );
		$name     = $inviter ? $inviter->display_name : '';
		$subj     = self::replace_circle_placeholders( (string) ( $settings['circle_invite_email_subject'] ?? '' ), $accept_link, $name );
		$body     = self::replace_circle_placeholders( (string) ( $settings['circle_invite_email_body'] ?? '' ), $accept_link, $name );
		return wp_mail( $to, $subj, $body );
	}

	/**
	 * Replace template tags.
	 *
	 * @param string $text Template.
	 * @param string $invite_link Link.
	 * @param string $inviter_name Name.
	 * @param string $accept_link Accept link (optional).
	 */
	private static function replace_placeholders( $text, $invite_link, $inviter_name, $accept_link = '' ) {
		$repl = array(
			'{invite_link}'  => $invite_link,
			'{accept_link}'  => $accept_link,
			'{site_name}'    => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'{inviter_name}' => $inviter_name,
		);
		return strtr( $text, $repl );
	}

	/**
	 * Replace circle-invite template tags.
	 *
	 * @param string $text Template.
	 * @param string $accept_link Accept URL.
	 * @param string $inviter_name Inviter display name.
	 */
	private static function replace_circle_placeholders( $text, $accept_link, $inviter_name ) {
		$repl = array(
			'{accept_link}'  => $accept_link,
			'{site_name}'    => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'{inviter_name}' => $inviter_name,
		);
		return strtr( $text, $repl );
	}

	/**
	 * Approve invitation row for email (registration flow).
	 *
	 * @param string $email User email.
	 * @param int    $inviter_id Inviter ID (0 if none).
	 */
	public static function mark_invitation_approved_for_registration( $email, $inviter_id ) {
		global $wpdb;
		$table = SIN_Database::invitations_table();
		$email = sanitize_email( $email );
		$now   = current_time( 'mysql', true );
		if ( $inviter_id > 0 ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET status = %s, accepted_at = %s WHERE LOWER(invitee_email) = LOWER(%s) AND inviter_user_id = %d AND status IN ('pending_registration','pending_approval')",
					'approved',
					$now,
					$email,
					$inviter_id
				)
			);
		} else {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET status = %s, accepted_at = %s WHERE LOWER(invitee_email) = LOWER(%s) AND status IN ('pending_registration','pending_approval')",
					'approved',
					$now,
					$email
				)
			);
		}
	}

	/**
	 * Mark invitation rejected.
	 *
	 * @param string $email Email.
	 * @param int    $inviter_id Inviter.
	 */
	public static function mark_invitation_rejected_for_registration( $email, $inviter_id ) {
		global $wpdb;
		$table = SIN_Database::invitations_table();
		$email = sanitize_email( $email );
		if ( $inviter_id > 0 ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET status = %s WHERE LOWER(invitee_email) = LOWER(%s) AND inviter_user_id = %d AND status IN ('pending_registration','pending_approval')",
					'rejected',
					$email,
					$inviter_id
				)
			);
		} else {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET status = %s WHERE LOWER(invitee_email) = LOWER(%s) AND status IN ('pending_registration','pending_approval')",
					'rejected',
					$email
				)
			);
		}
	}

	/**
	 * Insert relationship row (directional).
	 *
	 * @param int $inviter_id Inviter.
	 * @param int $invitee_id Invitee WP user.
	 */
	public static function add_relationship( $inviter_id, $invitee_id ) {
		global $wpdb;
		$table = SIN_Database::relationships_table();
		$wpdb->replace(
			$table,
			array(
				'inviter_id' => (int) $inviter_id,
				'invitee_id' => (int) $invitee_id,
			),
			array( '%d', '%d' )
		);
		if ( function_exists( 'sin_sync_connection_meta' ) ) {
			sin_sync_connection_meta( (int) $inviter_id );
			sin_sync_connection_meta( (int) $invitee_id );
		}
	}

	/**
	 * Front-end accept invite handler.
	 */
	public static function handle_accept_invite() {
		if ( ! isset( $_POST['sin_accept_invite_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sin_accept_invite_nonce'] ) ), 'sin_accept_invite' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'social-invite-network' ) );
		}
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url() );
			exit;
		}
		$uid = get_current_user_id();
		$invitation_id = isset( $_POST['invitation_id'] ) ? (int) $_POST['invitation_id'] : 0;
		if ( $invitation_id <= 0 ) {
			wp_die( esc_html__( 'Invalid invitation.', 'social-invite-network' ) );
		}
		global $wpdb;
		$table = SIN_Database::invitations_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $invitation_id ),
			ARRAY_A
		);
		if ( ! $row ) {
			wp_die( esc_html__( 'Invitation not found.', 'social-invite-network' ) );
		}
		$user = wp_get_current_user();
		if ( strtolower( (string) $user->user_email ) !== strtolower( (string) $row['invitee_email'] ) ) {
			wp_die( esc_html__( 'This invitation is not for your account.', 'social-invite-network' ) );
		}
		if ( ! in_array( $row['status'], array( 'pending_approval', 'approved' ), true ) ) {
			wp_die( esc_html__( 'This invitation is no longer pending.', 'social-invite-network' ) );
		}
		$inviter_id = (int) $row['inviter_user_id'];
		if ( class_exists( 'SIN_Registration' ) ) {
			SIN_Registration::approve_invited_friend( $uid, $inviter_id, $user->user_email );
		} else {
			self::add_relationship( $inviter_id, $uid );
		}
		$wpdb->update(
			$table,
			array(
				'status'      => 'approved',
				'accepted_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $invitation_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		self::remove_inbox( $uid, $invitation_id );
		$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/' );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Resend pending_registration email.
	 *
	 * @param int $invitation_id Row ID.
	 */
	public static function resend_email( $invitation_id ) {
		global $wpdb;
		$table = SIN_Database::invitations_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $invitation_id ),
			ARRAY_A
		);
		if ( ! $row ) {
			return new WP_Error( 'sin_invite', __( 'Cannot resend this invitation.', 'social-invite-network' ) );
		}
		if ( 'pending_registration' === $row['status'] ) {
			$link = function_exists( 'one1_build_timed_invite_link' )
				? one1_build_timed_invite_link( (int) $row['inviter_user_id'], (string) $row['invitee_email'] )
				: self::build_invite_link( (int) $row['inviter_user_id'] );
			$sent = self::send_invite_email( $row['invitee_email'], (int) $row['inviter_user_id'], $link );
			if ( ! $sent ) {
				return new WP_Error( 'sin_invite', __( 'Could not send the invitation email. Please check your mail settings and try again.', 'social-invite-network' ) );
			}
			return true;
		}
		if ( 'pending_approval' === $row['status'] ) {
			$accept_link = self::build_accept_invite_link( (int) $row['id'] );
			self::send_circle_invite_email( $row['invitee_email'], (int) $row['inviter_user_id'], $accept_link );
			return true;
		}
		return new WP_Error( 'sin_invite', __( 'Cannot resend this invitation.', 'social-invite-network' ) );
	}

	/**
	 * Cancel a pending invitation owned by the inviter.
	 *
	 * @param int $invitation_id Invitation row ID.
	 * @param int $inviter_id    Inviter user ID.
	 * @return true|WP_Error
	 */
	public static function cancel_invitation( $invitation_id, $inviter_id ) {
		global $wpdb;
		$table = SIN_Database::invitations_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND inviter_user_id = %d LIMIT 1",
				(int) $invitation_id,
				(int) $inviter_id
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return new WP_Error( 'sin_invite', __( 'Invitation not found.', 'social-invite-network' ) );
		}

		if ( ! in_array( $row['status'], array( 'pending_registration', 'pending_approval' ), true ) ) {
			return new WP_Error( 'sin_invite', __( 'This invitation can no longer be removed.', 'social-invite-network' ) );
		}

		$wpdb->update(
			$table,
			array( 'status' => 'cancelled' ),
			array( 'id' => (int) $row['id'] ),
			array( '%s' ),
			array( '%d' )
		);

		return true;
	}

	/**
	 * Resend invitation email if owned by inviter.
	 *
	 * @param int $invitation_id Invitation row ID.
	 * @param int $inviter_id    Inviter user ID.
	 * @return true|WP_Error
	 */
	public static function resend_for_user( $invitation_id, $inviter_id ) {
		global $wpdb;
		$table = SIN_Database::invitations_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND inviter_user_id = %d LIMIT 1",
				(int) $invitation_id,
				(int) $inviter_id
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return new WP_Error( 'sin_invite', __( 'Invitation not found.', 'social-invite-network' ) );
		}

		return self::resend_email( (int) $row['id'] );
	}
}
