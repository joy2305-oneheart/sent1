<?php
/**
 * Sync story fundraising totals (amount raised, donor count) from WP Full Pay / Stripe.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array{story_id:int,user_id:int}
 */
function one1_donation_form_get_context() {
	if ( ! isset( $GLOBALS['one1_donation_form_context'] ) || ! is_array( $GLOBALS['one1_donation_form_context'] ) ) {
		return array(
			'story_id' => 0,
			'user_id'  => 0,
		);
	}

	return array(
		'story_id' => max( 0, (int) ( $GLOBALS['one1_donation_form_context']['story_id'] ?? 0 ) ),
		'user_id'  => max( 0, (int) ( $GLOBALS['one1_donation_form_context']['user_id'] ?? 0 ) ),
	);
}

/**
 * @param int $story_id Story post ID.
 * @param int $user_id  Donor user ID (0 if guest).
 */
function one1_donation_form_set_context( $story_id, $user_id = 0 ) {
	$GLOBALS['one1_donation_form_context'] = array(
		'story_id' => max( 0, (int) $story_id ),
		'user_id'  => max( 0, (int) $user_id ),
	);
}

/**
 * @param array<string, mixed> $metadata Stripe / WPFS metadata.
 * @return array{story_id:int,user_id:int}
 */
function one1_extract_story_user_ids_from_metadata( array $metadata ) {
	if ( function_exists( 'sentone_stripe_review_extract_ids_from_metadata' ) ) {
		return sentone_stripe_review_extract_ids_from_metadata( $metadata );
	}

	$story_id = 0;
	$user_id  = 0;

	foreach ( $metadata as $k => $v ) {
		$key = strtolower( str_replace( array( '-', ' ' ), '_', (string) $k ) );
		if ( in_array( $key, array( 'story_id', 'storyid', 'story', 'post_id', 'campaign_id' ), true ) ) {
			$story_id = max( $story_id, (int) preg_replace( '/\D+/', '', (string) $v ) );
		}
		if ( in_array( $key, array( 'user_id', 'userid', 'user', 'donor_user_id', 'customer_user_id' ), true ) ) {
			$user_id = max( $user_id, (int) preg_replace( '/\D+/', '', (string) $v ) );
		}
	}

	return array(
		'story_id' => $story_id,
		'user_id'  => $user_id,
	);
}

/**
 * @param array<string, mixed> $params WPFS after-charge params.
 * @return array{story_id:int,user_id:int}
 */
function one1_extract_story_user_ids_from_wpfs_params( array $params ) {
	$metadata = array();

	if ( isset( $params['stripePaymentIntent'] ) && is_object( $params['stripePaymentIntent'] ) && isset( $params['stripePaymentIntent']->metadata ) ) {
		$raw = $params['stripePaymentIntent']->metadata;
		$metadata = is_array( $raw ) ? $raw : (array) $raw;
	}

	$ids = one1_extract_story_user_ids_from_metadata( $metadata );

	if ( isset( $params['rawPlaceholders'] ) && is_array( $params['rawPlaceholders'] ) ) {
		$rp = $params['rawPlaceholders'];
		if ( $ids['story_id'] <= 0 && ! empty( $rp['%STORY_ID%'] ) ) {
			$ids['story_id'] = (int) preg_replace( '/\D+/', '', (string) $rp['%STORY_ID%'] );
		}
		if ( $ids['user_id'] <= 0 && ! empty( $rp['%USER_ID%'] ) ) {
			$ids['user_id'] = (int) preg_replace( '/\D+/', '', (string) $rp['%USER_ID%'] );
		}
	}

	if ( $ids['story_id'] <= 0 && ! empty( $metadata['custom_fields'] ) && function_exists( 'sentone_wpfs_extract_ids_from_custom_fields' ) ) {
		$custom = json_decode( (string) $metadata['custom_fields'], true );
		$from_cf = sentone_wpfs_extract_ids_from_custom_fields( $custom );
		if ( $from_cf['story_id'] > 0 ) {
			$ids['story_id'] = (int) $from_cf['story_id'];
		}
		if ( $from_cf['user_id'] > 0 ) {
			$ids['user_id'] = (int) $from_cf['user_id'];
		}
	}

	if ( $ids['story_id'] <= 0 || $ids['user_id'] <= 0 ) {
		$ctx = one1_donation_form_get_context();
		if ( $ids['story_id'] <= 0 ) {
			$ids['story_id'] = (int) $ctx['story_id'];
		}
		if ( $ids['user_id'] <= 0 ) {
			$ids['user_id'] = (int) $ctx['user_id'];
		}
	}

	return $ids;
}

/**
 * @param array<string, mixed> $params WPFS after-charge params.
 * @return array<string, mixed>|null
 */
