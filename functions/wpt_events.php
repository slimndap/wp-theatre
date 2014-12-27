<?php
class WPT_Events extends WPT_Listing {

	/**
	 * An array of all categories with upcoming events.
	 * @since 0.5
	 */
	function categories($filters=array()) {
		// get all events according to remaining filters
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
	 * An array of all days with upcoming events.
	 * @since 0.8
	 */
	function days($filters=array()) {
		$events = $this->load($filters);		
		$days = array();
		foreach ($events as $event) {
			$days[date('Y-m-d',$event->datetime())] = date_i18n('D j M',$event->datetime());
		}
		ksort($days);

		return $days;
	}
	
	function defaults() {
		return array(
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
			'status' => array('publish')
		);
	}
	
	
	/**
	 * A list of upcoming events in HTML.
	 * 
	 * Example:
	 *
	 * $args = array('paginateby'=>'month');
	 * echo $wp_theatre->events->html($args); // a list of all upcoming events, paginated by month
	 *
	 * @since 0.5
	 *
	 * @param array $args {
	 *     An array of arguments. Optional.
	 *
	 *     @type bool $paged Paginate the list by month. Default <false>.
	 *     @type bool $grouped Group the list by month. Default <false>.
	 *     @type int $limit Limit the list to $limit events. Use <false> for an unlimited list. Default <false>.
	 * }
 	 * @return string HTML.
	 */
	public function html($args=array()) {
		global $wp_theatre;

		$defaults = array(
			'cat' => false,
			'category_name' => false,
			'category__and' => false,
			'category__in' => false,
			'category__not_in' => false,
			'groupby'=>false,
			'limit' => false,
			'start' => false,
			'end' => false,
			'paginateby' => array(),
			'production' => false,
			'season' => false,
			'template' => NULL,
			'upcoming' => true
		);
		$args = wp_parse_args($args, $defaults );

		$classes = array('wpt_listing','wpt_events');

		// Thumbnail
		if (!empty($args['template']) && strpos($args['template'],'{{thumbnail}}')===false) { 
			$classes[] = 'wpt_events_without_thumbnail';
		}

		$filters = array(
			'upcoming' => $args['upcoming'],
			'production' => $args['production'],
			'limit' => $args['limit'],
			'cat' => $args['cat'],
			'category_name' => $args['category_name'],
			'category__and' => $args['category__and'],
			'category__in' => $args['category__in'],
			'category__not_in' => $args['category__not_in'],
			'start' => $args['start'],
			'end' => $args['end'],
			'season' => $args['season']
		);

		$html = '';

		$html.= $this->get_html_pagination($filters, $args);
		$html.= $this->get_html_for_page($filters, $args['groupby'], $args['template']);
		
		// Wrapper
		$html = '<div class="'.implode(' ',$classes).'">'.$html.'</div>'; 
		
		return $html;
	}
	

	private function get_html_pagination($filters=array(), $args=array()) {
		$html = '';

		/*
		 * Days navigation
		 */
		$html.= $this->filter_pagination('day', $this->days($filters), $args);

		/*
		 * Months navigation
		 */
		$html.= $this->filter_pagination('month', $this->months($filters), $args);

		/*
		 * Categories navigation
		 */
		$html.= $this->filter_pagination('category', $this->categories($filters), $args);

		return $html;		
	}

	private function get_html_grouped($filters=array(), $groupby=false, $template=NULL) {
		$html = '';
		switch ($groupby) {
			case 'day':
				$days = $this->days($filters);
				foreach($days as $day=>$name) {
					if ($day_html = $this->get_html_for_day($day, $filters, false, $template)) {
						$html.= '<h3 class="wpt_listing_group day">';
						$html.= apply_filters('wpt_listing_group_day',date_i18n('l d F',strtotime($day)),$day);
						$html.= '</h3>';
						$html.= $day_html;
					}
				}
				break;					
			case 'month':
				$months = $this->months($filters);
				foreach($months as $month=>$name) {
					if ($month_html = $this->get_html_for_month($month, $filters, false, $template)) {
						$html.= '<h3 class="wpt_listing_group month">';
						$html.= apply_filters('wpt_listing_group_month',date_i18n('F',strtotime($month)),$month);
						$html.= '</h3>';
						$html.= $month_html;
					}
				}
				break;					
			case 'category':
				$categories = $this->categories($filters);
				foreach($categories as $cat_id=>$name) {
					if ($cat_html = $this->get_html_for_category($cat_id, $filters, false, $template)) {
						$html.= '<h3 class="wpt_listing_group category">';
						$html.= apply_filters('wpt_listing_group_category',$name,$cat_id);
						$html.= '</h3>';
						$html.= $cat_html;						
					}
				}
				break;					
			default:
				$events = $this->get($filters);
				foreach ($events as $event) {
					$args = array();
					if (!empty($template)) {
						$args = array('template'=>$template);
					}
					$html.= $event->html($args);
				}					
		}
		return $html;
	}
	
	private function get_html_for_page($filters=array(), $groupby=false, $template=NULL) {
		global $wp_query;
		
		if (!empty($wp_query->query_vars['wpt_month'])) {
			return $this->get_html_for_month($wp_query->query_vars['wpt_month'], $filters, $groupby, $template);
		} elseif (!empty($wp_query->query_vars['wpt_day'])) {
			return $this->get_html_for_day($wp_query->query_vars['wpt_day'], $filters, $groupby, $template);			
		} elseif (!empty($wp_query->query_vars['wpt_category'])) {
			return $this->get_html_for_category($wp_query->query_vars['wpt_category'], $filters, $groupby, $template);			
		} else {
			return $this->get_html_grouped($filters, $groupby, $template);
		}
	}
	
	private function get_html_for_category($cat_id, $filters = array(), $groupby=false, $template=NULL) {
		if ($category = get_category($cat_id)) {
  			$filters['cat'] = $category->term_id;				
		}

		return $this->get_html_grouped($filters, $groupby, $template);
	}
	
	private function get_html_for_day($day, $filters = array(), $groupby=false, $template=NULL) {
				
		/*
		 * Set the `start`-filter to today.
		 * Except when the active `start`-filter is set to a later date.
		 */

		if (
			empty($filters['start']) ||
			(strtotime($filters['start']) < strtotime($day))
		) {
			$filters['start'] = $day;			
		}
		
		/*
		 * Set the `end`-filter to the next day.
		 * Except when the active `end`-filter is set to an earlier date.
		 */
		 
		if (
			empty($filters['end']) ||
			(strtotime($filters['end']) > strtotime($day.' +1 day'))
		) {
			$filters['end'] = $day.' +1 day';			
		}
		
		return $this->get_html_grouped($filters, $groupby, $template);
	}
	
	private function get_html_for_month($month, $filters = array(), $groupby=false, $template=NULL) {
				
		/*
		 * Set the `start`-filter to the first day of the month.
		 * Except when the active `start`-filter is set to a later date.
		 */

		if (
			empty($filters['start']) ||
			(strtotime($filters['start']) < strtotime($month))
		) {
			$filters['start'] = $month;			
		}
		
		/*
		 * Set the `end`-filter to the first day of the next month.
		 * Except when the active `end`-filter is set to an earlier date.
		 */
		 
		if (
			empty($filters['end']) ||
			(strtotime($filters['end']) > strtotime($month.' +1 month'))
		) {
			$filters['end'] = $month.' +1 month';			
		}
		return $this->get_html_grouped($filters, $groupby, $template);
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
	 * Setup the current selection of events.
	 * 
	 * @since 0.5
	 *
 	 * @return array Events.
	 */
	 
	function load($filters=array()) {
		global $wp_theatre;

		$filters = wp_parse_args( $filters, $this->defaults() );

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
		
		$posts = get_posts($args);

		$events = array();
		for ($i=0;$i<count($posts);$i++) {
			$key = $posts[$i]->ID;
			$event = new WPT_Event($posts[$i]->ID);
			$events[] = $event;
		}

		return $events;
	}

	/**
	 * Gets all months that have events.
	 *
	 * @since 0.5
	 * @since 0.10				No longer limits the output to months with upcoming events.
	 *							See: https://github.com/slimndap/wp-theatre/issues/75
	 *
	 * @param array $filters	See WPT_Events::load() for possible values.
	 @ return array 			Months.
	 */
	function months($filters=array()) {
		$events = $this->load($filters);
		$months = array();
		foreach ($events as $event) {
			$months[date('Y-m',$event->datetime())] = date_i18n('M Y',$event->datetime());
		}
		ksort($months);
		return $months;
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
		
}
?>