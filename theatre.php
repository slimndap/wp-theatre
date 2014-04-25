<?php
/*
Plugin Name: Theatre
Plugin URI: http://wordpress.org/plugins/theatre/
Description: Turn your Wordpress website into a theatre website.
Author: Jeroen Schmit, Slim & Dapper
Version: 0.7.3
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

$wpt_version = '0.7.3';

class WP_Theatre {
	function __construct() {

		// Set version
		global $wpt_version;
		$this->wpt_version = $wpt_version;
	
		// Includes
		$this->includes();
	
		// Setup
		$this->setup = new WPT_Setup();
		$this->admin = new WPT_Admin();
		$this->events = new WPT_Events();
		$this->productions = new WPT_Productions();
		$this->order = new WPT_Order();
		$this->feeds = new WPT_Feeds();
		$this->transient = new WPT_Transient();
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

		// Plugin (de)activation hooks
		register_activation_hook( __FILE__, array($this, 'activate' ));		
		register_deactivation_hook( __FILE__, array($this, 'deactivate' ));	
		
		// Plugin update hooks
		if ($wpt_version!=get_option('wpt_version')) {
			update_option('wpt_version', $wpt_version);
			add_action('admin_init',array($this,'update'));
		}
		
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
		require_once(dirname(__FILE__) . '/functions/wpt_listing.php');
		require_once(dirname(__FILE__) . '/functions/wpt_production.php');
		require_once(dirname(__FILE__) . '/functions/wpt_productions.php');
		require_once(dirname(__FILE__) . '/functions/wpt_event.php');
		require_once(dirname(__FILE__) . '/functions/wpt_events.php');
		require_once(dirname(__FILE__) . '/functions/wpt_setup.php');
		require_once(dirname(__FILE__) . '/functions/wpt_season.php');
		require_once(dirname(__FILE__) . '/functions/wpt_widget.php');
		require_once(dirname(__FILE__) . '/functions/wpt_admin.php');
		require_once(dirname(__FILE__) . '/functions/wpt_order.php');
		require_once(dirname(__FILE__) . '/functions/wpt_feeds.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_transient.php');	
		if (is_admin()) {
		} else {
			require_once(dirname(__FILE__) . '/functions/wpt_frontend.php');
			require_once(dirname(__FILE__) . '/functions/wpt_cart.php');	
		}
		require_once(dirname(__FILE__) . '/integrations/wordpress-seo.php');
		require_once(dirname(__FILE__) . '/integrations/jetpack-featured-content.php');
		
	}
	
	public function seasons($PostClass = false) {
		return $this->get_seasons($PostClass);
	}		

	function activate() {
		wp_schedule_event( time(), 'wpt_schedule', 'wpt_cron');

		//defines the post types so the rules can be flushed.
		$this->setup->init();

		//and flush the rules.
		flush_rewrite_rules();		
	}
	
	function deactivate() {
		wp_clear_scheduled_hook('wpt_cron');
		delete_post_meta_by_key($this->order->meta_key);
		flush_rewrite_rules();		
	}

	function update() {
		$this->activate();
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
