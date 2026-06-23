<?php
/**
 * one (one-1) child theme — Sent One landing on front page.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/inc/one-auth.php';
require_once get_stylesheet_directory() . '/inc/one-button.php';
require_once get_stylesheet_directory() . '/inc/homie-cta-button.php';
require_once get_stylesheet_directory() . '/inc/one-user-menu.php';
require_once get_stylesheet_directory() . '/inc/one-connections.php';
require_once get_stylesheet_directory() . '/inc/one-invite.php';
require_once get_stylesheet_directory() . '/inc/one-story-support.php';
require_once get_stylesheet_directory() . '/inc/stories/one-stories.php';
require_once get_stylesheet_directory() . '/inc/stories/story-detail-ajax.php';
require_once get_stylesheet_directory() . '/inc/share/share-story-card.php';
require_once get_stylesheet_directory() . '/inc/profile/profile-post-cell.php';
require_once get_stylesheet_directory() . '/inc/profile/profile-avatar.php';
require_once get_stylesheet_directory() . '/inc/profile/profile-edit-ajax.php';
require_once get_stylesheet_directory() . '/inc/one-composer.php';
require_once get_stylesheet_directory() . '/inc/share/one-modals-assets.php';
require_once get_stylesheet_directory() . '/inc/one-pwa.php';
require_once get_stylesheet_directory() . '/inc/one-bootstrap.php';

add_filter( 'show_admin_bar', '__return_false' );
/**
 * Sent One layout is intended for a static front page (Settings → Reading).
 */
function one1_homie_is_static_front() {
	return 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) && is_front_page();
}

add_action( 'wp_enqueue_scripts', 'one_enqueue_styles', 5 );
function one_enqueue_styles() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css', array(), wp_get_theme( get_template() )->get( 'Version' ) );
}

add_action( 'wp_enqueue_scripts', 'one1_homie_enqueue_front_assets', 20 );
function one1_homie_enqueue_front_assets() {
	if ( ! one1_homie_is_static_front() || is_admin() ) {
		return;
	}

	$ver  = '1.0.3';
	$base = get_stylesheet_directory_uri() . '/assets/homie';

	wp_enqueue_style(
		'one-homie-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'one-homie-home',
		$base . '/homie-homepage.css',
		array( 'one-homie-fonts' ),
		$ver
	);

	wp_enqueue_script(
		'one-homie-home',
		$base . '/homie-homepage.js',
		array(),
		$ver,
		true
	);

	one1_enqueue_button_assets();
	one1_enqueue_user_menu_assets();
}

