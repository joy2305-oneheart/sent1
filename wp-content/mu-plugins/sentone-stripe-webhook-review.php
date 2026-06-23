<?php
/**
 * Plugin Name: SentOne Stripe webhooks (summary log)
 * Description: POST JSON body only — no Stripe-Signature or other auth. Logs email, name, metadata, amount, currency, status summary. **Not for production** (endpoint is public).
 *
 * Endpoint:
 *   POST /wp-json/sentone/v1/stripe-payment-review
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return string
 */
function sentone_stripe_review_get_log_path() {
	$base_dir = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : dirname( __FILE__, 2 );

	if ( function_exists( 'wp_upload_dir' ) ) {
		$uploads = wp_upload_dir( null, false );
		if ( is_array( $uploads ) && empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ) {
			$base_dir = $uploads['basedir'];
		}
	}

	$log_dir = trailingslashit( $base_dir ) . 'sentone-logs';
	if ( ! is_dir( $log_dir ) ) {
		wp_mkdir_p( $log_dir );
	}

	return trailingslashit( $log_dir ) . 'stripe-payment-review.log';
}

/**
 * @param string $message Full line.
 */
function sentone_stripe_review_log( $message ) {
	$line = '[' . gmdate( 'c' ) . '] ' . $message;

	error_log( $line );

	$log_path = sentone_stripe_review_get_log_path();
	if ( ! empty( $log_path ) ) {
		file_put_contents( $log_path, $line . PHP_EOL, FILE_APPEND | LOCK_EX );
	}
}

/**
 * @param mixed $value
 * @return int
 */
function sentone_stripe_review_to_positive_int( $value ) {
	if ( is_int( $value ) ) {
		return $value > 0 ? $value : 0;
	}
	if ( is_string( $value ) ) {
		$digits = preg_replace( '/\D+/', '', $value );
		return $digits !== '' ? (int) $digits : 0;
	}
	return 0;
}

/**
 * @param array<string,mixed> $metadata
 * @return array{story_id:int,user_id:int}
 */
function sentone_stripe_review_extract_ids_from_metadata( array $metadata ) {
	$story_id = 0;
	$user_id  = 0;

	$story_keys = array( 'story_id', 'storyid', 'story', 'post_id', 'campaign_id' );
	$user_keys  = array( 'user_id', 'userid', 'user', 'donor_user_id', 'customer_user_id' );

	foreach ( $metadata as $k => $v ) {
		$key = strtolower( str_replace( array( '-', ' ' ), '_', (string) $k ) );
		if ( in_array( $key, $story_keys, true ) ) {
			$story_id = max( $story_id, sentone_stripe_review_to_positive_int( $v ) );
		}
		if ( in_array( $key, $user_keys, true ) ) {
			$user_id = max( $user_id, sentone_stripe_review_to_positive_int( $v ) );
		}
	}

	return array(
		'story_id' => $story_id,
		'user_id'  => $user_id,
	);
}

/**
 * Collect metadata from wrapper payload/body so story_id and user_id can be read from payload metadata.
 *
 * @param array<string,mixed> $root
 * @param array<string,mixed> $inner
 * @return array<string,string>
 */
function sentone_stripe_review_collect_payload_metadata( array $root, array $inner = array() ) {
	$metadata = array();

	$append_metadata = static function ( $source ) use ( &$metadata ) {
		if ( ! is_array( $source ) ) {
			return;
		}
		foreach ( $source as $k => $v ) {
			$metadata[ (string) $k ] = is_scalar( $v ) ? (string) $v : wp_json_encode( $v );
		}
	};

	if ( isset( $root['metadata'] ) && is_array( $root['metadata'] ) ) {
		$append_metadata( $root['metadata'] );
	}

	if ( isset( $root['payload'] ) ) {
		if ( is_array( $root['payload'] ) && isset( $root['payload']['metadata'] ) && is_array( $root['payload']['metadata'] ) ) {
			$append_metadata( $root['payload']['metadata'] );
		} elseif ( is_string( $root['payload'] ) ) {
			$decoded = json_decode( $root['payload'], true );
			if ( is_array( $decoded ) && isset( $decoded['metadata'] ) && is_array( $decoded['metadata'] ) ) {
				$append_metadata( $decoded['metadata'] );
			}
		}
	}

	if ( isset( $inner['metadata'] ) && is_array( $inner['metadata'] ) ) {
		$append_metadata( $inner['metadata'] );
	}

	return $metadata;
}

