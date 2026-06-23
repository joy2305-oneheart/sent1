<?php
/**
 * Public join landing page (invite link destination).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'one-join-body sent-app-body' ); ?>>
<?php wp_body_open(); ?>
<?php require get_stylesheet_directory() . '/join-markup.php'; ?>
<?php wp_footer(); ?>
</body>
</html>
