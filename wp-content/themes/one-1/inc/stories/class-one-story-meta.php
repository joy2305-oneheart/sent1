<?php
/**
 * Story post meta registration and persistence.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class One_Story_Meta
 */
class One_Story_Meta {

	/**
	 * Register post meta.
	 */
	public static function register() {
		static $registered = false;
		if ( $registered ) {
			return;
		}
		$registered = true;

		$keys = array(
			'one_story_featured'          => 'boolean',
			'one_story_verified'          => 'boolean',
			'one_story_is_donation'       => 'boolean',
			'one_story_comments_enabled'  => 'boolean',
			'one_story_hide_likes'        => 'boolean',
			'one_story_urgency'           => 'string',
			'one_story_fundraising_goal'  => 'number',
			'one_story_amount_raised'     => 'number',
			'one_story_donor_count'       => 'integer',
			'one_story_end_date'          => 'string',
			'one_story_city'              => 'string',
			'one_story_state_region'      => 'string',
			'one_story_location_label'    => 'string',
			'one_story_location_place_id' => 'string',
			'one_story_upi_id'            => 'string',
			'one_story_payment_link'      => 'string',
		);

		foreach ( $keys as $key => $type ) {
			register_post_meta(
				ONE_STORY_POST_TYPE,
				$key,
				array(
					'type'              => $type,
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => static function () {
						return one_story_user_can_submit( get_current_user_id() );
					},
					'sanitize_callback' => array( __CLASS__, 'sanitize_meta' ),
				)
			);
		}
	}

	/**
	 * Sanitize meta by key.
	 *
	 * @param mixed  $value   Value.
	 * @param string $key     Meta key.
	 * @param string $type    Meta type.
	 */
	public static function sanitize_meta( $value, $key, $type ) {
		unset( $type );
		switch ( $key ) {
			case 'one_story_featured':
			case 'one_story_verified':
			case 'one_story_is_donation':
			case 'one_story_comments_enabled':
			case 'one_story_hide_likes':
				return (bool) $value;

			case 'one_story_urgency':
				$options = array_keys( one_story_urgency_options() );
				return in_array( $value, $options, true ) ? $value : 'standard';

			case 'one_story_fundraising_goal':
			case 'one_story_amount_raised':
				return max( 0, (float) $value );

			case 'one_story_donor_count':
				return max( 0, (int) $value );

			case 'one_story_end_date':
				return self::sanitize_datetime( $value );

			case 'one_story_city':
			case 'one_story_state_region':
			case 'one_story_location_label':
			case 'one_story_location_place_id':
			case 'one_story_upi_id':
				return sanitize_text_field( (string) $value );

			case 'one_story_payment_link':
				return esc_url_raw( (string) $value );

			default:
				return $value;
		}
	}

	/**
	 * Normalize datetime for storage.
	 *
	 * @param string $value Raw datetime.
	 */
	public static function sanitize_datetime( $value ) {
		$value = trim( (string) $value );
		if ( $value === '' ) {
			return '';
		}
		$ts = strtotime( $value );
		if ( ! $ts ) {
			return '';
		}
		return gmdate( 'Y-m-d H:i:s', $ts );
	}