/**
 * Unwrap WP Full Pay / custom logs where the real body lives in `payload` (JSON string or object).
 *
 * @param array<string,mixed> $root
 * @return array<string,mixed>
 */
function sentone_stripe_review_get_inner_data( array $root ) {
	if ( ! isset( $root['payload'] ) ) {
		return $root;
	}
	if ( is_string( $root['payload'] ) ) {
		$decoded = json_decode( $root['payload'], true );
		return is_array( $decoded ) ? $decoded : array();
	}
	if ( is_array( $root['payload'] ) ) {
		return $root['payload'];
	}
	return $root;
}

/**
 * @param array<string,mixed> $inner
 * @param array<string,mixed> $root
 * @return array<string,mixed>
 */
function sentone_stripe_review_summarize_wpfs_payload( array $inner, array $root = array() ) {
	$summary = array(
		'event_id'            => null,
		'type'                => 'wpfs_form_payload',
		'livemode'            => null,
		'email'               => null,
		'customer_name'       => null,
		'metadata'            => array(),
		'amount'              => null,
		'currency'            => null,
		'status'              => null,
		'form_name'           => null,
		'frequency'           => null,
		'payment_intent_id'   => null,
		'payment_id'          => null,
		'charge_id'           => null,
		'story_id'            => 0,
		'user_id'             => 0,
	);

	$pi = isset( $inner['stripePaymentIntent'] ) && is_array( $inner['stripePaymentIntent'] ) ? $inner['stripePaymentIntent'] : array();

	if ( ! empty( $inner['email'] ) && is_string( $inner['email'] ) ) {
		$summary['email'] = $inner['email'];
	}
	if ( isset( $inner['formName'] ) && is_string( $inner['formName'] ) ) {
		$summary['form_name'] = $inner['formName'];
	}
	if ( isset( $inner['frequency'] ) && is_string( $inner['frequency'] ) ) {
		$summary['frequency'] = $inner['frequency'];
	}
	if ( isset( $inner['amount'] ) ) {
		$summary['amount'] = $inner['amount'];
	}
	if ( isset( $inner['currency'] ) && is_string( $inner['currency'] ) ) {
		$summary['currency'] = $inner['currency'];
	}

	if ( isset( $pi['id'] ) && is_string( $pi['id'] ) ) {
		$summary['payment_intent_id'] = $pi['id'];
		$summary['payment_id']        = $pi['id'];
	}
	if ( isset( $pi['livemode'] ) ) {
		$summary['livemode'] = (bool) $pi['livemode'];
	}
	if ( isset( $pi['amount'] ) && null === $summary['amount'] ) {
		$summary['amount'] = $pi['amount'];
	}
	if ( empty( $summary['currency'] ) && isset( $pi['currency'] ) && is_string( $pi['currency'] ) ) {
		$summary['currency'] = $pi['currency'];
	}
	if ( isset( $pi['status'] ) && is_string( $pi['status'] ) ) {
		$summary['status'] = $pi['status'];
	}

	if ( isset( $pi['metadata'] ) && is_array( $pi['metadata'] ) ) {
		foreach ( $pi['metadata'] as $k => $v ) {
			$summary['metadata'][ (string) $k ] = is_scalar( $v ) ? (string) $v : wp_json_encode( $v );
		}
	}

	if ( isset( $inner['metadata'] ) && is_array( $inner['metadata'] ) ) {
		foreach ( $inner['metadata'] as $k => $v ) {
			$summary['metadata'][ (string) $k ] = is_scalar( $v ) ? (string) $v : wp_json_encode( $v );
		}
	}

	$payload_metadata = sentone_stripe_review_collect_payload_metadata( $root, $inner );
	foreach ( $payload_metadata as $k => $v ) {
		$summary['metadata'][ (string) $k ] = (string) $v;
	}

	if ( empty( $summary['email'] ) && ! empty( $summary['metadata']['customer_email'] ) ) {
		$summary['email'] = (string) $summary['metadata']['customer_email'];
	}
	if ( empty( $summary['customer_name'] ) && ! empty( $summary['metadata']['customer_name'] ) ) {
		$summary['customer_name'] = (string) $summary['metadata']['customer_name'];
	}

	$lc = isset( $pi['latest_charge'] ) && is_array( $pi['latest_charge'] ) ? $pi['latest_charge'] : null;
	if ( $lc ) {
		if ( ! empty( $lc['id'] ) && is_string( $lc['id'] ) ) {
			$summary['charge_id'] = $lc['id'];
		}
		if ( isset( $lc['billing_details'] ) && is_array( $lc['billing_details'] ) ) {
			$bd = $lc['billing_details'];
			if ( empty( $summary['email'] ) && ! empty( $bd['email'] ) && is_string( $bd['email'] ) ) {
				$summary['email'] = $bd['email'];
			}
			if ( empty( $summary['customer_name'] ) && ! empty( $bd['name'] ) && is_string( $bd['name'] ) ) {
				$summary['customer_name'] = $bd['name'];
			}
		}
	}

	if ( isset( $inner['rawPlaceholders'] ) && is_array( $inner['rawPlaceholders'] ) ) {
		$rp = $inner['rawPlaceholders'];
		if ( empty( $summary['email'] ) && isset( $rp['%CUSTOMER_EMAIL%'] ) && is_string( $rp['%CUSTOMER_EMAIL%'] ) ) {
			$summary['email'] = $rp['%CUSTOMER_EMAIL%'];
		}
		if ( empty( $summary['customer_name'] ) && isset( $rp['%CUSTOMERNAME%'] ) && is_string( $rp['%CUSTOMERNAME%'] ) ) {
			$summary['customer_name'] = $rp['%CUSTOMERNAME%'];
		}
		if ( empty( $summary['event_id'] ) && isset( $rp['%TRANSACTION_ID%'] ) && is_string( $rp['%TRANSACTION_ID%'] ) ) {
			$summary['event_id'] = $rp['%TRANSACTION_ID%'];
		}
		if ( isset( $rp['%STORY_ID%'] ) ) {
			$summary['story_id'] = sentone_stripe_review_to_positive_int( $rp['%STORY_ID%'] );
		}
		if ( isset( $rp['%USER_ID%'] ) ) {
			$summary['user_id'] = sentone_stripe_review_to_positive_int( $rp['%USER_ID%'] );
		}
	}

	$ids         = sentone_stripe_review_extract_ids_from_metadata( $summary['metadata'] );
	$payload_ids = sentone_stripe_review_extract_ids_from_metadata( $payload_metadata );

	if ( (int) $payload_ids['story_id'] > 0 ) {
		$summary['story_id'] = (int) $payload_ids['story_id'];
	} elseif ( $summary['story_id'] <= 0 ) {
		$summary['story_id'] = (int) $ids['story_id'];
	}

	if ( (int) $payload_ids['user_id'] > 0 ) {
		$summary['user_id'] = (int) $payload_ids['user_id'];
	} elseif ( $summary['user_id'] <= 0 ) {
		$summary['user_id'] = (int) $ids['user_id'];
	}

	return $summary;
}

