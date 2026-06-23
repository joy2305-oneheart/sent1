<?php
/**
 * Front-end story submission template.
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
<body <?php body_class( 'homie-landing-body sent-share-body sent-app-body' ); ?>>
<?php wp_body_open(); ?>
<?php require get_stylesheet_directory() . '/story-form-markup.php'; ?>
<?php wp_footer(); ?>
</body>
</html>
