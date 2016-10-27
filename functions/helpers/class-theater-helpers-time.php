<?php
	
/**
 * Time-related helper functions.
 * @since	0.15.11
 * @package	Theater/Helpers
 */
class Theater_Helpers_Time {
	
	/**
	 * Gets the next day start time offset.
	 * 
	 * The next day start time offset is used to determine on which day an event should appear in lists.
	 * Eg. if the offset is '2 * HOUR_IN_SECONDS' then all events that start before 2 AM are treated as if 
	 * they start on the previous day.
	 *
	 * @since	0.15.11
	 * @return	int		The offset in seconds.
	 */
	static function get_next_day_start_time_offset() {

		$next_day_start_time_offset = 0;

		/**
		 * Filters the next day start time offset.
		 * 
		 * @since	0.15.11
		 * @since	0.15.14	Changed filtername to lowercase.
		 * 
		 * @var 	int		The offset in seconds.
		 */
		$next_day_start_time_offset = apply_filters('theater/helpers/time/next_day_start_time_offset', $next_day_start_time_offset);

		return $next_day_start_time_offset;
		
	}
	
}
