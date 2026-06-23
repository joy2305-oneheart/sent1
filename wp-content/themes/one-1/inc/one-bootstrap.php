<?php
/**
 * One-time theme page bootstrap (avoids heavy work every request).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONE1_PAGES_BOOTSTRAP_VERSION', '6' );

/**
 * Create required theme pages once.
 */
function one1_bootstrap_theme_pages() {
	if ( get_option( 'one1_pages_bootstrapped', '' ) === ONE1_PAGES_BOOTSTRAP_VERSION ) {
		return;
	}

	static $running = false;
	if ( $running ) {
		return;
	}
	$running = true;

	if ( function_exists( 'one1_maybe_create_auth_pages' ) ) {
		one1_maybe_create_auth_pages();
	}

	if ( function_exists( 'one1_ensure_share_page' ) ) {
		one1_ensure_share_page();
	}

	if ( class_exists( 'One_Story_Frontend' ) ) {
		One_Story_Frontend::ensure_form_page();
	}

	if ( function_exists( 'one1_ensure_profile_page' ) ) {
		one1_ensure_profile_page();
	}

	if ( function_exists( 'one1_ensure_invite_page' ) ) {
		one1_ensure_invite_page();
	}

	if ( function_exists( 'one1_ensure_join_page' ) ) {
		one1_ensure_join_page();
	}

	if ( function_exists( 'one1_retire_signup_page' ) ) {
		one1_retire_signup_page();
	}

	update_option( 'one1_pages_bootstrapped', ONE1_PAGES_BOOTSTRAP_VERSION, false );

	$running = false;
}

add_action( 'after_setup_theme', 'one1_bootstrap_theme_pages', 20 );

add_action(
	'after_switch_theme',
	static function () {
		delete_option( 'one1_pages_bootstrapped' );
		one1_bootstrap_theme_pages();
		if ( function_exists( 'one_story_flush_rewrites' ) ) {
			one_story_flush_rewrites();
		}
	}
);
