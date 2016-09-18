<?php
	
/**
 * Time-related helper functions.
 * @since	0.15.11
 * @package	Theater/Helpers
 */
class Theater_Helpers_Time {
	
	/**
	 * get_next_day_offset function.
	 * 
	 * @since	0.15.11
	 * @static
	 * @return	int
	 */
	static function get_next_day_start_time_offset() {

		$next_day_start_time_offset = 0;

		/**
		 * next_day_start_time_offset
		 * 
		 * (default value: apply_filters('Theater/Helper/Time/Next_Day_Start_Time_Offset', $next_day_offset))
		 * 
		 * @var string
		 * @access public
		 */
		$next_day_start_time_offset = apply_filters('Theater/Helpers/Time/Next_Day_Start_Time_Offset', $next_day_start_time_offset);

		return $next_day_start_time_offset;
		
	}
	
}