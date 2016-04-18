<?php

require __DIR__.'/class-wc-api-client.php';

$consumer_key = 'ck_7e5314e7afdeda227909f';
$consumer_secret = 'cs_6e05b6fdb35729548e';
$store_url = 'http://localhost/example.com/';

// Initialize the class
$wc_api = new WC_API_Client( $consumer_key, $consumer_secret, $store_url );

// Get Index
$response = $wc_api->get_index();

// Get all orders
// $response = $wc_api->get_orders( array( 'status' => 'completed' ) );

// Get a single order by id
// $response = $wc_api->get_order( 166 );

// Get orders count
// $response = $wc_api->get_orders_count();

// Get order notes for a specific order
// $response = $wc_api->get_order_notes( 166 );

// Update order status
// $response = $wc_api->update_order( 166, $data = array( 'status' => 'failed' ) );

// Get all coupons
// $response = $wc_api->get_coupons();

// Get coupon by id
// $response = $wc_api->get_coupon( 173 );

// Get coupon by code
// $response = $wc_api->get_coupon_by_code( 'test coupon' );

// Get coupons count
// $response = $wc_api->get_coupons_count();

// Get customers
// $response = $wc_api->get_customers();

// Get customer by id
// $response = $wc_api->get_customer( 2 );

// Get customer count
// $response = $wc_api->get_customers_count();

// Get customer orders
// $response = $wc_api->get_customer_orders( 2 );

// Get all products
// $response = $wc_api->get_products();

// Get a single product by id
// $response = $wc_api->get_product( 167 );

// Get products count
// $response = $wc_api->get_products_count();

// Get product reviews
// $response = $wc_api->get_product_reviews( 167 );

// Get reports
// $response = $wc_api->get_reports();

// Get sales report
// $response = $wc_api->get_sales_report();

// Get top sellers report
// $response = $wc_api->get_top_sellers_report();

// Update product
$product_id = 7022;
$data = array(
    'product' => array(
        'title' => 'API test',
    ),
);
// $response = $wc_api->_make_api_call( 'products/'.$product_id, array(), 'PUT', $data );

echo "<pre>"; print_r($response); echo "</pre>";
