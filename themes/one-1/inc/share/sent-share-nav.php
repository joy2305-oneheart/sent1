<?php
/**
 * Shared left navigation for member app pages (share, profile, invite).
 *
 * @package one
 *
 * @var string $one_nav_active Active slug: share|about|profile|invite.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_nav_active   = isset( $one_nav_active ) ? $one_nav_active : '';
$one_share_url    = function_exists( 'one1_share_page_url' ) ? one1_share_page_url() : home_url( '/share/' );
$one_about_url    = function_exists( 'one1_about_page_url' ) ? one1_about_page_url() : home_url( '/about/' );
$one_profile_url  = function_exists( 'one1_profile_page_url' ) ? one1_profile_page_url() : home_url( '/profile/' );
$one_invite_url   = function_exists( 'one1_invite_page_url' ) ? one1_invite_page_url() : home_url( '/invite/' );
$one_share_uid    = get_current_user_id();
$one_can_share    = function_exists( 'sin_is_network_approved' ) && sin_is_network_approved( $one_share_uid );
$one_is_pu        = function_exists( 'sin_is_pu' ) && sin_is_pu( $one_share_uid );
?>
<aside class="sent-share-sidebar sent-share-sidebar--left" aria-label="<?php esc_attr_e( 'Main navigation', 'one' ); ?>">
	<div class="sent-share-nav-panel">
		<div class="sent-share-nav-panel__user">
			<?php echo get_avatar( $one_share_uid, 48, '', '', array( 'class' => 'sent-share-nav-panel__avatar' ) ); ?>
			<div class="sent-share-nav-panel__user-meta">
				<p class="sent-share-nav-panel__user-name"><?php echo esc_html( wp_get_current_user()->display_name ); ?></p>
			</div>
		</div>
		<p class="sent-share-nav-panel__label"><?php esc_html_e( 'Navigation', 'one' ); ?></p>
		<a class="sent-share-nav-link<?php echo 'share' === $one_nav_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $one_share_url ); ?>"<?php echo 'share' === $one_nav_active ? ' aria-current="page"' : ''; ?>>
			<?php one1_render_nav_icon( 'share', 'one-nav-icon sent-share-nav-link__icon' ); ?>
			<?php esc_html_e( 'Share', 'one' ); ?>
		</a>
		<a class="sent-share-nav-link<?php echo 'about' === $one_nav_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $one_about_url ); ?>"<?php echo 'about' === $one_nav_active ? ' aria-current="page"' : ''; ?>>
			<?php one1_render_nav_icon( 'about', 'one-nav-icon sent-share-nav-link__icon' ); ?>
			<?php esc_html_e( 'About', 'one' ); ?>
		</a>
		<a class="sent-share-nav-link<?php echo 'profile' === $one_nav_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $one_profile_url ); ?>"<?php echo 'profile' === $one_nav_active ? ' aria-current="page"' : ''; ?>>
			<?php one1_render_nav_icon( 'profile', 'one-nav-icon sent-share-nav-link__icon' ); ?>
			<?php esc_html_e( 'Profile', 'one' ); ?>
		</a>
		<?php if ( $one_is_pu ) : ?>
			<a class="sent-share-nav-link<?php echo 'invite' === $one_nav_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $one_invite_url ); ?>"<?php echo 'invite' === $one_nav_active ? ' aria-current="page"' : ''; ?>>
				<?php one1_render_nav_icon( 'invite', 'one-nav-icon sent-share-nav-link__icon' ); ?>
				<?php esc_html_e( 'Invite', 'one' ); ?>
			</a>
		<?php endif; ?>
	</div>
</aside>
