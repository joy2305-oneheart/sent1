<?php
/**
 * Shared bottom navigation for member app pages.
 *
 * @package one
 *
 * @var string $one_nav_active Active slug: share|profile|invite.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_nav_active  = isset( $one_nav_active ) ? $one_nav_active : '';
$one_share_url   = function_exists( 'one1_share_page_url' ) ? one1_share_page_url() : home_url( '/share/' );
$one_profile_url = function_exists( 'one1_profile_page_url' ) ? one1_profile_page_url() : home_url( '/profile/' );
$one_invite_url  = function_exists( 'one1_invite_page_url' ) ? one1_invite_page_url() : home_url( '/invite/' );
$one_is_pu       = function_exists( 'sin_is_pu' ) && sin_is_pu( get_current_user_id() );
?>
<nav class="sent-share-mobile-nav" aria-label="<?php esc_attr_e( 'Mobile navigation', 'one' ); ?>">
	<a href="<?php echo esc_url( $one_share_url ); ?>" class="sent-share-mobile-nav__link<?php echo 'share' === $one_nav_active ? ' is-active' : ''; ?>"<?php echo 'share' === $one_nav_active ? ' aria-current="page"' : ''; ?>>
		<span class="material-symbols-outlined<?php echo 'share' === $one_nav_active ? ' sent-share-nav-link__icon--fill' : ''; ?>" aria-hidden="true">auto_stories</span>
		<span class="sent-share-mobile-nav__label"><?php esc_html_e( 'Feed', 'one' ); ?></span>
	</a>
	<a href="<?php echo esc_url( $one_profile_url ); ?>" class="sent-share-mobile-nav__link<?php echo 'profile' === $one_nav_active ? ' is-active' : ''; ?>"<?php echo 'profile' === $one_nav_active ? ' aria-current="page"' : ''; ?>>
		<span class="material-symbols-outlined<?php echo 'profile' === $one_nav_active ? ' sent-share-nav-link__icon--fill' : ''; ?>" aria-hidden="true">person</span>
		<span class="sent-share-mobile-nav__label"><?php esc_html_e( 'Profile', 'one' ); ?></span>
	</a>
	<?php if ( $one_is_pu ) : ?>
	<a href="<?php echo esc_url( $one_invite_url ); ?>" class="sent-share-mobile-nav__link<?php echo 'invite' === $one_nav_active ? ' is-active' : ''; ?>"<?php echo 'invite' === $one_nav_active ? ' aria-current="page"' : ''; ?>>
		<span class="material-symbols-outlined<?php echo 'invite' === $one_nav_active ? ' sent-share-nav-link__icon--fill' : ''; ?>" aria-hidden="true">mail</span>
		<span class="sent-share-mobile-nav__label"><?php esc_html_e( 'Invite', 'one' ); ?></span>
	</a>
	<?php endif; ?>
</nav>
