<?php
/**
 * Member-facing story edit (AJAX).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse title and content from a story submission request.
 *
 * @return array{title: string, content: string}
 */
function one1_story_parse_submission_fields() {
	$title   = isset( $_POST['one_story_title'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['one_story_title'] ) ) ) : '';
	$content = isset( $_POST['one_story_content'] ) ? wp_kses_post( wp_unslash( $_POST['one_story_content'] ) ) : '';

	return array(
		'title'   => $title,
		'content' => $content,
	);
}

/**
 * Collect posted meta and strip privileged flags for members.
 *
 * @return array<string, mixed>
 */
function one1_story_collect_meta_from_request() {
	$data = One_Story_Admin::collect_posted_data();
	if ( ! current_user_can( 'manage_options' ) ) {
		$data['featured'] = false;
		$data['verified'] = false;
	}

	return $data;
}

/**
 * Validate story meta (donation requirements).
 *
 * @param array<string, mixed> $data Story meta.
 * @param bool                 $require_end_date Whether end date is required.
 * @return true|WP_Error
 */
function one1_story_validate_meta( array $data, $require_end_date = false ) {
	if ( empty( $data['is_donation'] ) ) {
		return true;
	}

	if ( empty( $data['fundraising_goal'] ) || (float) $data['fundraising_goal'] <= 0 ) {
		return new WP_Error( 'invalid_goal', __( 'Please enter a fundraising goal for donation posts.', 'one' ) );
	}

	if ( $require_end_date && empty( $data['end_date'] ) ) {
		return new WP_Error( 'invalid_end_date', __( 'Please enter an end date for donation posts.', 'one' ) );
	}

	return true;
}

/**
 * Upload featured image if provided in the request.
 *
 * @param int $post_id Post ID.
 */
function one1_story_maybe_set_featured_image( $post_id ) {
	if ( empty( $_FILES['one_story_featured_image']['name'] ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_handle_upload( 'one_story_featured_image', (int) $post_id );
	if ( ! is_wp_error( $attachment_id ) ) {
		set_post_thumbnail( (int) $post_id, (int) $attachment_id );
	}
}

/**
 * Save meta and optional featured image for a story.
 *
 * @param int                  $post_id Post ID.
 * @param array<string, mixed> $data    Meta data.
 */
function one1_story_save_meta_and_image( $post_id, array $data ) {
	One_Story_Meta::save_from_array( (int) $post_id, $data );
	one1_story_maybe_set_featured_image( (int) $post_id );
}

/**
 * Build edit payload for the composer modal.
 *
 * @param int $post_id Post ID.
 * @return array<string, mixed>|WP_Error
 */
function one1_story_get_edit_payload( $post_id ) {
	$post_id = (int) $post_id;
	$post    = get_post( $post_id );

	if ( ! $post || ONE_STORY_POST_TYPE !== $post->post_type ) {
		return new WP_Error( 'not_found', __( 'Post not found.', 'one' ) );
	}

	if ( ! one1_can_user_manage_own_story( $post_id, get_current_user_id() ) ) {
		return new WP_Error( 'forbidden', __( 'You cannot edit this post.', 'one' ) );
	}

	$meta    = One_Story_Meta::get_all( $post_id );
	$end_val = ! empty( $meta['end_date'] ) ? One_Story_Meta::format_end_date_for_input( $meta['end_date'] ) : '';
	if ( $end_val && strlen( $end_val ) > 10 ) {
		$end_val = substr( $end_val, 0, 10 );
	}

	return array(
		'post_id'             => $post_id,
		'title'               => $post->post_title,
		'content'             => $post->post_content,
		'thumbnail_url'       => get_the_post_thumbnail_url( $post_id, 'large' ) ?: '',
		'is_donation'         => ! empty( $meta['is_donation'] ),
		'urgency'             => $meta['urgency'] ?? 'standard',
		'fundraising_goal'    => $meta['fundraising_goal'] ? (string) $meta['fundraising_goal'] : '',
		'end_date'            => $end_val,
		'location_label'      => $meta['location_label'] ?? '',
		'location_place_id'   => $meta['location_place_id'] ?? '',
		'city'                => $meta['city'] ?? '',
		'state_region'        => $meta['state_region'] ?? '',
		'comments_enabled'    => ! empty( $meta['comments_enabled'] ),
		'hide_likes'          => ! empty( $meta['hide_likes'] ),
	);
}

/**
 * AJAX: return story data for editing.
 */
function one1_ajax_story_get_edit_data() {
	if ( ! is_user_logged_in() || ! one_story_user_can_submit() ) {
		wp_send_json_error( array( 'message' => __( 'You are not allowed to edit stories.', 'one' ) ), 403 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'one_story_edit' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'one' ) ), 403 );
	}

	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	$result  = one1_story_get_edit_payload( $post_id );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
	}

	wp_send_json_success( $result );
}
add_action( 'wp_ajax_one_story_get_edit_data', 'one1_ajax_story_get_edit_data' );

/**
 * AJAX: update an existing story.
 */
function one1_ajax_story_update() {
	if ( ! is_user_logged_in() || ! one_story_user_can_submit() ) {
		wp_send_json_error( array( 'message' => __( 'You are not allowed to edit stories.', 'one' ) ), 403 );
	}

	if ( ! isset( $_POST['one_story_form_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['one_story_form_nonce'] ) ), 'one_story_form' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'one' ) ), 403 );
	}

	$post_id = isset( $_POST['one_story_post_id'] ) ? (int) $_POST['one_story_post_id'] : 0;
	if ( $post_id <= 0 || ! one1_can_user_manage_own_story( $post_id, get_current_user_id() ) ) {
		wp_send_json_error( array( 'message' => __( 'You cannot edit this post.', 'one' ) ), 403 );
	}

	$fields = one1_story_parse_submission_fields();
	if ( '' === trim( $fields['content'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Please write something for your post.', 'one' ) ), 400 );
	}

	$data = one1_story_collect_meta_from_request();
	$valid = one1_story_validate_meta( $data, false );
	if ( is_wp_error( $valid ) ) {
		wp_send_json_error( array( 'message' => $valid->get_error_message() ), 400 );
	}

	$updated = wp_update_post(
		array(
			'ID'           => $post_id,
			'post_title'   => $fields['title'],
			'post_content' => $fields['content'],
		),
		true
	);

	if ( is_wp_error( $updated ) ) {
		wp_send_json_error( array( 'message' => $updated->get_error_message() ), 400 );
	}

	one1_story_save_meta_and_image( $post_id, $data );

	$thumbnail_url = get_the_post_thumbnail_url( $post_id, 'medium_large' ) ?: '';

	wp_send_json_success(
		array(
			'post_id'       => $post_id,
			'thumbnail_url' => $thumbnail_url,
		)
	);
}
add_action( 'wp_ajax_one_story_update', 'one1_ajax_story_update' );
