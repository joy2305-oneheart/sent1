<?php
/**
 * Story support + donate controls (feed, modal, single).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render heart + count support control (minimal, not button-styled).
 *
 * @param int $post_id   Story post ID.
 * @param int $viewer_id Current user ID.
 */
function one1_render_story_support_control( $post_id, $viewer_id ) {
	$post_id        = (int) $post_id;
	$viewer_id      = (int) $viewer_id;
	$post           = get_post( $post_id );
	$author_id      = $post ? (int) $post->post_author : 0;
	$is_owner       = $author_id === $viewer_id;

	if ( function_exists( 'one1_story_hide_likes' ) && one1_story_hide_likes( $post_id ) && ! $is_owner ) {
		return;
	}

	$support_count  = one1_get_story_support_count( $post_id );
	$user_supported = one1_user_has_supported_story( $post_id, $viewer_id );
	$can_support    = one1_can_user_support_story( $post_id, $viewer_id );
	?>
	<button
		type="button"
		class="sent-share-support sent-share-support--icon-only<?php echo $user_supported ? ' is-active' : ''; ?><?php echo $can_support ? '' : ' is-disabled'; ?>"
		data-one-story-support
		data-post-id="<?php echo esc_attr( (string) $post_id ); ?>"
		data-supported="<?php echo $user_supported ? '1' : '0'; ?>"
		aria-pressed="<?php echo $user_supported ? 'true' : 'false'; ?>"
		aria-label="<?php esc_attr_e( 'Support this post', 'one' ); ?>"
		<?php echo $can_support ? '' : 'disabled'; ?>
	>
		<span class="material-symbols-outlined sent-share-support__icon" aria-hidden="true">favorite</span>
		<span class="sent-share-support__count" data-one-support-count><?php echo esc_html( (string) $support_count ); ?></span>
	</button>
	<?php
}

/**
 * Render Donate CTA for donation posts.
 *
 * @param int                  $post_id Story post ID.
 * @param array<string, mixed> $meta    Story meta (optional).
 * @param array<string, mixed> $args    Button args: block, class.
 */
function one1_render_story_donate_button( $post_id, $meta = null, $args = array() ) {
	$post_id = (int) $post_id;
	if ( null === $meta ) {
		$meta = One_Story_Meta::get_all( $post_id );
	}
	if ( empty( $meta['is_donation'] ) ) {
		return;
	}

	$args = wp_parse_args(
		$args,
		array(
			'block' => false,
			'class' => 'sent-share-donate-btn',
		)
	);

	$has_payment_link = ! empty( $meta['payment_link'] );

	if ( $has_payment_link ) {
		one1_button(
			array(
				'url'     => $meta['payment_link'],
				'label'   => __( 'Donate', 'one' ),
				'variant' => 'primary',
				'skin'    => 'share',
				'block'   => (bool) $args['block'],
				'class'   => $args['class'],
				'attrs'   => array(
					'target' => '_blank',
					'rel'    => 'noopener noreferrer',
				),
			)
		);
		return;
	}
}
