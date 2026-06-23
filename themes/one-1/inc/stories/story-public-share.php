<?php
/**
 * Temporary public story share page (token-only access).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page slug for public story view.
 */
function one1_public_story_slug() {
	return 'view';
}

/**
 * Public story page URL base.
 */
function one1_public_story_page_url() {
	return one1_page_url_by_slug( one1_public_story_slug() );
}

/**
 * Whether current request is the public story page.
 */
function one1_is_public_story_page() {
	$page = get_page_by_path( one1_public_story_slug() );
	if ( $page instanceof WP_Post && is_page( (int) $page->ID ) ) {
		return true;
	}
	return false;
}

/**
 * Create public story page on theme setup.
 */
function one1_ensure_public_story_page() {
	$page_id = (int) get_option( 'one1_public_story_page_id', 0 );
	if ( $page_id && get_post( $page_id ) ) {
		return;
	}
	$existing = get_page_by_path( one1_public_story_slug() );
	if ( $existing instanceof WP_Post ) {
		update_option( 'one1_public_story_page_id', (int) $existing->ID );
		return;
	}
	$new_id = wp_insert_post(
		array(
			'post_title'   => __( 'Shared post', 'one' ),
			'post_name'    => one1_public_story_slug(),
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		),
		true
	);
	if ( ! is_wp_error( $new_id ) ) {
		update_option( 'one1_public_story_page_id', (int) $new_id );
	}
}
add_action( 'after_setup_theme', 'one1_ensure_public_story_page', 20 );

/**
 * Resolve valid share token from request.
 *
 * @return array<string, mixed>|null
 */
function one1_get_public_story_share_row() {
	if ( ! class_exists( 'SIN_Story_Share_Links' ) ) {
		return null;
	}
	$token = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : '';
	if ( $token === '' ) {
		return null;
	}
	return SIN_Story_Share_Links::get_valid_row( $token );
}

add_filter( 'template_include', 'one1_public_story_template_include', 95 );
/**
 * Template for public story page.
 *
 * @param string $template Template path.
 */
function one1_public_story_template_include( $template ) {
	if ( one1_is_public_story_page() && ! is_admin() ) {
		$custom = get_stylesheet_directory() . '/public-post.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}

add_action( 'wp_enqueue_scripts', 'one1_public_story_enqueue_assets', 28 );
/**
 * Assets for public story page.
 */
function one1_public_story_enqueue_assets() {
	if ( ! one1_is_public_story_page() || is_admin() ) {
		return;
	}

	$row = one1_get_public_story_share_row();
	if ( ! $row ) {
		return;
	}

	$ver  = '1.0.0';
	$base = get_stylesheet_directory_uri();

	wp_enqueue_style(
		'one-share-fonts',
		'https://fonts.googleapis.com/css2?family=Libre+Caslon+Text:ital,wght@0,400;0,700;1,400&family=Manrope:wght@400;500;600;700&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'one-public-story',
		$base . '/assets/stories/one-public-story.css',
		array( 'one-share-fonts' ),
		$ver
	);

	if ( function_exists( 'one1_story_is_donation' ) && one1_story_is_donation( (int) $row['post_id'] ) ) {
		if ( function_exists( 'one1_enqueue_donation_form_assets' ) ) {
			one1_enqueue_donation_form_assets();
		}
	}
}

add_action( 'wp_enqueue_scripts', 'one1_story_share_link_assets', 29 );
/**
 * Assets for PU share-link + blast actions on member story views.
 */
function one1_story_share_link_assets() {
	if ( ! is_user_logged_in() || is_admin() ) {
		return;
	}

	$load = ( function_exists( 'one1_is_single_story_page' ) && one1_is_single_story_page() )
		|| ( function_exists( 'one1_is_share_page' ) && one1_is_share_page() )
		|| ( function_exists( 'one1_is_profile_page' ) && one1_is_profile_page() );

	if ( ! $load || ! function_exists( 'sin_is_pu' ) || ! sin_is_pu( get_current_user_id() ) ) {
		return;
	}

	$ver  = '1.5.2';
	$base = get_stylesheet_directory_uri();

	one1_enqueue_modal_assets( $ver, $base );

	wp_enqueue_script(
		'one-story-share-tools',
		$base . '/assets/stories/one-story-share-tools.js',
		array( 'one-confirm' ),
		$ver,
		true
	);

	wp_localize_script(
		'one-story-share-tools',
		'oneStoryShareTools',
		array(
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'shareNonce'       => wp_create_nonce( 'one_story_share_link' ),
			'blastNonce'       => wp_create_nonce( 'one_story_blast' ),
			'copiedLabel'      => __( 'Link copied to clipboard.', 'one' ),
			'sharedLabel'      => __( 'Shared successfully.', 'one' ),
			'shareTitle'       => __( 'Shared post', 'one' ),
			'createLabel'      => __( 'Create link', 'one' ),
			'shareLabel'       => __( 'Share', 'one' ),
			'regenerateLabel'  => __( 'Create new link', 'one' ),
			'errorGeneric'     => __( 'Something went wrong. Please try again.', 'one' ),
			'expiresLabel'     => __( 'Expires %s', 'one' ),
			'blastConfirm'     => __( 'Send an email to your friends about this post?', 'one' ),
			'blastConfirmTitle'=> __( 'Notify friends', 'one' ),
			'blastConfirmOk'   => __( 'Send', 'one' ),
			'blastSentTitle'   => __( 'Notification sent', 'one' ),
			'errorTitle'       => __( 'Could not send', 'one' ),
		)
	);
}
