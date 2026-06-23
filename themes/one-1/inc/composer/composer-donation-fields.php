<?php
/**
 * Donation fields for the post composer modal.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_urgency = isset( $one_story_values['urgency'] ) ? $one_story_values['urgency'] : 'standard';
$one_goal    = isset( $one_story_values['fundraising_goal'] ) ? $one_story_values['fundraising_goal'] : '';
$one_end     = isset( $one_story_values['end_date'] ) ? $one_story_values['end_date'] : '';
$one_end_val = $one_end ? One_Story_Meta::format_end_date_for_input( $one_end ) : '';
if ( $one_end_val && strlen( $one_end_val ) > 10 ) {
	$one_end_val = substr( $one_end_val, 0, 10 );
}
?>
<div class="one-composer__donation-panel" data-one-story-donation-panel>
	<div class="one-composer__field">
		<span class="one-composer__field-label"><?php esc_html_e( 'Urgency level', 'one' ); ?></span>
		<div class="one-composer__segmented one-composer__segmented--urgency" data-one-composer-urgency role="group" aria-label="<?php esc_attr_e( 'Urgency level', 'one' ); ?>">
			<?php foreach ( one_story_urgency_options() as $value => $label ) : ?>
				<label class="one-composer__segment one-composer__segment--<?php echo esc_attr( $value ); ?><?php echo $one_urgency === $value ? ' is-active' : ''; ?>">
					<input type="radio" name="one_story_urgency" value="<?php echo esc_attr( $value ); ?>" <?php checked( $one_urgency, $value ); ?> />
					<span><?php echo esc_html( $label ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="one-composer__field">
		<span class="one-composer__field-label"><?php esc_html_e( 'Fundraising goal', 'one' ); ?></span>
		<div class="one-composer__goal-presets" role="group" aria-label="<?php esc_attr_e( 'Suggested goals', 'one' ); ?>">
			<button type="button" class="one-composer__goal-preset" data-one-goal-preset="500"><?php esc_html_e( '$500', 'one' ); ?></button>
			<button type="button" class="one-composer__goal-preset" data-one-goal-preset="1000"><?php esc_html_e( '$1,000', 'one' ); ?></button>
			<button type="button" class="one-composer__goal-preset" data-one-goal-preset="2500"><?php esc_html_e( '$2,500', 'one' ); ?></button>
		</div>
		<div class="one-composer__input-wrap one-composer__input-wrap--money">
			<span class="one-composer__input-prefix" aria-hidden="true">$</span>
			<input
				type="number"
				name="one_story_fundraising_goal"
				id="one-composer-goal"
				class="one-composer__input"
				min="0"
				step="1"
				value="<?php echo esc_attr( $one_goal ); ?>"
				placeholder="<?php esc_attr_e( 'Custom amount', 'one' ); ?>"
				inputmode="decimal"
				data-one-donation-required
				data-one-goal-input
			/>
		</div>
	</div>

	<div class="one-composer__field">
		<label class="one-composer__field-label" for="one-composer-end"><?php esc_html_e( 'Campaign end date (optional)', 'one' ); ?></label>
		<div class="one-composer__date-field" data-one-composer-date-field>
			<span class="material-symbols-outlined one-composer__date-icon" aria-hidden="true">calendar_today</span>
			<input
				type="date"
				name="one_story_end_date"
				id="one-composer-end"
				class="one-composer__input one-composer__input--date"
				value="<?php echo esc_attr( $one_end_val ); ?>"
				data-one-composer-end-date
			/>
		</div>
	</div>
</div>
