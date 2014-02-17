<?php

/** Usage:
 *
 *  $event = new WPT_Event(); 
 *  $event = new WPT_Event($post_id); 
 *  $event = new WPT_Event($post); 
 *
 *	echo $event->html(); // output the details of an event as HTML
 *
 *	echo $event->prices( array('summary'=>true) ) // // a summary of all available ticketprices
 *	echo $event->datetime() // timestamp of the event
 *	echo $event->date() // localized and formatted date of the event
 *	echo $event->time() // localized and formatted time of the event
 *
 */

class WPT_Event {

	const post_type_name = 'wp_theatre_event';
	
	function __construct($ID=false, $PostClass=false) {
		$this->PostClass = $PostClass;
	
		if ($ID instanceof WP_Post) {
			// $ID is a WP_Post object
			if (!$PostClass) {
				$this->post = $ID;
			}
			$ID = $ID->ID;
		}

		$this->ID = $ID;
		
		$this->format = 'full';		
	}

	function post_type() {
		return get_post_type_object(self::post_type_name);
	}
	
	function post_class() {
		$classes = array();
		$classes[] = self::post_type_name;		
		return implode(' ',$classes);
	}
	
	/**
	 * Event city.
	 *
	 * @since 0.4
	 *
	 * @return string City.
	 */
	function city() {
		if (!isset($this->city)) {
			$this->city = apply_filters('wpt_event_venue',get_post_meta($this->ID,'city',true),$this);
		}	
		return $this->city;			
	}
	
