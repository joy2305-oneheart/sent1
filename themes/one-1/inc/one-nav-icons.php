<?php
/**
 * Inline SVG icons for navigation (no font flash on page load).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a navigation SVG icon.
 *
 * @param string $name  Icon slug.
 * @param string $class CSS class(es).
 */
function one1_render_nav_icon( $name, $class = 'one-nav-icon' ) {
	$class = trim( (string) $class );
	$attr  = $class !== '' ? ' class="' . esc_attr( $class ) . '"' : '';

	switch ( $name ) {
		case 'dashboard':
			echo '<svg' . $attr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
			echo '<rect x="3" y="3" width="7" height="7" rx="1.5"></rect>';
			echo '<rect x="14" y="3" width="7" height="7" rx="1.5"></rect>';
			echo '<rect x="3" y="14" width="7" height="7" rx="1.5"></rect>';
			echo '<rect x="14" y="14" width="7" height="7" rx="1.5"></rect>';
			echo '</svg>';
			break;

		case 'share':
			echo '<svg' . $attr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
			echo '<path d="M4 6.5A2.5 2.5 0 0 1 6.5 4H20v13.5A2.5 2.5 0 0 0 17.5 15H4z"></path>';
			echo '<path d="M6.5 20A2.5 2.5 0 0 1 4 17.5V6.5"></path>';
			echo '<path d="M9 8h7"></path>';
			echo '<path d="M9 11h5"></path>';
			echo '</svg>';
			break;

		case 'about':
			echo '<svg' . $attr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
			echo '<path d="M12 12a3 3 0 1 0 0-6a3 3 0 0 0 0 6Z"></path>';
			echo '<path d="M19 9.5V5l-4.1.4L12 3 9.1 5.4 5 5v4.5L3 12l2 2.5V19l4.1-.4L12 21l2.9-2.4 4.1.4v-4.5L21 12 19 9.5Z"></path>';
			echo '</svg>';
			break;

		case 'profile':
			echo '<svg' . $attr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
			echo '<circle cx="12" cy="8" r="3.5"></circle>';
			echo '<path d="M5 19c1.8-3 4.2-4.5 7-4.5s5.2 1.5 7 4.5"></path>';
			echo '</svg>';
			break;

		case 'invite':
			echo '<svg' . $attr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
			echo '<rect x="3" y="5" width="18" height="14" rx="2"></rect>';
			echo '<path d="m3 7 9 6 9-6"></path>';
			echo '</svg>';
			break;

		case 'add':
			echo '<svg' . $attr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
			echo '<path d="M12 5v14"></path>';
			echo '<path d="M5 12h14"></path>';
			echo '</svg>';
			break;

		default:
			break;
	}
}
