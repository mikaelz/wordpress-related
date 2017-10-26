<?php
/**
 * No COD for virtual product
 *
 * @wordpress-plugin
 * @package WordPress
 * @category Plugin
 * Plugin Name: No COD for virtual product
 * Plugin URI:  https://www.nevilleweb.sk/
 * Description: Hide COD payment if virtual/downloadable product in cart
 * Version:     1.0
 * Author:      Michal Zuber
 * Author URI:  https://www.nevilleweb.sk/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

/**
 * Disable COD payment if virtual product is in cart
 *
 * @param array $available_gateways Available payment methods.
 * @return array
 */
function nw_no_cod_payment( $available_gateways ) {
	if ( nw_is_virtual_in_cart() ) {
		unset( $available_gateways['cod'] );
	}

	return $available_gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'nw_no_cod_payment' );

/**
 * Check if virtual/downloadable product is in cart
 */
function nw_is_virtual_in_cart() {
	$reposnse = false;

	foreach ( WC()->cart->cart_contents as $item ) {
		$product_id = $item['product_id'];
		if ( isset( $item['variation_id'] ) && $item['variation_id'] > 0 ) {
			$product_id = $item['variation_id'];
		}
		$product = wc_get_product( $product_id );

		$is_virtual = get_post_meta( $product_id, '_virtual', true );
		if ( 'yes' === $is_virtual ) {
			$reposnse = true;
			break;
		}

		$is_downloadable = get_post_meta( $product_id, '_downloadable', true );
		if ( 'yes' === $is_downloadable ) {
			$reposnse = true;
			break;
		}
	}

	return $reposnse;
}
