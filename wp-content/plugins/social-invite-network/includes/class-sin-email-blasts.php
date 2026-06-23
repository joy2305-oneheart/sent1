<?php
/**
 * Email blasts from PU to friends when new content is published.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Email_Blasts
 */
class SIN_Email_Blasts {

	const META_SENT_AT = 'one_story_blast_sent_at';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_one_story_send_blast', array( __CLASS__, 'ajax_send' ) );
	}

	/**
	 * Send blast emails to all followers of the story author.
	 *
	 * @param int  $post_id Story post ID.
	 * @param bool $force   Send even if already sent.
	 * @return array{sent:int,skipped:int}|WP_Error
	 */
	public static function send_for_story( $post_id, $force = false ) {
		$post_id = (int) $post_id;
		$post    = get_post( $post_id );

		if ( ! $post || 'story' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new WP_Error( 'sin_blast', __( 'Story not found.', 'social-invite-network' ) );
		}

		$author_id = (int) $post->post_author;
		if ( ! sin_is_pu( $author_id ) && ! sin_is_staff_user( $author_id ) ) {
			return new WP_Error( 'sin_blast', __( 'Only Primary Users can notify friends.', 'social-invite-network' ) );
		}

		if ( ! $force && get_post_meta( $post_id, self::META_SENT_AT, true ) ) {
			return new WP_Error( 'sin_blast', __( 'Friends were already notified about this post.', 'social-invite-network' ) );
		}

		$follower_ids = sin_get_follower_ids( $author_id );
		if ( empty( $follower_ids ) ) {
			return new WP_Error( 'sin_blast', __( 'You have no friends to notify yet.', 'social-invite-network' ) );
		}

		$author  = get_userdata( $author_id );
		$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 40, '…' );
		$title   = one1_story_has_headline( $post_id ) ? get_the_title( $post_id ) : __( 'New update', 'social-invite-network' );

		$story_url = get_permalink( $post_id );
		$login_url = function_exists( 'one1_login_url' )
			? one1_login_url( $story_url )
			: wp_login_url( $story_url );

		$settings = sin_get_settings();
		$subj_tpl = isset( $settings['blast_email_subject'] ) ? (string) $settings['blast_email_subject'] : '{author_name} shared an update on {site_name}';
		$body_tpl = isset( $settings['blast_email_body'] ) ? (string) $settings['blast_email_body'] : '';

		if ( $body_tpl === '' ) {
			$body_tpl = "Hello,\n\n{author_name} shared a new update on {site_name}.\n\n{post_title}\n{post_excerpt}\n\nRead and comment after you sign in:\n{login_link}\n\nThis message was sent because you follow {author_name}'s journey.\n";
		}

		$replacements = array(
			'{site_name}'    => $site,
			'{author_name}'  => $author ? $author->display_name : '',
			'{post_title}'   => $title,
			'{post_excerpt}' => $excerpt,
			'{login_link}'   => $login_url,
			'{story_link}'   => $story_url,
		);

		$subject = strtr( $subj_tpl, $replacements );
		$body    = strtr( $body_tpl, $replacements );

		$sent    = 0;
		$skipped = 0;

		foreach ( $follower_ids as $friend_id ) {
			$friend = get_userdata( (int) $friend_id );
			if ( ! $friend || ! is_email( $friend->user_email ) ) {
				++$skipped;
				continue;
			}
			if ( wp_mail( $friend->user_email, $subject, $body ) ) {
				++$sent;
			} else {
				++$skipped;
			}
		}

		update_post_meta( $post_id, self::META_SENT_AT, current_time( 'mysql', true ) );

		return array(
			'sent'    => $sent,
			'skipped' => $skipped,
		);
	}

	/**
	 * Whether blast was already sent for a story.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function was_sent( $post_id ) {
		return (bool) get_post_meta( (int) $post_id, self::META_SENT_AT, true );
	}

	/**
	 * AJAX: manually send blast.
	 */
	public static function ajax_send() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in.', 'social-invite-network' ) ), 401 );
		}

		check_ajax_referer( 'one_story_blast', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$post    = get_post( $post_id );

		if ( ! $post || (int) $post->post_author !== get_current_user_id() ) {
			wp_send_json_error( array( 'message' => __( 'You cannot notify friends for this post.', 'social-invite-network' ) ), 403 );
		}

		$force  = ! empty( $_POST['force'] );
		$result = self::send_for_story( $post_id, $force );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of emails sent */
					__( 'Notification sent to %d friend(s).', 'social-invite-network' ),
					(int) $result['sent']
				),
				'sent'    => (int) $result['sent'],
			)
		);
	}
}
