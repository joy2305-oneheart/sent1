<?php
/**
 * Core helpers for Social Invite Network.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin settings with defaults.
 *
 * @return array<string, mixed>
 */
function sin_get_settings() {
	$defaults = array(
		'invite_email_subject'        => __( 'You are invited to join {site_name}', 'social-invite-network' ),
		'invite_email_body'           => __( "Hello,\n\n{inviter_name} has invited you to join {site_name}.\n\nUse this link to register:\n{invite_link}\n\n", 'social-invite-network' ),
		'circle_invite_email_subject' => __( '{inviter_name} invited you to join their circle on {site_name}', 'social-invite-network' ),
		'circle_invite_email_body'    => __( "Hello,\n\n{inviter_name} wants you to join their circle on {site_name}.\n\nSign in and accept the invitation:\n{accept_link}\n\n", 'social-invite-network' ),
		'admin_notify_new_user' => true,
		'max_invites_per_day'   => 10,
		'blast_email_subject'   => __( '{author_name} shared an update on {site_name}', 'social-invite-network' ),
		'blast_email_body'      => __( "Hello,\n\n{author_name} shared a new update on {site_name}.\n\n{post_title}\n{post_excerpt}\n\nTo read, comment, or support this post, sign in here:\n{login_link}\n\nYou are receiving this because you follow {author_name}'s journey.\n", 'social-invite-network' ),
		'secret_key'            => '',
		'homepage_id'           => 0,
		'register_page_id'      => 0,
	);
	$stored = get_option( 'sin_settings', array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	return array_merge( $defaults, $stored );
}

/**
 * Binary secret key for AES-128 (16 bytes).
 */
function sin_get_encryption_key_binary() {
	if ( defined( 'SIN_SECRET_KEY' ) && is_string( SIN_SECRET_KEY ) && SIN_SECRET_KEY !== '' ) {
		return substr( hash( 'sha256', SIN_SECRET_KEY, true ), 0, 16 );
	}
	$settings = sin_get_settings();
	$key       = isset( $settings['secret_key'] ) ? (string) $settings['secret_key'] : '';
	if ( $key === '' ) {
		// Fallback: site-specific but not ideal for production.
		return substr( hash( 'sha256', wp_salt( 'auth' ) . 'sin', true ), 0, 16 );
	}
	return substr( hash( 'sha256', $key, true ), 0, 16 );
}

/**
 * Whether user is administrator or editor (full site access).
 *
 * @param int $user_id User ID.
 */
function sin_is_staff_user( $user_id ) {
	$user = get_userdata( (int) $user_id );
	if ( ! $user || empty( $user->roles ) ) {
		return false;
	}
	return in_array( 'administrator', $user->roles, true ) || in_array( 'editor', $user->roles, true );
}

/**
 * Whether user has the Primary User role.
 *
 * @param int $user_id User ID.
 */
function sin_is_pu( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return false;
	}
	if ( sin_is_staff_user( $user_id ) ) {
		return true;
	}
	$user = get_userdata( $user_id );
	return $user && in_array( SIN_Roles::ROLE_PU, (array) $user->roles, true );
}

/**
 * Whether user has the Friend role.
 *
 * @param int $user_id User ID.
 */
function sin_is_friend( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return false;
	}
	$user = get_userdata( $user_id );
	return $user && in_array( SIN_Roles::ROLE_FRIEND, (array) $user->roles, true );
}

/**
 * Whether user is a network member (pu, friend, or staff).
 *
 * @param int $user_id User ID.
 */
function sin_is_member( $user_id ) {
	return sin_is_pu( $user_id ) || sin_is_friend( $user_id );
}

/**
 * Approved network member (pu or friend, not rejected) or staff.
 *
 * @param int $user_id User ID.
 */
function sin_is_network_approved( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return false;
	}
	if ( sin_is_staff_user( $user_id ) ) {
		return true;
	}
	if ( 'rejected' === sin_get_account_status( $user_id ) ) {
		return false;
	}
	if ( ! sin_is_member( $user_id ) ) {
		// Legacy: approved meta without role yet (pending migration).
		return get_user_meta( $user_id, 'sin_account_status', true ) === 'approved';
	}
	return get_user_meta( $user_id, 'sin_account_status', true ) === 'approved';
}

/**
 * Account status meta.
 *
 * @param int $user_id User ID.
 */
function sin_get_account_status( $user_id ) {
	$status = get_user_meta( (int) $user_id, 'sin_account_status', true );
	return is_string( $status ) && $status !== '' ? $status : '';
}

/**
 * Public homepage: designated page or front page / posts home.
 */
function sin_is_public_home_request() {
	if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	$settings    = sin_get_settings();
	$page_id     = isset( $settings['homepage_id'] ) ? (int) $settings['homepage_id'] : 0;
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

	if ( $page_id > 0 ) {
		if ( is_page( $page_id ) ) {
			return true;
		}
		return false;
	}

	if ( is_front_page() || ( is_home() && ! is_paged() ) ) {
		return true;
	}

	// Root URL with pretty permalinks sometimes maps to front.
	if ( $request_uri !== '' && ( '/' === $request_uri || '' === trim( $request_uri, '/' ) ) && ! is_admin() ) {
		return is_front_page() || is_home();
	}

	return false;
}

/**
 * Registration page (guests must reach it via invite link).
 */
function sin_is_public_register_page() {
	if ( function_exists( 'one1_is_join_page' ) && one1_is_join_page() && ( ! empty( $_GET['register'] ) || ! empty( $_GET['pu_token'] ) ) ) {
		return true;
	}

	$settings = sin_get_settings();
	$page_id  = isset( $settings['register_page_id'] ) ? (int) $settings['register_page_id'] : 0;
	if ( $page_id > 0 ) {
		return is_page( $page_id );
	}

	return false;
}

