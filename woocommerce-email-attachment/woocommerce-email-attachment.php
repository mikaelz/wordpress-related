<?php
/**
 * The WordPress Plugin Boilerplate.
 *
 * A foundation off of which to build well-documented WordPress plugins that
 * also follow WordPress Coding Standards and PHP best practices.
 *
 * @package   Woocommerce_Email_Attachment
 * @author    Michal Zuber <info@nevilleweb.sk>
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       Woocommerce Email Attachment
 * Plugin URI:        http://www.nevilleweb.sk/
 * Description:       Add attachment to woocommerce email
 * Version:           1.0.0
 * Author:            Michal Zuber
 * Author URI:        http://www.nevilleweb.sk/
 * Text Domain:       woocommerce-email-attachment-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-woocommerce-email-attachment.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'Woocommerce_Email_Attachment', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Woocommerce_Email_Attachment', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Woocommerce_Email_Attachment', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-woocommerce-email-attachment-admin.php' );
	add_action( 'plugins_loaded', array( 'Woocommerce_Email_Attachment_Admin', 'get_instance' ) );

}

