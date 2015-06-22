<?php

/*
 * Manages event listings.
 *
 * Uses this class to compile lists of events or fully formatted HTML listings of events.
 *
 * @since 0.5
 * @since 0.10	Complete rewrite, while maintaining backwards compatibility.
 */
 
class WPT_Events extends WPT_Listing {
	
	/**
	 * Adds the page selectors for seasons and categories to the public query vars.
	 * 
	 * This is needed to make `$wp_query->query_vars['wpt_category']` work.
	 *
	 * @since 0.10
	 *
	 * @param array $vars	The current public query vars.
	 * @return array		The new public query vars.
	 */
	public function add_query_vars($vars) {
		$vars[] = 'wpt_day';
		$vars[] = 'wpt_month';
		$vars[] = 'wpt_year';
		$vars[] = 'wpt_category';
		return $vars;
	}

	/**
	 * Gets an array of all categories events.
	 *
	 * @since 0.5
	 * @since 0.10		Renamed method from `categories()` to `get_categories()`.
 	 * @since 0.10.2	Now returns the slug instead of the term_id as the array keys.
 	 * @since 0.10.14	Significally decreased the number of queries used.
	 *
	 * @param 	array $filters	See WPT_Events::get() for possible values.
	 * @return 	array 			Categories.
	 */
	function get_categories($filters=array()) {
		$filters['category'] = false;
		$events = $this->get($filters);	
		$event_ids = wp_list_pluck($events, 'ID');
		$terms = wp_get_object_terms($event_ids, 'category');
		$categories = array();

		foreach ($terms as $term) {
			$categories[$term->slug] = $term->name;
		}
		
		asort($categories);
		return $categories;	
	}
		
	/**
	 * Gets the CSS classes for an event listing.
	 *
	 * @see WPT_Listing::get_classes_for_html()
	 *
	 * @since 0.10
	 * 
	 * @access 	protected
	 * @param 	array $args 	See WPT_Events::get_html() for possible values. Default: array().
	 * @return 	array 			The CSS classes.
	 */
	protected function get_classes_for_html($args=array()) {

		// Start with the default classes for listings.
		$classes = parent::get_classes_for_html();

		$classes[] = 'wpt_events';
		
		// Thumbnail
		if (!empty($args['template']) && strpos($args['template'],'{{thumbnail}}')===false) { 
			$classes[] = 'wpt_events_without_thumbnail';
		}
			
		/**
		 * Filter the CSS classes.
		 * 
		 * @since 0.10
		 *
		 * @param 	array $classes 	The CSS classesSee WPT_Events::get_html() for possible values.
		 * @param 	array $args 	The $args that are being used for the listing.
		 */
		$classes = apply_filters('wpt_events_classes', $classes, $args);
		
		return $classes;
	}
	
	/**
	 * Gets an array of all days with events.
	 *
	 * @since 0.8
	 * @since 0.10				No longer limits the output to days with upcoming events.
	 *							See: https://github.com/slimndap/wp-theatre/issues/75
	 * 							Renamed method from `days()` to `get_days()`.
	 * @since 0.10.1			Removed custom sorting. Rely on the sorting order of the events instead.
	 * @since 0.10.6			Added custom sorting again. 
	 *							Can't rely on sorting order of events, because historic events have no
	 *							`_wpt_order` set.
	 *
	 * @param 	array $filters	See WPT_Events::get() for possible values.
	 * @return 	array 			Days.
	 */
	function get_days($filters=array()) {
		$events = $this->get($filters);		
		$days = array();
		foreach ($events as $event) {
			$days[ date('Y-m-d',$event->datetime() + get_option('gmt_offset') * HOUR_IN_SECONDS) ] = date_i18n('D j M',$event->datetime() + get_option('gmt_offset') * HOUR_IN_SECONDS);
		}

		if (!empty($filters['order']) && 'desc'==$filters['order']) {
			krsort($days);
		} else {
			ksort($days);		
		}
		
		return $days;
	}

