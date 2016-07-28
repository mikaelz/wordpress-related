<?php
/**
 * Plugin Name: GA enhanced ecommerce for WooCommerce
 * Description: Google Analytics enhanced ecommerce for WooCommerce
 * Version: 1.0
 * Author: Michal Zuber
 * Author URI: http://www.nevilleweb.sk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'gaEcommerce' ) ) {
    return;
}

if (strpos($_SERVER['REQUEST_URI'], 'order-received/') === false) {
    return;
}

$gaEcommerce = new gaEcommerce();
add_action('woocommerce_thankyou', array($gaEcommerce, 'setData'));
add_action('wp_footer', array($gaEcommerce, 'outputJavascript'));

class gaEcommerce
{
    private $order_id;
    private $products;

    public function setData($order_id)
    {
        if ( 1 > $order_id ) {
            return;
        }

        $this->order_id = $order_id;
        $this->order = new WC_Order( $order_id );

        $this->setProducts();
    }

    public function outputJavascript()
    {

        echo sprintf('<script>
            if (typeof(ga) == "function") {
                ga("require", "ecommerce");
                %s
                %s
                ga("ecommerce:send");
            }
            </script>',
            $this->getTransactionJs(),
            $this->getProductJs()
        );
    }

    private function getProductJs() {
        $jsProducts = '';
        foreach ($this->products as $product) {
            $jsProducts .= "
                ga('ecommerce:addItem', {
                    'id': '$this->order_id',
                    'name': '{$product['name']}',
                    'sku': '{$product['sku']}',
                    'price': '{$product['price']}',
                    'quantity': '{$product['quantity']}'
                });
            ";
        }

        return $jsProducts;
    }

    private function getTransactionJs() {
        return "
                ga('ecommerce:addTransaction', {
                    'id': '{$this->order_id}',
                    'revenue': '".$this->order->get_total()."',
                    'shipping': '".$this->order->get_total_shipping()."',
                });
            ";
    }

    private function setProducts() {
        foreach ( $this->order->get_items() as $item ) {
            if (empty($item['name'])) {
                continue;
            }

            $this->products[] = array(
                'sku' => $item['product_id'],
                'name' => $item['name'],
                'price' => $item['line_total'],
                'quantity' => $item['qty'],
            );
        }
    }

}