add_filter( 'template_include', 'one1_homie_template_include', 99 );
function one1_homie_template_include( $template ) {
	if ( one1_homie_is_static_front() && ! is_admin() ) {
		$custom = get_stylesheet_directory() . '/homie-home.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}

add_filter( 'body_class', 'one1_homie_body_class' );
function one1_homie_body_class( $classes ) {
	if ( one1_homie_is_static_front() && ! is_admin() ) {
		$classes[] = 'homie-landing-body';
	}
	if ( one1_is_share_page() && ! is_admin() ) {
		$classes[] = 'sent-share-body';
		$classes[] = 'sent-app-body';
	}
	if ( function_exists( 'one1_is_story_form_page' ) && one1_is_story_form_page() && ! is_admin() ) {
		$classes[] = 'sent-share-body';
	}
	if ( function_exists( 'one1_is_profile_page' ) && one1_is_profile_page() && ! is_admin() ) {
		$classes[] = 'sent-share-body';
		$classes[] = 'sent-app-body';
		$classes[] = 'one-profile-body';
	}
	if ( function_exists( 'one1_is_invite_page' ) && one1_is_invite_page() && ! is_admin() ) {
		$classes[] = 'sent-share-body';
		$classes[] = 'sent-app-body';
	}
	if ( function_exists( 'one1_is_single_story_page' ) && one1_is_single_story_page() && ! is_admin() ) {
		$classes[] = 'sent-share-body';
		$classes[] = 'sent-app-body';
		$classes[] = 'one-story-single-body';
	}
	return $classes;
}

/**
 * Share feed page ID (auto-created on theme load).
 */
function one1_share_page_id() {
	return (int) get_option( 'one1_share_page_id', 0 );
}

/**
 * Public URL for the member sharing feed.
 */
function one1_share_page_url() {
	$page_id = one1_share_page_id();
	if ( $page_id ) {
		$url = get_permalink( $page_id );
		if ( $url ) {
			return $url;
		}
	}
	$page = get_page_by_path( 'share' );
	if ( $page ) {
		return get_permalink( $page );
	}
	return home_url( '/share/' );
}

/**
 * Whether the current request is the sharing feed page.
 */
function one1_is_share_page() {
	if ( ! is_page() ) {
		return false;
	}
	$page_id = one1_share_page_id();
	if ( $page_id && (int) get_queried_object_id() === $page_id ) {
		return true;
	}
	$post = get_queried_object();
	return $post instanceof WP_Post && 'share' === $post->post_name;
}

/**
 * Create the Share page if missing.
 */
function one1_ensure_share_page() {
	$page_id = one1_share_page_id();
	if ( $page_id && get_post( $page_id ) ) {
		return;
	}
	$existing = get_page_by_path( 'share' );
	if ( $existing instanceof WP_Post ) {
		update_option( 'one1_share_page_id', (int) $existing->ID );
		return;
	}
	$new_id = wp_insert_post(
		array(
			'post_title'   => __( 'Share', 'one' ),
			'post_name'    => 'share',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		),
		true
	);
	if ( ! is_wp_error( $new_id ) ) {
		update_option( 'one1_share_page_id', (int) $new_id );
	}
}

/**
 * Require login to view the sharing feed.
 */
function one1_share_page_access() {
	if ( ! one1_is_share_page() || is_admin() ) {
		return;
	}
	if ( is_user_logged_in() ) {
		return;
	}
	wp_safe_redirect( one1_login_url( one1_share_page_url() ) );
	exit;
}
add_action( 'template_redirect', 'one1_share_page_access', 5 );

/**
 * Feed query for approved members (Social Invite Network).
 *
 * @param int $paged     Page number.
 * @param int $per_page  Posts per page.
 * @return WP_Query
 */
function one1_share_feed_query( $paged = 1, $per_page = 10 ) {
	$uid = get_current_user_id();
	$args = array(
		'post_type'      => 'story',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => max( 1, (int) $paged ),
		'post__in'       => array( 0 ),
	);

	if ( $uid > 0 && function_exists( 'sin_is_network_approved' ) && sin_is_network_approved( $uid ) && function_exists( 'sin_get_share_feed_author_ids' ) ) {
		$allowed = sin_get_share_feed_author_ids( $uid );
		if ( ! empty( $allowed ) ) {
			unset( $args['post__in'] );
			$args['author__in'] = $allowed;
		}
	}

	$args['meta_query'] = array( one1_story_exclude_member_archived_meta_query() );

	return new WP_Query( $args );
}

/**
 * Profile page ID.
 */
function one1_profile_page_id() {
	return (int) get_option( 'one1_profile_page_id', 0 );
}

/**
 * Profile page URL.
 */
function one1_profile_page_url() {
	$page_id = one1_profile_page_id();
	if ( $page_id ) {
		$url = get_permalink( $page_id );
		if ( $url ) {
			return $url;
		}
	}
	$page = get_page_by_path( 'profile' );
	if ( $page ) {
		return get_permalink( $page );
	}
	return home_url( '/profile/' );
}

/**
 * Whether current request is the profile page.
 */
function one1_is_profile_page() {
	if ( ! is_page() ) {
		return false;
	}
	$page_id = one1_profile_page_id();
	if ( $page_id && (int) get_queried_object_id() === $page_id ) {
		return true;
	}
	$post = get_queried_object();
	return $post instanceof WP_Post && 'profile' === $post->post_name;
}

/**
 * Create profile page if missing.
 */
function one1_ensure_profile_page() {
	$page_id = one1_profile_page_id();
	if ( $page_id && get_post( $page_id ) ) {
		return;
	}
	$existing = get_page_by_path( 'profile' );
	if ( $existing instanceof WP_Post ) {
		update_option( 'one1_profile_page_id', (int) $existing->ID );
		return;
	}
	$new_id = wp_insert_post(
		array(
			'post_title'   => __( 'Profile', 'one' ),
			'post_name'    => 'profile',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		),
		true
	);
	if ( ! is_wp_error( $new_id ) ) {
		update_option( 'one1_profile_page_id', (int) $new_id );
	}
}

/**
 * Require login on profile page.
 */
function one1_profile_page_access() {
	if ( ! one1_is_profile_page() || is_admin() ) {
		return;
	}
	if ( is_user_logged_in() ) {
		return;
	}
	wp_safe_redirect( one1_login_url( one1_profile_page_url() ) );
	exit;
}
add_action( 'template_redirect', 'one1_profile_page_access', 5 );

/**
 * Stories by a single author.
 *
 * @param int $user_id   Author ID.
 * @param int $paged     Page.
 * @param int $per_page  Per page.
 * @return WP_Query
 */
function one1_user_stories_query( $user_id, $paged = 1, $per_page = 12 ) {
	return new WP_Query(
		array(
			'post_type'      => 'story',
			'post_status'    => 'publish',
			'author'         => (int) $user_id,
			'posts_per_page' => (int) $per_page,
			'paged'          => max( 1, (int) $paged ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array( one1_story_exclude_member_archived_meta_query() ),
		)
	);
}

/**
 * Plain-text preview for a story post.
 *
 * @param int $post_id Post ID.
 */
function one1_share_post_preview( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return '';
	}
	$content = $post->post_content;
	if ( function_exists( 'do_blocks' ) ) {
		$content = do_blocks( $content );
	}
	$content = strip_shortcodes( $content );
	$content = wp_strip_all_tags( $content, true );
	return wp_trim_words( $content, 55, '…' );
}

add_action( 'wp_enqueue_scripts', 'one1_share_enqueue_assets', 25 );
function one1_share_enqueue_assets() {
	if ( ! one1_is_share_page() || is_admin() ) {
		return;
	}

	$ver  = '1.7.6';
	$base = get_stylesheet_directory_uri() . '/assets/sharing';

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
		$base . '/sharing-feed.css',
		array( 'one-share-fonts' ),
		$ver
	);

	one1_enqueue_button_assets();
	one1_enqueue_user_menu_assets();
	one1_enqueue_connections_assets();

	wp_enqueue_script(
		'one-share-feed',
		$base . '/one-share-feed.js',
		array(),
		$ver,
		true
	);

	wp_localize_script(
		'one-share-feed',
		'oneShareFeed',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'one_story_support' ),
			'i18n'    => array(
				'error'     => __( 'Something went wrong. Please try again.', 'one' ),
				'upiCopied' => __( 'UPI ID copied to clipboard.', 'one' ),
			),
		)
	);
}

