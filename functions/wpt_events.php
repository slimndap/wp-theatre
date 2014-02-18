<?php
class WPT_Events {
		
	/**
	 * A list of upcoming events in HTML.
	 *
	 * Compiles a list of all upcoming events and outputs the result to the browser.
	 * 
	 * Example:
	 *
	 * $args = array('paged'=>true);
	 * WP_Theatre::render_events($args); // a list of all upcoming events, paginated by month
	 *
	 * @since 0.3.5
	 *
	 * @param array $args {
	 *     An array of arguments. Optional.
	 *
	 *     @type bool $paged Paginate the list by month. Default <false>.
	 *     @type bool $grouped Group the list by month. Default <false>.
	 *     @type int $limit Limit the list to $limit events. Use <false> for an unlimited list. Default <false>.
	 * }
	 * @see WP_Theatre::get_events()
 	 * @return string HTML.
	 */
	public function html_listing($args=array()) {
		global $wpdb;

		$defaults = array(
			'paged' => false,
			'grouped' => false,
			'limit' => false,
			WPT_Production::post_type_name => false,
		);
		$args = wp_parse_args( $args, $defaults );
		extract($args);

		$events_args = array(
			'limit' => $args['limit'],
			WPT_Production::post_type_name => $args[WPT_Production::post_type_name]
		);
		
		$html = '';
		$html.= '<div class="wp_theatre_events">';

		if ($args['paged']) {
			$querystr = "
				SELECT substring( event_date.meta_value, 1, 7 ) AS
				title FROM $wpdb->posts AS
				EVENTS
				JOIN $wpdb->postmeta AS event_date ON events.ID = event_date.post_ID
				WHERE events.post_type = 'wp_theatre_event'
				AND event_date.meta_key = 'event_date'
				AND event_date.meta_value > NOW( )
				GROUP BY title
				ORDER BY title
			";
			$months = $wpdb->get_results($querystr, OBJECT);
			
			if (!empty($_GET[__('month','wp_theatre')])) {
				$page = $_GET[__('month','wp_theatre')];
			} else {
				$page = $months[0]->title;				
			}

			$html.= '<nav>';
			foreach($months as $month) {
				$url = remove_query_arg(__('month','wp_theatre'));
				$url = add_query_arg( __('month','wp_theatre'), sanitize_title($month->title) , $url);
				$html.= '<span>';
				
				$title = date_i18n('M Y',strtotime($month->title));
				if (sanitize_title($month->title) != $page) {
					$html.= '<a href="'.$url.'">'.$title.'</a>';
				} else {
					$html.= $title;
					
				}
				$html.= '</span>';
			}
			$html.= '</nav>';
			
			$events_args[__('month','wp_theatre')] = $page;
		}

		$events = $this->upcoming($events_args);

		$event_args = array();
		if (isset($args['fields'])) { $event_args['fields'] = $args['fields']; }
		if (isset($args['hide'])) { $event_args['hide'] = $args['hide']; }
		if (isset($args['thumbnail'])) { $event_args['thumbnail'] = $args['thumbnail']; }
		if (isset($args['tickets'])) { $event_args['tickets'] = $args['tickets']; }

		$group = '';
		foreach ($events as $event) {
			if ($args['grouped']) {
				$month = date('Y-m',$event->datetime());
				if ($group != $month) {
					$html.= '<h3>'.date_i18n('F',$event->datetime()).'</h3>';
					$group = $month;
				}
			}
			$html.=$event->html($event_args);
		}

		$html.= '</div>'; //.wp-theatre_events
		
		return $html;
	}
	
	public function meta_listing() {
		$defaults = array(
			'paged' => false,
			'grouped' => false,
			'limit' => false,
			WPT_Production::post_type_name => false,
		);
		$args = wp_parse_args( $args, $defaults );
		extract($args);

		$html = '';

		$events_args = array(
			'limit' => $args['limit'],
			WPT_Production::post_type_name => $args[WPT_Production::post_type_name]
		);
		$events = $this->upcoming($events_args);
		
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
			if ($venue!='' && $city!='') {
				$html.= ', ';
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

	/**
	 * All upcoming events.
	 *
	 * Returns an array of all pubished events attached to a production and with a startdate in the future.
	 * 
	 * Example:
	 *
	 * $events = $wp_theatre->events();
	 *
	 * @since 0.3.6
	 *
	 * @see WP_Theatre::get_events()
	 *
	 * @param  string $PostClass Optional. 
	 * @return mixed An array of WPT_Event objects.
	 */
 	public function upcoming($args = array(), $PostClass = false) {
 		$args['upcoming'] = true;
 		return $this->all($args,$PostClass);
 	}
 	
 	public function past($args = array(), $PostClass = false) {
 		$args['past'] = true;
 		return $this->all($args,$PostClass);
 	}
 	
 	public function all($args = array(), $PostClass = false) {
		global $wpdb;

		$defaults = array(
			'paged' => false,
			'grouped' => false,
			'limit' => false,
			'upcoming' => false,
			'past' => false
		);
		$args = wp_parse_args( $args, $defaults );

		$querystr = "
			SELECT events.ID
			FROM $wpdb->posts AS
			events
			
			join $wpdb->postmeta AS productions on events.ID = productions.post_ID
			join $wpdb->postmeta AS event_date on events.ID = event_date.post_ID
			
			WHERE 
			events.post_type = '".WPT_Event::post_type_name."'
			AND events.post_status='publish'
			AND productions.meta_key = '".WPT_Production::post_type_name."'
			AND event_date.meta_key = 'event_date'
		";

		if ($args['upcoming']) {
			$querystr.= ' AND event_date.meta_value > NOW( )';
		} elseif ($args['past']) {
			$querystr.= ' AND event_date.meta_value < NOW( )';
		}
		
		if (!empty($args[__('month','wp_theatre')])) {
			$querystr.= ' AND event_date.meta_value LIKE "'.$args[__('month','wp_theatre')].'%"';
		}
		
		if ($args[WPT_Production::post_type_name]) {
			$querystr.= ' AND productions.meta_value='.$args[WPT_Production::post_type_name].'';			
		}
		$querystr.= ' ORDER BY event_date.meta_value';
		
		if ($args['limit']) {
			$querystr.= ' LIMIT 0,'.$args['limit'];
		}
		$posts = $wpdb->get_results($querystr, OBJECT);

		$events = array();
		for ($i=0;$i<count($posts);$i++) {
			$events[] = new WPT_Event($posts[$i]->ID, $PostClass);
		}
		return $events;
	}
		
}
?>