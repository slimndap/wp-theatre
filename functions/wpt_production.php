<?php
class WPT_Production {

	const post_type_name = 'wp_theatre_prod';
	
	function __construct($ID=false, $PostClass=false) {
		$this->PostClass = $PostClass;
	
		if ($ID instanceof WP_Post) {
			// $ID is a WP_Post object
			if (!$PostClass) {
				$this->post = $ID;
			}
			$ID = $ID->ID;
		}

		if (!$ID) {
			$post = get_post();
			if ($post) {
				$ID = $post->ID;				
			}
		}		

		$this->ID = $ID;		
	}

	function post_type() {
		return get_post_type_object(self::post_type_name);
	}

	function is_upcoming() {		
		$events = $this->upcoming_events();
		return (is_array($events) && (count($events)>0));
	}
	
	function dates() {
		if (!isset($this->dates)) {			
			$dates = '';
			$dates_short = '';
			$first_datetimestamp = $last_datetimestamp = '';
			
			$events = $this->events();
			$upcoming = $this->upcoming_events();
			if (is_array($upcoming) && (count($upcoming)>0)) {

				$first = $events[0];
				$next = $upcoming[0];
				$last = $events[count($events)-1];

				if ($next->date()==$last->date()) {
					// one or more events on the same day
					$dates.= $next->date();
				} else {
					if (time() < $first->datetime()) {
						// serie starts in the future
						$dates.= $first->date().' '.__('to','wp_theatre').' '.$last->date();
					} else {
						// serie is already running
						$dates.= __('until','wp_theatre').' '.$last->date();
					}
				}
			}
			$this->dates = $dates;
		}
		return $this->dates;
	}

	function cities() {
		if (!isset($this->cities)) {
			$cities = array();
			
			$events = $this->upcoming_events();
			if (is_array($events) && (count($events)>0)) {
				foreach ($events as $event) {
					$city = trim(ucwords(get_post_meta($event->ID,'city',true)));
					if (!empty($city) && !in_array($city, $cities)) {
						$cities[] = $city;
					}
				}
			}
			
			$cities_text = '';
			
			switch (count(array_slice($cities,0,3))) {
				case 1:
					$cities_text.= $cities[0];
					break;
				case 2:
					$cities_text.= $cities[0].' '.__('and','wp_theatre').' '.$cities[1];
					break;
				case 3:
					$cities_text.= $cities[0].', '.$cities[1].' '.__('and','wp_theatre').' '.$cities[2];
					break;
			}
			
			
			if (count($cities)>3) {
				$cities_text = __('ao','wp_theatre').' '.$cities_text;
			}
			$this->cities = $cities_text;
		}
		return $this->cities;
	}
	
	function summary() {
		if (!isset($this->summary)) {
			if ($this->dates()!='') {
				$short = $this->dates();
				if ($this->cities()!='') {
					$short .= ' '.__('in','wp_theatre').' '.$this->cities();
				}
				$short.='.';
			}
			$full = $short.' '.wp_trim_words($this->post()->post_content, 15);
			$this->summary = array(
				'dates' => $this->dates(),
				'cities' => $this->cities(),
				'short' => $short,
				'full' => $full
			);
		}		
		return $this->summary;
	}


	function get_events() {
		if (!isset($this->events)) {
			$args = array(
				'post_type'=>WPT_Event::post_type_name,
				'meta_key' => 'event_date',
				'order_by' => 'meta_value',
				'order' => 'ASC',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => self::post_type_name,
						'value' => $this->ID,
						'compare' => '=',
					),
				),
			);
			$posts = get_posts($args);
	
			$events = array();
			for ($i=0;$i<count($posts);$i++) {
				$datetime = strtotime(get_post_meta($posts[$i]->ID,'event_date',true));
				$events[$datetime.$posts[$i]->ID] = new WPT_Event($posts[$i]);
			}
			
