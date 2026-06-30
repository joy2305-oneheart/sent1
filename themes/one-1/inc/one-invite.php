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
 * Friends in a PU's circle (valid users only).
 *
 * @param int $pu_id Primary user ID.
 * @return array<int, array<string, mixed>>
 */
function one1_get_circle_friends( $pu_id ) {
	$pu_id = (int) $pu_id;
	if ( $pu_id <= 0 ) {
		return array();
	}

	if ( class_exists( 'SIN_Friend_Details' ) ) {
		return SIN_Friend_Details::list_friends( $pu_id );
	}

	$friends = array();
	foreach ( one1_get_follower_ids( $pu_id ) as $friend_id ) {
		$user = get_userdata( (int) $friend_id );
		if ( ! $user ) {
			continue;
		}

		$friends[] = array(
			'id'           => (int) $friend_id,
			'display_name' => $user->display_name,
			'email'        => $user->user_email,
			'nickname'     => '',
			'notes'        => '',
			'joined'       => $user->user_registered,
			'role'         => in_array( 'friend', (array) $user->roles, true ) ? 'friend' : 'pu',
			'avatar_url'   => get_avatar_url( (int) $friend_id, array( 'size' => 96 ) ),
		);
	}

	return $friends;
}

/**
 * Render one friend row for the invite page circle list.
 *
 * @param array<string, mixed> $friend Friend payload.
 */
function one1_render_circle_friend_item( $friend ) {
	$friend_id = isset( $friend['id'] ) ? (int) $friend['id'] : 0;
	if ( $friend_id <= 0 ) {
		return;
	}

	$nickname     = isset( $friend['nickname'] ) ? (string) $friend['nickname'] : '';
	$display_name = isset( $friend['display_name'] ) ? (string) $friend['display_name'] : '';
	$email        = isset( $friend['email'] ) ? (string) $friend['email'] : '';
	$avatar_url   = isset( $friend['avatar_url'] ) ? (string) $friend['avatar_url'] : '';
	$label        = $nickname !== '' ? $nickname : $display_name;
	$subline      = $nickname !== '' ? $display_name : $email;
	$edit_label   = __( 'Edit friend details', 'one' );
	?>
	<li class="one-friends-manage__item" data-friend-id="<?php echo esc_attr( (string) $friend_id ); ?>">
		<?php if ( $avatar_url ) : ?>
			<img class="one-friends-manage__avatar" src="<?php echo esc_url( $avatar_url ); ?>" alt="" width="40" height="40" />
		<?php else : ?>
			<span class="one-friends-manage__avatar one-friends-manage__avatar--fallback" aria-hidden="true">
				<?php echo esc_html( strtoupper( substr( $label, 0, 1 ) ) ); ?>
			</span>
		<?php endif; ?>
		<div class="one-friends-manage__info">
			<p class="one-friends-manage__name"><?php echo esc_html( $label ); ?></p>
			<p class="one-friends-manage__meta"><?php echo esc_html( $subline ); ?></p>
		</div>
		<button
			type="button"
			class="one-friends-manage__menu"
			data-one-friend-edit
			data-friend-id="<?php echo esc_attr( (string) $friend_id ); ?>"
			aria-label="<?php echo esc_attr( $edit_label ); ?>"
		>
			<span class="material-symbols-outlined" aria-hidden="true">more_vert</span>
		</button>
	</li>
	<?php
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
 * Invite link lifetime in seconds (6 hours).
 */
function one1_invite_link_expiry_seconds() {
	return 6 * HOUR_IN_SECONDS;
}

/**
 * Transient key for a timed invite token.
 *
 * @param string $token Token string.
 */
function one1_invite_token_transient_key( $token ) {
	return 'sin_it_' . sanitize_key( (string) $token );
}

/**
 * Create a timed invite token for an inviter.
 *
 * @param int    $user_id Inviter user ID.
 * @param string $email   Optional invitee email to restrict signup.
 * @return string Token.
 */
function one1_create_invite_link_token( $user_id, $email = '' ) {
	$user_id = (int) $user_id;
	$token   = bin2hex( random_bytes( 16 ) );
	$expiry  = one1_invite_link_expiry_seconds();

	set_transient(
		one1_invite_token_transient_key( $token ),
		array(
			'inviter_id' => $user_id,
			'expires_at' => time() + $expiry,
			'email'      => $email !== '' ? sanitize_email( $email ) : '',
		),
		$expiry
	);

	return $token;
}

/**
 * Resolve inviter from a timed invite token.
 *
 * @param string $token Invite token.
 * @param string $email Optional email to match restricted tokens.
 * @return WP_User|null
 */
function one1_resolve_inviter_from_invite_token( $token, $email = '' ) {
	$token = sanitize_text_field( (string) $token );
	if ( $token === '' ) {
		return null;
	}

	$payload = get_transient( one1_invite_token_transient_key( $token ) );
	if ( ! is_array( $payload ) || empty( $payload['inviter_id'] ) ) {
		return null;
	}

	if ( ! empty( $payload['expires_at'] ) && (int) $payload['expires_at'] < time() ) {
		delete_transient( one1_invite_token_transient_key( $token ) );
		return null;
	}

	if ( ! empty( $payload['email'] ) && $email !== '' && strtolower( (string) $payload['email'] ) !== strtolower( sanitize_email( $email ) ) ) {
		return null;
	}

	$inviter = get_userdata( (int) $payload['inviter_id'] );
	if ( ! $inviter instanceof WP_User ) {
		return null;
	}

	if ( function_exists( 'sin_is_pu' ) && sin_is_pu( (int) $inviter->ID ) ) {
		return $inviter;
	}

	return null;
}

/**
 * Whether a timed invite token is still valid.
 *
 * @param string $token Token string.
 */
function one1_is_invite_token_valid( $token ) {
	return one1_resolve_inviter_from_invite_token( $token ) instanceof WP_User;
}

/**
 * Build a timed invite URL (registration flow).
 *
 * @param int    $user_id Inviter user ID.
 * @param string $email   Optional invitee email restriction.
 */
function one1_build_timed_invite_link( $user_id, $email = '' ) {
	$token = one1_create_invite_link_token( $user_id, $email );
	return add_query_arg(
		array(
			'invite_token' => $token,
			'register'     => '1',
		),
		one1_join_page_url()
	);
}

/**
 * Build a timed invite URL for the join landing page.
 *
 * @param int $user_id Inviter user ID.
 */
function one1_build_timed_invite_landing_link( $user_id ) {
	$token = one1_create_invite_link_token( $user_id );
	return add_query_arg( 'invite_token', $token, one1_join_page_url() );
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
function one1_apply_invite_ref_for_user( $user_id, $ref_code, $invite_token = '' ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return false;
	}

	$inviter = null;
	if ( $invite_token !== '' ) {
		$user     = get_userdata( $user_id );
		$inviter  = one1_resolve_inviter_from_invite_token( $invite_token, $user ? $user->user_email : '' );
	}
	if ( ! $inviter && $ref_code !== '' ) {
		$inviter = one1_resolve_inviter_from_ref( $ref_code );
	}
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
	$user_id = $user_id > 0 ? $user_id : get_current_user_id();
	if ( $user_id <= 0 ) {
		return '';
	}
	return one1_build_timed_invite_landing_link( $user_id );
}

/**
 * Human-readable expiry hint for invite links.
 */
function one1_invite_link_expiry_label() {
	return sprintf(
		/* translators: %d: number of hours */
		__( 'Valid for %d hours', 'one' ),
		(int) ( one1_invite_link_expiry_seconds() / HOUR_IN_SECONDS )
	);
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

/**
 * AJAX: generate a fresh timed invite link.
 */
function one1_ajax_generate_invite_link() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Please log in.', 'one' ) ), 403 );
	}

	check_ajax_referer( 'one1_send_invite', 'nonce' );

	$uid = get_current_user_id();
	if ( function_exists( 'sin_is_pu' ) && ! sin_is_pu( $uid ) && ! ( function_exists( 'sin_is_staff_user' ) && sin_is_staff_user( $uid ) ) ) {
		wp_send_json_error( array( 'message' => __( 'You are not allowed to invite others.', 'one' ) ), 403 );
	}

	wp_send_json_success(
		array(
			'link'        => one1_get_invite_link( $uid ),
			'expiresHint' => one1_invite_link_expiry_label(),
		)
	);
}
add_action( 'wp_ajax_one1_generate_invite_link', 'one1_ajax_generate_invite_link' );

