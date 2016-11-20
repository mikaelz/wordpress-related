<?php
/**
 * Plugin Name:       WooCommerce Upsells after add to cart
 * Plugin URI:        https://www.nevilleweb.sk
 * Description:       Show upsell products after add to cart on single product page
 * Version:           1.0.0
 * Author:            Michal Zuber
 * Author URI:        https://www.nevilleweb.sk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

add_filter('wc_add_to_cart_message', 'wuac_add_to_cart_message', 10, 2);
function wuac_add_to_cart_message($message, $product_id)
{
    $product = wc_get_product($product_id);
    if (!$upsells = $product->get_upsells()) {
        return $message;
    }

    $args = array(
        'post_type' => 'product',
        'ignore_sticky_posts' => 1,
        'no_found_rows' => 1,
        'posts_per_page' => 3,
        'orderby' => 'rand',
        'post__in' => $upsells,
        'post__not_in' => array($product_id),
        'meta_query' => WC()->query->get_meta_query(),
    );

    $products = new WP_Query($args);
    $woocommerce_loop['name'] = 'up-sells';
    $woocommerce_loop['columns'] = apply_filters('woocommerce_up_sells_columns', 3);

    if (!$products->have_posts()) {
        return $message;
    }

    $message .= '</div><!-- woocommerce-message --><div class="upsells">';

    ob_start();
    ?>
    <div class="up-sells upsells products">
        <h2><?php _e('You may also like&hellip;', 'woocommerce') ?></h2>
        <?php
        woocommerce_product_loop_start();

        while ($products->have_posts()) {
            $products->the_post();
            wc_get_template_part('content', 'product');
        }

        woocommerce_product_loop_end();
        ?>
    </div>
    <?php
    $message .= ob_get_contents();
    ob_end_clean();

    wp_reset_postdata();

    return $message;
}
