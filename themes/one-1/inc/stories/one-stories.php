<?php
/**
 * Stories CPT bootstrap.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONE_STORY_POST_TYPE', 'story' );
define( 'ONE_STORY_VERSION', '1.0.0' );

require_once __DIR__ . '/class-one-story-meta.php';
require_once __DIR__ . '/class-one-story-cpt.php';
require_once __DIR__ . '/class-one-story-admin.php';
require_once __DIR__ . '/class-one-story-frontend.php';
require_once __DIR__ . '/story-location.php';
require_once __DIR__ . '/story-donation-display.php';
require_once __DIR__ . '/story-donation-sync.php';
require_once __DIR__ . '/story-donation-form.php';
require_once __DIR__ . '/story-engage.php';
require_once __DIR__ . '/story-view.php';
require_once __DIR__ . '/story-comments.php';
require_once __DIR__ . '/story-remove.php';
require_once __DIR__ . '/story-edit.php';
require_once __DIR__ . '/story-analytics.php';
require_once __DIR__ . '/story-public-share.php';

One_Story_CPT::init();
One_Story_Admin::init();
One_Story_Frontend::init();

add_filter( 'wp_insert_post_data', 'one1_story_preserve_empty_title', 10, 2 );

/**
 * Whether a story has a non-empty headline.
 *
 * @param int $post_id Post ID.
 */
function one1_story_has_headline( $post_id ) {
	return trim( (string) get_post_field( 'post_title', $post_id ) ) !== '';
}

/**
 * Keep story post titles empty when the author did not provide a headline.
 *
 * @param array<string, mixed> $data    Slashed post data.
 * @param array<string, mixed> $postarr Raw post array.
 * @return array<string, mixed>
 */
function one1_story_preserve_empty_title( $data, $postarr ) {
	if ( ONE_STORY_POST_TYPE !== ( $data['post_type'] ?? '' ) ) {
		return $data;
	}
	if ( trim( wp_unslash( $data['post_title'] ?? '' ) ) === '' ) {
		$data['post_title'] = '';
	}
	return $data;
}

/**
 * Flush permalinks after theme switch (called from one-bootstrap.php).
 */
function one_story_flush_rewrites() {
	One_Story_CPT::register();
	flush_rewrite_rules( false );
}

/**
 * Meta keys list.
 *
 * @return string[]
 */
function one_story_meta_keys() {
	return array(
		'one_story_featured',
		'one_story_verified',
		'one_story_urgency',
		'one_story_is_donation',
		'one_story_fundraising_goal',
		'one_story_amount_raised',
		'one_story_donor_count',
		'one_story_end_date',
		'one_story_city',
		'one_story_state_region',
		'one_story_location_label',
		'one_story_location_place_id',
	);
}

/**
 * Urgency level options.
 *
 * @return array<string, string>
 */
function one_story_urgency_options() {
	return array(
		'standard' => __( 'Standard', 'one' ),
		'moderate' => __( 'Moderate', 'one' ),
		'urgent'   => __( 'Urgent', 'one' ),
	);
}

/**
 * Can the user create or edit stories?
 *
 * @param int $user_id User ID.
 */
function one_story_user_can_submit( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( $user_id <= 0 ) {
		return false;
	}
	$user = get_userdata( $user_id );
	if ( $user && in_array( 'administrator', (array) $user->roles, true ) ) {
		return true;
	}
	if ( function_exists( 'sin_is_staff_user' ) && sin_is_staff_user( $user_id ) ) {
		return true;
	}
	if ( function_exists( 'sin_is_pu' ) && sin_is_pu( $user_id ) ) {
		return true;
	}
	return false;
}

/**
 * Front-end story form URL.
 */
function one1_story_form_url() {
	$page_id = (int) get_option( 'one1_story_form_page_id', 0 );
	if ( $page_id ) {
		$url = get_permalink( $page_id );
		if ( $url ) {
			return $url;
		}
	}
	$page = get_page_by_path( 'share-story' );
	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}
	return home_url( '/share-story/' );
}

/**
 * Is current request the story form page?
 */
function one1_is_story_form_page() {
	if ( ! is_page() ) {
		return false;
	}
	$page_id = (int) get_option( 'one1_story_form_page_id', 0 );
	if ( $page_id && (int) get_queried_object_id() === $page_id ) {
		return true;
	}
	$post = get_queried_object();
	return $post instanceof WP_Post && 'share-story' === $post->post_name;
}
