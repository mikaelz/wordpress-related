<?php
/**
 * Plugin Name:       WooCommerce Optional Advance Pay
 * Description:       Enable advance pay (BACS), PayPal and card payment if all cart quantity is available in stock. Payments need to be enabled. Plugin only removes enabled payments.
 * Version:           1.0.0
 * Author:            info@nevilleweb.sk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require dirname(__FILE__).'/OptionalAdvancePay.php';

add_action('init', array('Nevilleweb\OptionalAdvancePay', 'init'));