			ksort($events);
			$this->events = array_values($events);

		}
		return $this->events;
	}
	
	function events() {
		return $this->get_events();
	}
	
	function upcoming_events() {
		$events = $this->get_events();
	
		$upcoming_events = array();
		$now = time();
		foreach ($events as $event)	{
			if (strtotime($event->post()->event_date) >= $now) {
				$upcoming_events[] = $event;
			}
		}
		return $upcoming_events;
	}
	
	function past_events() {
		$events = $this->get_events();
		
		$past_events = array();
		$now = time();
		foreach ($events as $event)	{
			if (strtotime($event->post()->event_date) < $now) {
				$past_events[] = $event;
			}
		}
		return $past_events;		
	}

	function render() {
		$summary = $this->summary();
		
		$html = '';
		
		$html.= '<div class='.self::post_type_name.' itemscope itemtype="http://schema.org/Event">';

		$img_id = self::post_type_name.'_thumbnail_'.$this->ID;
		$url_id = self::post_type_name.'_url_'.$this->ID;
		$name_id = self::post_type_name.'_name_'.$this->ID;

		$attr = array(
			'id'=>$img_id,
			'itemprop'=>'image'
		);
		$thumbnail = get_the_post_thumbnail($this->ID,'thumbnail',$attr);
		if (!empty($thumbnail)) {
			$html.= '<figure>';
			$html.= $thumbnail;
			$html.= '</figure>';
		}

		$html.= '<div class="'.self::post_type_name.'_main">';

		$html.= '<div class="'.self::post_type_name.'_title">';
		$html.= '<a itemprop="url" href="'.get_permalink($this->ID).'" id="'.$url_id.'">';
		$html.= '<span itemprop="name" id="'.$name_id.'">'.$this->post()->post_title.'</span>';
		$html.= '</a>';
		$html.= '</div>'; //.title

		$html.= '<div class="'.self::post_type_name.'_summary">';
		$html.= $summary['short']; 
		$html.= '</div>';

		$html.= '</div>'; // .main

		/**
		 * Microdata for events.
		 */
		$events = $this->upcoming_events();
		for($i=0;$i<count($events);$i++) {
		
			if ($i>0) {
				$html.= '<span itemscope itemtype="http://schema.org/Event" itemref="'.$img_id.' '.$url_id.' '.$name_id.'">';
			}
		
			$html.= '<meta itemprop="startDate" content="'.date('c',$events[$i]->datetime()).'" />';
			$html.= '<span class="'.self::post_type_name.'_location" itemprop="location" itemscope itemtype="http://data-vocabulary.org/Organization">';
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
			if ($i>0) {
				$html.= '</span>';
			}
		
		}

		$html.= '</div>';
		return $html;
	}
	
	function compile_events() {
		$events = $this->upcoming_events();
		if (!empty($events)) {
			$html = '';
			$html.= '<div class="wp_theatre_events">';
			foreach ($events as $event) {
				$html.= $event->compile();			
			}
			$html.= '</div>';
			return $html;		
		}
	}

	function render_events() {
		echo $this->compile_events();
	}

	/**
	 * Social meta tags for this production.
	 *
	 * Compiles meta tags for Facebook (Open Graph), Twitter (Twitetr Cards) and Google+ (Schema.org).
	 * Can be place in the HTML head of a production page.
	 * 
	 * @since 0.3.7
	 *
	 * @return mixed HTML
	 */
	function social_meta_tags() {
		global $wp_theatre;
		
		$meta = array();
		
		$summary = $this->summary();
		$thumbnail = wp_get_attachment_url( get_post_thumbnail_id($this->ID) );

		if (!empty($wp_theatre->wpt_social_options['social_meta_tags']) && is_array($wp_theatre->wpt_social_options['social_meta_tags'])) {
			foreach ($wp_theatre->wpt_social_options['social_meta_tags'] as $option) {
				switch ($option) {
					case 'facebook':
						$meta[] = '<!-- Open Graph data -->';	
						$meta[] = '<meta property="og:title" content="'.$this->post()->post_title.'" />';
						$meta[] = '<meta property="og:type" content="article" />';
						$meta[] = '<meta property="og:url" content="'.get_permalink($this->ID).'" />';
						if (!empty($thumbnail)) {
							$meta[] = '<meta property="og:image" content="'.$thumbnail.'" />';
						}
						$meta[] = '<meta property="og:description" content="'.$summary['full'].'" />';
						$meta[] = '<meta property="og:site_name" content="'.get_bloginfo('site_name').'" />';
						break;
					case 'twitter':
						$meta[] = '<!-- Twitter Card data -->';	
						$meta[] = '<meta name="twitter:card" content="summary">';
						$meta[] = '<meta name="twitter:title" content="'.$this->post()->post_title.'">';
						$meta[] = '<meta name="twitter:description" content="'.$summary['full'].'">';
						if (!empty($thumbnail)) {
							$meta[] = '<meta name="twitter:image:src" content="'.$thumbnail.'" />';
						}
						break;
					case 'google+':
						$meta[] = '<!-- Schema.org markup for Google+ -->';	
						$meta[] = '<meta itemprop="name" content="'.$this->post()->post_title.'">';
						$meta[] = '<meta itemprop="description" content="'.$summary['full'].'">';
						if (!empty($thumbnail)) {
							$meta[] = '<meta itemprop="image" content="'.$thumbnail.'">';
						}
						break;
				}
			}
		}
		return implode("\n",$meta);
	}

	/**
	 * The custom post as a WP_Post object.
	 *
	 * This function is inherited by the WPT_Production, WPT_Event and WPT_Seasons object.
	 * It can be used to access all properties and methods of the corresponding WP_Post object.
	 * 
	 * Example:
	 *
	 * $event = new WPT_Event();
	 * echo WPT_Event->post()->post_title();
	 *
	 * @since 0.3.5
	 *
	 * @return mixed A WP_Post object.
	 */
	public function post() {
		return $this->get_post();
	}

	private function get_post() {
		if (!isset($this->post)) {
			if ($this->PostClass) {
				$this->post = new $this->PostClass($this->ID);				
			} else {
				$this->post = get_post($this->ID);
			}
		}
		return $this->post;
	}
	

}

?>