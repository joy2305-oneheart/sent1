<?php
/**
 * Location field for the post composer modal.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_location_label = isset( $one_story_values['location_label'] ) ? $one_story_values['location_label'] : '';
$one_has_places     = one1_get_places_api_key() !== '';
?>
<div class="one-composer__location" data-one-composer-location>
	<button type="button" class="one-composer__location-toggle" data-one-composer-location-toggle aria-expanded="false">
		<span class="material-symbols-outlined" aria-hidden="true">location_on</span>
		<?php esc_html_e( 'Add location', 'one' ); ?>
	</button>
	<div class="one-composer__location-panel" data-one-composer-location-panel hidden>
		<label class="one-composer__field-label" for="one-composer-location-input"><?php esc_html_e( 'Search for a place', 'one' ); ?></label>
		<div class="one-composer__input-wrap">
			<input
				type="text"
				name="one_story_location_label"
				id="one-composer-location-input"
				class="one-composer__input"
				value="<?php echo esc_attr( $one_location_label ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. New York, USA', 'one' ); ?>"
				autocomplete="off"
				<?php echo $one_has_places ? 'data-one-places-autocomplete' : ''; ?>
			/>
		</div>
		<input type="hidden" name="one_story_location_place_id" value="<?php echo esc_attr( $one_story_values['location_place_id'] ?? '' ); ?>" data-one-location-place-id />
		<input type="hidden" name="one_story_city" value="<?php echo esc_attr( $one_story_values['city'] ?? '' ); ?>" data-one-location-city />
		<input type="hidden" name="one_story_state_region" value="<?php echo esc_attr( $one_story_values['state_region'] ?? '' ); ?>" data-one-location-region />
		<?php if ( ! $one_has_places ) : ?>
			<p class="one-composer__location-hint"><?php esc_html_e( 'Type a city and region, or ask your site admin to enable Google Places search.', 'one' ); ?></p>
		<?php endif; ?>
		<button type="button" class="one-composer__location-clear" data-one-composer-location-clear hidden>
			<?php esc_html_e( 'Remove location', 'one' ); ?>
		</button>
	</div>
</div>
