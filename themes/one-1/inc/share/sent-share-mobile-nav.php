<?php
/**
 * Shared bottom navigation for member app pages.
 *
 * @package one
 *
 * @var string $one_nav_active Active slug: share|about|profile|invite|dashboard.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_nav_active    = isset( $one_nav_active ) ? $one_nav_active : '';
$one_share_url     = function_exists( 'one1_share_page_url' ) ? one1_share_page_url() : home_url( '/share/' );
$one_about_url     = function_exists( 'one1_about_page_url' ) ? one1_about_page_url() : home_url( '/about/' );
$one_profile_url   = function_exists( 'one1_profile_page_url' ) ? one1_profile_page_url() : home_url( '/profile/' );
$one_can_compose   = function_exists( 'one_story_user_can_submit' ) && one_story_user_can_submit();
$one_is_dashboard  = in_array( $one_nav_active, array( 'dashboard', 'share' ), true );
?>
<nav class="sent-share-mobile-nav" aria-label="<?php esc_attr_e( 'Mobile navigation', 'one' ); ?>">
	<a href="<?php echo esc_url( $one_share_url ); ?>" class="sent-share-mobile-nav__link<?php echo $one_is_dashboard ? ' is-active' : ''; ?>" aria-label="<?php esc_attr_e( 'Dashboard', 'one' ); ?>"<?php echo $one_is_dashboard ? ' aria-current="page"' : ''; ?>>
		<?php one1_render_nav_icon( 'dashboard', 'one-nav-icon sent-share-mobile-nav__icon' ); ?>
		<span class="sent-share-mobile-nav__label"><?php esc_html_e( 'Dashboard', 'one' ); ?></span>
	</a>
	<a href="<?php echo esc_url( $one_about_url ); ?>" class="sent-share-mobile-nav__link<?php echo 'about' === $one_nav_active ? ' is-active' : ''; ?>" aria-label="<?php esc_attr_e( 'About', 'one' ); ?>"<?php echo 'about' === $one_nav_active ? ' aria-current="page"' : ''; ?>>
		<?php one1_render_nav_icon( 'about', 'one-nav-icon sent-share-mobile-nav__icon' ); ?>
		<span class="sent-share-mobile-nav__label"><?php esc_html_e( 'About', 'one' ); ?></span>
	</a>
	<a href="<?php echo esc_url( $one_profile_url ); ?>" class="sent-share-mobile-nav__link<?php echo 'profile' === $one_nav_active ? ' is-active' : ''; ?>" aria-label="<?php esc_attr_e( 'Profile', 'one' ); ?>"<?php echo 'profile' === $one_nav_active ? ' aria-current="page"' : ''; ?>>
		<?php one1_render_nav_icon( 'profile', 'one-nav-icon sent-share-mobile-nav__icon' ); ?>
		<span class="sent-share-mobile-nav__label"><?php esc_html_e( 'Profile', 'one' ); ?></span>
	</a>
	<?php if ( $one_can_compose ) : ?>
	<button type="button" class="sent-share-mobile-nav__link sent-share-mobile-nav__link--share" data-one-open-composer aria-label="<?php esc_attr_e( 'Share', 'one' ); ?>">
		<span class="sent-share-mobile-nav__share-pill">
			<?php one1_render_nav_icon( 'add', 'one-nav-icon sent-share-mobile-nav__icon sent-share-mobile-nav__icon--share' ); ?>
		</span>
		<span class="sent-share-mobile-nav__label"><?php esc_html_e( 'Share', 'one' ); ?></span>
	</button>
	<?php else : ?>
	<a href="<?php echo esc_url( $one_share_url ); ?>" class="sent-share-mobile-nav__link sent-share-mobile-nav__link--share" aria-label="<?php esc_attr_e( 'Share', 'one' ); ?>">
		<span class="sent-share-mobile-nav__share-pill">
			<?php one1_render_nav_icon( 'share', 'one-nav-icon sent-share-mobile-nav__icon sent-share-mobile-nav__icon--share' ); ?>
		</span>
		<span class="sent-share-mobile-nav__label"><?php esc_html_e( 'Share', 'one' ); ?></span>
	</a>
	<?php endif; ?>
</nav>
