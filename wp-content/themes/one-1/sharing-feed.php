<?php
/**
 * Sent One — community sharing feed (logged-in members).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="sent-share-html">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'sent-share-body' ); ?>>
<?php wp_body_open(); ?>
<?php require get_stylesheet_directory() . '/sharing-feed-markup.php'; ?>
<?php wp_footer(); ?>
</body>
</html>
