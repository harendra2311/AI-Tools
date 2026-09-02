<?php
/**
 * Plugin Name: WP Security & Mail Scanner
 * Plugin URI: https://wordpress.org/plugins/wp-security-mail-scanner/
 * Description: Investigation-first scanner for possible website compromise and unauthorized spam, phishing, or unwanted email activity. Does not delete or modify files without explicit administrator confirmation.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: WP Security & Mail Scanner
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-security-mail-scanner
 * Domain Path: /languages
 *
 * @package WPSMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPSMS_VERSION', '1.0.0' );
define( 'WPSMS_PLUGIN_FILE', __FILE__ );
define( 'WPSMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSMS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPSMS_CAPABILITY', 'manage_options' );

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

require_once WPSMS_PLUGIN_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'WPSMS_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPSMS_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'WPSMS_Plugin', 'instance' ) );
