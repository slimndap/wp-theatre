<?php
	/*
	 * Apply filters to production fields and event fields.
	 * Usage: $wp_theatre->filter->apply( $content, $filters, $object)
	 * Usage in templates: {{title|permalink}} or {{datetime|date('j M')}} or {{datetime|date('j M')|permalink}}
	 * @since: 0.8.2
	 */

	class WPT_Filter {
		
		public $allowed_functions;
		
		function __construct() {
			$this->allowed_functions = array('permalink','date','wpautop','tickets_url');

			/**
			 * Make sure all filters are cleaned up.
			 * Necessary for unit tests to work.
			 */
			remove_all_filters('wpt_filter_date');
			remove_all_filters('wpt_filter_permalink');
			remove_all_filters('wpt_filter_wpautop');
			remove_all_filters('wpt_filter_tickets_url');

			add_filter('wpt_filter_date', array($this,'date'),10,3);
			add_filter('wpt_filter_permalink', array($this,'permalink'),10,2);
			add_filter('wpt_filter_wpautop', array($this,'wpautop'),10,1);
			add_filter('wpt_filter_tickets_url', array($this,'tickets_url'),10,2);
			
		}
		
	 	/*
	 	 * Permalink filter.
		 * Add a link (<a>) to the production detail page around the content.
		 */	 
		function permalink($content, $object) {
			if (!empty($content)) {
				$permalink_args = array(
					'html'=>true,
					'text'=> $content,
					'inside'=>true
				);
				if (method_exists($object, 'permalink')) {
					$content = $object->permalink($permalink_args);
				}
			}
			return $content;		
		}
				
		/**
		 * Date filter.
		 * Format the content using the date format defined in the third argument.
		 *
		 * @since 0.8.2
		 * @since 0.10.16	Now accounts for timezones.
		 *					Fixes #117.
		 */
		function date($content, $object, $format='') {
			if (!empty($format)) {	
				if (is_numeric($content)) {
					$timestamp = $content;
				} else {
					$timestamp = strtotime($content);								
				}
				$content = date_i18n(
					$format,
					$timestamp + get_option('gmt_offset') * HOUR_IN_SECONDS
				);
			}
			return $content;
		}
		
	 	/*
	 	 * Wpautop filter.
		 * Changes double line-breaks in the content into HTML paragraphs (<p>...</p>).
		 */
		function wpautop($content) {
			return wpautop($content);
		}
		
		/**
	 	 * Tickets URL filter.
		 * Add a link (<a>) to the tickets URL around the content.
		 */	 
		function tickets_url($content, $object) {
			if (!empty($content)) {
				$tickets_url_args = array(
					'html'=>true,
					'text'=> $content
				);
				if (method_exists($object, 'tickets_url')) {
					$tickets_url_content = $object->tickets_url($tickets_url_args);	
					if (!empty($tickets_url_content)) {
						$content = $tickets_url_content;
					}
				}
			}
			return $content;				
		}
		
		/*
		 * Apply the filters.
		 *
		 * @param $content string                       The content to apply the filters on.
		 * @param $filters array                        The filters to apply to the content.
		 *                                              example: array( 'permalink', 'date("j M")')
		 * @param $object  WPT_Production or WPT_Event
		 * @return $content string
		 */
		 
		function apply($content, $filters, $object) {
			$this->object = $object;
			foreach($filters as $filter) {
				$function = $this->get_function($filter);
				$arguments = $this->get_arguments($filter);
				if ($this->is_valid($function, $arguments, $object)) {
					array_unshift($arguments, $content, $object);
					$content = apply_filters_ref_array('wpt_filter_'.$function,$arguments);
				}				
			}

			return $content;
			
		}
		
		function get_functions($filters) {
			$functions = array();
			foreach($filters as $filter) {
				$functions[] = $this->get_function($filter);
			}
			return $functions;
		}
		
		/*
		 * Extract the function name from a filter.
		 */
		
		function get_function($filter) {
			$brackets_open = strpos($filter, '(');
			$brackets_close = strpos($filter, ')');
			if (
				$brackets_open !== false && 
				$brackets_close !== false &&
				$brackets_open < $brackets_close
			) {
				return trim(substr($filter, 0, $brackets_open));
			} else {
				return trim($filter);
			}
		}
		
		/*
		 * Extract the arguments from a filter.
		 */
		function get_arguments($filter) {
			$arguments = array();
		
			$brackets_open = strpos($filter, '(');
			$brackets_close = strpos($filter, ')');
			if (
				$brackets_open !== false && 
				$brackets_close !== false &&
				$brackets_open < $brackets_close
			) {
				$arguments = explode(',',substr($filter, $brackets_open + 1, $brackets_close - $brackets_open -1));
				$arguments = $this->sanitize_arguments($arguments);
			}
			return $arguments;
		}
		
		/*
		 * Sanitize arguments.
		 * Removed surrounding quotes.
		 * @param $arguments array 
		 */
		function sanitize_arguments($arguments) {
			if (!empty($arguments) && is_array($arguments)) {
				for ($i=0;$i<count($arguments);$i++) {
					$arguments[$i] = trim($arguments[$i],'"');
					$arguments[$i] = trim($arguments[$i],"'");
				}
			}
			return $arguments;
		}
		
		/*
		 * Check if the function and the arguments are valid.
		 */
		function is_valid($function,$arguments,$object) {
			return 
				!empty($function) && 
				in_array($function, $this->allowed_functions) &&
				is_array($arguments);
		}
	}