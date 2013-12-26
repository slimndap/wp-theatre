<?php
class WPT_Event extends WP_Theatre {

	const post_type_name = 'wp_theatre_event';
	
	function __construct($ID=false, $PostClass=false) {
		if ($ID===false) {
			$ID = get_the_ID();
		}
		$this->ID = $ID;
		$this->PostClass = $PostClass;
	}
	
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
		
	function get_upcoming() {
		
	}
}

?>