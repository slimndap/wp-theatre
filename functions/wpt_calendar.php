<?php

	/*
	 * A calendar with upcoming events.
	 * @since 0.8
	 *
	 */

	class WPT_Calendar {
	
	function __construct() {
	}
	
	
	/*
	* Get the HTML version of the calendar.
	* @since 0.8
	*/
	
	function html($args = array()) {
		global $wp_theatre;
		
		$defaults = array(
			'months' => array()
		);
		$args = wp_parse_args($args, $defaults );
		
		/*
		 * If no months are set, show all months between now and the month of the last event.
		 */
		 
		if (empty($args['month'])) {
			$months = $wp_theatre->events->months();
			$months = array_keys($months);			
		}
		
		$html = '';
		
		foreach ($months as $month) {
		
			$month_html = '';

			$start_of_week = get_option('start_of_week');

			$first_day = strtotime($month.'-01');
			$no_of_days = date('t',$first_day);
			$last_day = strtotime($month.'-'.$no_of_days);

			// Month header
			$month_html.= '<h3>'.date_i18n('F Y',$first_day).'</h3>';
			
			// Calculate leading days (of previous month)
			$first_day_pos = date('w',$first_day) - $start_of_week;
			if ($first_day_pos < 0) {
				$leading_days = 7 + $first_day_pos;
			} else {
				$leading_days = $first_day_pos;
			}
			
			// Calculate trailing days (of next month)
			$last_day_pos = date('w',$last_day) - $start_of_week;
			if ($last_day_pos < 0) {
				$trailing_days = -1 - $last_day_pos;
			} else {
				$trailing_days = 6 - $last_day_pos;
			}
			
			$first_day -= $leading_days * 60 * 60 * 24;
			$no_of_days += $leading_days + $trailing_days;
		
			$days = array();
			for($i=0;$i<$no_of_days;$i++) {
				$date = date('Y-m-d', $first_day + ($i * 60*60*24));
				$days[$date] = array();
			}

			$filters = array(
				'month' => $month,
				'upcoming' => true
			);
			$filters['month'] = $month;
			$events = $wp_theatre->events->load($filters);
			foreach ($events as $event) {
				$date = date('Y-m-d',$event->datetime());
				$days[$date][] = $event;
			}

			$month_html.= '<div class="wpt_days">';
			
			foreach($days as $day=>$events) {
				$day_html = '';
				$classes = array();

				$day_label = (int) substr($day,8,2);

				if (empty($events)) {
					$day_html.= $day_label;				
				} else {
					
					if (count($events)==1) {
						$url = $events[0]->production()->permalink();
					} else {
						$url = $wp_theatre->listing_page->url(array('wpt_day'=>$day));
					}

					$day_html.= '<a href="'.$url.'">';
					$day_html.= $day_label;				
					$day_html.= '</a>';
				}

					if (date('Y-m',strtotime($day)) != $month) {
						$classes[] = 'trailing';
					}

				$month_html.= '<div class="wpt_day '.implode(' ',$classes).'">'.$day_html.'</div>';
			}

			$month_html.= '</div>';
			
			$html.= $month_html;

		}
		$html = '<div class="wpt_calendar">'.$html.'</div>';
		
		return $html;
	}
	
	/*
	* Get the JSON version of the calendar.
	*/
	
	function json($args = array()) {
	
	}
	
	function check_dependencies() {
	
	}
	}

?>