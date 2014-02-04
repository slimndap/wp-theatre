<?php
class WPT_Event extends WP_Theatre {

	const post_type_name = 'wp_theatre_event';
	
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
		if (!isset($this->summary)) {
			$prices = $this->prices();
			$prices_summary = '';
			if (count($prices)>0) {
				if (count($prices)==1) {
					$prices_summary = $prices[0]->price;
				} else {
					$prices_lowest = $prices[0]->price;
					for($p=1;$p<count($prices);$p++) {
						if ($prices_lowest > $prices[$p]->price) {
							$prices_lowest = $prices[$p]->price;
						}
					}
					$prices_summary = __('from','wp_theatre').' &euro;&nbsp;'.$prices[0]->price;
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
	
	function render() {
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

		$html.= '<div class="main">';

		$html.= '<div class="date">';
		$html.= '<time itemprop="startDate" datetime="'.date('c',$this->datetime()).'">';
		$html.= $this->date().' '.$this->time(); 
		$html.= '</time>';
		$html.= '</div>';

		$html.= '<div class="content">';
		
		$html.= '<div class="title">';
		$html.= '<a itemprop="url" href="'.get_permalink($this->production()->ID).'">';
		$html.= '<span itemprop="summary">'.$this->production()->post()->post_title.'</span>';
		$html.= '</a>';
		$html.= '</div>'; //.title

		$remark = get_post_meta($this->ID,'remark',true);
		if ($remark!='') {
			$html.= '<div class="remark">'.$remark.'</div>';
		}
		
		$html.= '<div class="location" itemprop="location" itemscope itemtype="http://data-vocabulary.org/Organization">';

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

		$html.= '<div class="tickets">';			
		if (get_post_meta($this->ID,'tickets_status',true) == 'soldout') {
			$html.= '<span class="soldout">'.__('Sold out', 'wp_theatre').'</span>';
		} else {
			$url = get_post_meta($this->ID,'tickets_url',true);
			if ($url!='') {
				$html_tickets_button = '';
				$html_tickets_button.= '<a href="'.get_post_meta($this->ID,'tickets_url',true).'">';
				$text = get_post_meta($this->ID,'tickets_button',true);
				if ($text=='') {
					$text = __('Tickets','wp_theatre');			
				}
				$html_tickets_button.= $text;
				$html_tickets_button.= '</a>';
				$html.= apply_filters('wpt_event_tickets_button',$html_tickets_button,$url,$text);
			}
		}

		if ($summary['prices']!='') {
			$html.= '<div class="prices">'.$summary['prices'].'</div>';
		}
		
		$html.= '</div>'; // .tickets

		$html.= '</div>'; // .main

		$html.= '</div>';
		return $html;
	}
}

?>