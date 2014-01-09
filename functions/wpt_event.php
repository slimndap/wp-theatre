<?php
class WPT_Event extends WP_Theatre {

	const post_type_name = 'wp_theatre_event';
	
	function post_type() {
		return get_post_type_object(self::post_type_name);
	}
	
	function get_production() {
		if (!isset($this->production)) {
			$this->production = new WPT_Production(get_post_meta($this->ID,WPT_Production::post_type_name, TRUE), $this->PostClass);
		}
		return $this->production;		
	}
	
	function production() {
		return $this->get_production();
	}
	
	function datetime() {
		if (!isset($this->datetime)) {
			$this->datetime = strtotime($this->post()->event_date);
		}	
		return $this->datetime;	
	}

	function date() {
		if (!isset($this->date)) {
			$this->date = date(get_option('date_format'),$this->datetime());
		}	
		return $this->date;	
	}

	function time() {
		if (!isset($this->time)) {
			$this->time = date(get_option('time_format'),$this->datetime());
		}	
		return $this->time;
	}
	
	function post_class() {
	}
}

?>