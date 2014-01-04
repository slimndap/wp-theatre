<?php
/*
Plugin Name: Theatre
Plugin URI: http://wordpress.org/plugins/theatre/
Description: Turn your Wordpress website into a theatre website.
Author: Jeroen Schmit, Slim & Dapper
Version: 0.2.2
Author URI: http://slimndap.com/
Text Domain: wp_theatre
Domain Path: /lang
*/

global $wp_theatre;

require_once(__DIR__ . '/functions/wpt_season.php');
require_once(__DIR__ . '/functions/wpt_production.php');
require_once(__DIR__ . '/functions/wpt_event.php');
require_once(__DIR__ . '/functions/wpt_setup.php');
require_once(__DIR__ . '/functions/wpt_admin.php');

$wp_theatre = new WP_Theatre();
	
class WP_Theatre {
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
	}
	
	function get_post() {
		if (!isset($this->post)) {
			if ($this->PostClass) {
				$this->post = new $this->PostClass($this->ID);				
			} else {
				$this->post = get_post($this->ID);
			}
		}
		return $this->post;
	}
	
	function post() {
		return $this->get_post();
	}
	
	function get_posts($args = array(), $PostClass = false) {
		if (isset($this) && get_class($this) == __CLASS__) {
			// in object context
			if ($PostClass===false) {
				$PostClass = $this->PostClass;
			}
		}
		
		if ($PostClass!==false) {
			$posts = $PostClass::get_posts($args);
		} else {
			$posts = get_posts($args);
		}
		
		return $posts;
	}
	
	function get_productions($PostClass = false) {
		$args = array(
			'post_type'=>WPT_Production::post_type_name,
			'posts_per_page' => -1
		);
		
		$posts = get_posts($args);
		
		$productions = array();
		for ($i=0;$i<count($posts);$i++) {
			$production = new WPT_Production($posts[$i], $PostClass);
			if ($production->is_upcoming()) {
				$events = $production->events();
				$productions[$events[0]->datetime()] = $production;
			}
		}
		
		ksort($productions);
		return array_values($productions);
	}
	
	function get_events($PostClass = false) {
		$args = array(
			'post_type'=>WPT_Event::post_type_name,
			'posts_per_page' => -1,
			'meta_key'=>'event_date',
			'orderby' => 'meta_value_num',
			'order' => 'ASC',
			'meta_query' => array( // WordPress has all the results, now, return only the events after today's date
				array(
					'key' => 'event_date', // Check the start date field
					'value' => date("Y-m-d"), // Set today's date (note the similar format)
					'compare' => '>=', // Return the ones greater than today's date
				),
				array(
					'key' => WPT_Production::post_type_name, // Check if events is attached to production
					'compare' => 'EXISTS'
				)
			),
		);
		
		$posts = get_posts($args);

		for ($i=0;$i<count($posts);$i++) {
			$datetime = strtotime(get_post_meta($posts[$i]->ID,'event_date',true));
			$events[$datetime] = new WPT_Event($posts[$i], $PostClass);
		}
		
		ksort($events);
		return array_values($events);
	}

	function render_events() {
		$html = '';
		$html.= '<h3>'.WPT_Event::post_type()->labels->name.'</h3>';
		$html.= '<ul>';
		foreach ($this->get_events() as $event) {
			$html.= '<li itemscope itemtype="http://data-vocabulary.org/Event">';
			$html.= '<h3 itemprop="summary"><a href="'.get_permalink($event->production()->post()->ID).'" itemprop="url">'.$event->production->post()->post_title.'</a></h3>';
			$html.= '<span itemprop="startDate" datetime="'.date('c',$event->datetime()).'">';
			$html.= $event->date().' '.$event->time(); 
			$html.= '</span>';

			$html.= '<br />';

			$html.= '<span itemprop="location" itemscope itemtype="http://data-vocabulary.org/?Organization">';

			$html.= '<span itemprop="name">';
			$html.= get_post_meta($event->ID,'venue',true);
			$html.= '</span>';

			$html.= ', <span itemprop="address" itemscope itemtype="http://data-vocabulary.org/Address">';
			$html.= '<span itemprop="locality">'.get_post_meta($event->ID,'city',true).'</span>';
			$html.= '</span>';
			
			$html.= '</span>';

			$html.= '<br />';
			$html.= '<a href="'.get_post_meta($event->ID,'tickets_url',true).'">';
			$html.= __('Tickets');			
			$html.= '</a>';
			$html.= '</li>';
		}
		$html.= '</ul>';
		return $html;
	}

	function get_seasons($PostClass=false) {
		$args = array(
			'post_type'=>WPT_Season::post_type_name,
			'posts_per_page' => -1,
			'orderby' => 'title'
		);
		
		$posts = get_posts($args);
			
		$seasons = array();
		for ($i=0;$i<count($posts);$i++) {
			$seasons[] = new WPT_Season($posts[$i], $PostClass);
		}
		return $seasons;
		
	}

}

?>
