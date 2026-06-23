<?php
/**
 * Public temporary story view markup (guests via token).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$row          = one1_get_public_story_share_row();
$one_can_share = false;
?>
<div class="sent-share-page one-public-story-page">
	<?php require get_stylesheet_directory() . '/inc/share/sent-share-header.php'; ?>

	<div class="sent-share-layout sent-share-layout--app sent-share-layout--centered">
		<main class="sent-share-main one-public-story-main">
			<?php if ( ! $row ) : ?>
				<section class="sent-share-notice one-public-story-notice">
					<h1 class="sent-share-notice__title"><?php esc_html_e( 'This link has expired', 'one' ); ?></h1>
					<p class="sent-share-notice__text">
						<?php esc_html_e( 'Public share links are valid for 24 hours. Ask the person who shared this post for a new link.', 'one' ); ?>
					</p>
					<?php
					one1_button(
						array(
							'url'     => one1_login_url(),
							'label'   => __( 'Log in to Sent One', 'one' ),
							'variant' => 'primary',
							'skin'    => 'share',
							'block'   => true,
						)
					);
					?>
				</section>
			<?php else : ?>
				<?php
				$post_id    = (int) $row['post_id'];
				$expires_ts = strtotime( $row['expires_at'] . ' UTC' );
				?>
				<article class="one-public-story-panel">
					<p class="one-public-story-eyebrow"><?php esc_html_e( 'Shared journey update', 'one' ); ?></p>
					<?php if ( $expires_ts ) : ?>
						<p class="one-public-story-expiry" role="status">
							<?php
							printf(
								/* translators: %s: human-readable time remaining */
								esc_html__( 'This link expires %s.', 'one' ),
								esc_html( human_time_diff( time(), $expires_ts ) )
							);
							?>
						</p>
					<?php endif; ?>

					<div class="one-public-story-shell">
						<?php one1_render_story_view( $post_id, 0, 'public' ); ?>
					</div>

					<div class="one-public-story-login-cta">
						<p><?php esc_html_e( 'Sign in to comment, like, or follow this journey.', 'one' ); ?></p>
						<?php
						one1_button(
							array(
								'url'     => one1_login_url( get_permalink( $post_id ) ),
								'label'   => __( 'Log in to Sent One', 'one' ),
								'variant' => 'primary',
								'skin'    => 'share',
								'block'   => true,
							)
						);
						?>
					</div>
				</article>
			<?php endif; ?>
		</main>
	</div>
</div>
