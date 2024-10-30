<?php

class Burd_Conditional_Method
{

    /**
     * Holds the WC_Shipping instance.
     * @var
     */
    private $wc_shipping;

    /**
     *
     * Holds the Burd_Condition_Filter instance.
     * @var
     */
    private $condition_filter;

    /**
     * Holds the shipping possibilities for the Burd.
     * We can have multiple zones defined, where the Burd-shipping can be in.
     */
    private $allowed_shipping_classes;

    // private $isCheckout;

    /**
     * Sets up all instances,
     * filter, and hooks.
     */
    public function setup() {

        $this->wc_shipping = WC_Shipping::instance();
        $this->allowed_shipping_classes = $this->wc_shipping->get_shipping_method_class_names();
        $this->condition_filter = new Burd_Condition_Filter();

        /*
        add_filter( 'woocommerce_cart_ready_to_calc_shipping',
            function( $bool ) {
                if ( ! is_checkout() ) {
                    return false;
                }
                return $bool;
                }
            );
            */

        // access the shipping instance settings.
        add_filter( 'woocommerce_shipping_' . Burd_Shipping_Method::get_method_id() . '_instance_settings_values',
          array( $this, 'process_options' ), 10, 2 );

        // adds the condition fields..
        add_filter( 'woocommerce_settings_shipping', array( $this, 'add_fields' ), 20, 0 );
        
        // when the check-out order review gets updated...
	    add_action( 'woocommerce_checkout_update_order_review', array( $this, 'clear_package_cache_on_update_order' ), 10, 2 );

	    // Checks if the shipping method should be excluded, if the conditions that are set is not met.
	    add_filter( 'woocommerce_package_rates', array( $this, 'exclude_shipping_methods' ), 10, 2 );

	    //css + js
        wp_enqueue_script( 'jquery-ui-autocomplete' );
	    wp_enqueue_script( 'woo_conditional_shipping_js', plugin_dir_url( __FILE__ ) . '/admin/js/woo-conditional-shipping.js', array( 'jquery', 'wp-util' ) );
        wp_enqueue_style( 'woo_conditional_shipping_css', plugin_dir_url( __FILE__ ) . '/admin/css/woo-conditional-shipping.css' );
	    wp_enqueue_script( 'woo_specific_date', plugin_dir_url( __FILE__ ) . '/admin/js/woo-specific-date.js' );
    }

	/**
	 * @param $instance_settings
	 * @param $method
	 *
	 * @return mixed
	 */
    public function process_options( $instance_settings, $method ) {
        $instance_settings['wcs_conditions'] = array();

        $post_data = $method->get_post_data();

        $settings_prefix = $method->plugin_id . $method->id;

        if ( isset( $post_data[ $settings_prefix . '_wcs_condition_ids' ] ) ) {
            foreach ( $post_data[ $settings_prefix . '_wcs_condition_ids' ] as $key ) {
                $instance_settings['wcs_conditions'][] = array(
                    'type' => $this->get_field_value( $method, $key, 'wcs_type' ),
                    'value' => $this->get_field_value( $method, $key, 'wcs_value' ),
                );
            }
        }
        // specific delivery days..
        /*
        $instance_settings['specific_delivery_dates'] = [];
        if(isset($post_data['W_Burd_SpecificDeliveryDate'])) {
            foreach($post_data['W_Burd_SpecificDeliveryDate'] as $value) {
                $instance_settings['specific_delivery_dates'][] = $value;
            }
        }
        */
        return $instance_settings;
    }

    /**
     * Get field value from post data
     */
    private function get_field_value( $method, $key, $index ) {
        $post_data = $method->get_post_data();
        $settings_prefix = $method->plugin_id . $method->id;
        $settings_key = "{$settings_prefix}_{$index}_{$key}";
        return isset( $post_data[$settings_key] ) ? $post_data[$settings_key] : NULL;
    }

    /**
     * Adds the condition fields that can be chosen to the shipping-view.
     */
    public function add_fields() {

        if(isset($_REQUEST['instance_id'])) {
            $instance_id = absint( $_REQUEST['instance_id'] );
            $shipping_method = WC_Shipping_Zones::get_shipping_method( $instance_id );
            echo $this->generate_settings_html( $shipping_method );
        }

    }

