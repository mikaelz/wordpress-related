<?php

declare( strict_types = 1 );

namespace Cron;

use WC_Product;
use WC_Product_Attribute;
use WP_Term;
use wpdb;

class UpdateAttributes {
	private wpdb $wpdb;

	public function __construct() {
		define( 'DOING_CRON', true );
		error_reporting( - 1 );
		set_time_limit( 600 );
		ini_set( 'display_errors', '1' );
		require_once __DIR__ . '/../wp-load.php';
		/** @var wpdb $wpdb */
		$this->wpdb = $wpdb;

		if ( taxonomy_exists( 'pa_priemer' ) ) {
			$this->updateDiameterAttributes();
		}
		if ( taxonomy_exists( 'pa_objem' ) ) {
			$this->updateVolumeAttributes();
		}
	}

	private function updateDiameterAttributes(): void {
		$products = $this->wpdb->get_results( "
		SELECT ID, post_title
		FROM {$this->wpdb->posts}
		WHERE post_type = 'product' AND post_title LIKE '%Ø%'
		" );
		foreach ( $products as $product ) {
			$wcProduct = wc_get_product( $product->ID );
			if ( ! $wcProduct instanceof WC_Product ) {
				continue;
			}
			if ( ! empty( $wcProduct->get_attribute( 'pa_priemer' ) ) ) {
				continue;
			}
			$replaced = str_replace( ['Ø', 'ø'], '||', $product->post_title );
			$diameter = explode( '||', $replaced );
			$diameter = explode( ',', $diameter[1] );
			$diameter = explode( ' ', $diameter[0] );
			$diameter = $diameter[0];
			if ( str_contains( $diameter, 'x' ) ) {
				continue;
			}
			if ( ! str_contains( $diameter, 'm' ) ) {
				continue;
			}
			$diameter = str_replace( ['mm', 'cm'], [' mm', ' cm'], $diameter );
			$this->saveAttribute( 'pa_priemer', $diameter, $wcProduct );
		}
	}

	private function updateVolumeAttributes(): void {
		$products = $this->wpdb->get_results( "
		SELECT ID, post_title
		FROM {$this->wpdb->posts}
		WHERE post_type = 'product' AND post_title LIKE '%ml %'
		" );
		foreach ( $products as $product ) {
			$wcProduct = wc_get_product( $product->ID );
			if ( ! $wcProduct instanceof WC_Product ) {
				continue;
			}
			if ( ! empty( $wcProduct->get_attribute( 'pa_objem' ) ) ) {
				continue;
			}
			if ( preg_match( '/\b(?P<value>\d+(?:[.,]\d+)?)[\s]*(?P<unit>ml)\b/i', $product->post_title, $matches ) ) {
				$volume = $matches['value'] . ' ' . $matches['unit'];
				$this->saveAttribute( 'pa_objem', $volume, $wcProduct );
			}
		}
	}

	private function saveAttribute( string $taxonomy, string $value, WC_Product $wcProduct ): void {
		$term = get_term_by( 'name', $value, $taxonomy );
		if ( $term instanceof WP_Term ) {
			$termId = $term->term_id;
		} else {
			$term = wp_insert_term( $value, $taxonomy );
			$termId = $term['term_id'];
		}

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
		$attribute->set_name( $taxonomy );
		$attribute->set_visible( true );
		$attribute->set_variation( false );
		$attribute->set_options( [$termId] );

		$attributes = $wcProduct->get_attributes();
		$attributes[] = $attribute;
		$wcProduct->set_attributes( $attributes );
		$wcProduct->save();
	}
}

return new UpdateAttributes();