	/**
	 * Gets a fully formatted listing of events in HTML.
	 *
	 * The list of events is compiled using filter-arguments that are part of $args.
	 * See WPT_Events::get() for possible values.
	 *
	 * The events can be shown on a single page or be cut up into multiple pages by setting
	 * $paginateby. If $paginateby is set then a page navigation is added to the top of
	 * the listing.
	 *
	 * The events can be grouped inside the pages by setting $groupby.
	 * 
	 * @since 0.5
	 * @since 0.10	Moved parts of this method to seperate reusable methods.
	 *				Renamed method from `html()` to `get_html()`.
	 *				Rewrote documentation.
	 *
	 * @see WPT_Listing::get_html()
	 * @see WPT_Events::get_html_pagination()
	 * @see WPT_Events::get_html_for_page()
	 *
	 * @param array $args {
	 * 		An array of arguments. Optional.
	 *
	 *		These can be any of the arguments used in the $filters of WPT_Events::get(), plus:
	 *
	 *		@type array		$paginateby	Fields to paginate the listing by.
	 *									@see WPT_Events::get_html_pagination() for possible values.
	 *									Default <[]>.
	 *		@type string	$groupby 	Field to group the listing by. 
	 *									@see WPT_Events::get_html_grouped() for possible values.
	 *									Default <false>.
	 * 		@type string	$template	Template to use for the individual events.
	 *									Default <NULL>.
	 * }
 	 * @return string HTML.
	 */
	public function get_html($args=array()) {
		
		$html = parent::get_html($args);
		
		/**
		 * Filter the formatted listing of events in HTML.
		 * 
		 * @since 0.10
		 *
		 * @param 	string $html 	The HTML.
		 * @param 	array $args 	The $args that are being used for the listing.
		 */
		$html = apply_filters('wpt_events_html', $html, $args);
		
		return  $html;
	}
	
	/**
	 * Gets a list of events in HTML for a single category.
	 * 
	 * @since 0.10
 	 * @since 0.10.2	Category now uses slug instead of term_id.
	 *
	 * @see WPT_Events::get_html_grouped();
	 *
	 * @access private
	 * @param 	string $category_slug		Slug of the category.
	 * @param 	array $args 				See WPT_Events::get_html() for possible values.
	 * @return 	string						The HTML.
	 */
	private function get_html_for_category($category_slug, $args=array()) {
		if ($category = get_category_by_slug($category_slug)) {
  			$args['cat'] = $category->term_id;				
		}
		
		return $this->get_html_grouped($args);
	}
	
	/**
	 * Gets a list of events in HTML for a single day.
	 * 
	 * @since 0.10
	 *
	 * @see WPT_Events::get_html_grouped();
	 *
	 * @access private
	 * @param 	string $day		The day in `YYYY-MM-DD` format.
	 * @param 	array $args 	See WPT_Events::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	private function get_html_for_day($day, $args=array()) {
		
		/*
		 * Set the `start`-filter to today.
		 * Except when the active `start`-filter is set to a later date.
		 */
		if (
			empty($args['start']) ||
			(strtotime($args['start']) < strtotime($day))
		) {
			$args['start'] = $day;			
		}
		
		/*
		 * Set the `end`-filter to the next day.
		 * Except when the active `end`-filter is set to an earlier date.
		 */		 
		if (
			empty($args['end']) ||
			(strtotime($args['end']) > strtotime($day.' +1 day'))
		) {
			$args['end'] = $day.' +1 day';			
		}
		
