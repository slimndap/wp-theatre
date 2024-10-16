<?php
/*
	
	Plugin Name: Theater
	Plugin URI: https://wp.theater/
	Description: Manage and publish events for your theater, live venue, cinema, club or festival.
	Author: Jeroen Schmit
	Version: 0.18.6.1
	Author URI: http://slimndap.com/
	Text Domain: theatre
	Domain Path: /lang

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License along
	with this program; if not, write to the Free Software Foundation, Inc.,
	51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
$wpt_version = '0.18.6.1';

class WP_Theatre {
	
	public $wpt_version;
	public $setup;
	public $admin;
	public $status;
	public $feeds;
	public $listing_page;
	public $calendar;
	public $context;
	public $filter;
	public $event_admin;
	public $event_editor;
	public $events;
	public $production_permalink;
	public $productions;
	public $productions_admin;
	public $tags;
	public $extensions_updater;
	public $extensions_promo;
	public $gutenberg;
	public $jeero_suggest;
	public $order;
	public $cart;
	public $wpt_language_options;
	public $wpt_listing_page_options;
	public $wpt_style_options;
	public $wpt_tickets_options;	
	public $frontend;	
	
	function __construct() {

		// Set version
		global $wpt_version;

		$this->wpt_version = $wpt_version;
	
		// Includes
		$this->includes();

		// Setup
		$this->setup = new WPT_Setup();
		$this->admin = new WPT_Admin();
		Theater_Event_Order::init();
		$this->status = new WPT_Status();
		$this->feeds = new WPT_Feeds();
		Theater_Transients::init();
		
		$this->listing_page = new WPT_Listing_Page();
		Theater_Event_Archive::init();
		$this->calendar = new WPT_Calendar();
		$this->context = new WPT_Context();
		$this->filter = new WPT_Filter();

		$this->event_admin = new WPT_Event_Admin();
		$this->event_editor = new WPT_Event_Editor();

		$this->events = new WPT_Events();

		$this->production_permalink = new WPT_Production_Permalink();

		$this->productions = new WPT_Productions();
		$this->productions_admin = new WPT_Productions_Admin();

		$this->tags = new WPT_Tags();
		$this->extensions_updater= new WPT_Extensions_Updater();
		$this->extensions_promo= new WPT_Extensions_Promo();
		if (is_admin()) {
		} else {
			$this->frontend = new WPT_Frontend();
		}
		
		// Gutenberg
		$this->gutenberg = new Theater_Gutenberg();
		
		// Jeero
		$this->jeero_suggest = new Theater_Jeero_Suggest();
		
		// Deprecated properties
		$this->order = new WPT_Order();
		Theater_Custom_CSS::init();
		$this->cart = new WPT_Cart();		
		
		// Options
		$this->wpt_language_options = get_option( 'wpt_language' );
		$this->wpt_listing_page_options = get_option( 'wpt_listing_page' );
		$this->wpt_style_options = get_option( 'wpt_style' );
		$this->wpt_tickets_options = get_option( 'wpt_tickets' );
		$this->deprecated_options();
		
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
		
		// Hook wpt_loaded action.
		add_action ('plugins_loaded', array($this,'wpt_loaded') );
	}
		
	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @access public
	 * @return void
	 */
	function includes() {
		require_once(dirname(__FILE__) . '/functions/helpers/class-theater-helpers-time.php');

		require_once(dirname(__FILE__) . '/functions/wpt_listing.php');
		require_once(dirname(__FILE__) . '/functions/event/class-theater-event-archive.php');
		require_once(dirname(__FILE__) . '/functions/event/class-theater-event-gutenberg.php');
		require_once(dirname(__FILE__) . '/functions/event/class-theater-event-order.php');
		require_once(dirname(__FILE__) . '/functions/event/class-theater-event-embed.php');

		require_once(dirname(__FILE__) . '/functions/template/wpt_template.php');	
		require_once(dirname(__FILE__) . '/functions/template/wpt_template_placeholder.php');	
		require_once(dirname(__FILE__) . '/functions/template/wpt_template_placeholder_filter.php');	

		require_once(dirname(__FILE__) . '/functions/wpt_production.php');
		require_once(dirname(__FILE__) . '/functions/wpt_production_permalink.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_production_template.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_production_widget.php');

		require_once(dirname(__FILE__) . '/functions/wpt_productions.php');
		require_once(dirname(__FILE__) . '/functions/wpt_productions_admin.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_productions_list_table.php');	

		require_once(dirname(__FILE__) . '/functions/wpt_event.php');
		require_once(dirname(__FILE__) . '/functions/wpt_event_admin.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_event_editor.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_event_template.php');	

		require_once(dirname(__FILE__) . '/functions/wpt_events.php');
		require_once(dirname(__FILE__) . '/functions/wpt_events_widget.php');

		require_once(dirname(__FILE__) . '/functions/wpt_setup.php');
		require_once(dirname(__FILE__) . '/functions/wpt_season.php');
		require_once(dirname(__FILE__) . '/functions/wpt_widget.php');
		require_once(dirname(__FILE__) . '/functions/wpt_admin.php');
		require_once(dirname(__FILE__) . '/functions/wpt_status.php');
		require_once(dirname(__FILE__) . '/functions/wpt_feeds.php');	
		
		require_once(dirname(__FILE__) . '/functions/transient/class-theater-transient.php');	
		require_once(dirname(__FILE__) . '/functions/transient/class-theater-transients.php');	
			
		require_once(dirname(__FILE__) . '/functions/wpt_listing_page.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_calendar.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_context.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_filter.php');	
		require_once(dirname(__FILE__) . '/functions/wpt_tags.php');	

		require_once(dirname(__FILE__) . '/functions/extensions/wpt_extensions_updater.php');	
		require_once(dirname(__FILE__) . '/functions/extensions/wpt_extensions_promo.php');	

		require_once(dirname(__FILE__) . '/functions/wpt_importer.php');	

		require_once(dirname(__FILE__) . '/functions/jeero/class-theater-jeero-suggest.php');	

		if (is_admin()) {
		} else {
			require_once(dirname(__FILE__) . '/functions/wpt_frontend.php');
		}
		require_once(dirname(__FILE__) . '/integrations/wordpress-seo.php');
		require_once(dirname(__FILE__) . '/integrations/jetpack-featured-content.php');
		
		require_once(dirname(__FILE__) . '/functions/deprecated/wpt_cart.php');	
		require_once(dirname(__FILE__) . '/functions/deprecated/class-wpt-order.php');
		require_once(dirname(__FILE__) . '/functions/deprecated/class-theater-custom-css.php');
		
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
	
	/**
	 * Cleans up after plugin deactivation.
	 * 
	 * @since 	0.?
	 * @since	0.15.33	No longer removes all order indexes.
	 *			Fixes #274.
	 * @return 	void
	 */
	function deactivate() {
		wp_clear_scheduled_hook('wpt_cron');
		flush_rewrite_rules();		
	}

	function update() {
		$this->activate();
	}


 
 	/**
 	 * Fires the `wpt_loaded` action.
 	 * 
 	 * Use this to safely load plugins that depend on Theater.
 	 *
 	 * @access public
 	 * @return void
 	 */
 	function wpt_loaded() {
		do_action('wpt_loaded');
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

 	/*
 	 * For backward compatibility purposes
 	 * Use old theatre options for style options and tickets options.
 	 * As of v0.8 style options and tickets options are stored seperately.
 	 */
 	
 	function deprecated_options() {
	 	if (empty($this->wpt_style_options)) {
		 	$this->wpt_style_options = get_option( 'theatre' );
	 	}
	 	if (empty($this->wpt_tickets_options)) {
		 	$this->wpt_tickets_options = get_option( 'theatre' );
	 	}
 	}
}

/**
 * Init WP_Theatre class
 *
 * @since	0.?
 * @since	0.15.24	Explicitly register $wp_theatre as a global variable.
 *					See: https://github.com/slimndap/wp-theatre/issues/245 
 */
global $wp_theatre;
$wp_theatre = new WP_Theatre();


?>
