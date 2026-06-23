<?php
/**
 * Front-end invite page and AJAX handlers.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Invite page ID.
 */
function one1_invite_page_id() {
	return (int) get_option( 'one1_invite_page_id', 0 );
}

/**
 * Invite page URL.
 */
function one1_invite_page_url() {
	$page_id = one1_invite_page_id();
	if ( $page_id ) {
		$url = get_permalink( $page_id );
		if ( $url ) {
			return $url;
		}
	}
	$page = get_page_by_path( 'invite' );
	if ( $page ) {
		return get_permalink( $page );
	}
	return home_url( '/invite/' );
}

/**
 * Whether current request is the invite page.
 */
function one1_is_invite_page() {
	if ( ! is_page() ) {
		return false;
	}
	$page_id = one1_invite_page_id();
	if ( $page_id && (int) get_queried_object_id() === $page_id ) {
		return true;
	}
	$post = get_queried_object();
	return $post instanceof WP_Post && 'invite' === $post->post_name;
}

/**
 * Create invite page if missing.
 */
function one1_ensure_invite_page() {
	$page_id = one1_invite_page_id();
	if ( $page_id && get_post( $page_id ) ) {
		return;
	}
	$existing = get_page_by_path( 'invite' );
	if ( $existing instanceof WP_Post ) {
		update_option( 'one1_invite_page_id', (int) $existing->ID );
		return;
	}
	$new_id = wp_insert_post(
		array(
			'post_title'   => __( 'Invite', 'one' ),
			'post_name'    => 'invite',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		),
		true
	);
	if ( ! is_wp_error( $new_id ) ) {
		update_option( 'one1_invite_page_id', (int) $new_id );
	}
}

/**
 * Join landing page ID.
 */
function one1_join_page_id() {
	return (int) get_option( 'one1_join_page_id', 0 );
}

/**
 * Public join landing page URL.
 */
function one1_join_page_url() {
	$page_id = one1_join_page_id();
	if ( $page_id ) {
		$url = get_permalink( $page_id );
		if ( $url ) {
			return $url;
		}
	}
	$page = get_page_by_path( 'join' );
	if ( $page ) {
		return get_permalink( $page );
	}
	return home_url( '/join/' );
}

/**
 * Whether current request is the join page.
 */
function one1_is_join_page() {
	if ( ! is_page() ) {
		return false;
	}
	$page_id = one1_join_page_id();
	if ( $page_id && (int) get_queried_object_id() === $page_id ) {
		return true;
	}
	$post = get_queried_object();
	return $post instanceof WP_Post && 'join' === $post->post_name;
}

/**
 * Create join page if missing.
 */
function one1_ensure_join_page() {
	$page_id = one1_join_page_id();
	if ( $page_id && get_post( $page_id ) ) {
		return;
	}
	$existing = get_page_by_path( 'join' );
	if ( $existing instanceof WP_Post ) {
		update_option( 'one1_join_page_id', (int) $existing->ID );
		return;
	}
	$new_id = wp_insert_post(
		array(
			'post_title'   => __( 'Join', 'one' ),
			'post_name'    => 'join',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		),
		true
	);
	if ( ! is_wp_error( $new_id ) ) {
		update_option( 'one1_join_page_id', (int) $new_id );
	}
}

/**
 * Resolve inviter user from invite ref code.
 *
 * @param string $ref_code Invite ref query value.
 * @return WP_User|null
 */
function one1_resolve_inviter_from_ref( $ref_code ) {
	if ( ! class_exists( 'SIN_Crypto' ) ) {
		return null;
	}
	$ref_code = sanitize_text_field( (string) $ref_code );
	if ( $ref_code === '' ) {
		return null;
	}
	$login = SIN_Crypto::decrypt_username( $ref_code );
	if ( $login === '' ) {
		return null;
	}
	$inviter = get_user_by( 'login', $login );
	if ( ! $inviter instanceof WP_User ) {
		return null;
	}
	if ( function_exists( 'sin_is_pu' ) && sin_is_pu( (int) $inviter->ID ) ) {
		return $inviter;
	}
	return null;
}

/**
 * Link invitee to inviter from a personal invite ref.
 *
 * @param int    $user_id  Invitee user ID.
 * @param string $ref_code Invite ref code.
 * @return bool Whether inviter was recorded or a connection was created.
 */
