<?php

namespace Nevilleweb;

class OptionalAdvancePay
{
    public static $instance = false;

    public function __construct()
    {
        if (!class_exists('WooCommerce')) {
            return;
        }

        add_filter('woocommerce_available_payment_gateways', array($this, 'editPaymentGateways'));
    }

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function isInStock()
    {
        $inStock = true;

        foreach (WC()->cart->cart_contents as $item) {
            $product = new \WC_Product_Factory();
            $product = $product->get_product($item['product_id']);
            if ($item['quantity'] > $product->get_stock_quantity()) {
                $inStock = false;
                break;
            }
        }

        return $inStock;
    }

    public function editPaymentGateways($available_gateways)
    {
        if (!$this->isInStock()) {
            unset($available_gateways['bacs']);
            unset($available_gateways['paypal']);
        }

        return $available_gateways;
    }
}
