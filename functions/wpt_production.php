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

	/**
	 * Production cites.
	 * 
	 * Returns a summary of the cities of the production events as plain text or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string URL or HTML.
	 */
	function cities($args=array()) {
		$defaults = array(
			'html' => false
		);

		$args = wp_parse_args( $args, $defaults );
		
		if (!isset($this->cities)) {
			$cities = array();
			
			$events = $this->upcoming();
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
			$this->cities = apply_filters('wpt_event_cities',$cities_text, $this);
		}
		if ($args['html']) {
			$html = '';
			$html.= '<div class="'.self::post_type_name.'_cities">'.$this->cities.'</div>';
			return apply_filters('wpt_event_cities_html', $html, $this);				
		} else {
			return $this->cities;
		}
	}

	/**
	 * Production dates.
	 * 
	 * Returns a summary of the dates of the production events as plain text or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string URL or HTML.
	 */
	function dates($args=array()) {
		$defaults = array(
			'html' => false
		);

		$args = wp_parse_args( $args, $defaults );
				
		if (!isset($this->dates)) {			
			$dates = '';
			$dates_short = '';
			$first_datetimestamp = $last_datetimestamp = '';
			
			$upcoming = $this->upcoming();
			$events = $this->events();
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
			$this->dates = apply_filters('wpt_event_dates',$dates, $this);
		}
		if ($args['html']) {
			$html = '';
			$html.= '<div class="'.self::post_type_name.'_dates">'.$this->dates.'</div>';
			return apply_filters('wpt_event_dates_html', $html, $this);				
		} else {
			return $this->dates;
		}
	}

	function events() {
		global $wp_theatre;
		if (!isset($this->events)) {
			$args = array(
				WPT_Production::post_type_name => $this->ID
			);
			$this->events = $wp_theatre->events->all($args);
		}
		return $this->events;
	}
	
	/**
	 * Production excerpt.
	 * 
	 * Returns an excerpt of the production page as plain text or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string URL or HTML.
	 */
	function excerpt($args=array()) {
		$defaults = array(
			'html' => false,
			'words' => 15
		);

		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->excerpt)) {
			$this->excerpt = apply_filters('wpt_production_excerpt',wp_trim_words($this->post()->post_content, $args['words']), $this);
		}

		if ($args['html']) {
			$html = '';
			$html.= '<p class="'.self::post_type_name.'_excerpt">'.$this->excerpt.'</p>';
			return apply_filters('wpt_event_excerpt_html', $html, $this);				
		} else {
			return $this->excerpt;				
		}
	}

	function past() {
		global $wp_theatre;
		if (!isset($this->past)) {
			$args = array(
				WPT_Production::post_type_name => $this->ID
			);
			$this->past = $wp_theatre->events->past($args);
		}
		return $this->past;
	}

	/**
	 * Production permalink.
	 * 
	 * Returns a link to the production page as a URL or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 *     @type string $text Display text for HTML version. Defaults to the title of the production.
	 * }
	 * @return string URL or HTML.
	 */
	function permalink($args=array()) {
		$defaults = array(
			'html' => false,
			'text' => $this->post()->post_title
		);

		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->permalink)) {
			$this->permalink = apply_filters('wpt_production_permalink',get_permalink($this->ID), $this);
		}

		if ($args['html']) {
			$html = '';
			$html.= '<a itemprop="url" href="'.get_permalink($this->ID).'">';
			$html.= $args['text'];
			$html.= '</a>';
			return apply_filters('wpt_event_permalink_html', $html, $this);				
		} else {
			return $this->permalink;				
		}
	}
	
	/**
	 * Production season.
	 *
	 * @since 0.4
	 *
	 * @return object WPT_Season.
	 */
	function season() {
		if (!isset($this->season)) {
			$season = get_post_meta($this->ID,'wp_theatre_season',true);
			if (!empty($season)) {
				$this->season = new WPT_Season($season);
			} else {
				$this->season = false;
			}
		}	
		return $this->season;			
	}

	/**
	 * Production summary.
	 * 
	 * Returns a summary of the production page containing dates, cities and excerpt as plain text or as an HTML element.
	 *
	 * @todo Add prices.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string URL or HTML.
	 */
	function summary($args=array()) {
		$defaults = array(
			'html' => false
		);

		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->summary)) {
			if ($this->dates()!='') {
				$short = $this->dates();
				if ($this->cities()!='') {
					$short .= ' '.__('in','wp_theatre').' '.$this->cities();
				}
				$short.='.';
			}
			$this->summary = $short.' '.$this->excerpt();
		}
		
		if ($args['html']) {
			$html = '';
			$html.= '<p class="'.self::post_type_name.'_summary">'.$this->summary.'</p>';
			return apply_filters('wpt_event_summary_html', $html, $this);				
		} else {
			return $this->summary;
		}
	}

	/**
	 * Production thumbnail.
	 * 
	 * Returns the production thumbnail as an ID or as an HTML element.
	 * The HTML version includes a link to the production page.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 *     @type bool $meta Return as invisible meta tag? Default <false>.
	 * }
	 * @return integer ID or string HTML.
	 */
	function thumbnail($args=array()) {
		$defaults = array(
			'html' => false,
			'meta' => false
		);
		$args = wp_parse_args( $args, $defaults );
		
		if (!isset($this->thumbnail)) {
			$this->thumbnail = get_post_thumbnail_id($this->ID);
		}	
	
		if ($args['html']) {
			$html = '';
			if ($args['meta']) {
				$thumbnail = wp_get_attachment_url($this->thumbnail);
				if (!empty($thumbnail)) {
					$html_thumbnail.= '<meta itemprop="image" content="'.$thumbnail.'" />';
				}
			} else {
				$attr = array(
					'itemprop'=>'image'
				);
				$thumbnail = get_the_post_thumbnail($this->ID,'thumbnail',$attr);					
				if (!empty($thumbnail)) {
					$html.= '<figure>';
					$permalink_args = $args;
					$permalink_args['text'] = $thumbnail;
					$html.= $this->permalink($permalink_args);
					$html.= '</figure>';
				}
			}
			return apply_filters('wpt_production_thumbnail_html', $html, $this);
		} else {
			return $this->thumbnail;			
		}
	}

	/**
	 * Event title.
	 * 
	 * Returns the event title as plain text or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 *     @type bool $meta Return as invisible meta tag? Default <false>.
	 * }
	 * @return string text or HTML.
	 */
	function title($args=array()) {
		$defaults = array(
			'html' => false,
			'meta' => false
		);
		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->title)) {
			$this->title = apply_filters('wpt_event_title',$this->post()->post_title,$this);
		}	
		if ($args['html']) {
			$html = '';
			if ($args['meta']) {
				$html.= '<meta itemprop="summary" content="'.$this->title.'" />';
				$html.= '<meta itemprop="url" content="'.$this->permalink().'" />';					
			} else {
				$html.= '<div class="'.self::post_type_name.'_title">';
				$permalink_args = $args;
				$permalink_args['text'] = '<span itemprop="summary">'.$this->title.'</span>';
				$html.= $this->permalink($permalink_args);
				$html.= '</div>'; //.title								
			}
			return apply_filters('wpt_event_title_html', $html, $this);
		} else {
			return $this->title;			
		}
	}

	function upcoming() {
		global $wp_theatre;
		if (!isset($this->upcoming)) {
			$args = array(
				WPT_Production::post_type_name => $this->ID
			);
			$this->upcoming = $wp_theatre->events->upcoming($args);
		}
		return $this->upcoming;
	}

	/**
	 * HTML version of the production.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *
	 *	   @type array $fields Fields to include. Default <array('title','remark', 'datetime','location')>.
	 *     @type array $hide Fields that should be included as invisible meta elements. Default <array()>
	 *     @type bool $thumbnail Include thumbnail? Default <true>.
	 *     @type bool $tickets Include tickets button? Default <true>.
	 * }
	 * @return string HTML.
	 */
	function html($args=array()) {
		global $wp_theatre;
		
		$defaults = array(
			'fields' => array('title','dates','cities'),
			'thumbnail' => true,
			'hide' => array()
		);
		$args = wp_parse_args( $args, $defaults );

		$html = '';
		
		$classes = array();
		$classes[] = self::post_type_name;

		// Thumbnail
		$thumbnail = false;
		if ($args['thumbnail']) {
			$thumbnail_args = array(
				'html'=>true,
				'meta'=>in_array('thumbnail', $args['hide'])
			);
			$thumbnail = $this->thumbnail($thumbnail_args);
		}
		if (empty($thumbnail)) {
			$classes[] = self::post_type_name.'_without_thumbnail';
		} else {
			$html.= $thumbnail;
		}

		$html.= '<div class="'.self::post_type_name.'_main">';
		foreach ($args['fields'] as $field) {
			$field_args = array(
				'html'=>true
			);
			switch ($field) {
				case 'title':
					$html.= $this->title($field_args);
					break;
				case 'dates':
					$html.= $this->dates($field_args);
					break;
				case 'cities':
					$html.= $this->cities($field_args);
					break;
				case 'excerpt':
					$html.= $this->excerpt($field_args);
					break;
				case 'summary':
					$html.= $this->summary($field_args);
					break;
			}
		}
		$html.= '</div>'; // .main

		// Microdata for events
		$args = array(
			WPT_Production::post_type_name => $this->ID
		);
		$html.= $wp_theatre->events->meta_listing($args);

		// Wrapper
		$html = '<div class="'.implode(' ',$classes).'">'.$html.'</div>';
		
		return apply_filters('wpt_production_html',$html, $this);		
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
		
		$thumbnail = wp_get_attachment_url($this->thumbnail());

		if (!empty($wp_theatre->wpt_social_options['social_meta_tags']) && is_array($wp_theatre->wpt_social_options['social_meta_tags'])) {
			foreach ($wp_theatre->wpt_social_options['social_meta_tags'] as $option) {
				switch ($option) {
					case 'facebook':
						$meta[] = '<!-- Open Graph data -->';	
						$meta[] = '<meta property="og:title" content="'.$this->title().'" />';
						$meta[] = '<meta property="og:type" content="article" />';
						$meta[] = '<meta property="og:url" content="'.$this->permalink().'" />';
						if (!empty($thumbnail)) {
							$meta[] = '<meta property="og:image" content="'.$thumbnail.'" />';
						}
						$meta[] = '<meta property="og:description" content="'.$this->summary().'" />';
						$meta[] = '<meta property="og:site_name" content="'.get_bloginfo('site_name').'" />';
						break;
					case 'twitter':
						$meta[] = '<!-- Twitter Card data -->';	
						$meta[] = '<meta name="twitter:card" content="summary">';
						$meta[] = '<meta name="twitter:title" content="'.$this->title().'">';
						$meta[] = '<meta name="twitter:description" content="'.$this->summary().'">';
						if (!empty($thumbnail)) {
							$meta[] = '<meta name="twitter:image:src" content="'.$thumbnail.'" />';
						}
						break;
					case 'google+':
						$meta[] = '<!-- Schema.org markup for Google+ -->';	
						$meta[] = '<meta itemprop="name" content="'.$this->title().'">';
						$meta[] = '<meta itemprop="description" content="'.$this->summary().'">';
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
	
	function render() {
		return $this->html();
	}
	
	function get_events() {
		return $this->events();
	}
	

}

?>