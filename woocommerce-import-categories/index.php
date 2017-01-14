<?php
/**
 * Import categories and subcategories from CSV(;) into WooCommerce
 */

require __DIR__.'/wp-load.php';
error_reporting(-1);
set_time_limit(600);

$item_count = 0;
$parent = 0;
$execStart = date('Y-m-d H:i:s');

if (($handle = fopen(__DIR__.'/test.csv', 'r')) !== false) {
    while (($data = fgetcsv($handle, 1000, ';')) !== false) {
        // Insert category
        $slug = sanitize_title($data[0]);
        $category_term = get_term_by('slug', $slug, 'product_cat');
        if (!empty($slug) && empty($category_term->term_id)) {
            $category = wp_insert_term($data[0], 'product_cat');
            $parent = $category['term_id'];
        }

        // Insert subcategory
        $slug = sanitize_title($data[1]);
        if (!empty($slug) && $parent > 0) {
            $category = wp_insert_term($data[1], 'product_cat', ['parent' => $parent]);
        }

        $item_count++;
    }
    fclose($handle);
}

echo $execStart.'<br>';
echo "Imported $item_count<br>";
echo date('Y-m-d H:i:s');
