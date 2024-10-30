<?php

class Burd_Package_Calculation
{

    /**
     * Calculate cart weight
     */
    protected function calculate_package_weight($package) {
        $total_weight = 0;

        foreach ( $package['contents'] as $key => $data ) {
            $product = $data['data'];

            if ( ! $product->needs_shipping() )
            {
                continue;
            }

            $item_weight = $product->get_weight();
            if ($item_weight) {
                $total_weight += $item_weight * $data['quantity'];
            }
        }

        return $total_weight;
    }

    /**
     * Calculate cart volume
     */
    protected function calculate_package_volume($package) {
        $total_volume = 0;

        foreach ( $package['contents'] as $key => $data ) {
            $product = $data['data'];

            if ( ! $product->needs_shipping() ) {
                continue;
            }

            $length = $product->get_length();
            $width = $product->get_width();
            $height = $product->get_height();

            if ( is_numeric ( $length ) && is_numeric( $width ) && is_numeric( $height ) ) {
                $volume = $length * $width * $height;
                $total_volume += $volume * $data['quantity'];
            }

        }

        return $total_volume;
    }

    /**
     * Calculate cart height
     */
    protected function calculate_package_height($package) {
        $total = 0;

        foreach ( $package['contents'] as $key => $data ) {
            $product = $data['data'];

            if ( ! $product->needs_shipping() || ! $product->has_dimensions() ) {
                continue;
            }

            $item_height = $product->get_height();

            if ( $item_height ) {
                $total += floatval( $item_height ) * $data['quantity'];
            }
        }

        return $total;
    }


    /**
     * Calculate cart width
     */
    protected function calculate_package_width($package) {

        $total = 0;

        foreach ( $package['contents'] as $key => $data ) {
            $product = $data['data'];

            if ( ! $product->needs_shipping() or ! $product->has_dimensions() ) {
                continue;
            }

            $width = $product->get_width();

            if ( $width ) {
                $total += floatval( $width ) * $data['quantity'];
            }
        }

        return $total;

    }
}