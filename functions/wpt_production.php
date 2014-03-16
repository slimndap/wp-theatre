<?php
class WPT_Production {

	const post_type_name = 'wp_theatre_prod';
	
	function __construct($ID=false) {	
		if ($ID instanceof WP_Post) {
			// $ID is a WP_Post object
			$this->post = $ID;
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

	function categories($args=array()) {
		$defaults = array(
			'html' => false
		);

		$args = wp_parse_args( $args, $defaults );
		
		if (!isset($this->categories)) {
			$this->categories = apply_filters('wpt_production_categories',wp_get_post_categories($this->ID),$this);
		}
		
		if ($args['html']) {
			if (!empty($this->categories)) {
				$html = '';
				$html.= '<ul class="wpt_production_categories">';
				foreach ($this->categories as $category_id) {
					$category = get_category( $category_id );
					$html.= '<li>'.$category->name.'</li>';
				}
				$html.= '</ul>';
				return apply_filters('wpt_production_categories_html', $html, $this);
			}
		} else {
			return $this->categories;
		}
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
			if (is_array($upcoming) && (count($upcoming)>0)) {
				$events = $this->events();
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
			$filters = array(
				'production'=>$this->ID,
			);			
			$this->events = $wp_theatre->events($filters);
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
			$excerpt = apply_filters('get_the_excerpt', $this->post()->post_excerpt);
			if (empty($excerpt)) {
				 $excerpt = wp_trim_words($this->post()->post_content, $args['words']);
			}
			$this->excerpt = apply_filters('wpt_production_excerpt',$excerpt, $this);

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
			$filters = array(
				'production' => $this->ID,
				'past' => true
			);
			$this->past = $wp_theatre->events($filters);
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
			$html.= '<a href="'.get_permalink($this->ID).'">';
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
	 * }
	 * @return integer ID or string HTML.
	 */
	function thumbnail($args=array()) {
		$defaults = array(
			'html' => false,
			'size' => 'thumbnail'
		);
		$args = wp_parse_args( $args, $defaults );
		
		if (!isset($this->thumbnails[$args['size']])) {
			$this->thumbnails[$args['size']] = get_post_thumbnail_id($this->ID,$args['size']);
		}	
	
		if ($args['html']) {
			$html = '';
			$thumbnail = get_the_post_thumbnail($this->ID,$args['size']);					
			if (!empty($thumbnail)) {
				$html.= '<figure>';
				$permalink_args = $args;
				$permalink_args['text'] = $thumbnail;
				$html.= $this->permalink($permalink_args);
				$html.= '</figure>';
			}
			return apply_filters('wpt_production_thumbnail_html', $html, $this);
		} else {
			return $this->thumbnails[$args['size']];			
		}
	}

	/**
	 * Production title.
	 * 
	 * Returns the production title as plain text or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string text or HTML.
	 */
	function title($args=array()) {
		$defaults = array(
			'html' => false
		);
		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->title)) {
			$this->title = apply_filters('wpt_event_title',$this->post()->post_title,$this);
		}	
		if ($args['html']) {
			$html = '';
			$html.= '<div class="'.self::post_type_name.'_title">';
			$permalink_args = $args;
			$permalink_args['text'] = $this->title;
			$html.= $this->permalink($permalink_args);
			$html.= '</div>'; //.title								
			return apply_filters('wpt_event_title_html', $html, $this);
		} else {
			return $this->title;			
		}
	}

	function upcoming() {
		global $wp_theatre;
		if (!isset($this->upcoming)) {
			$filters = array(
				'production' => $this->ID,
				'upcoming' => true
			);
			$this->upcoming = $wp_theatre->events($filters);
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
	 *	   @type array $fields Fields to include. Default <array('title','dates','cities')>.
	 *     @type bool $thumbnail Include thumbnail? Default <true>.
	 * }
	 * @return string HTML.
	 */
	function html($args=array()) {
		global $wp_theatre;
		
		$defaults = array(
			'template' => '{{thumbnail}} {{title}} {{dates}} {{cities}}'
		);
		$args = wp_parse_args( $args, $defaults );

		$html = $args['template'];
		
		$classes = array();
		$classes[] = self::post_type_name;

		// Thumbnail
		if (strpos($html,'{{thumbnail}}')!==false) { 
			$thumbnail_args = array(
				'html'=>true
			);
			$thumbnail = $this->thumbnail($thumbnail_args);
			$html = str_replace('{{thumbnail}}', $thumbnail, $html);
		}
		if (empty($thumbnail)) {
			$classes[] = self::post_type_name.'_without_thumbnail';
		}

		$field_args = array(
			'html'=>true
		);
		if (strpos($html,'{{title}}')!==false) { $html = str_replace('{{title}}', $this->title($field_args), $html); }
		if (strpos($html,'{{dates}}')!==false) { $html = str_replace('{{dates}}', $this->dates($field_args), $html); }
		if (strpos($html,'{{cities}}')!==false) { $html = str_replace('{{cities}}', $this->cities($field_args), $html); }
		if (strpos($html,'{{excerpt}}')!==false) { $html = str_replace('{{excerpt}}', $this->excerpt($field_args), $html); }
		if (strpos($html,'{{summary}}')!==false) { $html = str_replace('{{summary}}', $this->title($field_args), $html); }
		if (strpos($html,'{{categories}}')!==false) { $html = str_replace('{{categories}}', $this->categories($field_args), $html); }

		// Microdata for events
		if (!is_singular(WPT_Production::post_type_name)) {		
			$filters = array(
				'production' => $this->ID
			);
			$html.= $wp_theatre->events->meta($filters);
		}

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
			$this->post = get_post($this->ID);
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