<?php
class WPT_Season extends WP_Theatre {
	const post_type_name = 'wp_theatre_season';
	
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

	function get_productions($PostClass = false) {
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

			if ($PostClass===false) {
				$PostClass = $this->PostClass;
			}
			
			$posts = parent::get_posts($args, $PostClass);		
			
			$productions = array();
			for ($i=0;$i<count($posts);$i++) {
				$production = new WPT_Production($posts[$i]->ID);
				$production->post = $posts[$i];
				$productions[] = $production;
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