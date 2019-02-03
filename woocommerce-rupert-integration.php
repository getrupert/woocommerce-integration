<?php
/**
 * Plugin Name: WooCommerce Rupert Integration
 * Plugin URI: http://www.getrupert.com/integration/woocommerce/
 * Description: Allows Rupert tracking code to be inserted into WooCommerce store pages.
 * Author: GetRupert
 * Author URI: https://www.getrupert.com
 * Version: 1.0.0
 * WC requires at least: 2.1
 * WC tested up to: 3.5
 * License: GPLv2 or later
 * Text Domain: woocommerce-rupert-integration
 * Domain Path: languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Rupert_Integration' ) ) {

	/**
	 * WooCommerce Rupert Integration main class.
	 */
	class WC_Rupert_Integration {

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		const VERSION = '1.0.0';

		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Initialize the plugin.
		 */
		public function __construct() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				return;
			}

			// Load plugin text domain
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

			// Checks with WooCommerce is installed.
			if ( class_exists( 'WC_Integration' ) && defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '2.1-beta-1', '>=' ) ) {
				include_once 'includes/class-wc-rupert.php';

				// Register the integration.
				add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			}

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_links' ) );
		}

		public function plugin_links( $links ) {
			$settings_url = add_query_arg(
				array(
					'page' => 'wc-settings',
					'tab' => 'integration',
				),
				admin_url( 'admin.php' )
			);

			$plugin_links = array(
				'<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'woocommerce-rupert-integration' ) . '</a>',
				'<a href="https://wordpress.org/support/plugin/woocommerce-rupert-integration">' . __( 'Support', 'woocommerce-rupert-integration' ) . '</a>',
			);

			return array_merge( $plugin_links, $links );
		}

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @return void
		 */
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-rupert-integration' );

			load_textdomain( 'woocommerce-rupert-integration', trailingslashit( WP_LANG_DIR ) . 'woocommerce-rupert-integration/woocommerce-rupert-integration-' . $locale . '.mo' );
			load_plugin_textdomain( 'woocommerce-rupert-integration', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * WooCommerce fallback notice.
		 *
		 * @return string
		 */
		public function woocommerce_missing_notice() {
			echo '<div class="error"><p>' . sprintf( __( 'WooCommerce Rupert depends on the last version of %s to work!', 'woocommerce-rupert-integration' ), '<a href="http://www.woothemes.com/woocommerce/" target="_blank">' . __( 'WooCommerce', 'woocommerce-rupert-integration' ) . '</a>' ) . '</p></div>';
		}

		/**
		 * Add a new integration to WooCommerce.
		 *
		 * @param  array $integrations WooCommerce integrations.
		 *
		 * @return array               Rupert integration.
		 */
		public function add_integration( $integrations ) {
			$integrations[] = 'WC_Rupert';

			return $integrations;
		}
	}

	add_action( 'plugins_loaded', array( 'WC_Rupert_Integration', 'get_instance' ), 0 );

}
