<?php

use Automattic\WooCommerce\Client;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../wp-load.php';

/**
 * Migrate products from remote WooCommerce via API
 */
class MigrateProducts
{
    // Number of products to migrate
    const IMPORT_LIMIT = 100;

    // Should be bigger than IMPORT_LIMIT
    const CURL_TIMEOUT = 600;

    const REMOTE_API_URL = 'http://localhost/REMOTE.COM';
    const REMOTE_API_KEY = 'ck_REPLACE_CONSUMER_KEY';
    const REMOTE_API_SECRET = 'cs_REPLACE_CONSUMER_SECRET';

    const LOCAL_API_URL = 'http://localhost/LOCAL.DEV';
    const LOCAL_API_KEY = 'ck_REPLACE_CONSUMER_KEY';
    const LOCAL_API_SECRET = 'cs_REPLACE_CONSUMER_SECRET';

    private $_localClientApi;
    private $_remoteClientApi;
    private $_importCount = 0;
    private $_processedCount = 0;
    private $_remoteProducts = [];
    private $_remoteProductCategories = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        error_reporting(-1);
        ob_end_flush();
        ini_set('display_errors', 1);
        ini_set('output_buffering', 1);
        date_default_timezone_set('Europe/Bratislava');

        $start = time();
        echo 'Start: '.date('d.m.Y H:i:s', $start).'<br>';
        ob_flush();
        flush();

        $this->_remoteClientApi = new Client(
            self::REMOTE_API_URL,
            self::REMOTE_API_KEY,
            self::REMOTE_API_SECRET,
            [
                'wp_api' => false,
                'version' => 'v3',
                'timeout' => self::CURL_TIMEOUT,
            ]
        );

        $this->setRemoteProductCategories();

        $this->_localClientApi = new Client(
            self::LOCAL_API_URL,
            self::LOCAL_API_KEY,
            self::LOCAL_API_SECRET,
            [
                'wp_api' => false,
                'version' => 'v3',
                'timeout' => self::CURL_TIMEOUT,
            ]
        );

        deactivate_plugins('ewww-image-optimizer/ewww-image-optimizer.php');

        $this->importProducts();

        $end = time();
        echo sprintf(
            'Import count: %s<br>End: %s<br>Took: %d s',
            $this->_importCount,
            date('d.m.Y H:i:s', $end),
            ($end - $start)
        );

