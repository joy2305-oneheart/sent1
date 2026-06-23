<?php
/**
 * Admin: followers / following columns and user profile fields.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_User_Connections_Admin
 */
class SIN_User_Connections_Admin {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_filter( 'manage_users_columns', array( __CLASS__, 'users_columns' ) );
		add_filter( 'manage_users_custom_column', array( __CLASS__, 'users_column_content' ), 10, 3 );
		add_action( 'show_user_profile', array( __CLASS__, 'render_profile_section' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'render_profile_section' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_heal_existing_connections' ) );
	}

	/**
	 * One-time repair: sync relationships and counts for approved members.
	 */
	public static function maybe_heal_existing_connections() {
		if ( ! current_user_can( 'manage_options' ) || get_option( 'sin_connections_healed_v1' ) === '1' ) {
			return;
		}

		$users = get_users(
			array(
				'meta_key'   => 'sin_account_status',
				'meta_value' => 'approved',
				'fields'     => 'ID',
				'number'     => 5000,
			)
		);

		foreach ( $users as $user_id ) {
			if ( class_exists( 'SIN_Registration' ) ) {
				SIN_Registration::sync_relationship_for_user( (int) $user_id );
			} elseif ( function_exists( 'sin_sync_connection_meta' ) ) {
				sin_sync_connection_meta( (int) $user_id );
			}
		}

		update_option( 'sin_connections_healed_v1', '1', false );
	}

	/**
	 * Add columns to the Users list table.
	 *
	 * @param string[] $columns Existing columns.
	 * @return string[]
	 */
	public static function users_columns( $columns ) {
		$columns['sin_followers'] = __( 'Followers', 'social-invite-network' );
		$columns['sin_following'] = __( 'Following', 'social-invite-network' );
		return $columns;
	}

	/**
	 * Render column values.
	 *
	 * @param string $output      Cell output.
	 * @param string $column_name Column key.
	 * @param int    $user_id     User ID.
	 * @return string
	 */
	public static function users_column_content( $output, $column_name, $user_id ) {
		if ( 'sin_followers' === $column_name ) {
			return esc_html( (string) sin_count_followers( $user_id ) );
		}
		if ( 'sin_following' === $column_name ) {
			return esc_html( (string) sin_count_following( $user_id ) );
		}
		return $output;
	}

	/**
	 * User edit screen: connection summary.
	 *
	 * @param WP_User $user User object.
	 */
	public static function render_profile_section( $user ) {
		if ( ! current_user_can( 'list_users' ) ) {
			return;
		}

		$user_id   = (int) $user->ID;
		$followers = sin_get_follower_ids( $user_id );
		$following = sin_get_following_ids( $user_id );

		sin_sync_connection_meta( $user_id );
		?>
		<h2><?php esc_html_e( 'Network connections', 'social-invite-network' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Followers', 'social-invite-network' ); ?></th>
				<td>
					<p>
						<strong><?php echo esc_html( (string) count( $followers ) ); ?></strong>
						<?php esc_html_e( '(people who joined through this member’s invite)', 'social-invite-network' ); ?>
					</p>
					<?php self::render_user_id_list( $followers ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Following', 'social-invite-network' ); ?></th>
				<td>
					<p>
						<strong><?php echo esc_html( (string) count( $following ) ); ?></strong>
						<?php esc_html_e( '(people who invited this member)', 'social-invite-network' ); ?>
					</p>
					<?php self::render_user_id_list( $following ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Stored meta', 'social-invite-network' ); ?></th>
				<td>
					<p class="description">
						<?php
						printf(
							/* translators: 1: followers count meta, 2: following count meta */
							esc_html__( 'sin_followers_count: %1$s — sin_following_count: %2$s', 'social-invite-network' ),
							esc_html( (string) get_user_meta( $user_id, 'sin_followers_count', true ) ),
							esc_html( (string) get_user_meta( $user_id, 'sin_following_count', true ) )
						);
						?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * @param int[] $user_ids User IDs.
	 */
	private static function render_user_id_list( array $user_ids ) {
		if ( empty( $user_ids ) ) {
			echo '<p class="description">' . esc_html__( 'None', 'social-invite-network' ) . '</p>';
			return;
		}
		echo '<ul style="margin:0;">';
		foreach ( $user_ids as $uid ) {
			$member = get_userdata( (int) $uid );
			if ( ! $member ) {
				continue;
			}
			$edit_url = get_edit_user_link( (int) $uid );
			echo '<li>';
			if ( $edit_url ) {
				echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html( $member->display_name ) . '</a>';
			} else {
				echo esc_html( $member->display_name );
			}
			echo ' <span class="description">(#' . (int) $uid . ' — ' . esc_html( $member->user_email ) . ')</span>';
			echo '</li>';
		}
		echo '</ul>';
	}
}
