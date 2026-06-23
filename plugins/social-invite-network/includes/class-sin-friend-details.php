<?php
/**
 * PU-managed friend list details (nickname, notes).
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Friend_Details
 */
class SIN_Friend_Details {

	/**
	 * Table name.
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sin_friend_details';
	}

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_sin_save_friend_details', array( __CLASS__, 'ajax_save' ) );
		add_action( 'wp_ajax_sin_get_friends_list', array( __CLASS__, 'ajax_list' ) );
	}

	/**
	 * Get details row for a PU/friend pair.
	 *
	 * @param int $pu_id     PU user ID.
	 * @param int $friend_id Friend user ID.
	 * @return array{nickname:string,notes:string}
	 */
	public static function get( $pu_id, $friend_id ) {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT nickname, notes FROM {$table} WHERE inviter_id = %d AND friend_id = %d LIMIT 1",
				(int) $pu_id,
				(int) $friend_id
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return array(
				'nickname' => '',
				'notes'    => '',
			);
		}

		return array(
			'nickname' => (string) ( $row['nickname'] ?? '' ),
			'notes'    => (string) ( $row['notes'] ?? '' ),
		);
	}

	/**
	 * Save friend details.
	 *
	 * @param int    $pu_id     PU user ID.
	 * @param int    $friend_id Friend user ID.
	 * @param string $nickname  Optional nickname.
	 * @param string $notes     Optional notes.
	 * @return bool|WP_Error
	 */
	public static function save( $pu_id, $friend_id, $nickname, $notes ) {
		global $wpdb;

		$pu_id     = (int) $pu_id;
		$friend_id = (int) $friend_id;

		if ( ! sin_is_pu( $pu_id ) && ! sin_is_staff_user( $pu_id ) ) {
			return new WP_Error( 'sin_friend', __( 'Only Primary Users can manage friends.', 'social-invite-network' ) );
		}

		$follower_ids = sin_get_follower_ids( $pu_id );
		if ( ! in_array( $friend_id, $follower_ids, true ) ) {
			return new WP_Error( 'sin_friend', __( 'That person is not in your friends list.', 'social-invite-network' ) );
		}

		$nickname = sanitize_text_field( (string) $nickname );
		$notes    = sanitize_textarea_field( (string) $notes );

		if ( strlen( $nickname ) > 190 ) {
			$nickname = substr( $nickname, 0, 190 );
		}

		$table = self::table();
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT inviter_id FROM {$table} WHERE inviter_id = %d AND friend_id = %d",
				$pu_id,
				$friend_id
			)
		);

		$data = array(
			'nickname'   => $nickname,
			'notes'      => $notes,
			'updated_at' => current_time( 'mysql', true ),
		);

		if ( $exists ) {
			$wpdb->update(
				$table,
				$data,
				array(
					'inviter_id' => $pu_id,
					'friend_id'  => $friend_id,
				),
				array( '%s', '%s', '%s' ),
				array( '%d', '%d' )
			);
		} else {
			$wpdb->insert(
				$table,
				array_merge(
					$data,
					array(
						'inviter_id' => $pu_id,
						'friend_id'  => $friend_id,
					)
				),
				array( '%s', '%s', '%s', '%d', '%d' )
			);
		}

		return true;
	}

	/**
	 * Build friend list with stats for a PU.
	 *
	 * @param int $pu_id PU user ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_friends( $pu_id ) {
		$pu_id        = (int) $pu_id;
		$follower_ids = sin_get_follower_ids( $pu_id );
		$friends      = array();

		foreach ( $follower_ids as $friend_id ) {
			$user = get_userdata( (int) $friend_id );
			if ( ! $user ) {
				continue;
			}

			$details = self::get( $pu_id, (int) $friend_id );
			$joined  = $user->user_registered;

			$friends[] = array(
				'id'           => (int) $friend_id,
				'display_name' => $user->display_name,
				'email'        => $user->user_email,
				'nickname'     => $details['nickname'],
				'notes'        => $details['notes'],
				'joined'       => $joined,
				'role'         => in_array( 'friend', (array) $user->roles, true ) ? 'friend' : 'pu',
				'avatar_url'   => get_avatar_url( (int) $friend_id, array( 'size' => 96 ) ),
			);
		}

		return $friends;
	}

	/**
	 * Display label for a friend (nickname or display name).
	 *
	 * @param int $pu_id     PU user ID.
	 * @param int $friend_id Friend user ID.
	 */
	public static function display_name( $pu_id, $friend_id ) {
		$details = self::get( $pu_id, $friend_id );
		if ( $details['nickname'] !== '' ) {
			return $details['nickname'];
		}
		$user = get_userdata( (int) $friend_id );
		return $user ? $user->display_name : '';
	}

	/**
	 * AJAX: save friend details.
	 */
	public static function ajax_save() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in.', 'social-invite-network' ) ), 401 );
		}

		check_ajax_referer( 'sin_friend_details', 'nonce' );

		$pu_id     = get_current_user_id();
		$friend_id = isset( $_POST['friend_id'] ) ? (int) $_POST['friend_id'] : 0;
		$nickname  = isset( $_POST['nickname'] ) ? sanitize_text_field( wp_unslash( $_POST['nickname'] ) ) : '';
		$notes     = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		$result = self::save( $pu_id, $friend_id, $nickname, $notes );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'message' => __( 'Friend details saved.', 'social-invite-network' ) ) );
	}

	/**
	 * AJAX: list friends with stats.
	 */
	public static function ajax_list() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in.', 'social-invite-network' ) ), 401 );
		}

		check_ajax_referer( 'sin_friend_details', 'nonce' );

		$pu_id = get_current_user_id();
		if ( ! sin_is_pu( $pu_id ) && ! sin_is_staff_user( $pu_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Only Primary Users can view this list.', 'social-invite-network' ) ), 403 );
		}

		$friends = self::list_friends( $pu_id );

		wp_send_json_success(
			array(
				'friends' => $friends,
				'count'   => count( $friends ),
			)
		);
	}
}
