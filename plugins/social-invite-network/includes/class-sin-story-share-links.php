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
		add_action( 'wp_ajax_one_story_send_share_email', array( __CLASS__, 'ajax_send_email' ) );
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
		return add_query_arg( 't', $token, $url );
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

	/**
	 * Send a public share link to one or more email addresses.
	 *
	 * @param int    $post_id        Story post ID.
	 * @param int    $user_id        Sender user ID.
	 * @param array  $emails         Recipient emails.
	 * @param int    $expiry_seconds Link lifetime in seconds.
	 * @return array{sent:int,failed:int}|WP_Error
	 */
	public static function send_to_emails( $post_id, $user_id, $emails, $expiry_seconds = 0 ) {
		$post_id = (int) $post_id;
		$user_id = (int) $user_id;
		$emails  = array_values(
			array_filter(
				array_map(
					static function ( $email ) {
						$email = sanitize_email( (string) $email );
						return is_email( $email ) ? $email : '';
					},
					(array) $emails
				)
			)
		);

		if ( empty( $emails ) ) {
			return new WP_Error( 'sin_share', __( 'Please enter at least one valid email address.', 'social-invite-network' ) );
		}

		$link = self::create( $post_id, $user_id, $expiry_seconds );
		if ( is_wp_error( $link ) ) {
			return $link;
		}

		$post   = get_post( $post_id );
		$author = get_userdata( $user_id );
		$site   = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$title  = function_exists( 'one1_story_has_headline' ) && one1_story_has_headline( $post_id )
			? get_the_title( $post_id )
			: __( 'Shared post', 'social-invite-network' );
		$excerpt = wp_trim_words( wp_strip_all_tags( $post ? $post->post_content : '' ), 40, '…' );

		$subject = sprintf(
			/* translators: 1: author name, 2: site name */
			__( '%1$s shared a post with you on %2$s', 'social-invite-network' ),
			$author ? $author->display_name : __( 'Someone', 'social-invite-network' ),
			$site
		);

		$body = sprintf(
			/* translators: 1: author name, 2: post title, 3: excerpt, 4: share URL, 5: site name */
			__( "Hello,\n\n%1\$s shared a post with you:\n\n%2\$s\n%3\$s\n\nView it here (no login required):\n%4\$s\n\nThis link expires after the time limit set by the sender.\n\n— %5\$s", 'social-invite-network' ),
			$author ? $author->display_name : __( 'Someone', 'social-invite-network' ),
			$title,
			$excerpt,
			$link['url'],
			$site
		);

		$sent   = 0;
		$failed = 0;
		foreach ( $emails as $email ) {
			if ( wp_mail( $email, $subject, $body ) ) {
				++$sent;
			} else {
				++$failed;
			}
		}

		if ( $sent <= 0 ) {
			return new WP_Error( 'sin_share', __( 'Could not send the email. Please check your mail settings and try again.', 'social-invite-network' ) );
		}

		return array(
			'sent'   => $sent,
			'failed' => $failed,
			'url'    => $link['url'],
		);
	}

	/**
	 * AJAX: email public share link to specific addresses.
	 */
	public static function ajax_send_email() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in.', 'social-invite-network' ) ), 401 );
		}

		check_ajax_referer( 'one_story_share_link', 'nonce' );

		$post_id        = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$expiry_seconds = isset( $_POST['expiry_seconds'] ) ? (int) $_POST['expiry_seconds'] : 0;
		$raw_emails     = isset( $_POST['emails'] ) ? sanitize_text_field( wp_unslash( $_POST['emails'] ) ) : '';
		$emails         = preg_split( '/[\s,;]+/', $raw_emails, -1, PREG_SPLIT_NO_EMPTY );

		$result = self::send_to_emails( $post_id, get_current_user_id(), $emails, $expiry_seconds );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of emails sent */
					__( 'Share link sent to %d recipient(s).', 'social-invite-network' ),
					(int) $result['sent']
				),
				'url'     => $result['url'],
				'sent'    => (int) $result['sent'],
			)
		);
	}
}
