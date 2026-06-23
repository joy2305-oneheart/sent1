<?php
/**
 * Full Stripe donation form embedding for story posts.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** WP Full Pay form name used for story donations. */
const ONE1_DONATION_FORM_NAME = 'SentOneDt';

/**
 * Whether the Full Stripe plugin and SentOneDt form shortcode are available.
 */
function one1_has_donation_payment_form() {
	return shortcode_exists( 'fullstripe_form' );
}

/**
 * Resolve inline shortcode type for a WP Full Pay form (donation vs payment table).
 *
 * @param string $form_name Form name in WP Full Pay.
 * @return string MM_WPFS::FORM_TYPE_INLINE_DONATION or MM_WPFS::FORM_TYPE_INLINE_PAYMENT.
 */
function one1_fullstripe_resolve_inline_form_type( $form_name ) {
	$form_name = (string) $form_name;
	if ( '' === $form_name ) {
		return 'inline_payment';
	}

	global $wpdb;
	$donation_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT donationFormID FROM {$wpdb->prefix}fullstripe_donation_forms WHERE name = %s LIMIT 1",
			$form_name
		)
	);

	if ( ! empty( $donation_id ) ) {
		return class_exists( 'MM_WPFS' ) ? MM_WPFS::FORM_TYPE_INLINE_DONATION : 'inline_donation';
	}

	return class_exists( 'MM_WPFS' ) ? MM_WPFS::FORM_TYPE_INLINE_PAYMENT : 'inline_payment';
}

/**
 * Stripe rejects empty payment_method_types; WP Full Pay can send one when paymentMethods is blank in the DB.
 *
 * Direct Stripe connections disable automatic_payment_methods in the plugin before this filter runs,
 * so we must supply explicit payment_method_types (not rely on automatic_payment_methods).
 *
 * @param array<string, mixed> $params PaymentIntent parameters.
 * @return array<string, mixed>
 */
function one1_fullstripe_fix_payment_intent_parameters( $params ) {
	if ( ! is_array( $params ) ) {
		return $params;
	}

	$types = $params['payment_method_types'] ?? null;
	if ( is_string( $types ) && '' !== $types ) {
		$decoded = json_decode( $types, true );
		if ( is_array( $decoded ) ) {
			$types = $decoded;
		}
	}

	$invalid = '' === $types
		|| null === $types
		|| ( is_array( $types ) && 0 === count( $types ) );

	if ( ! $invalid ) {
		return $params;
	}

	unset( $params['automatic_payment_methods'] );
	$params['payment_method_types'] = array( 'card', 'link' );

	return $params;
}
add_filter( 'fullstripe_payment_intent_parameters', 'one1_fullstripe_fix_payment_intent_parameters', 99 );

/**
 * Whether a story post is a donation campaign.
 *
 * @param int $post_id Post ID.
 */
function one1_story_is_donation( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return false;
	}
	return (bool) get_post_meta( $post_id, 'one_story_is_donation', true );
}

/**
 * Render inline Full Stripe payment form for a donation story.
 *
 * @param int    $post_id Post ID.
 * @param string $context single|card|public.
 */
function one1_render_story_donation_form( $post_id, $context = 'single' ) {
	$post_id = (int) $post_id;
	if ( ! in_array( $context, array( 'single', 'public' ), true ) || ! one1_story_is_donation( $post_id ) || ! one1_has_donation_payment_form() ) {
		return;
	}

	$user_id = get_current_user_id();
	one1_donation_form_set_context( $post_id, $user_id );
	?>
	<div
		class="one-story-donation-form"
		data-story-id="<?php echo esc_attr( (string) $post_id ); ?>"
		<?php if ( $user_id > 0 ) : ?>
			data-user-id="<?php echo esc_attr( (string) $user_id ); ?>"
		<?php endif; ?>
	>
		<?php
		$form_type = one1_fullstripe_resolve_inline_form_type( ONE1_DONATION_FORM_NAME );
		echo do_shortcode(
			sprintf(
				'[fullstripe_form name="%s" type="%s"]',
				esc_attr( ONE1_DONATION_FORM_NAME ),
				esc_attr( $form_type )
			)
		);
		?>
	</div>
	<?php
}

/**
 * Enqueue donation form assets on pages that may show donation stories.
 */
function one1_enqueue_donation_form_assets() {
	if ( is_admin() ) {
		return;
	}

	$load = one1_is_share_page()
		|| one1_is_profile_page()
		|| ( function_exists( 'one1_is_single_story_page' ) && one1_is_single_story_page() )
		|| ( function_exists( 'one1_is_public_story_page' ) && one1_is_public_story_page() );

	if ( ! $load ) {
		return;
	}

	$ver  = '1.0.1';
	$base = get_stylesheet_directory_uri();

	wp_enqueue_style(
		'one-donation-form',
		$base . '/assets/stories/one-donation-form.css',
		array(),
		$ver
	);

	wp_enqueue_script(
		'one-donation-form',
		$base . '/assets/stories/one-donation-form.js',
		array( 'jquery' ),
		$ver,
		true
	);

	wp_localize_script(
		'one-donation-form',
		'oneDonationForm',
		array(
			'userId' => get_current_user_id(),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'one1_enqueue_donation_form_assets', 30 );