	/**
	 * @return string
     * @TODO: move into own class.
	 */
    private function generate_title_html_specific_delivery_days($method) {
        /*
        $html = '<h3 class="wc-settings-sub-title">Specific Delivery days</h3>';
        $html.= 'If you want Burd to only deliver at specific dates, then you can customize the settings here. 
Burd will display the nearest delivery-date in the check-out that is set here.<br><br>';

        $html.= '<div id="burd_specific_delivery_dates"></div>';
        */

	    if ( method_exists( $method, 'init_instance_settings' ) && empty( $method->instance_settings ) ) {
		    $method->init_instance_settings();
	    }

	    // specific delivery dates view.
	    if ( isset( $method->instance_settings['specific_delivery_dates'] ) ) {
	        $index = 0;

		    foreach(Burd_Date_Helper::specific_date_helper($method->instance_settings['specific_delivery_dates'])
              as $key => $value) {

		        $weekDaysOptions = [];

		        $weekDaysOptions[] = ['name' => "Monday", 'value' => -4];
                $weekDaysOptions[] = ['name' => "Tuesday", 'value' => -3];
                $weekDaysOptions[] = ['name' => "Wednesday", 'value' => -2];
                $weekDaysOptions[] = ['name' => "Thursday", 'value' => -1];
                $weekDaysOptions[] = ['name' => "Friday", 'value' => 0];

                $weekOptions = '<optgroup label="By week days">';

                foreach($weekDaysOptions as $weekValue) {
	                $selected = '';
	                if($weekValue['value'] == $value['value']) {
		                $selected = 'selected';
	                }
	                $weekOptions.= '<option value="'.$weekValue['value'].'" '.$selected.'>Every '.$weekValue['name'].'</option>';
                }
                $weekOptions.= '</optgroup>';

		        $byDate = '<optgroup label="By Dates">';
		        for($i = 1; 31 >= $i; $i++) {
		            $checked = '';
		            if($i == $key) {
		                $checked = 'checked';
                    }
		            $byDate.= '<option value="'.$i.'" '.$checked.'>Burd will deliver '.$i.'th of the month</option>';
                }
                $byDate.= '</optgroup>';

		       $html.='<div id="specific_element_a_' .  $index . '">
		        <a href="javascript:void(0)" onclick="jQuery(\'#specific_element_a_' .  $index . '\').remove();">Remove</a> -
             <select class="select_specific_delivery" name="W_Burd_SpecificDeliveryDate[]" style="padding: 7px;">
                '.$weekOptions.' '.$byDate.'
            </select><br><br></div>';

            $index++;
		        
            }
	    }

	    //$html.='<button type="button" class="button" id="wcs-add-condition" onclick="Burd_Specific_Date.add()">Add Specific Date</button>';

        return $html;

    }

    /**
     * Generate settings HTML for conditions
     */
    public function generate_settings_html( $method ) {

        $output = $this->generate_title_html_specific_delivery_days($method);

        //$output .= $this->generate_title_html( __( 'Conditions', 'woo-conditional-shipping' ) );
        //$output .= $this->generate_table_html( $method );

        return $output;
    }

    /**
     * Generate settings title
     * @param $title
     * @return string
     */
    private function generate_title_html( $title ) {
        return '<h3 class="wc-settings-sub-title">'.wp_kses_post( $title ).'</h3>';
    }

    /**
     * Generate table HTML
     */
    private function generate_table_html( $method ) {
        if ( method_exists( $method, 'init_instance_settings' ) && empty( $method->instance_settings ) ) {
            $method->init_instance_settings();
        }

        $conditions = [];
        if ( isset( $method->instance_settings['wcs_conditions'] ) ) {
            $conditions = $method->instance_settings['wcs_conditions'];
        }

        return '<table class="form-table wcs-conditions-table" 
        data-instance-id="' . $method->instance_id .'"  
        data-conditions="' . htmlspecialchars( json_encode( $conditions ), ENT_QUOTES, 'UTF-8' ) . '">
        <tbody>' . $this->generate_rows_html( $method ) . '</tbody>' . $this->generate_tfoot_html() . '</table>';
    }

    /**
     * Generate table rows HTML
     */
    private function generate_rows_html( $method ) {
        ob_start();
        ?>
        <?php $this->_conditions_row_template( $method ); ?>
        <?php

        return ob_get_clean();
    }

