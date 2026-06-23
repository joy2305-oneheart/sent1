<?php
/**
 * Shared story meta fields markup (admin + front-end + composer).
 *
 * @package one
 *
 * @var array<string, mixed> $one_story_values Field values.
 * @var string               $one_story_context admin|front|composer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_story_values  = isset( $one_story_values ) ? $one_story_values : array();
$one_story_context = isset( $one_story_context ) ? $one_story_context : 'front';
$one_is_admin      = ( 'admin' === $one_story_context );
$one_is_composer   = ( 'composer' === $one_story_context );
$one_is_front      = ( 'front' === $one_story_context );
$one_is_donation   = ! empty( $one_story_values['is_donation'] );
$one_urgency       = $one_story_values['urgency'] ?? 'standard';
$one_field_id      = static function ( $slug ) use ( $one_story_context ) {
	$suffix = 'admin' === $one_story_context ? '' : ( 'composer' === $one_story_context ? '-composer' : '-front' );
	return 'one-story-' . $slug . $suffix;
};
$one_is_wp_admin   = $one_is_admin && current_user_can( 'manage_options' );
$one_use_post_type = $one_is_front || $one_is_composer;
?>
<div class="one-story-fields" data-one-story-fields>

	<?php if ( ! $one_is_composer ) : ?>
	<section class="one-story-section">
		<header class="one-story-section__head">
			<span class="one-story-section__icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10z"/><circle cx="12" cy="11" r="2.5"/></svg>
			</span>
			<div>
				<h3 class="one-story-section__title"><?php esc_html_e( 'Location', 'one' ); ?></h3>
				<p class="one-story-section__desc"><?php esc_html_e( 'Optional — search for a place to tag on your story.', 'one' ); ?></p>
			</div>
		</header>
		<div class="one-story-section__body">
			<p class="one-story-fields__row">
				<label for="<?php echo esc_attr( $one_field_id( 'location' ) ); ?>"><?php esc_html_e( 'Add location', 'one' ); ?></label>
				<input
					type="text"
					name="one_story_location_label"
					id="<?php echo esc_attr( $one_field_id( 'location' ) ); ?>"
					value="<?php echo esc_attr( $one_story_values['location_label'] ?? '' ); ?>"
					placeholder="<?php esc_attr_e( 'e.g. New York, USA', 'one' ); ?>"
					autocomplete="off"
					<?php echo one1_get_places_api_key() !== '' ? 'data-one-places-autocomplete' : ''; ?>
				/>
				<input type="hidden" name="one_story_location_place_id" value="<?php echo esc_attr( $one_story_values['location_place_id'] ?? '' ); ?>" data-one-location-place-id />
				<input type="hidden" name="one_story_city" value="<?php echo esc_attr( $one_story_values['city'] ?? '' ); ?>" data-one-location-city />
				<input type="hidden" name="one_story_state_region" value="<?php echo esc_attr( $one_story_values['state_region'] ?? '' ); ?>" data-one-location-region />
			</p>
		</div>
	</section>
	<?php endif; ?>

	<section class="one-story-section one-story-section--donation" data-one-story-donation-section <?php echo ( $one_use_post_type && ! $one_is_donation ) ? 'hidden' : ''; ?>>
		<?php if ( ! $one_is_composer ) : ?>
		<header class="one-story-section__head">
			<span class="one-story-section__icon one-story-section__icon--donation" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
			</span>
			<div>
				<h3 class="one-story-section__title"><?php esc_html_e( 'Donation details', 'one' ); ?></h3>
				<p class="one-story-section__desc"><?php esc_html_e( 'Fundraising goal, payment info, and campaign timeline.', 'one' ); ?></p>
			</div>
		</header>
		<?php endif; ?>

		<div class="one-story-section__body">
			<?php if ( $one_is_wp_admin ) : ?>
			<div class="one-story-fields__badges">
				<label class="one-story-check-pill">
					<input type="checkbox" name="one_story_featured" value="1" <?php checked( ! empty( $one_story_values['featured'] ) ); ?> />
					<span><?php esc_html_e( 'Featured', 'one' ); ?></span>
				</label>
				<label class="one-story-check-pill">
					<input type="checkbox" name="one_story_verified" value="1" <?php checked( ! empty( $one_story_values['verified'] ) ); ?> />
					<span><?php esc_html_e( 'Verified', 'one' ); ?></span>
				</label>
			</div>
			<?php endif; ?>

			<?php if ( ! $one_use_post_type ) : ?>
			<label class="one-story-donation-switch" data-one-story-donation-switch>
				<input type="checkbox" name="one_story_is_donation" value="1" data-one-story-donation-toggle <?php checked( $one_is_donation ); ?> />
				<span class="one-story-donation-switch__track" aria-hidden="true"></span>
				<span class="one-story-donation-switch__text">
					<strong><?php esc_html_e( 'This is a donation post', 'one' ); ?></strong>
					<small><?php esc_html_e( 'Show a fundraising goal and end date on your story.', 'one' ); ?></small>
				</span>
			</label>
			<?php endif; ?>

			<div class="one-story-donation-fields" data-one-story-donation-panel <?php echo ( ! $one_use_post_type && ! $one_is_donation ) ? 'hidden' : ''; ?>>
				<div class="one-story-fields__row one-story-fields__row--urgency">
					<span class="one-story-fields__label"><?php esc_html_e( 'Urgency level', 'one' ); ?></span>
					<?php if ( $one_is_front || $one_is_composer ) : ?>
					<div class="one-story-urgency" data-one-story-urgency role="group" aria-label="<?php esc_attr_e( 'Urgency level', 'one' ); ?>">
						<?php foreach ( one_story_urgency_options() as $value => $label ) : ?>
							<label class="one-story-urgency__option one-story-urgency__option--<?php echo esc_attr( $value ); ?>">
								<input type="radio" name="one_story_urgency" value="<?php echo esc_attr( $value ); ?>" <?php checked( $one_urgency, $value ); ?> />
								<span><?php echo esc_html( $label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<?php else : ?>
					<select name="one_story_urgency" id="<?php echo esc_attr( $one_field_id( 'urgency' ) ); ?>" class="one-story-fields__select">
						<?php foreach ( one_story_urgency_options() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $one_urgency, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php endif; ?>
				</div>

				<p class="one-story-fields__row">
					<label for="<?php echo esc_attr( $one_field_id( 'goal' ) ); ?>"><?php esc_html_e( 'Fundraising goal', 'one' ); ?> <span class="one-story-fields__hint"><?php esc_html_e( '(USD)', 'one' ); ?></span></label>
					<div class="one-story-fields__input-wrap one-story-fields__input-wrap--prefix">
						<span class="one-story-fields__prefix" aria-hidden="true">$</span>
						<input type="number" name="one_story_fundraising_goal" id="<?php echo esc_attr( $one_field_id( 'goal' ) ); ?>" min="0" step="0.01" value="<?php echo esc_attr( $one_story_values['fundraising_goal'] ?? '' ); ?>" placeholder="0.00" data-one-donation-required />
					</div>
				</p>
				<?php if ( $one_is_wp_admin ) : ?>
				<div class="one-story-section__body--grid">
					<p class="one-story-fields__row">
						<label for="<?php echo esc_attr( $one_field_id( 'raised' ) ); ?>"><?php esc_html_e( 'Amount raised', 'one' ); ?></label>
						<div class="one-story-fields__input-wrap one-story-fields__input-wrap--prefix">
							<span class="one-story-fields__prefix" aria-hidden="true">$</span>
							<input type="number" name="one_story_amount_raised" id="<?php echo esc_attr( $one_field_id( 'raised' ) ); ?>" min="0" step="0.01" value="<?php echo esc_attr( $one_story_values['amount_raised'] ?? 0 ); ?>" />
						</div>
					</p>
					<p class="one-story-fields__row">
						<label for="<?php echo esc_attr( $one_field_id( 'donors' ) ); ?>"><?php esc_html_e( 'Number of donors', 'one' ); ?></label>
						<input type="number" name="one_story_donor_count" id="<?php echo esc_attr( $one_field_id( 'donors' ) ); ?>" min="0" step="1" value="<?php echo esc_attr( (int) ( $one_story_values['donor_count'] ?? 0 ) ); ?>" />
					</p>
				</div>
				<?php endif; ?>
				<p class="one-story-fields__row">
					<label for="<?php echo esc_attr( $one_field_id( 'end' ) ); ?>"><?php esc_html_e( 'Campaign end date', 'one' ); ?></label>
					<input type="datetime-local" name="one_story_end_date" id="<?php echo esc_attr( $one_field_id( 'end' ) ); ?>" value="<?php echo esc_attr( One_Story_Meta::format_end_date_for_input( $one_story_values['end_date'] ?? '' ) ); ?>" data-one-donation-required />
				</p>
				<?php if ( $one_is_wp_admin ) : ?>
				<p class="one-story-fields__row">
					<label for="<?php echo esc_attr( $one_field_id( 'upi' ) ); ?>"><?php esc_html_e( 'UPI ID', 'one' ); ?></label>
					<input type="text" name="one_story_upi_id" id="<?php echo esc_attr( $one_field_id( 'upi' ) ); ?>" value="<?php echo esc_attr( $one_story_values['upi_id'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'name@bank', 'one' ); ?>" />
				</p>
				<p class="one-story-fields__row">
					<label for="<?php echo esc_attr( $one_field_id( 'payment' ) ); ?>"><?php esc_html_e( 'Payment link', 'one' ); ?></label>
					<input type="url" name="one_story_payment_link" id="<?php echo esc_attr( $one_field_id( 'payment' ) ); ?>" value="<?php echo esc_attr( $one_story_values['payment_link'] ?? '' ); ?>" placeholder="https://" />
				</p>
				<?php endif; ?>
			</div>
		</div>
	</section>
</div>
