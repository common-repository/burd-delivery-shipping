<?php

class Burd_Checkout {

	/**
	 *
	 * Holds the list of delivery-days.
	 * @var array
	 */
	// private $delivery_dates = null;

	/**
	 * @var string
	 */
	private $shipping_description = "";

	/**
	 * @var bool
	 */
	private $delivery_date_found = false;

	/**
	 * @var array
	 */
	private $options_delivery = array();

	/**
	 * @var string
	 */
	private $flex_delivery_display_class = "burd-flex-display-none";

	/**
	 * @var
	 */
	private $burd_default_delivery_date;

	/**
	 * @var
	 */
	private $burd_shipping_method;

	/**
	 * @var
	 */
	private $cut_off;

	/**
	 * @var
	 */
	private $cut_off_delay;

	/**
	 * @var Burd_Api_Client
	 */
	private $burd;


	public function __construct() {
		add_action( 'wp_enqueue_scripts', array($this, 'add_burd_shipping_styles'), 99 );
		add_action( 'woocommerce_after_shipping_rate', array($this, 'carrier_custom_fields'), 20, 2 );
		$this->burd = new Burd_Api_Client();
	}

	/**
	 * Adds the Burd styles to the check-out-page.
	 */
	public function add_burd_shipping_styles() {
		wp_enqueue_style( 'burd-checkout-css',  plugin_dir_url( __FILE__ ) . "/assets/css/checkout.css", false, false, false );
		wp_enqueue_script( 'burd-checkout-js',  plugin_dir_url( __FILE__ ) . "/assets/js/checkout.js", false, false, false);
	}

	/**
	 * @param $method
	 * @return Burd_Shipping_Method|null
	 */
	private function burd_shipping_method($method) {
		if($method->id !== Burd_Shipping_Method::get_method_id()) {
			return null;
		}

		if (method_exists( $method, 'get_instance_id' ) && strlen(strval( $method->get_instance_id())) > 0 ) {
			$instance_id = $method->get_instance_id();
		} else {
			$instance_id = Burd_Shipping_Method::faker_instance_id();
		}
		return new Burd_Shipping_Method($instance_id);
	}

	/**
	 * @param $postcode
	 * @return array|mixed|null|object
	 */
	public function get_postcode($postcode) {
		// only check api, if post_code is not empty. If empty, just return false
		$client = new Burd_Api_Client();
		$args = array('headers' => array('Authorization' => $client->getBasicAuthentication()));
		$response = wp_remote_get("https://burdecommerceapiprod.azurewebsites.net/v1/postcode/" . $postcode, $args );
		$http_code = wp_remote_retrieve_response_code( $response );
		if($http_code == 200)
		{
			$body = json_decode(wp_remote_retrieve_body( $response ));
			return $body;
		}

		return null;
	}

	/**
	 * @param $method
	 * @param $index
	 * @return bool
	 */
	public function carrier_custom_fields( $method, $index ) {
		
		$this->burd_shipping_method = $this->burd_shipping_method($method);
		if($this->burd_shipping_method == null) {
			return false;
		}

		$logger = wc_get_logger();
		$context = array( 'source' => 'burddelivery' );
		$loggingEnabled = $this->burd_shipping_method->get_option('enableLogger') == 'yes';

		$specific_delivery_days = null;

		if(!isset($this->burd_shipping_method->instance_settings['specific_delivery_dates'])) {
			$this->burd_shipping_method->instance_settings['specific_delivery_dates'] = [];
		}

		$postcode = $shipping_postcode = WC()->customer->get_shipping_postcode();

		if($postcode == null) {
			if($loggingEnabled) {
				$logger->info( 'Delivery postcode not set, return', $context);
			}
			return false;
		}

		if (isset($postcode)) {			
			$postcode = sanitize_text_field($postcode);
			
			if($loggingEnabled) {
				$logger->info( 'Delivery postcode: ' . $postcode, $context);
			}
		}

		/*
		if (isset($_POST['postcode'])) {			
			$postcode = sanitize_text_field($_POST['postcode']);
			
			if($loggingEnabled) {
				$logger->info( 'Posted postcode: ' . $postcode, $context);
			}
		}

		if($postcode == null) {
			if($loggingEnabled) {
				$logger->info( 'Delivery postcode not set, return', $context);
			}
			return false;
		}
		*/

		// customer postcode and webshop.
		$postcodeResult = $this->get_postcode($postcode);
		if(!isset($postcodeResult)) {
			if($loggingEnabled) {
				$logger->info( 'Shop postcode not set, return', $context);
			}
			return false;
		}

		// holds flex option: yes / no.
		$flex_option = $this->burd_shipping_method->get_option( "flex_option" );

		setlocale( LC_ALL, "da_DK.UTF-8", "Danish_Denmark.1252", "danish_denmark", "danish", "dk_DK@euro" );		

		$this->cut_off = $this->burd_shipping_method->get_option( 'cut_off_time' . '_' . $postcodeResult->area );
		$this->cut_off_delay = $this->burd_shipping_method->get_option( 'cut_off_time' . '_' . $postcodeResult->area . '_delay' );

		$delivery_dates = get_option('burd_delivery_delivery_dates_postcode_' . $postcode) ?? null;
		if(!isset($delivery_dates) || !is_array($delivery_dates)) {
			return false;
		}

		if(count($delivery_dates) == 0) {
			
			$burd_Delivery_Date = new Burd_Delivery_Date();
			$delivery_dates = $burd_Delivery_Date->burd_delivery_dates($postcode);
			
		}
		
		$itemsInStock = 0;
		$itemsNotInStock = 0;
		$totalweight = 0;
		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$stock_status = '';
				if ( method_exists( $values['data'], 'get_stock_status' ) ) {
					$stock_status = $values['data']->get_stock_status(); // For version 3.0+
				} else {
					$stock_status = $values['data']->stock_status; // Older than version 3.0
				}

				if($stock_status == 'instock')
				{
                    $itemsInStock += 1;
				}
				else 
				{
					$itemsNotInStock += 1;					
				}

				$weight = ($values['data']->get_weight() || 0) * ($values['quantity'] || 0);
				$totalweight += $weight;
		}