/**
 * @param array<string,mixed> $event_arr
 * @return array<string,mixed>
 */
function sentone_stripe_review_summarize_event( array $event_arr ) {
	$inner = sentone_stripe_review_get_inner_data( $event_arr );

	if ( ! isset( $inner['data']['object'] ) && ( isset( $inner['stripePaymentIntent'] ) || isset( $inner['formName'] ) || ( isset( $inner['email'] ) && isset( $inner['amount'] ) ) ) ) {
		return sentone_stripe_review_summarize_wpfs_payload( $inner, $event_arr );
	}

	$summary = array(
		'event_id'            => isset( $inner['id'] ) ? $inner['id'] : ( isset( $event_arr['id'] ) ? $event_arr['id'] : null ),
		'type'                => isset( $inner['type'] ) ? $inner['type'] : ( isset( $event_arr['type'] ) ? $event_arr['type'] : null ),
		'livemode'            => isset( $inner['livemode'] ) ? $inner['livemode'] : ( isset( $event_arr['livemode'] ) ? $event_arr['livemode'] : null ),
		'email'               => null,
		'customer_name'       => null,
		'metadata'            => array(),
		'amount'              => null,
		'currency'            => null,
		'status'              => null,
		'form_name'           => null,
		'frequency'           => null,
		'payment_intent_id'   => null,
		'payment_id'          => null,
		'charge_id'           => null,
		'story_id'            => 0,
		'user_id'             => 0,
	);

	$obj = array();
	if ( isset( $inner['data']['object'] ) && is_array( $inner['data']['object'] ) ) {
		$obj = $inner['data']['object'];
	}
	if ( empty( $obj ) ) {
		return $summary;
	}

	if ( isset( $obj['metadata'] ) && is_array( $obj['metadata'] ) ) {
		foreach ( $obj['metadata'] as $k => $v ) {
			$summary['metadata'][ (string) $k ] = is_scalar( $v ) ? (string) $v : wp_json_encode( $v );
		}
	}

	$payload_metadata = sentone_stripe_review_collect_payload_metadata( $event_arr, $inner );
	foreach ( $payload_metadata as $k => $v ) {
		$summary['metadata'][ (string) $k ] = (string) $v;
	}

	if ( isset( $obj['amount'] ) ) {
		$summary['amount'] = $obj['amount'];
	} elseif ( isset( $obj['amount_received'] ) ) {
		$summary['amount'] = $obj['amount_received'];
	} elseif ( isset( $obj['amount_due'] ) ) {
		$summary['amount'] = $obj['amount_due'];
	}

	if ( isset( $obj['currency'] ) && is_string( $obj['currency'] ) ) {
		$summary['currency'] = $obj['currency'];
	}
	if ( isset( $obj['status'] ) && is_string( $obj['status'] ) ) {
		$summary['status'] = $obj['status'];
	}
	if ( ! empty( $obj['receipt_email'] ) && is_string( $obj['receipt_email'] ) ) {
		$summary['email'] = $obj['receipt_email'];
	}
	if ( ! empty( $obj['customer_email'] ) && is_string( $obj['customer_email'] ) ) {
		$summary['email'] = $obj['customer_email'];
	}

	if ( isset( $obj['id'] ) && is_string( $obj['id'] ) ) {
		$obj_id = (string) $obj['id'];
		if ( strpos( $obj_id, 'pi_' ) === 0 ) {
			$summary['payment_intent_id'] = $obj_id;
			$summary['payment_id']        = $obj_id;
		} elseif ( strpos( $obj_id, 'ch_' ) === 0 ) {
			$summary['charge_id'] = $obj_id;
		}
	}
	if ( isset( $obj['payment_intent'] ) && is_string( $obj['payment_intent'] ) ) {
		$summary['payment_intent_id'] = $obj['payment_intent'];
		$summary['payment_id']        = $obj['payment_intent'];
	}

	if ( isset( $obj['billing_details'] ) && is_array( $obj['billing_details'] ) ) {
		$bd = $obj['billing_details'];
		if ( empty( $summary['email'] ) && ! empty( $bd['email'] ) && is_string( $bd['email'] ) ) {
			$summary['email'] = $bd['email'];
		}
		if ( ! empty( $bd['name'] ) && is_string( $bd['name'] ) ) {
			$summary['customer_name'] = $bd['name'];
		}
	}

	if ( isset( $obj['charges']['data'] ) && is_array( $obj['charges']['data'] ) && ! empty( $obj['charges']['data'][0] ) && is_array( $obj['charges']['data'][0] ) ) {
		$ch = $obj['charges']['data'][0];
		if ( empty( $summary['email'] ) && isset( $ch['billing_details']['email'] ) && is_string( $ch['billing_details']['email'] ) ) {
			$summary['email'] = $ch['billing_details']['email'];
		}
		if ( empty( $summary['customer_name'] ) && isset( $ch['billing_details']['name'] ) && is_string( $ch['billing_details']['name'] ) ) {
			$summary['customer_name'] = $ch['billing_details']['name'];
		}
		if ( null === $summary['amount'] && isset( $ch['amount'] ) ) {
			$summary['amount'] = $ch['amount'];
		}
		if ( empty( $summary['currency'] ) && isset( $ch['currency'] ) && is_string( $ch['currency'] ) ) {
			$summary['currency'] = $ch['currency'];
		}
		if ( empty( $summary['status'] ) && isset( $ch['status'] ) && is_string( $ch['status'] ) ) {
			$summary['status'] = $ch['status'];
		}
		if ( empty( $summary['charge_id'] ) && isset( $ch['id'] ) && is_string( $ch['id'] ) ) {
			$summary['charge_id'] = $ch['id'];
		}
		if ( isset( $ch['payment_intent'] ) && is_string( $ch['payment_intent'] ) && empty( $summary['payment_intent_id'] ) ) {
			$summary['payment_intent_id'] = $ch['payment_intent'];
			$summary['payment_id']        = $ch['payment_intent'];
		}
		if ( empty( $summary['metadata'] ) && isset( $ch['metadata'] ) && is_array( $ch['metadata'] ) ) {
			foreach ( $ch['metadata'] as $k => $v ) {
				$summary['metadata'][ (string) $k ] = is_scalar( $v ) ? (string) $v : wp_json_encode( $v );
			}
		}
	}

	$ids         = sentone_stripe_review_extract_ids_from_metadata( $summary['metadata'] );
	$payload_ids = sentone_stripe_review_extract_ids_from_metadata( $payload_metadata );

	$summary['story_id'] = (int) ( $payload_ids['story_id'] > 0 ? $payload_ids['story_id'] : $ids['story_id'] );
	$summary['user_id']  = (int) ( $payload_ids['user_id'] > 0 ? $payload_ids['user_id'] : $ids['user_id'] );

	return $summary;
}

