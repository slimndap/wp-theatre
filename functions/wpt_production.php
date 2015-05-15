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
					$html.= '<li class="wpt_production_category wpt_production_category_'.$category->slug.'">'.$category->name.'</li>';
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
		global $wp_theatre;
		
		$defaults = array(
			'html' => false,
			'filters' => array()
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
			$this->cities = apply_filters('wpt_production_cities',$cities_text, $this);
		}
		if ($args['html']) {
			$html = '';
			$html.= '<div class="'.self::post_type_name.'_cities">';
			$html.= $wp_theatre->filter->apply($this->cities, $args['filters'], $this);
			$html.= '</div>';
			return apply_filters('wpt_production_cities_html', $html, $this);				
		} else {
			return $this->cities;
		}
	}

	function content($args = array()) {
		global $wp_theatre;
		$defaults = array(
			'html' => false
		);

		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->content)) {
			$content = $this->post()->post_content;
			$this->content = apply_filters('wpt_production_content',$content, $this);

		}

		if ($args['html']) {
			
			/*
			 * Temporarily unhook other Theater filters that hook into `the_content`
			 * to avoid loops.
			 */
			 
			remove_filter('the_content', array($wp_theatre->frontend, 'the_content'));
			remove_filter('the_content', array($wp_theatre->listing_page, 'the_content'));

			$html = '';
			$html.= '<div class="'.self::post_type_name.'_content">';
			$html.= apply_filters('the_content',$this->content);
			$html.= '</div>';
			
			/*
			 * Re-hook other Theater filters that hook into `the_content`.
			 */

			add_filter('the_content', array($wp_theatre->frontend, 'the_content'));
			add_filter('the_content', array($wp_theatre->listing_page, 'the_content'));

			return apply_filters('wpt_production_content_html', $html, $this);				
		} else {
			return $this->content;				
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
		global $wp_theatre;
		$defaults = array(
			'html' => false,
			'filters' => array()
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
			$this->dates = apply_filters('wpt_production_dates',$dates, $this);
		}
		
		if ($args['html']) {
			$html = '';
			$html.= '<div class="'.self::post_type_name.'_dates">';
			$html.= $wp_theatre->filter->apply($this->dates, $args['filters'], $this);
			$html.= '</div>';
			return apply_filters('wpt_production_dates_html', $html, $this);				
		} else {
			return $this->dates;
		}
	}

	function events($filters = array()) {
		global $wp_theatre;

		$defaults = array(
			'production'=>$this->ID,
			'status'=>$this->post()->post_status
		);			

		$filters = wp_parse_args( $filters, $defaults );

		if (!isset($this->events)) {
			$this->events = $wp_theatre->events->get($filters);
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
		global $wp_theatre;

		$defaults = array(
			'html' => false,
			'words' => 15,
			'filters' => array()
		);

		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->excerpt)) {
			$excerpt = $this->post()->post_excerpt;
			if (empty($excerpt)) {
				 $excerpt = wp_trim_words(strip_shortcodes($this->post()->post_content), $args['words']);
			}
			$this->excerpt = apply_filters('wpt_production_excerpt',$excerpt, $this);

		}

		if ($args['html']) {
			$html = '';
			$html.= '<p class="'.self::post_type_name.'_excerpt">';
			$html.= $wp_theatre->filter->apply($this->excerpt, $args['filters'], $this);
			$html.= '</p>';
			return apply_filters('wpt_production_excerpt_html', $html, $this);				
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
			$this->past = $wp_theatre->events->get($filters);
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
	 *     @type bool $inside Try to place de link inside the surrounding div. Default <false>.
	 * }
	 * @return string URL or HTML.
	 */
	function permalink($args=array()) {
		$defaults = array(
			'html' => false,
			'text' => $this->title(),
			'inside' => false
		);

		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->permalink)) {
			$this->permalink = apply_filters('wpt_production_permalink',get_permalink($this->ID), $this);
		}

		if ($args['html']) {
			$html = '';

			if ($args['inside']) {
				$text_sanitized = trim($args['text']);

				$before = '';
				$after = '';
				$text = $args['text'];				
				
				$elements = array('div','figure');
				foreach ($elements as $element) {
					if (
						$args['inside'] &&
						strpos($text_sanitized, '<'.$element) === 0 &&
						strrpos($text_sanitized, '</'.$element) === strlen($text_sanitized) - strlen($element) - 3
					) {
						$before = substr($args['text'], 0, strpos($args['text'], '>') + 1);
						$after = '</'.$element.'>';
						$text = substr($args['text'], strpos($args['text'], '>') + 1, strrpos($args['text'],'<') - strpos($args['text'], '>') - 1);
						continue;
					}					
				}
				$inside_args = array(
					'html'=>true,
					'text'=>$text
				);					
				return $before.$this->permalink($inside_args).$after;
			} else {
				$html.= '<a href="'.get_permalink($this->ID).'">';
				$html.= $args['text'];
				$html.= '</a>';				
			}
			return apply_filters('wpt_production_permalink_html', $html, $this);				
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
		global $wp_theatre;
		
		$defaults = array(
			'html' => false,
			'filters' => array()
		);

		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->summary)) {
			$this->summary = '';
			if ($this->dates()!='') {
				$short = $this->dates();
				if ($this->cities()!='') {
					$short .= ' '.__('in','wp_theatre').' '.$this->cities();
				}
				$short.='. ';
				$this->summary .= ucfirst($short);
			}
			$this->summary .= $this->excerpt();
		}
		
		if ($args['html']) {
			$html = '';
			$html.= '<p class="'.self::post_type_name.'_summary">';
			$html.= $wp_theatre->filter->apply($this->summary, $args['filters'], $this);
			$html.= '</p>';

			return apply_filters('wpt_production_summary_html', $html, $this);				
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
	 *     @type bool 		$html 		Return HTML? Default <false>.
	 *     @type string 	$size 		Either a string keyword (thumbnail, medium, large or full) or 
	 *									a 2-item array representing width and height in pixels, 
	 *									e.g. array(32,32). 
	 * 									Default 'thumbnail'.
	 *     @type array 		$filters 	Default array().
	 * }
	 * @return integer ID or string HTML.
	 */
	function thumbnail($args=array()) {
		global $wp_theatre;
		
		$defaults = array(
			'html' => false,
			'size' => 'thumbnail',
			'filters' => array()
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
				$html.= $wp_theatre->filter->apply($thumbnail, $args['filters'], $this);
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
		global $wp_theatre;
		
		$defaults = array(
			'html' => false,
			'filters' => array()
		);
		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->title)) {
			$post = $this->post();
			if (empty($post)) {
				$title = '';
			} else {
				$title = $this->post()->post_title;
			}
			$this->title = apply_filters('wpt_production_title', $title, $this);
		}
		
		if ($args['html']) {
			$html = '';
			$html.= '<div class="'.self::post_type_name.'_title">';
			$html.= $wp_theatre->filter->apply($this->title, $args['filters'], $this);
			$html.= '</div>';
			return apply_filters('wpt_production_title_html', $html, $this);
		} else {
			return $this->title;			
		}
	}

    /**
     * Returns value of a custom field.
     *
     * @since 0.8
     *
     * @param array $args {
     *     @type string $field custom field name.
     * }
     * @return string.
     */
	function custom($field, $args=array()) {
		global $wp_theatre;
		
		$defaults = array(
			'html' => false,
			'filters' => array()
		);
		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->{$field})) {
			$this->{$field} = apply_filters(
				'wpt_production_'.$field, 
				get_post_meta($this->post()->ID, $field, true),
				$field,
				$this
			);
		}

		if ($args['html']) {
			$html = '';
			$html.= '<div class="'.self::post_type_name.'_'.$field.'">';
			$html.= $wp_theatre->filter->apply($this->{$field}, $args['filters'], $this);
			$html.= '</div>';

			return apply_filters('wpt_production_'.$field.'_html', $html, $this);
		} else {
			return $this->{$field};
		}
	}

	function upcoming() {
		global $wp_theatre;
		if (!isset($this->upcoming)) {
			$filters = array(
				'upcoming' => true
			);
			$this->upcoming = $this->events($filters);
		}
		return $this->upcoming;
	}

	/**
	 * HTML version of the production.
	 *
	 * @since 0.4
	 * @since 0.10.8	Added a filter to the default template.
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
			'template' => apply_filters(
				'wpt_production_template_default',
				'{{thumbnail|permalink}} {{title|permalink}} {{dates}} {{cities}}'
			),
		);

		$args = wp_parse_args( $args, $defaults );
		$html = $args['template'];

		$classes = array();
		$classes[] = self::post_type_name;

		// Parse template
		$placeholders = array();
		preg_match_all('~{{(.*?)}}~', $html, $placeholders);
		foreach($placeholders[1] as $placeholder) {

			$field = '';
			$filters = array();

			$placeholder_parts = explode('|',$placeholder);
			if (!empty($placeholder_parts[0])) {
				$field = $placeholder_parts[0];
			}
			if (!empty($placeholder_parts[1])) {
				$filters = $placeholder_parts;
				array_shift($filters);
			}

			switch($field) {
				case 'title':
				case 'dates':
				case 'cities':
				case 'content':
				case 'excerpt':
				case 'summary':
				case 'categories':
				case 'thumbnail':
					$replacement = $this->{$field}(array('html'=>true, 'filters'=>$filters));
					break;
				default:
					$replacement = $this->custom($field,array('html'=>true, 'filters'=>$filters));
			}
			$html = str_replace('{{'.$placeholder.'}}', $replacement, $html);
		}

		// Filters
		$html = apply_filters('wpt_production_html',$html, $this);
		$classes = apply_filters('wpt_production_classes',$classes, $this);

		// Wrapper
		$html = '<div class="'.implode(' ',$classes).'">'.$html.'</div>';
		
		return $html;		
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