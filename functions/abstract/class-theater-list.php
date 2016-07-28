<?php

/**
 * Abstract Theater Lists class.
 * 
 * @abstract
 * @package	Theater/Abstracts
 */
abstract class Theater_List {
	
	protected $default_args = array();
	
	/**
	 * Default arguments for all HTML methods.
	 *
	 * @since 0.10
	 *
	 * @var 	array
	 * @access 	protected
	 */
	protected $default_args_for_html = array(
		'groupby' => false,
		'paginateby' => array(),
		'template' => null,
	);
	
	/**
	 * Sets the default filter arguments for a list.
	 * 
	 * @since	0.16
	 * @param 	array[string]	$default_args 	Optional. Custom default filter arguments for this list.
	 * @return 	void
	 * @uses	Theater_Dates::$default_args to merge the built-in filter argument defaults with custom filter argument defaults.
	 */
	function __construct ( $default_args = array() ) {
		if (!empty( $default_args )) {
			$this->default_args = wp_parse_args($default_args, $this->default_args);
		}
		
	}
	
	/**
	 * Retrieves a list of items if the class is called as a function.
	 *
	 * @uses 	Theater_Lists::get() to return an array of event dates.
	 *
	 * @since	0.16
	 * @return 	Theater_Item[]	An array of items.
	 */
	function __invoke() {
		return (array) $this->get();
	}
	
