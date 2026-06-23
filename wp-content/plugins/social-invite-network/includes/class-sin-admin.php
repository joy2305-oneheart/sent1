<?php
/**
 * Admin menus and invitation management.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Admin
 */
class SIN_Admin {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menus' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_invitation_admin' ) );
	}

	/**
	 * Register admin pages.
	 */
	public static function menus() {
		add_users_page(
			__( 'Invite Primary User', 'social-invite-network' ),
			__( 'Invite Primary User', 'social-invite-network' ),
			'manage_options',
			'sin-invite-pu',
			array( 'SIN_Admin_PU_Invites', 'render_admin_page' )
		);
		add_users_page(
			__( 'Pending Approvals', 'social-invite-network' ),
			__( 'Pending Approvals', 'social-invite-network' ),
			'manage_options',
			'sin-pending-approvals',
			array( __CLASS__, 'render_pending' )
		);
		add_users_page(
			__( 'Manage Invitations', 'social-invite-network' ),
			__( 'Manage Invitations', 'social-invite-network' ),
			'manage_options',
			'sin-manage-invitations',
			array( __CLASS__, 'render_invitations' )
		);
	}

	/**
	 * Handle resend / manual create.
	 */
	public static function handle_invitation_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_GET['page'] ) || 'sin-manage-invitations' !== $_GET['page'] ) {
			return;
		}
		if ( isset( $_GET['sin_resend'] ) && isset( $_GET['_wpnonce'] ) ) {
			$id = (int) $_GET['sin_resend'];
			if ( $id > 0 && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sin_resend_' . $id ) ) {
				SIN_Invitations::resend_email( $id );
			}
			wp_safe_redirect( admin_url( 'users.php?page=sin-manage-invitations&resent=1' ) );
			exit;
		}

		if ( isset( $_POST['sin_manual_invite'], $_POST['sin_manual_invite_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sin_manual_invite_nonce'] ) ), 'sin_manual_invite' ) ) {
			$email      = isset( $_POST['sin_manual_email'] ) ? sanitize_email( wp_unslash( $_POST['sin_manual_email'] ) ) : '';
			$inviter_id = isset( $_POST['sin_manual_inviter'] ) ? (int) $_POST['sin_manual_inviter'] : 0;
			if ( ! is_email( $email ) || $inviter_id <= 0 ) {
				wp_safe_redirect( admin_url( 'users.php?page=sin-manage-invitations&sin_err=1' ) );
				exit;
			}
			if ( SIN_Invitations::has_open_invitation_for_email( $email ) ) {
				wp_safe_redirect( admin_url( 'users.php?page=sin-manage-invitations&sin_dup=1' ) );
				exit;
			}
			$existing = get_user_by( 'email', $email );
			if ( $existing ) {
				$invitee_id = (int) $existing->ID;
				if ( SIN_Invitations::users_are_connected( $inviter_id, $invitee_id ) ) {
					wp_safe_redirect( admin_url( 'users.php?page=sin-manage-invitations&sin_connected=1' ) );
					exit;
				}
				$id = SIN_Invitations::insert_row( $inviter_id, $email, 'pending_approval', '' );
				if ( $id ) {
					SIN_Invitations::push_inbox( $invitee_id, $id, $inviter_id );
					$accept_link = SIN_Invitations::build_accept_invite_link( $id );
					SIN_Invitations::send_circle_invite_email( $email, $inviter_id, $accept_link );
				}
			} else {
				$code = get_user_meta( $inviter_id, 'sin_invite_code', true );
				if ( ! is_string( $code ) || $code === '' ) {
					$u = get_userdata( $inviter_id );
					if ( $u ) {
						$code = SIN_Crypto::encrypt_username( $u->user_login );
						if ( $code !== '' ) {
							update_user_meta( $inviter_id, 'sin_invite_code', $code );
						}
					}
				}
				$id = SIN_Invitations::insert_row( $inviter_id, $email, 'pending_registration', is_string( $code ) ? $code : '' );
				if ( $id ) {
					$link = SIN_Invitations::build_invite_link( $inviter_id );
					SIN_Invitations::send_invite_email( $email, $inviter_id, $link );
				}
			}
			wp_safe_redirect( admin_url( 'users.php?page=sin-manage-invitations&created=1' ) );
			exit;
		}
	}

	/**
	 * Pending users screen.
	 */
	public static function render_pending() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'social-invite-network' ) );
		}
		$users = get_users(
			array(
				'meta_key'   => 'sin_account_status',
				'meta_value' => 'pending',
				'orderby'    => 'registered',
				'order'      => 'ASC',
			)
		);
		echo '<div class="wrap"><h1>' . esc_html__( 'Legacy Pending Approvals', 'social-invite-network' ) . '</h1>';
		echo '<p>' . esc_html__( 'These users registered before the role system was introduced. Approving assigns them the Primary User role.', 'social-invite-network' ) . '</p>';
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="updated notice"><p>' . esc_html__( 'Updated.', 'social-invite-network' ) . '</p></div>';
		}
		if ( empty( $users ) ) {
			echo '<p>' . esc_html__( 'No pending users.', 'social-invite-network' ) . '</p></div>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Name', 'social-invite-network' ) . '</th>';
		echo '<th>' . esc_html__( 'Email', 'social-invite-network' ) . '</th>';
		echo '<th>' . esc_html__( 'Registered', 'social-invite-network' ) . '</th>';
		echo '<th>' . esc_html__( 'Invited by', 'social-invite-network' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'social-invite-network' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $users as $u ) {
			$inv   = (int) get_user_meta( $u->ID, 'sin_invited_by', true );
			$inv_u = $inv ? get_userdata( $inv ) : false;
			echo '<tr>';
			echo '<td>' . esc_html( $u->display_name ) . '</td>';
			echo '<td>' . esc_html( $u->user_email ) . '</td>';
			echo '<td>' . esc_html( mysql2date( get_option( 'date_format' ), $u->user_registered ) ) . '</td>';
			echo '<td>' . ( $inv_u ? esc_html( $inv_u->display_name ) . ' (#' . (int) $inv . ')' : '—' ) . '</td>';
			echo '<td>';
			$approve = wp_nonce_url(
				add_query_arg(
					array(
						'page'       => 'sin-pending-approvals',
						'sin_action' => 'approve',
						'user_id'    => $u->ID,
					),
					admin_url( 'users.php' )
				),
				'sin_approve_' . $u->ID
			);
			$reject = wp_nonce_url(
				add_query_arg(
					array(
						'page'       => 'sin-pending-approvals',
						'sin_action' => 'reject',
						'user_id'    => $u->ID,
					),
					admin_url( 'users.php' )
				),
				'sin_approve_' . $u->ID
			);
			echo '<a class="button button-primary" href="' . esc_url( $approve ) . '">' . esc_html__( 'Approve', 'social-invite-network' ) . '</a> ';
			echo '<a class="button" href="' . esc_url( $reject ) . '">' . esc_html__( 'Reject', 'social-invite-network' ) . '</a>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * Invitations table + manual form.
	 */
	public static function render_invitations() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'social-invite-network' ) );
		}

		$filter_status  = isset( $_GET['sin_status'] ) ? sanitize_text_field( wp_unslash( $_GET['sin_status'] ) ) : '';
		$filter_inviter = isset( $_GET['sin_inviter'] ) ? (int) $_GET['sin_inviter'] : 0;
		$filter_email   = isset( $_GET['sin_email'] ) ? sanitize_text_field( wp_unslash( $_GET['sin_email'] ) ) : '';
		$paged          = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$per_page       = 25;

		$result = SIN_Invitations::query_for_admin(
			array(
				'status'     => $filter_status,
				'inviter_id' => $filter_inviter,
				'email'      => $filter_email,
				'paged'      => $paged,
				'per_page'   => $per_page,
			)
		);
		$rows       = $result['rows'];
		$total      = $result['total'];
		$total_pages = (int) ceil( $total / $per_page );

		echo '<div class="wrap"><h1>' . esc_html__( 'Manage Invitations', 'social-invite-network' ) . '</h1>';
		if ( isset( $_GET['resent'] ) ) {
			echo '<div class="updated notice"><p>' . esc_html__( 'Invitation email resent (if applicable).', 'social-invite-network' ) . '</p></div>';
		}
		if ( isset( $_GET['created'] ) ) {
			echo '<div class="updated notice"><p>' . esc_html__( 'Invitation created.', 'social-invite-network' ) . '</p></div>';
		}
		if ( isset( $_GET['sin_dup'] ) ) {
			echo '<div class="error notice"><p>' . esc_html__( 'An open invitation already exists for that email.', 'social-invite-network' ) . '</p></div>';
		}
		if ( isset( $_GET['sin_connected'] ) ) {
			echo '<div class="error notice"><p>' . esc_html__( 'Those users are already connected.', 'social-invite-network' ) . '</p></div>';
		}

		echo '<h2>' . esc_html__( 'Create invitation', 'social-invite-network' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'users.php?page=sin-manage-invitations' ) ) . '" style="max-width:640px">';
		wp_nonce_field( 'sin_manual_invite', 'sin_manual_invite_nonce' );
		echo '<input type="hidden" name="sin_manual_invite" value="1" />';
		echo '<p><label>' . esc_html__( 'Email', 'social-invite-network' ) . '<br /><input type="email" name="sin_manual_email" class="regular-text" required /></label></p>';
		echo '<p><label>' . esc_html__( 'Inviter', 'social-invite-network' ) . '<br />';
		wp_dropdown_users(
			array(
				'name'             => 'sin_manual_inviter',
				'show_option_none' => __( 'Select user', 'social-invite-network' ),
				'capability'       => 'read',
				'selected'         => get_current_user_id(),
			)
		);
		echo '</label></p>';
		submit_button( __( 'Create invitation', 'social-invite-network' ) );
		echo '</form>';

		echo '<h2>' . esc_html__( 'All invitations', 'social-invite-network' ) . '</h2>';
		echo '<form method="get" action="' . esc_url( admin_url( 'users.php' ) ) . '" style="margin-bottom:1em;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">';
		echo '<input type="hidden" name="page" value="sin-manage-invitations" />';
		echo '<p><label>' . esc_html__( 'Status', 'social-invite-network' ) . '<br />';
		echo '<select name="sin_status">';
		echo '<option value="">' . esc_html__( 'All', 'social-invite-network' ) . '</option>';
		echo '<option value="pending"' . selected( $filter_status, 'pending', false ) . '>' . esc_html__( 'Pending', 'social-invite-network' ) . '</option>';
		echo '<option value="accepted"' . selected( $filter_status, 'accepted', false ) . '>' . esc_html__( 'Accepted', 'social-invite-network' ) . '</option>';
		echo '</select></label></p>';
		echo '<p><label>' . esc_html__( 'Sender', 'social-invite-network' ) . '<br />';
		wp_dropdown_users(
			array(
				'name'              => 'sin_inviter',
				'show_option_none'  => __( 'All senders', 'social-invite-network' ),
				'option_none_value' => '0',
				'selected'          => $filter_inviter,
				'capability'        => 'read',
			)
		);
		echo '</label></p>';
		echo '<p><label>' . esc_html__( 'Recipient email', 'social-invite-network' ) . '<br /><input type="search" name="sin_email" class="regular-text" value="' . esc_attr( $filter_email ) . '" placeholder="name@example.com" /></label></p>';
		submit_button( __( 'Filter', 'social-invite-network' ), 'secondary', '', false );
		echo '</form>';

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Sender', 'social-invite-network' ) . '</th>';
		echo '<th>' . esc_html__( 'Recipient', 'social-invite-network' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'social-invite-network' ) . '</th>';
		echo '<th>' . esc_html__( 'Date sent', 'social-invite-network' ) . '</th>';
		echo '<th>' . esc_html__( 'Date accepted', 'social-invite-network' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'social-invite-network' ) . '</th>';
		echo '</tr></thead><tbody>';
		if ( empty( $rows ) ) {
			echo '<tr><td colspan="6">' . esc_html__( 'No invitations found.', 'social-invite-network' ) . '</td></tr>';
		} else {
			foreach ( $rows as $r ) {
				$inv      = get_userdata( (int) $r['inviter_user_id'] );
				$invitee  = get_user_by( 'email', $r['invitee_email'] );
				$accepted = ! empty( $r['accepted_at'] ) && '0000-00-00 00:00:00' !== $r['accepted_at'];
				echo '<tr>';
				echo '<td>' . ( $inv ? esc_html( $inv->display_name ) . ' (#' . (int) $inv->ID . ')' : '—' ) . '</td>';
				echo '<td>';
				echo esc_html( $r['invitee_email'] );
				if ( $invitee ) {
					echo '<br /><a href="' . esc_url( get_edit_user_link( $invitee->ID ) ) . '">' . esc_html( $invitee->display_name ) . '</a>';
				}
				echo '</td>';
				echo '<td>' . esc_html( SIN_Invitations::get_display_status( $r['status'] ) ) . '</td>';
				echo '<td>' . esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $r['created_at'] ) ) . '</td>';
				echo '<td>' . ( $accepted ? esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $r['accepted_at'] ) ) : '—' ) . '</td>';
				echo '<td>';
				if ( in_array( $r['status'], array( 'pending_registration', 'pending_approval' ), true ) ) {
					$url = wp_nonce_url(
						add_query_arg(
							array(
								'page'       => 'sin-manage-invitations',
								'sin_resend' => (int) $r['id'],
							),
							admin_url( 'users.php' )
						),
						'sin_resend_' . (int) $r['id']
					);
					echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Re-send email', 'social-invite-network' ) . '</a>';
				} else {
					echo '—';
				}
				echo '</td>';
				echo '</tr>';
			}
		}
		echo '</tbody></table>';

		if ( $total_pages > 1 ) {
			$base = add_query_arg(
				array(
					'page'        => 'sin-manage-invitations',
					'sin_status'  => $filter_status,
					'sin_inviter' => $filter_inviter,
					'sin_email'   => $filter_email,
				),
				admin_url( 'users.php' )
			);
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo paginate_links(
				array(
					'base'      => add_query_arg( 'paged', '%#%', $base ),
					'format'    => '',
					'current'   => $paged,
					'total'     => $total_pages,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				)
			);
			echo '</div></div>';
		}

		echo '</div>';
	}
}
