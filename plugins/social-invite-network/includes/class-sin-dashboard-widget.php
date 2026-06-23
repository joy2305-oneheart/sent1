<?php
/**
 * Dashboard summary widget.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Dashboard_Widget
 */
class SIN_Dashboard_Widget {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register widget.
	 */
	public static function register() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_add_dashboard_widget(
			'sin_summary_widget',
			__( 'Social Invite Network', 'social-invite-network' ),
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Render widget body.
	 */
	public static function render() {
		$approved = count(
			get_users(
				array(
					'meta_key'   => 'sin_account_status',
					'meta_value' => 'approved',
					'fields'     => 'ID',
				)
			)
		);
		$pending_users = SIN_Registration::count_pending_users();
		global $wpdb;
		$inv_table = SIN_Database::invitations_table();
		$total_inv = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$inv_table}" );
		$pending_inv = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$inv_table} WHERE status IN ('pending_registration','pending_approval')"
		);

		echo '<ul>';
		echo '<li><strong>' . esc_html__( 'Approved users:', 'social-invite-network' ) . '</strong> ' . (int) $approved . '</li>';
		echo '<li><strong>' . esc_html__( 'Pending approvals:', 'social-invite-network' ) . '</strong> ' . (int) $pending_users . '</li>';
		echo '<li><strong>' . esc_html__( 'Total invitations sent:', 'social-invite-network' ) . '</strong> ' . (int) $total_inv . '</li>';
		echo '<li><strong>' . esc_html__( 'Pending invitations:', 'social-invite-network' ) . '</strong> ' . (int) $pending_inv . '</li>';
		echo '</ul>';
	}
}
