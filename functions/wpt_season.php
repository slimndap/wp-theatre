<?php
class WPT_Season extends WP_Theatre {
	const post_type_name = 'wp_theatre_season';
	
	function __construct($ID=false, $PostClass=false) {
		parent::__construct($ID, $PostClass);
		if (!$this->ID) {
			$this->ID = get_the_ID();
		}		
	}
	
	function post_type() {
		return get_post_type_object(self::post_type_name);
	}

	function get_productions() {
		if (!isset($this->productions)) {
			$args = array(
				'post_type'=>WPT_Production::post_type_name,
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => self::post_type_name,
						'value' => $this->ID,
						'compare' => '=',
					)
				),
			);

			$posts = get_posts($args);		
			
			$productions = array();
			for ($i=0;$i<count($posts);$i++) {
				$productions[] = new WPT_Production($posts[$i], $this->PostClass);
			}
			$this->productions = $productions;
		}

		return $this->productions;
	}
	
	function productions() {
		return $this->get_productions();
	}
}
?>