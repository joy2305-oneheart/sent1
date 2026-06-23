<?php
/**
 * Full-page auth template (login, signup, admin dashboard).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_auth_type = one1_auth_page_type();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
if ( 'login' === $one_auth_type ) {
	require get_stylesheet_directory() . '/auth-login-markup.php';
} elseif ( 'dashboard' === $one_auth_type ) {
	require get_stylesheet_directory() . '/auth-dashboard-markup.php';
} elseif ( 'forgot' === $one_auth_type ) {
	require get_stylesheet_directory() . '/auth-forgot-markup.php';
} elseif ( 'reset' === $one_auth_type ) {
	require get_stylesheet_directory() . '/auth-reset-markup.php';
}
?>
<?php wp_footer(); ?>
</body>
</html>
