<?php
if ( ! defined( 'WPINC' ) ) exit;

    function burd_shipping_method() {

        if ( ! class_exists( 'Burd_Shipping_Method' ) ) {

            class Burd_Shipping_Method extends WC_Shipping_Method {

                /**
                 * Burd_Shipping_Method constructor.
                 * @param int $instance_id
                 */
                public function __construct($instance_id = 0) {
                  $this->instance_id = absint($instance_id);
                  $this->id                 = 'burd_delivery_shipping'; // never change this.
                  $this->method_title       = 'Burd Delivery';
                  $this->method_description = __( 'For any questions, contact <strong>dev@burd.dk</strong>', $this->id );

                  // what we support.
                  $this->supports = array('shipping-zones', 'instance-settings');

                  // Load the settings API
	                $this->init_settings();
	                $this->init_form_fields();

	                // Save settings in admin if you have any defined
	                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

	                // getting the options for the settings.
	                $this->title = $this->get_option('title');
	                $this->enabled = $this->get_option('enabled');
	                $this->fee = $this->get_option('fee');
                }

		            /**
		             * For woocoomerces under version 3.2.
		             * WC_Shipping_Rate does not contain get_instance_id(), we'll use a custom method to handle it.
		             */
                public static function faker_instance_id() {
                	global $wpdb;
	                $Burd_Shipping_Method = new Burd_Shipping_Method();
	                $result = $wpdb->get_results("SELECT instance_id FROM " . $wpdb->prefix . "woocommerce_shipping_zone_methods WHERE method_id = '".$Burd_Shipping_Method->id."'");
	                if(!isset($result[0])) {
	                	return null;
	                }
	                return $result[0]->instance_id;
                }

                /**
                 * Returns the method id.
                 * @return string
                 */
                public static function get_method_id() {
                    $Burd_Shipping_Method = new Burd_Shipping_Method();
                    return $Burd_Shipping_Method->id;
                }

                /**
                 * Define settings field for this shipping
                 * @return void
                 */
                public function init_form_fields() {

	                // We will add our settings here
	                $this->instance_form_fields = array(
                        'enabled'      => array(
                            'title'   => __( 'Enable', 'woocommerce' ),
                            'type'    => 'checkbox',
                            'label'   => __( 'Once disabled, this legacy method will no longer be available.', 'woocommerce' ),
                            'default' => 'no',
                        ),

                        'title'        => array(
                            'title'       => __( 'Title', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                            'default'     => __( 'Burd Delivery', $this->id ),
                            'desc_tip'    => true,
                        ),

                        'type'         => array(
                            'title'       => __( 'Fee type', 'woocommerce' ),
                            'type'        => 'select',
                            'class'       => 'wc-enhanced-select',
                            'description' => __( 'How to calculate delivery charges', 'woocommerce' ),
                            'default'     => 'fixed',
                            'options'     => array(
                                'fixed'   => __( 'Fixed amount', 'woocommerce' ),
                                'percent' => __( 'Percentage of cart total', 'woocommerce' ),
                                'product' => __( 'Fixed amount per product', 'woocommerce' ),
                            ),
                            'desc_tip'    => true,
                        ),

                        'fee'          => array(
                            'title'       => __( 'Delivery fee', 'woocommerce' ),
                            'type'        => 'price',
                            'description' => __( 'What fee do you want to charge for local delivery, disregarded if you choose free. Leave blank to disable.', 'woocommerce' ),
                            'default'     => '',
                            'desc_tip'    => true,
                            'placeholder' => wc_format_localized_price( 0 ),
                        ),

                        'backorder'      => array(
                            'title'   => __( 'Allow backorder', 'woocommerce' ),
                            'type'    => 'checkbox',
                            'label'   => __( 'Allow backorder to be placed', 'woocommerce' ),
                            'default' => 'yes',
                        ),

                        'cut_off'          => array(
                            'title'       => __( 'Cut off time', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'This determines the deadline for booking a same day delivery, defaults to 12:30. Notice: we use server-time for the calculation.', 'woocommerce' ),
                            'default'     => '12:30',
                            'desc_tip'    => true,
                            'placeholder' => 'Default never',
                        ),

                        'cut_off_time_east'          => array(
                            'title'       => __( 'Cut off time east', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'This determines the deadline for booking a same day delivery, defaults to 12:30. Notice: we use server-time for the calculation.', 'woocommerce' ),
                            'default'     => '12:30',
                            'desc_tip'    => true,
                            'placeholder' => '12:30',
                        ),

                        'cut_off_time_east_delay'          => array(
                            'title'       => __( 'Cut off time east delay', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'This determines the deadline for booking a same day delivery, defaults to 12:30. Notice: we use server-time for the calculation.', 'woocommerce' ),
                            'default'     => '0',
                            'desc_tip'    => true,
                            'placeholder' => '0',
                        ),

                        'cut_off_time_west'          => array(
                            'title'       => __( 'Cut off time west', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'This determines the deadline for booking a same day delivery, defaults to 12:30. Notice: we use server-time for the calculation.', 'woocommerce' ),
                            'default'     => '12:30',
                            'desc_tip'    => true,
                            'placeholder' => '12:30',
                        ),

                        'cut_off_time_west_delay'          => array(
                            'title'       => __( 'Cut off time west delay', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'This determines the deadline for booking a same day delivery, defaults to 12:30. Notice: we use server-time for the calculation.', 'woocommerce' ),
                            'default'     => '0',
                            'desc_tip'    => true,
                            'placeholder' => '0',
                        ),

                        'cut_off_time_mid'          => array(
                            'title'       => __( 'Cut off time mid', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'This determines the deadline for booking a same day delivery, defaults to 12:30. Notice: we use server-time for the calculation.', 'woocommerce' ),
                            'default'     => '12:30',
                            'desc_tip'    => true,
                            'placeholder' => '12:30',
                        ),

                        'cut_off_time_mid_delay'          => array(
                            'title'       => __( 'Cut off time mid delay', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'This determines the deadline for booking a same day delivery, defaults to 12:30. Notice: we use server-time for the calculation.', 'woocommerce' ),
                            'default'     => '0',
                            'desc_tip'    => true,
                            'placeholder' => '0',
                        ),

                        'checkouttexttoday'          => array(
                            'title'       => __( 'Checkout text today', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'I aften mellem kl. 18:00-22:00.', 'woocommerce' ),
                            'default'     => 'I aften mellem kl. 18:00-22:00.',
                            'desc_tip'    => true,
                            'placeholder' => 'I aften mellem kl. 18:00-22:00.',
                        ),

                        'checkouttextpartial'          => array(
                            'title'       => __( 'Checkout text partial order', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'Hvad haves på lager leveres i aften mellem kl. 18:00-22:00, resten afsendes samme dag som modtages på lager.', 'woocommerce' ),
                            'default'     => 'Hvad haves på lager leveres i aften mellem kl. 18:00-22:00, resten afsendes samme dag som modtages på lager.',
                            'desc_tip'    => true,
                            'placeholder' => 'Hvad haves på lager leveres i aften mellem kl. 18:00-22:00, resten afsendes samme dag som modtages på lager.',
                        ),

                        'checkouttextdelay'          => array(
                            'title'       => __( 'Checkout text delay', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'Leveres %deliverydate% mellem kl. 18:00-22:00.', 'woocommerce' ),
                            'default'     => 'Leveres %deliverydate% mellem kl. 18:00-22:00.',
                            'desc_tip'    => true,
                            'placeholder' => 'Leveres %deliverydate% mellem kl. 18:00-22:00.',
                        ),

                        'checkouttextdelaypartial'          => array(
                            'title'       => __( 'Checkout text delay partial', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'Hvad haves på lager leveres %deliverydate% mellem kl. 18:00-22:00, resten afsendes samme dag som modtages på lager.', 'woocommerce' ),
                            'default'     => 'Hvad haves på lager leveres %deliverydate% mellem kl. 18:00-22:00, resten afsendes samme dag som modtages på lager.',
                            'desc_tip'    => true,
                            'placeholder' => 'Hvad haves på lager leveres %deliverydate% mellem kl. 18:00-22:00, resten afsendes samme dag som modtages på lager.',
                        ),

                        'checkouttextbackorder'          => array(
                            'title'       => __( 'Checkout text backorder', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'Leveres samme dag som modtages på lager mellem kl. 18:00-22:00.', 'woocommerce' ),
                            'default'     => 'Leveres samme dag som modtages på lager mellem kl. 18:00-22:00.',
                            'desc_tip'    => true,
                            'placeholder' => 'Leveres samme dag som modtages på lager mellem kl. 18:00-22:00.',
                        ),

                        'maxorderweight'          => array(
                            'title'       => __( 'Max total order weight', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'Max total order weight', 'woocommerce' ),
                            'default'     => '500',
                            'desc_tip'    => true,
                            'placeholder' => '500',
                        ),

                        'maxitemweight'          => array(
                            'title'       => __( 'Max item weight', 'woocommerce' ),
                            'type'        => 'text',
                            'description' => __( 'Max item weight', 'woocommerce' ),
                            'default'     => '20',
                            'desc_tip'    => true,
                            'placeholder' => '20',
                        ),

                        'enableLogger'      => array(
                            'title'   => __( 'Enable logger', 'woocommerce' ),
                            'type'    => 'checkbox',
                            'label'   => __( 'Enable logger', 'woocommerce' ),
                            'default' => 'no',
                        ),
                        /*
                        'flex_option'          => array(
	                        'title'       => __( 'Flex option', 'woocommerce' ),
	                        'type'        => 'checkbox',
	                        'description' => __( 'If enabled, the customer will have the opportunity to chose a available delivery-date in the future.', 'woocommerce' ),
	                        'desc_tip'    => true,
                        ),
                        
                        'flex_options_amount' => array(
	                        'title'       => __( 'Flex options', 'woocommerce' ),
	                        'type'        => 'select',
	                        'class'       => 'wc-enhanced-select',
	                        'description' => __( 'How many flex-options should be displayed. (Will only work, if flex options is enabled).', 'woocommerce' ),
	                        'default'     => 'fixed',
	                        'options'     => array(
		                        1   => __( 'Show 1 flex-options', 'woocommerce' ),
		                        2   => __( 'Show 2 flex-options', 'woocommerce' ),
		                        3   => __( 'Show 3 flex-options', 'woocommerce' ),
		                        4   => __( 'Show 4 flex-options', 'woocommerce' ),
		                        5   => __( 'Show 5 flex-options', 'woocommerce' ),
		                        6   => __( 'Show 6 flex-options', 'woocommerce' ),
		                        7   => __( 'Show 7 flex-options', 'woocommerce' ),
		                        8   => __( 'Show 8 flex-options', 'woocommerce' ),
		                        9   => __( 'Show 9 flex-options', 'woocommerce' ),
		                        10   => __( 'Show 10 flex-options', 'woocommerce' ),
	                        ),
	                        'desc_tip'    => true,
                        ),
                        */

                        'free_shipping_over'          => array(
	                        'title'       => __( 'Free shipping', 'woocommerce' ),
	                        'type'        => 'text',
	                        'description' => __( 'Give free shipping if the cart contains over the specificed amount. Leave empty or 0 if it should not be enabled.', 'woocommerce' ),
	                        'default'     => '0',
	                        'desc_tip'    => true,
	                        'placeholder' => '0',
                        ),


                    );

                }

		            /**
		             * @return int|mixed
		             */
                private function calculate_price() {

	                global $woocommerce;

	                $amountCart = $woocommerce->cart->cart_contents_total+$woocommerce->cart->tax_total;
                	$free_shipping_over = $this->get_option( 'free_shipping_over' );

									if($amountCart  > $free_shipping_over && $free_shipping_over > 0) {
										return 0;
									}

                	return $this->get_option( 'fee' );
                }

                /**
                 * This function is used to calculate the shipping cost.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package = array() ) {

	                // add the shipping-method with cost to the user.
                    $this->add_rate( array(
                        'id'   => $this->id,
                        'label' => $this->title,
                        'cost'   => $this->calculate_price(),
                        'package' => $package,
                    ));
                }
            }
        }
    }