		$deliveryDatesCount = count($delivery_dates);
		$delivery_date_object = new DateTime('2120-01-01');
		for($i = 0; $i < count($delivery_dates); $i++ )
		{
			$delivery_date = $delivery_dates[$i];
			$delivery_date_object = new DateTime($delivery_date);
			
			if (! Burd_Date_Helper::cut_off_time_exceeded($delivery_date_object->format("Y-m-d"), $this->cut_off)) {
				// apply delay if any
				if($this->cut_off_delay > 0)
				{
					$delivery_date = $delivery_dates[$i+$this->cut_off_delay];
					$delivery_date_object = new DateTime($delivery_date);
				}

				//$this->shipping_description .= "<br>";
				if (Burd_Date_Helper::is_today($delivery_date_object->format( "Y-m-d" )))
				{
					if($itemsInStock > 0 && $itemsNotInStock == 0)
					{
						$this->shipping_description.= $this->burd_shipping_method->get_option( 'checkouttexttoday' );
					} elseif($itemsInStock > 0 && $itemsNotInStock > 0)
					{
						$this->shipping_description.= $this->burd_shipping_method->get_option( 'checkouttextpartial' );
					} else {
						$this->shipping_description.= $this->burd_shipping_method->get_option( 'checkouttextbackorder' );
						$delivery_date_object = new DateTime('2120-01-01');
						$delivery_date = $delivery_date_object->format("Y-m-d");
					}
				} else {					
					if($itemsInStock > 0 && $itemsNotInStock == 0)
					{
						$this->shipping_description.= str_replace("%deliverydate%", Burd_Date_Helper::readable_date($delivery_date_object), $this->burd_shipping_method->get_option('checkouttextdelay'));
					} elseif($itemsInStock > 0 && $itemsNotInStock > 0)
					{
						$this->shipping_description.= str_replace("%deliverydate%", Burd_Date_Helper::readable_date($delivery_date_object), $this->burd_shipping_method->get_option('checkouttextdelaypartial'));
					} else {
						$this->shipping_description.= $this->burd_shipping_method->get_option( 'checkouttextbackorder' );
						$delivery_date_object = new DateTime('2120-01-01');
						$delivery_date = $delivery_date_object->format("Y-m-d");
					}					
				}
				$this->burd_default_delivery_date = $delivery_date;
				$this->delivery_date_found = true;
			break;
			}

			$this->burd_default_delivery_date = $delivery_date;
		}

		$this->shipping_description.= '<input type="hidden" value="' . $delivery_date_object->format('Y-m-d\TH:i:s.u\Z') . '" id="burd_default_delivery_date" name="burd_default_delivery_date">';

		// handles if the chosen delivery is flex or not.
		$this->shipping_description.= '<input type="hidden" value="0" id="burd_future_delivery" name="burd_future_delivery">';

		// echoes the delivery.
		echo $this->shipping_description;
	}

}
$Burd_Checkout = new Burd_Checkout();