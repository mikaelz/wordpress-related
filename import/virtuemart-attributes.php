<?php

require __DIR__ . '/../wp-load.php';
date_default_timezone_set( 'Europe/Bratislava' );
error_reporting( - 1 );
ini_set( 'display_errors', 1 );
set_time_limit( 600 );

class ImportAttributesFromVirtuemart {

	private static $db_host = 'localhost';
	private static $db_user = 'root';
	private static $db_pass = 'root';
	private static $db_name = 'dbName';
	private static $db_prefix = 'jos_';

	/** @var wpdb */
	private $wpdb;

	/**
	 * ImportAttributesFromVirtuemart constructor.
	 *
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
		$this->import_product_attrib();
	}

	/**
	 * Import product attributes
	 *
	 * @param void
	 *
	 * @return string
	 **/
	public function import_product_attrib() {
		$prefix = self::$db_prefix;
		$virtuemart = new wpdb( self::$db_user, self::$db_pass, self::$db_name, self::$db_host );

		$offset = 0;
		$limit = 200;
		$imported = 0;
		$date_format = 'Y-m-d H:i:s';
		$started = date( $date_format );
		echo 'Started @ ' . $started . '<br>';
		wp_ob_end_flush_all();
		flush();

		do {
			$products = [];
			$results = $virtuemart->get_results( "SELECT product_id FROM {$prefix}vm_product LIMIT {$offset}, {$limit}", 'ARRAY_A' );
			if ( empty( $results ) ) {
				continue;
			}
			foreach ( $results as $product ) {
				$products[ $product['product_id'] ] = $product['product_id'];
			}
			$in_products = implode( ',', $products );
			$sql = "SELECT product_sku, sezona as sezona, rychlostnindex as rychlostnyindex, sirka, vyska_profil as vyska, priemer_rafika as radius, fuel_saving as usporapaliva, wet, noise, mf_name as vyrobca
	              FROM {$prefix}vm_product p
	              LEFT JOIN {$prefix}vm_product_type_1 t ON p.product_id = t.product_id
	              LEFT JOIN {$prefix}vm_product_mf_xref mf ON p.product_id = mf.product_id
	              LEFT JOIN {$prefix}vm_manufacturer m ON m.manufacturer_id = mf.manufacturer_id
				  WHERE p.product_id IN ({$in_products})
		    ";
			$products = $virtuemart->get_results( $sql, 'ARRAY_A' );

			$attributes = [];
			foreach ( $products as $vm_attributes ) {
				$sql = "SELECT post_id FROM {$this->wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value = %s";
				$post_id = $this->wpdb->get_var( $this->wpdb->prepare( $sql, $vm_attributes['product_sku'] ) );
				if ( empty( $post_id ) ) {
					continue;
				}

				// Unset key which won't be attribute
				unset( $vm_attributes['product_sku'] );

				$position = 0;
				foreach ( $vm_attributes as $meta_key => $meta_value ) {
					$meta_key = 'pa_' . $meta_key;
					$meta_value = trim( $meta_value );

					// Update post terms
					wp_set_object_terms( $post_id, $meta_value, $meta_key );

					// Prepare attributes
					// Src: /wp-content/plugins/woocommerce/includes/class-wc-ajax.php method save_attributes()
					$attributes[ $meta_key ] = [
						'name'         => $meta_key,
						'value'        => '',
						'position'     => $position ++,
						'is_variation' => 0,
						'is_visible'   => 1,
						'is_taxonomy'  => 1,
					];
				}

				update_post_meta( $post_id, '_product_attributes', $attributes );

				$imported ++;
			}

			$offset += $limit;
			printf( "Offset %d @ %s<br>", $offset, date( $date_format ) );
			wp_ob_end_flush_all();
			flush();
		} while ( ! empty( $products ) );

		$virtuemart->close();

		echo 'Imported: ' . $imported . ' products<br>
            Finished @ ' . date( $date_format ) . '<br>';
	}
}

return new ImportAttributesFromVirtuemart( $wpdb );
