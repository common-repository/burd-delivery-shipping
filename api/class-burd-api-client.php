<?php

class Burd_Api_Client {

	/**
	 * Holds the possible base urls.
	 * @var array
	 */
	private $baseUrl = [
		'label'       => "https://burdlabelapiprod.azurewebsites.net",
		'order'       => "https://productionburdapi.azurewebsites.net",
		'areaprofile' => "https://burdareaprofileapiprod.azurewebsites.net",
		'delivery'    => "https://burddeliverydateapiprod.azurewebsites.net"
	];

	public function getBasicAuthentication() {
		return "Basic " . base64_encode(get_option('woocommerce_burd_settings_api_username') . ":" . get_option('woocommerce_burd_settings_api_password'));
	}

	/**
	 * @return array
	 */
	public function getBaseUrl() {
		return $this->baseUrl;
	}

}
