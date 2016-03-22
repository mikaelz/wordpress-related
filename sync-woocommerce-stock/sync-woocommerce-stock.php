<?php

date_default_timezone_set('Europe/Bratislava');
error_reporting(-1);

/**
 * Fetch stock from SK site
 **/
$table_prefix = 'wp_';
if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], array('localhost'))) {
    $mysqli = new mysqli('localhost', 'root', '', 'sk_db_name');
}
else {
    $mysqli = new mysqli('localhost', 'root', 'pass', 'sk_db_name');
}
$mysqli->query( 'SET NAMES utf8' );

$sk_products = array();
$q = "SELECT ID FROM {$table_prefix}posts WHERE post_type IN ('product', 'product_variation')";
$q = $mysqli->query($q);
while ( $post = $q->fetch_assoc() ) {
    $meta = array();
    $metaq = "SELECT * FROM {$table_prefix}postmeta WHERE post_id = {$post['ID']} AND meta_key IN ('_sku', '_stock')";
    $metaq = $mysqli->query($metaq);
    while ( $postmeta = $metaq->fetch_assoc() ) {
        $meta[$postmeta['meta_key']] = $postmeta['meta_value'];
    }
    $sk_products[$post['ID']] = $meta;
}
$mysqli->close();

/**
 * Update stock on CZ
 **/
$updated = 0;
$table_prefix = 'wp_';
if (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], array('localhost'))) {
    $mysqli = new mysqli('localhost', 'root', '', 'cz_db_name');
}
else {
    $mysqli = new mysqli('localhost', 'root', 'pass', 'cz_db_name');
}
$mysqli->query( 'SET NAMES utf8' );
foreach ($sk_products as $product) {
    if (empty($product['_stock'])) {
        continue;
    }
    if (empty($product['_sku'])) {
        continue;
    }
    $q = "SELECT ID FROM {$table_prefix}posts p
          LEFT JOIN {$table_prefix}postmeta pm ON p.ID = pm.post_id
          WHERE meta_key = '_sku' AND meta_value = '{$product['_sku']}' LIMIT 1";
    $post = $mysqli->query($q)->fetch_assoc();

    $q = "UPDATE {$table_prefix}postmeta SET meta_value = '{$product['_stock']}' WHERE meta_key = '_stock' AND post_id = '{$post['ID']}' LIMIT 1";
    $mysqli->query($q);

    $updated++;
}
$mysqli->close();

echo "Updated: $updated items";
