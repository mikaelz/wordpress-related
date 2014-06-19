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
 * administrative side of the WordPress site.
 *
 */
if ( ! class_exists( 'Vubpay_Admin' ) ) :

class Vubpay_Admin {

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
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		$plugin = Vubpay::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add a plugin page action link pointing to the settings page.
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
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the custom post type menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 *   For reference: http://codex.wordpress.org/Roles_and_Capabilities
		 */
		$this->plugin_screen_hook_suffix = add_submenu_page(
			"edit.php?post_type=$this->plugin_slug",
			__( 'VÚB eCard payments settings', $this->plugin_slug ),
			__( 'Settings', $this->plugin_slug ),
			'manage_options',
			"settings",
			array( $this, 'display_plugin_settings' )
		);

		add_submenu_page(
			"edit.php?post_type=$this->plugin_slug",
			__( 'Generate VÚB eCard payment button', $this->plugin_slug ),
			__( 'Generate button', $this->plugin_slug ),
			'manage_options',
			"generate_button",
			array( $this, 'display_generate_button_subpage' )
		);

		add_action( 'admin_menu', array( $this, 'remove_add_submenu' ), 999 );
		add_action( 'admin_head', array( $this, 'remove_add_new_btn' ), 999 );

	}

	/**
	 * Remove Add new menu link from admin
	 *
	 * @since    1.0.0
	 */
	public function remove_add_submenu() {
		remove_submenu_page( "edit.php?post_type=$this->plugin_slug", "post-new.php?post_type=$this->plugin_slug" );
	}

	/**
	 * Remove Add new button link from All payments admin page
	 *
	 * @since    1.0.0
	 */
	public function remove_add_new_btn() {
		if ( $this->plugin_slug == get_post_type() ) {
			echo '<style type="text/css">
				#favorite-actions {display:none}
				.add-new-h2{display:none}
				.tablenav{display:none}
			</style>';
		}
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_settings() {

		if ( isset( $_POST['submit'] ) ) {

			if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], $this->plugin_slug . '-options' ) )
				die( __( '<div id="message" class="error fade"><p><strong>Action failed. Please go back and retry.</strong></p></div>', $this->plugin_slug ) );

			$whitelist_options = array(
				'client_id',
				'store_key',
				'gateway_url',
				'ok_url',
				'fail_url',
			);

			foreach ( $whitelist_options as $option ) {
				$option = "{$this->plugin_slug}_" . trim( $option );
				$value = null;
				if ( isset( $_POST[ $option ] ) )
					$value = wp_unslash( trim( $_POST[ $option ] ) );

				update_option( $option, $value );
			}

			/**
			 * Handle settings errors and return to options page
			 */
			// If no settings errors were registered add a general 'updated' message.
			if ( !count( get_settings_errors() ) )
				add_settings_error('general', 'settings_updated', __('Settings saved.'), 'updated');
			set_transient('settings_errors', get_settings_errors(), 30);

			/**
			 * Redirect back to the settings page that was submitted
			 */
			$goback = add_query_arg( 'settings-updated', 'true',  wp_get_referer() );
			die( __( '<div id="message" class="updated fade"><p><strong>Settings successfully updated.</strong></p></div>', $this->plugin_slug ) );
		}

		include_once( 'views/settings.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( "edit.php?post_type=$this->plugin_slug&page=settings" ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
	 * Render the generate button subpage
	 *
	 * @since    1.0.0
	 */
	public function display_generate_button_subpage() {

		$html_btn = $this->create_html_button();
		if ( isset( $_POST['submit'] ) ) {

			if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], $this->plugin_slug . '-options' ) )
				die( __( '<div id="message" class="error fade"><p><strong>Action failed. Please go back and retry.</strong></p></div>', $this->plugin_slug ) );

			$html_btn = $this->create_html_button();

		}

		include_once( 'views/generate_button.php' );
	}

	/**
	 * Create HTML payment button
	 *
	 */
	public function create_html_button() {

		$currency    = ! empty( $_POST['btn_currency'] ) ? intval( $_POST['btn_currency'] ) : '';
		$amount      = ! empty( $_POST['btn_amount'] ) ? intval( $_POST['btn_amount'] ) : '';
		$lang        = ! empty( $_POST['btn_lang'] ) ? esc_html( $_POST['btn_lang'] ) : '';
		$description = ! empty( $_POST['btn_description'] ) ? esc_html( $_POST['btn_description'] ) : '';
		$btn_text    = ! empty( $_POST['btn_text'] ) ? esc_html( $_POST['btn_text'] ) : '';

		$input_data = array(
			'amount' => $amount,
			'currency' => $currency,
			'description' => $description,
			'lang' => $lang,
		);

		$inputs = array();
		foreach ($input_data as $k => $v) {
			$inputs[] = "\t".'&lt;input type="hidden" name="'.$k.'" value="'.esc_attr( $v ).'" /&gt;';
		}

		return '
&lt;form name="btn-vubecard" id="btn-vubecard" class="btn-vubecard" action="'.site_url( $this->plugin_slug ).'/?action=request" method="post"&gt;
' . implode("\n", $inputs) . '
'."\t".'&lt;button type="submit"&gt;' . $btn_text . '&lt;/button&gt;
&lt;/form&gt;';

	}

}

endif;

