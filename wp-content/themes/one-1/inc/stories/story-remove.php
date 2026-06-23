<?php
/**
 * Member archive / delete for story posts (soft hide from network, retained in admin).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONE_STORY_MEMBER_ARCHIVED_META', 'one_story_member_archived' );
define( 'ONE_STORY_MEMBER_ARCHIVED_AT_META', 'one_story_member_archived_at' );

/**
 * Meta query args excluding member-archived stories from frontend lists.
 *
 * @return array<int, array<string, mixed>>
 */
function one1_story_exclude_member_archived_meta_query() {
	return array(
		'relation' => 'OR',
		array(
			'key'     => ONE_STORY_MEMBER_ARCHIVED_META,
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => ONE_STORY_MEMBER_ARCHIVED_META,
			'value'   => '1',
			'compare' => '!=',
		),
	);
}

/**
 * Whether a story is hidden from the member-facing site.
 *
 * @param int $post_id Post ID.
 */
function one1_story_is_hidden_from_members( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return true;
	}

	$post = get_post( $post_id );
	if ( ! $post || ONE_STORY_POST_TYPE !== $post->post_type ) {
		return true;
	}

	if ( 'trash' === $post->post_status ) {
		return true;
	}

	return '1' === (string) get_post_meta( $post_id, ONE_STORY_MEMBER_ARCHIVED_META, true );
}

/**
 * Whether the user may archive or delete a story from the frontend.
 *
 * @param int $post_id Post ID.
 * @param int $user_id User ID.
 */
function one1_can_user_manage_own_story( $post_id, $user_id ) {
	$post_id = (int) $post_id;
	$user_id = (int) $user_id;

	if ( $post_id <= 0 || $user_id <= 0 ) {
		return false;
	}

	if ( function_exists( 'sin_is_pu' ) && ! sin_is_pu( $user_id ) ) {
		return false;
	}

	$post = get_post( $post_id );
	if ( ! $post || ONE_STORY_POST_TYPE !== $post->post_type ) {
		return false;
	}

	if ( (int) $post->post_author !== $user_id ) {
		return false;
	}

	return ! one1_story_is_hidden_from_members( $post_id );
}

/**
 * Archive a story (hidden from members, still published in admin).
 *
 * @param int $post_id Post ID.
 * @param int $user_id User ID.
 * @return true|WP_Error
 */
function one1_archive_story_for_member( $post_id, $user_id ) {
	if ( ! one1_can_user_manage_own_story( $post_id, $user_id ) ) {
		return new WP_Error( 'forbidden', __( 'You cannot archive this post.', 'one' ) );
	}

	update_post_meta( (int) $post_id, ONE_STORY_MEMBER_ARCHIVED_META, '1' );
	update_post_meta( (int) $post_id, ONE_STORY_MEMBER_ARCHIVED_AT_META, gmdate( 'Y-m-d H:i:s' ) );

	return true;
}

/**
 * Delete a story for members (move to trash; admin can restore from Stories → Trash).
 *
 * @param int $post_id Post ID.
 * @param int $user_id User ID.
 * @return true|WP_Error
 */
function one1_delete_story_for_member( $post_id, $user_id ) {
	if ( ! one1_can_user_manage_own_story( $post_id, $user_id ) ) {
		return new WP_Error( 'forbidden', __( 'You cannot delete this post.', 'one' ) );
	}

	$trashed = wp_trash_post( (int) $post_id );
	if ( ! $trashed ) {
		return new WP_Error( 'delete_failed', __( 'Could not delete this post.', 'one' ) );
	}

	return true;
}

/**
 * Render owner manage actions on story detail.
 *
 * @param int $post_id Post ID.
 */
