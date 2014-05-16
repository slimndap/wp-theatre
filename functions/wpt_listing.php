<?php
class WPT_Listing {
	public function __toString() {
		return $this->html();
	}
	
	public function __invoke($filters=array()) {
		return $this->get($filters);
	}

	function defaults() {
		return array();
	}

	function get($filters=array()) {
		$filters = wp_parse_args( $filters, $this->defaults() );
		return $this->load($filters);				
	}
	
	/*
	 * Generate a month, category or season filter navigation for listings.
	 */
	
	function filter_navigation($args=array()) {

		$defaults = array(
			'paginateby' => array(),
			'production' => false,
			'season' => false,
			'limit' => false,
			'category' => false,
			'template' => NULL
		);
		$args = wp_parse_args( $args, $defaults );

		$filters = array(
			'upcoming' => true,
			'production' => $args['production'],
			'limit' => $args['limit'],
			'category' => $args['category'],
			'season' => $args['season']
		);

		// Months
		if (in_array('month',$args['paginateby'])) {
			$months = $this->months($filters);
		}
		if (in_array('month',$args['paginateby'])) {
			$months = $this->categories($filters);
		}
		
		
		global $wp_query;
		$html = '';
		
		if (
			in_array($filter,$args['paginateby']) ||
			!empty($wp_query->query_vars[__($filter,'wp_theatre')])
		) {
			$get_options = $filter.'_options';
			$options = call_user_func(array($this, $get_options ));

			if (!empty($options)) {

				$filter_value = false;
				if (!empty($wp_query->query_vars[__($filter,'wp_theatre')])) {
					$filter_value = $wp_query->query_vars[__('month','wp_theatre')];
				}				

				$html.= '<nav class="wpt_event_nav_'.$filter.'">';
				if (in_array($filter,$args['paginateby'])) {
					foreach($options as $option) {
						$url = remove_query_arg(__($filter,'wp_theatre'));
						$url = add_query_arg( __($filter,'wp_theatre'), sanitize_title($option) , $url);
						$html.= '<span>';
						
						$title = date_i18n('M Y',strtotime($option));
						if (sanitize_title($option) != $filter_value) {
							$html.= '<a href="'.$url.'">'.$title.'</a>';
						} else {
							$html.= $title;
							
						}
						$html.= '</span>';
					}
				} else {
					$html.= '<span>';
					$title = date_i18n('M Y',strtotime($filter));
					$html.= $title;
					$html.= '</span>';
				}
				$html.= '</nav>';
			}

		}



		return $html;
	}
	
	public function html($args=array()) {
		return '';
	}

	function load() {
		return array();
	}
	
	/*
	 * Generate a link to the filtered results of a listing.
	 * Supports month, category and season filters.
	 */
	
	function filter_link($filter, $base=false) {
		global $wp_query;

		$defaults = array(
			'month'=>false,
			'category'=>false,
			'season'=>false
		);
		
		$filter = wp_parse_args( $filter, $defaults );
		
		foreach ($defaults as $key=>$value) {
			if ($filter[$key]) {
				$url = remove_query_arg(__($key,'wp_theatre'));
				$url = add_query_arg( __($key,'wp_theatre'), sanitize_title($option) , $url);	
			}
		}
		
		if ($filter['month']) {
			
		}
		
	}
}
?>