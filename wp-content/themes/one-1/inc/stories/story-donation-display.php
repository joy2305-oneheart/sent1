<?php
/**
 * Shared donation display fragments.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render campaign end date when set (legacy paragraph style).
 *
 * @param array<string, mixed> $meta Story meta.
 * @param string               $class Optional extra class.
 */
function one1_render_story_donation_end_date( $meta, $class = '' ) {
	$end = ! empty( $meta['end_date'] ) ? $meta['end_date'] : '';
	$ts  = $end ? strtotime( $end ) : false;
	if ( ! $ts ) {
		return;
	}
	$classes = 'one-story-donation-end';
	if ( $class !== '' ) {
		$classes .= ' ' . $class;
	}
	?>
	<p class="<?php echo esc_attr( $classes ); ?>">
		<?php
		printf(
			/* translators: %s: formatted date */
			esc_html__( 'Campaign ends: %s', 'one' ),
			esc_html( date_i18n( get_option( 'date_format' ), $ts ) )
		);
		?>
	</p>
	<?php
}

/**
 * Formatted campaign end date for display.
 *
 * @param array<string, mixed> $meta Story meta.
 * @return string Empty when no end date.
 */
function one1_story_donation_end_label( $meta ) {
	$end = ! empty( $meta['end_date'] ) ? $meta['end_date'] : '';
	$ts  = $end ? strtotime( $end ) : false;
	if ( ! $ts ) {
		return '';
	}
	return date_i18n( 'M j, Y', $ts );
}

/**
 * Render structured donation summary (raised, goal, progress, end pill).
 *
 * @param int                  $post_id Post ID.
 * @param array<string, mixed> $meta    Story meta.
 * @param string               $context card|single|public.
 */
function one1_render_story_donation_summary( $post_id, $meta, $context = 'card' ) {
	$post_id = (int) $post_id;
	$goal    = (float) ( $meta['fundraising_goal'] ?? 0 );
	$raised  = (float) ( $meta['amount_raised'] ?? 0 );
	$pct     = $goal > 0 ? min( 100, (int) round( ( $raised / $goal ) * 100 ) ) : 0;
	$end_lbl = one1_story_donation_end_label( $meta );

	$wrap_class = 'sent-share-donation__summary';
	if ( in_array( $context, array( 'single', 'public' ), true ) ) {
		$wrap_class .= ' sent-share-donation__summary--single';
	}
	?>
	<div
		class="<?php echo esc_attr( $wrap_class ); ?>"
		data-one-donation-summary
		data-story-id="<?php echo esc_attr( (string) (int) $post_id ); ?>"
	>
		<?php if ( $goal > 0 ) : ?>
			<div class="sent-share-donation__amount-row">
				<span class="sent-share-donation__raised"><?php echo esc_html( one1_format_story_money( $raised ) ); ?></span>
				<span class="sent-share-donation__goal">
					<?php
					printf(
						/* translators: %s: goal amount */
						esc_html__( 'of %s goal', 'one' ),
						esc_html( one1_format_story_money( $goal ) )
					);
					?>
				</span>
				<?php if ( ! empty( $meta['donor_count'] ) ) : ?>
					<span class="sent-share-donation__donors">
						<?php echo esc_html( sprintf( _n( '%d donor', '%d donors', (int) $meta['donor_count'], 'one' ), (int) $meta['donor_count'] ) ); ?>
					</span>
				<?php endif; ?>
			</div>
			<div class="sent-share-donation__progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) $pct ); ?>" aria-label="<?php esc_attr_e( 'Fundraising progress', 'one' ); ?>">
				<span class="sent-share-donation__progress-bar" style="width: <?php echo esc_attr( (string) $pct ); ?>%;"></span>
			</div>
		<?php else : ?>
			<p class="sent-share-donation__label-only"><?php esc_html_e( 'Fundraising campaign', 'one' ); ?></p>
		<?php endif; ?>

		<?php if ( $end_lbl !== '' ) : ?>
			<div class="sent-share-donation__meta-row">
				<span class="sent-share-donation__end-pill">
					<span class="material-symbols-outlined" aria-hidden="true">schedule</span>
					<?php
					printf(
						/* translators: %s: formatted end date */
						esc_html__( 'Ends %s', 'one' ),
						esc_html( $end_lbl )
					);
					?>
				</span>
			</div>
		<?php endif; ?>

		<div class="sent-share-donation__cta">
			<?php
			if ( in_array( $context, array( 'single', 'public' ), true ) && function_exists( 'one1_has_donation_payment_form' ) && one1_has_donation_payment_form() ) {
				one1_render_story_donation_form( $post_id, $context );
			} else {
				one1_render_story_donate_button( $post_id, $meta, array( 'block' => true ) );
			}
			?>
		</div>
	</div>
	<?php
}
