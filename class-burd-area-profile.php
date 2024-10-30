<?php
class Burd_Area_Profile {

	/**
	 * @param $postcodes
	 * @return array|mixed|null|object
	 */
	public function get_area($postcodes) {
		// only check api, if post_code is not empty. If empty, just return false
		$client = new Burd_Api_Client();
		$args = array('headers' => array('Authorization' => $client->getBasicAuthentication()));
		$parameter = "?postcodes=" . implode("&postcodes=", $postcodes);
		$response = wp_remote_get($client->getBaseUrl()['areaprofile']."/v1/Areaprofile/Profiles" . $parameter, $args );
		$http_code = wp_remote_retrieve_response_code( $response );
		if($http_code == 200)
		{
			$body = json_decode(wp_remote_retrieve_body( $response ));
			return $body;
		}
		return null;
	}
}