function one1_apply_invite_ref_for_user( $user_id, $ref_code ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return false;
	}
	$inviter = one1_resolve_inviter_from_ref( $ref_code );
	if ( ! $inviter ) {
		return false;
	}
	$inviter_id = (int) $inviter->ID;
	if ( $inviter_id === $user_id ) {
		return false;
	}

	if ( class_exists( 'SIN_Invitations' ) && SIN_Invitations::users_are_connected( $inviter_id, $user_id ) ) {
		return false;
	}

	$existing = (int) get_user_meta( $user_id, 'sin_invited_by', true );
	if ( $existing <= 0 ) {
		update_user_meta( $user_id, 'sin_invited_by', $inviter_id );
	}

	if (
		class_exists( 'SIN_Invitations' )
		&& function_exists( 'sin_is_network_approved' )
		&& sin_is_network_approved( $user_id )
		&& sin_is_network_approved( $inviter_id )
	) {
		SIN_Invitations::add_relationship( $inviter_id, $user_id );
		if ( class_exists( 'SIN_Registration' ) ) {
			SIN_Registration::sync_relationship_for_user( $user_id );
		}
	}

	return true;
}

/**
 * Extract ref code from a SIN register invite URL.
 *
 * @param string $url Invite URL.
 * @return string
 */
function one1_extract_invite_ref_from_url( $url ) {
	if ( preg_match( '/[?&]ref=([^&]+)/', (string) $url, $matches ) ) {
		return sanitize_text_field( rawurldecode( $matches[1] ) );
	}
	return '';
}

/**
 * Personal invite link for a user (public join landing).
 *
 * @param int $user_id User ID.
 */
function one1_get_invite_link( $user_id = 0 ) {
	if ( ! class_exists( 'SIN_Invitations' ) ) {
		return '';
	}
	$user_id = $user_id > 0 ? $user_id : get_current_user_id();
	$ref     = one1_extract_invite_ref_from_url( SIN_Invitations::build_invite_link( $user_id ) );
	if ( $ref === '' ) {
		return '';
	}
	return add_query_arg( 'ref', rawurlencode( $ref ), one1_join_page_url() );
}

/**
 * Require login on invite page.
 */
function one1_invite_page_access() {
	if ( ! one1_is_invite_page() || is_admin() ) {
		return;
	}
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( one1_login_url( one1_invite_page_url() ) );
		exit;
	}
	if ( function_exists( 'sin_is_pu' ) && ! sin_is_pu( get_current_user_id() ) && ! ( function_exists( 'sin_is_staff_user' ) && sin_is_staff_user( get_current_user_id() ) ) ) {
		wp_safe_redirect( one1_share_page_url() );
		exit;
	}
}
add_action( 'template_redirect', 'one1_invite_page_access', 5 );

/**
 * AJAX: send invitation email.
 */
function one1_ajax_send_invite() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Please log in.', 'one' ) ), 403 );
	}

	check_ajax_referer( 'one1_send_invite', 'nonce' );

	$uid   = get_current_user_id();
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( ! class_exists( 'SIN_Invitations' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invitations are not available.', 'one' ) ), 500 );
	}

	$result = SIN_Invitations::submit_invite_for_user( $uid, $email );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
	}

	wp_send_json_success( array( 'message' => $result ) );
}
add_action( 'wp_ajax_one1_send_invite', 'one1_ajax_send_invite' );

/**
 * AJAX: resend a pending invitation.
 */
function one1_ajax_resend_invite() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Please log in.', 'one' ) ), 403 );
	}

	check_ajax_referer( 'one1_manage_invite', 'nonce' );

	if ( ! class_exists( 'SIN_Invitations' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invitations are not available.', 'one' ) ), 500 );
	}

	$invitation_id = isset( $_POST['invitation_id'] ) ? (int) $_POST['invitation_id'] : 0;
	$result        = SIN_Invitations::resend_for_user( $invitation_id, get_current_user_id() );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
	}

	wp_send_json_success( array( 'message' => __( 'Invitation resent.', 'one' ) ) );
}
add_action( 'wp_ajax_one1_resend_invite', 'one1_ajax_resend_invite' );

/**
 * AJAX: remove a pending invitation.
 */