	/**
	 * Gets a fully formatted list of items in HTML if this class is treated like a string.
	 *
	 * @uses 	Theater_Lists::get_html() to return a formatted HTML list of items.
	 *
	 * @since	0.16
	 * @return 	string	Formatted HTML list of items.
	 */
	function __toString() {
		return $this->get_html();
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
	static function add_query_vars( $vars ) {
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
	 * @since 0.10.10	XSS Vulnerability fix. See:
	 *					https://make.wordpress.org/plugins/2015/04/20/fixing-add_query_arg-and-remove_query_arg-usage/
	 *
	 * @access protected
	 */

	protected function filter_pagination( $field, $options, $args = array() ) {
		global $wp_query;

		$args = wp_parse_args( $args, $this->default_args_for_html );

		$html = '';

		$query_var = 'wpt_'.$field;
		$paginate = in_array( $field,$args['paginateby'] );

		/**
		 * Bail if:
		 * - pagination is not active for this $field and
		 * - no query_var is set for this $field.
		 */

		if (
			! $paginate &&
			empty( $wp_query->query_vars[ $query_var ] )
		) {
			return $html;
		};

		/*
		 * Build the base url for all filters
		 */
		$current_url = $_SERVER['REQUEST_URI'];

		if ( ! empty( $args[ $field ] ) ) {
			$current_url = add_query_arg( $query_var,$args[ $field ],$current_url );
		}

		foreach ( $options as $slug => $name ) {

			$url = remove_query_arg( $query_var, $current_url );
			$classes = array(
				'wpt_listing_filter',
				$field.'-'.$slug,
			);

			/**
			 * Check if $option is the current tab.
			 */

			$is_current_page = false;
			if (
				isset( $wp_query->query_vars[ $query_var ] ) &&
				$slug == $wp_query->query_vars[ $query_var ]
			) {
				$is_current_page = true;
			}

			if ( $is_current_page ) {
				$classes[] = 'wpt_listing_filter_active';
			} else {
				if ( ! $paginate ) {
					continue;
				}
				$url = add_query_arg( $query_var, $slug , $url );
			}

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
			$name = apply_filters( 'wpt_listing_filter_pagination_option_name', $name, $field, $slug );
			$name = apply_filters( 'wpt_listing_filter_pagination_'.$field.'_option_name', $name, $slug );

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
			$url = apply_filters( 'wpt_listing_filter_pagination_option_url', $url, $field, $name, $slug );
			$url = apply_filters( 'wpt_listing_filter_pagination_'.$field.'_option_url', $url, $name, $slug );

			/**
			 * @deprecated 0.10.10
			 * Use 'wpt_listing_filter_pagination_option_url'.
			 */
			$url = apply_filters( 'wpt_listing_filter_pagination_url', $url );

			$option_html = '<span class="'.implode( ' ',$classes ).'"><a href="'.htmlentities( $url ).'">'.$name.'</a></span> ';

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
			$option_html = apply_filters( 'wpt_listing_filter_pagination_option_html', $option_html, $field, $name, $slug );
			$option_html = apply_filters( 'wpt_listing_filter_pagination_'.$field.'_option_html', $option_html, $name, $slug );

			$html .= $option_html;

		}

		/**
		 * Filter the HTML of the navigation for a listing filter.
		 *
		 * @since	0.13.3
		 * @since	0.13.4	Added the listing object to the filter variables.
		 * @since	0.16	Removed listing object from the filter params.
		 *
		 * @param	string	$html		The HTML of the navigation for a listing filter.
		 * @param	string	$field		The field being filtered.
		 *								Eg. 'month' or 'category'.
		 * @param	array	$options	The possible values for the filter.
		 * @param	array	$args		The arguments used for the listing.
		 */
		$html = apply_filters( 'wpt/listing/pagination/filter/html',$html, $field, $options, $args );
		$html = apply_filters( 'wpt/listing/pagination/filter/html/field='.$field, $html, $options, $args );

		return '<div class="wpt_listing_filter_pagination '.$field.'">'.$html.'</div>';

	}

	/**
	 * Gets the classes of a listing.
	 *
	 * @since	0.?
	 * @since	0.14.7	Added a new 'wpt/listing/classes' filter.
	 *					Deprecated the 'wpt_listing_classes' filter.
	 * @param 	array 	$args 	The listing args.
	 * @return	array			The classes of a listing.
	 */
	protected function get_classes_for_html( $args = array() ) {

		$classes = array( 'wpt_listing' );

		/**
		 * Filters the classes of a listing
		 *
		 * @since	0.14.7
		 * @param	array	$classes	The classes of a listing.
		 * @param	array	$args		The listing args.
		 */
		$classes = apply_filters( 'wpt/listing/classes',$classes, $args );

		/**
		 * @deprecated	0.14.7
		 */
		$classes = apply_filters( 'wpt_listing_classes',$classes );

		return $classes;
	}

	/**
	 * Gets a list in HTML.
	 *
	 * @since 	0.10
	 * @since	0.15.2	Added actions directly before and after the HTML is generated.
	 *					Used by WPT_Context() to give set the context for a listing.
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
	 *		@type string    $groupby    Field to group the listing by.
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
	protected function get_html( $args = array() ) {

		ob_start();
	
		/*
		 * Runs before the listing HTML is generated.
		 *
		 * @since	0.15.2
		 * @param	array	$args	The listing arguments.
		 */
		do_action('wpt/listing/html/before', $args);

		$html_page_navigation = $this->get_html_page_navigation( $args );
		$html_for_page = $this->get_html_for_page( $args );

		if ( ! empty( $html_page_navigation ) || ! empty( $html_for_page ) ) {
			?><div class="<?php echo implode( ' ',$this->get_classes_for_html( $args ) ); ?>"><?php
				echo $html_page_navigation.$html_for_page; 
			?></div><?php
		}

		/*
		 * Runs after the listing HTML is generated.
		 *
		 * @since	0.15.2
		 * @param	array	$args	The listing arguments.
		 */
		do_action('wpt/listing/html/after', $args);
		
		$html = ob_get_clean();
		$html = apply_filters( 'wpt_listing_html', $html, $args );

		return $html;
	}

	/**
	 * Gets the pagination filters for a listing.
	 *
	 * @since	0.13.4
	 * @return 	array	The pagination filters for a listing.
	 */
	protected function get_pagination_filters() {

		/**
		 * Filter the pagination filters for a listing.
		 *
		 * @since	0.13.4
		 * @param	array	The current pagination filters for a listing.
		 */
		$filters = apply_filters( 'wpt/listing/pagination/filters', array() );

		return $filters;
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
	 * @param 	array $args     The arguments being used for the event listing.
	 *							See WPT_Listing::get_html() for possible values.
	 * @return 	string			The HTML for the page navigation.
	 */
	protected function get_html_page_navigation( $args = array() ) {
		
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
	protected function get_html_for_page( $args = array() ) {
		
	}

	/**
	 * Gets a list of events.
	 *
	 * @since 0.8
	 *
	 * @return array An array of WPT_Event objects.
	 */
	function get($filters = array()) {
		
	}
}