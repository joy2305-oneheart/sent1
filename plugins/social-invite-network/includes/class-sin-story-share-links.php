<?php
/**
 * Temporary public story share links (24-hour token).
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Story_Share_Links
 */
class SIN_Story_Share_Links {

	const STATUS_ACTIVE  = 'active';
	const STATUS_EXPIRED = 'expired';
	const STATUS_REVOKED = 'revoked';

	const EXPIRY_SECONDS = DAY_IN_SECONDS;

	/**
	 * Table name.
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sin_story_share_links';
	}

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_one_create_story_share_link', array( __CLASS__, 'ajax_create' ) );
	}

	/**
	 * Expire stale links.
	 */
	public static function expire_stale() {
		global $wpdb;
		$table = self::table();
		$now   = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = %s WHERE status = %s AND expires_at <= %s",
				self::STATUS_EXPIRED,
				self::STATUS_ACTIVE,
				$now
			)
		);
	}

	/**
	 * Create a new share link for a story (revokes prior active links for same post).
	 *
	 * @param int $post_id         Story post ID.
	 * @param int $user_id         PU user ID.
	 * @param int $expiry_seconds  Link lifetime in seconds.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create( $post_id, $user_id, $expiry_seconds = 0 ) {
		global $wpdb;

		$post_id = (int) $post_id;
		$user_id = (int) $user_id;

		$post = get_post( $post_id );
		if ( ! $post || 'story' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new WP_Error( 'sin_share', __( 'Story not found.', 'social-invite-network' ) );
		}

		if ( (int) $post->post_author !== $user_id && ! sin_is_staff_user( $user_id ) ) {
			return new WP_Error( 'sin_share', __( 'You can only share your own posts.', 'social-invite-network' ) );
		}

		if ( ! sin_is_pu( $user_id ) && ! sin_is_staff_user( $user_id ) ) {
			return new WP_Error( 'sin_share', __( 'Only Primary Users can create public share links.', 'social-invite-network' ) );
		}

		self::expire_stale();

		$table = self::table();
		$wpdb->update(
			$table,
			array( 'status' => self::STATUS_REVOKED ),
			array(
				'post_id' => $post_id,
				'status'  => self::STATUS_ACTIVE,
			),
			array( '%s' ),
			array( '%d', '%s' )
		);

		$expiry_seconds = (int) $expiry_seconds;
		if ( $expiry_seconds <= 0 ) {
			$expiry_seconds = self::EXPIRY_SECONDS;
		}
		$expiry_seconds = max( HOUR_IN_SECONDS, min( 30 * DAY_IN_SECONDS, $expiry_seconds ) );

		$token      = bin2hex( random_bytes( 32 ) );
		$created_at = current_time( 'mysql', true );
		$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( $created_at ) + $expiry_seconds );

		$inserted = $wpdb->insert(
			$table,
			array(
				'post_id'    => $post_id,
				'token'      => $token,
				'created_by' => $user_id,
				'status'     => self::STATUS_ACTIVE,
				'created_at' => $created_at,
				'expires_at' => $expires_at,
			),
			array( '%d', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return new WP_Error( 'sin_share', __( 'Could not create share link.', 'social-invite-network' ) );
		}

		return array(
			'token'      => $token,
			'url'        => self::public_url( $token ),
			'expires_at' => $expires_at,
		);
	}

	/**
	 * Validate token and return row.
	 *
	 * @param string $token Token.
	 * @return array<string, mixed>|null
	 */
	public static function get_valid_row( $token ) {
		if ( ! is_string( $token ) || $token === '' ) {
			return null;
		}

		self::expire_stale();

		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE token = %s AND status = %s LIMIT 1",
				$token,
				self::STATUS_ACTIVE
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		$now = current_time( 'mysql', true );
		if ( $row['expires_at'] <= $now ) {
			$wpdb->update(
				$table,
				array( 'status' => self::STATUS_EXPIRED ),
				array( 'id' => (int) $row['id'] ),
				array( '%s' ),
				array( '%d' )
			);
			return null;
		}

		$post = get_post( (int) $row['post_id'] );
		if ( ! $post || 'story' !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		if ( function_exists( 'one1_story_is_hidden_from_members' ) && one1_story_is_hidden_from_members( (int) $row['post_id'] ) ) {
			return null;
		}

		return $row;
	}

	/**
	 * Public URL for a token.
	 *
	 * @param string $token Token.
	 */
	public static function public_url( $token ) {
		$url = function_exists( 'one1_public_story_page_url' )
			? one1_public_story_page_url()
			: home_url( '/view/' );
		return add_query_arg( 't', rawurlencode( $token ), $url );
	}

	/**
	 * AJAX: create share link.
	 */
	public static function ajax_create() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in.', 'social-invite-network' ) ), 401 );
		}

		check_ajax_referer( 'one_story_share_link', 'nonce' );

		$post_id        = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$expiry_seconds = isset( $_POST['expiry_seconds'] ) ? (int) $_POST['expiry_seconds'] : 0;
		$result         = self::create( $post_id, get_current_user_id(), $expiry_seconds );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( $result );
	}
}
