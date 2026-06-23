<?php
/**
 * Plugin Name: Sent One Social Login
 * Description: Google social login for the Sent One custom login and signup pages.
 * Version: 1.0.2
 * Author: Sent One
 * Text Domain: sent-one-social-login
 *
 * @package Sent_One_Social_Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SOSL_VERSION', '1.0.2' );
define( 'SOSL_PLUGIN_FILE', __FILE__ );
define( 'SOSL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SOSL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SOSL_PLUGIN_DIR . 'includes/class-sosl-settings.php';
require_once SOSL_PLUGIN_DIR . 'includes/class-sosl-user-manager.php';
require_once SOSL_PLUGIN_DIR . 'includes/class-sosl-google-auth.php';
require_once SOSL_PLUGIN_DIR . 'includes/class-sosl-button.php';
require_once SOSL_PLUGIN_DIR . 'includes/class-sosl-plugin.php';

SOSL_Plugin::init();

/**
 * Render social login buttons.
 *
 * @param array $args {
 *     @type string $context     login|register.
 *     @type string $redirect_to Redirect URL after auth.
 *     @type string $ref         Invite ref code.
 * }
 */
function sosl_render_buttons( $args = array() ) {
	SOSL_Button::render( $args );
}