    /**
     * Template for conditions row
     */
    private function _conditions_row_template( $method ) {
    ?>
    <script type="text/html" id="tmpl-wcs_row_template_<?php echo $method->instance_id; ?>">
        <tr valign="top" class="condition_row">
            <th class="condition_remove">
                <input type="checkbox" class="remove_condition">
            </th>
            <th scope="row" class="titledesc">
                <fieldset>
                    <input type="hidden" name="<?php echo $this->get_field_key( $method, 'wcs_condition_ids'); ?>[]" value="{{ data.index }}" />
                    <select name="<?php echo $this->get_field_key( $method, 'wcs_type' ); ?>_{{data.index}}" class="wcs_condition_type_select">
                        <?php foreach ( $this->condition_filter->filter_groups() as $filter_group ) { ?>
                            <optgroup label="<?php echo $filter_group['title']; ?>">
                                <?php foreach ( $filter_group['filters'] as $key => $title ) { ?>
                                <option value="<?php echo $key; ?>" <# if ( data.type == '<?php echo $key; ?>' ) { #>selected<# } #>><?php echo $title; ?></option>
                                        <?php } ?>
                            </optgroup>
                        <?php } ?>
                    </select>
                </fieldset>
            </th>
            <td class="forminp">
                <fieldset class="wcs_condition_value_inputs">
                    <input class="input-text value_input regular-input wcs_text_value_input" type="text" name="<?php echo $this->get_field_key( $method, 'wcs_value' ); ?>_{{data.index}}" value="{{data.value}}" />

                </fieldset>
            </td>
        </tr>
    </script>
    <?php
    }

    /**
     * Get field key for a shipping method
     */
    private function get_field_key( $method, $key ) {
        return $method->plugin_id . $method->id . '_' . $key;
    }

    /**
     * Generate table foot HTML
     */
    public function generate_tfoot_html() {
        ob_start();
        ?>
        <tfoot>
        <tr valign="top">
            <td colspan="2" class="forminp">
                <button type="button" class="button" id="wcs-add-condition"><?php _e( 'Add Condition', 'woo-conditional-shipping' ); ?></button>
                <button type="button" class="button" id="wcs-remove-conditions"><?php _e( 'Remove Selected', 'woo-conditional-shipping' ); ?></button>
            </td>
        </tr>
        </tfoot>
        <?php

        return ob_get_clean();
    }

