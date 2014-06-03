<?php
class WPT_Listing {
	function defaults() {
		return array();
	}

	function filter_pagination($field, $options, $args=array()) {
		global $wp_query;

		$current_url = $_SERVER['REQUEST_URI'];
		if (!empty($args['month'])) {
			$current_url = add_query_arg('wpt_month',$args['month']);
		}
		if (!empty($args['category'])) {
			$current_url = add_query_arg('wpt_category',$args['category']);
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
				$html.= '<span class="'.implode(' ',$classes).'"><a href="'.$url.'">'.$name.'</a></span>';
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