<?php
/**
 * Admin-only Primary User onboarding invites (1-hour token).
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Admin_PU_Invites
 */
class SIN_Admin_PU_Invites {

	const STATUS_PENDING  = 'pending';
	const STATUS_ACCEPTED = 'accepted';
	const STATUS_EXPIRED  = 'expired';
	const STATUS_REVOKED  = 'revoked';

	const EXPIRY_SECONDS = HOUR_IN_SECONDS;

	/**
	 * Table name.
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sin_admin_pu_invites';
	}

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'handle_admin_actions' ) );
	}

	/**
	 * Expire stale pending invites.
	 */
	public static function expire_stale() {
		global $wpdb;
		$table = self::table();
		$now   = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = %s WHERE status = %s AND expires_at <= %s",
				self::STATUS_EXPIRED,
				self::STATUS_PENDING,
				$now
			)
		);
	}

	/**
	 * Create invite and send email.
	 *
	 * @param string $email       Invitee email.
	 * @param int    $admin_id    Admin user ID.
	 * @return int|WP_Error Row ID or error.
	 */
	public static function create_and_send( $email, $admin_id ) {
		global $wpdb;

		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'sin_pu_invite', __( 'Please enter a valid email address.', 'social-invite-network' ) );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( 'sin_pu_invite', __( 'That email is already registered.', 'social-invite-network' ) );
		}

		self::expire_stale();

		$table = self::table();
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE LOWER(invitee_email) = LOWER(%s) AND status = %s LIMIT 1",
				$email,
				self::STATUS_PENDING
			)
		);
		if ( $existing ) {
			return new WP_Error( 'sin_pu_invite', __( 'A pending Primary User invite already exists for this email.', 'social-invite-network' ) );
		}

		$token      = bin2hex( random_bytes( 32 ) );
		$created_at = current_time( 'mysql', true );
		$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( $created_at ) + self::EXPIRY_SECONDS );

		$inserted = $wpdb->insert(
			$table,
			array(
				'invitee_email' => $email,
				'token'         => $token,
				'admin_user_id' => (int) $admin_id,
				'status'        => self::STATUS_PENDING,
				'created_at'    => $created_at,
				'expires_at'    => $expires_at,
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return new WP_Error( 'sin_pu_invite', __( 'Could not create invite.', 'social-invite-network' ) );
		}

		$id = (int) $wpdb->insert_id;
		self::send_email( $email, $token );

		return $id;
	}

	/**
	 * Resend invite (resets expiry).
	 *
	 * @param int $invite_id Row ID.
	 * @return bool|WP_Error
	 */
	public static function resend( $invite_id ) {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", (int) $invite_id ),
			ARRAY_A
		);
		if ( ! $row ) {
			return new WP_Error( 'sin_pu_invite', __( 'Invite not found.', 'social-invite-network' ) );
		}
		if ( self::STATUS_ACCEPTED === $row['status'] ) {
			return new WP_Error( 'sin_pu_invite', __( 'This invite was already accepted.', 'social-invite-network' ) );
		}

		$created_at = current_time( 'mysql', true );
		$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( $created_at ) + self::EXPIRY_SECONDS );

		$wpdb->update(
			$table,
			array(
				'status'     => self::STATUS_PENDING,
				'created_at' => $created_at,
				'expires_at' => $expires_at,
			),
			array( 'id' => (int) $invite_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		self::send_email( $row['invitee_email'], $row['token'] );
		return true;
	}

	/**
	 * Revoke a pending invite.
	 *
	 * @param int $invite_id Row ID.
	 */
	public static function revoke( $invite_id ) {
		global $wpdb;
		$table = self::table();
		$wpdb->update(
			$table,
			array( 'status' => self::STATUS_REVOKED ),
			array(
				'id'     => (int) $invite_id,
				'status' => self::STATUS_PENDING,
			),
			array( '%s' ),
			array( '%d', '%s' )
		);
	}

	/**
	 * Validate token for signup (does not consume).
	 *
	 * @param string $token Token.
	 * @return array<string, mixed>|null Row or null.
	 */
	public static function get_valid_token_row( $token ) {
		if ( ! is_string( $token ) || $token === '' ) {
			return null;
		}

		self::expire_stale();

		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE token = %s AND status = %s LIMIT 1",
				$token,
				self::STATUS_PENDING
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		$now = current_time( 'mysql', true );
		if ( $row['expires_at'] <= $now ) {
			$wpdb->update(
				$table,
				array( 'status' => self::STATUS_EXPIRED ),
				array( 'id' => (int) $row['id'] ),
				array( '%s' ),
				array( '%d' )
			);
			return null;
		}

		return $row;
	}

	/**
	 * Validate token + email for registration.
	 *
	 * @param string $token Token.
	 * @param string $email Registration email.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function validate_for_registration( $token, $email ) {
		$row = self::get_valid_token_row( $token );
		if ( ! $row ) {
			return new WP_Error( 'sin_pu_invite', __( 'This Primary User invitation is invalid or has expired.', 'social-invite-network' ) );
		}
		if ( strtolower( sanitize_email( $email ) ) !== strtolower( $row['invitee_email'] ) ) {
			return new WP_Error( 'sin_pu_invite', __( 'You must register with the email address that received the invitation.', 'social-invite-network' ) );
		}
		return $row;
	}

	/**
	 * Mark invite accepted after successful registration.
	 *
	 * @param string $token Token.
	 */
	public static function mark_accepted( $token ) {
		global $wpdb;
		$table = self::table();
		$wpdb->update(
			$table,
			array(
				'status'      => self::STATUS_ACCEPTED,
				'accepted_at' => current_time( 'mysql', true ),
			),
			array(
				'token'  => $token,
				'status' => self::STATUS_PENDING,
			),
			array( '%s', '%s' ),
			array( '%s', '%s' )
		);
	}

	/**
	 * Build signup URL with token.
	 *
	 * @param string $token Token.
	 */
	public static function signup_url( $token ) {
		if ( function_exists( 'one1_signup_url' ) ) {
			return one1_signup_url( '', $token );
		}
		return add_query_arg( 'pu_token', rawurlencode( $token ), home_url( '/join/' ) );
	}

	/**
	 * Send invite email.
	 *
	 * @param string $email Invitee email.
	 * @param string $token Token.
	 */
	private static function send_email( $email, $token ) {
		$link = self::signup_url( $token );
		$subj = sprintf(
			/* translators: %s: site name */
			__( 'You are invited to become a Primary User on %s', 'social-invite-network' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);
		$body  = __( 'You have been invited to join as a Primary User and share your journey.', 'social-invite-network' ) . "\n\n";
		$body .= __( 'This link expires in 1 hour and can only be used with this email address:', 'social-invite-network' ) . "\n";
		$body .= $link . "\n\n";
		$body .= __( 'If you did not expect this invitation, you can ignore this email.', 'social-invite-network' ) . "\n";

		wp_mail( $email, $subj, $body );
	}

	/**
	 * Recent invites for admin list.
	 *
	 * @param int $limit Max rows.
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_recent( $limit = 50 ) {
		global $wpdb;
		self::expire_stale();
		$table = self::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", (int) $limit ),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Handle admin POST/GET actions.
	 */
	public static function handle_admin_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_GET['page'] ) || 'sin-invite-pu' !== $_GET['page'] ) {
			return;
		}

		if ( isset( $_POST['sin_pu_invite_submit'], $_POST['sin_pu_invite_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sin_pu_invite_nonce'] ) ), 'sin_pu_invite' ) ) {
			$email = isset( $_POST['sin_pu_invite_email'] ) ? sanitize_email( wp_unslash( $_POST['sin_pu_invite_email'] ) ) : '';
			$result = self::create_and_send( $email, get_current_user_id() );
			if ( is_wp_error( $result ) ) {
				wp_safe_redirect( admin_url( 'users.php?page=sin-invite-pu&sin_err=' . rawurlencode( $result->get_error_message() ) ) );
			} else {
				wp_safe_redirect( admin_url( 'users.php?page=sin-invite-pu&created=1' ) );
			}
			exit;
		}

		if ( isset( $_GET['sin_resend_pu'], $_GET['_wpnonce'] ) ) {
			$id = (int) $_GET['sin_resend_pu'];
			if ( $id > 0 && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sin_resend_pu_' . $id ) ) {
				self::resend( $id );
			}
			wp_safe_redirect( admin_url( 'users.php?page=sin-invite-pu&resent=1' ) );
			exit;
		}

		if ( isset( $_GET['sin_revoke_pu'], $_GET['_wpnonce'] ) ) {
			$id = (int) $_GET['sin_revoke_pu'];
			if ( $id > 0 && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sin_revoke_pu_' . $id ) ) {
				self::revoke( $id );
			}
			wp_safe_redirect( admin_url( 'users.php?page=sin-invite-pu&revoked=1' ) );
			exit;
		}
	}

	/**
	 * Render admin page.
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'social-invite-network' ) );
		}

		if ( isset( $_GET['created'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Primary User invitation sent.', 'social-invite-network' ) . '</p></div>';
		}
		if ( isset( $_GET['resent'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Invitation resent with a new 1-hour window.', 'social-invite-network' ) . '</p></div>';
		}
		if ( isset( $_GET['revoked'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Invitation revoked.', 'social-invite-network' ) . '</p></div>';
		}
		if ( isset( $_GET['sin_err'] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['sin_err'] ) ) ) ) . '</p></div>';
		}

		$rows = self::list_recent();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Invite Primary User', 'social-invite-network' ); ?></h1>
			<p><?php esc_html_e( 'Send a time-limited invitation (1 hour) for someone to register as a Primary User. The link is bound to their email address.', 'social-invite-network' ); ?></p>

			<form method="post" action="" style="max-width:32rem;margin:1.5rem 0;">
				<?php wp_nonce_field( 'sin_pu_invite', 'sin_pu_invite_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="sin_pu_invite_email"><?php esc_html_e( 'Email address', 'social-invite-network' ); ?></label></th>
						<td><input type="email" name="sin_pu_invite_email" id="sin_pu_invite_email" class="regular-text" required /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Send invitation', 'social-invite-network' ), 'primary', 'sin_pu_invite_submit' ); ?>
			</form>

			<h2><?php esc_html_e( 'Recent invitations', 'social-invite-network' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Email', 'social-invite-network' ); ?></th>
						<th><?php esc_html_e( 'Status', 'social-invite-network' ); ?></th>
						<th><?php esc_html_e( 'Expires (UTC)', 'social-invite-network' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'social-invite-network' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No invitations yet.', 'social-invite-network' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['invitee_email'] ); ?></td>
							<td><?php echo esc_html( $row['status'] ); ?></td>
							<td><?php echo esc_html( $row['expires_at'] ); ?></td>
							<td>
								<?php if ( self::STATUS_PENDING === $row['status'] ) : ?>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'users.php?page=sin-invite-pu&sin_resend_pu=' . (int) $row['id'] ), 'sin_resend_pu_' . (int) $row['id'] ) ); ?>"><?php esc_html_e( 'Resend', 'social-invite-network' ); ?></a>
									|
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'users.php?page=sin-invite-pu&sin_revoke_pu=' . (int) $row['id'] ), 'sin_revoke_pu_' . (int) $row['id'] ) ); ?>"><?php esc_html_e( 'Revoke', 'social-invite-network' ); ?></a>
								<?php else : ?>
									&mdash;
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
