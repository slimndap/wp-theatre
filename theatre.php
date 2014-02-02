<?php
/*
Plugin Name: Theatre
Plugin URI: http://wordpress.org/plugins/theatre/
Description: Turn your Wordpress website into a theatre website.
Author: Jeroen Schmit, Slim & Dapper
Version: 0.3.1
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
require_once(__DIR__ . '/functions/wpt_widget.php');

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
		add_action('wp_head', array($this,'wp_head'));
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
		
		$upcoming = array();
		$stickies = array();
		for ($i=0;$i<count($posts);$i++) {
			$production = new WPT_Production($posts[$i], $PostClass);
			if ($production->is_upcoming()) {
				$events = $production->events();
				$upcoming[$events[0]->datetime()] = $production;				
			} elseif (is_sticky($production->ID)) {
				$stickies[] = $production;
			}
		}
		
		ksort($upcoming);
		$upcoming = array_values($upcoming);
		
		$productions = array_merge($upcoming,$stickies);
		
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
		$posts = get_posts($args);

		$events = array();
		for ($i=0;$i<count($posts);$i++) {
			$datetime = strtotime(get_post_meta($posts[$i]->ID,'event_date',true));
			$events[$datetime.$posts[$i]->ID] = new WPT_Event($posts[$i], $PostClass);
		}
		
		ksort($events);
		return array_values($events);
	}

	function render_events($args=array()) {
		$defaults = array(
			'paged' => false,
			'grouped' => false,
		);
		$args = wp_parse_args( $args, $defaults );
		extract($args);
		
		$events = $this->get_events();
		$months = array();
		
		foreach($events as $event) {
			$month = date_i18n('M Y',$event->datetime());
			$months[$month][] = $event;
		}			

		$html = '';
		$html.= '<div class="wp_theatre_events">';

		if ($paged && count($months)) {
			if (empty($_GET['month'])) {
				reset($months);
				$current_month = sanitize_title(key($months));
			} else {
				$current_month = $_GET['month'];
			}
			
			$html.= '<nav>';
			foreach($months as $month=>$events) {
				$url = remove_query_arg('month');
				$url = add_query_arg( 'month', sanitize_title($month) , $url);
				$html.= '<span>';
				if (sanitize_title($month) != $current_month) {
					$html.= '<a href="'.$url.'">'.$month.'</a>';
				} else {
					$html.= $month;
					
				}
				$html.= '</span>';
			}
			$html.= '</nav>';
		}

		foreach($months as $month=>$events) {
			if ($paged) {
				if (sanitize_title($month) != $current_month) {
					continue;
				}
			}
			if ($grouped) {
				$html.= '<h4>'.$month.'</h4>';				
			}
			$html.= '<ul>';
			foreach ($events as $event) {
				$html.= '<li>';
				$html.= $event->render();			
				$html.= '</li>';
			}
			$html.= '</ul>';			
		}
	
		$html.= '</div>'; //.wp-theatre_events
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

	function wp_head() {
		echo '<meta name="generator" content="Theatre" />'."\n";
	}

}

?>