	/**
	 * Event date.
	 * 
	 * Returns the event date as plain text or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 *     @type bool $meta Return as invisible meta tag? Default <false>.
	 * }
	 * @return string text or HTML.
	 */
	function date($args=array()) {
		$defaults = array(
			'html' => false,
			'meta' => false
		);
		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->date)) {
			$this->date = apply_filters('wpt_event_date',date_i18n(get_option('date_format'),$this->datetime()),$this);
		}	
		if ($args['html']) {
			$html= '<span class="'.self::post_type_name.'_date">'.$this->date().'</span>';
			return apply_filters('wpt_event_date_html', $html, $this);
		} else {
			return $this->date;			
		}
	}
	
	/**
	 * Event date and time.
	 * 
	 * Returns the event date and time combined as plain text or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 *     @type bool $meta Return as invisible meta tag? Default <false>.
	 * }
	 *
	 * @see WPT_Event::date().
	 * @see WPT_Event::time().
	 *
	 * @return string text or HTML.
	 */
	function datetime($args=array()) {
		$defaults = array(
			'html' => false,
			'meta' => false
		);
		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->datetime)) {
			$this->datetime = apply_filters('wpt_event_datetime',strtotime($this->post()->event_date), $this);
		}
		
		if ($args['html']) {
			$html = '';
			$html.= '<time class="'.self::post_type_name.'_datetime" itemprop="startDate" datetime="'.date('c',$this->datetime()).'">';
			$html.= $this->date($args);
			$html.= $this->time($args);
			$html.= '</time>';
			return apply_filters('wpt_event_datetime_html', $html, $this);				
		} else {
			return $this->datetime;				
		}
	}
	
	/**
	 * Event location.
	 * 
	 * Returns the event venue and city combined as plain text or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 *     @type bool $meta Return as invisible meta tag? Default <false>.
	 * }
	 *
	 * @see WPT_Event::venue().
	 * @see WPT_Event::city().
	 *
	 * @return string text or HTML.
	 */
	function location($args=array()) {
		$defaults = array(
			'html' => false,
			'meta' => false
		);
		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->location)) {
			$location = '';
			$venue = $this->venue();
			$city = $this->city();
			if (!empty($venue)) {
				$location.=$this->venue();
			}
			if (!empty($city)) {
				if (!empty($venue)) {
					$location.= ' ';
				}
				$location.=$this->city();
			}
			$this->location = apply_filters('wpt_event_location',$location,$this);
		}	
		if ($args['html']) {
			$venue = $this->venue();
			$city = $this->city();
			$html = '';
			$html.= '<div class="'.self::post_type_name.'_location" itemprop="location" itemscope itemtype="http://data-vocabulary.org/Organization">';
			if ($args['meta']) {
				if (!empty($venue)) {
					$html.= '<meta itemprop="name" content="'.$this->venue().'" />';
				}
				if (!empty($city)) {
					$html.= '<span itemprop="address" itemscope itemtype="http://data-vocabulary.org/Address">';
					$html.= '<meta itemprop="locality" content="'.$this->city().'" />';
					$html.= '</span>';
				}
			} else {
				if (!empty($venue)) {
					$html.= '<div itemprop="name">'.$this->venue().'</div>';
				}
				if (!empty($city)) {
					$html.= '<div itemprop="address" itemscope itemtype="http://data-vocabulary.org/Address">';
					$html.= '<span itemprop="locality">'.$this->city().'</span>';
					$html.= '</div>';
				}
			}
			$html.= '</div>'; // .location
			return apply_filters('wpt_event_location_html', $html, $this);
		} else {
			return $this->location;			
		}
	}
	
	/**
	 * Event permalink.
	 * 
	 * Returns a link to the production page of the event as a URL or as an HTML element.
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
			'text' => $this->production()->post()->post_title
		);

		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->permalink)) {
			$this->permalink = apply_filters('wpt_event_permalink',get_permalink($this->production()->ID), $this);
		}

		if ($args['html']) {
			$html = '';
			$html.= '<a itemprop="url" href="'.get_permalink($this->production()->ID).'">';
			$html.= $args['text'];
			$html.= '</a>';
			return apply_filters('wpt_event_permalink_html', $html, $this);				
		} else {
			return $this->permalink;				
		}
	}
	
	/**
	 * Event prices.
	 * 
	 * Returns the event prices as an array or as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 *     @type bool $summary Return a summary of all prices in a single line? Default <false>.
	 * }
	 * @return array Prices or string HTML.
	 */
	function prices($args=array()) {
		global $wp_theatre;
		$defaults = array(
			'html' => false,
			'summary' => false
		);

		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->prices)) {
			$this->prices = apply_filters('wpt_event_prices',get_post_meta($this->ID,'price',false), $this);
		}

		if ($args['html']) {
			$html = '';
			$html.= '<div class="'.self::post_type_name.'_prices">';
			$prices_args = array(
				'summary' => $args['summary']
			);
			$html.= $this->prices($prices_args);
			$html.= '</div>';
			return apply_filters('wpt_event_permalink_html', $html, $this);				
		} else {
			if ($args['summary']) {
				$summary = '';
				if (count($this->prices)>0) {
					if (count($this->prices)==1) {
						$summary = $wp_theatre->options['currencysymbol'].'&nbsp;'.$this->prices[0]->price;
					} else {
						$lowest = $this->prices[0]->price;
						for($p=1;$p<count($this->prices);$p++) {
							if ($lowest > $this->prices[$p]->price) {
								$lowest = $this->prices[$p]->price;
							}
						}
						$summary = __('from','wp_theatre').' '.$wp_theatre->options['currencysymbol'].'&nbsp;'.$this->prices[0]->price;
					}
				}
				return $summary;
			} else {
				return $this->prices;								
			}
		}
	}
	
	/**
	 * Event production.
	 * 
	 * Returns the production of the event as a WPT_Production object.
	 *
	 * @since 0.4
	 *
	 * @return WPT_Production Production.
	 */
	function production() {
		if (!isset($this->production)) {
			$this->production = new WPT_Production(get_post_meta($this->ID,WPT_Production::post_type_name, TRUE), $this->PostClass);
		}
		return $this->production;		
	}
	
	/**
	 * Event remark.
	 * 
	 * Returns the event remark as plain text of as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 * @return string text or HTML.
	 */
	function remark($args=array()) {
		$defaults = array(
			'html' => false,
			'meta' => false,
			'text' => false
		);

		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->remark)) {
			$this->remark = apply_filters('wpt_event_remark',get_post_meta($this->ID,'remark',true), $this);
		}

		if ($args['html']) {
			$html = '';
			$html.= '<div class="'.self::post_type_name.'_remark">'.$this->remark.'</div>';
			return apply_filters('wpt_event_remark_html', $html, $this);				
		} else {
			return $this->remark;				
		}
		
	}
	
	/**
	 * Event thumbnail.
	 * 
	 * Returns the event thumbnail as an ID or as an HTML element.
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
			$this->thumbnail = get_post_thumbnail_id($this->production()->ID);
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
				$thumbnail = get_the_post_thumbnail($this->production()->ID,'thumbnail',$attr);					
				if (!empty($thumbnail)) {
					$html.= '<figure>';
					$permalink_args = $args;
					$permalink_args['text'] = $thumbnail;
					$html.= $this->permalink($permalink_args);
					$html.= '</figure>';
				}
			}
			return apply_filters('wpt_event_thumbnail_html', $html, $this);
		} else {
			return $this->thumbnail;			
		}
	}

	/**
	 * Event ticket link.
	 * 
	 * Returns the event ticket link as plain text of as an HTML element.
	 * The HTML version includes a summary of the event prices.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 * }
	 *
	 * @see WPT_Event::prices().
	 *
	 * @return string text or HTML.
	 */
	function tickets($args=array()) {
		global $wp_theatre;
		
		$defaults = array(
			'html' => false
		);
		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->tickets)) {
			if ($wp_theatre->options['integrationtype']=='iframe') {
				$url = get_permalink($wp_theatre->options['iframepage']);
				$args = array(
					__('Event','wp_theatre') => $this->ID
				);
				$url = add_query_arg( $args , $url);
			} else {
				$url = get_post_meta($this->ID,'tickets_url',true);
			}
			$this->tickets = apply_filters('wpt_event_tickets',$url,$this);
		}	
		
		if ($args['html']) {
			$html = '<div class="'.self::post_type_name.'_tickets">';			
			if (get_post_meta($this->ID,'tickets_status',true) == 'soldout') {
				$html.= '<span class="'.self::post_type_name.'_soldout">'.__('Sold out', 'wp_theatre').'</span>';
			} else {
				if (!empty($this->tickets)) {
					$html.= '<a href="'.$this->tickets.'" rel="nofollow"';
					
					// Add classes to tickets button
					$classes = array();
					$classes[] = self::post_type_name.'_tickets_url';
					if (!empty($wp_theatre->options['ticket_button_tag']) && $wp_theatre->options['ticket_button_tag']=='button') {
						$classes[] = 'button';
					}
					if (!empty($wp_theatre->options['integrationtype'])) {
						$classes[] = 'wp_theatre_integrationtype_'.$wp_theatre->options['integrationtype'];
					}
					$classes = apply_filters('wpt_event_tickets_classes',$classes,$this);
					$html.= ' class="'.implode(' ' ,$classes).'"';
	
					$html.= '>';
	
					$text = get_post_meta($this->ID,'tickets_button',true);
					if ($text=='') {
						$text = __('Tickets','wp_theatre');			
					}
					$html.= $text;
					$html.= '</a>';						
				}
				
				$prices_args = array(
					'html'=>true,
					'summary'=>true
				);
				$html.= $this->prices($prices_args);
			}
	
			$html.= '</div>'; // .tickets		
			return apply_filters('wpt_event_tickets_html', $html, $this);
		} else {
			return $this->tickets;			
		}
	}

	/**
	 * Event time.
	 * 
	 * Returns the event time as plain text of as an HTML element.
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     @type bool $html Return HTML? Default <false>.
	 *     @type bool $meta Return as invisible meta tag? Default <false>.
	 * }
	 * @return string text or HTML.
	 */
	function time($args=array()) {
		$defaults = array(
			'html' => false,
			'meta' => false
		);
		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->time)) {
			$this->time = apply_filters('wpt_event_time',date_i18n(get_option('time_format'),$this->datetime()),$this);
		}	
		if ($args['html']) {
			$html= '<div class="'.self::post_type_name.'_time">'.$this->time().'</div>';
			return apply_filters('wpt_event_time_html', $html, $this);
		} else {
			return $this->time;			
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
			$this->title = apply_filters('wpt_event_title',$this->production()->post()->post_title,$this);
		}	
		if ($args['html']) {
			$html = '';
			if ($args['meta']) {
				$html.= '<meta itemprop="summary" content="'.$this->title.'" />';
				$html.= '<meta itemprop="url" content="'.$this->permalink().'" />';					
			} else {
				$html.= '<h4 class="'.self::post_type_name.'_title">';
				$permalink_args = $args;
				$permalink_args['text'] = '<span itemprop="summary">'.$this->title.'</span>';
				$html.= $this->permalink($permalink_args);
				$html.= '</h4>'; //.title								
			}
			return apply_filters('wpt_event_title_html', $html, $this);
		} else {
			return $this->title;			
		}
	}
	
	/**
	 * Event venue.
	 *
	 * @since 0.4
	 *
	 * @return string Venue.
	 */
	function venue() {
		if (!isset($this->venue)) {
			$this->venue = apply_filters('wpt_event_venue',get_post_meta($this->ID,'venue',true),$this);
		}	
		return $this->venue;			
	}
	
	/**
	 * HTML version of the event.
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
		$defaults = array(
			'fields' => array('title','remark', 'datetime','location'),
			'hide' => array(),
			'thumbnail' => true,
			'tickets' => true
		);
		$args = wp_parse_args( $args, $defaults );
		$classes = array();
		$classes[] = self::post_type_name;

		$html = '';
		
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
				'html'=>true,
				'meta'=>in_array($field, $args['hide'])				
			);
			switch ($field) {
				case 'datetime':
					$html.= $this->datetime($field_args);
					break;
				case 'title':
					$html.= $this->title($field_args);
					break;
				case 'location':
					$html.= $this->location($field_args);
					break;
				case 'remark':
					$html.= $this->remark($field_args);
					break;
			}
		}
		$html.= '</div>'; // .main

		// Tickets
		$tickets = false;
		if ($args['tickets']) {
			$tickets_args = array(
				'html'=>true,
				'meta'=>in_array('thumbnail', $args['hide'])
			);
			$tickets = $this->tickets($tickets_args);
		}
		if (empty($tickets)) {
			$classes[] = self::post_type_name.'_without_tickets';
		} else {
			$html.= $tickets;
		}

		// Wrapper
		$html = '<div class="'.implode(' ',$classes).'" itemscope itemtype="http://data-vocabulary.org/Event">'.$html.'</div>';
		
		return apply_filters('wpt_event_html',$html, $this);		
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

	/**
	 * HTML version of the event.
	 *
	 * @deprecated 0.4 Use $event->html() instead.
	 * @see $event->html()
	 *
	 * @return string HTML.
	 */
	function compile() {
		return $this->html();
	}
			
	/**
	 * Event production.
	 * 
	 * Returns the production of the event as a WPT_Production object.
	 *
	 * @deprecated 0.4 Use $event->production() instead.
	 * @see $event->production()
	 *
	 * @return WPT_Production Production.
	 */
	function get_production() {
		return $this->production();		
	}

	/**
	 * Echoes an HTML version of the event.
	 *
	 * @deprecated 0.4 Use echo $event->html() instead.
	 * @see $event->html()
	 *
	 * @return void.
	 */
	function render() {
		echo $this->html();		
	}

	/**
	 * Summary of the event. 
	 *
	 * An array of strings that can be used to summerize the event.  
	 * Currently only returns a summary of the event prices.
	 *
	 * @deprecated 0.4 Use $event->prices() instead.
	 * @see $event->prices()
	 *
	 * @return array Summary.
	 */
	function summary() {
		global $wp_theatre;
		if (!isset($this->summary)) {
			$args = array(
				'summary' => true
			);
			$this->summary = array(
				'prices' => $this->prices($args)
			);
		}		
		return $this->summary;
	}

	

}

?>