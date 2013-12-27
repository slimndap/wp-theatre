<?php
/*
Plugin Name: Theatre
Plugin URI: http://wordpress.org/plugins/theatre/
Description: Turn your Wordpress website into a theatre website.
Author: Jeroen Schmit, Slim & Dapper
Version: 0.2.1
Author URI: http://slimndap.com/
Text Domain: wp_theatre
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
		$this->ID = $ID;
		$this->PostClass = $PostClass;
	}
	
	function get_post($ID=false, $PostClass = false) {
		if ($PostClass!==false) {
			$post = $PostClass::get_post($ID);
		} else {
			$post = get_post($ID);
		}
		return $post;
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
			'post_type'=>'wp_theatre_prod',
			'posts_per_page' => -1
		);
		
		if ($PostClass===false) {
			$PostClass = 'WPT_Production';
		}
		
		$posts = static::get_posts($args, $PostClass);		

		$productions = array();
		for ($i=0;$i<count($posts);$i++) {
			$production = new WPT_Production($posts[$i]->ID);
			if ($production->is_upcoming()) {
				$production->post = $posts[$i];
				$productions[] = $production;
			}
		}
		return $productions;
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
		
		$posts = static::get_posts($args, $PostClass);		

		$events = array();
		for ($i=0;$i<count($posts);$i++) {
			$event = new WPT_Event($posts[$i]->ID, $PostClass);
			$event->post = $posts[$i];
			$events[] = $event;
		}
		
		return $events;
	}

	function render_events() {
		$html = '';
		$html.= '<h3>'.WPT_Event::post_type()->labels->name.'</h3>';
		$html.= '<ul>';
		foreach ($this->get_events() as $event) {
			$production = new WPT_Production(get_post_meta($event->ID,WPT_Production::post_type_name,true));
			$html.= '<li>';
			$html.= '<h3><a href="'.get_permalink($production->post()->ID).'">'.$production->post()->post_title.'</a></h3>';
			$html.= get_post_meta($event->ID,'event_date',true); 
			$html.= '<br />';
			$html.= get_post_meta($event->ID,'venue',true).', '.get_post_meta($event->ID,'city',true);
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
		
		$posts = static::get_posts($args, $PostClass);
			
		$seasons = array();
		for ($i=0;$i<count($posts);$i++) {
			$season = new WPT_Season($posts[$i]->ID, $PostClass);
			$season->post = $posts[$i];
			$seasons[] = $season;
		}
		return $seasons;
		
	}

}

?>
