<?php
/**
 *
 * All hooks should be added here for the plugin
 * functions first, actions second, filters third, cssjs fourth.
 */

/**
 *
 * Adds the burd shipping to woocommerce.
 * @param $methods
 * @return mixed
 */
function add_burd_shipping_method( $methods ) {
    $methods['burd_delivery_shipping'] = 'Burd_Shipping_Method';
    return $methods;
}

/**
 * Handles conditionals for the shipping.
 */
function init_burd_conditional_shipping() {
    $burd_conditional_method = new Burd_Conditional_Method();
    $burd_conditional_method->setup();
}

// actions.
add_action( 'init', 'init_burd_conditional_shipping', 110 );
add_action( 'woocommerce_shipping_init', 'burd_shipping_method' );

// filters
add_filter( 'woocommerce_shipping_methods', 'add_burd_shipping_method' , 10, 1);