/**
 * Enqueue connections drawer assets (share + profile).
 */
function one1_enqueue_connections_assets() {
	$ver  = '1.1.0';
	$base = get_stylesheet_directory_uri() . '/assets/profile';

	wp_enqueue_style(
		'one-connections',
		$base . '/one-connections.css',
		array(),
		$ver
	);

	wp_enqueue_script(
		'one-connections',
		$base . '/one-connections.js',
		array( 'sin-connections-actions' ),
		$ver,
		true
	);
}

add_action( 'wp_enqueue_scripts', 'one1_profile_enqueue_assets', 26 );
function one1_profile_enqueue_assets() {
	if ( ! one1_is_profile_page() || is_admin() ) {
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
		'one-profile',
		$base . '/assets/profile/one-profile.css',
		array( 'one-share-feed', 'one-connections', 'one-single-story' ),
		$ver
	);

	one1_enqueue_button_assets( array( 'one-share-feed' ) );
	one1_enqueue_user_menu_assets();
	one1_enqueue_connections_assets();

	one1_enqueue_story_view_scripts( $ver, $base );

	wp_enqueue_script(
		'one-share-feed',
		$base . '/assets/sharing/one-share-feed.js',
		array(),
		$ver,
		true
	);

	wp_localize_script(
		'one-share-feed',
		'oneShareFeed',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'one_story_support' ),
			'i18n'    => array(
				'error'     => __( 'Something went wrong. Please try again.', 'one' ),
				'upiCopied' => __( 'UPI ID copied to clipboard.', 'one' ),
			),
		)
	);

	wp_enqueue_script(
		'one-profile',
		$base . '/assets/profile/one-profile.js',
		array( 'one-connections', 'one-share-feed', 'one-story-view', 'one-donation-form' ),
		$ver,
		true
	);

	wp_localize_script(
		'one-profile',
		'oneProfileConfig',
		one1_profile_modal_config()
	);

	wp_localize_script(
		'one-profile',
		'oneProfileEdit',
		one1_profile_edit_config()
	);
}

