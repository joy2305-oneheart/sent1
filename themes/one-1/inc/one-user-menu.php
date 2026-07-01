<?php
/**
 * User account dropdown (avatar) for site headers.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue user menu assets.
 */
function one1_enqueue_user_menu_assets() {
	$ver  = '1.0.0';
	$base = get_stylesheet_directory_uri() . '/assets/homie';

	wp_enqueue_style(
		'one-user-menu',
		$base . '/one-user-menu.css',
		array(),
		$ver
	);

	wp_enqueue_script(
		'one-user-menu',
		$base . '/homie-user-menu.js',
		array(),
		$ver,
		true
	);
}

/**
 * Render avatar dropdown: Dashboard (admin) + Log out.
 *
 * @param string $variant Context: homie|share.
 */
function one1_render_user_menu( $variant = 'homie' ) {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$uid           = get_current_user_id();
	$is_admin      = function_exists( 'one1_is_admin_user' ) && one1_is_admin_user();
	$dashboard_url = function_exists( 'one1_dashboard_url' ) ? one1_dashboard_url() : '';
	$profile_url   = function_exists( 'one1_profile_page_url' ) ? one1_profile_page_url() : '';
	$about_url     = function_exists( 'one1_about_page_url' ) ? one1_about_page_url() : '';
	$logout_url    = wp_logout_url( function_exists( 'one1_login_url' ) ? one1_login_url() : home_url( '/login/' ) );
	$menu_id       = 'one-user-menu-' . wp_unique_id();
	$user          = wp_get_current_user();
	$display_name  = $user->display_name ? $user->display_name : $user->user_login;
	?>
	<div class="one-user-menu one-user-menu--<?php echo esc_attr( $variant ); ?>" data-one-user-menu>
		<button
			type="button"
			class="one-user-menu__toggle"
			aria-expanded="false"
			aria-controls="<?php echo esc_attr( $menu_id ); ?>"
			aria-label="<?php esc_attr_e( 'Account menu', 'one' ); ?>"
		>
			<?php
			echo get_avatar(
				$uid,
				40,
				'',
				'',
				array(
					'class' => 'one-user-menu__avatar',
				)
			);
			?>
			<svg class="one-user-menu__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<polyline points="6 9 12 15 18 9"></polyline>
			</svg>
		</button>
		<div class="one-user-menu__dropdown" id="<?php echo esc_attr( $menu_id ); ?>" role="menu" hidden>
			<p class="one-user-menu__name" role="presentation"><?php echo esc_html( $display_name ); ?></p>
			<?php if ( $about_url ) : ?>
				<a role="menuitem" class="one-user-menu__item" href="<?php echo esc_url( $about_url ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M6 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"></path></svg>
					<?php esc_html_e( 'About', 'one' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $profile_url ) : ?>
				<a role="menuitem" class="one-user-menu__item" href="<?php echo esc_url( $profile_url ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
					<?php esc_html_e( 'Profile', 'one' ); ?>
				</a>
			<?php endif; ?>
			<a role="menuitem" class="one-user-menu__item" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
				<?php esc_html_e( 'Home', 'one' ); ?>
			</a>
			<?php if ( $is_admin && $dashboard_url ) : ?>
				<a role="menuitem" class="one-user-menu__item" href="<?php echo esc_url( $dashboard_url ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
					<?php esc_html_e( 'Dashboard', 'one' ); ?>
				</a>
			<?php endif; ?>
			<a role="menuitem" class="one-user-menu__item one-user-menu__item--logout" href="<?php echo esc_url( $logout_url ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
				<?php esc_html_e( 'Log out', 'one' ); ?>
			</a>
		</div>
	</div>
	<?php
}

/**
 * Homie header actions: Start sharing / Log in + user menu when logged in.
 */
function one1_render_homie_header_actions() {
	$share_url = function_exists( 'one1_share_page_url' ) ? one1_share_page_url() : home_url( '/share/' );

	if ( is_user_logged_in() ) {
		one1_homie_cta_button( $share_url, __( 'Start sharing', 'one' ) );
		return;
	}

	one1_homie_cta_button( one1_login_url(), __( 'Log in', 'one' ) );
}

/**
 * Mobile nav extras (start sharing only; account links live in header avatar menu).
 */
function one1_render_homie_mobile_auth() {
	if ( ! is_user_logged_in() ) {
		echo '<div class="mobile-nav-cta">';
		one1_homie_cta_button( one1_login_url(), __( 'Log in', 'one' ) );
		echo '</div>';
		return;
	}

	$share_url = function_exists( 'one1_share_page_url' ) ? one1_share_page_url() : home_url( '/share/' );
	echo '<div class="mobile-nav-cta">';
	one1_homie_cta_button( $share_url, __( 'Start sharing', 'one' ) );
	echo '</div>';
}
