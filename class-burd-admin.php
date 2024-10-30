<?php
class Burd_Admin {
	/**
	 * Burd_Admin constructor.
	 */
	public function __construct() {
		// add_action( 'manage_shop_order_posts_custom_column' , array($this, 'add_label_to_the_column'), 10, 2 );
		add_filter( 'manage_edit-shop_order_columns', array($this, 'add_column_label'), 20 );
		add_filter( 'woocommerce_shipping_settings', array($this, 'add_shipping_options'), 10, 1 );
		add_action('admin_init', array($this, 'generate_burd_label_by_order'));


	}

	/**
	 * Generates the burd label and displays it to the user.
	 */
	public function generate_burd_label_by_order() {
		if(isset($_GET['action']) && $_GET['perform'] == "generateBurdLabel") {
			$post_id = $_GET['post_id'];
			$burd_orderNumber = get_post_meta($post_id, "burd_orderNumber", true);

			$client = new Burd_Api_Client();
			$args = array('headers' => array('Authorization' => $client->getBasicAuthentication()));
			$response = wp_remote_get($client->getBaseUrl()['label'] . "/v1/Labels?orderNumber=". $burd_orderNumber . "&labelFormat=pdf", $args );
			$http_code = wp_remote_retrieve_response_code( $response );
			if($http_code == 200)
			{
				$response = json_decode(wp_remote_retrieve_body( $response ));

				$labelData = base64_decode($response[0]->labelData);

				if(empty($labelData)) {
					exit('No label data returned for the Burd order: ' . $burd_orderNumber);
				}

				// store data, that we'll generated the label.
				update_post_meta($post_id, 'burd_label_generated', date("Y-m-d H:i:s"));
				// set header to pdf.
				header("Content-type: application/pdf");
				exit($labelData);

			}

				exit('No label data returned for the Burd order: ' . $burd_orderNumber);

		}
	}
	/**
	 * Adds extra options to the page: ?page=wc-settings&tab=shipping&section=options, by using the filter woocommerce_shipping_settings.
	 * @param $array
	 * @return mixed
	 */
	public function add_shipping_options($array) {
		array_push($array, array(
			'title' => __( 'Burd options', 'woocommerce' ),
			'type'  => 'title',
			'id'    => 'burd_options',
		));

		/*
		array_push($array, array(
			'title'   => 'Order settings',
			'type'    => 'select',
			'default' => 'option_2',
			'desc' => 'Automatically send collect orders immediately as orders are placed in the store, alternatively you may send it manually from the order page. Please note that orders must be Authorized/Paid upon creation, for collect orders to be transferred automatically. If your orders are handled by an external warehouse, you may want to select  "Do not send data to BURD directly", if you are handling packaging and shipment yourself choose "I will send the data".',
			'desc_tip' => true,
			'class'   => 'availability wc-enhanced-select',
			'options' => array(
				'option_1' => 'Automatically send collect orders to burd',
				'option_2' => 'Do not send data to BURD directly',
				'option_3' => 'I will send the data'
			),
			'id'            => 'woocommerce_burd_settings_api_send',
			'autoload'      => false,
		));
		*/
		
		array_push($array, array(
			'title'   => 'Your company name',
			'type'    => 'text',
			'desc' => 'Your company name.',
			'desc_tip' => true,
			'id'            => 'woocommerce_burd_settings_company_name',
			'autoload'      => false,
		));
		array_push($array, array(
			'title'   => 'Burd API username',
			'type'    => 'text',
			'desc' => 'API username for using the Burd API.',
			'desc_tip' => true,
			'id'            => 'woocommerce_burd_settings_api_username',
			'autoload'      => false,
		));
		array_push($array, array(
			'title'   => 'Burd API password',
			'type'    => 'text',
			'desc' => 'API password for using the Burd API.',
			'desc_tip' => true,
			'id'            => 'woocommerce_burd_settings_api_password',
			'autoload'      => false,
		));
		array_push($array, array(
			'type' => 'sectionend',
			'id'   => 'burd_options',
		));
		array_push($array, array(
			'title' => __( 'Burd Origin', 'woocommerce' ),
			'type'  => 'title',
			'id'    => 'burd_origin',
		));
		array_push($array, array(
			'text' => __( 'Burd Origin', 'woocommerce' ),
			'type'  => 'paragraph',
			'id'    => 'burd_origin',
		));
		array_push($array, array(
			'title'   => 'Address',
			'type'    => 'text',
			'id'            => 'woocommerce_burd_settings_origin_address',
			'autoload'      => false,
			'default'       => get_option('woocommerce_store_address')
		));
		array_push($array, array(
			'title'   => 'City',
			'type'    => 'text',
			'id'            => 'woocommerce_burd_settings_origin_city',
			'autoload'      => false,
			'default'       => get_option('woocommerce_store_city')
		));
		array_push($array, array(
			'title'   => 'Postcode',
			'type'    => 'text',
			'id'            => 'woocommerce_burd_settings_origin_postcode',
			'autoload'      => false,
			'default'       => get_option('woocommerce_store_postcode')
		));
		array_push($array, array(
			'title'   => 'Phone number',
			'type'    => 'text',
			'id'            => 'woocommerce_burd_settings_origin_phone',
			'autoload'      => false,
			'default'       => get_option('woocommerce_store_phone')
		));

		/*
		array_push($array, array(
			'title'             => 'Test API call',
			'type'              => 'button',
			'custom_attributes' => array(
				'onclick' => "location.href='http://www.burd.dk'",
			),
			'description'       => 'Will call Burd API',
			'desc_tip'          => true,
		));
		*/

		array_push($array, array(
			'type' => 'sectionend',
			'id'   => 'burd_origin',
		));

		return $array;
	}
	/**
	 * Adds the generate label button for the order_burd_label column.
	 * @param string $column_name
	 * @param id $post_id
	 */
	public function add_label_to_the_column($column_name, $post_id) {
		$Burd_Shipping_Order_Handler = new Burd_Order_Shipping_Handler();
		if(!$Burd_Shipping_Order_Handler->validate_if_shipping_is_burd(new WC_Order($post_id))) return; // only display label, if the order has burd as shipping.
		if($column_name !== "order_burd_label") return;
		echo "<p><a href='/wp-admin/edit.php?action=label&perform=generateBurdLabel&post_id=$post_id' class='button' target='_blank' style='float:left; margin-top:0px; height:27px;' data-order='$post_id'>Print label</a></p>";
	}
	/**
	 * Adds 'Burd_delivery' column header to 'Orders' page immediately after 'Total' column.
	 *
	 * @param string[] $columns
	 * @return string[] $new_columns
	 */
	public function add_column_label( $columns ) {
		$new_columns = array();
		foreach ( $columns as $column_name => $column_info ) {
			$new_columns[ $column_name ] = $column_info;
			/*
			if ( 'order_total' === $column_name ) {
				$new_columns['order_burd_label'] = 'Burd Delivery Label';
			}
			*/
		}
		return $new_columns;
	}
}
$Burd_Admin = new Burd_Admin();