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
				$categories[$cat->slug] = $cat->name;
			}
		}
		asort($categories);
		
		return $categories;
		
	}
	
	function defaults() {
		return array(
			'limit' => false,
			'upcoming' => false,
			'past' => false,
			'month' => false,
			'category' => false,
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
		$defaults = array(
			'paged' => false, //deprecated
			'grouped' => false,
			'thumbnail'=>true,
			'tickets'=>true,
			'fields'=>NULL,
			'hide'=>NULL,
			'paginateby' => array(),
			'groupby'=>false,
			'production' => false,
			'limit' => false
		);
		$args = wp_parse_args( $args, $defaults );

		// translate deprecated 'paged' argument
		if ($args['paged'] && !in_array('month', $args['paginateby'])) {
			$args['paginateby'][] ='month';
		}

		$classes = array();
		$classes[] = "wpt_events";

		// Thumbnail
		if (!$args['thumbnail']) {
			$classes[] = 'wpt_events_without_thumbnail';
		}

		$html = '';

		$filters = array(
			'upcoming' => true,
			'production' => $args['production'],
			'limit' => $args['limit']
		);

		if (in_array('month',$args['paginateby'])) {
			$months = $this->months($filters);
			
			if (!empty($_GET[__('month','wp_theatre')])) {
				$filters['month'] = $_GET[__('month','wp_theatre')];
			} else {
				$filters['month'] = $months[0];
			}

			$html.= '<nav class="wpt_event_months">';
			foreach($months as $month) {
				$url = remove_query_arg(__('month','wp_theatre'));
				$url = add_query_arg( __('month','wp_theatre'), sanitize_title($month) , $url);
				$html.= '<span>';
				
				$title = date_i18n('M Y',strtotime($month));
				if (sanitize_title($month) != $filters['month']) {
					$html.= '<a href="'.$url.'">'.$title.'</a>';
				} else {
					$html.= $title;
					
				}
				$html.= '</span>';
			}
			$html.= '</nav>';
		}
	
		if (in_array('category',$args['paginateby'])) {
			$categories = $this->categories($filters);

			if (!empty($_GET[__('category','wp_theatre')])) {
				if ($category = get_category_by_slug($_GET[__('category','wp_theatre')])) {
		  			$filters['category'] = $category->term_id;				
				}
			}
			
			$html.= '<nav class="wpt_event_categories">';

			$html.= '<span>';
			if (empty($filters['category'])) {
				$html.= __('All','wp_theatre').' '.__('categories','wp_theatre');
			} else {				
				$url = remove_query_arg(__('category','wp_theatre'));
				$html.= '<a href="'.$url.'">'.__('All','wp_theatre').' '.__('categories','wp_theatre').'</a>';
			}
			$html.= '</span>';
			
			foreach($categories as $slug=>$name) {
				$url = remove_query_arg(__('category','wp_theatre'));
				$url = add_query_arg( __('category','wp_theatre'), $slug , $url);
				$html.= '<span>';
				if (empty($_GET[__('category','wp_theatre')]) || $slug != $_GET[__('category','wp_theatre')]) {
					$html.= '<a href="'.$url.'">'.$name.'</a>';
				} else {
					$html.= $name;
					
				}
				$html.= '</span>';
			}
			$html.= '</nav>';
		}


		$event_args = array();
		if (isset($args['fields'])) { $event_args['fields'] = $args['fields']; }
		if (isset($args['thumbnail'])) { $event_args['thumbnail'] = $args['thumbnail']; }
		if (isset($args['tickets'])) { $event_args['tickets'] = $args['tickets']; }

		
		switch ($args['groupby']) {
			case 'month':
				if (!in_array('month', $args['paginateby'])) {
					$months = $this->months($filters);
					foreach($months as $month) {
						$filters['month'] = $month;
						$events = $this->get($filters);
						if (!empty($events)) {
							$html.= '<h3>'.date_i18n('F',strtotime($month)).'</h3>';
							foreach ($events as $event) {
								$html.=$event->html($event_args);							
							}
						}
					}
					break;					
				}
			case 'category':
				if (!in_array('category', $args['paginateby'])) {
					$categories = $this->categories($filters);
					foreach($categories as $slug=>$name) {
						if ($category = get_category_by_slug($slug)) {
				  			$filters['category'] = $category->term_id;				
						}
						$events = $this->get($filters);
						if (!empty($events)) {
							$html.= '<h3>'.$name.'</h3>';
							foreach ($events as $event) {
								$html.=$event->html($event_args);							
							}							
						}
					}
					break;					
				}
			default:
				$events = $this->get($filters);
				foreach ($events as $event) {
					$html.=$event->html($event_args);							
				}
		}

		// Wrapper
		$html = '<div class="'.implode(' ',$classes).'">'.$html.'</div>'; 
		
		return $html;
	}
	
	/**
	 * Setup the current selection of events.
	 * 
	 * @since 0.5
	 *
 	 * @return array Events.
	 */
	function load($filters=array()) {
		global $wpdb;
		
		$filters = wp_parse_args( $filters, $this->defaults() );
		$value_parameters = array();
		
		$querystr = "
			SELECT events.ID
			FROM $wpdb->posts AS
			events
			
			JOIN $wpdb->postmeta AS productions on events.ID = productions.post_ID
			LEFT OUTER JOIN $wpdb->term_relationships AS term_relationships on productions.meta_value = term_relationships.object_id
			LEFT OUTER JOIN $wpdb->term_taxonomy AS categories on term_relationships.term_taxonomy_id = categories.term_taxonomy_id
			JOIN $wpdb->postmeta AS event_date on events.ID = event_date.post_ID
			
			WHERE 
			events.post_type = '".WPT_Event::post_type_name."'
			AND events.post_status IN ("."'" . implode("','", $filters['status']) . "')
			AND productions.meta_key = '".WPT_Production::post_type_name."'
			AND event_date.meta_key = 'event_date'
		";
		
		if ($filters['upcoming']) {
			$querystr.= ' AND event_date.meta_value > NOW( )';
		} elseif ($filters['past']) {
			$querystr.= ' AND event_date.meta_value < NOW( )';
		}
		
		if ($filters['month']) {
			$querystr.= ' AND event_date.meta_value LIKE "%s"';
			$value_parameters[] = $filters['month'].'%';
		}
		
		if ($filters['category']) {
			$querystr.= ' AND categories.term_id = %d';
			$value_parameters[] = $filters['category'];
		}
		
		if ($filters['production']) {
			$querystr.= ' AND productions.meta_value=%d';			
			$value_parameters[] = $filters['production'];
		}
		$querystr.= ' GROUP BY events.ID';
		$querystr.= ' ORDER BY event_date.meta_value';
		
		if ($filters['limit']) {
			$querystr.= ' LIMIT 0,%d';
			$value_parameters[] = $filters['limit'];
		}

		$querystr = $wpdb->prepare($querystr,$value_parameters);

		$posts = $wpdb->get_results($querystr, OBJECT);

		$events = array();
		for ($i=0;$i<count($posts);$i++) {
			$events[] = new WPT_Event($posts[$i]->ID);
		}
		
		return $events;
	}

	/**
	 * An array of all months with upcoming events.
	 * @since 0.5
	 */
	function months($filters=array()) {
		// get all event according to remaining filters
		$filters['month'] = false;
		$events = $this->get($filters);		
		$months = array();
		foreach ($events as $event) {
			$months[] = date('Y-m',$event->datetime());
		}
		$months = array_unique($months);
		sort($months);

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