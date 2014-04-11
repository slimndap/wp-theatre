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
	 * }
	 * @return string text or HTML.
	 */
	function date($args=array()) {
		$defaults = array(
			'html' => false,
			'start' => true
		);
		$args = wp_parse_args( $args, $defaults );

		if ($args['start']) {
			$field = 'event_date';
		} else {
			$field = 'enddate';
		}
		if (!isset($this->date[$field])) {
			$datetime_args = array('start'=>$args['start']);
			$this->date[$field] = apply_filters('wpt_event_date',date_i18n(get_option('date_format'),$this->datetime($datetime_args)),$this);
		}	
		if ($args['html']) {
			$html= '<div class="'.self::post_type_name.'_date">'.$this->date[$field].'</div>';
			return apply_filters('wpt_event_date_html', $html, $this);
		} else {
			return $this->date[$field];			
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
			'start' => true
		);
		$args = wp_parse_args( $args, $defaults );

		if ($args['start']) {
			$field = 'event_date';
		} else {
			$field = 'enddate';
		}

		if (!isset($this->datetime[$field])) {
			$this->datetime[$field] = apply_filters('wpt_event_datetime',date_i18n('U',strtotime($this->post()->{$field}),true), $this);
		}
		
		if ($args['html']) {
			$html = '';
			$html.= '<div class="'.self::post_type_name.'_datetime">';
			$html.= $this->date($args);
			$html.= $this->time($args);
			$html.= '</div>';
			return $html;
		} else {
			return $this->datetime[$field];				
		}
	}
	
	function duration($args=array()) {
		$defaults = array(
			'html' => false
		);
		$args = wp_parse_args( $args, $defaults );
		if (
			!isset($this->duration) && 
			!empty($this->post()->enddate) &&
			$this->post()->enddate > $this->post()->event_date
		) {
			
			// Don't use human_time_diff until filters are added.
			// See: https://core.trac.wordpress.org/ticket/27271
			// $this->duration = apply_filters('wpt_event_duration',human_time_diff(strtotime($this->post()->enddate), strtotime($this->post()->event_date)),$this);
			$seconds = abs(strtotime($this->post()->enddate) - strtotime($this->post()->event_date));
			$minutes = (int) $seconds/60;
			$text = $minutes.' '._n('minute','minutes', $minutes, 'wp_theatre');
			$this->duration = apply_filters('wpt_event_duration',$text,$this);
		}
		if ($args['html']) {
			$html = '';
			$html.= '<div class="'.self::post_type_name.'_duration">'.$this->duration.'</div>';
			return $html;
		} else {
			return $this->duration;				
		}
		return $this->duration;		
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
			$html.= '<div class="'.self::post_type_name.'_location">';
			if (!empty($venue)) {
				$html.= '<div class="'.self::post_type_name.'_venue">'.$this->venue().'</div>';
			}
			if (!empty($city)) {
				$html.= '<div class="'.self::post_type_name.'_city" >'.$this->city().'</div>';
			}
			$html.= '</div>'; // .location
			return apply_filters('wpt_event_location_html', $html, $this);
		} else {
			return $this->location;			
		}
	}
	
	function meta() {
		$html = '';
		
		$html.= '<span itemscope itemtype="http://data-vocabulary.org/Event">';	

		// image
		// Thumbnail
		if ($this->production()->thumbnail()!='') {
			$html.= '<meta itemprop="image" content="'.wp_get_attachment_url($this->production()->thumbnail()).'" />';
		}

		// startDate
		$html.= '<meta itemprop="startDate" content="'.date('c',$this->datetime()).'" />';
		
		// summary
		$html.= '<meta itemprop="summary" content="'.$this->production()->title().'" />';
		
		// url
		$html.= '<meta itemprop="url" content="'.get_permalink($this->production()->ID).'" />';

		//location
		$html.= '<span itemprop="location" itemscope itemtype="http://data-vocabulary.org/Organization">';
		if ($this->venue()!='') {
			$html.= '<meta itemprop="name" content="'.$this->venue().'" />';
		}
		if ($this->city()!='') {
			$html.= '<span itemprop="address" itemscope itemtype="http://data-vocabulary.org/Address">';
			$html.= '<meta itemprop="locality" content="'.$this->city().'" />';
			$html.= '</span>';
		}
		
		$html.= '</span>'; // .location

		$html.= '</span>';
		
		return $html;
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
			$this->prices = apply_filters('wpt_event_tickets_prices',get_post_meta($this->ID,'_wpt_event_tickets_price'), $this);
		}

		if ($args['html']) {
			$prices_args = array(
				'summary' => $args['summary']
			);
			$html = $this->prices($prices_args);
			
			if (!empty($html)) {
				$html= '<div class="'.self::post_type_name.'_prices">'.$html.'</div>';
				
			}
			return apply_filters('wpt_event_tickets_prices_html', $html, $this);				
		} else {
			if ($args['summary']) {
				$summary = '';
				if (count($this->prices)>0) {
					if (count($this->prices)==1) {
						$summary = $wp_theatre->options['currencysymbol'].'&nbsp;'.number_format_i18n($this->prices[0],2);
					} else {
						$lowest = $this->prices[0];
						for($p=1;$p<count($this->prices);$p++) {
							if ($lowest > $this->prices[$p]) {
								$lowest = $this->prices[$p];
							}
						}
						$summary = __('from','wp_theatre').' '.$wp_theatre->options['currencysymbol'].'&nbsp;'.number_format_i18n($lowest,2);
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
			'text' => false
		);

		$args = wp_parse_args( $args, $defaults );

		if (!isset($this->remark)) {
			$this->remark = apply_filters('wpt_event_remark',get_post_meta($this->ID,'remark',true), $this);
		}

		if ($args['html'] && !empty($this->remark)) {
			$html = '';
			$html.= '<div class="'.self::post_type_name.'_remark">'.$this->remark.'</div>';
			return apply_filters('wpt_event_remark_html', $html, $this);				
		} else {
			return $this->remark;				
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
			if (!empty($wp_theatre->options['integrationtype']) && $wp_theatre->options['integrationtype']=='iframe') {
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
			
			$status = get_post_meta($this->ID,'tickets_status',true);
			if (!empty($status)) {
				$html.= '<span class="'.self::post_type_name.'_tickets_status '.self::post_type_name.'_tickets_status_'.$status.'">'.__($status, 'wp_theatre').'</span>';
				
			} else {
				if (!empty($this->tickets)) {
					$html.= '<a href="'.$this->tickets.'" rel="nofollow"';
					
					// Add classes to tickets button
					$classes = array();
					$classes[] = self::post_type_name.'_tickets_url';
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
	 * }
	 * @return string text or HTML.
	 */
	function time($args=array()) {
		$defaults = array(
			'html' => false,
			'start' => true
		);
		$args = wp_parse_args( $args, $defaults );

		if ($args['start']) {
			$field = 'event_date';
		} else {
			$field = 'enddate';
		}
		
		if (!isset($this->time[$field])) {
			$datetime_args = array('start'=>$args['start']);
			$this->time[$field] = apply_filters('wpt_event_time',date_i18n(get_option('time_format'),$this->datetime($datetime_args)),$this);
		}	
		if ($args['html']) {
			$html= '<div class="'.self::post_type_name.'_time">'.$this->time[$field].'</div>';
			return apply_filters('wpt_event_time_html', $html, $this);
		} else {
			return $this->time[$field];			
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
	 *     @type bool $thumbnail Include thumbnail? Default <true>.
	 *     @type bool $tickets Include tickets button? Default <true>.
	 * }
	 * @return string HTML.
	 */
	function html($args=array()) {
		$defaults = array(
			'template' => '{{thumbnail}} {{title}} {{remark}} {{datetime}} {{location}} {{tickets}}'
		);
		$args = wp_parse_args( $args, $defaults );

		$classes = array();
		$classes[] = self::post_type_name;

		$html = $args['template'];

		// Thumbnail
		if (strpos($html,'{{thumbnail}}')!==false) { 
			$thumbnail_args = array(
				'html'=>true
			);
			$thumbnail = $this->production()->thumbnail($thumbnail_args);
			$html = str_replace('{{thumbnail}}', $thumbnail, $html);
		}
		if (empty($thumbnail)) {
			$classes[] = self::post_type_name.'_without_thumbnail';
		}

		$field_args = array(
			'html'=>true
		);
		if (strpos($html,'{{date}}')!==false) { $html = str_replace('{{date}}', $this->date($field_args), $html); }
		if (strpos($html,'{{datetime}}')!==false) { $html = str_replace('{{datetime}}', $this->datetime($field_args), $html); }
		if (strpos($html,'{{duration}}')!==false) { $html = str_replace('{{duration}}', $this->duration($field_args), $html); }
		if (strpos($html,'{{location}}')!==false) { $html = str_replace('{{location}}', $this->location($field_args), $html); }
		if (strpos($html,'{{remark}}')!==false) { $html = str_replace('{{remark}}', $this->remark($field_args), $html); }
		if (strpos($html,'{{time}}')!==false) { $html = str_replace('{{time}}', $this->time($field_args), $html); }
		if (strpos($html,'{{title}}')!==false) { $html = str_replace('{{title}}', $this->production()->title($field_args), $html); }
		if (strpos($html,'{{categories}}')!==false) { $html = str_replace('{{categories}}', $this->production()->categories($field_args), $html); }

		// Tickets
		if (strpos($html,'{{tickets}}')!==false) { 
			$tickets_args = array(
				'html'=>true
			);
			$tickets = $this->tickets($tickets_args);
			if (empty($tickets)) {
				$classes[] = self::post_type_name.'_without_tickets';
			}
			$html = str_replace('{{tickets}}', $tickets, $html);
		}
		
		$html.= $this->meta();

		// Filters
		$html = apply_filters('wpt_event_html',$html, $this);
		$classes = apply_filters('wpt_event_classes',$classes, $this);
		
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