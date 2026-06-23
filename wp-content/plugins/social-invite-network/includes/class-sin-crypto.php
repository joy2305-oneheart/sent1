<?php
/**
 * AES-128-ECB encryption helpers for invite codes.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Crypto
 */
class SIN_Crypto {

	/**
	 * Encrypt plaintext (typically user_login) for URL ref param.
	 *
	 * @param string $plaintext Plain text.
	 */
	public static function encrypt_username( $plaintext ) {
		$key = sin_get_encryption_key_binary();
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}
		$raw = openssl_encrypt( (string) $plaintext, 'AES-128-ECB', $key, OPENSSL_RAW_DATA );
		if ( false === $raw ) {
			return '';
		}
		return rtrim( strtr( base64_encode( $raw ), '+/', '-_' ), '=' );
	}

	/**
	 * Decrypt ref token to username.
	 *
	 * @param string $token URL-safe base64.
	 */
	public static function decrypt_username( $token ) {
		$key = sin_get_encryption_key_binary();
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		$b64 = strtr( (string) $token, '-_', '+/' );
		$pad = strlen( $b64 ) % 4;
		if ( $pad ) {
			$b64 .= str_repeat( '=', 4 - $pad );
		}
		$bin = base64_decode( $b64, true );
		if ( false === $bin ) {
			return '';
		}
		$plain = openssl_decrypt( $bin, 'AES-128-ECB', $key, OPENSSL_RAW_DATA );
		return is_string( $plain ) ? $plain : '';
	}
}
