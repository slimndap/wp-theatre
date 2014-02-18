<?php
/*
Plugin Name: Theatre
Plugin URI: http://wordpress.org/plugins/theatre/
Description: Turn your Wordpress website into a theatre website.
Author: Jeroen Schmit, Slim & Dapper
Version: 0.4
Author URI: http://slimndap.com/
Text Domain: wp_theatre
Domain Path: /lang
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
/** Usage:
 *
 *  $events = $wp_theatre->events->upcoming();
 *  $productions = WP_Theatre::productions();
 *  $seasons = WP_Theatre::seasons();
 *
 *	$args = array('limit'=>5);
 *	echo $wp_theatreWP_Theatre::render_productions($args); // a list of 5 production with upcoming events
 *
 *	$args = array('paged'=>true);
 *	echo $wp_theatre->events->html_listing($args); // a list of all upcoming events, paginated by month
 */

class WP_Theatre {
	function __construct() {
		$this->version = '0.4';

		// Includes
		$this->includes();
	
		// Setup
		$this->setup = new WPT_Setup();
		$this->admin = new WPT_Admin();
		$this->events = new WPT_Events();
		if (is_admin()) {
		} else {
			$this->frontend = new WPT_Frontend();
			$this->cart = new WPT_Cart();
		}
		
		// Options
		$this->options = get_option( 'wp_theatre' );
		$this->wpt_social_options = get_option( 'wpt_social' );
		$this->wpt_language_options = get_option( 'wpt_language' );

		// Hooks
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this->setup, 'plugin_action_links' ) );
		
		// Loaded action
		do_action( 'wpt_loaded' );
	}
	
	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @access public
	 * @return void
	 */
	function includes() {
		require_once(__DIR__ . '/functions/wpt_production.php');
		require_once(__DIR__ . '/functions/wpt_event.php');
		require_once(__DIR__ . '/functions/wpt_events.php');
		require_once(__DIR__ . '/functions/wpt_setup.php');
		require_once(__DIR__ . '/functions/wpt_season.php');
		require_once(__DIR__ . '/functions/wpt_widget.php');
		require_once(__DIR__ . '/functions/wpt_admin.php');
		if (is_admin()) {
		} else {
			require_once(__DIR__ . '/functions/wpt_frontend.php');
			require_once(__DIR__ . '/functions/wpt_cart.php');	
		}
	}
	
	/**
	 * All upcoming productions.
	 *
	 * Returns an array of all productions that have pubished events with a startdate in the future.
	 * 
	 * Example:
	 *
	 * $productions = WP_Theatre::productions();
	 *
	 * @since 0.3.6
	 *
	 * @see WP_Theatre::get_productions()
	 *
	 * @param  string $PostClass Optional. 
	 * @return mixed An array of WPT_Production objects.
	 */
	public function productions($PostClass = false) {
		return self::get_productions($PostClass);
	}

	public function seasons($PostClass = false) {
		return self::get_seasons($PostClass);
	}
		
	function render_productions($args=array()) {
		$defaults = array(
			'limit' => false
		);
		$args = wp_parse_args( $args, $defaults );
		extract($args);
		
		$productions = self::get_productions();
		
		if ($limit) {
			$productions = array_slice($productions, 0, $limit);
		}

		$html.= '<div class="wp_theatre_productions">';

		foreach ($productions as $production) {
			$html.= $production->render();			
		}
	
		$html.= '</div>'; //.wp-theatre_productions
		return $html;
	}

	/*
	 * Private functions.
	 */
	 
	private function get_productions($PostClass = false) {
		
		global $wpdb;
		
		$querystr = "
			SELECT productions . ID
			FROM $wpdb->posts AS
			events
			JOIN $wpdb->postmeta AS event_date ON events.ID = event_date.post_ID
			JOIN $wpdb->postmeta AS wp_theatre_prod ON events.ID = wp_theatre_prod.post_ID
			JOIN $wpdb->posts AS productions ON wp_theatre_prod.meta_value = productions.ID
			JOIN $wpdb->postmeta AS sticky ON productions.ID = sticky.post_ID
			WHERE 
			(
				events.post_type = '".WPT_Event::post_type_name."'
				AND events.post_status = 'publish'
				AND event_date.meta_key = 'event_date'
				AND wp_theatre_prod.meta_key = '".WPT_Production::post_type_name."'
				AND sticky.meta_key = 'sticky'
				AND event_date.meta_value > NOW( )
			) 
			OR sticky.meta_value = 'on'
			GROUP BY productions.ID
			ORDER BY sticky.meta_value DESC , event_date.meta_value ASC				
		";
		$posts = $wpdb->get_results($querystr, OBJECT);
		
		$productions = array();
		for ($i=0;$i<count($posts);$i++) {
			$productions[] = new WPT_Production($posts[$i]->ID, $PostClass);
		}
		return $productions;
	}
	

	private function get_seasons($PostClass=false) {
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

	/**
	 * Deprecated functions. 
	 *
	 * @deprecated 0.4.
	 */

	function compile_events($args=array()) {
		return $this->events->html_listing($args);
	}
	
 	public function events($args = array(), $PostClass = false) {
 		return $this->events->upcoming(null, $PostClass);
	}

	private function get_events($PostClass = false) {
		return WP_Theatre::events(null, $PosctClass);
	}

	function render_events($args=array()) {
		echo $this->compile_events($args);
	}
}

/**
 * Init WP_Theatre class
 */
$wp_theatre = new WP_Theatre();


?>
