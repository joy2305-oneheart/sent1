<?php
/**
 * Admin UI for user reports queue.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Reports_Admin
 */
class SIN_Reports_Admin {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menus' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
	}

	/**
	 * Register admin page under Users.
	 */
	public static function menus() {
		add_users_page(
			__( 'User Reports', 'social-invite-network' ),
			__( 'User Reports', 'social-invite-network' ),
			'manage_options',
			'sin-user-reports',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Handle status updates.
	 */
	public static function handle_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_GET['page'] ) || 'sin-user-reports' !== $_GET['page'] ) {
			return;
		}
		if ( ! isset( $_GET['sin_report_action'], $_GET['report_id'], $_GET['_wpnonce'] ) ) {
			return;
		}

		$report_id = (int) $_GET['report_id'];
		$action    = sanitize_key( wp_unslash( $_GET['sin_report_action'] ) );
		if ( $report_id <= 0 || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sin_report_' . $report_id ) ) {
			wp_die( esc_html__( 'Invalid request.', 'social-invite-network' ) );
		}

		if ( 'review' === $action ) {
			SIN_Reports::update_status( $report_id, SIN_Reports::STATUS_REVIEWED );
		} elseif ( 'dismiss' === $action ) {
			SIN_Reports::update_status( $report_id, SIN_Reports::STATUS_DISMISSED );
		}

		wp_safe_redirect( admin_url( 'users.php?page=sin-user-reports&updated=1' ) );
		exit;
	}

	/**
	 * Render reports list.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : SIN_Reports::STATUS_PENDING;
		$paged  = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$data   = SIN_Reports::query_for_admin(
			array(
				'status'   => $status,
				'paged'    => $paged,
				'per_page' => 25,
			)
		);
		$reasons = SIN_Reports::reason_options();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'User Reports', 'social-invite-network' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Report updated.', 'social-invite-network' ); ?></p></div>
			<?php endif; ?>

			<ul class="subsubsub">
				<?php
				$tabs = array(
					SIN_Reports::STATUS_PENDING   => __( 'Pending', 'social-invite-network' ),
					SIN_Reports::STATUS_REVIEWED  => __( 'Reviewed', 'social-invite-network' ),
					SIN_Reports::STATUS_DISMISSED => __( 'Dismissed', 'social-invite-network' ),
					''                            => __( 'All', 'social-invite-network' ),
				);
				$links = array();
				foreach ( $tabs as $key => $label ) {
					$url     = add_query_arg(
						array(
							'page'   => 'sin-user-reports',
							'status' => $key,
						),
						admin_url( 'users.php' )
					);
					$class   = (string) $status === (string) $key ? 'class="current"' : '';
					$links[] = '<li><a href="' . esc_url( $url ) . '" ' . $class . '>' . esc_html( $label ) . '</a></li>';
				}
				echo wp_kses_post( implode( ' | ', $links ) );
				?>
			</ul>

			<table class="widefat striped" style="margin-top:1rem;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'social-invite-network' ); ?></th>
						<th><?php esc_html_e( 'Reporter', 'social-invite-network' ); ?></th>
						<th><?php esc_html_e( 'Reported', 'social-invite-network' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'social-invite-network' ); ?></th>
						<th><?php esc_html_e( 'Details', 'social-invite-network' ); ?></th>
						<th><?php esc_html_e( 'Status', 'social-invite-network' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'social-invite-network' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $data['rows'] ) ) : ?>
						<tr><td colspan="7"><?php esc_html_e( 'No reports found.', 'social-invite-network' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $data['rows'] as $row ) : ?>
							<?php
							$reporter = get_userdata( (int) $row['reporter_id'] );
							$reported = get_userdata( (int) $row['reported_id'] );
							$reason   = isset( $reasons[ $row['reason'] ] ) ? $reasons[ $row['reason'] ] : $row['reason'];
							?>
							<tr>
								<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['created_at'] ) ); ?></td>
								<td>
									<?php if ( $reporter ) : ?>
										<a href="<?php echo esc_url( get_edit_user_link( $reporter->ID ) ); ?>"><?php echo esc_html( $reporter->display_name ); ?></a>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $reported ) : ?>
										<a href="<?php echo esc_url( get_edit_user_link( $reported->ID ) ); ?>"><?php echo esc_html( $reported->display_name ); ?></a>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $reason ); ?></td>
								<td><?php echo esc_html( (string) $row['details'] ); ?></td>
								<td><?php echo esc_html( $row['status'] ); ?></td>
								<td>
									<?php if ( SIN_Reports::STATUS_PENDING === $row['status'] ) : ?>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'users.php?page=sin-user-reports&sin_report_action=review&report_id=' . (int) $row['id'] ), 'sin_report_' . (int) $row['id'] ) ); ?>"><?php esc_html_e( 'Mark reviewed', 'social-invite-network' ); ?></a>
										|
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'users.php?page=sin-user-reports&sin_report_action=dismiss&report_id=' . (int) $row['id'] ), 'sin_report_' . (int) $row['id'] ) ); ?>"><?php esc_html_e( 'Dismiss', 'social-invite-network' ); ?></a>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
