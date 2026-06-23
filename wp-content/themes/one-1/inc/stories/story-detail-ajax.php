<?php
/**
 * Story detail loading for profile modal (fragment URL + AJAX).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verify viewer may load a story detail fragment.
 *
 * @param int $post_id Post ID.
 * @param int $viewer  Viewer user ID.
 * @return true|WP_Error
 */
function one1_validate_story_detail_request( $post_id, $viewer ) {
	$post_id = (int) $post_id;
	$viewer  = (int) $viewer;

	if ( $post_id <= 0 ) {
		return new WP_Error( 'invalid_post', __( 'Invalid post.', 'one' ) );
	}

	$post = get_post( $post_id );
	if ( ! $post || ONE_STORY_POST_TYPE !== $post->post_type ) {
		return new WP_Error( 'not_found', __( 'Post not found.', 'one' ) );
	}

	if ( function_exists( 'one1_story_is_hidden_from_members' ) && one1_story_is_hidden_from_members( $post_id ) ) {
		return new WP_Error( 'not_found', __( 'Post not found.', 'one' ) );
	}

	if ( 'publish' !== $post->post_status ) {
		$is_author = (int) $post->post_author === $viewer;
		if ( ! $is_author && ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'not_found', __( 'Post not found.', 'one' ) );
		}
	}

	$is_author = (int) $post->post_author === $viewer;
	if ( ! $is_author && ! one1_can_user_view_story( $post_id, $viewer ) ) {
		return new WP_Error( 'forbidden', __( 'This post is not available.', 'one' ) );
	}

	return true;
}

/**
 * Render story detail HTML or return empty string.
 *
 * @param int $post_id Post ID.
 * @param int $viewer  Viewer user ID.
 * @return string
 */
function one1_load_story_detail_html( $post_id, $viewer ) {
	if ( ! function_exists( 'one1_get_story_view_html' ) ) {
		return '';
	}

	return one1_get_story_view_html( (int) $post_id, (int) $viewer, 'modal' );
}

/**
 * GET fragment endpoint — avoids admin-ajax returning 0 on some hosts.
 */
function one1_story_detail_fragment_endpoint() {
	if ( ! isset( $_GET['one1_story_fragment'] ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		status_header( 401 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo esc_html__( 'Please log in to view this post.', 'one' );
		exit;
	}

	$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'one_story_detail' ) ) {
		status_header( 403 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo esc_html__( 'Security check failed. Please refresh the page.', 'one' );
		exit;
	}

	$post_id = (int) $_GET['one1_story_fragment'];
	$viewer  = get_current_user_id();
	$valid   = one1_validate_story_detail_request( $post_id, $viewer );

	if ( is_wp_error( $valid ) ) {
		status_header( 'forbidden' === $valid->get_error_code() ? 403 : 404 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo esc_html( $valid->get_error_message() );
		exit;
	}

	$html = one1_load_story_detail_html( $post_id, $viewer );
	if ( '' === trim( $html ) ) {
		status_header( 500 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo esc_html__( 'Unable to load post. Please try again.', 'one' );
		exit;
	}

	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted theme markup.
	echo $html;
	exit;
}
add_action( 'template_redirect', 'one1_story_detail_fragment_endpoint', 0 );

/**
 * AJAX: load story detail HTML for profile modal.
 */
function one1_ajax_story_detail() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Please log in to view this post.', 'one' ) ), 401 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'one_story_detail' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page.', 'one' ) ), 403 );
	}

	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	$viewer  = get_current_user_id();
	$valid   = one1_validate_story_detail_request( $post_id, $viewer );

	if ( is_wp_error( $valid ) ) {
		$code = $valid->get_error_code();
		$http = 'forbidden' === $code ? 403 : ( 'invalid_post' === $code ? 400 : 404 );
		wp_send_json_error( array( 'message' => $valid->get_error_message() ), $http );
	}

	$html = one1_load_story_detail_html( $post_id, $viewer );
	if ( '' === trim( $html ) ) {
		wp_send_json_error( array( 'message' => __( 'Unable to load post. Please try again.', 'one' ) ) );
	}

	wp_send_json_success( array( 'html' => $html ) );
}

/**
 * AJAX: logged-out story detail request.
 */
function one1_ajax_story_detail_nopriv() {
	wp_send_json_error( array( 'message' => __( 'Please log in to view this post.', 'one' ) ), 401 );
}

add_action( 'wp_ajax_one_story_detail', 'one1_ajax_story_detail' );
add_action( 'wp_ajax_nopriv_one_story_detail', 'one1_ajax_story_detail_nopriv' );

/**
 * Profile modal config for JS.
 *
 * @return array<string, string>
 */
function one1_profile_modal_config() {
	return array(
		'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
		'fragmentBase' => home_url( '/' ),
		'nonce'        => wp_create_nonce( 'one_story_detail' ),
	);
}