/**
 * @param mixed $custom_fields_decoded
 * @return array{story_id:int,user_id:int}
 */
if ( ! function_exists( 'sentone_wpfs_extract_ids_from_custom_fields' ) ) :
function sentone_wpfs_extract_ids_from_custom_fields( $custom_fields_decoded ) {
	$story_id = 0;
	$user_id  = 0;
	if ( ! is_array( $custom_fields_decoded ) ) {
		return array( 'story_id' => 0, 'user_id' => 0 );
	}
	foreach ( $custom_fields_decoded as $field ) {
		if ( ! is_array( $field ) ) {
			continue;
		}
		$label = isset( $field['label'] ) ? strtolower( trim( (string) $field['label'] ) ) : '';
		$label = trim( $label, "{} \t\n\r\0\x0B" );
		$label = str_replace( ' ', '_', $label );
		$label = preg_replace( '/_+/', '_', $label );
		$value = isset( $field['value'] ) ? (string) $field['value'] : '';
		$digits = preg_replace( '/\D+/', '', $value );
		if ( $digits === '' ) {
			continue;
		}
		$num = (int) $digits;
		if ( $label === 'story_id' ) {
			$story_id = $num;
		} elseif ( $label === 'user_id' ) {
			$user_id = $num;
		}
	}
	return array( 'story_id' => $story_id, 'user_id' => $user_id );
}
endif;

