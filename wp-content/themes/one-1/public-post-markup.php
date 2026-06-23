<?php
/**
 * Public temporary story view markup (guests via token).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$row   = one1_get_public_story_share_row();
$token = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : '';
?>
<div class="homie-homepage homie-auth one-public-story-page">
	<header class="homie-auth-header">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo-link">
			<svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<path d="M2 3h6a4 4 0 0 1 4 4v14a4 4 0 0 0-4-4H2z" />
				<path d="M22 3h-6a4 4 0 0 0-4 4v14a4 4 0 0 1 4-4h6z" />
			</svg>
			<span class="logo-text">Sent One</span>
		</a>
	</header>

	<main class="homie-auth-main one-public-story-main">
		<div class="homie-auth-card one-public-story-card">
			<?php if ( ! $row ) : ?>
				<h1 class="homie-auth-title"><?php esc_html_e( 'This link has expired', 'one' ); ?></h1>
				<p class="homie-auth-lead">
					<?php esc_html_e( 'Public share links are valid for 24 hours. Ask the person who shared this post for a new link.', 'one' ); ?>
				</p>
				<p class="homie-auth-footer-text">
					<a class="homie-auth-link" href="<?php echo esc_url( one1_login_url() ); ?>"><?php esc_html_e( 'Log in to Sent One', 'one' ); ?></a>
				</p>
			<?php else : ?>
				<?php
				$post_id    = (int) $row['post_id'];
				$expires_ts = strtotime( $row['expires_at'] . ' UTC' );
				?>
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
							'skin'    => 'homie',
							'block'   => true,
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>
	</main>
</div>
