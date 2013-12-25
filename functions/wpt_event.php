<?php
class WPT_Event extends WP_Theatre {

	const post_type_name = 'wp_theatre_event';
	
	function __construct($ID=false) {
		if ($ID===false) {
			$ID = get_the_ID();
		}
		parent::__construct($ID);
	}
	
	function post_type() {
		return get_post_type_object(self::post_type_name);
	}
	
	function get_production() {
		if (!isset($this->production)) {
			$this->production = get_post(get_post_meta($this->ID,'wp_theatre_prod', TRUE));
		}
		return $this->production;		
	}
	
	function production() {
		return $this->get_production();
	}
		
	function get_upcoming() {
		
	}
}

?>