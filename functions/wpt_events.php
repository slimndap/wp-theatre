<?php
class WPT_Events extends WPT_Listing {

	/**
	 * An array of all categories with upcoming events.
	 * @since 0.5
	 */
	function categories() {
		$current_category = $this->filters['category'];
		
		// temporarily disable current month filter
		$this->filters['category'] = false;

		// get all events according to remaining filters
		$events = $this->get();		
		$categories = array();
		foreach ($events as $event) {
			$post_categories = wp_get_post_categories( $event->production()->ID );
			foreach($post_categories as $c){
				$cat = get_category( $c );
				$categories[$cat->slug] = $cat->name;
			}
		}
		asort($categories);
		
		// reset current month filter
		$this->filters['category'] = $current_category;
		return $categories;
		
	}
	
	function defaults() {
		return array(
			'limit' => false,
			'upcoming' => false,
			'past' => false,
			'month' => false,
			'category' => false,
			'production' => false
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
			$months = $this->months();
			
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
			
			$events_args[__('month','wp_theatre')] = $page;
		}
	
		if (in_array('category',$args['paginateby'])) {
			$categories = $this->categories();

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
				
				if ($slug != $_GET[__('category','wp_theatre')]) {
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
		if (isset($args['hide'])) { $event_args['hide'] = $args['hide']; }
		if (isset($args['thumbnail'])) { $event_args['thumbnail'] = $args['thumbnail']; }
		if (isset($args['tickets'])) { $event_args['tickets'] = $args['tickets']; }

		
		switch ($args['groupby']) {
			case 'month':
				if (!in_array('month', $args['paginateby'])) {
					$months = $this->months();
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
					$categories = $this->categories();
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
		
		$querystr = "
			SELECT events.ID
			FROM $wpdb->posts AS
			events
			
			JOIN $wpdb->postmeta AS productions on events.ID = productions.post_ID
			LEFT OUTER JOIN $wpdb->term_relationships AS categories on productions.meta_value = categories.object_id
			JOIN $wpdb->postmeta AS event_date on events.ID = event_date.post_ID
			
			WHERE 
			events.post_type = '".WPT_Event::post_type_name."'
			AND events.post_status='publish'
			AND productions.meta_key = '".WPT_Production::post_type_name."'
			AND event_date.meta_key = 'event_date'
		";
		
		if ($filters['upcoming']) {
			$querystr.= ' AND event_date.meta_value > NOW( )';
		} elseif ($filters['past']) {
			$querystr.= ' AND event_date.meta_value < NOW( )';
		}
		
		if ($filters['month']) {
			$querystr.= ' AND event_date.meta_value LIKE "'.$filters['month'].'%"';
		}
		
		if ($filters['category']) {
			$querystr.= ' AND term_taxonomy_id = '.$filters['category'];
		}
		
		if ($filters['production']) {
			$querystr.= ' AND productions.meta_value='.$filters['production'].'';			
		}
		$querystr.= ' GROUP BY events.ID';
		$querystr.= ' ORDER BY event_date.meta_value';
		
		if ($filters['limit']) {
			$querystr.= ' LIMIT 0,'.$filters['limit'];
		}


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
	function months() {
		// get all event according to remaining filters
		$events = $this->get();		
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
			'production' => false
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
		
			if ($i==0) {
				$html.= '<span itemscope itemtype="http://schema.org/Event">';			
				$html.= '<meta itemprop="name" id="'.WPT_Production::post_type_name.'_title_'.$uniqid.'" content="'.$events[$i]->production()->title().'" />';
				$html.= '<meta itemprop="url" id="'.WPT_Production::post_type_name.'_permalink_'.$uniqid.'" content="'.$events[$i]->production()->permalink().'" />';
				$html.= '<meta itemprop="image" id="'.WPT_Production::post_type_name.'_thumbnail_'.$uniqid.'" content="'.wp_get_attachment_url($events[$i]->production()->thumbnail()).'" />';
			} else {
				$html.= '<span itemscope itemtype="http://schema.org/Event" itemref="'.WPT_Production::post_type_name.'_title_'.$uniqid.' '.WPT_Production::post_type_name.'_permalink_'.$uniqid.' '.WPT_Production::post_type_name.'_thumbnail_'.$uniqid.'">';
			}
		
			$html.= '<meta itemprop="startDate" content="'.date('c',$events[$i]->datetime()).'" />';
			$html.= '<span class="'.WPT_Event::post_type_name.'_location" itemprop="location" itemscope itemtype="http://data-vocabulary.org/Organization">';
			$venue = get_post_meta($events[$i]->ID,'venue',true);
			$city = get_post_meta($events[$i]->ID,'city',true);
			if ($venue!='') {
				$html.= '<meta itemprop="name" content="'.$venue.'" />';
			}
			if ($city!='') {
				$html.= '<span itemprop="address" itemscope itemtype="http://data-vocabulary.org/Address">';
				$html.= '<meta itemprop="locality" content="'.$city.'" />';
				$html.= '</span>';
			}
			$html.= '</span>'; // .location

			$html.= '</span>'; // .event
		
		}

		return $html;
	}
		
}
?>