    /**
     * Checking if the Burd-shipping should be filtered out from the checkout
     * or not by checking cut-off and filters.
     * @param $rates
     * @param $package
     * @return $rates
     */
    public function exclude_shipping_methods( $rates, $package ) {
        $timepre = microtime(true);
        $logger = wc_get_logger();
        $context = array( 'source' => 'burddelivery' );

        foreach( $rates as $key => $rate ) {

            if($key != Burd_Shipping_Method::get_method_id()) {                
                continue;
            }

            // skip current rate, if it does not match the burd shipping method. (We get all rates from the filter.).
            if($loggingEnabled) {
                $logger->info( 'get_method_id: ' . Burd_Shipping_Method::get_method_id() . ' key: ' . $key , $context);
            }


            if($loggingEnabled) {
                $logger->info( 'rate: ' . wc_print_r( $rate , true ) . ' method_id: ' .  $rate->method_id, $context);
            }

            if (!is_object( $rate ) || !isset( $rate->method_id ) ) {                
                continue;
            }

            if (method_exists( $rate, 'get_instance_id' ) && strlen(strval( $rate->get_instance_id())) > 0 ) {
                $instance_id = $rate->get_instance_id();
            } else {
                $instance_id = Burd_Shipping_Method::faker_instance_id();
            }

            $instance_id = strval( $instance_id );

            if($loggingEnabled) {
                $logger->info( 'instance_id: ' . $instance_id, $context);
            }

            if (! $instance_id ) {                
                continue;
            }

            $instance = new Burd_Shipping_Method($instance_id);

            $loggingEnabled = $instance->get_option('enableLogger') == 'yes';

            if($loggingEnabled) {
                $logger->info('Start: at rate', $context);
            }
    
            if (!isset($package['destination']['postcode']) || empty($package['destination']['postcode'])) {
                unset( $rates[Burd_Shipping_Method::get_method_id()] );
    
                if($loggingEnabled) {
                    $logger->info( 'Postcode is empty.', $context);
                }
    
                return $rates;
            }

	        $class_name = $this->allowed_shipping_classes[$rate->method_id];

            if($loggingEnabled) {
                $logger->info( 'class_name: ' . $class_name, $context);
            }

            if ( ! is_object( $class_name ) && ! class_exists( $class_name ) ) {                
                continue;
            }

            $itemsInStock = 0;
            $itemsNotInStock = 0;
            $totalweight = 0;
            $itemlimitreached = false;
            foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
                if($loggingEnabled) {
                    $logger->info( 'get_cart values: ' . wc_print_r( $values , true ), $context);
                }
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
                    
                    // Some version default is 0 if not set, some null                
                    $itemweight = $values['data']->get_weight() || 0;
                    $weight = $itemweight * $values['quantity'];
                    $totalweight += $weight;

                    if($itemweight > $instance->get_option('maxitemweight')) {
                        $itemlimitreached = true;
                    } 
            }

            if($loggingEnabled) {
                $logger->info( 'totalweight: ' . $totalweight . ' maxorderweight: ' . $instance->get_option('maxorderweight'), $context);
            }

            if($totalweight > $instance->get_option('maxorderweight') || $itemlimitreached) {
                unset( $rates[$key] );
            }

            if($loggingEnabled) {
                $logger->info( 'itemsNotInStock: ' . $itemsNotInStock . ' backorder: ' . $instance->get_option('backorder'), $context);
            }

            if($itemsNotInStock > 0 && $instance->get_option('backorder') == 'no') {
                unset( $rates[$key] );
            }
                 

            // validate origin fields is validated, otherwise unset it.
            // order handler requires the data to be non-empty.
            if($loggingEnabled) {
                $logger->info( 'origin adress:' . get_option('woocommerce_burd_settings_origin_address') . ' postcode: ' . get_option('woocommerce_burd_settings_origin_postcode') . ' City: ' . get_option('woocommerce_burd_settings_origin_city') . ' Name: ' . get_option('woocommerce_burd_settings_company_name'), $context);
            }

            if(empty(get_option('woocommerce_burd_settings_origin_address'))
               ||  empty(get_option('woocommerce_burd_settings_origin_postcode'))
               ||  empty(get_option('woocommerce_burd_settings_origin_city'))
               ||  empty(get_option('woocommerce_burd_settings_company_name'))) {                
                unset( $rates[$key] );
            }

	        // Some 3rd party shipping methods such as WooCommerce Services provides object
            // directly instead of class name
            if ( is_object( $class_name ) ) {
                $method = $class_name;
            } else {
                $method = new $class_name($instance_id);
            }

	        $instance_settings = isset( $method->instance_settings ) ? $method->instance_settings : array();
            if ( ( ! isset( $method->instance_settings ) || empty( $method->instance_settings ) ) && method_exists( $method, 'init_instance_settings' ) ) {
                $method->init_instance_settings();
                $instance_settings = $method->instance_settings;
            }

	        $burd_Delivery_Date = new Burd_Delivery_Date();

            $delivery_dates = $burd_Delivery_Date->burd_delivery_dates($package['destination']['postcode']);

            if($loggingEnabled) {       
                $logger->info( 'delivery_dates: ' . wc_print_r( $delivery_dates , true ) . ' postcode: ' . $package['destination']['postcode'], $context);
            }

	        if(!isset($delivery_dates) || !is_array($delivery_dates) || count($delivery_dates) == 0) {
                unset( $rates[$key] );
            } else {
	            // store delivery dates in option.
                $key = 'burd_delivery_delivery_dates_postcode_' . $package['destination']['postcode'];
                delete_option($key);
                add_option( $key, $delivery_dates, '', 'no' );
            }

            if($loggingEnabled) {
                $logger->info( 'Burd: option key 1', $context);
            }

            // Fix for WooCommerce Services, making sure they are using the correct option_key.            
            if ( strpos( $method->id, 'wc_services' )  && empty( $instance_settings ) && ! empty( $instance_id ) ) {
                $option_key = $method->plugin_id . $method->id . '_' . $instance_id . '_settings';
                $instance_settings = get_option( $option_key, array() );
            }

            if($loggingEnabled) {
                $logger->info( 'Burd: option key 2', $context);
            }

            if ( isset( $instance_settings['wcs_conditions'] ) && is_array( $instance_settings['wcs_conditions'] ) ) {
                foreach ( $instance_settings['wcs_conditions'] as $index => $condition ) {
                    if ( isset( $condition['type'] ) && ! empty( $condition['type'] ) && method_exists(  $this->condition_filter, "filter_{$condition['type']}" ) ) {
                        if ( call_user_func( array(  $this->condition_filter, "filter_{$condition['type']}" ), $condition, $package ) ) {
                           if(isset($rates[$key])) { unset( $rates[$key] ); } // making sure, the key is still set. (notice: our cut-off, unsets if the time is exceeded).
                        }
                    }
                }
            }

        }
        
        if($loggingEnabled) {
            $timepost = microtime(true);
            $exectime = ($timepost - $timepre) * 1000;
            $logger->info( 'rates: ' . wc_print_r( $rates , true ) . ' exectime: ' . $exectime . ' ms', $context);
        }

        return $rates;

    }

	/**
	 * Clears the caching.
     * filter woocommerce_checkout_update_order_review is called
	 */
	public function clear_package_cache_on_update_order() {
        $packages = WC()->cart->get_shipping_packages();
        if(count($packages) > 0) {
	        foreach ( $packages as $key => $value ) {
		        $shipping_session = "shipping_for_package_$key";
		        unset( WC()->session->$shipping_session );
	        }
        }
    }

}

