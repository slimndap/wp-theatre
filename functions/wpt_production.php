<?php
class WPT_Production extends WP_Theatre {

	const post_type_name = 'wp_theatre_prod';
	
	function __construct($ID=false) {
		if ($ID===false) {
			$ID = get_the_ID();
		}
		parent::__construct($ID);
	}
	
	function post_type() {
		return get_post_type_object(self::post_type_name);
	}

	function is_upcoming() {		
		$events = $this->get_events();
		return (is_array($events) && (count($events)>0));
	}
	
	function dates_short() {
		$dates_short = '';
		$first_datetimestamp = $last_datetimestamp = '';
		
		$events = $this->get_events();
		
		if (is_array($events) && (count($events)>0)) {
		
			foreach ($this->get_events() as $event) {
				if ($first_datetimestamp == '') {
					$first_datetimestamp = strtotime($event->event_date);
				}
				
			}
			$last_datetimestamp = strtotime($event->event_date);
			$datetimestamp_now = strtotime('now');

		
			if ($datetimestamp_now < $first_datetimestamp) {
				$dates_short.= strftime('%e %b.', $first_datetimestamp);
				if ($last_datetimestamp != $first_datetimestamp) {
					$dates_short.= ' t/m '.strftime('%e %b.', $last_datetimestamp);
				}
			}
			else {
				if ($last_datetimestamp != $first_datetimestamp) {
					$dates_short.= 'nog t/m '.strftime('%e %b.', $last_datetimestamp);
				}
			}
		}
		return $dates_short;
	}

	function get_events() {
		if (!isset($this->events)) {
			$args = array(
				'post_type'=>WPT_Event::post_type_name,
				'meta_key' => 'event_date',
				'order_by' => 'meta_value_num',
				'order' => 'ASC',
				'meta_query' => array(
					array(
						'key' => self::post_type_name,
						'value' => $this->ID,
						'compare' => '=',
					),
					array(
						'key' => 'event_date', // Check the start date field
						'value' => date("Y-m-d"), // Set today's date (note the similar format)
						'compare' => '>=', // Return the ones greater than today's date
						'type' => 'NUMERIC,' // Let WordPress know we're working with numbers
					)
				),
			);
			$this->events = get_posts($args);
		}
		return $this->events;
	}
	
	function events() {
		return $this->get_events();
	}

	function render_events() {
		$html = '';
		$html.= '<h3>'.WPT_Event::post_type()->labels->name.'</h3>';
		$html.= '<ul>';
		foreach ($this->get_events() as $event) {
			$html.= '<li>';
			$html.=strftime('%x %X',strtotime(get_post_meta($event->ID,'event_date',true))); 
			$html.= '<br />';
			$html.= get_post_meta($event->ID,'venue',true).', '.get_post_meta($event->ID,'city',true);
			$html.= '<br />';
			$html.= '<a href="'.get_post_meta($event->ID,'tickets_url',true).'">';
			$html.= __('Tickets');			
			$html.= '</a>';
			$html.= '</li>';
		}
		$html.= '</ul>';
		return $html;
	}


}

?>