/**
 * Idempotently update story + user meta using webhook metadata first (story_id/user_id), with DB fallback.
 *
 * @param object|null          $donation WPFS donations row (optional).
 * @param array<string,mixed>  $summary  webhook summary.
 * @param array<string,mixed>  $event_arr full webhook body.
 * @return void
 */
if ( ! function_exists( 'sentone_sync_story_and_user_meta_from_wpfs_donation' ) ) :
function sentone_sync_story_and_user_meta_from_wpfs_donation( $donation, array $summary, array $event_arr = array() ) {
	$pi = isset( $summary['payment_intent_id'] ) && is_string( $summary['payment_intent_id'] ) ? $summary['payment_intent_id'] : '';
	if ( $pi === '' && is_object( $donation ) && isset( $donation->stripePaymentIntentID ) ) {
		$pi = (string) $donation->stripePaymentIntentID;
	}
	$charge_id = isset( $summary['charge_id'] ) && is_string( $summary['charge_id'] ) ? $summary['charge_id'] : '';
	$event_id  = isset( $summary['event_id'] ) && is_string( $summary['event_id'] ) ? $summary['event_id'] : '';
	$payment_reference = $pi !== '' ? $pi : ( $charge_id !== '' ? $charge_id : $event_id );
	if ( $payment_reference === '' ) {
		return;
	}

	$story_id = isset( $summary['story_id'] ) ? (int) $summary['story_id'] : 0;
	$user_id  = isset( $summary['user_id'] ) ? (int) $summary['user_id'] : 0;

	if ( ( $story_id <= 0 || $user_id <= 0 ) && is_object( $donation ) ) {
		$custom_fields_raw = isset( $donation->customFields ) ? (string) $donation->customFields : '';
		$custom_fields     = $custom_fields_raw !== '' ? json_decode( $custom_fields_raw, true ) : null;
		$ids               = sentone_wpfs_extract_ids_from_custom_fields( $custom_fields );
		if ( $story_id <= 0 ) {
			$story_id = (int) $ids['story_id'];
		}
		if ( $user_id <= 0 ) {
			$user_id = (int) $ids['user_id'];
		}
	}

	if ( $story_id <= 0 ) {
		return;
	}

	$status = isset( $summary['status'] ) ? strtolower( (string) $summary['status'] ) : '';
	if ( $status !== '' && ! in_array( $status, array( 'succeeded', 'requires_capture', 'paid' ), true ) ) {
		return;
	}

	$amount_cents = 0;
	if ( isset( $summary['amount'] ) && is_numeric( $summary['amount'] ) ) {
		$amount_cents = (int) $summary['amount'];
	} elseif ( is_object( $donation ) && isset( $donation->amount ) ) {
		$amount_cents = (int) $donation->amount;
	}
	$amount_dollars = $amount_cents > 0 ? round( $amount_cents / 100, 2 ) : 0.0;

	$currency = isset( $summary['currency'] ) && is_string( $summary['currency'] ) ? $summary['currency'] : null;
	if ( empty( $currency ) && is_object( $donation ) && isset( $donation->currency ) ) {
		$currency = (string) $donation->currency;
	}

	$email = isset( $summary['email'] ) && is_string( $summary['email'] ) ? $summary['email'] : null;
	if ( empty( $email ) && is_object( $donation ) && isset( $donation->email ) ) {
		$email = (string) $donation->email;
	}

	$name = isset( $summary['customer_name'] ) && is_string( $summary['customer_name'] ) ? $summary['customer_name'] : null;
	if ( empty( $name ) && is_object( $donation ) && isset( $donation->name ) ) {
		$name = (string) $donation->name;
	}

	$created       = time();
	$processed_key = '_story_processed_payment_refs';
	$history_key   = '_story_donation_history';
	$donor_ids_key = '_story_donor_ids';

	$processed_raw = get_post_meta( $story_id, $processed_key, true );
	$processed     = is_string( $processed_raw ) ? json_decode( $processed_raw, true ) : $processed_raw;
	if ( ! is_array( $processed ) ) {
		$processed = array();
	}
	if ( in_array( $payment_reference, $processed, true ) ) {
		return;
	}
	$processed[] = $payment_reference;
	update_post_meta( $story_id, $processed_key, wp_json_encode( $processed ) );

	$donor_ids_raw = get_post_meta( $story_id, $donor_ids_key, true );
	$donor_ids     = is_string( $donor_ids_raw ) ? json_decode( $donor_ids_raw, true ) : $donor_ids_raw;
	if ( ! is_array( $donor_ids ) ) {
		$donor_ids = array();
	}
	$donor_key = '';
	if ( $user_id > 0 ) {
		$donor_key = 'user:' . $user_id;
	} elseif ( ! empty( $email ) && is_string( $email ) ) {
		$donor_key = 'email:' . strtolower( trim( $email ) );
	} else {
		$donor_key = 'payment:' . $payment_reference;
	}
	if ( $donor_key !== '' && ! in_array( $donor_key, $donor_ids, true ) ) {
		$donor_ids[] = $donor_key;
	}
	update_post_meta( $story_id, $donor_ids_key, wp_json_encode( $donor_ids ) );
	update_post_meta( $story_id, '_story_donors', count( $donor_ids ) );

	$history_raw = get_post_meta( $story_id, $history_key, true );
	$history     = is_string( $history_raw ) ? json_decode( $history_raw, true ) : $history_raw;
	if ( ! is_array( $history ) ) {
		$history = array();
	}

	$history_item = array(
		'event_id'                 => $event_id,
		'event_type'               => isset( $summary['type'] ) ? (string) $summary['type'] : null,
		'livemode'                 => isset( $summary['livemode'] ) ? (bool) $summary['livemode'] : null,
		'story_id'                 => (string) $story_id,
		'user_id'                  => $user_id > 0 ? (string) $user_id : null,
		'amount_dollars'           => $amount_dollars,
		'amount_cents'             => $amount_cents,
		'email'                    => $email,
		'name'                     => $name,
		'currency'                 => $currency,
		'status'                   => isset( $summary['status'] ) ? (string) $summary['status'] : null,
		'stripe_charge_id'         => $charge_id !== '' ? $charge_id : null,
		'stripe_payment_intent_id' => $pi !== '' ? $pi : null,
		'payment_id'               => isset( $summary['payment_id'] ) ? (string) $summary['payment_id'] : ( $pi !== '' ? $pi : null ),
		'reference'                => $payment_reference,
		'metadata'                 => isset( $summary['metadata'] ) && is_array( $summary['metadata'] ) ? $summary['metadata'] : array(),
		'webhook_payload'          => $event_arr,
		'created'                  => $created,
	);

	$history[] = $history_item;
	if ( count( $history ) > 100 ) {
		$history = array_slice( $history, -100 );
	}
	update_post_meta( $story_id, $history_key, wp_json_encode( $history ) );

	$sum = 0.0;
	foreach ( $history as $h ) {
		if ( is_array( $h ) && isset( $h['amount_dollars'] ) && is_numeric( $h['amount_dollars'] ) ) {
			$sum += (float) $h['amount_dollars'];
		}
	}
	update_post_meta( $story_id, '_story_raised', $sum );
	update_post_meta( $story_id, 'one_story_amount_raised', $sum );
	update_post_meta( $story_id, 'one_story_donor_count', count( $donor_ids ) );
	update_post_meta( $story_id, '_story_last_payment', wp_json_encode( $history_item ) );
	update_post_meta( $story_id, '_story_last_payment_intent_id', $pi );
	update_post_meta( $story_id, '_story_last_charge_id', $charge_id );

	if ( $user_id > 0 ) {
		$user_key = '_sentone_donation_history';
		$user_raw = get_user_meta( $user_id, $user_key, true );
		$user_hist = is_string( $user_raw ) ? json_decode( $user_raw, true ) : $user_raw;
		if ( ! is_array( $user_hist ) ) {
			$user_hist = array();
		}

		$user_hist[] = array(
			'event_id'                 => $event_id,
			'event_type'               => isset( $summary['type'] ) ? (string) $summary['type'] : null,
			'story_id'                 => (string) $story_id,
			'story_title'              => (string) get_the_title( $story_id ),
			'amount_dollars'           => $amount_dollars,
			'amount_cents'             => $amount_cents,
			'currency'                 => $currency,
			'status'                   => isset( $summary['status'] ) ? (string) $summary['status'] : null,
			'stripe_charge_id'         => $charge_id !== '' ? $charge_id : null,
			'stripe_payment_intent_id' => $pi !== '' ? $pi : null,
			'payment_id'               => isset( $summary['payment_id'] ) ? (string) $summary['payment_id'] : ( $pi !== '' ? $pi : null ),
			'reference'                => $payment_reference,
			'metadata'                 => isset( $summary['metadata'] ) && is_array( $summary['metadata'] ) ? $summary['metadata'] : array(),
			'created'                  => $created,
		);

		if ( count( $user_hist ) > 300 ) {
			$user_hist = array_slice( $user_hist, -300 );
		}
		update_user_meta( $user_id, $user_key, wp_json_encode( $user_hist ) );
		update_user_meta( $user_id, '_sentone_last_donation', wp_json_encode( end( $user_hist ) ) );
	}
}
endif;

