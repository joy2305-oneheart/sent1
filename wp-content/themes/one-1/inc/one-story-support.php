<?php
/**
 * Story support (like) toggles per user.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta key storing supporter user IDs.
 */
function one1_story_supporters_meta_key() {
	return 'one_story_supporters';
}

/**
 * @param int $post_id Post ID.
 * @return int[]
 */
function one1_get_story_supporter_ids( $post_id ) {
	$raw = get_post_meta( (int) $post_id, one1_story_supporters_meta_key(), true );
	if ( ! is_array( $raw ) ) {
		return array();
	}
	return array_values(
		array_unique(
			array_filter(
				array_map( 'intval', $raw )
			)
		)
	);
}

/**
 * @param int $post_id Post ID.
 */
function one1_get_story_support_count( $post_id ) {
	return count( one1_get_story_supporter_ids( $post_id ) );
}

/**
 * @param int $post_id Post ID.
 * @param int $user_id User ID.
 */
function one1_user_has_supported_story( $post_id, $user_id ) {
	if ( $user_id <= 0 ) {
		return false;
	}
	return in_array( (int) $user_id, one1_get_story_supporter_ids( $post_id ), true );
}

/**
 * Whether the viewer may support a story.
 *
 * @param int $post_id   Post ID.
 * @param int $viewer_id Viewer user ID.
 */
function one1_can_user_support_story( $post_id, $viewer_id ) {
	if ( $viewer_id <= 0 ) {
		return false;
	}

	$post = get_post( (int) $post_id );
	if ( ! $post || 'story' !== $post->post_type || 'publish' !== $post->post_status ) {
		return false;
	}

	if ( ! function_exists( 'sin_is_network_approved' ) || ! sin_is_network_approved( $viewer_id ) ) {
		return false;
	}

	if ( ! function_exists( 'sin_get_share_feed_author_ids' ) ) {
		return false;
	}

	$allowed = sin_get_share_feed_author_ids( $viewer_id );
	return in_array( (int) $post->post_author, $allowed, true );
}

/**
 * Toggle support for a story.
 *
 * @param int $post_id Post ID.
 * @param int $user_id User ID.
 * @return array{supported:bool,count:int}|WP_Error
 */
function one1_toggle_story_support( $post_id, $user_id ) {
	if ( ! one1_can_user_support_story( $post_id, $user_id ) ) {
		return new WP_Error( 'forbidden', __( 'You cannot support this post.', 'one' ) );
	}

	$supporters = one1_get_story_supporter_ids( $post_id );
	$user_id    = (int) $user_id;
	$supported  = false;

	if ( in_array( $user_id, $supporters, true ) ) {
		$supporters = array_values(
			array_filter(
				$supporters,
				static function ( $id ) use ( $user_id ) {
					return (int) $id !== $user_id;
				}
			)
		);
	} else {
		$supporters[] = $user_id;
		$supported    = true;
	}

	update_post_meta( (int) $post_id, one1_story_supporters_meta_key(), $supporters );

	return array(
		'supported' => $supported,
		'count'     => count( $supporters ),
	);
}

/**
 * AJAX: toggle story support.
 */
function one1_ajax_toggle_story_support() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Please log in to support posts.', 'one' ) ), 401 );
	}

	check_ajax_referer( 'one_story_support', 'nonce' );

	$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
	$user_id = get_current_user_id();

	$result = one1_toggle_story_support( $post_id, $user_id );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 403 );
	}

	wp_send_json_success( $result );
}
add_action( 'wp_ajax_one_story_toggle_support', 'one1_ajax_toggle_story_support' );

/**
 * Format money for display.
 *
 * @param float $amount Amount.
 */
function one1_format_story_money( $amount ) {
	return '$' . number_format_i18n( (float) $amount, 0 );
}

/**
 * Whether a viewer may open a single story permalink.
 *
 * @param int $post_id   Post ID.
 * @param int $viewer_id Viewer user ID.
 */
function one1_can_user_view_story( $post_id, $viewer_id ) {
	$post_id   = (int) $post_id;
	$viewer_id = (int) $viewer_id;

	$post = get_post( $post_id );
	if ( ! $post || 'story' !== $post->post_type || 'publish' !== $post->post_status ) {
		return false;
	}

	if ( function_exists( 'one1_story_is_hidden_from_members' ) && one1_story_is_hidden_from_members( $post_id ) ) {
		return false;
	}

	if ( $viewer_id <= 0 ) {
		return false;
	}

	if ( (int) $post->post_author === $viewer_id ) {
		return true;
	}

	$user = get_userdata( $viewer_id );
	if ( $user && in_array( 'administrator', (array) $user->roles, true ) ) {
		return true;
	}

	if ( function_exists( 'sin_is_staff_user' ) && sin_is_staff_user( $viewer_id ) ) {
		return true;
	}

	if ( ! function_exists( 'sin_is_network_approved' ) || ! sin_is_network_approved( $viewer_id ) ) {
		return false;
	}

	if ( ! function_exists( 'sin_get_share_feed_author_ids' ) ) {
		return false;
	}

	$allowed = sin_get_share_feed_author_ids( $viewer_id );
	return in_array( (int) $post->post_author, $allowed, true );
}
