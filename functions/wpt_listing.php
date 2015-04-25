<?php
/*
 * Manages listings.
 *
 * Extend this class to compile lists or fully formatted HTML listings.
 * Don't use it directly.
 *
 * @since 0.8
 * @since 0.10	Major rewrite, while maintaining backwards compatibility.
 */
 
class WPT_Listing {

	/**
	 * Default arguments for all HTML methods.
	 * 
	 * @since 0.10
	 *
	 * @var array
	 * @access protected
	 */
	protected $default_args_for_html = array (
		'groupby'=>false,
		'paginateby' => array(),
		'template' => NULL,					
	);

	function __construct() {
		add_filter( 'query_vars', array($this,'add_query_vars'));	 
	}

	/**
	 * Adds the page selectors to the public query vars.
	 *
	 * This is needed to make `$wp_query->query_vars['wpt_category']` work.
	 * Override this method to add your own page selectors.
	 *
	 * @since 0.10
	 *
	 * @param array $vars	The current public query vars.
	 * @return array		The new public query vars.
	 */
	public function add_query_vars($vars) {
		return $vars;
	}

	/*
	 * Generate navigation for a listing filter.
	 *
	 * @see WPT_Productions::get_html()
	 * @see WPT_Events::get_html()
	 *
	 * @since 0.8
	 * @since 0.10.4	Added an extra class to the filter tabs.
	 *					The class is based on $field and the tab.
	 *					Useful for styling and automated tests.
	 *
	 * @access protected
	 */

	protected function filter_pagination($field, $options, $args=array()) {
		global $wp_query;

		$args = wp_parse_args($args, $this->default_args_for_html );

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
			$classes = array(
				'wpt_listing_filter',
				$field.'-'.$slug,
			);

			/**
			 * Check if $option is the current tab.
			 */

			$is_current_page = false;
			if (
				isset($wp_query->query_vars[$query_var]) &&
				$slug == $wp_query->query_vars[$query_var]
			) {
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
			
			/**
			 * Filter the name of an option in the navigation for a listing filter.
			 *
			 * @since 0.10.10
			 * 
			 * @param	string 	$name	The name of the option.
			 * @param	string	$field	The field being filtered.
			 *							Eg. 'month' or 'category'.
			 * @param	string	$slug	The slug op the option.
			 */
			$name = apply_filters( 'wpt_listing_filter_pagination_option_name', $name, $field, $slug);
			$name = apply_filters( 'wpt_listing_filter_pagination_'.$field.'_option_name', $name, $slug);
			
			/**
			 * Filter the url of an option in the navigation for a listing filter.
			 *
			 * @since 0.10.10
			 * 
			 * @param	string 	$url	The url of the option.
			 * @param	string	$field	The field being filtered.
			 *							Eg. 'month' or 'category'.
			 * @param	string 	$name	The name of the option.
			 * @param	string	$slug	The slug op the option.
			 */
			$url = apply_filters( 'wpt_listing_filter_pagination_option_url', $url, $field, $name, $slug);
			$url = apply_filters( 'wpt_listing_filter_pagination_'.$field.'_option_url', $url, $name, $slug);

			/**
			 * @deprecated 0.10.10
			 * Use 'wpt_listing_filter_pagination_option_url'.
			 */
			$url = apply_filters('wpt_listing_filter_pagination_url', $url);

			$option_html = '<span class="'.implode(' ',$classes).'"><a href="'.htmlentities($url).'">'.$name.'</a></span> ';

			/**
			 * Filter the html of an option in the navigation for a listing filter.
			 *
			 * @since 0.10.10
			 * 
			 * @param	string 	$html	The html of the option.
			 * @param	string	$field	The field being filtered.
			 *							Eg. 'month' or 'category'.
			 * @param	string 	$name	The name of the option.
			 * @param	string	$slug	The slug op the option.
			 */
			$option_html = apply_filters( 'wpt_listing_filter_pagination_option_html', $option_html, $field, $name, $slug);
			$option_html = apply_filters( 'wpt_listing_filter_pagination_'.$field.'_option_html', $option_html, $name, $slug);
			
			$html.= $option_html;
		}

		return '<div class="wpt_listing_filter_pagination '.$field.'">'.$html.'</div>';

	}

	protected function get_classes_for_html($args=array()) {
		return apply_filters('wpt_events_classes',array('wpt_listing'));
	}
	
	/**
	 * Gets a list in HTML.
	 * 
	 * @since 0.10	
	 *
	 * @see WPT_Listing::get_html_pagination()
	 * @see WPT_Listing::get_html_for_page()
	 *
	 * @access protected
	 * @param array $args {
	 * 		An array of arguments. Optional.
	 *
	 *		These can be any of the arguments used in the $filters of WPT_Listing::load(), plus:
	 *
	 *		@type string	$groupby 	Field to group the listing by. 
	 *									@see WPT_Listing::get_html_grouped() for possible values.
	 *									Default <false>.
	 *		@type array		$paginateby	Fields to paginate the listing by.
	 *									@see WPT_Listing::get_html_pagination() for possible values.
	 *									Default <[]>.
	 * 		@type string	$template	Template to use for the individual list items.
	 *									Default <NULL>.
	 * }
 	 * @return string HTML.
	 */
	protected function get_html($args=array()) {
		$html = '';
		
		$html_page_navigation = $this->get_html_page_navigation($args);
		$html_for_page = $this->get_html_for_page($args);
		
		if (!empty($html_page_navigation) || !empty($html_for_page)) {
			$html = '<div class="'.implode(' ',$this->get_classes_for_html($args)).'">'.$html_page_navigation.$html_for_page.'</div>';
		}
		
		return apply_filters('wpt_listing_html', $html, $args);
	}
	
	/**
	 * Gets the page navigation for a listing in HTML.
	 *
	 * Override this method to create your own page navigation
	 * using WPT_Listing::filter_pagination() helper method.
	 *
	 * @see WPT_Listing::filter_pagination()
	 *
	 * @since 0.10
	 * 
	 * @access 	protected
	 * @param 	array $args 	The arguments being used for the event listing. 
	 *							See WPT_Listing::get_html() for possible values.
	 * @return 	string			The HTML for the page navigation.
	 */
	protected function get_html_page_navigation($args=array()) {
		return '';
	}

	/**
	 * Gets a list of events in HTML for a page.
	 * 
	 * Override this method to assemble your own page content.
	 *
	 * @since 0.10
	 *
	 * @access protected
	 * @param 	array $args 	See WPT_Listing::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	protected function get_html_for_page($args=array()) {
		return '';
	}
	
	/**
	 * Gets a list of events.
	 * 
	 * @since 0.8
	 *
 	 * @return array An array of WPT_Event objects.
	 */
	public function get() {
		return array();
	}

	/**
	 * @deprecated 0.10
	 * @see WPT_Listing::get_html()
	 */
	public function html($args=array()) {
		_deprecated_function('WPT_Listing::html()', '0.10', 'WPT_Listing::get_html()');
		return $this->get_html($args);
	}
	
	/**
	 * @deprecated 0.10
	 * @see WPT_Listing::get()
	 */
	public function load($filters=array()) {
		_deprecated_function('WPT_Listing::load()', '0.10', 'WPT_Listing::get()');
		return $this->get($filters);
	}
}
?>