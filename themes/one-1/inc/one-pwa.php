<?php
/**
 * Progressive Web App manifest, service worker, and install hooks.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether PWA assets should load on this front-end request.
 */
function one1_is_pwa_eligible_page() {
	return ! is_admin();
}

/**
 * Register pretty routes for manifest + service worker at site root scope.
 */
function one1_pwa_register_rewrites() {
	add_rewrite_rule( '^sent-one-manifest\.webmanifest$', 'index.php?one1_web_manifest=1', 'top' );
	add_rewrite_rule( '^sent-one-sw\.js$', 'index.php?one1_service_worker=1', 'top' );
}
add_action( 'init', 'one1_pwa_register_rewrites' );

/**
 * Query vars for PWA endpoints.
 *
 * @param string[] $vars Query vars.
 * @return string[]
 */
function one1_pwa_query_vars( $vars ) {
	$vars[] = 'one1_web_manifest';
	$vars[] = 'one1_service_worker';
	return $vars;
}
add_filter( 'query_vars', 'one1_pwa_query_vars' );

/**
 * Flush rewrite rules once after theme update.
 */
function one1_pwa_maybe_flush_rewrites() {
	if ( get_option( 'one1_pwa_rewrite_version', '' ) === '1.2.0' ) {
		return;
	}
	one1_pwa_register_rewrites();
	flush_rewrite_rules( false );
	update_option( 'one1_pwa_rewrite_version', '1.2.0', false );
}
add_action( 'after_setup_theme', 'one1_pwa_maybe_flush_rewrites', 20 );

/**
 * Ensure theme PWA icons exist on disk.
 */
function one1_pwa_ensure_icons() {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return;
	}

	$dir = get_stylesheet_directory() . '/assets/pwa';
	foreach ( array( 192, 512 ) as $size ) {
		$path = $dir . '/icon-' . (int) $size . '.png';
		if ( is_readable( $path ) ) {
			continue;
		}

		$img  = imagecreatetruecolor( (int) $size, (int) $size );
		$bg   = imagecolorallocate( $img, 10, 10, 10 );
		$gold = imagecolorallocate( $img, 184, 150, 62 );
		imagefilledrectangle( $img, 0, 0, (int) $size, (int) $size, $bg );
		$w      = (int) round( $size * 0.11 );
		$gap    = (int) round( $size * 0.06 );
		$top    = (int) round( $size * 0.22 );
		$bottom = (int) round( $size * 0.78 );
		$mid    = (int) round( $size / 2 );
		imagefilledrectangle( $img, $mid - $gap - $w, $top, $mid - $gap, $bottom, $gold );
		imagefilledrectangle( $img, $mid + $gap, $top, $mid + $gap + $w, $bottom, $gold );
		imagepng( $img, $path, 9 );
		imagedestroy( $img );
	}
}
add_action( 'after_setup_theme', 'one1_pwa_ensure_icons', 19 );

/**
 * Copy service worker to site root when theme version changes (reliable registration).
 */
function one1_pwa_sync_root_service_worker() {
	$version = '1.2.0';
	if ( get_option( 'one1_pwa_sw_version', '' ) === $version ) {
		return;
	}

	$source = get_stylesheet_directory() . '/assets/pwa/sw.js';
	$target = ABSPATH . 'sent-one-sw.js';
	if ( is_readable( $source ) ) {
		copy( $source, $target );
	}

	update_option( 'one1_pwa_sw_version', $version, false );
}
add_action( 'after_setup_theme', 'one1_pwa_sync_root_service_worker', 19 );

/**
 * PWA icon URL for a given size.
 *
 * @param int $size Icon size.
 */
function one1_pwa_icon_url( $size ) {
	one1_pwa_ensure_icons();

	$theme_icon = get_stylesheet_directory_uri() . '/assets/pwa/icon-' . (int) $size . '.png';
	$theme_path = get_stylesheet_directory() . '/assets/pwa/icon-' . (int) $size . '.png';
	if ( is_readable( $theme_path ) ) {
		return $theme_icon;
	}

	$site_icon = get_site_icon_url( (int) $size );
	if ( $site_icon ) {
		return $site_icon;
	}

	return '';
}

/**
 * Web app manifest JSON.
 *
 * @return array<string, mixed>
 */