function one1_render_story_owner_actions( $post_id ) {
	$post_id = (int) $post_id;
	if ( ! one1_can_user_manage_own_story( $post_id, get_current_user_id() ) ) {
		return;
	}
	?>
	<div class="one-story-view__owner-menu" data-one-story-owner-menu>
		<button
			type="button"
			class="one-story-view__owner-menu-trigger"
			data-one-story-owner-menu-toggle
			aria-expanded="false"
			aria-haspopup="true"
			aria-label="<?php esc_attr_e( 'Post options', 'one' ); ?>"
		>
			<span class="material-symbols-outlined" aria-hidden="true">more_vert</span>
		</button>
		<div class="one-story-view__owner-dropdown" data-one-story-owner-menu-panel hidden role="menu">
			<button type="button" class="one-story-view__owner-dropdown-item" role="menuitem" data-one-story-edit data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
				<span class="material-symbols-outlined" aria-hidden="true">edit</span>
				<?php esc_html_e( 'Edit', 'one' ); ?>
			</button>
			<?php if ( function_exists( 'sin_is_pu' ) && sin_is_pu( get_current_user_id() ) ) : ?>
				<button type="button" class="one-story-view__owner-dropdown-item" role="menuitem" data-one-story-public-share data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
					<span class="material-symbols-outlined" aria-hidden="true">link</span>
					<?php esc_html_e( 'Share publicly', 'one' ); ?>
				</button>
				<?php
				$blast_sent = class_exists( 'SIN_Email_Blasts' ) && SIN_Email_Blasts::was_sent( $post_id );
				?>
				<button
					type="button"
					class="one-story-view__owner-dropdown-item"
					role="menuitem"
					data-one-story-notify-friends
					data-post-id="<?php echo esc_attr( (string) $post_id ); ?>"
					<?php echo $blast_sent ? ' disabled' : ''; ?>
				>
					<span class="material-symbols-outlined" aria-hidden="true">campaign</span>
					<?php echo $blast_sent ? esc_html__( 'Friends notified', 'one' ) : esc_html__( 'Notify friends', 'one' ); ?>
				</button>
			<?php endif; ?>
			<button type="button" class="one-story-view__owner-dropdown-item" role="menuitem" data-one-story-archive data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
				<span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
				<?php esc_html_e( 'Archive', 'one' ); ?>
			</button>
			<button type="button" class="one-story-view__owner-dropdown-item one-story-view__owner-dropdown-item--danger" role="menuitem" data-one-story-delete data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
				<span class="material-symbols-outlined" aria-hidden="true">delete</span>
				<?php esc_html_e( 'Delete', 'one' ); ?>
			</button>
		</div>
	</div>
	<?php
}

/**
 * AJAX: archive or delete own story.
 */
function one1_ajax_story_remove() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Please log in.', 'one' ) ), 401 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'one_story_remove' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'one' ) ), 403 );
	}

	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	$mode    = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';
	$user_id = get_current_user_id();

	if ( ! in_array( $mode, array( 'archive', 'delete' ), true ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid action.', 'one' ) ), 400 );
	}

	if ( 'archive' === $mode ) {
		$result = one1_archive_story_for_member( $post_id, $user_id );
	} else {
		$result = one1_delete_story_for_member( $post_id, $user_id );
	}

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
	}

	wp_send_json_success(
		array(
			'post_id' => $post_id,
			'mode'    => $mode,
		)
	);
}
add_action( 'wp_ajax_one_story_remove', 'one1_ajax_story_remove' );

/**
 * Admin list column: member visibility status.
 *
 * @param string[] $columns Columns.
 * @return string[]
 */
function one1_story_admin_columns( $columns ) {
	$columns['one_member_status'] = __( 'Member visibility', 'one' );
	return $columns;
}
add_filter( 'manage_story_posts_columns', 'one1_story_admin_columns' );

/**
 * Render admin member visibility column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function one1_story_admin_column_content( $column, $post_id ) {
	if ( 'one_member_status' !== $column ) {
		return;
	}

	$post = get_post( (int) $post_id );
	if ( ! $post ) {
		return;
	}

	if ( 'trash' === $post->post_status ) {
		echo esc_html__( 'In trash (removed by member)', 'one' );
		return;
	}

	if ( '1' === (string) get_post_meta( (int) $post_id, ONE_STORY_MEMBER_ARCHIVED_META, true ) ) {
		echo esc_html__( 'Archived by member', 'one' );
		return;
	}

	echo esc_html__( 'Visible', 'one' );
}
add_action( 'manage_story_posts_custom_column', 'one1_story_admin_column_content', 10, 2 );
