<?php

class Burd_Date_Helper {

	private $months = array('januar', 'februar', 'marts', 'april', 'maj', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'december');

	/**
	 * @param $date
	 * @return bool
	 */
	public static function is_today($date) {
		return $date == date("Y-m-d");
	}

	public static function readable_date($datetime_object) {
		setlocale(LC_TIME, "Danish");
		return strtolower( strftime( "",
			$datetime_object->getTimestamp() ) ) . " d. " . (int) strftime( "%d",
			$datetime_object->getTimestamp() ) . "/" . strtolower( strftime( "%m",
			$datetime_object->getTimestamp() ));
	}

	/**
	 * @param $date
	 * @param $cut_off
	 *
	 * @return bool
	 */
	public static function cut_off_time_exceeded($date, $cut_off)
	{
		if(!empty($cut_off))
		{
			$cut_off_helper = self::cut_off_helper($date, $cut_off);

			if(!empty($cut_off_helper['cut_off_timestamp']))
			{
				if($cut_off_helper['cut_off_timestamp'] < $cut_off_helper['now'])
				{
					return true;
				}
			}
		}

		return false;

	}

	/**
	 * @param $index
	 *
	 * @return mixed|string
	 */
	public function getMonthName($index)
	{
		$index--; // we get the month index as 1,2,3,4
		if(!isset($this->months[$index])) {
			return "";
		}
		return $this->months[$index];
	}

	/**
	 * Makes sure all dates and timestamps is in Europe/Copenhagen,
	 * if the timezone is not available use server-time.
	 * @param $cut_off
	 * @return array
	 */
	private static function cut_off_helper($date, $cut_off)
	{
		$date_utc = new \DateTime("now", new \DateTimeZone("UTC"));
		$cut_off = new \DateTime($date . " " . $cut_off, new \DateTimeZone("UTC"));
		// returning array.
		return array( 'now' => $date_utc->getTimestamp(), 'cut_off_timestamp' => ($cut_off->getTimestamp() - 7200) );

	}

	/**
	 * @param $list
	 * @param null $cut_off
	 *
	 * @return array
	 */
	public static function specific_date_helper($list, $cut_off = null)
	{
		$newList = [];
		foreach ( $list as $value ) {

			// in array means every x day.
			if ( in_array( $value, array( 0, -1, -2, -3, -4) ) )
			{
				$newList[] = ['timestamp' => self::next_timestamp($value, $cut_off), 'value' => $value];
			} else {

				$timestampNextDate = strtotime(date("Y-m-" . $value));

				if(time() > $timestampNextDate)
				{
					$timestampNextDate = strtotime(date("Y-m-" . $value),strtotime("+1 months"));
				}

				$newList[] = ['timestamp' => $timestampNextDate, 'value' => $value];

			}

		}

		// sort ASC.
		sort($newList);

		return $newList;

	}

	/**
	 * @param $weekDay
	 * @param null $cut_off
	 *
	 * @return false|int
	 */
	private static function next_timestamp( $weekDay, $cut_off = null )
	{

      if ( $weekDay == -4) {
      	if(date("D") == "Mon") {
		      return strtotime(date("Y-m-d 00:00:01"));
	      }
        return strtotime('next monday');
      }

      if ( $weekDay == - 3) {
	      if(date("D") == "Tue") {
		      return strtotime(date("Y-m-d 00:00:01"));
	      }
        return strtotime('next tuesday');
      }

      if ( $weekDay == - 2) {
	      if(date("D") == "Wed") {
		      return strtotime(date("Y-m-d 00:00:01"));
	      }
        return strtotime('next wednesday');
      }

      if ( $weekDay == - 1) {
	      if(date("D") == "Thu") {
		      return strtotime(date("Y-m-d 00:00:01"));
	      }
        return strtotime('next thursday');
      }

      if ( $weekDay == 0 ) {
	      if(date("D") == "Fri") {
		      return strtotime(date("Y-m-d 00:00:01"));
	      }
        return strtotime('next friday');
      }

      return time();

	}

}