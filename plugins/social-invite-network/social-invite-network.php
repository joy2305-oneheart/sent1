<?php
/**
 * Plugin Name:       Social Invite Network
 * Plugin URI:        https://example.com/social-invite-network
 * Description:       Private invite-based network with moderated registration and relationship-based visibility (use with Sent One theme for stories and invites).
 * Version:           1.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Social Invite Network
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       social-invite-network
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SIN_VERSION', '1.3.0' );
define( 'SIN_PLUGIN_FILE', __FILE__ );
define( 'SIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'SIN_SECRET_KEY' ) ) {
	define( 'SIN_SECRET_KEY', '' );
}

require_once SIN_PLUGIN_DIR . 'includes/functions-core.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-database.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-roles.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-crypto.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-admin-pu-invites.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-story-share-links.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-email-blasts.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-friend-details.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-access.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-auth.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-invitations.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-reports.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-connections.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-registration.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-shortcodes.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-admin.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-user-connections-admin.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-reports-admin.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-settings.php';
require_once SIN_PLUGIN_DIR . 'includes/class-sin-dashboard-widget.php';

register_activation_hook( __FILE__, array( 'SIN_Database', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SIN_Database', 'deactivate' ) );

add_action( 'plugins_loaded', 'sin_bootstrap' );

/**
 * Load textdomain and init subsystems.
 */
function sin_bootstrap() {
	load_plugin_textdomain( 'social-invite-network', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	$installed = get_option( 'sin_db_version', '' );
	if ( SIN_VERSION !== $installed ) {
		SIN_Database::activate();
		update_option( 'sin_db_version', SIN_VERSION );
	}

	SIN_Access::init();
	SIN_Auth::init();
	SIN_Invitations::init();
	SIN_Connections::init();
	SIN_Registration::init();
	SIN_Shortcodes::init();
	SIN_Admin::init();
	SIN_Admin_PU_Invites::init();
	SIN_Story_Share_Links::init();
	SIN_Email_Blasts::init();
	SIN_Friend_Details::init();
	SIN_User_Connections_Admin::init();
	SIN_Reports_Admin::init();
	SIN_Settings::init();
	SIN_Dashboard_Widget::init();
}