function one1_pwa_manifest_data() {
	$start = function_exists( 'one1_login_url' ) ? one1_login_url() : home_url( '/' );
	$start = add_query_arg( 'source', 'pwa', $start );

	$icon_192 = one1_pwa_icon_url( 192 );
	$icon_512 = one1_pwa_icon_url( 512 );

	$icons = array(
		array(
			'src'     => $icon_192,
			'sizes'   => '192x192',
			'type'    => 'image/png',
			'purpose' => 'any',
		),
		array(
			'src'     => $icon_512,
			'sizes'   => '512x512',
			'type'    => 'image/png',
			'purpose' => 'any',
		),
		array(
			'src'     => $icon_512,
			'sizes'   => '512x512',
			'type'    => 'image/png',
			'purpose' => 'maskable',
		),
	);

	$icons = array_values(
		array_filter(
			$icons,
			static function ( $icon ) {
				return ! empty( $icon['src'] );
			}
		)
	);

	return array(
		'id'               => trailingslashit( home_url( '/' ) ),
		'name'             => get_bloginfo( 'name' ) ?: 'Sent One',
		'short_name'       => 'Sent One',
		'description'      => __( 'Share journeys with your circle.', 'one' ),
		'start_url'        => $start,
		'scope'            => '/',
		'display'          => 'standalone',
		'display_override' => array( 'standalone', 'minimal-ui' ),
		'orientation'      => 'portrait',
		'background_color' => '#f8f8f8',
		'theme_color'      => '#0a0a0a',
		'icons'            => $icons,
		'prefer_related_applications' => false,
	);
}

/**
 * Serve manifest or service worker.
 */
