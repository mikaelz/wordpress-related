<?php

namespace nevilleweb;

class ImportStock {
	/** @var wpdb */
	private $wpdb;

	/** @var string */
	private $logFile = '';

	public function __construct() {
		require dirname( __FILE__ ) . '/config.php';
		require dirname( __FILE__ ) . '/../wp-load.php';
		error_reporting( - 1 );
		set_time_limit( 600 );
		date_default_timezone_set( 'Europe/Bratislava' );

		/** @var wpdb $wpdb */
		$this->wpdb = $wpdb;
		$this->logFile = __DIR__ . '/log/' . str_replace( '.php', '.log', basename( __FILE__ ) );

		$this->log( sprintf(
			'Start %s from %s',
			date( 'c' ),
			$_SERVER['REMOTE_ADDR']
		) );

		$this->importStock();

		$this->log( sprintf(
			'Finish %s from %s',
			date( 'c' ),
			$_SERVER['REMOTE_ADDR']
		) );

		if ( ! isset( $_GET['quiet'] ) ) {
			printf( "Log at %s <br>", $this->logFile );
		}
	}

	private function log( $data ) {
		if ( ! is_dir( __DIR__ . '/log' ) ) {
			mkdir( __DIR__ . '/log' );
		}

        // Monthly rotation
		if ( ! isset( $this->logPurged )
		     && is_file( $this->logFile ) && date( 'm', filemtime( $this->logFile ) ) != date( 'm' )
		) {
			$this->logPurged = true;
			unlink( $this->logFile );
		}

		file_put_contents( $this->logFile, $data . PHP_EOL, FILE_APPEND );
	}

	private function importStock() {
		$processed = 0;
		$paging = 1;
		$items = $this->getItemsFromTradeGecko( '/products', $paging ++ );
		while ( $items->meta->total != $processed ) {
			foreach ( $items->products as $product ) {
				$processed ++;
				$productId = $this->wpdb->get_results(
					$this->wpdb->prepare(
						"SELECT ID FROM {$this->wpdb->posts} WHERE post_title = %s AND post_type = 'product'",
						$product->name
					)
				);
				$productId = reset( $productId );

				if ( empty( $productId ) ) {
					continue;
				}

				if ( update_post_meta( $productId, '_stock', $product->quantity ) ) {
					$this->log( sprintf( 'Product %d (%s) to %d', $productId, get_permalink( $productId ), $product->quantity ) );
				}
			}

			$items = $this->getItemsFromTradeGecko( '/products', $paging ++ );
		}

		$processed = 0;
		$paging = 1;
		$items = $this->getItemsFromTradeGecko( '/variants', $paging ++ );
		while ( $items->meta->total != $processed ) {
			foreach ( $items->variants as $product ) {
				$processed ++;
				$post = $this->wpdb->get_results(
					$this->wpdb->prepare(
						"SELECT post_id, post_parent
						FROM {$this->wpdb->postmeta} pm
						LEFT JOIN {$this->wpdb->posts} p ON pm.post_id = p.ID
						WHERE meta_key = '_sku' AND meta_value = %s",
						$product->sku
					)
				);
				$post = reset( $post );

				if ( empty( $post->post_id ) ) {
					continue;
				}

				if ( update_post_meta( $post->post_id, '_stock', $product->available_stock ) ) {
					$this->log( sprintf(
						'Variant %d (%s) to %d',
						$post->post_id,
						get_permalink( $post->post_parent !== 0 ? $post->post_parent : $post->post_id ),
						$product->available_stock
					) );
				}
			}

			$items = $this->getItemsFromTradeGecko( '/variants', $paging ++ );
		}
	}

	private function getItemsFromTradeGecko( $path, $page = 1 ) {
		$url = sprintf(
			'%s%s?page=%d',
            TG_API_URL,
			$path,
			$page
		);
		if ( ! isset( $_GET['all'] ) ) {
			$days = $_GET['days'] ?? 1;
			$url .= sprintf( '&updated_at_min=%s', date( 'c', strtotime( "-$days days" ) ) );
		}
		if ( ! isset( $_GET['quiet'] ) ) {
			echo "$url<br>";
		}

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Authorization: Bearer ' . TG_PRIVILIGED_CODE,
		] );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HEADER, 1 );
		$response = curl_exec( $ch );
		if ( curl_errno( $ch ) ) {
			echo curl_error( $ch );
		}
		curl_close( $ch );
		list( $header, $body ) = explode( "\r\n\r\n", $response, 2 );

		$header = explode( "\n", $header );
		foreach ( $header as $row ) {
			if ( strpos( $row, 'X-Rate-Limit-Remaining' ) !== false ) {
				$error = sprintf(
					"Rate limit hit. Try after %s",
					date( 'H:i:s', time() + preg_replace( '/[^0-9]/', '', $row ) )
				);
				$this->log( $error );
				echo "$error<br>";
				exit;
			}
		}

		return json_decode( $body );
	}
}

return new ImportStock();
