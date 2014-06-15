<?php
class WPT_Listing {
	function defaults() {
		return array();
	}

	function to_array($filters=array()) {
		$objects = $this->get($filters);
		
		foreach($objects as $object) {
			$array[] = $object->to_array();
		}
		
		return $array;
		
	}

	function json($args=array()) {
		return json_encode($this->to_array());
	}
	

	/*
	 * Generate navigation for a listing filter.
	 * @see WPT_Productions::html()
	 * @see WPT_Events::html()
	 * @since 0.8
	 */

	function filter_pagination($field, $options, $args=array()) {
		global $wp_query;

		/*
		 * Build the base url for all filters
		 */
		$current_url = $_SERVER['REQUEST_URI'];
		if (!empty($args['day'])) {
			$current_url = add_query_arg('wpt_day',$args['day'],$current_url);
		}
		if (!empty($args['month'])) {
			$current_url = add_query_arg('wpt_month',$args['month'],$current_url);
		}
		if (!empty($args['category'])) {
			$current_url = add_query_arg('wpt_category',$args['category'],$current_url);
		}		

		$query_var = 'wpt_'.$field;

		$paginate = in_array($field,$args['paginateby']);
		
		$html = '';

		if (
			$paginate ||
			!empty($wp_query->query_vars[$query_var])
		) {
			foreach($options as $slug=>$name) {
			
				$url = remove_query_arg($query_var, $current_url);
				$classes = array('wpt_listing_filter');
				if ($slug != $args[$field]) {
					if (!$paginate) {
						continue;
					}
					$url = add_query_arg($query_var, $slug , $url);
				} else {
					$classes[] = 'wpt_listing_filter_active';					
				}
				
				$url = apply_filters('wpt_listing_filter_pagination_url', $url);
				$html.= '<span class="'.implode(' ',$classes).'"><a href="'.htmlentities($url).'">'.$name.'</a></span> ';
			}

			$html = '<div class="wpt_listing_filter_pagination '.$field.'">'.$html.'</div>';

		}
		
		return $html;

	}

	function get($filters=array()) {
		$filters = wp_parse_args( $filters, $this->defaults() );
		return $this->load($filters);				
	}
	
	public function html($args=array()) {
		return '';
	}

	function load() {
		return array();
	}
}
?>