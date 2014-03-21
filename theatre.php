<?php
/*
Plugin Name: Theatre
Plugin URI: http://wordpress.org/plugins/theatre/
Description: Turn your Wordpress website into a theatre website.
Author: Jeroen Schmit, Slim & Dapper
Version: 0.6.1
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
		$this->version = '0.6.1';

		// Includes
		$this->includes();
	
		// Setup
		$this->setup = new WPT_Setup();
		$this->admin = new WPT_Admin();
		$this->events = new WPT_Events();
		$this->productions = new WPT_Productions();
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
	 * Enable magic __invoke function in child classes.
	 * See: http://stackoverflow.com/a/3108130/1153764
	 *
	 * Example:
	 * $events = $wp_theatre->events();
	 *
	 */
	public function __call($method, $args) {
		if(property_exists($this, $method)) {
		    $prop = $this->$method;
		    return call_user_func_array($this->$method,$args);
		}
	}
	
	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @access public
	 * @return void
	 */
	function includes() {
		require_once(__DIR__ . '/functions/wpt_listing.php');
		require_once(__DIR__ . '/functions/wpt_production.php');
		require_once(__DIR__ . '/functions/wpt_productions.php');
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
	
	public function seasons($PostClass = false) {
		return $this->get_seasons($PostClass);
	}
		

	/*
	 * Private functions.
	 */
	 
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
		return $this->events->html($args);
	}
	
	private function get_events($PostClass = false) {
		return $this->events();
	}

	function render_events($args=array()) {
		echo $this->compile_events($args);
	}

	private function get_productions($PostClass = false) {
		return $this->productions();
	}

	function render_productions($args=array()) {
		return $this->productions->html_listing();
	}

}

/**
 * Init WP_Theatre class
 */
$wp_theatre = new WP_Theatre();


?>