function one1_pwa_serve_assets() {
	if ( (int) get_query_var( 'one1_web_manifest' ) === 1 ) {
		nocache_headers();
		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		echo wp_json_encode( one1_pwa_manifest_data(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		exit;
	}

	if ( (int) get_query_var( 'one1_service_worker' ) === 1 ) {
		nocache_headers();
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: /' );
		$sw = get_stylesheet_directory() . '/assets/pwa/sw.js';
		if ( is_readable( $sw ) ) {
			readfile( $sw );
		} else {
			echo "self.addEventListener('install',function(e){self.skipWaiting();});self.addEventListener('activate',function(e){e.waitUntil(self.clients.claim());});";
		}
		exit;
	}
}
add_action( 'template_redirect', 'one1_pwa_serve_assets', 0 );

/**
 * Body class hints for PWA install banner layout.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function one1_pwa_body_class( $classes ) {
	if ( ! one1_is_pwa_eligible_page() ) {
		return $classes;
	}

	if ( wp_is_mobile() ) {
		$classes[] = 'one-pwa-mobile';
	}

	return $classes;
}
add_filter( 'body_class', 'one1_pwa_body_class' );

/**
 * Inline script: hide install banner immediately when dismissed or standalone.
 */
function one1_pwa_head_inline() {
	if ( ! one1_is_pwa_eligible_page() ) {
		return;
	}
	?>
	<script>
	window.__onePwaDeferredInstall = null;
	window.__onePwaSwReady = false;
	window.addEventListener('beforeinstallprompt', function (e) {
		e.preventDefault();
		window.__onePwaDeferredInstall = e;
		window.dispatchEvent(new CustomEvent('one-pwa-install-ready'));
	}, { capture: true });
	(function () {
		function syncBannerLayout() {
			var hide = document.documentElement.classList.contains('one-pwa-install-dismissed') ||
				document.documentElement.classList.contains('one-pwa-standalone');
			if (hide && document.body) {
				document.body.classList.remove('has-pwa-install-banner');
			}
		}
		try {
			if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
				document.documentElement.classList.add('one-pwa-standalone');
			}
			if (sessionStorage.getItem('one_pwa_install_dismissed') === '1') {
				document.documentElement.classList.add('one-pwa-install-dismissed');
			}
		} catch (e) {}
		if (document.body) {
			syncBannerLayout();
		} else {
			document.addEventListener('DOMContentLoaded', syncBannerLayout);
		}
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'one1_pwa_head_inline', 1 );

/**
 * Markup for the install app banner (enhanced by one-pwa.js).
 */
function one1_pwa_render_install_banner() {
	if ( ! one1_is_pwa_eligible_page() ) {
		return;
	}
	?>
	<div class="one-pwa-install" id="one-pwa-install-banner" role="region" aria-label="<?php echo esc_attr__( 'Install app', 'one' ); ?>" hidden>
		<span class="material-symbols-outlined one-pwa-install__icon" aria-hidden="true">install_mobile</span>
		<p class="one-pwa-install__text">
			<?php echo esc_html__( 'Install Sent One for quick access from your home screen.', 'one' ); ?>
		</p>
		<div class="one-pwa-install__actions">
			<button type="button" class="one-pwa-install__install" data-one-pwa-install>
				<?php echo esc_html__( 'Install', 'one' ); ?>
			</button>
			<button type="button" class="one-pwa-install__dismiss" data-one-pwa-dismiss aria-label="<?php echo esc_attr__( 'Dismiss', 'one' ); ?>">
				<span class="material-symbols-outlined" aria-hidden="true">close</span>
			</button>
		</div>
	</div>
	<?php
}
add_action( 'wp_body_open', 'one1_pwa_render_install_banner', 1 );

/**
 * Manual install steps modal (iOS / browsers without native prompt).
 */
function one1_pwa_render_install_modal() {
	if ( ! one1_is_pwa_eligible_page() ) {
		return;
	}
	?>
	<div class="one-pwa-guide" id="one-pwa-install-guide" hidden aria-hidden="true">
		<div class="one-pwa-guide__backdrop" data-one-pwa-guide-close tabindex="-1"></div>
		<div class="one-pwa-guide__dialog" role="dialog" aria-modal="true" aria-labelledby="one-pwa-guide-title">
			<h2 id="one-pwa-guide-title" class="one-pwa-guide__title"><?php esc_html_e( 'Add Sent One to your home screen', 'one' ); ?></h2>
			<ol class="one-pwa-guide__steps" data-one-pwa-guide-steps></ol>
			<button type="button" class="one-pwa-guide__close" data-one-pwa-guide-close><?php esc_html_e( 'Got it', 'one' ); ?></button>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'one1_pwa_render_install_modal', 5 );

/**
 * PWA meta tags and manifest link.
 */
function one1_pwa_head_tags() {
	if ( ! one1_is_pwa_eligible_page() ) {
		return;
	}

	$manifest = home_url( '/sent-one-manifest.webmanifest' );
	?>
	<link rel="manifest" href="<?php echo esc_url( $manifest ); ?>" />
	<meta name="theme-color" content="#0a0a0a" />
	<meta name="mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="default" />
	<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( get_bloginfo( 'name' ) ?: 'Sent One' ); ?>" />
	<?php
	$icon = one1_pwa_icon_url( 192 );
	if ( $icon ) {
		printf( '<link rel="apple-touch-icon" href="%s" />', esc_url( $icon ) );
	}
}
add_action( 'wp_head', 'one1_pwa_head_tags', 2 );

/**
 * Enqueue PWA install + service worker registration.
 */
function one1_pwa_enqueue_assets() {
	if ( ! one1_is_pwa_eligible_page() ) {
		return;
	}

	$ver  = '1.7.1';
	$base = get_stylesheet_directory_uri();

	wp_enqueue_style(
		'one-share-material-icons',
		'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'one-pwa',
		$base . '/assets/pwa/one-pwa.css',
		array(),
		$ver
	);

	wp_enqueue_script(
		'one-pwa',
		$base . '/assets/pwa/one-pwa.js',
		array(),
		$ver,
		true
	);

	wp_localize_script(
		'one-pwa',
		'onePwaConfig',
		array(
			'swUrl' => home_url( '/sent-one-sw.js' ),
			'i18n'  => array(
				'install'       => __( 'Install', 'one' ),
				'installLead'   => __( 'Install Sent One for quick access from your home screen.', 'one' ),
				'installRegion' => __( 'Install app', 'one' ),
				'dismiss'       => __( 'Dismiss', 'one' ),
				'addToHome'     => __( 'Add to Home Screen', 'one' ),
				'installing'    => __( 'Installing…', 'one' ),
				'iosSteps'      => array(
					__( 'Tap the Share button at the bottom of Safari.', 'one' ),
					__( 'Scroll down and tap “Add to Home Screen”.', 'one' ),
					__( 'Tap “Add” in the top-right corner.', 'one' ),
				),
				'androidSteps'  => array(
					__( 'Tap the menu (⋮) in the top-right of Chrome.', 'one' ),
					__( 'Tap “Install app” or “Add to Home screen”.', 'one' ),
					__( 'Confirm to add the Sent One shortcut.', 'one' ),
				),
				'waiting'       => __( 'Preparing install… Refresh once if the Install button does not appear.', 'one' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'one1_pwa_enqueue_assets', 35 );
