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
 * Public story page ID.
 */
function one1_public_story_page_id() {
	return (int) get_option( 'one1_public_story_page_id', 0 );
}

/**
 * Whether current request is the public story page.
 */
function one1_is_public_story_page() {
	if ( is_page() ) {
		$page_id = one1_public_story_page_id();
		if ( $page_id && (int) get_queried_object_id() === $page_id ) {
			return true;
		}

		$post = get_queried_object();
		if ( $post instanceof WP_Post && one1_public_story_slug() === $post->post_name ) {
			return true;
		}
	}

	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	if ( is_string( $uri ) && preg_match( '#/' . preg_quote( one1_public_story_slug(), '#' ) . '/?(\?|$)#i', $uri ) ) {
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
		one1_assign_public_story_page_template( $page_id );
		return;
	}
	$existing = get_page_by_path( one1_public_story_slug() );
	if ( $existing instanceof WP_Post ) {
		update_option( 'one1_public_story_page_id', (int) $existing->ID );
		one1_assign_public_story_page_template( (int) $existing->ID );
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
		one1_assign_public_story_page_template( (int) $new_id );
	}
}
add_action( 'after_setup_theme', 'one1_ensure_public_story_page', 20 );

/**
 * Assign the custom PHP template to the public view page.
 *
 * @param int $page_id Page ID.
 */
function one1_assign_public_story_page_template( $page_id ) {
	$page_id = (int) $page_id;
	if ( $page_id <= 0 ) {
		return;
	}
	update_post_meta( $page_id, '_wp_page_template', 'public-post.php' );
}

/**
 * Ensure the view page always uses the custom shell template.
 */
function one1_sync_public_story_page_template() {
	$page_id = one1_public_story_page_id();
	if ( ! $page_id ) {
		$existing = get_page_by_path( one1_public_story_slug() );
		if ( $existing instanceof WP_Post ) {
			$page_id = (int) $existing->ID;
			update_option( 'one1_public_story_page_id', $page_id );
		}
	}
	if ( $page_id > 0 ) {
		one1_assign_public_story_page_template( $page_id );
	}
}
add_action( 'after_setup_theme', 'one1_sync_public_story_page_template', 21 );

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

add_action( 'template_redirect', 'one1_public_story_render_shell', 1 );
/**
 * Render the public share page as a standalone app shell (no block theme wrapper).
 */
function one1_public_story_render_shell() {
	if ( ! one1_is_public_story_page() || is_admin() ) {
		return;
	}

	status_header( 200 );
	nocache_headers();

	require get_stylesheet_directory() . '/public-post.php';
	exit;
}

add_filter( 'body_class', 'one1_public_story_body_class' );
/**
 * Body classes for public story share page.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function one1_public_story_body_class( $classes ) {
	if ( one1_is_public_story_page() && ! is_admin() ) {
		$classes[] = 'sent-share-body';
		$classes[] = 'one-public-story-body';
		$classes[] = 'sent-app-body';
	}
	return $classes;
}

add_action( 'wp_enqueue_scripts', 'one1_public_story_dequeue_parent_assets', 100 );
/**
 * Strip block parent theme chrome styles on the public share shell.
 */
function one1_public_story_dequeue_parent_assets() {
	if ( ! one1_is_public_story_page() || is_admin() ) {
		return;
	}

	wp_dequeue_style( 'parent-style' );
	wp_dequeue_style( 'twentytwentyfive-style' );
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}

add_action( 'wp_enqueue_scripts', 'one1_public_story_enqueue_assets', 28 );
/**
 * Assets for public story page.
 */
function one1_public_story_enqueue_assets() {
	if ( ! one1_is_public_story_page() || is_admin() ) {
		return;
	}

	$ver  = '1.7.6';
	$base = get_stylesheet_directory_uri();

	wp_enqueue_style(
		'one-share-fonts',
		'https://fonts.googleapis.com/css2?family=Libre+Caslon+Text:ital,wght@0,400;0,700;1,400&family=Manrope:wght@400;500;600;700&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'one-share-material-icons',
		'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'one-share-feed',
		$base . '/assets/sharing/sharing-feed.css',
		array( 'one-share-fonts' ),
		$ver
	);

	wp_enqueue_style(
		'one-single-story',
		$base . '/assets/sharing/one-single-story.css',
		array( 'one-share-feed' ),
		$ver
	);

	wp_enqueue_style(
		'one-public-story',
		$base . '/assets/stories/one-public-story.css',
		array( 'one-share-feed', 'one-single-story' ),
		$ver
	);

	if ( function_exists( 'one1_enqueue_button_assets' ) ) {
		one1_enqueue_button_assets();
	}

	if ( function_exists( 'one1_enqueue_user_menu_assets' ) ) {
		one1_enqueue_user_menu_assets();
	}

	wp_enqueue_script( 'jquery' );

	$row = one1_get_public_story_share_row();
	if ( $row && function_exists( 'one1_story_is_donation' ) && one1_story_is_donation( (int) $row['post_id'] ) ) {
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
