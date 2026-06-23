<?php
/**
 * User report storage and validation.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Reports
 */
class SIN_Reports {

	const STATUS_PENDING   = 'pending';
	const STATUS_REVIEWED  = 'reviewed';
	const STATUS_DISMISSED = 'dismissed';

	/**
	 * Allowed report reasons.
	 *
	 * @return array<string, string>
	 */
	public static function reason_options() {
		return array(
			'harassment'    => __( 'Harassment or abuse', 'social-invite-network' ),
			'spam'          => __( 'Spam', 'social-invite-network' ),
			'inappropriate' => __( 'Inappropriate content', 'social-invite-network' ),
			'other'         => __( 'Other', 'social-invite-network' ),
		);
	}

	/**
	 * Create a user report.
	 *
	 * @param int    $reporter_id Reporter user ID.
	 * @param int    $reported_id Reported user ID.
	 * @param string $reason      Reason key.
	 * @param string $details     Optional details.
	 * @return int|WP_Error Report ID or error.
	 */
	public static function create_report( $reporter_id, $reported_id, $reason, $details = '' ) {
		global $wpdb;

		$reporter_id = (int) $reporter_id;
		$reported_id = (int) $reported_id;
		$reason      = sanitize_key( $reason );
		$details     = sanitize_textarea_field( (string) $details );

		if ( $reporter_id <= 0 || $reported_id <= 0 || $reporter_id === $reported_id ) {
			return new WP_Error( 'invalid_users', __( 'Invalid users.', 'social-invite-network' ) );
		}

		$options = self::reason_options();
		if ( ! isset( $options[ $reason ] ) ) {
			return new WP_Error( 'invalid_reason', __( 'Please select a valid reason.', 'social-invite-network' ) );
		}

		if ( self::is_rate_limited( $reporter_id ) ) {
			return new WP_Error( 'rate_limited', __( 'Too many reports today. Please try again tomorrow.', 'social-invite-network' ) );
		}

		if ( self::has_recent_duplicate( $reporter_id, $reported_id ) ) {
			return new WP_Error( 'duplicate', __( 'You already reported this member recently.', 'social-invite-network' ) );
		}

		$table = SIN_Database::reports_table();
		$now   = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$table,
			array(
				'reporter_id' => $reporter_id,
				'reported_id' => $reported_id,
				'reason'      => $reason,
				'details'     => $details,
				'status'      => self::STATUS_PENDING,
				'created_at'  => $now,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return new WP_Error( 'db_error', __( 'Could not save report.', 'social-invite-network' ) );
		}

		self::bump_rate_limit( $reporter_id );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Whether reporter already reported this user in the last 24 hours.
	 *
	 * @param int $reporter_id Reporter.
	 * @param int $reported_id Reported user.
	 */
	public static function has_recent_duplicate( $reporter_id, $reported_id ) {
		global $wpdb;
		$table = SIN_Database::reports_table();
		$since = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE reporter_id = %d AND reported_id = %d AND created_at >= %s LIMIT 1",
				(int) $reporter_id,
				(int) $reported_id,
				$since
			)
		);

		return (bool) $found;
	}

	/**
	 * Rate limit: max 10 reports per day.
	 *
	 * @param int $user_id User ID.
	 */
	public static function is_rate_limited( $user_id ) {
		$key   = 'sin_report_rate_' . (int) $user_id . '_' . gmdate( 'Ymd' );
		$count = (int) get_transient( $key );
		return $count >= 10;
	}

	/**
	 * Increment daily report counter.
	 *
	 * @param int $user_id User ID.
	 */
	public static function bump_rate_limit( $user_id ) {
		$key   = 'sin_report_rate_' . (int) $user_id . '_' . gmdate( 'Ymd' );
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, DAY_IN_SECONDS );
	}

	/**
	 * Query reports for admin.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array{rows: array<int, array<string, mixed>>, total: int}
	 */
	public static function query_for_admin( $args = array() ) {
		global $wpdb;
		$table  = SIN_Database::reports_table();
		$status = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		$paged  = max( 1, (int) ( $args['paged'] ?? 1 ) );
		$per    = max( 1, min( 100, (int) ( $args['per_page'] ?? 25 ) ) );
		$offset = ( $paged - 1 ) * $per;

		$where  = array( '1=1' );
		$params = array();

		if ( $status !== '' ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}

		$where_sql = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var(
			$params
				? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", ...$params )
				: "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}"
		);

		$query_params = $params;
		$query_params[] = $per;
		$query_params[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				...$query_params
			),
			ARRAY_A
		);

		return array(
			'rows'  => is_array( $rows ) ? $rows : array(),
			'total' => $total,
		);
	}

	/**
	 * Update report status.
	 *
	 * @param int    $report_id Report ID.
	 * @param string $status    New status.
	 */
	public static function update_status( $report_id, $status ) {
		global $wpdb;
		$report_id = (int) $report_id;
		$allowed   = array( self::STATUS_PENDING, self::STATUS_REVIEWED, self::STATUS_DISMISSED );
		if ( $report_id <= 0 || ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		$table = SIN_Database::reports_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (bool) $wpdb->update(
			$table,
			array( 'status' => $status ),
			array( 'id' => $report_id ),
			array( '%s' ),
			array( '%d' )
		);
	}
}