function one1_build_donation_summary_from_wpfs_params( array $params ) {
	if ( empty( $params['stripePaymentIntent'] ) || ! is_object( $params['stripePaymentIntent'] ) ) {
		return null;
	}

	$pi = $params['stripePaymentIntent'];
	$status = isset( $pi->status ) ? (string) $pi->status : '';
	if ( $status !== '' && ! in_array( $status, array( 'succeeded', 'requires_capture' ), true ) ) {
		return null;
	}

	$ids = one1_extract_story_user_ids_from_wpfs_params( $params );
	if ( $ids['story_id'] <= 0 ) {
		return null;
	}

	$charge_id = '';
	if ( isset( $pi->latest_charge ) ) {
		if ( is_string( $pi->latest_charge ) ) {
			$charge_id = $pi->latest_charge;
		} elseif ( is_object( $pi->latest_charge ) && isset( $pi->latest_charge->id ) ) {
			$charge_id = (string) $pi->latest_charge->id;
		}
	}

	$metadata = array();
	if ( isset( $pi->metadata ) ) {
		$raw = $pi->metadata;
		$metadata = is_array( $raw ) ? $raw : (array) $raw;
	}

	return array(
		'event_id'            => isset( $pi->id ) ? (string) $pi->id : null,
		'type'                => 'fullstripe_after_charge',
		'livemode'            => isset( $pi->livemode ) ? (bool) $pi->livemode : null,
		'email'               => isset( $params['email'] ) ? (string) $params['email'] : null,
		'customer_name'       => null,
		'metadata'            => $metadata,
		'amount'              => isset( $pi->amount ) ? (int) $pi->amount : null,
		'currency'            => isset( $pi->currency ) ? (string) $pi->currency : null,
		'status'              => $status,
		'form_name'           => isset( $params['formName'] ) ? (string) $params['formName'] : null,
		'payment_intent_id'   => isset( $pi->id ) ? (string) $pi->id : null,
		'payment_id'          => isset( $pi->id ) ? (string) $pi->id : null,
		'charge_id'           => $charge_id,
		'story_id'            => (int) $ids['story_id'],
		'user_id'             => (int) $ids['user_id'],
	);
}

/**
 * @param array<string, mixed> $summary Donation summary (see sentone_sync_story_and_user_meta_from_wpfs_donation).
 */
function one1_sync_story_donation_from_summary( array $summary ) {
	$status = isset( $summary['status'] ) ? strtolower( (string) $summary['status'] ) : '';
	if ( $status !== '' && ! in_array( $status, array( 'succeeded', 'requires_capture', 'paid' ), true ) ) {
		return;
	}

	if ( function_exists( 'sentone_sync_story_and_user_meta_from_wpfs_donation' ) ) {
		sentone_sync_story_and_user_meta_from_wpfs_donation( null, $summary, array() );
		return;
	}

	$story_id = isset( $summary['story_id'] ) ? (int) $summary['story_id'] : 0;
	if ( $story_id <= 0 || 'story' !== get_post_type( $story_id ) ) {
		return;
	}

	$pi = isset( $summary['payment_intent_id'] ) ? (string) $summary['payment_intent_id'] : '';
	if ( $pi === '' ) {
		return;
	}

	$amount_cents = isset( $summary['amount'] ) ? (int) $summary['amount'] : 0;
	$amount       = $amount_cents > 0 ? round( $amount_cents / 100, 2 ) : 0.0;

	$processed_key = '_story_processed_payment_refs';
	$processed_raw = get_post_meta( $story_id, $processed_key, true );
	$processed     = is_string( $processed_raw ) ? json_decode( $processed_raw, true ) : $processed_raw;
	if ( ! is_array( $processed ) ) {
		$processed = array();
	}
	if ( in_array( $pi, $processed, true ) ) {
		return;
	}
	$processed[] = $pi;
	update_post_meta( $story_id, $processed_key, wp_json_encode( $processed ) );

	$raised = (float) get_post_meta( $story_id, 'one_story_amount_raised', true );
	update_post_meta( $story_id, 'one_story_amount_raised', round( $raised + $amount, 2 ) );
	update_post_meta( $story_id, '_story_raised', (float) get_post_meta( $story_id, 'one_story_amount_raised', true ) );

	$donors = (int) get_post_meta( $story_id, 'one_story_donor_count', true );
	update_post_meta( $story_id, 'one_story_donor_count', $donors + 1 );
	update_post_meta( $story_id, '_story_donors', (int) get_post_meta( $story_id, 'one_story_donor_count', true ) );
}

/**
 * @param array<string, mixed> $params WPFS charge hook params.
 */
function one1_on_fullstripe_after_charge( $params ) {
	if ( ! is_array( $params ) ) {
		return;
	}

	$form_name = isset( $params['formName'] ) ? (string) $params['formName'] : '';
	if ( defined( 'ONE1_DONATION_FORM_NAME' ) && ONE1_DONATION_FORM_NAME !== $form_name ) {
		return;
	}

	$summary = one1_build_donation_summary_from_wpfs_params( $params );
	if ( ! is_array( $summary ) ) {
		return;
	}

	one1_sync_story_donation_from_summary( $summary );
}