add_filter( 'template_include', 'one1_share_template_include', 98 );
function one1_share_template_include( $template ) {
	if ( one1_is_share_page() && ! is_admin() ) {
		$custom = get_stylesheet_directory() . '/sharing-feed.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}

add_filter( 'template_include', 'one1_profile_template_include', 96 );
function one1_profile_template_include( $template ) {
	if ( one1_is_profile_page() && ! is_admin() ) {
		$custom = get_stylesheet_directory() . '/profile.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}

/**
 * Whether the current request is a single story permalink.
 */
function one1_is_single_story_page() {
	return is_singular( 'story' );
}

/**
 * Require login to view single stories.
 */
function one1_single_story_page_access() {
	if ( ! one1_is_single_story_page() || is_admin() ) {
		return;
	}
	if ( is_user_logged_in() ) {
		return;
	}
	wp_safe_redirect( one1_login_url( get_permalink() ) );
	exit;
}
add_action( 'template_redirect', 'one1_single_story_page_access', 5 );

add_action( 'wp_enqueue_scripts', 'one1_single_story_enqueue_assets', 25 );
function one1_single_story_enqueue_assets() {
	if ( ! one1_is_single_story_page() || is_admin() ) {
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

	one1_enqueue_button_assets( array( 'one-share-feed' ) );
	one1_enqueue_user_menu_assets();
	one1_enqueue_connections_assets();

	one1_enqueue_story_view_scripts( $ver, $base );

	wp_enqueue_script(
		'one-share-feed',
		$base . '/assets/sharing/one-share-feed.js',
		array(),
		$ver,
		true
	);

	wp_localize_script(
		'one-share-feed',
		'oneShareFeed',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'one_story_support' ),
			'i18n'    => array(
				'error'     => __( 'Something went wrong. Please try again.', 'one' ),
				'upiCopied' => __( 'UPI ID copied to clipboard.', 'one' ),
			),
		)
	);
}

/**
 * Story view JS (comments + engage) for single and profile modal.
 *
 * @param string $ver  Asset version.
 * @param string $base Theme URI.
 */
function one1_enqueue_story_view_scripts( $ver, $base ) {
	one1_enqueue_modal_assets( $ver, $base );

	wp_enqueue_script(
		'one-story-view',
		$base . '/assets/sharing/one-story-view.js',
		array( 'one-composer', 'one-confirm' ),
		$ver,
		true
	);

	wp_localize_script(
		'one-story-view',
		'oneStoryView',
		array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'commentNonce' => wp_create_nonce( 'one_story_comment' ),
			'removeNonce'  => wp_create_nonce( 'one_story_remove' ),
			'profileUrl'   => function_exists( 'one1_profile_page_url' ) ? one1_profile_page_url() : home_url( '/profile/' ),
			'i18n'         => array(
				'error'            => __( 'Something went wrong. Please try again.', 'one' ),
				'archiveTitle'     => __( 'Archive post', 'one' ),
				'deleteTitle'      => __( 'Delete post', 'one' ),
				'archiveConfirm'   => __( 'Archive this post? It will be hidden from your profile and the feed, but admins can still see it.', 'one' ),
				'deleteConfirm'    => __( 'Delete this post? It will be removed from the site for members. Admins can restore it from Stories → Trash.', 'one' ),
				'archiveOk'        => __( 'Archive', 'one' ),
				'deleteOk'         => __( 'Delete', 'one' ),
				'archiveSuccess'   => __( 'Post archived.', 'one' ),
				'deleteSuccess'    => __( 'Post deleted.', 'one' ),
			),
		)
	);
}

add_filter( 'template_include', 'one1_single_story_template_include', 97 );
function one1_single_story_template_include( $template ) {
	if ( one1_is_single_story_page() && ! is_admin() ) {
		$custom = get_stylesheet_directory() . '/single-story.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
