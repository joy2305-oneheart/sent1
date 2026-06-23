<?php
/**
 * Shared modal assets and footer markup.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether shared modals should load on this request.
 */
function one1_should_enqueue_modals() {
	if ( is_admin() || ! is_user_logged_in() ) {
		return false;
	}

	return ( function_exists( 'one1_is_profile_page' ) && one1_is_profile_page() )
		|| ( function_exists( 'one1_is_share_page' ) && one1_is_share_page() )
		|| ( function_exists( 'one1_is_single_story_page' ) && one1_is_single_story_page() )
		|| ( function_exists( 'one1_is_invite_page' ) && one1_is_invite_page() );
}

/**
 * Enqueue confirm + share-link modal assets.
 *
 * @param string $ver  Asset version.
 * @param string $base Theme URI.
 */
function one1_enqueue_modal_assets( $ver = '1.7.1', $base = '' ) {
	if ( ! one1_should_enqueue_modals() ) {
		return;
	}

	if ( $base === '' ) {
		$base = get_stylesheet_directory_uri();
	}

	wp_enqueue_style(
		'one-modals',
		$base . '/assets/share/one-modals.css',
		array(),
		$ver
	);

	wp_enqueue_script(
		'one-confirm',
		$base . '/assets/share/one-confirm.js',
		array(),
		$ver,
		true
	);
}

add_action( 'wp_footer', 'one1_render_shared_modals', 19 );
/**
 * Render shared modal shells.
 */
function one1_render_shared_modals() {
	if ( ! one1_should_enqueue_modals() ) {
		return;
	}
	require get_stylesheet_directory() . '/inc/share/one-modals.php';
}
