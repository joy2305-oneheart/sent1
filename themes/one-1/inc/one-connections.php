<?php
/**
 * Directional connection helpers (SIN invite graph).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Relationships table name.
 */
function one1_sin_relationships_table() {
	if ( class_exists( 'SIN_Database' ) ) {
		return SIN_Database::relationships_table();
	}
	global $wpdb;
	return $wpdb->prefix . 'sin_relationships';
}

/**
 * User IDs who follow this member (people who joined through their invite).
 *
 * @param int $user_id User ID.
 * @return int[]
 */
function one1_get_follower_ids( $user_id ) {
	if ( function_exists( 'sin_get_follower_ids' ) ) {
		return sin_get_follower_ids( $user_id );
	}

	global $wpdb;
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return array();
	}
	$table = one1_sin_relationships_table();
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT invitee_id FROM {$table} WHERE inviter_id = %d", $user_id ) );
	return array_values( array_filter( array_map( 'intval', $ids ) ) );
}

/**
 * User IDs this member follows (people who invited them).
 *
 * @param int $user_id User ID.
 * @return int[]
 */
function one1_get_following_ids( $user_id ) {
	if ( function_exists( 'sin_get_following_ids' ) ) {
		return sin_get_following_ids( $user_id );
	}

	global $wpdb;
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return array();
	}
	$table = one1_sin_relationships_table();
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT inviter_id FROM {$table} WHERE invitee_id = %d", $user_id ) );
	$ids   = array_values( array_filter( array_map( 'intval', $ids ) ) );
	$inviter_id = (int) get_user_meta( $user_id, 'sin_invited_by', true );
	if ( $inviter_id > 0 ) {
		$skip = class_exists( 'SIN_Invitations' ) && SIN_Invitations::is_disconnected_from( $user_id, $inviter_id );
		if ( ! $skip ) {
			$ids[] = $inviter_id;
		}
	}

	$disconnected = get_user_meta( $user_id, 'sin_disconnected_users', true );
	if ( is_array( $disconnected ) && ! empty( $disconnected ) ) {
		$blocked = array_map( 'intval', $disconnected );
		$ids     = array_values( array_diff( $ids, $blocked ) );
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Followers count.
 *
 * @param int $user_id User ID.
 */
function one1_count_followers( $user_id ) {
	return count( one1_get_follower_ids( $user_id ) );
}

/**
 * Following count.
 *
 * @param int $user_id User ID.
 */
function one1_count_following( $user_id ) {
	return count( one1_get_following_ids( $user_id ) );
}

/**
 * Render a user list for connections UI.
 *
 * @param int[]  $user_ids     User IDs.
 * @param string $empty        Empty message.
 * @param bool   $show_actions Show disconnect/report actions.
 */
function one1_render_connection_user_list( array $user_ids, $empty = '', $show_actions = true ) {
	if ( empty( $user_ids ) ) {
		if ( $empty ) {
			echo '<p class="one-connections-list__empty">' . esc_html( $empty ) . '</p>';
		}
		return;
	}
	echo '<ul class="one-connections-list">';
	foreach ( $user_ids as $uid ) {
		$user = get_userdata( (int) $uid );
		if ( ! $user ) {
			continue;
		}
		echo '<li class="one-connections-list__item">';
		echo get_avatar( (int) $uid, 40, '', '', array( 'class' => 'one-connections-list__avatar' ) );
		echo '<span class="one-connections-list__name">' . esc_html( $user->display_name ) . '</span>';
		if ( $show_actions ) {
			echo '<span class="one-connections-list__actions">';
			echo '<button type="button" class="one-connections-list__action one-connections-list__action--disconnect" data-sin-disconnect="' . esc_attr( (string) (int) $uid ) . '">' . esc_html__( 'Disconnect', 'one' ) . '</button>';
			echo '<button type="button" class="one-connections-list__action one-connections-list__action--report" data-sin-report="' . esc_attr( (string) (int) $uid ) . '">' . esc_html__( 'Report', 'one' ) . '</button>';
			echo '</span>';
		}
		echo '</li>';
	}
	echo '</ul>';
}