/**
 * Themed login page (public for guests).
 */
function sin_is_public_login_page() {
	foreach ( array( 'login' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post && is_page( (int) $page->ID ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Forgot / reset password pages (public for guests).
 */
function sin_is_public_password_page() {
	foreach ( array( 'forgot-password', 'reset-password' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post && is_page( (int) $page->ID ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Public join landing page (invite links).
 */
function sin_is_public_join_page() {
	if ( function_exists( 'one1_is_join_page' ) && one1_is_join_page() ) {
		return true;
	}
	foreach ( array( 'join' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post && is_page( (int) $page->ID ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Public temporary story share page.
 */
function sin_is_public_story_share_page() {
	if ( function_exists( 'one1_is_public_story_page' ) && one1_is_public_story_page() ) {
		return true;
	}
	foreach ( array( 'view' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post && is_page( (int) $page->ID ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Any URL that is intentionally public (login, register, password recovery, join, or public story).
 */
function sin_is_public_shell_request() {
	return sin_is_public_register_page() || sin_is_public_login_page() || sin_is_public_password_page() || sin_is_public_join_page() || sin_is_public_story_share_page();
}

/**
 * User IDs who follow this member (joined through their invite).
 *
 * @param int $user_id User ID.
 * @return int[]
 */
function sin_get_follower_ids( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return array();
	}

	global $wpdb;
	$table = SIN_Database::relationships_table();
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT invitee_id FROM {$table} WHERE inviter_id = %d", $user_id ) );

	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );

	$disconnected = get_user_meta( $user_id, SIN_Invitations::META_DISCONNECTED, true );
	if ( is_array( $disconnected ) && ! empty( $disconnected ) ) {
		$blocked = array_map( 'intval', $disconnected );
		$ids     = array_values( array_diff( $ids, $blocked ) );
	}

	return $ids;
}

/**
 * User IDs this member follows (people who invited them).
 *
 * @param int $user_id User ID.
 * @return int[]
 */
function sin_get_following_ids( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return array();
	}

	global $wpdb;
	$table = SIN_Database::relationships_table();
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT inviter_id FROM {$table} WHERE invitee_id = %d", $user_id ) );

	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );

	// Heal missing graph rows when meta still points at an inviter.
	$inviter_id = (int) get_user_meta( $user_id, 'sin_invited_by', true );
	if ( $inviter_id > 0 && ! SIN_Invitations::is_disconnected_from( $user_id, $inviter_id ) ) {
		$ids[] = $inviter_id;
	}

	$disconnected = get_user_meta( $user_id, SIN_Invitations::META_DISCONNECTED, true );
	if ( is_array( $disconnected ) && ! empty( $disconnected ) ) {
		$blocked = array_map( 'intval', $disconnected );
		$ids     = array_values( array_diff( $ids, $blocked ) );
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Follower count.
 *
 * @param int $user_id User ID.
 */
function sin_count_followers( $user_id ) {
	return count( sin_get_follower_ids( $user_id ) );
}

/**
 * Following count.
 *
 * @param int $user_id User ID.
 */
function sin_count_following( $user_id ) {
	return count( sin_get_following_ids( $user_id ) );
}

/**
 * Persist follower/following counts on the user for admin list columns.
 *
 * @param int $user_id User ID.
 */
function sin_sync_connection_meta( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return;
	}
	update_user_meta( $user_id, 'sin_followers_count', sin_count_followers( $user_id ) );
	update_user_meta( $user_id, 'sin_following_count', sin_count_following( $user_id ) );
}

/**
 * Allowed author IDs for Share feed: self + people the viewer follows only.
 *
 * One-directional: viewer sees posts from users who invited them (following),
 * not from users they invited unless those users also invited the viewer back.
 *
 * @param int $viewer_id User ID.
 * @return int[]
 */
function sin_get_share_feed_author_ids( $viewer_id ) {
	$viewer_id = (int) $viewer_id;
	if ( $viewer_id <= 0 || ! sin_is_network_approved( $viewer_id ) ) {
		return array();
	}

	if ( sin_is_friend( $viewer_id ) ) {
		return sin_get_following_ids( $viewer_id );
	}

	$ids = array( $viewer_id );
	$ids = array_merge( $ids, sin_get_following_ids( $viewer_id ) );

	return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
}

/**
 * Broader visibility (no chain): self + following + followers + direct invitees.
 *
 * @param int $viewer_id User ID.
 * @return int[]
 */
function sin_get_allowed_author_ids( $viewer_id ) {
	$viewer_id = (int) $viewer_id;
	if ( $viewer_id <= 0 || ! sin_is_network_approved( $viewer_id ) ) {
		return array();
	}

	$ids = array( $viewer_id );
	$ids = array_merge( $ids, sin_get_following_ids( $viewer_id ) );
	$ids = array_merge( $ids, sin_get_follower_ids( $viewer_id ) );

	// Invitees linked only via sin_invited_by (relationship row not created yet).
	$invitees = get_users(
		array(
			'meta_key'   => 'sin_invited_by',
			'meta_value' => (string) $viewer_id,
			'fields'     => 'ID',
			'number'     => 500,
		)
	);
	if ( ! empty( $invitees ) ) {
		foreach ( $invitees as $invitee_id ) {
			$ids[] = (int) $invitee_id;
		}
	}

	return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
}

/**
 * Enqueue minimal frontend styles when shortcodes or restricted page render.
 */
function sin_enqueue_frontend_styles() {
	wp_register_style(
		'sin-frontend',
		SIN_PLUGIN_URL . 'assets/css/sin-frontend.css',
		array(),
		SIN_VERSION
	);
	wp_enqueue_style( 'sin-frontend' );
}
