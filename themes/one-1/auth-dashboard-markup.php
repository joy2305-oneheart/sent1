<?php
/**
 * Admin dashboard shell (manage_options only).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pending_count = 0;
if ( class_exists( 'SIN_Registration' ) ) {
	$pending_count = (int) SIN_Registration::count_pending_users();
}

$invite_url = function_exists( 'one1_invite_page_url' ) ? one1_invite_page_url() : home_url( '/invite/' );
?>
<div class="homie-homepage homie-auth homie-auth--dashboard sent-share-page">
	<header class="homie-auth-header homie-auth-header--spread">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo-link">
			<svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<path d="M2 3h6a4 4 0 0 1 4 4v14a4 4 0 0 0-4-4H2z" />
				<path d="M22 3h-6a4 4 0 0 0-4 4v14a4 4 0 0 1 4-4h6z" />
			</svg>
			<span class="logo-text">Sent One</span>
		</a>
		<?php one1_render_user_menu( 'homie' ); ?>
	</header>

	<main class="homie-auth-main homie-auth-main--wide">
		<div class="homie-auth-card homie-auth-card--wide">
			<p class="homie-auth-eyebrow"><?php esc_html_e( 'Admin', 'one' ); ?></p>
			<h1 class="homie-auth-title"><?php esc_html_e( 'Dashboard', 'one' ); ?></h1>
			<p class="homie-auth-lead"><?php esc_html_e( 'Manage member approvals and site settings. Members use the public homepage after logging in.', 'one' ); ?></p>

			<div class="homie-dashboard-grid">
				<?php
				one1_button(
					array(
						'url'         => admin_url( 'users.php?page=sin-pending-approvals' ),
						'label'       => __( 'Pending approvals', 'one' ),
						'description' => sprintf(
							/* translators: %d: number of users */
							_n( '%d user waiting', '%d users waiting', $pending_count, 'one' ),
							$pending_count
						),
						'variant'     => 'tile',
						'skin'        => 'homie',
					)
				);
				one1_button(
					array(
						'url'         => $invite_url,
						'label'       => __( 'Invite page', 'one' ),
						'description' => __( 'Preview the member invite experience', 'one' ),
						'variant'     => 'tile',
						'skin'        => 'homie',
					)
				);
				one1_button(
					array(
						'url'         => admin_url(),
						'label'       => __( 'WordPress admin', 'one' ),
						'description' => __( 'Full site administration', 'one' ),
						'variant'     => 'tile',
						'skin'        => 'homie',
					)
				);
				one1_button(
					array(
						'url'         => home_url( '/' ),
						'label'       => __( 'View homepage', 'one' ),
						'description' => __( 'Public landing page', 'one' ),
						'variant'     => 'tile',
						'skin'        => 'homie',
						'tile_muted'  => true,
					)
				);
				?>
			</div>
		</div>
	</main>

	<?php
	$one_nav_active = 'dashboard';
	require get_stylesheet_directory() . '/inc/share/sent-share-mobile-nav.php';
	?>
</div>