	/**
	 * Get all meta for a story.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	public static function get_all( $post_id ) {
		$amount_raised = (float) get_post_meta( $post_id, 'one_story_amount_raised', true );
		$webhook_raised = (float) get_post_meta( $post_id, '_story_raised', true );
		if ( $webhook_raised > $amount_raised ) {
			$amount_raised = $webhook_raised;
		}

		$donor_count = (int) get_post_meta( $post_id, 'one_story_donor_count', true );
		$webhook_donors = (int) get_post_meta( $post_id, '_story_donors', true );
		if ( $webhook_donors > $donor_count ) {
			$donor_count = $webhook_donors;
		}

		return array(
			'featured'          => (bool) get_post_meta( $post_id, 'one_story_featured', true ),
			'verified'          => (bool) get_post_meta( $post_id, 'one_story_verified', true ),
			'urgency'           => get_post_meta( $post_id, 'one_story_urgency', true ) ?: 'standard',
			'is_donation'       => (bool) get_post_meta( $post_id, 'one_story_is_donation', true ),
			'comments_enabled'  => function_exists( 'one1_story_comments_enabled' ) ? one1_story_comments_enabled( $post_id ) : true,
			'hide_likes'        => function_exists( 'one1_story_hide_likes' ) ? one1_story_hide_likes( $post_id ) : false,
			'fundraising_goal'  => (float) get_post_meta( $post_id, 'one_story_fundraising_goal', true ),
			'amount_raised'     => $amount_raised,
			'donor_count'       => $donor_count,
			'end_date'          => get_post_meta( $post_id, 'one_story_end_date', true ),
			'city'              => get_post_meta( $post_id, 'one_story_city', true ),
			'state_region'      => get_post_meta( $post_id, 'one_story_state_region', true ),
			'location_label'    => get_post_meta( $post_id, 'one_story_location_label', true ),
			'location_place_id' => get_post_meta( $post_id, 'one_story_location_place_id', true ),
			'upi_id'            => get_post_meta( $post_id, 'one_story_upi_id', true ),
			'payment_link'      => get_post_meta( $post_id, 'one_story_payment_link', true ),
		);
	}

	/**
	 * Save meta from request array.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $data    Field data.
	 */
	public static function save_from_array( $post_id, array $data ) {
		update_post_meta( $post_id, 'one_story_featured', ! empty( $data['featured'] ) ? 1 : 0 );
		update_post_meta( $post_id, 'one_story_verified', ! empty( $data['verified'] ) ? 1 : 0 );
		update_post_meta( $post_id, 'one_story_urgency', self::sanitize_meta( $data['urgency'] ?? 'standard', 'one_story_urgency', 'string' ) );
		update_post_meta( $post_id, 'one_story_city', self::sanitize_meta( $data['city'] ?? '', 'one_story_city', 'string' ) );
		update_post_meta( $post_id, 'one_story_state_region', self::sanitize_meta( $data['state_region'] ?? '', 'one_story_state_region', 'string' ) );
		update_post_meta( $post_id, 'one_story_location_label', self::sanitize_meta( $data['location_label'] ?? '', 'one_story_location_label', 'string' ) );
		update_post_meta( $post_id, 'one_story_location_place_id', self::sanitize_meta( $data['location_place_id'] ?? '', 'one_story_location_place_id', 'string' ) );
		update_post_meta( $post_id, 'one_story_comments_enabled', ! empty( $data['comments_enabled'] ) ? 1 : 0 );
		update_post_meta( $post_id, 'one_story_hide_likes', ! empty( $data['hide_likes'] ) ? 1 : 0 );

		$is_donation = ! empty( $data['is_donation'] );
		update_post_meta( $post_id, 'one_story_is_donation', $is_donation ? 1 : 0 );

		if ( $is_donation ) {
			update_post_meta( $post_id, 'one_story_fundraising_goal', self::sanitize_meta( $data['fundraising_goal'] ?? 0, 'one_story_fundraising_goal', 'number' ) );
			update_post_meta( $post_id, 'one_story_end_date', self::sanitize_meta( $data['end_date'] ?? '', 'one_story_end_date', 'string' ) );
			update_post_meta( $post_id, 'one_story_upi_id', self::sanitize_meta( $data['upi_id'] ?? '', 'one_story_upi_id', 'string' ) );
			update_post_meta( $post_id, 'one_story_payment_link', self::sanitize_meta( $data['payment_link'] ?? '', 'one_story_payment_link', 'string' ) );

			if ( ! metadata_exists( 'post', $post_id, 'one_story_amount_raised' ) ) {
				update_post_meta( $post_id, 'one_story_amount_raised', 0 );
			}
			if ( ! metadata_exists( 'post', $post_id, 'one_story_donor_count' ) ) {
				update_post_meta( $post_id, 'one_story_donor_count', 0 );
			}
		} else {
			delete_post_meta( $post_id, 'one_story_fundraising_goal' );
			delete_post_meta( $post_id, 'one_story_end_date' );
			delete_post_meta( $post_id, 'one_story_upi_id' );
			delete_post_meta( $post_id, 'one_story_payment_link' );
		}
	}

	/**
	 * Format end date for datetime-local input.
	 *
	 * @param string $stored Stored datetime.
	 */
	public static function format_end_date_for_input( $stored ) {
		if ( ! $stored ) {
			return '';
		}
		$ts = strtotime( $stored );
		if ( ! $ts ) {
			return '';
		}
		return wp_date( 'Y-m-d\TH:i', $ts );
	}
}
