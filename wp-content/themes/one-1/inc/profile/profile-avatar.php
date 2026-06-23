<?php
/**
 * Custom profile avatar via user meta attachment.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONE1_PROFILE_BIO_META', 'one1_profile_bio' );
define( 'ONE1_AVATAR_ATTACHMENT_META', 'one1_avatar_attachment_id' );
define( 'ONE1_PROFILE_BIO_MAX_LENGTH', 160 );

/**
 * Get stored profile bio for a user.
 *
 * @param int $user_id User ID.
 */
function one1_get_profile_bio( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return '';
	}
	return sanitize_textarea_field( (string) get_user_meta( $user_id, ONE1_PROFILE_BIO_META, true ) );
}

/**
 * Get custom avatar attachment ID.
 *
 * @param int $user_id User ID.
 */
function one1_get_avatar_attachment_id( $user_id ) {
	return max( 0, (int) get_user_meta( (int) $user_id, ONE1_AVATAR_ATTACHMENT_META, true ) );
}

/**
 * Get custom avatar URL or empty string.
 *
 * @param int $user_id User ID.
 * @param int $size    Image size.
 */
function one1_get_avatar_url( $user_id, $size = 96 ) {
	$attachment_id = one1_get_avatar_attachment_id( $user_id );
	if ( $attachment_id <= 0 ) {
		return '';
	}
	$url = wp_get_attachment_image_url( $attachment_id, array( $size, $size ) );
	return is_string( $url ) ? $url : '';
}

/**
 * Filter avatar to use custom uploaded image when set.
 *
 * @param array<string, mixed> $args        Avatar data.
 * @param mixed                $id_or_email User ID, email, or object.
 * @return array<string, mixed>
 */
function one1_filter_avatar_data( $args, $id_or_email ) {
	$user_id = 0;
	if ( is_numeric( $id_or_email ) ) {
		$user_id = (int) $id_or_email;
	} elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) ) {
		$user_id = (int) $id_or_email->user_id;
	} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
		if ( $user ) {
			$user_id = (int) $user->ID;
		}
	}

	if ( $user_id <= 0 ) {
		return $args;
	}

	$size = isset( $args['size'] ) ? (int) $args['size'] : 96;
	$url  = one1_get_avatar_url( $user_id, $size );
	if ( $url === '' ) {
		return $args;
	}

	$args['url']           = $url;
	$args['found_avatar']  = true;
	$args['force_default'] = false;

	return $args;
}
add_filter( 'pre_get_avatar_data', 'one1_filter_avatar_data', 10, 2 );
