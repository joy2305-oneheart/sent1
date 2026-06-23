<?php
/**
 * Reusable header CTA pill button.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output a header-style CTA button link.
 *
 * @param string $url   Link URL.
 * @param string $label Button label.
 */
function one1_homie_cta_button( $url, $label ) {
	one1_button(
		array(
			'url'     => $url,
			'label'   => $label,
			'variant' => 'outline',
			'size'    => 'sm',
			'skin'    => 'homie',
			'icon'    => 'dual-arrow',
		)
	);
}
