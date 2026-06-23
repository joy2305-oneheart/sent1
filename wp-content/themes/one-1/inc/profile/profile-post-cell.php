<?php
/**
 * Profile grid cell for a single story post.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render one clickable profile grid tile with post context.
 *
 * @param int $post_id Post ID.
 */
function one1_render_profile_post_cell( $post_id ) {
	$post_id = (int) $post_id;
	$post    = get_post( $post_id );

	if ( ! $post || 'story' !== $post->post_type ) {
		return;
	}

	$meta        = One_Story_Meta::get_all( $post_id );
	$is_donation = ! empty( $meta['is_donation'] );
	$thumb       = get_the_post_thumbnail_url( $post_id, 'medium_large' );
	$title       = one1_story_has_headline( $post_id ) ? get_the_title( $post_id ) : '';
	$preview     = one1_share_post_preview( $post_id );
	$caption     = $title ? $title : $preview;
	$caption     = $caption ? wp_trim_words( $caption, 12, '…' ) : __( 'View post', 'one' );
	$type_label  = $is_donation ? __( 'Donation', 'one' ) : __( 'Journey', 'one' );
	$aria_label  = $title
		? sprintf(
			/* translators: 1: post title, 2: post type */
			__( 'Open post: %1$s (%2$s)', 'one' ),
			$title,
			$type_label
		)
		: sprintf(
			/* translators: %s: post type */
			__( 'Open %s post', 'one' ),
			$type_label
		);
	?>
	<button
		type="button"
		class="one-profile-posts__cell<?php echo $is_donation ? ' one-profile-posts__cell--donation' : ''; ?><?php echo $thumb ? '' : ' one-profile-posts__cell--text'; ?>"
		data-one-open-post="<?php echo esc_attr( (string) $post_id ); ?>"
		aria-label="<?php echo esc_attr( $aria_label ); ?>"
	>
		<div class="one-profile-posts__media">
			<?php if ( $thumb ) : ?>
				<img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy" />
			<?php else : ?>
				<div class="one-profile-posts__text-preview" aria-hidden="true">
					<p><?php echo esc_html( wp_trim_words( $preview ? $preview : get_post_field( 'post_content', $post_id ), 18, '…' ) ); ?></p>
				</div>
			<?php endif; ?>
		</div>

		<div class="one-profile-posts__overlay">
			<span class="one-profile-posts__type"><?php echo esc_html( $type_label ); ?></span>
			<p class="one-profile-posts__caption"><?php echo esc_html( $caption ); ?></p>
		</div>

		<span class="one-profile-posts__open-hint" aria-hidden="true">
			<span class="material-symbols-outlined">open_in_full</span>
		</span>
	</button>
	<?php
}
