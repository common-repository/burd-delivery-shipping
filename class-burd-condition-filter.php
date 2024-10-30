<?php

class Burd_Condition_Filter extends Burd_Package_Calculation {

    /**
     * Holds the possible conditions.
     * @return array
     */
    public function filter_groups() {

        return array(
            array(
                'title' => __( 'Measurements', 'woo-conditional-shipping' ),
                'filters' => array(
                    'min_weight' => sprintf( __( 'Minimum Weight (%s)', 'woo-conditional-shipping' ),
                        get_option( 'woocommerce_weight_unit' ) ),
                    'max_weight' => sprintf( __( 'Maximum Weight (%s)', 'woo-conditional-shipping' ),
                        get_option( 'woocommerce_weight_unit' ) ),
                    'max_height' => sprintf( __( 'Maximum Total Height (%s)', 'woo-conditional-shipping' ), get_option( 'woocommerce_dimension_unit' ) ),
                    'max_length' => sprintf( __( 'Maximum Total Length (%s)', 'woo-conditional-shipping' ), get_option( 'woocommerce_dimension_unit' ) ),
                    'max_width' => sprintf( __( 'Maximum Total Width (%s)', 'woo-conditional-shipping' ), get_option( 'woocommerce_dimension_unit' ) ),
                    'min_volume' => sprintf( __( 'Minimum Total Volume (%s&sup3;)', 'woo-conditional-shipping' ), get_option( 'woocommerce_dimension_unit' ) ),
                    'max_volume' => sprintf( __( 'Maximum Total Volume (%s&sup3;)', 'woo-conditional-shipping' ), get_option( 'woocommerce_dimension_unit' ) ),
                )
            ),
            array(
                'title' => __( 'Order Totals', 'woo-conditional-shipping' ),
                'filters' => array(
                    'min_subtotal' => __( 'Minimum Subtotal', 'woo-conditional-shipping' ),
                    'max_subtotal' => __( 'Maximum Subtotal', 'woo-conditional-shipping' ),
                )
            ),
	        array(
		        'title' => __( 'Products', 'woo-conditional-shipping' ),
		        'filters' => array(
			        'product_in_stock' => __( 'Product must be in stock', 'woo-conditional-shipping' ),
		        )
	        )
        );

    }

    /**
     * Filter by cart maximum weight
     * @return bool
     */
    public function filter_max_weight( $condition, $package ) {
        $weight = $this->calculate_package_weight( $package );

        if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
            $max_weight = Number_Helper::parseNumberToFloat( $condition['value'] );
            if ( $max_weight && $max_weight > 0 && $weight > $max_weight ) {
                return true;
            }
        }

        return false;
    }

		/**
		* @param $condition
		* @param $package
	  * @return bool
		*/
		public function filter_product_in_stock($condition, $package) {

			foreach ( $package['contents'] as $key => $item ) {

				$product = $item['data'];

				// Compatibility for WC versions from 2.5.x to 3.0+
				if ( method_exists( $product, 'get_stock_status' ) ) {
					$stock_status = $product->get_stock_status(); // For version 3.0+
				} else {
					$stock_status = $product->stock_status; // Older than version 3.0
				}

				// product is not in stock.
				if( ! $product->managing_stock() && ! $product->is_in_stock() || $stock_status != "instock") {
					return true;
				}

			}

			return false;

		}

    /**
     * Filter by cart minimum weight
     * @return bool
     */
    public function filter_min_weight( $condition, $package ) {
        $weight = $this->calculate_package_weight( $package );

        if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
            $min_weight = Number_Helper::parseNumberToFloat( $condition['value'] );
            if ( $min_weight && $min_weight > 0 && $weight < $min_weight ) {
                return true;
            }
        }

        return false;

    }

    /**
     * Filter by cart maximum height
     * @return bool
     */
    public function filter_max_height( $condition, $package ) {
        $height = $this->calculate_package_height( $package );

        if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
            $max_height = Number_Helper::parseNumberToFloat($condition['value'] );
            if ( $max_height && $max_height > 0 && $height > $max_height ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter by cart maximum length
     * @return bool
     */
    public function filter_max_length( $condition, $package ) {
        $length = $this->calculate_package_length( $package );

        if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
            $max_length = Number_Helper::parseNumberToFloat($condition['value'] );
            if ( $max_length && $max_length > 0 && $length > $max_length ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter by cart maximum width
     */
    public function filter_max_width( $condition, $package ) {
        $width = $this->calculate_package_width( $package );

        if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
            $max_width = Number_Helper::parseNumberToFloat( $condition['value'] );
            if ( $max_width  && $max_width > 0 && $width > $max_width ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter by cart minimum volume
     */
    public function filter_min_volume( $condition, $package ) {
        $volume = $this->calculate_package_volume( $package );

        if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
            $min_volume = Number_Helper::parseNumberToFloat( $condition['value'] );
            if ( $min_volume && $min_volume > 0 && $volume < $min_volume ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter by cart maximum volume
     */
    public function filter_max_volume( $condition, $package ) {
        $volume = $this->calculate_package_volume( $package );

        if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
            $max_volume = Number_Helper::parseNumberToFloat( $condition['value'] );
            if ( $max_volume && $max_volume > 0 && $volume > $max_volume ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter by maximum subtotal
     */
    public function filter_max_subtotal( $condition, $package ) {
        $subtotal = WC()->cart->subtotal;

        if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
            $max_subtotal = Number_Helper::parseNumberToFloat( $condition['value'] );
            if ( $max_subtotal && $max_subtotal > 0 && $subtotal > $max_subtotal ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter by cart
     * minimum subtotal
     */
    public function filter_min_subtotal( $condition, $package ) {
        $subtotal = WC()->cart->subtotal;

        if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
            $max_subtotal = Number_Helper::parseNumberToFloat( $condition['value'] );
            if ( $max_subtotal && $max_subtotal > 0 && $subtotal < $max_subtotal ) {
                return true;
            }
        }

        return false;
    }

}