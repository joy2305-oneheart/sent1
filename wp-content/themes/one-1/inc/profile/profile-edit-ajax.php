<?php
/**
 * Frontend profile edit AJAX handler.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current user may edit their profile.
 */
function one1_can_edit_profile() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	$user_id = get_current_user_id();
	if ( function_exists( 'sin_is_pu' ) ) {
		return sin_is_pu( $user_id );
	}
	return true;
}

/**
 * Handle profile bio + avatar update.
 */
function one1_ajax_update_profile() {
	if ( ! one1_can_edit_profile() ) {
		wp_send_json_error( array( 'message' => __( 'You cannot edit your profile yet.', 'one' ) ), 403 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'one1_update_profile' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'one' ) ), 403 );
	}

	$user_id = get_current_user_id();
	$bio     = isset( $_POST['bio'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bio'] ) ) : '';

	if ( mb_strlen( $bio ) > ONE1_PROFILE_BIO_MAX_LENGTH ) {
		wp_send_json_error(
			array(
				'message' => sprintf(
					/* translators: %d: max characters */
					__( 'Bio must be %d characters or fewer.', 'one' ),
					ONE1_PROFILE_BIO_MAX_LENGTH
				),
			),
			400
		);
	}

	update_user_meta( $user_id, ONE1_PROFILE_BIO_META, $bio );

	if ( ! empty( $_FILES['avatar']['name'] ) ) {
		$result = one1_handle_profile_avatar_upload( $user_id, $_FILES['avatar'] );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
	}

	$avatar_url = one1_get_avatar_url( $user_id, 176 ) ?: get_avatar_url( $user_id, array( 'size' => 176 ) );
	if ( $avatar_url ) {
		$avatar_url = add_query_arg( 'v', (string) time(), $avatar_url );
	}

	wp_send_json_success(
		array(
			'bio'        => one1_get_profile_bio( $user_id ),
			'avatar_url' => $avatar_url,
		)
	);
}
add_action( 'wp_ajax_one1_update_profile', 'one1_ajax_update_profile' );

/**
 * Upload and attach a new profile avatar.
 *
 * @param int                  $user_id User ID.
 * @param array<string, mixed> $file    Uploaded file array.
 * @return int|WP_Error Attachment ID or error.
 */
function one1_handle_profile_avatar_upload( $user_id, array $file ) {
	if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
		return new WP_Error( 'invalid_file', __( 'Invalid upload.', 'one' ) );
	}

	$max_bytes = 2 * MB_IN_BYTES;
	if ( ! empty( $file['size'] ) && (int) $file['size'] > $max_bytes ) {
		return new WP_Error( 'file_too_large', __( 'Image must be 2 MB or smaller.', 'one' ) );
	}

	$allowed = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
	);

	$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );
	if ( empty( $checked['type'] ) || ! in_array( $checked['type'], $allowed, true ) ) {
		return new WP_Error( 'invalid_type', __( 'Please upload a JPEG, PNG, or WebP image.', 'one' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$upload = wp_handle_upload(
		$file,
		array(
			'test_form' => false,
			'mimes'     => $allowed,
		)
	);

	if ( isset( $upload['error'] ) ) {
		return new WP_Error( 'upload_error', $upload['error'] );
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $upload['type'],
			'post_title'     => sanitize_file_name( wp_basename( $upload['file'] ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$upload['file']
	);

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return new WP_Error( 'attachment_error', __( 'Could not save image.', 'one' ) );
	}

	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );

	$old_id = one1_get_avatar_attachment_id( $user_id );
	update_user_meta( $user_id, ONE1_AVATAR_ATTACHMENT_META, (int) $attachment_id );

	if ( $old_id > 0 && $old_id !== (int) $attachment_id ) {
		wp_delete_attachment( $old_id, true );
	}

	return (int) $attachment_id;
}

/**
 * Profile edit config for JS.
 *
 * @return array<string, mixed>
 */
function one1_profile_edit_config() {
	return array(
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'nonce'     => wp_create_nonce( 'one1_update_profile' ),
		'maxBioLen' => ONE1_PROFILE_BIO_MAX_LENGTH,
		'i18n'      => array(
			'save'       => __( 'Save profile', 'one' ),
			'cancel'     => __( 'Cancel', 'one' ),
			'edit'       => __( 'Edit profile', 'one' ),
			'error'      => __( 'Something went wrong. Please try again.', 'one' ),
			'success'    => __( 'Profile updated.', 'one' ),
			'bioLabel'   => __( 'Status', 'one' ),
			'avatarLabel'=> __( 'Profile photo', 'one' ),
			'defaultBio' => __( 'Your journey, shared with your circle.', 'one' ),
		),
	);
}
