<?php
/**
 * Plugin Name: Burd Delivery Shipping
 * Plugin URI: https://burd.dk/
 * Description: Shipping method - allows same- and flex day delivery.
 * Version: 1.5
 * Author: Burd Delivery ApS
 * Author URI: https://burd.dk
 *
 * @package WooCommerce
 */

if ( ! defined( 'WPINC' ) ) exit;

// only autoload files, if woocommmerce is active.
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

		$delivery_dates_burd = null;

    // helpers here.
    include_once ("helper/class-number-helper.php");
    include_once ("helper/class-burd-date-helper.php");

    // config here
		include_once ("config/plugin_info.php");

		// api client here
		include_once ("api/class-burd-api-client.php");

		// classes here
		include_once ("class-burd-delivery-date.php");
		include_once ("class-burd-area-profile.php");
		include_once ("class-burd-package-calculation.php");
    include_once ("class-burd-condition-filter.php");
    include_once ("class-burd-order-shipping-handler.php");
    include_once ("class-burd-condition-method.php");
    include_once ("class-burd-shipping-method.php");
		include_once ("class-burd-admin.php");
    include_once ("class-burd-checkout.php");
    include_once ("hooks.php");

    // getting woocommerce shop.
    $base_address = get_option( 'woocommerce_store_address', '' );
    $base_city = get_option('woocommerce_store_city', '');
    $base_country = get_option('woocommerce_default_country', '');
    $base_postcode = get_option('woocommerce_store_postcode', '');

}