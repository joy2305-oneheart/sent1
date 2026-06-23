<?php
/**
 * Settings page under Settings menu.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Settings
 */
class SIN_Settings {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Add settings page.
	 */
	public static function menu() {
		add_options_page(
			__( 'Social Invite Network', 'social-invite-network' ),
			__( 'Social Invite Network', 'social-invite-network' ),
			'manage_options',
			'sin-settings',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register() {
		register_setting(
			'sin_settings_group',
			'sin_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	/**
	 * Sanitize settings array.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$old     = sin_get_settings();
		$clean   = $old;
		$input   = is_array( $input ) ? $input : array();
		if ( isset( $input['invite_email_subject'] ) ) {
			$clean['invite_email_subject'] = sanitize_text_field( (string) $input['invite_email_subject'] );
		}
		if ( isset( $input['invite_email_body'] ) ) {
			$clean['invite_email_body'] = sanitize_textarea_field( (string) $input['invite_email_body'] );
		}
		if ( isset( $input['circle_invite_email_subject'] ) ) {
			$clean['circle_invite_email_subject'] = sanitize_text_field( (string) $input['circle_invite_email_subject'] );
		}
		if ( isset( $input['circle_invite_email_body'] ) ) {
			$clean['circle_invite_email_body'] = sanitize_textarea_field( (string) $input['circle_invite_email_body'] );
		}
		$clean['admin_notify_new_user'] = ! empty( $input['admin_notify_new_user'] );
		if ( isset( $input['max_invites_per_day'] ) ) {
			$clean['max_invites_per_day'] = max( 1, (int) $input['max_invites_per_day'] );
		}
		if ( isset( $input['secret_key'] ) ) {
			$sk = sanitize_text_field( (string) $input['secret_key'] );
			if ( $sk !== '' ) {
				$clean['secret_key'] = $sk;
			}
		}
		if ( isset( $input['homepage_id'] ) ) {
			$clean['homepage_id'] = max( 0, (int) $input['homepage_id'] );
		}
		if ( isset( $input['register_page_id'] ) ) {
			$clean['register_page_id'] = max( 0, (int) $input['register_page_id'] );
		}
		return $clean;
	}

	/**
	 * Render settings form.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = sin_get_settings();
		echo '<div class="wrap"><h1>' . esc_html__( 'Social Invite Network', 'social-invite-network' ) . '</h1>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'sin_settings_group' );
		echo '<h2>' . esc_html__( 'Invite email', 'social-invite-network' ) . '</h2>';
		echo '<p><label>' . esc_html__( 'Subject', 'social-invite-network' ) . '<br /><input type="text" class="large-text" name="sin_settings[invite_email_subject]" value="' . esc_attr( $s['invite_email_subject'] ) . '" /></label></p>';
		echo '<p><label>' . esc_html__( 'Body', 'social-invite-network' ) . '<br /><textarea class="large-text" rows="6" name="sin_settings[invite_email_body]">' . esc_textarea( $s['invite_email_body'] ) . '</textarea></label></p>';
		echo '<p class="description">' . esc_html__( 'Placeholders: {invite_link}, {site_name}, {inviter_name}', 'social-invite-network' ) . '</p>';

		echo '<h2>' . esc_html__( 'Join circle email (existing accounts)', 'social-invite-network' ) . '</h2>';
		echo '<p><label>' . esc_html__( 'Subject', 'social-invite-network' ) . '<br /><input type="text" class="large-text" name="sin_settings[circle_invite_email_subject]" value="' . esc_attr( $s['circle_invite_email_subject'] ?? '' ) . '" /></label></p>';
		echo '<p><label>' . esc_html__( 'Body', 'social-invite-network' ) . '<br /><textarea class="large-text" rows="6" name="sin_settings[circle_invite_email_body]">' . esc_textarea( $s['circle_invite_email_body'] ?? '' ) . '</textarea></label></p>';
		echo '<p class="description">' . esc_html__( 'Placeholders: {accept_link}, {site_name}, {inviter_name}', 'social-invite-network' ) . '</p>';

		echo '<h2>' . esc_html__( 'Notifications', 'social-invite-network' ) . '</h2>';
		echo '<p><label><input type="checkbox" name="sin_settings[admin_notify_new_user]" value="1" ' . checked( ! empty( $s['admin_notify_new_user'] ), true, false ) . ' /> ';
		echo esc_html__( 'Email administrators when a new user registers (pending approval).', 'social-invite-network' ) . '</label></p>';

		echo '<h2>' . esc_html__( 'Invitations', 'social-invite-network' ) . '</h2>';
		echo '<p><label>' . esc_html__( 'Max invites per user per day', 'social-invite-network' ) . '<br /><input type="number" min="1" name="sin_settings[max_invites_per_day]" value="' . esc_attr( (string) (int) $s['max_invites_per_day'] ) . '" /></label></p>';

		echo '<h2>' . esc_html__( 'Encryption', 'social-invite-network' ) . '</h2>';
		echo '<p><label>' . esc_html__( 'Secret key (optional if SIN_SECRET_KEY is set in wp-config.php)', 'social-invite-network' ) . '<br /><input type="password" autocomplete="new-password" class="large-text" name="sin_settings[secret_key]" value="' . esc_attr( $s['secret_key'] ) . '" /></label></p>';
		echo '<p class="description">' . esc_html__( 'Define SIN_SECRET_KEY in wp-config.php for stronger separation from the database. Keys are hashed to 16 bytes for AES-128-ECB.', 'social-invite-network' ) . '</p>';

		echo '<h2>' . esc_html__( 'Public pages', 'social-invite-network' ) . '</h2>';
		echo '<p><label>' . esc_html__( 'Homepage page ID (0 = use Reading Settings front page / blog home)', 'social-invite-network' ) . '<br /><input type="number" min="0" name="sin_settings[homepage_id]" value="' . esc_attr( (string) (int) $s['homepage_id'] ) . '" /></label></p>';
		echo '<p><label>' . esc_html__( 'Registration page ID (0 = look for a page with slug “register”)', 'social-invite-network' ) . '<br /><input type="number" min="0" name="sin_settings[register_page_id]" value="' . esc_attr( (string) (int) $s['register_page_id'] ) . '" /></label></p>';

		echo '<h2>' . esc_html__( 'Coming soon', 'social-invite-network' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'One-to-One Private Messaging: planned for a future release. The sin_relationships table already models directional connections suitable for messaging between connected users only.', 'social-invite-network' ) . '</p>';

		submit_button();
		echo '</form></div>';
	}
}
