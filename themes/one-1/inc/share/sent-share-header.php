<?php
/**
 * Shared top header for member app pages (share, profile, invite).
 *
 * @package one
 *
 * @var bool $one_can_share Whether the user can use network features.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_share_home = home_url( '/' );
$one_can_share  = isset( $one_can_share ) ? (bool) $one_can_share : false;
?>
<header class="sent-share-header">
	<div class="sent-share-header__inner">
		<a href="<?php echo esc_url( $one_share_home ); ?>" class="sent-share-brand">
			<svg class="sent-share-brand__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<path d="M2 3h6a4 4 0 0 1 4 4v14a4 4 0 0 0-4-4H2z" />
				<path d="M22 3h-6a4 4 0 0 0-4 4v14a4 4 0 0 1 4-4h6z" />
			</svg>
			<span class="sent-share-brand__name"><?php esc_html_e( 'Sent One', 'one' ); ?></span>
		</a>
		<div class="sent-share-header__actions">
			<?php if ( is_user_logged_in() ) : ?>
				<?php one1_render_user_menu( 'share' ); ?>
			<?php else : ?>
				<a class="sent-share-header__login-link" href="<?php echo esc_url( function_exists( 'one1_login_url' ) ? one1_login_url() : wp_login_url() ); ?>">
					<?php esc_html_e( 'Log in', 'one' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>
