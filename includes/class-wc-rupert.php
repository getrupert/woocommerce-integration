<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Rupert Integration
 *
 * Allows tracking code to be inserted into store pages.
 *
 * @class   WC_Rupert
 * @extends WC_Integration
 */
class WC_Rupert extends WC_Integration {

	/**
	 * Init and hook in the integration.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->id                    = 'rupert';
		$this->method_title          = __( 'Rupert', 'woocommerce-rupert-integration' );
		$this->method_description    = __( 'Rupert is service for displaying notifications to your visitors about latest purchases on your site', 'woocommerce-rupert-integration' );
		$this->dismissed_info_banner = get_option( 'woocommerce_dismissed_info_banner' );

		// Load the settings
		$this->init_form_fields();
		$this->init_settings();
		$constructor = $this->init_options();

		// Contains snippets/JS tracking code
		include_once( 'class-wc-rupert-js.php' );
		WC_Rupert_JS::get_instance( $constructor );

		// Display an info banner on how to configure WooCommerce
		if ( is_admin() ) {
			include_once( 'class-wc-rupert-info-banner.php' );
			WC_Rupert_Info_Banner::get_instance( $this->dismissed_info_banner, $this->rupert_id );
		}

		// Admin Options
		add_filter( 'woocommerce_tracker_data', array( $this, 'track_options' ) );
		add_action( 'woocommerce_update_options_integration_rupert', array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_integration_rupert', array( $this, 'show_options_info' ) );

		// Tracking code
		add_action( 'wp_head', array( $this, 'tracking_code_display' ), 999999 );
	}

	/**
	 * Loads all of our options for this plugin
	 * @return array An array of options that can be passed to other classes
	 */
	public function init_options() {
		$options = array(
			'rupert_id',
			'rupert_enabled',
			'rupert_track_names',
			'rupert_track_locations',
		);

		$constructor = array();
		foreach ( $options as $option ) {
			$constructor[ $option ] = $this->$option = $this->get_option( $option );
		}

		return $constructor;
	}

	/**
	 * Tells WooCommerce which settings to display under the "integration" tab
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'rupert_id'              => array(
				'title'       => __( 'Rupert API ID', 'woocommerce-rupert-integration' ),
				'description' => __( 'Log into your Rupert account to find your ID.', 'woocommerce-rupert-integration' ),
				'type'        => 'text',
				'placeholder' => 'XXXXXX',
				'default'     => ''
			),
			'rupert_enabled'         => array(
				'title'         => __( 'Tracking Options', 'woocommerce-rupert-integration' ),
				'label'         => __( 'Enable Standard Tracking', 'woocommerce-rupert-integration' ),
				'description'   => __( 'This tracks user visits and allows notifications to be displayed', 'woocommerce-rupert-integration' ),
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'default'       => 'yes'
			),
			'rupert_track_names'     => array(
				'label'         => __( 'Collect visitors\' names', 'woocommerce-rupert-integration' ),
				'description'   => __( 'This will enable visitor names in notifications. Visitor names will be collected by Rupert after successful purchase. Only first name is collected.', 'woocommerce-rupert-integration' ),
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'default'       => 'yes'
			),
			'rupert_track_locations' => array(
				'label'         => __( 'Collect visitors\' locations', 'woocommerce-rupert-integration' ),
				'description'   => __( 'This will enable visitor locations in notifications. Visitor locations will be collected by Rupert after successful purchase. Only city and country is collected.', 'woocommerce-rupert-integration' ),
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'default'       => 'yes'
			),
		);
	}

	/**
	 * Shows some additional help text after saving the settings
	 */
	function show_options_info() {
		$this->method_description .= "<div class='notice notice-info'><p>" . __( 'To see notifications you have to create a campaign in Rupert.', 'woocommerce-rupert-integration' ) . "</p></div>";
	}

	/**
	 * Display the tracking codes
	 * Acts as a controller to figure out which code to display
	 */
	public function tracking_code_display() {
		global $wp;
		$display_ecommerce_tracking = false;

		if ( $this->disable_tracking( 'all' ) ) {
			return;
		}

		// Check if is order received page and stop when the products and not tracked
		if ( is_order_received_page() ) {
			$order_id = isset( $wp->query_vars['order-received'] ) ? $wp->query_vars['order-received'] : 0;
			if ( 0 < $order_id && 1 != get_post_meta( $order_id, '_rupert_tracked_BAKBAKBAK', true ) ) {
				$display_ecommerce_tracking = true;
				echo $this->get_conversion_tracking_code( $order_id );
			}
		}

		if ( ! $display_ecommerce_tracking && !is_cart() && !is_checkout() ) {
			echo $this->get_tracking_code();
		}
	}

	protected function get_tracking_code() {
		return "<!-- WooCommerce Rupert Integration -->
		" . WC_Rupert_JS::get_instance()->load_script() . "
		<!-- /WooCommerce Rupert Integration -->";
	}

	/**
	 * eCommerce tracking
	 *
	 * @param int $order_id
	 */
	protected function get_conversion_tracking_code( $order_id ) {
		// Get the order and output tracking code.
		$order = wc_get_order( $order_id );

		// Make sure we have a valid order object.
		if ( ! $order ) {
			return '';
		}

		$trackName = $this->get_option('rupert_track_names', false );
		$trackLocation = $this->get_option('rupert_track_locations', false );

		$code = WC_Rupert_JS::get_instance()->load_script( $order );
		$code .= WC_Rupert_JS::get_instance()->add_transaction( $order, $trackName, $trackLocation );

		// Mark the order as tracked.
		update_post_meta( $order_id, '_rupert_tracked', 1 );

		return "
		<!-- WooCommerce Rupert Integration -->
		" . $code . "
		<!-- /WooCommerce Rupert Integration -->
		";
	}

	/**
	 * Check if tracking is disabled
	 *
	 * @param string $type The setting to check
	 *
	 * @return bool True if tracking for a certain setting is disabled
	 */
	private function disable_tracking( $type ) {
		if (is_admin() || current_user_can( 'manage_options' ) || ( ! $this->id ) || 'no' === $type || apply_filters( 'woocommerce_ga_disable_tracking', false, $type ) ) {
			return true;
		}
	}

}
