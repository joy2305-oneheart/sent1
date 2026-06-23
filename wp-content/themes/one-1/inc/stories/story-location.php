<?php
/**
 * Story location helpers.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google Places API key (filterable; or ONE_GOOGLE_PLACES_API_KEY in wp-config).
 */
function one1_get_places_api_key() {
	if ( defined( 'ONE_GOOGLE_PLACES_API_KEY' ) && ONE_GOOGLE_PLACES_API_KEY ) {
		return (string) ONE_GOOGLE_PLACES_API_KEY;
	}
	$key = (string) get_option( 'one_google_places_api_key', '' );
	/**
	 * Filter the Google Places API key used for story location autocomplete.
	 *
	 * @param string $key API key.
	 */
	return (string) apply_filters( 'one_story_places_api_key', $key );
}

/**
 * Format location label for display.
 *
 * @param array<string, mixed> $meta Story meta from One_Story_Meta::get_all().
 * @return string
 */
function one1_format_story_location( $meta ) {
	if ( ! empty( $meta['location_label'] ) ) {
		return (string) $meta['location_label'];
	}
	$city  = isset( $meta['city'] ) ? trim( (string) $meta['city'] ) : '';
	$state = isset( $meta['state_region'] ) ? trim( (string) $meta['state_region'] ) : '';
	if ( $city && $state ) {
		return $city . ', ' . $state;
	}
	return $city ?: $state;
}

/**
 * Google Maps search URL for a location label.
 *
 * @param string $label Location label.
 * @return string
 */
function one1_story_location_maps_url( $label ) {
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $label );
}

/**
 * Render location tag markup (standalone block).
 *
 * @param array<string, mixed> $meta Story meta.
 */
function one1_render_story_location( $meta ) {
	$label = one1_format_story_location( $meta );
	if ( $label === '' ) {
		return;
	}
	?>
	<p class="one-story-location">
		<span class="one-story-location__icon" aria-hidden="true">📍</span>
		<?php echo esc_html( $label ); ?>
	</p>
	<?php
}

/**
 * Render inline location next to journey meta (navigation icon).
 *
 * @param array<string, mixed> $meta Story meta.
 */
function one1_render_story_location_inline( $meta ) {
	$label = one1_format_story_location( $meta );
	if ( $label === '' ) {
		return;
	}
	$maps_url = one1_story_location_maps_url( $label );
	?>
	<span class="one-story-location--inline">
		<span class="sent-share-card__meta-sep" aria-hidden="true">·</span>
		<a class="one-story-location__link" href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer">
			<span class="material-symbols-outlined one-story-location__nav-icon" aria-hidden="true">near_me</span>
			<span class="one-story-location__text"><?php echo esc_html( $label ); ?></span>
		</a>
	</span>
	<?php
}
