<?php
/**
 * Public story page template shell.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
require get_stylesheet_directory() . '/public-post-markup.php';
get_footer();
