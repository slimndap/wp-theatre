<?php

/** Usage:
 *
 *  $event = new WPT_Event(); 
 *  $event = new WPT_Event($post_id); 
 *  $event = new WPT_Event($post); 
 *
 *	$event->render(); // output the details of an event as HTML
 *	echo $event->compile(); // or like this
 *
 *	$summary = $event->summary();
 *	echo $summary['prices'] // a summary of all available ticketprices.
 *
 *	echo $event->datetime // timestamp of the event
 *	echo $event->date // localized and formatted date of the event
 *	echo $event->time // localized and formatted time of the event
 *
 */

class WPT_Event {

	const post_type_name = 'wp_theatre_event';
	
	function __construct($ID=false, $PostClass=false) {
	
		if ($ID instanceof WP_Post) {
			// $ID is a WP_Post object
			if (!$PostClass) {
				$this->post = $ID;
			}
			$ID = $ID->ID;
		}

		$this->ID = $ID;		
	}

	function post_type() {
		return get_post_type_object(self::post_type_name);
	}
	
	function get_production() {
		if (!isset($this->production)) {
			$this->production = new WPT_Production(get_post_meta($this->ID,WPT_Production::post_type_name, TRUE), $this->PostClass);
		}
		return $this->production;		
	}
	
	function production() {
		return $this->get_production();
	}
	
	function datetime() {
		if (!isset($this->datetime)) {
			$this->datetime = strtotime($this->post()->event_date);
		}	
		return $this->datetime;	
	}

	function date() {
		if (!isset($this->date)) {
			$this->date = date_i18n(get_option('date_format'),$this->datetime());
		}	
		return $this->date;	
	}

	function time() {
		if (!isset($this->time)) {
			$this->time = date_i18n(get_option('time_format'),$this->datetime());
		}	
		return $this->time;
	}
	
	function prices() {
		if (!isset($this->prices)) {
			$this->prices = get_post_meta($this->ID,'price',false);
		}
		return $this->prices;
	}
	
	function summary() {
		global $wp_theatre;
		if (!isset($this->summary)) {
			$prices = $this->prices();
			$prices_summary = '';
			if (count($prices)>0) {
				if (count($prices)==1) {
					$prices_summary = $wp_theatre->options['currencysymbol'].'&nbsp;'.$prices[0]->price;
				} else {
					$prices_lowest = $prices[0]->price;
					for($p=1;$p<count($prices);$p++) {
						if ($prices_lowest > $prices[$p]->price) {
							$prices_lowest = $prices[$p]->price;
						}
					}
					$prices_summary = __('from','wp_theatre').' '.$wp_theatre->options['currencysymbol'].'&nbsp;'.$prices[0]->price;
				}
			}
			$this->summary = array(
				'prices' => $prices_summary
			);
		}		
		return $this->summary;
	}

	function post_class() {
		$classes = array();
		$classes[] = self::post_type_name;
		
		return implode(' ',$classes);
	}
	
	function compile() {
		$summary = $this->summary();
		
		$html = '';
		
		$html.= '<div class='.self::post_type_name.' itemscope itemtype="http://data-vocabulary.org/Event">';

		$attr = array(
			'itemprop'=>'image'
		);
		$thumbnail = get_the_post_thumbnail($this->production()->ID,'thumbnail',$attr);
		if (!empty($thumbnail)) {
			$html.= '<figure>';
			$html.= $thumbnail;
			$html.= '</figure>';
		}

		$html.= '<div class="'.self::post_type_name.'_main">';

		$html.= '<div class="'.self::post_type_name.'_date">';
		$html.= '<time itemprop="startDate" datetime="'.date('c',$this->datetime()).'">';
		$html.= $this->date().' '.$this->time(); 
		$html.= '</time>';
		$html.= '</div>';

		$html.= '<div class="'.self::post_type_name.'_content">';

		if (is_singular(WPT_Production::post_type_name)) {
			$html.= '<meta itemprop="summary" content="'.$this->production()->post()->post_title.'" />';
			$html.= '<meta itemprop="url" content="'.get_permalink($this->production()->ID).'" />';
			
		} else {
			$html.= '<div class="'.self::post_type_name.'_title">';
			$html.= '<a itemprop="url" href="'.get_permalink($this->production()->ID).'">';
			$html.= '<span itemprop="summary">'.$this->production()->post()->post_title.'</span>';
			$html.= '</a>';
			$html.= '</div>'; //.title			
		}
		
		$remark = get_post_meta($this->ID,'remark',true);
		if ($remark!='') {
			$html.= '<div class="'.self::post_type_name.'_remark">'.$remark.'</div>';
		}
		
		$html.= '<div class="'.self::post_type_name.'_location" itemprop="location" itemscope itemtype="http://data-vocabulary.org/Organization">';

		$venue = get_post_meta($this->ID,'venue',true);
		$city = get_post_meta($this->ID,'city',true);
		if ($venue!='') {
			$html.= '<span itemprop="name">'.$venue.'</span>';
		}
		if ($venue!='' && $city!='') {
			$html.= ', ';
		}
		if ($city!='') {
			$html.= '<span itemprop="address" itemscope itemtype="http://data-vocabulary.org/Address">';
			$html.= '<span itemprop="locality">'.$city.'</span>';
			$html.= '</span>';
		}
		
		$html.= '</div>'; // .location
		
		$html.= '</div>'; // .content

		$html.= '<div class="'.self::post_type_name.'_tickets">';			
		if (get_post_meta($this->ID,'tickets_status',true) == 'soldout') {
			$html.= '<span class="'.self::post_type_name.'_soldout">'.__('Sold out', 'wp_theatre').'</span>';
		} else {
			if ($this->options['integrationtype']=='iframe') {
				$url = get_permalink($this->options['iframepage']);
				$args = array(
					__('Event','wp_theatre') => $this->ID
				);
				$url= add_query_arg( $args , $url);
			} else {
				$url = get_post_meta($this->ID,'tickets_url',true);
			}
			
			$url = apply_filters('wpt_event_tickets_url',$url,$this);
			if ($url!='') {
				$html_tickets_button = '';
				$html_tickets_button.= '<a href="'.$url.'"';

				if (!empty($this->options['integrationtype'])) {
					$html_tickets_button.= ' class="wpt_tickets_url wp_theatre_integrationtype_'.$this->options['integrationtype'].'"';
				}

				$html_tickets_button.= '>';

				$text = get_post_meta($this->ID,'tickets_button',true);
				if ($text=='') {
					$text = __('Tickets','wp_theatre');			
				}
				$html_tickets_button.= $text;
				$html_tickets_button.= '</a>';
				
				$html.= $html_tickets_button;
			}
		}

		if ($summary['prices']!='') {
			$html.= '<div class="'.self::post_type_name.'_prices">'.$summary['prices'].'</div>';
		}
		
		$html.= '</div>'; // .tickets

		$html.= '</div>'; // .main

		$html.= '</div>';
		return $html;	
	}
	
	function render() {
		do_action('wpt_before_event',$this);
		echo $this->compile();		
		do_action('wpt_after_event',$this);
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