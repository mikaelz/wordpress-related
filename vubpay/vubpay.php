<?php
/**
 * WordPress-Plugin-Boilerplate: v2.6.1
 *
 * A foundation off of which to build well-documented WordPress plugins that
 * also follow WordPress Coding Standards and PHP best practices.
 *
 * @package   vubpay
 * @author    Michal Zuber <info@nevilleweb.sk>
 * @license   GPL-2.0+
 * @link      http://nevilleweb.sk
 * @copyright 2014 Michal Zuber
 *
 * @wordpress-plugin
 * Plugin Name:       VUB eCard Payments
 * Plugin URI:        http://nevilleweb.sk
 * Description:       Create, process and manage VUB eCard payments
 * Version:           1.0.0
 * Author:            Michal Zuber
 * Author URI:        http://nevilleweb.sk
 * Text Domain:       vubpay
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

require_once( plugin_dir_path( __FILE__ ) . 'class-vubpay.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 */
register_activation_hook( __FILE__, array( 'Vubpay', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Vubpay', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'Vubpay', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-vubpay-admin.php' );
	add_action( 'plugins_loaded', array( 'Vubpay_Admin', 'get_instance' ) );

}
