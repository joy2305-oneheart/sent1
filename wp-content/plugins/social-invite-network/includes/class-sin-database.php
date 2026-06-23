<?php
/**
 * Database schema and activation.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Database
 */
class SIN_Database {

	/**
	 * Invitations table name (without prefix).
	 */
	public static function invitations_table() {
		global $wpdb;
		return $wpdb->prefix . 'sin_invitations';
	}

	/**
	 * Relationships table name.
	 */
	public static function relationships_table() {
		global $wpdb;
		return $wpdb->prefix . 'sin_relationships';
	}

	/**
	 * Reports table name.
	 */
	public static function reports_table() {
		global $wpdb;
		return $wpdb->prefix . 'sin_user_reports';
	}

	/**
	 * Admin PU invite table name.
	 */
	public static function admin_pu_invites_table() {
		global $wpdb;
		return $wpdb->prefix . 'sin_admin_pu_invites';
	}

	/**
	 * Temporary public story share links table.
	 */
	public static function story_share_links_table() {
		global $wpdb;
		return $wpdb->prefix . 'sin_story_share_links';
	}

	/**
	 * PU friend details table.
	 */
	public static function friend_details_table() {
		global $wpdb;
		return $wpdb->prefix . 'sin_friend_details';
	}

	/**
	 * Activation: create tables.
	 */
	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$inv             = self::invitations_table();
		$rel             = self::relationships_table();
		$reports         = self::reports_table();

		$sql_inv = "CREATE TABLE {$inv} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			inviter_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			invitee_email varchar(190) NOT NULL DEFAULT '',
			invite_code text NULL,
			status varchar(32) NOT NULL DEFAULT 'pending_registration',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			accepted_at datetime NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY inviter_user_id (inviter_user_id),
			KEY invitee_email (invitee_email(100)),
			KEY status (status)
		) {$charset_collate};";

		$sql_rel = "CREATE TABLE {$rel} (
			inviter_id bigint(20) unsigned NOT NULL,
			invitee_id bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (inviter_id, invitee_id),
			KEY invitee_id (invitee_id)
		) {$charset_collate};";

		dbDelta( $sql_inv );
		dbDelta( $sql_rel );

		$sql_reports = "CREATE TABLE {$reports} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			reporter_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reported_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reason varchar(64) NOT NULL DEFAULT '',
			details text NULL,
			status varchar(32) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY reporter_id (reporter_id),
			KEY reported_id (reported_id),
			KEY status (status)
		) {$charset_collate};";

		dbDelta( $sql_reports );

		$pu_invites = self::admin_pu_invites_table();
		$sql_pu     = "CREATE TABLE {$pu_invites} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			invitee_email varchar(190) NOT NULL DEFAULT '',
			token varchar(64) NOT NULL DEFAULT '',
			admin_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(32) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			expires_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			accepted_at datetime NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY token (token),
			KEY invitee_email (invitee_email(100)),
			KEY status (status),
			KEY expires_at (expires_at)
		) {$charset_collate};";

		dbDelta( $sql_pu );

		$share_links = self::story_share_links_table();
		$sql_share   = "CREATE TABLE {$share_links} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			token varchar(64) NOT NULL DEFAULT '',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(32) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			expires_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY token (token),
			KEY post_id (post_id),
			KEY status (status),
			KEY expires_at (expires_at)
		) {$charset_collate};";

		dbDelta( $sql_share );

		$friend_details = self::friend_details_table();
		$sql_friends    = "CREATE TABLE {$friend_details} (
			inviter_id bigint(20) unsigned NOT NULL,
			friend_id bigint(20) unsigned NOT NULL,
			nickname varchar(190) NOT NULL DEFAULT '',
			notes text NULL,
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (inviter_id, friend_id),
			KEY friend_id (friend_id)
		) {$charset_collate};";

		dbDelta( $sql_friends );

		require_once SIN_PLUGIN_DIR . 'includes/class-sin-crypto.php';
		require_once SIN_PLUGIN_DIR . 'includes/class-sin-roles.php';
		SIN_Roles::register_roles();
		SIN_Roles::migrate_approved_users();

		flush_rewrite_rules();
	}

	/**
	 * Deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
