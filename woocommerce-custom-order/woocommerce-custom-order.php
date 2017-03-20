<?php
/**
 * Plugin Name: WooCommerce custom order
 * Plugin URI:
 * Description: WooCommerce custom order form shortcode
 * Version: 1.0.0
 * Author: Michal Zuber
 * Author URI: https://www.nevilleweb.sk.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

add_shortcode('custom_order_form', 'custom_order_form');
function custom_order_form()
{
    global $wpdb;

    $form = '<form class="order" action="" method="post">';
    $form .= '<table class="products">';
    $products = $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'", ARRAY_A);
    foreach ($products as $product) {
        $meta = get_post_meta($product['ID']);
        $form .= '<tr>
            <td>
                <h4>'.$product['post_title'].'</h4>
                '.$product['post_content'].'
            </td>
            <td class="price-col"><span class="price">'.$meta['_regular_price'][0].'</span> &euro;</td>
            <td>
                <input type="number" name="quantity['.$product['ID'].']" data-id="'.$product['ID'].'" maxlength="2" value="0">
            </td>
        </tr>';
    }
    $form .= '</table>';
    $form .= '<button id="order">Objednať</button>';

    $form .= '<div id="order-address" class="hide">
        <h3>Fakturačná adresa</h3>
        <div class="cols group">
            <div class="col col1">
                <label for="first_name">
                    <span class="red">*</span>Meno
                </label>
            </div>
            <div class="col col2">
                <input type="text" name="first_name" id="first_name" maxlength="100" value="" required="required">
            </div>
            <div class="col col3">
                <label for="city">
                    <span class="red">*</span>Mesto
                </label>
            </div>
            <div class="col col4">
                <input type="text" name="city" id="city" maxlength="100" value="" required="required">
            </div>
        </div>
        <div class="cols group">
            <div class="col col1">
                <label for="last_name">
                    <span class="red">*</span>Priezvisko
                </label>
            </div>
            <div class="col col2">
                <input type="text" name="last_name" id="last_name" maxlength="100" value="" required="required">
            </div>
            <div class="col col3">
                <label for="zip">
                    <span class="red">*</span>PSČ
                </label>
            </div>
            <div class="col col4">
                <input type="text" name="zip" id="zip" maxlength="5" value="" required="required">
            </div>
        </div>
        <div class="cols group">
            <div class="col col1">
                <label for="street">
                    <span class="red">*</span>Ulica
                </label>
            </div>
            <div class="col col2">
                <input type="text" name="street" id="street" maxlength="200" value="" required="required">
            </div>
            <div class="col col3">
                <label for="email">
                    <span class="red">*</span>Email
                </label>
            </div>
            <div class="col col4">
                <input type="email" name="email" id="email" maxlength="255" value="" required="required">
            </div>
        </div>
        <div class="cols group">
            <div class="col col1">
                <label for="phone"><span class="red">*</span>Telefón</label>
            </div>
            <div class="col col2">
                <input type="text" name="phone" id="phone" maxlength="20" required="required">
            </div>
            <div class="col col3">
                <label for="note">Poznámka</label>
            </div>
            <div class="col col4">
                <textarea name="note" rows="3" cols="40" id="note"></textarea>
            </div>
        </div>
        <h3>Dodacia adresa</h3>
        <p>
            <label>
                <input type="checkbox" name="shipping" id="shipping" checked="checked">
                je rovnaká ako fakturačná
            </label>
        </p>
        <div id="ship-address-form" class="ship-address hide">
            <div class="cols group">
                <div class="col col1">
                    <label for="ship_first_name">Meno</label>
                </div>
                <div class="col col2">
                    <input type="text" name="ship_first_name" id="ship_first_name" maxlength="100">
                </div>
                <div class="col col3">
                    <label for="ship_city">Mesto</label>
                </div>
                <div class="col col4">
                    <input type="text" name="ship_city" id="ship_city" maxlength="100">
                </div>
            </div>
            <div class="cols group">
                <div class="col col1">
                    <label for="ship_last_name">Priezvisko</label>
                </div>
                <div class="col col2">
                    <input type="text" name="ship_last_name" id="ship_last_name" maxlength="100">
                </div>
                <div class="col col3">
                    <label for="ship_zip">PSČ</label>
                </div>
                <div class="col col4">
                    <input type="text" name="ship_zip" id="ship_zip" maxlength="5">
                </div>
            </div>
            <div class="cols group">
                <div class="col col1">
                    <label for="ship_street">Ulica</label>
                </div>
                <div class="col col2">
                    <input type="text" name="ship_street" id="ship_street" maxlength="200">
                </div>
            </div>
        </div>

        <div class="payment">
            <h3>Spôsob platby</h3>
            <p class="cols group">
                <label class="col">
                    <input type="radio" name="payment" value="bacs">
                    bankový prevod
                </label>
                <label class="col">
                    <input type="radio" name="payment" value="cod">
                    dobierka
                </label>
            </p>
        </div>

        <div class="cart">
            <table>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <td class="green" colspan="2">Súčet</td>
                        <td class="green tr"><span id="subtotal">0.00 </span>&euro;</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="total">Spolu: <span class="green"><span id="total">0.00</span> &euro;</span></p>
        <p class="agree tc">
            <label for="agree">
                <input type="checkbox" name="agree" id="agree" value="1" required="required">
                Súhlasím s <a href="obchodne-podmienky/" target="_blank">obchodnými podmienkami</a>
            </label>
        </p>
        <button id="save-order" type="submit" disabled="disabled">Objednať s povinnosťou platby</button>
    </div>';

    $form .= '</form>';

    return $form;
}

add_action('template_redirect', 'custom_save_order');
function custom_save_order()
{
    global $wpdb;

    if (!is_single() && !is_page()) {
        return;
    }

    if (empty($_POST)) {
        return;
    }

    $order_id = wp_insert_post(array(
        'post_title' => 'Order &ndash; '.date('d.m.Y H:i'),
        'post_status' => 'wc-pending',
        'comment_status' => 'closed',
        'post_type' => 'shop_order',
    ));

    if (1 > $order_id) {
        return;
    }

    $total = 0;
    foreach ($_POST['quantity'] as $product_id => $qnt) {
        if (1 > $qnt) {
            continue;
        }
        $wc_product = wc_get_product($product_id);
        $subtotal = $qnt * $wc_product->get_price();
        $total += $subtotal;

        $wpdb->insert(
            $wpdb->prefix.'woocommerce_order_items',
            array(
                'order_item_name' => $wc_product->get_title(),
                'order_item_type' => 'line_item',
                'order_id' => $order_id,
            ),
            array(
                '%s', '%s', '%d',
            )
        );
        $order_item_id = $wpdb->insert_id;

        wc_add_order_item_meta($order_item_id, '_product_id', $product_id);
        wc_add_order_item_meta($order_item_id, '_qty', $qnt);
        wc_add_order_item_meta($order_item_id, '_product_id', $product_id);
        wc_add_order_item_meta($order_item_id, '_line_subtotal', $subtotal);
        wc_add_order_item_meta($order_item_id, '_line_subtotal_tax', 0);
        wc_add_order_item_meta($order_item_id, '_line_total', $subtotal);
        wc_add_order_item_meta($order_item_id, '_line_tax', 0);
    }

    add_post_meta($order_id, '_billing_first_name', $_POST['first_name']);
    add_post_meta($order_id, '_billing_last_name', $_POST['last_name']);
    add_post_meta($order_id, '_billing_address_1', $_POST['street']);
    add_post_meta($order_id, '_billing_city', $_POST['city']);
    add_post_meta($order_id, '_billing_postcode', $_POST['zip']);
    add_post_meta($order_id, '_billing_email', $_POST['email']);
    add_post_meta($order_id, '_billing_phone', $_POST['phone']);
    add_post_meta($order_id, '_shipping_first_name', $_POST['ship_first_name']);
    add_post_meta($order_id, '_shipping_last_name', $_POST['ship_last_name']);
    add_post_meta($order_id, '_shipping_address_1', $_POST['ship_street']);
    add_post_meta($order_id, '_shipping_city', $_POST['ship_city']);
    add_post_meta($order_id, '_shipping_postcode', $_POST['ship_zip']);
    add_post_meta($order_id, '_order_total', $total);
    add_post_meta($order_id, '_order_tax', $total / 1.2);
    add_post_meta($order_id, '_order_currency', 'EUR');
    add_post_meta($order_id, '_customer_user', 0);
    add_post_meta($order_id, '_order_shipping', 0);
    add_post_meta($order_id, '_order_shipping_tax', 0);
    add_post_meta($order_id, '_cart_discount', 0);
    add_post_meta($order_id, '_cart_discount_tax', 0);
    add_post_meta($order_id, '_payment_method', $_POST['payment']);

    $comment_post_ID = $order_id;
    $comment_content = $_POST['note'];
    $comment_agent = 'WooCommerce';
    $comment_type = 'order_note';
    $comment_parent = 0;
    $comment_approved = 1;
    $commentdata = apply_filters(
        'woocommerce_new_order_note_data',
        compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_agent', 'comment_type', 'comment_parent', 'comment_approved'),
        array('order_id' => $order_id, 'is_customer_note' => 1)
    );

    $comment_id = wp_insert_comment($commentdata);

    $mailer = WC()->mailer();
    $mails = $mailer->get_emails();
    if (!empty($mails)) {
        foreach ($mails as $mail) {
            if ($mail->id == 'customer_processing_order') {
                $mail->trigger($order_id);
            }
        }
    }
}
