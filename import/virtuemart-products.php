<?php

require __DIR__ . '/../wp-load.php';
error_reporting( - 1 );
ini_set( 'display_errors', 1 );
set_time_limit( 600 );

require ABSPATH . 'wp-admin/includes/image.php';
if ( ! is_dir( ABSPATH . '/wp-content/uploads' ) ) {
	mkdir( ABSPATH . '/wp-content/uploads' );
}

$mysqli = new mysqli( 'localhost', 'root', 'root', 'dbName', 3306 );
$mysqli->query( 'SET NAMES utf8' );
$execStart = time();
$importCount = 0;

echo date( 'H:i:s d.m.Y', $execStart ) . '<br>';

$sql = "SELECT p.product_id, product_sku, product_s_desc, product_desc, product_name, product_in_stock, product_full_image, product_price, cat.category_name
        FROM jos_vm_product p
        LEFT JOIN jos_vm_product_price pr ON p.product_id=pr.product_id
        LEFT JOIN jos_vm_product_category_xref pcref ON p.product_id=pcref.product_id
        LEFT JOIN jos_vm_category cat ON pcref.category_id=cat.category_id
        WHERE product_publish = 'Y'
        ";
$res = $mysqli->query( $sql );
while ( $row = $res->fetch_assoc() ) {
	if ( time() >= $execStart + 550 ) {
		echo 'Time limit reached<br>';
		return;
	}

	if ( empty( $row['category_name'] ) ) {
		echo "Empty category {$row['product_name']}<br>";
		continue;
	}

	if ( getProduct( $row['product_sku'] ) ) {
		continue;
	}

	$product = [
		'post_author'  => 3,
		'post_title'   => $row['product_name'],
		'post_excerpt' => $row['product_s_desc'],
		'post_content' => $row['product_desc'],
		'post_status'  => 'publish',
		'post_type'    => 'product',
	];
	$product_id = wp_insert_post( $product );

	if ( 1 > $product_id ) {
		echo "Import of {$row['product_sku']} failed<br>";
		continue;
	}

	add_post_meta( $product_id, '_sku', $row['product_sku'] );
	add_post_meta( $product_id, '_price', $row['product_price'] );
	add_post_meta( $product_id, '_regular_price', $row['product_price'] );
	add_post_meta( $product_id, '_visibility', 'visible', true );
	add_post_meta( $product_id, '_downloadable', 'no' );
	add_post_meta( $product_id, '_virtual', 'no' );
	add_post_meta( $product_id, '_stock_status', 'instock' );
	add_post_meta( $product_id, '_manage_stock', 'yes' );
	add_post_meta( $product_id, '_stock', $row['product_in_stock'] );

	$category = wp_insert_term( $row['category_name'], 'product_cat' );
	if ( isset( $category->error_data['term_exists'] ) ) {
		$category_id = $category->error_data['term_exists'];
	} else {
		$category_id = $category['term_id'];
	}

	wp_set_object_terms( $product_id, [ $category_id ], 'product_cat' );

	$img_src = 'http://localhost/components/com_virtuemart/shop_image/product/' . $row['product_full_image'];
	saveImage( $product_id, $img_src, true );

	$importCount += 1;
}

echo "Imported $importCount<br>";
echo date( 'Y-m-d H:i:s' );

function getProduct( $sku ) {
	global $wpdb;

	$sql = 'SELECT post_id FROM ' . $wpdb->postmeta . "
        WHERE meta_key = '_sku' AND meta_value = '$sku' LIMIT 1";
	$product = $wpdb->get_results( $sql, ARRAY_A );

	return isset( $product[0]['post_id'] ) ? $product[0]['post_id'] : 0;
}

function saveImage( $product_id, $src, $main = false ) {
	if ( empty( $src ) ) {
		return;
	}

	$http_head = get_headers( $src );
	if ( stripos( $http_head[0], '200' ) === false ) {
		return;
	}

	$image_data = file_get_contents( $src );
	if ( empty( $image_data ) ) {
		return;
	}

	$upload_dir = wp_upload_dir();
	$filename = basename( $src );
	if ( wp_mkdir_p( $upload_dir['path'] . '/products' ) ) {
		$file = $upload_dir['path'] . '/products/' . $filename;
	}
	file_put_contents( $file, $image_data );

	$wp_filetype = wp_check_filetype( $filename, null );
	$attachment = [
		'post_mime_type' => $wp_filetype['type'],
		'post_title'     => sanitize_file_name( $filename ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	];
	$attach_id = wp_insert_attachment( $attachment, $file, $product_id );
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
	wp_update_attachment_metadata( $attach_id, $attach_data );

	if ( $main ) {
		set_post_thumbnail( $product_id, $attach_id );
	}

	return $attach_id;
}
