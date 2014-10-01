<?php
class WPT_Listing {
	function defaults() {
		return array();
	}

	/*
	 * Generate navigation for a listing filter.
	 * @see WPT_Productions::html()
	 * @see WPT_Events::html()
	 * @since 0.8
	 */

	function filter_pagination($field, $options, $args=array()) {
		global $wp_query;

		$html = '';

		$query_var = 'wpt_'.$field;
		$paginate = in_array($field,$args['paginateby']);
		
		/**
		 * Bail if:
		 * - pagination is not active for this $field and 
		 * - no query_var is set for this $field.
		 */

		if (
			!$paginate &&
			empty($wp_query->query_vars[$query_var])
		) {
			return $html;
		};

		/*
		 * Build the base url for all filters
		 */
		$current_url = $_SERVER['REQUEST_URI'];

		if (!empty($args[$field])) {
			$current_url = add_query_arg($query_var,$args[$field],$current_url);
		}

		foreach($options as $slug=>$name) {
		
			$url = remove_query_arg($query_var, $current_url);
			$classes = array('wpt_listing_filter');

			/**
			 * Check if $option is the current page.
			 */

			$is_current_page = false;
			
			if (!empty($args['start']) && $slug == $args['start']) {

				/**
				 * $option is the current page for a time-based pagination (eg. day or month).
				 */
			
				$is_current_page = true;

			} elseif ($slug == $args[$field]) {

				/**
				 * $option is the current page for a text-based pagination (eg. category).
				 */
			
				$is_current_page = true;				
				
			}

			if ($is_current_page) {
				$classes[] = 'wpt_listing_filter_active';					
			} else {
				if (!$paginate) {
					continue;
				}
				$url = add_query_arg($query_var, $slug , $url);
			}
			
			$url = apply_filters('wpt_listing_filter_pagination_url', $url);
			$html.= '<span class="'.implode(' ',$classes).'"><a href="'.htmlentities($url).'">'.$name.'</a></span> ';
		}

		return '<div class="wpt_listing_filter_pagination '.$field.'">'.$html.'</div>';

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