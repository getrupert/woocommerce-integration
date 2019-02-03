<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Rupert_JS class
 *
 * JS for recording Rupert info
 */
class WC_Rupert_JS {

	/** @var object Class Instance */
	private static $instance;

	/** @var array Inherited options */
	private static $options;

	/**
	 * Get the class instance
	 */
	public static function get_instance( $options = array() ) {
		return null === self::$instance ? ( self::$instance = new self( $options ) ) : self::$instance;
	}

	/**
	 * Constructor
	 * Takes our options from the parent class so we can later use them in the JS snippets
	 */
	public function __construct( $options = array() ) {
		self::$options = $options;
	}

	/**
	 * Return one of our options
	 * @param  string $option Key/name for the option
	 * @return string         Value of the option
	 */
	public static function get( $option ) {
		return self::$options[$option];
	}

	/**
	 * Loads GetRupert tracking code
	 * @return string loading code
	 */
	public static function load_script() {
		$logged_in = is_user_logged_in() ? 'yes' : 'no';
		if ( 'yes' === self::get( 'rupert_enabled' ) ) {
			return self::load_initial( $logged_in );
		}
	}

	/**
	 * Loads tracking code
	 * @return string tracking code
	 */
	public static function load_initial( $logged_in ) {

		$rupert_id = esc_js( self::get( 'rupert_id' ) );

		$code = '<script async src="//www.getrupert.com/js/rupert.min.js?id=' . $rupert_id . '"></script>';
		$code .= '<script type="text/javascript"> window.rupert = window.rupert || []; </script>';
		$code = apply_filters( 'woocommerce_rupert_snippet_output', $code );

		return $code;
	}

	/**
	 * Used to pass transaction data to GetRupert
	 * @param object $order WC_Order Object
	 * @return string Add Transaction code
	 */
	function add_transaction( $order, $trackCustomerNames = true, $trackCustomerLocations = true ) {

		$code = "";
		// Order items
		if ( $order->get_items() ) {
			foreach ( $order->get_items() as $item ) {



				/** @var WC_Product $_product */
				$_product = version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_product_from_item( $item ) : $item->get_product();

				$image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $_product->get_id() ), 'full' );

				$customerName = $trackCustomerNames ? esc_js( version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_name : $order->get_billing_first_name() ) : '';
				$customerLocation = $trackCustomerLocations ? esc_js( version_compare( WC_VERSION, '3.0', '<' ) ? $order->billing_city : $order->get_billing_city() ) : '';

				$code = "window.rupert.push(['_event', 'purchase', {
					  customerName: '".$customerName."',
					  customerLocation: '".$customerLocation."',
				      productUrl: '".esc_js( get_permalink( $_product->get_id() ) )."',
                      imageUrl: '".esc_js( $image_url[0] )."',
				      productId: '".esc_js( $_product->get_sku() ? $_product->get_sku() : $_product->get_id() )."',
				      product: '".esc_js( $item['name'] )."'				      
				    }]);";
			}
		}

		return '<script type="text/javascript">' . $code . '</script>';
	}
}
