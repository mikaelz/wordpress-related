<?php
/**
 * @package   vubpay
 * @author    Michal Zuber <info@nevilleweb.sk>
 * @license   GPL-2.0+
 * @link      http://nevilleweb.sk
 * @copyright 2014 Michal Zuber
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 */
if ( ! class_exists( 'Vubpay' ) ) :

class Vubpay {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'vubpay';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'init', array( $this, 'reg_cpt' ) );
		add_filter( 'the_content', array( $this, 'process_request' ) );

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register custom post type and taxonomy
	 *
	 * @return	void
	 */
	public function reg_cpt() {

		$slug = basename( dirname( __FILE__ ) );

		$labels = array(
			'name'               => _x( 'VÚB eCard Payments', 'post type general name', $slug ),
			'singular_name'      => _x( 'VÚB eCard Payment', 'post type singular name', $slug ),
			'menu_name'          => _x( 'VÚB eCard Payments', 'admin menu', $slug ),
			'name_admin_bar'     => _x( 'VÚB eCard Payment', 'add new on admin bar', $slug ),
			'add_new'            => _x( 'Add New', 'payment', $slug ),
			'add_new_item'       => __( 'Add New Payment', $slug ),
			'new_item'           => __( 'New Payment', $slug ),
			'edit_item'          => __( 'Edit Payment', $slug ),
			'view_item'          => __( 'View Payment', $slug ),
			'all_items'          => __( 'All Payments', $slug ),
			'search_items'       => __( 'Search Payments', $slug ),
			'parent_item_colon'  => __( 'Parent Payments:', $slug ),
			'not_found'          => __( 'No payments found.', $slug ),
			'not_found_in_trash' => __( 'No payments found in Trash.', $slug )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => $slug ),
			'capability_type'    => 'post',
			'capabilities'       => array( 'create_posts' => true ),
			'map_meta_cap'       => false, // Disable Edit/Delete
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'author', 'excerpt', 'comments' )
		);
		register_post_type( $slug, $args );

		$labels = array(
			'name'                       => _x( 'Statuses', 'taxonomy general name' ),
			'singular_name'              => _x( 'Status', 'taxonomy singular name' ),
			'search_items'               => __( 'Search Statuses' ),
			'popular_items'              => __( 'Popular Statuses' ),
			'all_items'                  => __( 'All Statuses' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Status' ),
			'update_item'                => __( 'Update Status' ),
			'add_new_item'               => __( 'Add New Status' ),
			'new_item_name'              => __( 'New Status Name' ),
			'separate_items_with_commas' => __( 'Separate writers with commas' ),
			'add_or_remove_items'        => __( 'Add or remove writers' ),
			'choose_from_most_used'      => __( 'Choose from the most used writers' ),
			'not_found'                  => __( 'No writers found.' ),
			'menu_name'                  => __( 'Statuses' ),
		);

		$args = array(
			'hierarchical'          => false,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => false,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'status' ),
		);
		register_taxonomy( 'status', $slug, $args );

		$statuses = array(
			'pending' => 'Pending payments status',
			'failed'  => 'Failed payments status',
			'aborted' => 'Aborted payments status',
			'success' => 'Successful payments status',
		);
		foreach ( $statuses as $status => $description ) {
			wp_insert_term(
				ucfirst( $status ),
				'status',
				array(
					'description'=> $description,
					'slug' => $status,
				)
			);
		}

	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Process request from POST
	 *
	 * @since    1.0.0
	 */
	public function process_request() {

		if ( empty( $_GET['action'] ) || empty( $_POST['amount'] ) )
			return;

		$currencies = array(
			978 => 'EUR',
			203 => 'CZK',
			348 => 'HUF',
			985 => 'PLN',
		);

		$valid_lang = array( 'en', 'hu', 'sk', 'cz' );

		$lang        = in_array( $_POST['lang'], $valid_lang ) ? esc_html( $_POST['lang'] ) : '';
		$currency    = in_array( $_POST['currency'], array_keys( $currencies ) ) ? intval( $_POST['currency'] ) : '';
		$amount      = ! empty( $_POST['amount'] ) ? intval( $_POST['amount'] ) : '';
		$description = ! empty( $_POST['description'] ) ? esc_html( $_POST['description'] ) : '';

		// Save payment request
		$post = array(
			'post_title' => __( 'Payment ', $this->plugin_slug ),
			'post_content' => "$amount {$currencies[$currency]}\n$description",
			'post_type' => $this->plugin_slug,
			'post_category' => array( 'pending' ),
		);

		$order_id = wp_insert_post( $post );

		if ( 1 > $order_id )
			wp_die( __( "Couldn't save payment. Please, try again.", $this->plugin_slug ) );

		$rnd         = uniqid();
		$trantype    = 'Auth';
		$client_id   = get_option( $this->plugin_slug . '_client_id');
		$ok_url      = site_url() . get_option( $this->plugin_slug . '_ok_url');
		$fail_url    = site_url() . get_option( $this->plugin_slug . '_fail_url');
		$hash = implode('', array(
			$client_id,
			$order_id,
			$amount,
			$ok_url,
			$fail_url,
			$trantype,
			$rnd,
			get_option( $this->plugin_slug . '_store_key' ),
		));
		$hash = base64_encode(pack('H*', sha1($hash)));

		$data = array(
			'clientid'     =>  $client_id,
			'storetype'    =>  '3d_pay_hosting',
			'trantype'     =>  $trantype,
			'amount'       =>  $amount,
			'currency'     =>  $currency,
			'oid'          =>  $order_id,
			'encoding'     =>  'utf-8',
			'okUrl'        =>  $ok_url,
			'failUrl'      =>  $fail_url,
			'lang'         =>  $lang,
			'rnd'          =>  $rnd,
			'hash'         =>  $hash,
			'description'  =>  $description,
		);

		$inputs = array();
		foreach ($data as $k => $v) {
			$inputs[] = "<input type='hidden' name='$k' value='$v'/>";
		}

		return '
		<center>
			<p>' . __( 'In case that you were not redirected, please click the &quot;Continue to the payment gateway&quot; button.', $this->plugin_slug ) . '</p>
			<form name="vubecard" action="' . get_option( $this->plugin_slug . '_gateway_url' ) . '" method="post">
				' . implode("\n", $inputs) . '
				<button type="submit">' . __( 'Continue to the payment gateway', $this->plugin_slug ) . '</button>
			</form>
		</center>
		<script>//setTimeout("document.vubecard.submit()", 200);</script>';

	}

	public function process_response() {

		$order_id = intval( $_REQUEST['oid'] );
		$trans_id = sanitize_text_field( $_REQUEST['TransId'] );
		$server_data = $request_data = '';

		foreach ( $_SERVER as $key => $value )
			$server_data .= "$key: $value\n";
		foreach ( $_REQUEST as $key => $value )
			$request_data .= "$key: $value\n";

		switch ( $_REQUEST['Response'] ) {
			case 'Approved':
				$msg = __( '<div id="message" class="updated fade"><p>Payment successful. Thank you. Have a nice day.</p></div>', $this->plugin_slug );
				break;
		
			case 'Declined':
				$msg = '<div id="message" class="error fade"><p>Payment was declined. ' . sanitize_text_field( $_POST['ErrMsg'] ) . '</p></div>';
				break;
		
			case 'Error':
				$msg = '<div id="message" class="error fade"><p>Payment error. ' . sanitize_text_field( $_POST['ErrMsg'] ) . '</p></div>';
				break;
		
			default:
				$msg = __( '<div id="message" class="error fade"><p>Payment failed. Unknown response from bank gateway.</p></div>', $this->plugin_slug );
		}

		// Save response
		$post = array(
			'post_title' => __( 'Payment ', $this->plugin_slug ) . $order_id,
			'post_content' => "$request_data\n\n$server_data",
			'post_status' => 'private',
			'post_type' => $this->plugin_slug,
			'post_category' => '', // @TODO add taxonomy Status for $_REQUEST['Response'] Approved | Error | Other ?
		);

		wp_insert_post( $post );

	}

}

endif;

