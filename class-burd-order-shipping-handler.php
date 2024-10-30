<?php

class Burd_Order_Shipping_Handler {

  /**
   * @var WC_Order
   */
  private $order;

  /**
   * @var string
   */
  private $validator_meta_key = "burd_api_order_data_sent";

	/**
	 * @var
	 */
  private $burd_order_number;

	/**
	 * @var Burd_Api_Client
	 */
  private $burd;

  /**
   * Burd_Order_Shipping_Handler constructor.
   */
	public function __construct() {
		// send extra info when the order is complete
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'checkout_field_update' ) );
		// when the order has been payed, call order_handling. (hook).
		add_action( 'woocommerce_order_status_processing', array( $this, 'order_handling' ) , 1, 1 );
		add_action( 'woocommerce_payment_complete', array( $this, 'order_handling' ) , 1, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_handling' ) , 1, 1 );
		add_action( 'woocommerce_order_status_completed_notification', array( $this, 'order_handling' ) , 1, 1 );
		$this->burd = new Burd_Api_Client();
	}

  /**
   * Handles the shipping.
   * @param $order_id
   */
  public function order_handling($order_id) {

    $this->order = new WC_Order($order_id);
    if($this->validate_if_shipping_is_burd($this->order))
    {
	    if (!$this->has_order_forecast_meta())
	    {
		    $this->order_to_forecast();
      }
      
      /*
	    if ($this->validate_before_api_send())
	    {
		    $this->burd_order_number = $this->generate_unique_order_number();
		    $this->order_send_to_api();
      }
      */
    }
  }

	/**
	 * @return bool
	 */
  private function has_order_forecast_meta() {
  	return get_post_meta( $this->order->get_id(), "burd_order_forecast", true ) == 1;
  }

	/**
	 * @param $order_id
	 */
	public function checkout_field_update($order_id)
	{

		// user has chosen a future delivery.
		$expected_delivery = null;
		if(isset($_POST['burd_default_delivery_date'])) {
			$expected_delivery = sanitize_text_field($_POST['burd_default_delivery_date']);
		}

		if(isset($_POST['burd_future_delivery']))
		{
			$burd_future_delivery = sanitize_text_field($_POST['burd_future_delivery']);
			update_post_meta( $order_id, 'burd_future_delivery', $burd_future_delivery );

			if ($burd_future_delivery == 1) {
				$burd_flex_delivery_date = sanitize_text_field($_POST['burd_flex_delivery_date']);
				$expected_delivery = $burd_flex_delivery_date;
				update_post_meta( $order_id, 'burd_flex_delivery_date', $burd_flex_delivery_date );
			}
		}

		update_post_meta( $order_id, 'burd_expected_delivery_date', $expected_delivery );

	}

	/**
	 * Ensures all validations is passed,
	 * before sending the data to the Burd-API.
	 * @return bool
	 */
  private function validate_before_api_send()
  {
			// we do not WANT to send the data automaticly.
      if(get_option('woocommerce_burd_settings_api_send') !== "option_1")
      {
        return false;
      }

      if($this->validate_if_order_sent_before())
      {
          return false;
      }

      return true;
  }

	/**
   * Generates a completely unique orderNumber for the API.
	 * @return string
	 */
  private function generate_unique_order_number()
  {
      return rand(2000,2999).$this->order->get_id();
  }

  private function order_to_forecast()
  {    
    $args = array(
    'headers' => array(
                       'Authorization' =>  $this->burd->getBasicAuthentication(),
                       'content-type' => 'application/json; charset=utf-8'
                       ),

    'body' => json_encode(array(
                    'address' => $this->order->get_shipping_address_1(),
                    'zipCode' => $this->order->get_shipping_postcode(),
                    'city' => $this->order->get_shipping_city(),
                    'expectedDelivery' => $_POST['burd_default_delivery_date'],
                    'orderNumber' => trim(str_replace('#', '', $this->order->get_order_number()))
                    ))

    );

    $response = wp_remote_post( $this->burd->getBaseUrl()['order'] . "/v1/OrderForecasts?woo=2", $args );
      
    $http_code = wp_remote_retrieve_response_code( $response );
      
     if($http_code == 201) {
      // Add the note
      $this->order->add_order_note( "BURD DELIVERY: Forecast Sent.");
      // Save the data
      $this->order->save();
      update_post_meta( $this->order->get_id(), "burd_order_forecast", 1 );
     } else {
			$this->order->add_order_note( "BURD DELIVERY: Forecast Not Sent.");
     }

  }

  /**
   * Handles the order, when it has been marked as completed.
   * If all validations went through, the method will be called.
   * @see validate_before_api_send()
   */
  private function order_send_to_api()
  {
      
      $args = array( 'headers' => array( 'Authorization' =>  $this->burd->getBasicAuthentication(), 'content-type' => 'application/json; charset=utf-8'
        ),
	      'body' => json_encode($this->burd_order_builder()),
	      'timeout' => 10);
      
      $response = wp_remote_post( $this->burd->getBaseUrl()['order'] . "/v1/Orders?woo=1", $args );
      
      $http_code = wp_remote_retrieve_response_code( $response );
      
      if($http_code == 201)
      {
          // update post meta, making sure, the plugin does not keep sending data to the api, by attaching the key.
          update_post_meta( $this->order->get_id(), $this->validator_meta_key, 1 );
          
          // keep the burd order number stored for the post, we'll add the api user included as value otherwise
          // we can't access apis such as label.
          update_post_meta( $this->order->get_id(), "burd_orderNumber", $this->burd_order_number . "--" . get_option( 'woocommerce_burd_settings_api_username' ) );

          $this->order->add_order_note("BURD DELIVERY: Order created with Order ID: " . $this->burd_order_number);
          $this->order->save();
      } else {
      	$this->order->add_order_note("BURD DELIVERY: Could not create order. " . $http_code);
      	$this->order->save();
      }

  }

	/**
	 * Data builder for sending data to the rest API.
	 * @return array
	 */
  private function burd_order_builder()
  {
  	$origin_phone = get_option('woocommerce_burd_settings_origin_phone');

  	if(empty($origin_phone))
  	{
  		$origin_phone = "000000";
	  }

    $data['orderType'] = "distribution";
    $data['orderNumber'] = $this->burd_order_number;

    $data['origin'] =
	    array(
		    'address' => get_option('woocommerce_burd_settings_origin_address'),
		    "zipCode" => get_option('woocommerce_burd_settings_origin_postcode'),
		    "city" => get_option('woocommerce_burd_settings_origin_city'),
		    "email" => get_option('admin_email'),
		    "companyName" => get_option('woocommerce_burd_settings_company_name'),
		    "phone" => array(
				    'prefix' => 45,
				    'number' => $origin_phone
			    )
	    );

    $customerPhoneNumber = trim($this->order->get_billing_phone());
    $customerPhoneNumber = str_replace(' ', '', $customerPhoneNumber);
  	$customerPhoneNumber = str_replace('+45', '', $customerPhoneNumber);

    $data['destination'] =
	    array(
		    'address' => $this->order->get_shipping_address_1(),
		    "zipCode" => $this->order->get_shipping_postcode(),
		    "city" => $this->order->get_shipping_city(),
		    "email" => $this->order->get_billing_email(),
		    "companyName" => $this->order->get_shipping_first_name() . " " . $this->order->get_shipping_last_name(),
		    "phone" => array(
				    'prefix' => 45,
				    'number' => $customerPhoneNumber
			    ),
		    "extraInfo" => "Navn: " . $this->order->get_shipping_first_name() . " " . $this->order->get_shipping_last_name()
	    );

    $senderPhoneNumber = trim($origin_phone);
    $senderPhoneNumber = str_replace(' ', '', $senderPhoneNumber);
    $senderPhoneNumber = str_replace('+45', '', $senderPhoneNumber);

		// sender..
	  $data['sender'] = array(
			  'address' => get_option('woocommerce_burd_settings_origin_address'),
			  "zipCode" => get_option('woocommerce_burd_settings_origin_postcode'),
			  "city" => get_option('woocommerce_burd_settings_origin_city'),
			  "email" => get_option('admin_email'),
			  "companyName" => get_option('woocommerce_burd_settings_company_name'),
			  "phone" => array (
					  'prefix' => 45,
					  'number' => $senderPhoneNumber
		      )

		  );

	  // future delivery, add to destination...
	  if(get_post_meta( $this->order->get_id(), "burd_future_delivery", true ) == 1)
	  {
	  	$delivery_date = get_post_meta($this->order->get_id(), 'burd_flex_delivery_date', true);
		  $data['destination']['timeWindow'] = array( 'start' => $delivery_date, 'end' => $delivery_date );
	  }

    $data['cargo'] = array(array('labelFormat' => "barcodeOnly"));

    return array($data);

  }

  /**
   * Makes sure, a order data only can be sent one time to the API.
   * @return bool
   */
  private function validate_if_order_sent_before()
  {
      return get_post_meta($this->order->get_id(), $this->validator_meta_key, true ) == 1;
  }

  /**
   * @param WC_Order $wc_order
   * @return bool
   */
	public function validate_if_shipping_is_burd($wc_order)
	{
      foreach( $wc_order->get_items( 'shipping' ) as $item_id => $shipping_item_obj )
      {
          if($shipping_item_obj->get_method_id() == "burd_delivery_shipping")
          {
              return true;
          }
      }
      return false;
  }

}
$Burd_Order_Shipping_Handler = new Burd_Order_Shipping_Handler();
