<?php
class WPT_Production extends WP_Theatre {

	const post_type_name = 'wp_theatre_prod';
	
	function __construct($ID=false, $PostClass=false) {
		parent::__construct($ID, $PostClass);
		if (!$this->ID) {
			$post = get_post();
			if ($post) {
				$this->ID = $post->ID;				
			}
		}		
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
					if (!in_array($city, $cities)) {
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
			$full = $this->dates();
			if ($this->cities!='') {
				$full .= ' '.__('in','wp_theatre').' '.$this->cities().'.';
			}
			$this->summary = array(
				'dates' => $this->dates(),
				'cities' => $this->cities(),
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
				$events[$datetime] = new WPT_Event($posts[$i], $this->PostClass);
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

	function render_events() {
		$html = '';
		$html.= '<h3>'.WPT_Event::post_type()->labels->name.'</h3>';
		$html.= '<ul class="wp_theatre_events">';
		foreach ($this->upcoming_events() as $event) {
			$html.= '<li>';
			$html.= $event->render();			
			$html.= '</li>';
		}
		$html.= '</ul>';
		return $html;
	}


}

?>