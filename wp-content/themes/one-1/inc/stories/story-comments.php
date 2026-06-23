<?php
/**
 * Story comments — list, form, and AJAX.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enable comments on the story post type.
 */
function one1_story_register_comment_support() {
	add_post_type_support( ONE_STORY_POST_TYPE, 'comments' );
}
add_action( 'init', 'one1_story_register_comment_support', 12 );

/**
 * Only circle members may comment on stories.
 *
 * @param bool $open    Whether comments are open.
 * @param int  $post_id Post ID.
 */
function one1_story_comments_open( $open, $post_id ) {
	if ( ONE_STORY_POST_TYPE !== get_post_type( $post_id ) ) {
		return $open;
	}
	if ( function_exists( 'one1_story_comments_enabled' ) && ! one1_story_comments_enabled( $post_id ) ) {
		return false;
	}
	return one1_can_user_view_story( $post_id, get_current_user_id() );
}
add_filter( 'comments_open', 'one1_story_comments_open', 10, 2 );

/**
 * Unix timestamp for a comment (safe for human_time_diff).
 *
 * @param WP_Comment $comment Comment object.
 * @return int
 */
function one1_get_comment_timestamp( $comment ) {
	if ( ! $comment instanceof WP_Comment ) {
		return 0;
	}

	$timestamp = (int) mysql2date( 'U', $comment->comment_date_gmt, false );
	if ( $timestamp > 0 ) {
		return $timestamp;
	}

	$timestamp = (int) strtotime( $comment->comment_date_gmt . ' GMT' );
	if ( $timestamp > 0 ) {
		return $timestamp;
	}

	return (int) strtotime( $comment->comment_date );
}

/**
 * Render one comment row.
 *
 * @param WP_Comment $comment Comment object.
 */
function one1_render_story_comment_item( $comment ) {
	if ( ! $comment instanceof WP_Comment ) {
		return;
	}
	$user_id     = (int) $comment->user_id;
	$comment_ts  = one1_get_comment_timestamp( $comment );
	$comment_iso = $comment_ts ? gmdate( 'c', $comment_ts ) : '';
	?>
	<li class="one-story-comments__item" id="comment-<?php echo esc_attr( (string) $comment->comment_ID ); ?>">
		<?php echo get_avatar( $user_id ? $user_id : $comment->comment_author_email, 36, '', '', array( 'class' => 'one-story-comments__avatar' ) ); ?>
		<div class="one-story-comments__bubble">
			<p class="one-story-comments__author"><?php echo esc_html( $comment->comment_author ); ?></p>
			<p class="one-story-comments__text"><?php echo esc_html( wp_strip_all_tags( $comment->comment_content ) ); ?></p>
			<?php if ( $comment_ts > 0 ) : ?>
			<time class="one-story-comments__time" datetime="<?php echo esc_attr( $comment_iso ); ?>">
				<?php
				printf(
					/* translators: %s: human-readable time */
					esc_html__( '%s ago', 'one' ),
					esc_html( human_time_diff( $comment_ts, (int) current_time( 'timestamp', true ) ) )
				);
				?>
			</time>
			<?php endif; ?>
		</div>
	</li>
	<?php
}

/**
 * Render comments section for a story.
 *
 * @param int $post_id   Post ID.
 * @param int $viewer_id Viewer user ID.
 */
function one1_render_story_comments( $post_id, $viewer_id ) {
	$post_id   = (int) $post_id;
	$viewer_id = (int) $viewer_id;

	if ( function_exists( 'one1_story_comments_enabled' ) && ! one1_story_comments_enabled( $post_id ) ) {
		return;
	}

	$comments  = get_comments(
		array(
			'post_id' => $post_id,
			'status'  => 'approve',
			'order'   => 'ASC',
		)
	);
	$can_comment = $viewer_id > 0 && one1_can_user_view_story( $post_id, $viewer_id );
	$count       = count( $comments );
	?>
	<section class="one-story-comments" data-one-story-comments data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
		<h2 class="one-story-comments__title">
			<?php esc_html_e( 'Comments', 'one' ); ?>
			<span class="one-story-comments__count" data-one-comment-count><?php echo esc_html( (string) $count ); ?></span>
		</h2>

		<?php if ( $comments ) : ?>
			<ul class="one-story-comments__list" data-one-story-comments-list>
				<?php foreach ( $comments as $comment ) : ?>
					<?php one1_render_story_comment_item( $comment ); ?>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<ul class="one-story-comments__list" data-one-story-comments-list hidden></ul>
			<p class="one-story-comments__empty" data-one-story-comments-empty><?php esc_html_e( 'Be the first to leave a comment.', 'one' ); ?></p>
		<?php endif; ?>

		<?php if ( $can_comment ) : ?>
			<form class="one-story-comments__form" data-one-story-comment-form novalidate>
				<label class="one-story-comments__visually-hidden" for="one-story-comment-<?php echo esc_attr( (string) $post_id ); ?>">
					<?php esc_html_e( 'Write a comment', 'one' ); ?>
				</label>
				<textarea
					id="one-story-comment-<?php echo esc_attr( (string) $post_id ); ?>"
					class="one-story-comments__input"
					name="comment"
					rows="2"
					placeholder="<?php esc_attr_e( 'Write a comment…', 'one' ); ?>"
					required
				></textarea>
				<div class="one-story-comments__form-actions">
					<p class="one-story-comments__feedback" data-one-story-comment-feedback hidden role="status"></p>
					<button type="submit" class="one-story-comments__submit" data-one-story-comment-submit>
						<?php esc_html_e( 'Post comment', 'one' ); ?>
					</button>
				</div>
			</form>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * AJAX: add a comment to a story.
 */
function one1_ajax_add_story_comment() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Please log in to comment.', 'one' ) ), 401 );
	}

	check_ajax_referer( 'one_story_comment', 'nonce' );

	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	$text    = isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '';

	if ( $post_id <= 0 || '' === trim( $text ) ) {
		wp_send_json_error( array( 'message' => __( 'Please write a comment.', 'one' ) ) );
	}

	if ( ! one1_can_user_view_story( $post_id, get_current_user_id() ) ) {
		wp_send_json_error( array( 'message' => __( 'You cannot comment on this post.', 'one' ) ), 403 );
	}

	if ( function_exists( 'one1_story_comments_enabled' ) && ! one1_story_comments_enabled( $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Comments are disabled on this post.', 'one' ) ), 403 );
	}

	$user = wp_get_current_user();
	$comment_id = wp_insert_comment(
		array(
			'comment_post_ID'      => $post_id,
			'comment_content'      => $text,
			'user_id'                => get_current_user_id(),
			'comment_author'       => $user->display_name ? $user->display_name : $user->user_login,
			'comment_author_email' => $user->user_email,
			'comment_approved'     => 1,
		)
	);

	if ( ! $comment_id || is_wp_error( $comment_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Could not post your comment.', 'one' ) ) );
	}

	$comment = get_comment( $comment_id );
	ob_start();
	one1_render_story_comment_item( $comment );
	$html = ob_get_clean();

	wp_send_json_success(
		array(
			'html'  => $html,
			'count' => (int) get_comments_number( $post_id ),
		)
	);
}
add_action( 'wp_ajax_one_story_add_comment', 'one1_ajax_add_story_comment' );
