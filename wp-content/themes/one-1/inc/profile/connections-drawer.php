<?php
/**
 * Following / Followers drawer panel.
 *
 * @package one
 *
 * @var int $one_profile_uid Profile user ID.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_profile_uid   = isset( $one_profile_uid ) ? (int) $one_profile_uid : get_current_user_id();
$one_followers     = one1_get_follower_ids( $one_profile_uid );
$one_following     = one1_get_following_ids( $one_profile_uid );
$one_invite_url    = function_exists( 'one1_invite_page_url' ) ? one1_invite_page_url() : home_url( '/invite/' );
$one_drawer_id     = 'one-connections-drawer-' . wp_unique_id();
?>
<div class="one-connections-drawer" data-one-connections-drawer hidden aria-hidden="true">
	<div class="one-connections-drawer__backdrop" data-one-connections-drawer-close tabindex="-1"></div>
	<aside
		class="one-connections-drawer__panel"
		id="<?php echo esc_attr( $one_drawer_id ); ?>"
		role="dialog"
		aria-modal="true"
		aria-labelledby="<?php echo esc_attr( $one_drawer_id ); ?>-title"
	>
		<header class="one-connections-drawer__header">
			<h2 id="<?php echo esc_attr( $one_drawer_id ); ?>-title" class="one-connections-drawer__title"><?php esc_html_e( 'Connections', 'one' ); ?></h2>
			<button type="button" class="one-connections-drawer__close" data-one-connections-drawer-close aria-label="<?php esc_attr_e( 'Close', 'one' ); ?>">
				<span class="material-symbols-outlined" aria-hidden="true">close</span>
			</button>
		</header>

		<div class="one-connections-tabs" data-one-connections-tabs role="tablist">
			<button type="button" class="one-connections-tabs__btn is-active" role="tab" aria-selected="true" data-one-connections-tab="followers">
				<?php esc_html_e( 'Followers', 'one' ); ?>
				<span class="one-connections-tabs__count"><?php echo esc_html( (string) count( $one_followers ) ); ?></span>
			</button>
			<button type="button" class="one-connections-tabs__btn" role="tab" aria-selected="false" data-one-connections-tab="following">
				<?php esc_html_e( 'Following', 'one' ); ?>
				<span class="one-connections-tabs__count"><?php echo esc_html( (string) count( $one_following ) ); ?></span>
			</button>
		</div>

		<div class="one-connections-drawer__body">
			<div class="one-connections-panel is-active" data-one-connections-panel="followers" role="tabpanel">
				<?php
				one1_render_connection_user_list(
					$one_followers,
					__( 'No followers yet. People who join through your invite will appear here.', 'one' )
				);
				?>
			</div>
			<div class="one-connections-panel" data-one-connections-panel="following" role="tabpanel" hidden>
				<?php
				one1_render_connection_user_list(
					$one_following,
					__( 'You are not following anyone yet. People who invited you will appear here.', 'one' )
				);
				?>
			</div>
		</div>

		<footer class="one-connections-drawer__footer">
			<a class="one-connections-drawer__hub" href="<?php echo esc_url( $one_invite_url ); ?>"><?php esc_html_e( 'Invite someone', 'one' ); ?></a>
		</footer>
	</aside>
</div>
