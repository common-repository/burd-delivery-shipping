<?php
class Number_Helper {

    /**
     * Parses a number to float.
     * @param $number
     * @return bool|float
     */
    public static function parseNumberToFloat($number)
    {
        $number = str_replace( ',', '.', $number );
        if ( is_numeric( $number ) ) {
            return floatval( $number );
        }
        return false;
    }

		/**
		 * Makes the cut off time more easier to read for the webshop-customers.
		 * If the admin have wrote for instance "15" it will parse it to 15:00.
		 * @param $cut_off_time
		 * @return string
		 */
    public static function cut_off_time($cut_off_time)
    {
	    $split = explode(":",$cut_off_time);
	    $length = count($split);
	    if($length == 1)
	    {
		    $cut_off_time = $cut_off_time.":00";
	    }
	    return $cut_off_time;
		}

}