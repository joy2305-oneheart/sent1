<?php
/**
 * Global post composer modal.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether composer assets should load.
 */
function one1_should_enqueue_composer() {
	if ( ! is_user_logged_in() || ! one_story_user_can_submit() ) {
		return false;
	}

	if ( one1_homie_is_static_front() ) {
		return true;
	}
	if ( function_exists( 'one1_is_share_page' ) && one1_is_share_page() ) {
		return true;
	}
	if ( function_exists( 'one1_is_profile_page' ) && one1_is_profile_page() ) {
		return true;
	}
	if ( function_exists( 'one1_is_story_form_page' ) && one1_is_story_form_page() ) {
		return true;
	}
	if ( function_exists( 'one1_is_auth_page' ) && one1_is_auth_page() ) {
		return true;
	}
	if ( function_exists( 'one1_is_invite_page' ) && one1_is_invite_page() ) {
		return true;
	}
	if ( is_singular( 'story' ) ) {
		return true;
	}

	return false;
}

/**
 * Enqueue composer assets.
 */
function one1_enqueue_composer_assets() {
	if ( ! one1_should_enqueue_composer() ) {
		return;
	}

	$ver  = '1.7.3';
	$base = get_stylesheet_directory_uri();

	wp_enqueue_style(
		'one-share-material-icons',
		'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'one-composer',
		$base . '/assets/composer/one-composer.css',
		array(),
		$ver
	);

	$places_key   = one1_get_places_api_key();
	$composer_deps = array();
	if ( $places_key !== '' ) {
		wp_enqueue_script(
			'google-maps-places',
			'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode( $places_key ) . '&libraries=places',
			array(),
			null,
			true
		);
		wp_script_add_data( 'google-maps-places', 'async', true );
		$composer_deps[] = 'google-maps-places';
	}

	wp_enqueue_script(
		'one-composer',
		$base . '/assets/composer/one-composer.js',
		$composer_deps,
		$ver,
		true
	);

	wp_localize_script(
		'one-composer',
		'oneComposerConfig',
		array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'one_story_form' ),
			'editNonce' => wp_create_nonce( 'one_story_edit' ),
			'placesKey' => $places_key,
			'i18n'      => array(
				'publishing'  => __( 'Publishing…', 'one' ),
				'publish'     => __( 'Publish', 'one' ),
				'saving'      => __( 'Saving…', 'one' ),
				'saveChanges' => __( 'Save changes', 'one' ),
				'createTitle' => __( 'Create post', 'one' ),
				'editTitle'   => __( 'Edit post', 'one' ),
				'error'       => __( 'Something went wrong. Please try again.', 'one' ),
			),
		)
	);

	if ( function_exists( 'one1_enqueue_button_assets' ) ) {
		one1_enqueue_button_assets();
	}
}
add_action( 'wp_enqueue_scripts', 'one1_enqueue_composer_assets', 30 );

/**
 * Render composer modal in footer.
 */
function one1_render_composer_modal() {
	if ( ! one1_should_enqueue_composer() ) {
		return;
	}
	require get_stylesheet_directory() . '/inc/composer/composer-markup.php';
}
add_action( 'wp_footer', 'one1_render_composer_modal', 20 );
