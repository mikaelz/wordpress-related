<?php
/**
 * Plugin Name.
 *
 * @package   Woocommerce_Email_Attachment
 * @author    Michal Zuber <info@nevilleweb.sk>
 * @license   GPL-2.0+
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 * @package Woocommerce_Email_Attachment_Admin
 * @author    Michal Zuber <info@nevilleweb.sk>
 */
class Woocommerce_Email_Attachment_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		$plugin = Woocommerce_Email_Attachment::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ), 60 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

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
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Woocommerce_Email_Attachment::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->screen_hook_suffix ) ) {
			return;
		}

		wp_enqueue_media();

		$screen = get_current_screen();
		if ( $this->screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Woocommerce_Email_Attachment::VERSION );

			$site = array(
				'url' => get_site_url() . '/wp-content',
			);
			wp_localize_script( $this->plugin_slug . '-admin-script', 'site', $site );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 */
		$this->screen_hook_suffix = add_submenu_page(
			'woocommerce',
			__( 'Woocommerce Email Attachment', $this->plugin_slug ),
			__( 'WC Email Attachment', $this->plugin_slug ),
			'upload_files',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		if ( isset( $_POST['processing_order_attachment'] ) ) {
			$attachments = array();
			foreach ( $_POST['processing_order_attachment'] as $attachment ) {
				if ( is_file( WP_CONTENT_DIR . $attachment ) ) {
					$attachments[] = $attachment;
				}
			}

			update_option( '_wc_processing_order_email_attachments', $attachments );
		}

		$processing_order_attachments = get_option( '_wc_processing_order_email_attachments' );

		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

}

