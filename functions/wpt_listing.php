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
	
	function get_filter_navigation($filter,$args) {
		global $wp_query;
		$html = '';
		
		if (
			in_array($filter,$args['paginateby']) ||
			!empty($wp_query->query_vars[__($filter,'wp_theatre')])
		) {
			$get_options = 'get_'.$filter.'_options';
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
}
?>