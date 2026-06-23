<?php
/**
 * Connection disconnect and report AJAX handlers.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Connections
 */
class SIN_Connections {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_sin_disconnect_user', array( __CLASS__, 'ajax_disconnect_user' ) );
		add_action( 'wp_ajax_sin_report_user', array( __CLASS__, 'ajax_report_user' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue connection action scripts where theme loads connections UI.
	 */
	public static function enqueue_scripts() {
		if ( ! is_user_logged_in() || is_admin() ) {
			return;
		}

		wp_enqueue_script( 'sin-connections-actions' );
		wp_localize_script(
			'sin-connections-actions',
			'sinConnections',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'sin_connections' ),
				'i18n'    => array(
					'disconnectConfirm' => __( 'Disconnect from this member? You will no longer see each other in your network.', 'social-invite-network' ),
					'disconnectSuccess' => __( 'Disconnected.', 'social-invite-network' ),
					'reportSuccess'     => __( 'Report submitted. Thank you.', 'social-invite-network' ),
					'error'             => __( 'Something went wrong. Please try again.', 'social-invite-network' ),
					'reportReason'      => __( 'Reason for report', 'social-invite-network' ),
					'reportDetails'     => __( 'Additional details (optional)', 'social-invite-network' ),
					'submitReport'      => __( 'Submit report', 'social-invite-network' ),
					'cancel'            => __( 'Cancel', 'social-invite-network' ),
					'disconnect'        => __( 'Disconnect', 'social-invite-network' ),
					'report'            => __( 'Report', 'social-invite-network' ),
				),
				'reasons' => SIN_Reports::reason_options(),
			)
		);
	}

	/**
	 * Register script handle for theme dependency.
	 */
	public static function register_scripts() {
		wp_register_script(
			'sin-connections-actions',
			SIN_PLUGIN_URL . 'assets/js/sin-connections.js',
			array(),
			SIN_VERSION,
			true
		);
	}

	/**
	 * Verify AJAX request basics.
	 *
	 * @return int|WP_Error Current user ID or error.
	 */
	private static function verify_ajax_request() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'auth', __( 'Please log in.', 'social-invite-network' ), 401 );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sin_connections' ) ) {
			return new WP_Error( 'nonce', __( 'Security check failed.', 'social-invite-network' ), 403 );
		}

		$user_id = get_current_user_id();
		if ( ! sin_is_network_approved( $user_id ) ) {
			return new WP_Error( 'forbidden', __( 'Your account is not approved yet.', 'social-invite-network' ), 403 );
		}

		return $user_id;
	}

	/**
	 * AJAX: disconnect from another user (both directions).
	 */
	public static function ajax_disconnect_user() {
		$user_id = self::verify_ajax_request();
		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'message' => $user_id->get_error_message() ), (int) $user_id->get_error_data() ?: 400 );
		}

		$target_id = isset( $_POST['target_id'] ) ? (int) $_POST['target_id'] : 0;
		if ( $target_id <= 0 || $target_id === $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid member.', 'social-invite-network' ) ), 400 );
		}

		if ( ! SIN_Invitations::users_are_connected( $user_id, $target_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not connected to this member.', 'social-invite-network' ) ), 400 );
		}

		SIN_Invitations::disconnect_users( $user_id, $target_id );

		wp_send_json_success(
			array(
				'followers' => sin_count_followers( $user_id ),
				'following' => sin_count_following( $user_id ),
			)
		);
	}

	/**
	 * AJAX: report a user.
	 */
	public static function ajax_report_user() {
		$user_id = self::verify_ajax_request();
		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'message' => $user_id->get_error_message() ), (int) $user_id->get_error_data() ?: 400 );
		}

		$target_id = isset( $_POST['target_id'] ) ? (int) $_POST['target_id'] : 0;
		$reason    = isset( $_POST['reason'] ) ? sanitize_key( wp_unslash( $_POST['reason'] ) ) : '';
		$details   = isset( $_POST['details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['details'] ) ) : '';

		if ( $target_id <= 0 || $target_id === $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid member.', 'social-invite-network' ) ), 400 );
		}

		$result = SIN_Reports::create_report( $user_id, $target_id, $reason, $details );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'report_id' => (int) $result ) );
	}
}

add_action( 'init', array( 'SIN_Connections', 'register_scripts' ), 5 );
