<?php
class Burd_Delivery_Date {

	/**
	 * Gets delivery dates from external API.
	 * @param $postcode
	 * @return array|mixed|object
	 */
	public function burd_delivery_dates($postcode)
	{
		// only check api, if post_code is not empty. If empty, just return false
		$client = new Burd_Api_Client();
		$args = array('headers' => array('Authorization' => $client->getBasicAuthentication()));
		$response = wp_remote_get($client->getBaseUrl()['delivery'] . "/v1/DeliveryDate?zipcode=" . $postcode . "&skip=0&take=10", $args );
		$http_code = wp_remote_retrieve_response_code($response);
		if($http_code == 200)
		{
			$body = json_decode(wp_remote_retrieve_body($response));
			return $body;
		}
		return array();
	}


}