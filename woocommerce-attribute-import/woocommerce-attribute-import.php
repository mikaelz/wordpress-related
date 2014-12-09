<?php
/*
Plugin Name: WooCommerce attribute import 
Description: Import WooCommerce product attributes from VirtueMart database
Version: 0.2
Author: Michal Zuber
Author URI: http://www.nevilleweb.sk
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('admin_menu', 'wai_admin_menu');

function wai_admin_menu() {
    add_menu_page( 'WooCommerce attribute import', 'WC attrib import', 'manage_options', 'import-product-attributes', 'wai_admin_page' );
}

function wai_admin_page() {

    if ( ! is_admin() ) return;

    $action = isset($_GET['action']) ? $_GET['action'] : '';

    switch ( $action ) {

        case 'import':
            wai::import_product_attrib();
            break;

        default:
            echo wai::get_admin_page();

    }

}

/**
 * Main class
 **/
class wai {
    
	public $version = '1.0';
    private static $instance = null;

    // DB login credentials for data export
    private static $db_host = 'DB_HOST';
    private static $db_user = 'DB_USER';
    private static $db_pass = 'DB_PASS';
    private static $db_name = 'DB_NAME';
    private static $db_prefix = 'VIRTUEMART_PREFIX';

    private function __construct() {

    }

    public static function get_instance() {

        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;

    }

    public static function get_admin_page() {

        $products = get_term( 2, 'product_type' );
        ?>
        <div class="wrap">
            <h2>Import WooCommerce product attributes from VirtueMart</h2>
            <p>Product count: <b><?php echo $products->count ?> pcs</b></p>
            <ol>
                <li>
                    <form action="<?php echo admin_url( 'admin.php' )?>">
                        <input type="hidden" name="page" id="page" value="<?php echo $_REQUEST['page'] ?>"/>
                        <input type="hidden" name="action" id="action" value="import"/>
                        <p class="submit"><input type="submit" value="Start import" class="button button-primary" id="submit" name="submit"></p>
                    </form>
                </li>
            </ol>
        </div>
        <?php

    }


    /**
     * Import product attributes
     *
     * @param void
     * @return string
     **/
    public static function import_product_attrib() {

        global $wpdb;

        set_time_limit( 0 );

        // get VirtueMart param
        $prefix = self::$db_prefix;
        $virtuemart = new wpdb( self::$db_user, self::$db_pass, self::$db_name, self::$db_host );

        // Get VirtueMart product attributes
        $q = "SELECT *
              FROM {$prefix}vm_product p 
              LEFT JOIN {$prefix}vm_product_type_1 t ON p.product_id = t.product_id
              LEFT JOIN {$prefix}vm_product_mf_xref mf ON p.product_id = mf.product_id
              LEFT JOIN {$prefix}vm_manufacturer m ON m.manufacturer_id = mf.manufacturer_id
              -- LIMIT 50";
        $products = $virtuemart->get_results( $q, 'ARRAY_A' );

        $imported = 0;
        $started = date('Y-m-d H:i:s');
        
        $attributes = array();
        foreach ($products as $vm_attributes) {

            // Get product ID by sku
            $q = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_sku' AND meta_value = %s"; 
            $post_id = $wpdb->get_var( $wpdb->prepare( $q, $vm_attributes['product_sku'] ) );

            // Unset key which won't be attribute
            unset( $vm_attributes['product_sku'] );

            $position = 0;
            foreach ($vm_attributes as $meta_key => $meta_value) {

                $meta_key = 'pa_' . $meta_key;
                $meta_value = trim( $meta_value );

                $term = term_exists( $meta_value, $meta_key );
                if ( $term === 0 && $term === null ) {
                    $term = wp_insert_term( $meta_value, $meta_key );
                }

                // Update post terms
                wp_set_object_terms( $post_id, $meta_value, $meta_key );

                // Prepare attributes
                // Src: /wp-content/plugins/woocommerce/includes/class-wc-ajax.php method save_attributes()
                $attributes[ $meta_key ] = array(
                    'name'         => $meta_key,
                    'value'        => '',
                    'position'     => $position++,
                    'is_variation' => 0,
                    'is_visible'   => 1,
                    'is_taxonomy'  => 1,
                );

                if ( $imported % 100 == 0 )
                    echo "Imported $imported";
            }

            update_post_meta( $post_id, '_product_attributes', $attributes );

            $imported++;
        }

        echo '
        <div class="wrap">
            <h2>Import WooCommerce product attributes from VirtueMart</h2>
            <p>Started @ ' . $started . '</p>
            <p>Imported: ' . $imported . ' products</p>
            <p>Finished @ ' . date('Y-m-d H:i:s') . '</p>
        </div>';

    }

}