/**
 * @param string $route_slug stripe-payment-review.
 * @return WP_REST_Response
 */
function sentone_stripe_review_process_verified_webhook( WP_REST_Request $request, $route_slug ) {
	$log_prefix = '[sentone-' . $route_slug . ']';

	if ( 'GET' === $request->get_method() ) {
		return new WP_REST_Response(
			array(
				'code'    => 'rest_no_route',
				'message' => 'Page not found.',
				'data'    => array( 'status' => 404 ),
			),
			404
		);
	}

	$payload = $request->get_body();
	if ( '' === $payload ) {
		$payload = file_get_contents( 'php://input' );
	}
	if ( false === $payload ) {
		$payload = '';
	}

	$event_arr = json_decode( $payload, true );
	if ( ! is_array( $event_arr ) ) {
		sentone_stripe_review_log( $log_prefix . ' Body is not valid JSON.' );
		return new WP_REST_Response( array( 'error' => 'Invalid JSON body' ), 400 );
	}

	$summary = sentone_stripe_review_summarize_event( $event_arr );

	$pi_id = isset( $summary['payment_intent_id'] ) && is_string( $summary['payment_intent_id'] ) ? $summary['payment_intent_id'] : '';
	$donation = null;

	if ( $pi_id !== '' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'fullstripe_donations';
		$donation = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE stripePaymentIntentID=%s", $pi_id )
		);
		if ( ! $donation ) {
			sentone_stripe_review_log( $log_prefix . ' No WPFS donation record found for payment_intent_id=' . $pi_id . ' (continuing with webhook metadata).' );
		}
	}

	// Always sync from webhook metadata (story_id/user_id), with DB fallback when available.
	sentone_sync_story_and_user_meta_from_wpfs_donation( $donation, $summary, $event_arr );

	$log_data = array(
		'route'   => $route_slug,
		'time'    => gmdate( 'c' ),
		'ip'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		'summary' => $summary,
	);

	sentone_stripe_review_log( $log_prefix . ' ' . wp_json_encode( $log_data, JSON_UNESCAPED_SLASHES ) );

	return new WP_REST_Response(
		array(
			'ok'    => true,
			'route' => $route_slug,
			'event' => $summary,
			'log'   => sentone_stripe_review_get_log_path(),
		),
		200
	);
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'sentone/v1',
			'/stripe-payment-review',
			array(
				'methods'             => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
				'callback'            => function ( WP_REST_Request $request ) {
					return sentone_stripe_review_process_verified_webhook( $request, 'stripe-payment-review' );
				},
				'permission_callback' => '__return_true',
			)
		);
	}
);
