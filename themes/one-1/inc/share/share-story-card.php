<?php
/**
 * Single story card for the share feed.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a feed card for one story post.
 *
 * @param int $post_id   Story post ID.
 * @param int $viewer_id Current user ID.
 */
function one1_render_share_story_card( $post_id, $viewer_id ) {
	$post_id   = (int) $post_id;
	$viewer_id = (int) $viewer_id;
	$post      = get_post( $post_id );

	if ( ! $post ) {
		return;
	}

	$author_id = (int) $post->post_author;
	$author    = get_userdata( $author_id );
	$meta      = One_Story_Meta::get_all( $post_id );
	$preview   = one1_share_post_preview( $post_id );
	$thumb     = get_the_post_thumbnail_url( $post_id, 'large' );
	$is_own    = $author_id === $viewer_id;
	$is_donation = ! empty( $meta['is_donation'] );

	$card_classes = 'sent-share-card';
	if ( $is_donation ) {
		$card_classes .= ' sent-share-card--donation';
	}
	?>
	<article class="<?php echo esc_attr( $card_classes ); ?>" data-story-id="<?php echo esc_attr( (string) $post_id ); ?>">
		<div class="sent-share-card__head">
			<div class="sent-share-card__author">
				<?php echo get_avatar( $author_id, 40, '', '', array( 'class' => 'sent-share-card__avatar' ) ); ?>
				<div>
					<h3 class="sent-share-card__name"><?php echo esc_html( $author ? $author->display_name : '' ); ?></h3>
					<p class="sent-share-card__meta">
						<?php
						if ( $is_donation ) {
							printf(
								/* translators: 1: human time diff */
								esc_html__( '%1$s ago · Donation', 'one' ),
								esc_html( human_time_diff( get_post_time( 'U', false, $post_id ), current_time( 'timestamp' ) ) )
							);
						} else {
							printf(
								/* translators: 1: human time diff, 2: journey label */
								esc_html__( '%1$s ago · %2$s', 'one' ),
								esc_html( human_time_diff( get_post_time( 'U', false, $post_id ), current_time( 'timestamp' ) ) ),
								$is_own ? esc_html__( 'Your journey', 'one' ) : esc_html__( 'Journey', 'one' )
							);
							one1_render_story_location_inline( $meta );
						}
						?>
					</p>
				</div>
			</div>
			<?php if ( $is_own && function_exists( 'one1_render_story_owner_actions' ) ) : ?>
				<?php one1_render_story_owner_actions( $post_id ); ?>
			<?php endif; ?>
		</div>

		<?php if ( $is_donation ) : ?>
			<div class="sent-share-donation">
				<?php one1_render_story_donation_summary( $post_id, $meta, 'card' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $thumb ) : ?>
			<div class="sent-share-card__media">
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
					<img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy" class="sent-share-card__img" />
				</a>
			</div>
		<?php endif; ?>

		<div class="sent-share-card__body">
			<?php if ( one1_story_has_headline( $post_id ) ) : ?>
				<h4 class="sent-share-card__title">
					<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
				</h4>
			<?php endif; ?>
			<?php if ( $preview ) : ?>
				<p class="sent-share-card__text<?php echo $thumb ? '' : ' sent-share-card__text--lead'; ?>"><?php echo esc_html( $preview ); ?></p>
			<?php endif; ?>
			<div class="sent-share-card__actions">
				<div class="sent-share-card__actions-primary">
					<?php
					if ( function_exists( 'one1_render_story_view_count' ) ) {
						one1_render_story_view_count( $post_id );
					}
					one1_render_story_support_control( $post_id, $viewer_id );
					?>
				</div>

				<div class="sent-share-card__actions-secondary">
					<?php
					one1_button(
						array(
							'url'           => get_permalink( $post_id ),
							'label'         => __( 'Read more', 'one' ),
							'variant'       => 'action',
							'skin'          => 'share',
							'icon'          => 'material:chat_bubble',
							'icon_position' => 'before',
							'action_style'  => 'gold',
						)
					);
					?>
				</div>
			</div>
		</div>
	</article>
	<?php
}
