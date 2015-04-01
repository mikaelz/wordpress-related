<?php
/**
 * Plugin Name: iKros order
 * Description: Sync WooCommerce order to iKros.sk
 * Version: 0.0.1
 * Author: Michal Zuber
 * Author URI: http://www.nevilleweb.sk
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action('woocommerce_checkout_order_processed', array(new iKrosOrder(), 'upload_to_ikros'), 10, 2);

class iKrosOrder
{
    private $api_email;
    private $api_token;
    
    public function __construct()
    {
        $this->api_email = 'IKROS_EMAIL';
        $this->api_token = 'IKROS_TOKEN';
    }

    /**
     * Upload order data to ikros.sk
     *
     * @return bool
     **/
    public function upload_to_ikros($order_id, $customerdata)
    {
        /**
         * From exapmle at
         * http://docs.ikros.apiary.io/#reference/podporovane-webove-sluzby/objednavky/priklad-objednavky-kod-a-online-demo
         **/
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://app.ikros.sk/public/api/v1/incomingorders/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->prepateData($order_id, $customerdata));

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          "X-ESHOP-TOKEN: {$this->api_token}",
          "X-ESHOP-EMAIL: {$this->api_email}"
        ));

        $response = curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Prepare data into JSON format
     *
     * @return string
     **/
    private function prepateData($order_id, $customer)
    {
        switch ($customer['payment_method']) {
            default:
                $paymentType = 'bankový prevod';
        }

        switch ($customer['shipping_method'][0]) {
            default:
                $deliveryType = 'dobierka';
        }

        $data = array(
            'documentNumber' => "$order_id",
            'createDate' => date('c'),
            'completionDate' => date('c'),
            'clientName' => $customer['billing_company'],
            'clientContact' => "{$customer['billing_first_name']} {$customer['billing_last_name']}",
            'clientStreet' => $customer['billing_address_1'],
            'clientPostCode' => $customer['billing_postcode'],
            'clientTown' => $customer['billing_city'],
            'clientCountry' => $customer['billing_country'],
            // 'clientRegistrationId' => $customer['registration_id'],
            // 'clientTaxId' => $customer['tax_id'],
            // 'clientVatId' => $customer['vat_id'],
            // 'clientInternalId' => 0,
            'variableSymbol' => $order_id,
            'openingText' => '',
            'closingText' => $customer['order_comments'],
            'senderName' => 'SENDER_NAME',
            'senderRegistrationId' => 123456,
            'senderRegistrationCourt' => 'Zapísaný v živnostenskom registri OÚ KN, č. 123-12345',
            'senderVatId' => '',
            'senderTaxId' => 123456,
            'senderStreet' => 'STREET',
            'senderPostCode' => 99999,
            'senderTown' => 'CITY',
            'senderRegion' => '',
            'senderCountry' => 'Slovensko',
            'senderBankAccount' => '',
            'senderBankIban' => '',
            'senderBankSwift' => '',
            'paymentType' => $paymentType,
            'deliveryType' => $deliveryType,
            'senderContactName' => 'CONTACTNAME',
            'senderPhone' => '0912 123 123',
            'senderEmail' => ' info@example.com',
            'senderWeb' => 'http://www.example.com/',
            'clientPostalName' => @$customer['shipping_company'],
            'clientPostalContact' => @$customer['shipping_first_name'].' '.@$customer['shipping_last_name'],
            'clientPostalStreet' => @$customer['shipping_address_1'],
            'clientPostalPostCode' => @$customer['shipping_postcode'],
            'clientPostalTown' => @$customer['shipping_city'],
            'clientPostalCountry' => @$customer['shipping_country'],
            'clientHasDifferentPostalAddress' => $customer['ship_to_different_address'],
            'currency' => 'EUR',
            'exchangeRate' => 1,
            'senderIsVatPayer' => 0,
        );

        foreach (WC()->cart->cart_contents as $item) {
            $data['items'][] = array(
                'name' => $item['data']->post->post_title,
                'description' => '',
                'count' => $item['quantity'],
                'measureType' => 'ks',
                'total' => $item['line_total'],
                'vat' => 20,
                'hasDiscount' => 0,
                'discountName' => '',
                'discountPercent' => 0,
                'discountTotal' => 0,
                'productCode' => $item['product_id'],
                'typeId' => 1, // product
                'warehouseCode' => '',
                'warehouseName' => '',
                'foreignName' => '',
                'customText' => '',
                'ean' => '',
                'jkpov' => '',
                'plu' => 0,
                'numberingSequenceCode' => '',
            );
        }

        return sprintf("[%s]", json_encode($data) );
    }
}

