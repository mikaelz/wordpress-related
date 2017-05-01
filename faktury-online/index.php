<?php
/**
 * Plugin Name: Send order to faktury-online.com/faktury
 * Description: Send WooCommerce order via API to <a target="_blank" href="https://www.faktury-online.com/faktury">https://www.faktury-online.com/faktury</a>
 * Version: 1.0.0
 * Author: Michal Zuber
 * Author URI: http://www.nevilleweb.sk.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class FakturyOnlineSendOrder
{
    const API_URL = 'https://www.faktury-online.com/api/nf';
    const API_EMAIL = 'info@example.com';
    const API_TOKEN = 'API_KEY';
    const API_FIRM_ID = 0;
    const IS_TEST = 0; // 0 for prod, 1 for test
    const CURRENCY = 'EUR';
    const VAT_RATE = 20;

    /**
     * Init API email and token.
     */
    public function __construct()
    {
        add_action('woocommerce_checkout_order_processed', array($this, 'sendOrder'), 10, 2);
    }

    /**
     * Upload order
     * https://www.faktury-online.com/faktury-online-api/manual.
     *
     * @param int   $orderId      Order ID
     * @param array $customerData Customer data
     *
     * @return bool
     **/
    public function sendOrder($orderId, $customerData)
    {
        $data = $this->_prepateData($orderId, $customerData);
        $url = sprintf(
            '%s?data=%s',
            self::API_URL,
            urlencode($data)
        );
        $output = file_get_contents($url);
        $result = json_decode($output, true);
        $wcOrder = new WC_Order($orderId);

        if ($result['status'] == 1) {
            $wcOrder->add_order_note(
                sprintf(
                    'Sent to <a target="_blank" href="%s%s">%s</a>',
                    'https://www.faktury-online.com/faktury/detail?f=',
                    $result['code'],
                    'https://www.faktury-online.com/faktury'
                ),
                false,
                true
            );

            return true;
        }

        $wcOrder->add_order_note(
            sprintf(
                'Send to https://www.faktury-online.com/faktury failed: %s',
                $result['status']
            ),
            false,
            true
        );

        return false;
    }

    /**
     * Prepare data into JSON format.
     *
     * @param int   $orderId  Order ID
     * @param array $customer Customer data
     *
     * @return string
     **/
    private function _prepateData($orderId, $customer)
    {
        $data = array(
            'test' => self::IS_TEST,
            'key' => self::API_TOKEN,
            'email' => self::API_EMAIL,
            'd' => array('d_id' => self::API_FIRM_ID),
        );

        $data['o'] = array(
            'o_name' => "{$customer['billing_first_name']} {$customer['billing_last_name']}",
            'o_street' => $customer['billing_address_1'],
            'o_city' => $customer['billing_city'],
            'o_zip' => $customer['billing_postcode'],
            'o_state' => $customer['billing_country'],
            'o_email' => $customer['billing_email'],
            'closingText' => $customer['order_comments'],
        );

        switch ($customer['payment_method']) {
        case 'bacs':
            $paymentType = 'prevod';
            break;
        default:
            $paymentType = 'dobierka';
        }

        $data['f'] = array(
            'f_vs' => $orderId,
            'f_ks' => '0308',
            'f_date_issue' => date('Y-m-d'),
            'f_date_delivery' => date('Y-m-d'),
            'f_date_due' => date('Y-m-d', strtotime('+2 weeks')),
            'f_payment' => $paymentType,
            'f_proforma' => 0,
            'f_currency' => self::CURRENCY,
        );

        foreach (WC()->cart->cart_contents as $item) {
            $data['p'][] = array(
                'p_text' => __($item['data']->post->post_title),
                'p_quantity' => $item['quantity'],
                'p_unit' => 'ks',
                'p_vat' => self::VAT_RATE,
                'p_price' => $item['line_total'],
            );
        }

        return json_encode($data);
    }
}

new FakturyOnlineSendOrder();