        activate_plugins('ewww-image-optimizer/ewww-image-optimizer.php');
    }

    /**
     * Set remote product categories
     */
    private function setRemoteProductCategories()
    {
        $response = $this->_remoteClientApi->get(
            'products/categories',
            [
                'fields' => 'id,name'
            ]
        );

        foreach ($response['product_categories'] as $category) {
            $this->_remoteProductCategories[$category['name']] = $category['id'];
        }
    }

    /**
     * Import products on local
     */
    private function importProducts()
    {
        $offset = 0;
        do {
            $response = $this->_remoteClientApi->get(
                'products',
                [
                    'filter[limit]' => self::IMPORT_LIMIT,
                    'filter[offset]' => self::IMPORT_LIMIT * $offset++,
                ]
            );

            foreach ($response['products'] as $remoteProduct) {
                $this->_processedCount++;
                $data['product'] = $this->prepData($remoteProduct);

                if (empty($data['product']['title'])) {
                    continue;
                }

                $this->_localClientApi->post('products', $data);
                $this->_importCount++;
            }

            echo "Processed: $this->_processedCount<br>";
            ob_flush();
            flush();
        } while (!empty($response['products'][0]));
    }

    /**
     * Prepare data
     *
     * @param array $remoteData Array of remote data
     *
     * @return array
     */
    private function prepData($remoteProduct)
    {
        if (!empty($remoteProduct['sku']) && $this->productExists($remoteProduct['sku'])) {
            return [];
        }

        unset($remoteProduct['id']);
        unset($remoteProduct['create_at']);
        unset($remoteProduct['updated_at']);
        unset($remoteProduct['price_html']);
        unset($remoteProduct['related_ids']);
        unset($remoteProduct['upsell_ids']);
        unset($remoteProduct['cross_sell_ids']);
        unset($remoteProduct['parent_id']);
        unset($remoteProduct['permalink']);

        $remoteProduct['status'] = 'draft';
        $remoteProduct['price'] = ceil($remoteProduct['price']) * 26;
        $remoteProduct['regular_price'] = ceil($remoteProduct['regular_price']) * 26;
        $remoteProduct['sale_price'] = $remoteProduct['sale_price'] > 0 ? ceil($remoteProduct['sale_price']) * 26 : '';
        $remoteProduct['attributes'] = $this->translateAttributes($remoteProduct['attributes']);

        foreach ($remoteProduct['categories'] as $key => $categoryTitle) {
            $remoteProduct['categories'][$key] = $this->mapCategoryId($categoryTitle);
        }

        if (!isset($_GET['noimages']) && !empty($remoteProduct['images'][0]['src'])) {
            foreach($remoteProduct['images'] as $key => $image) {
                $remoteProduct['images'][$key] = [
                    'src' => $image['src'],
                    'position' => $key,
                ];
            }
        }

        foreach ($remoteProduct['variations'] as $key => $variation) {
            if ($this->productExists($variation['sku'])) {
                return [];
            }

            unset($remoteProduct['variations'][$key]['id']);
            unset($remoteProduct['variations'][$key]['created_at']);
            unset($remoteProduct['variations'][$key]['updated_at']);
            unset($remoteProduct['variations'][$key]['permalink']);

            $remoteProduct['variations'][$key]['price'] = ceil($remoteProduct['variations'][$key]['price']) * 26;
            $remoteProduct['variations'][$key]['regular_price'] = ceil($remoteProduct['variations'][$key]['regular_price']) * 26;
            $remoteProduct['variations'][$key]['attributes'] = $this->translateVariationAttributes($variation['attributes']);

            if (!isset($_GET['noimages']) && !empty($variation['image'][0]['src'])) {
                foreach ($remoteProduct['variations'][$key]['image'] as $img_key => $image) {
                    $remoteProduct['variations'][$key]['image'][$img_key] = [
                        'src' => $image['src'],
                        'position' => $img_key,
                    ];
                }
            }
        }

        return $remoteProduct;
    }


    /**
     * Check if products exists on local
     *
     * @param string   $sku SKU identifier
     *
     * @return boolean
     */
    private function productExists($sku)
    {
        $exists = false;
        $response = $this->_localClientApi->get(
            'products',
            [
                'filter[sku]' => $sku,
                'filter[post_status]' => 'any',
            ]
        );

        if (!empty($response['products'][0])) {
            $exists = true;
        }

        return $exists;
    }

    /**
     * Remap category ID
     *
     * @param int $categoryTitle Category Title
     *
     * @return int
     */
    private function mapCategoryId($categoryTitle)
    {
        $categoryId = $this->_remoteProductCategories[$categoryTitle];

        $mapping = array(
            REMOTE_CAT_ID => LOCAL_CAT_ID,
            REMOTE_CAT_ID2 => LOCAL_CAT_ID2,
        );

        return $mapping[$categoryId] ?? 0;
    }

    /**
     * Translate attributes
     *
     * @return array $attributes
     */
    private function translateAttributes($attributes)
    {
        foreach ($attributes as $key => $attribute) {
            switch ($attribute['name']) {
                case 'Attribute name':
                    $attributes[$key]['name'] = 'New Attribute name';
                    $attributes[$key]['slug'] = 'new-attribute-name';
                    $attributes[$key]['options'] = $this->translateAttribOptions($attribute['options']);
                    break;
            }
        }

        return $attributes;
    }

    /**
     * Translate attribute options
     *
     * @return array $options
     */
    private function translateAttribOptions($options)
    {
        $from = [
            'Option1',
            'Option2',
        ];

        $to = [
            'New Option1',
            'New Option2',
        ];

        foreach ($options as $key => $value) {
            $options[$key] = str_replace($from, $to, $value);
        }

        return $options;
    }

    /**
     * Translate variation attributes
     *
     * @return array $attributes
     */
    private function translateVariationAttributes($attributes)
    {
        foreach ($attributes as $key => $attribute) {
            switch ($attribute['name']) {
                case 'Attribute name':
                    $attributes[$key]['name'] = 'New Attribute name';
                    $attributes[$key]['slug'] = 'new-attribute-name';
                    $attributes[$key]['option'] = $this->translateVariationAttribOptions($attribute['option']);
                    break;
            }
        }

        return $attributes;
    }


    /**
     * Translate variation attribute option
     *
     * @return array $option
     */
    private function translateVariationAttribOptions($option)
    {
        $from = [
            'slug1',
            'slug2',
        ];

        $to = [
            'new-slug1',
            'new-slug2',
        ];

        return str_replace($from, $to, $option);
    }
}

return new MigrateProducts();
