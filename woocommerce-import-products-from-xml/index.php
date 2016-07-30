<?php

define('DOING_CRON', true);
require_once dirname(__FILE__).'/../wp-load.php';
require ABSPATH.'wp-admin/includes/image.php';
error_reporting(-1);
set_time_limit(600);

$importXml = new ImportXml($wpdb);
echo $importXml->import();

class ImportXml
{
    const UPLOAD_DIR = ABSPATH.'/wp-content/uploads';
    const XML_URL = 'http://www.example.sk/najnakup_xml.php?token=b354c24c9dd736ed1450b283bc682e7a';
    const XML_PATH = self::UPLOAD_DIR.'/example.xml';

    private $item_count;
    private $start;
    private $wpdb;

    public function __construct($wpdb)
    {
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR);
        }

        $this->start = time();
        $this->wpdb = $wpdb;

        $this->xml = simplexml_load_string($this->getXml(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE);
    }

    public function import()
    {
        foreach ($this->xml as $item) {
            $this->insertProduct($item);
            ++$this->item_count;
        }

        return sprintf('Start: %s<br>Items: %s<br>End: %s', $this->start, $this->item_count, time());
    }

    private function getXml()
    {
        $this->updateXml();

        $xml = file_get_contents(self::XML_PATH);
        if (empty($xml)) {
            throw new Exception('Empty XML');
        }

        return $xml;
    }

    private function updateXml()
    {
        if (filemtime(self::XML_PATH) < strtotime('-1 hour')) {
            file_put_contents(self::XML_PATH, file_get_contents(self::XML_URL));
        }
    }

    private function addProductToCategory($productId, $category)
    {
        if (empty($category)) {
            return;
        }

        $slug = sanitize_title($category);
        $category_term = get_term_by('slug', $slug, 'product_cat');
        if (empty($category_term->term_id)) {
            $category_term = wp_insert_term($category, 'product_cat');
        }
        if (!empty($category_term->term_id)) {
            wp_set_object_terms($productId, $category_term->term_id, 'product_cat');
        }
    }

    private function getProductId($productId)
    {
        $sql = 'SELECT post_id FROM '.$this->wpdb->postmeta."
                WHERE meta_key = '_exampleid' AND meta_value = $productId LIMIT 1";
        $product = $this->wpdb->get_results($sql, ARRAY_A);

        return isset($product[0]['post_id']) ? $product[0]['post_id'] : 0;
    }

    private function insertProduct($item)
    {
        $productId = $this->getProductId($item->ITEM_ID);
        if ($productId) {
            $this->updateProduct($productId, $item);

            return;
        }

        $product = array(
            'post_title' => $item->NAME,
            'post_content' => $item->DESCRIPTION,
            'post_status' => 'publish',
            'post_type' => 'product',
        );
        $productId = wp_insert_post($product);

        add_post_meta($productId, '_visibility', 'visible', true);
        add_post_meta($productId, '_regular_price', (float) $item->PRICE);
        add_post_meta($productId, '_price', (float) $item->PRICE);

        $this->insertImage($productId, $item);
        $this->addProductToCategory($productId, $item->CATEGORY);
        $this->updateProductMeta($productId, $item);
    }

    private function updateProduct($productId, $item)
    {
        $product = array(
            'ID' => $productId,
            'post_title' => $item->NAME,
            'post_content' => $item->DESCRIPTION,
        );
        wp_update_post($product);

        $this->updateProductMeta($productId, $item);
    }

    private function updateProductMeta($productId, $item)
    {
        update_post_meta($productId, '_exampleid', (int) $item->ITEM_ID);
        update_post_meta($productId, '_sku', (string) $item->CODE);
        update_post_meta($productId, '_downloadable', 'no');
        update_post_meta($productId, '_virtual', 'no');
        update_post_meta($productId, '_stock_status', 'instock');
        update_post_meta($productId, '_manage_stock', 'no');
    }

    private function insertImage($productId, $item)
    {
        $img_url = (string) $item->IMAGE_URL;
        if ($productId > 0 && !empty($img_url)) {
            $http_head = get_headers($img_url);
            if (stripos($http_head[0], '200')) {
                $photo_url = $img_url;
            }

            if (!empty($photo_url)) {
                $image_data = file_get_contents($photo_url);
                if (!empty($image_data)) {
                    $upload_dir = wp_upload_dir();
                    $filename = basename($photo_url);
                    $file = $upload_dir['basedir'].'/'.$filename;
                    if (wp_mkdir_p($upload_dir['path'])) {
                        $file = $upload_dir['path'].'/'.$filename;
                    }
                    file_put_contents($file, $image_data);

                    $wp_filetype = wp_check_filetype($filename, null);
                    $attachment = array(
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title' => sanitize_file_name($filename),
                        'post_content' => '',
                        'post_status' => 'inherit',
                    );
                    $attach_id = wp_insert_attachment($attachment, $file, $productId);
                    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
                    wp_update_attachment_metadata($attach_id, $attach_data);

                    set_post_thumbnail($productId, $attach_id);
                }
            }
        }
    }
}
