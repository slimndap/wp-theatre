<?php

/*
 * Manages events listings.
 *
 * Uses this class to compile lists of events or fully formatted HTML listings of events.
 *
 * @since 0.5
 * @since 0.10	Complete rewrite, while maintaining backwards compatibility.
 */
 
class WPT_Events extends WPT_Listing {
	
	/**
	 * Gets an array of all categories events.
	 *
	 * @since 0.5
	 * @since 0.10	Renamed method from `categories()` to `get_categories()`.
	 *
	 * @param 	array $filters	See WPT_Events::get() for possible values.
	 * @return 	array 			Categories.
	 */
	function get_categories($filters=array()) {
		$filters['category'] = false;
		$events = $this->get($filters);		
		$categories = array();
		foreach ($events as $event) {
			$post_categories = wp_get_post_categories( $event->production()->ID );
			foreach($post_categories as $c){
				$cat = get_category( $c );
				$categories[$cat->term_id] = $cat->name;
			}
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
	 *
	 * @param 	array $filters	See WPT_Events::get() for possible values.
	 * @return 	array 			Days.
	 */
	function get_days($filters=array()) {
		$events = $this->get($filters);		
		$days = array();
		foreach ($events as $event) {
			$days[date('Y-m-d',$event->datetime())] = date_i18n('D j M',$event->datetime());
		}
		ksort($days);
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
	 *
	 * @see WPT_Events::get_html_grouped();
	 *
	 * @access private
	 * @param 	int $cat_id		ID of the category.
	 * @param 	array $args 	See WPT_Events::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	private function get_html_for_category($cat_id, $args=array()) {
		if ($category = get_category($cat_id)) {
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
	 * Gets a list of events in HTML for a page.
	 * 
	 * @since 0.10
	 *
	 * @see WPT_Events::get_html_grouped();
	 * @see WPT_Events::get_html_for_month();
	 * @see WPT_Events::get_html_for_day();
	 * @see WPT_Events::get_html_for_category();
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
		$html = '';

		// Days navigation
		$html.= $this->filter_pagination('day', $this->get_days($args), $args);

		// Months navigation
		$html.= $this->filter_pagination('month', $this->get_months($args), $args);

		// Categories navigation
		$html.= $this->filter_pagination('category', $this->get_categories($args), $args);

		return $html;		
	}

	/**
	 * Gets all months that have events.
	 *
	 * @since 0.5
	 * @since 0.10				No longer limits the output to months with upcoming events.
	 *							See: https://github.com/slimndap/wp-theatre/issues/75
	 * 							Renamed method from `months()` to `get_months()`.
	 *
	 * @param array $filters	See WPT_Events::get() for possible values.
	 * @return array 			Months.
	 */
	function get_months($filters=array()) {
		$events = $this->get($filters);
		$months = array();
		foreach ($events as $event) {
			$months[date('Y-m',$event->datetime())] = date_i18n('M Y',$event->datetime());
		}
		ksort($months);
		return $months;
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
	 * Get a list of events.
	 * 
	 * @since 0.5
	 * @since 0.10	Renamed method from `load()` to `get()`.
	 *
 	 * @return array Events.
	 */
	 
	function get($filters=array()) {
		global $wp_theatre;

		$defaults = array(
			'limit' => false,
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
			'order' => 'asc'
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
				'compare' => '='
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
				'value' => strtotime($filters['start']),
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
				'value' => strtotime($filters['end']),
				'compare' => '<='
			);
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
			$key = $posts[$i]->ID;
			$event = new WPT_Event($posts[$i]->ID);
			$events[] = $event;
		}

		return $events;
	}

	
	public function meta($args=array()) {
		$defaults = array(
			'paged' => false,
			'grouped' => false,
			'production' => false,
		);
		$args = wp_parse_args( $args, $defaults );

		$html = '';

		$filters = array(
			'upcoming' => true,
			'production' => $args['production']
		);

		$events = $this->get($filters);
		
		$uniqid = uniqid();
		
		for($i=0;$i<count($events);$i++) {
			$html.= $events[$i]->meta();
		}

		return $html;
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