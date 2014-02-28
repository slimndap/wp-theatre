<?php
class WPT_Season {
	const post_type_name = 'wp_theatre_season';
	
	function __construct($ID=false, $PostClass=false) {
		$this->PostClass = $PostClass;
	
		if ($ID instanceof WP_Post) {
			// $ID is a WP_Post object
			if (!$PostClass) {
				$this->post = $ID;
			}
			$ID = $ID->ID;
		}

		$this->ID = $ID;		
	}
	
	function post_type() {
		return get_post_type_object(self::post_type_name);
	}

	function productions() {
		global $wp_theatre;
		$args = array(
			self::post_type_name=>$this->post()->post_name
		);
		return $wp_theatre->productions->all($args,$this->PostClass);
	}

	function title() {
		if (!isset($this->title)) {
			$this->title = apply_filters('wpt_season_title',$this->post()->post_title,$this);
		}	
		return $this->title;			
	}


	/**
	 * The custom post as a WP_Post object.
	 *
	 * This function is inherited by the WPT_Production, WPT_Event and WPT_Seasons object.
	 * It can be used to access all properties and methods of the corresponding WP_Post object.
	 * 
	 * Example:
	 *
	 * $event = new WPT_Event();
	 * echo WPT_Event->post()->post_title();
	 *
	 * @since 0.3.5
	 *
	 * @return mixed A WP_Post object.
	 */
	public function post() {
		return $this->get_post();
	}

	private function get_post() {
		if (!isset($this->post)) {
			if ($this->PostClass) {
				$this->post = new $this->PostClass($this->ID);				
			} else {
				$this->post = get_post($this->ID);
			}
		}
		return $this->post;
	}
}
?>