add_action( 'wp_enqueue_scripts', 'one1_invite_enqueue_assets', 26 );
function one1_invite_enqueue_assets() {
	if ( ! one1_is_invite_page() || is_admin() ) {
		return;
	}

	$ver  = '1.7.6';
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
			'expiresHint' => one1_invite_link_expiry_label(),
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
				'regenerate'    => __( 'Generate new link', 'one' ),
				'regenerating'  => __( 'Generating…', 'one' ),
			),
		)
	);

	if ( is_user_logged_in() && function_exists( 'sin_is_pu' ) && sin_is_pu( get_current_user_id() ) ) {
		wp_enqueue_script(
			'one-friends-manage',
			$base . '/assets/invite/one-friends-manage.js',
			array(),
			$ver,
			true
		);

		wp_localize_script(
			'one-friends-manage',
			'oneFriendsManage',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'sin_friend_details' ),
				'i18n'    => array(
					'saved'      => __( 'Friend details saved.', 'one' ),
					'error'      => __( 'Could not save. Please try again.', 'one' ),
					'loading'    => __( 'Loading…', 'one' ),
					'roleFriend' => __( 'Friend', 'one' ),
					'rolePu'     => __( 'Primary User', 'one' ),
				),
			)
		);
	}
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
	$ref          = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
	$invite_token = isset( $_GET['invite_token'] ) ? sanitize_text_field( wp_unslash( $_GET['invite_token'] ) ) : '';
	if ( $ref !== '' || $invite_token !== '' ) {
		one1_apply_invite_ref_for_user( get_current_user_id(), $ref, $invite_token );
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

	$ver  = '1.6.4';
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