		return $this->get_html_grouped($args);
	}
	
	/**
	 * Gets a list of events in HTML for a single month.
	 * 
	 * @since 0.10
	 *
	 * @see WPT_Events::get_html_grouped();
	 *
	 * @access private
	 * @param 	string $day		The month in `YYYY-MM` format.
	 * @param 	array $args 	See WPT_Events::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	private function get_html_for_month($month, $args=array()) {
				
		/*
		 * Set the `start`-filter to the first day of the month.
		 * Except when the active `start`-filter is set to a later date.
		 */
		if (
			empty($args['start']) ||
			(strtotime($args['start']) < strtotime($month))
		) {
			$args['start'] = $month;			
		}
		
		/*
		 * Set the `end`-filter to the first day of the next month.
		 * Except when the active `end`-filter is set to an earlier date.
		 */		 
		if (
			empty($args['end']) ||
			(strtotime($args['end']) > strtotime($month.' +1 month'))
		) {
			$args['end'] = $month.' +1 month';			
		}
		
		return $this->get_html_grouped($args);
	}

	/**
	 * Gets a list of events in HTML for a single year.
	 * 
	 * @since 0.10
	 *
	 * @see WPT_Events::get_html_grouped();
	 *
	 * @access private
	 * @param 	string $day		The year in `YYYY` format.
	 * @param 	array $args 	See WPT_Events::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	private function get_html_for_year($year, $args=array()) {
				
		/*
		 * Set the `start`-filter to the first day of the year.
		 * Except when the active `start`-filter is set to a later date.
		 */
		if (
			empty($args['start']) ||
			(strtotime($args['start']) < strtotime($year.'-01-01'))
		) {
			$args['start'] = $year.'-01-01';			
		}
		
		/*
		 * Set the `end`-filter to the first day of the next year.
		 * Except when the active `end`-filter is set to an earlier date.
		 */		 
		if (
			empty($args['end']) ||
			(strtotime($args['end']) > strtotime($year.'-01-01 +1 year'))
		) {
			$args['end'] = $year.'-01-01 +1 year';			
		}

		return $this->get_html_grouped($args);
	}

	/**
	 * Gets a list of events in HTML for a page.
	 * 
	 * @since 0.10
	 *
	 * @see WPT_Events::get_html_grouped()
	 * @see WPT_Events::get_html_for_year()
	 * @see WPT_Events::get_html_for_month()
	 * @see WPT_Events::get_html_for_day()
	 * @see WPT_Events::get_html_for_category()
	 *
	 * @access protected
	 * @param 	array $args 	See WPT_Events::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	protected function get_html_for_page($args=array()) {
		global $wp_query;
		
		/*
		 * Check if the user used the page navigation to select a particular page.
		 * Then revert to the corresponding WPT_Events::get_html_for_* method.
		 * @see WPT_Events::get_html_page_navigation().
		 */
		
		if (!empty($wp_query->query_vars['wpt_year']))
			return $this->get_html_for_year($wp_query->query_vars['wpt_year'], $args);
			
		if (!empty($wp_query->query_vars['wpt_month']))
			return $this->get_html_for_month($wp_query->query_vars['wpt_month'], $args);
			
		if (!empty($wp_query->query_vars['wpt_day']))
			return $this->get_html_for_day($wp_query->query_vars['wpt_day'], $args);			
			
		if (!empty($wp_query->query_vars['wpt_category'])) 
			return $this->get_html_for_category($wp_query->query_vars['wpt_category'], $args);
			
		/*
		 * The user didn't select a page.
		 * Show the full listing.
		 */
		return $this->get_html_grouped($args);
	}

	/**
	 * Gets a list of events in HTML.
	 * 
	 * The events can be grouped inside a page by setting $groupby.
	 * If $groupby is not set then all events are show in a single, ungrouped list.
	 *
	 * @since 0.10
	 *
	 * @see WPT_Event::html();
	 * @see WPT_Events::get_html_for_month();
	 * @see WPT_Events::get_html_for_day();
	 * @see WPT_Events::get_html_for_category();
	 *
	 * @access protected
	 * @param 	array $args 	See WPT_Events::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	private function get_html_grouped($args=array()) {

		$args = wp_parse_args($args, $this->default_args_for_html);
		
		/*
		 * Get the `groupby` setting and remove it from $args.
		 * $args can now be passed on to any of the other `get_html_*`-methods safely
		 * without the risk of creating grouped listings within grouped listings.
		 */
		$groupby = $args['groupby'];
		$args['groupby'] = false;

		$html = '';
		switch ($groupby) {
			case 'day':
				$days = $this->get_days($args);
				foreach($days as $day=>$name) {
					if ($day_html = $this->get_html_for_day($day, $args)) {
						$html.= '<h3 class="wpt_listing_group day">';
						$html.= apply_filters('wpt_listing_group_day',date_i18n('l d F',strtotime($day)),$day);
						$html.= '</h3>';
						$html.= $day_html;
					}
				}
				break;					
			case 'month':
				$months = $this->get_months($args);
				foreach($months as $month=>$name) {
					if ($month_html = $this->get_html_for_month($month, $args)) {
						$html.= '<h3 class="wpt_listing_group month">';
						$html.= apply_filters('wpt_listing_group_month',date_i18n('F',strtotime($month)),$month);
						$html.= '</h3>';
						$html.= $month_html;
					}
				}
				break;					
			case 'year':
				$years = $this->get_years($args);
				foreach($years as $year=>$name) {
					if ($year_html = $this->get_html_for_year($year, $args)) {
						$html.= '<h3 class="wpt_listing_group year">';
						$html.= apply_filters('wpt_listing_group_year',date_i18n('Y',strtotime($year.'-01-01')),$year);
						$html.= '</h3>';
						$html.= $year_html;
					}
				}
				break;					
			case 'category':
				$categories = $this->get_categories($args);
				foreach($categories as $cat_id=>$name) {
					if ($cat_html = $this->get_html_for_category($cat_id, $args)) {
						$html.= '<h3 class="wpt_listing_group category">';
						$html.= apply_filters('wpt_listing_group_category',$name,$cat_id);
						$html.= '</h3>';
						$html.= $cat_html;						
					}
				}
				break;					
			default:
				$events = $this->get($args);
				$events = $this->preload_events_with_productions($events);
				foreach ($events as $event) {
					$event_args = array();
					if (!empty($args['template'])) {
						$event_args = array('template'=>$args['template']);
					}
					$html.= $event->html($event_args);
				}					
		}
		return $html;
	}

	/**
	 * Gets the page navigation for an event listing in HTML.
	 *
	 * @see WPT_Listing::filter_pagination()
	 * @see WPT_Events::get_days()
	 * @see WPT_Events::get_months()
	 * @see WPT_Events::get_categories()
	 *
	 * @since 0.10
	 * 
	 * @access protected
	 * @param 	array $args 	The arguments being used for the event listing. 
	 *							See WPT_Events::get_html() for possible values.
	 * @return 	string			The HTML for the page navigation.
	 */
	protected function get_html_page_navigation($args=array()) {
		global $wp_query;
		
		$html = '';
		
		// Days navigation		
		if (
			(!empty($args['paginateby']) && in_array('day', $args['paginateby'])) || 
			!empty($wp_query->query_vars['wpt_day'])
		) {
			$html.= $this->filter_pagination('day', $this->get_days($args), $args);
		}

		// Months navigation
		if (
			(!empty($args['paginateby']) && in_array('month', $args['paginateby'])) || 
			!empty($wp_query->query_vars['wpt_month'])
		) {
			$html.= $this->filter_pagination('month', $this->get_months($args), $args);		
		}

		// Years navigation
		if (
			(!empty($args['paginateby']) && in_array('year', $args['paginateby'])) || 
			!empty($wp_query->query_vars['wpt_year'])
		) {
			$html.= $this->filter_pagination('year', $this->get_years($args), $args);
		}

		// Categories navigation
		if (
			(!empty($args['paginateby']) && in_array('category', $args['paginateby'])) || 
			!empty($wp_query->query_vars['wpt_category'])
		) {
			$html.= $this->filter_pagination('category', $this->get_categories($args), $args);
		}
		
		return $html;		
	}

	/**
	 * Gets all months that have events.
	 *
	 * @since 0.5
	 * @since 0.10				No longer limits the output to months with upcoming events.
	 *							See: https://github.com/slimndap/wp-theatre/issues/75
	 * 							Renamed method from `months()` to `get_months()`.
	 * @since 0.10.1			Removed custom sorting. Rely on the sorting order of the events instead.
	 * @since 0.10.6			Added custom sorting again. 
	 *							Can't rely on sorting order of events, because historic events have no
	 *							`_wpt_order` set.
	 *
	 * @param array $filters	See WPT_Events::get() for possible values.
	 * @return array 			Months.
	 */
	function get_months($filters=array()) {
		$events = $this->get($filters);
		$months = array();
		foreach ($events as $event) {
			$months[ date('Y-m',$event->datetime() + get_option('gmt_offset') * HOUR_IN_SECONDS) ] = date_i18n('M Y',$event->datetime() + get_option('gmt_offset') * HOUR_IN_SECONDS);
		}
		
		if (!empty($filters['order']) && 'desc'==$filters['order']) {
			krsort($months);
		} else {
			ksort($months);		
		}
		
		return $months;
	}
	
	/**
	 * Gets all years that have events.
	 *
	 * @since 0.10
	 * @since 0.10.1			Removed custom sorting. Rely on the sorting order of the events instead.
	 * @since 0.10.6			Added custom sorting again. 
	 *							Can't rely on sorting order of events, because historic events have no
	 *							`_wpt_order` set.
	 *
	 * @param 	array $filters	See WPT_Events::get() for possible values.
	 * @return 	array 			Years.
	 */
	function get_years($filters=array()) {
		$events = $this->get($filters);
		$years = array();
		foreach ($events as $event) {
			$years[date('Y',$event->datetime() + get_option('gmt_offset') * HOUR_IN_SECONDS)] = date_i18n('Y',$event->datetime() + get_option('gmt_offset') * HOUR_IN_SECONDS);
		}

		if (!empty($filters['order']) && 'desc'==$filters['order']) {
			krsort($years);
		} else {
			ksort($years);		
		}
		
		return $years;
	}
	
	/* 
	 * Get the last event.
	 *
	 * @since 0.8
	 */
	
	function last() {
		$args = array(
			'post_type' => WPT_Event::post_type_name,
			'post_status' => 'publish',
			'order' => 'desc',
			'posts_per_page' => 1
		);
		
		$events = get_posts($args);
		
		if (empty($events)) {
			return false;
		} else {
			return new WPT_Event($events[0]);
		}
	}
	
	/**
	 * Gets a list of events.
	 * 
	 * @since 0.5
	 * @since 0.10		Renamed method from `load()` to `get()`.
	 * 					Added 'order' to $args.
	 * @since 0.10.14	Preload events with their productions.
	 *					This dramatically decreases the number of queries needed to show a listing of events.
	 * @since 0.10.15	'Start' and 'end' $args now account for timezones.
	 *					Fixes #117.
	 *
 	 * @return array Events.
	 */
	 
	public function get($filters=array()) {
		global $wp_theatre;

		$defaults = array(
			'order' => 'asc',
			'limit' => false,
			'post__in' => false,
			'post__not_in' => false,
			'upcoming' => false,
			'past' => false,
			'start' => false,
			'end' => false,
			'cat' => false,
			'category_name' => false,
			'category__and' => false,
			'category__in' => false,
			'category__not_in' => false,
			'season' => false,
			'production' => false,
			'status' => array('publish'),
		);
		$filters = wp_parse_args( $filters, $defaults );

		$args = array(
			'post_type' => WPT_Event::post_type_name,
			'post_status' => $filters['status'],
			'meta_query' => array(),
			'order' => $filters['order'],
		);
		
		/**
		 * Apply upcoming filter.
		 * Ignore when one of the other time related filters are set.
		 * Maybe deprecate in favor of `start="now"`.
		 */
		
		if (
			$filters['upcoming'] &&
			!$filters['start'] &&
			!$filters['end']
		) {
			$filters['start'] = 'now';
		}

		if ($filters['production']) {
			$args['meta_query'][] = array (
				'key' => WPT_Production::post_type_name,
				'value' => $filters['production'],
				'compare' => '=',
			);
		}
		
		/**
		 * Apply start filter.
		 * Only show events that start after the `start` value.
		 * Can be any value that is supported by strtotime().
		 * @since 0.9
		 */
		
		if ($filters['start']) {
			$args['meta_query'][] = array (
				'key' => $wp_theatre->order->meta_key,
				'value' => strtotime($filters['start'], current_time( 'timestamp' )) - get_option('gmt_offset') * 3600,
				'compare' => '>='
			);
		}

		/**
		 * Apply end filter.
		 * Only show events that start before the `end` value.
		 * Can be any value that is supported by strtotime().
		 * @since 0.9
		 */
		
		if ($filters['end']) {
			$args['meta_query'][] = array (
				'key' => $wp_theatre->order->meta_key,
				'value' => strtotime($filters['end'], current_time( 'timestamp' )) - get_option('gmt_offset') * 3600,
				'compare' => '<='
			);
		}

		if ($filters['post__in']) {
			$args['post__in'] = $filters['post__in'];
		}

		if ($filters['post__not_in']) {
			$args['post__not_in'] = $filters['post__not_in'];
		}

		if ($filters['season']) {
			$args['meta_query'][] = array (
				'key' => WPT_Season::post_type_name,
				'value' => $filters['season'],
				'compare' => '='
			);
		}
		
		if ($filters['cat']) {
			$args['cat'] = $filters['cat'];
		}
		
		if ($filters['category_name']) {
			$args['category_name'] = $filters['category_name'];
		}
		
		if ($filters['category__and']) {
			$args['category__and'] = $filters['category__and'];
		}
		
		if ($filters['category__in']) {
			$args['category__in'] = $filters['category__in'];
		}
		
		if ($filters['category__not_in']) {
			$args['category__not_in'] = $filters['category__not_in'];
		}
		
		if ($filters['limit']) {
			$args['posts_per_page'] = $filters['limit'];
			$args['numberposts'] = $filters['limit'];
		} else {	
			$args['posts_per_page'] = -1;
			$args['numberposts'] = -1;
		}
		
		/**
		 * Filter the $args before doing get_posts().
		 *
		 * @since 0.9.2
		 *
		 * @param array $args The arguments to use in get_posts to retrieve events.
		 */
		$args = apply_filters('wpt_events_load_args',$args);
		$args = apply_filters('wpt_events_get_args',$args);

		$posts = get_posts($args);

		$events = array();
		for ($i=0;$i<count($posts);$i++) {
			$event = new WPT_Event($posts[$i]);
			$events[] = $event;
		}
		
		return $events;
	}
	
	/**
	 * Preloads events with their productions.
	 *
	 * Sets the production of a each event in a list of events with a single query.
	 * This dramatically decreases the number of queries needed to show a listing of events.
	 * 
	 * @since 	0.10.14
	 * @access 	private
	 * @param 	array	$events		An array of WPT_Event objects.
	 * @return 	array				An array of WPT_Event objects, with the production preloaded.
	 */
	private function preload_events_with_productions($events) {
		
		$production_ids = array();
		
		foreach ($events as $event) {
			$production_ids[] = get_post_meta($event->ID, WPT_Production::post_type_name, true);			
		}
		
		$production_ids = array_unique($production_ids);
		
		$productions = get_posts(
			array(
				'post_type' => WPT_Production::post_type_name,
				'post__in' => array_unique( $production_ids ),
				'posts_per_page' => -1,
			)	
		);
		
		$productions_with_keys = array();
		
		foreach ($productions as $production) {
			$productions_with_keys[$production->ID] = $production;
		}
		
		for ($i=0; $i<count($events);$i++) {
			$production_id = get_post_meta( $events[$i]->ID, WPT_Production::post_type_name, true );
			if (in_array($production_id, array_keys($productions_with_keys)) ) {
				$events[$i]->production = new WPT_Production($productions_with_keys[$production_id]);
			}
		}
		 return $events;
	}

	/**
	 * @deprecated 0.10
	 * @see WPT_Events::get_categories()
	 */
	public function categories($filters=array()) {
		_deprecated_function('WPT_Events::categories()', '0.10', 'WPT_Events::get_categories()');
		return $this->get_categories($filters);
	}
	
	/**
	 * @deprecated 0.10
	 * @see WPT_Events::get_days()
	 */
	public function days($filters=array()) {
		_deprecated_function('WPT_Events::days()', '0.10', 'WPT_Events::get_days()');
		return $this->get_days($filters);
	}
	
	/**
	 * @deprecated 0.10
	 * @see WPT_Events::get_months()
	 */
	public function months($filters=array()) {
		_deprecated_function('WPT_Events::months()', '0.10', 'WPT_Events::get_months()');
		return $this->get_months($filters);
	}
	
	
}
?>