add_action( 'fullstripe_after_donation_charge', 'one1_on_fullstripe_after_charge', 10, 1 );
add_action( 'fullstripe_after_payment_charge', 'one1_on_fullstripe_after_charge', 10, 1 );

/**
 * @param int $post_id Story post ID.
 * @return array<string, mixed>|null
 */
function one1_get_story_donation_stats( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || 'story' !== get_post_type( $post_id ) ) {
		return null;
	}

	$meta = class_exists( 'One_Story_Meta' ) ? One_Story_Meta::get_all( $post_id ) : array();

	$goal   = (float) ( $meta['fundraising_goal'] ?? 0 );
	$raised = (float) ( $meta['amount_raised'] ?? 0 );
	$donors = (int) ( $meta['donor_count'] ?? 0 );
	$pct    = $goal > 0 ? min( 100, (int) round( ( $raised / $goal ) * 100 ) ) : 0;

	return array(
		'post_id'       => $post_id,
		'amount_raised' => $raised,
		'donor_count'   => $donors,
		'fundraising_goal' => $goal,
		'progress_pct'  => $pct,
		'raised_label'  => function_exists( 'one1_format_story_money' ) ? one1_format_story_money( $raised ) : (string) $raised,
		'goal_label'    => function_exists( 'one1_format_story_money' ) ? one1_format_story_money( $goal ) : (string) $goal,
		'donors_label'  => sprintf(
			_n( '%d donor', '%d donors', $donors, 'one' ),
			$donors
		),
	);
}

/**
 * AJAX: return current raised / donor stats for a story.
 */
function one1_ajax_story_donation_stats() {
	$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
	$stats   = one1_get_story_donation_stats( $post_id );

	if ( ! is_array( $stats ) ) {
		wp_send_json_error( array( 'message' => 'Invalid story.' ), 400 );
	}

	wp_send_json_success( $stats );
}

add_action( 'wp_ajax_one1_story_donation_stats', 'one1_ajax_story_donation_stats' );
add_action( 'wp_ajax_nopriv_one1_story_donation_stats', 'one1_ajax_story_donation_stats' );

/**
 * @param array<string, mixed> $metadata   Existing metadata.
 * @param string               $form_name  Form name.
 * @param array<string, mixed> $params     Form GET parameters.
 * @return array<string, mixed>
 */
function one1_fullstripe_story_donation_metadata( $metadata, $form_name, $params ) {
	if ( ! defined( 'ONE1_DONATION_FORM_NAME' ) || ONE1_DONATION_FORM_NAME !== $form_name ) {
		return $metadata;
	}

	if ( ! is_array( $metadata ) ) {
		$metadata = array();
	}

	$ctx = one1_donation_form_get_context();

	if ( empty( $metadata['story_id'] ) ) {
		$story_id = $ctx['story_id'];
		if ( $story_id <= 0 && is_array( $params ) ) {
			$ids = one1_extract_story_user_ids_from_metadata( $params );
			$story_id = (int) $ids['story_id'];
		}
		if ( $story_id > 0 ) {
			$metadata['story_id'] = (string) $story_id;
		}
	}

	if ( empty( $metadata['user_id'] ) ) {
		$user_id = $ctx['user_id'] > 0 ? $ctx['user_id'] : get_current_user_id();
		if ( $user_id > 0 ) {
			$metadata['user_id'] = (string) $user_id;
		}
	}

	return $metadata;
}

add_filter( 'fullstripe_add_transaction_metadata', 'one1_fullstripe_story_donation_metadata', 10, 3 );

/**
 * Pass updated stats to the browser after a successful inline charge.
 *
 * @param array<string, mixed> $return WPFS JSON response.
 * @return array<string, mixed>
 */
function one1_append_story_stats_to_donation_charge_response( $return ) {
	if ( empty( $return['success'] ) ) {
		return $return;
	}

	$ctx = one1_donation_form_get_context();
	if ( $ctx['story_id'] <= 0 ) {
		return $return;
	}

	$stats = one1_get_story_donation_stats( $ctx['story_id'] );
	if ( is_array( $stats ) ) {
		$return['oneStoryDonationStats'] = $stats;
	}

	return $return;
}

add_filter( 'fullstripe_onetime_donation_charge_return_message', 'one1_append_story_stats_to_donation_charge_response', 20 );
add_filter( 'fullstripe_inline_donation_charge_return_message', 'one1_append_story_stats_to_donation_charge_response', 20 );
add_filter( 'fullstripe_inline_payment_charge_return_message', 'one1_append_story_stats_to_donation_charge_response', 20 );

/**
 * Localize AJAX URL for donation stat refresh.
 */
function one1_localize_donation_sync_script() {
	if ( ! wp_script_is( 'one-donation-form', 'enqueued' ) ) {
		return;
	}

	wp_localize_script(
		'one-donation-form',
		'oneDonationSync',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'action'  => 'one1_story_donation_stats',
		)
	);
}

add_action( 'wp_enqueue_scripts', 'one1_localize_donation_sync_script', 35 );
