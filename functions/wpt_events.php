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
			'day' => false,
			'month' => false,
			'category' => false,
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
			'paginateby' => array(),
			'groupby'=>false,
			'production' => false,
			'season' => false,
			'limit' => false,
			'category' => false,
			'template' => NULL
		);
		$args = wp_parse_args( $args, $defaults );

		$classes = array();
		$classes[] = "wpt_events";

		// Thumbnail
		if (!empty($args['template']) && strpos($args['template'],'{{thumbnail}}')===false) { 
			$classes[] = 'wpt_events_without_thumbnail';
		}

		$html = '';

		$filters = array(
			'upcoming' => true,
			'production' => $args['production'],
			'limit' => $args['limit'],
			'category' => $args['category'],
			'season' => $args['season']
		);

		if (in_array('day',$args['paginateby'])) {
			$days = $this->days($filters);
			if (!empty($days)) {
				if (!empty($_GET[__('day','wp_theatre')])) {
					$filters['day'] = $_GET[__('day','wp_theatre')];
				} else {
					$filters['day'] = $days[0];
				}				
			}

			$html.= '<nav class="wpt_event_days">';
			foreach($days as $day) {
				$url = remove_query_arg(__('day','wp_theatre'));
				$url = add_query_arg( __('day','wp_theatre'), sanitize_title($day) , $url);
				$html.= '<span>';
				
				$title = date_i18n('D d M',strtotime($day));
				if (sanitize_title($day) != $filters['day']) {
					$html.= '<a href="'.$url.'">'.$title.'</a>';
				} else {
					$html.= $title;
					
				}
				$html.= '</span>';
			}
			$html.= '</nav>';
		}
	

		if (in_array('month',$args['paginateby'])) {
			$months = $this->months($filters);
			if (!empty($months)) {
				if (!empty($_GET[__('month','wp_theatre')])) {
					$filters['month'] = $_GET[__('month','wp_theatre')];
				} else {
					$filters['month'] = $months[0];
				}				
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

		if (in_array('season',$args['paginateby'])) {
			$seasons = $wp_theatre->productions->seasons();

			if (!empty($_GET[__('season','wp_theatre')])) {
				$filters['season'] = $_GET[__('season','wp_theatre')];
			} else {
				$slugs = array_keys($seasons);
				$filters['season'] = $slugs[0];				
			}

			$html.= '<nav>';
			foreach($seasons as $slug=>$season) {

				$url = remove_query_arg(__('season','wp_theatre'));
				$url = add_query_arg( __('season','wp_theatre'), $slug , $url);
				$html.= '<span>';

				$title = $season->title();
				if ($slug == $filters['season']) {
					$html.= $title;
				} else {
					$html.= '<a href="'.$url.'">'.$title.'</a>';					
				}
				$html.= '</span>';
			}
			$html.= '</nav>';
		}


		$event_args = array();
		if (isset($args['template'])) { $event_args['template'] = $args['template']; }

		
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
		global $wp_theatre;
		
		$filters = wp_parse_args( $filters, $this->defaults() );
		$args = array(
			'post_type' => WPT_Event::post_type_name,
			'post_status' => $filters['status'],
			'meta_query' => array(),
			'order' => 'asc'
		);
		
		if ($filters['upcoming']) {
			$args['meta_query'][] = array (
				'key' => $wp_theatre->order->meta_key,
				'value' => time(),
				'compare' => '>='
			);
		}

		if ($filters['production']) {
			$args['meta_query'][] = array (
				'key' => WPT_Production::post_type_name,
				'value' => $filters['production'],
				'compare' => '='
			);
		}
		
		if ($filters['month']) {
			$args['meta_query'][] = array (
				'key' => 'event_date',
				'value' => $filters['month'],
				'compare' => 'LIKE'
			);
		}

		if ($filters['day']) {
			$args['meta_query'][] = array (
				'key' => 'event_date',
				'value' => $filters['day'],
				'compare' => 'LIKE'
			);
		}

		if ($filters['season']) {
			$args['meta_query'][] = array (
				'key' => WPT_Season::post_type_name,
				'value' => $filters['season'],
				'compare' => '='
			);
		}
		
		if ($filters['category']) {
			$args['cat'] = $filters['category'];
		}
		
		if ($filters['limit']) {
			$args['posts_per_page'] = $filters['limit'];
		} else {
			$args['posts_per_page'] = -1;
			
		}

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
	 * An array of all days with upcoming events.
	 * @since 0.8
	 */
	function days($filters=array()) {
		// get all event according to remaining filters
		$filters['day'] = false;
		$events = $this->load($filters);		
		$days = array();
		foreach ($events as $event) {
			$days[] = date('Y-m-d',$event->datetime());
		}
		$days = array_unique($days);
		sort($days);

		return $days;
	}
	
	/**
	 * An array of all months with upcoming events.
	 * @since 0.5
	 */
	function months($filters=array()) {
		// get all event according to remaining filters
		$filters['month'] = false;
		$events = $this->load($filters);		
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