function one1_ajax_remove_invite() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Please log in.', 'one' ) ), 403 );
	}

	check_ajax_referer( 'one1_manage_invite', 'nonce' );

	if ( ! class_exists( 'SIN_Invitations' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invitations are not available.', 'one' ) ), 500 );
	}

	$invitation_id = isset( $_POST['invitation_id'] ) ? (int) $_POST['invitation_id'] : 0;
	$result        = SIN_Invitations::cancel_invitation( $invitation_id, get_current_user_id() );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
	}

	wp_send_json_success( array( 'message' => __( 'Invitation removed.', 'one' ) ) );
}
add_action( 'wp_ajax_one1_remove_invite', 'one1_ajax_remove_invite' );

add_action( 'wp_enqueue_scripts', 'one1_invite_enqueue_assets', 26 );
function one1_invite_enqueue_assets() {
	if ( ! one1_is_invite_page() || is_admin() ) {
		return;
	}

	$ver  = '1.5.0';
	$base = get_stylesheet_directory_uri();

	one1_enqueue_modal_assets( $ver, $base );

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
		'one-invite',
		$base . '/assets/invite/one-invite.css',
		array( 'one-share-feed' ),
		$ver
	);

	one1_enqueue_button_assets( array( 'one-share-feed' ) );
	one1_enqueue_user_menu_assets();
	one1_enqueue_connections_assets();

	wp_enqueue_script(
		'one-invite',
		$base . '/assets/invite/one-invite.js',
		array( 'one-confirm' ),
		$ver,
		true
	);

	wp_localize_script(
		'one-invite',
		'oneInviteConfig',
		array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'one1_send_invite' ),
			'sentNonce'  => wp_create_nonce( 'one1_manage_invite' ),
			'inviteLink' => one1_get_invite_link(),
			'i18n'       => array(
				'sending'       => __( 'Sending…', 'one' ),
				'send'          => __( 'Send invitation', 'one' ),
				'copied'        => __( 'Link copied', 'one' ),
				'copyFail'      => __( 'Could not copy link.', 'one' ),
				'error'         => __( 'Something went wrong. Please try again.', 'one' ),
				'resent'        => __( 'Invitation resent.', 'one' ),
				'removeTitle'   => __( 'Remove invitation', 'one' ),
				'removeConfirm' => __( 'Remove this invitation? They will no longer be able to accept it.', 'one' ),
				'removeOk'      => __( 'Remove', 'one' ),
			),
		)
	);
}

add_filter( 'template_include', 'one1_invite_template_include', 96 );
function one1_invite_template_include( $template ) {
	if ( one1_is_invite_page() && ! is_admin() ) {
		$custom = get_stylesheet_directory() . '/invite.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}

/**
 * Join page: connect logged-in visitors, allow public access.
 */
function one1_join_page_access() {
	if ( ! one1_is_join_page() || is_admin() ) {
		return;
	}
	if ( ! is_user_logged_in() ) {
		return;
	}
	$ref = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
	if ( $ref !== '' ) {
		one1_apply_invite_ref_for_user( get_current_user_id(), $ref );
	}
	$redirect = function_exists( 'one1_share_page_url' ) ? one1_share_page_url() : home_url( '/' );
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'template_redirect', 'one1_join_page_access', 5 );

add_action( 'wp_enqueue_scripts', 'one1_join_enqueue_assets', 26 );
/**
 * Assets for public join landing.
 */
function one1_join_enqueue_assets() {
	if ( ! one1_is_join_page() || is_admin() ) {
		return;
	}

	$ver  = '1.6.2';
	$base = get_stylesheet_directory_uri();

	wp_enqueue_style(
		'one-homie-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'one-share-material-icons',
		'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
		array(),
		null
	);

	$homie = get_stylesheet_directory_uri() . '/assets/homie/homie-homepage.css';
	wp_enqueue_style( 'one-homie-base', $homie, array(), $ver );
	wp_enqueue_style( 'one-homie-auth', get_stylesheet_directory_uri() . '/assets/homie/homie-auth.css', array( 'one-homie-base' ), $ver );
	wp_enqueue_style( 'one-join', $base . '/assets/invite/one-join.css', array( 'one-homie-auth' ), $ver );

	if ( function_exists( 'one1_enqueue_button_assets' ) ) {
		one1_enqueue_button_assets();
	}
}

add_filter( 'template_include', 'one1_join_template_include', 95 );
/**
 * Join page template.
 *
 * @param string $template Template path.
 */
function one1_join_template_include( $template ) {
	if ( one1_is_join_page() && ! is_admin() ) {
		$custom = get_stylesheet_directory() . '/join.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
