<?php
/*
	Plugin Name: Theater
	Plugin URI: https://wp.theater
	Description: Turn your Wordpress website into a theater website.
	Author: Jeroen Schmit
	Version: 0.16
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

$wpt_version = '0.16';


/**
 * Main Theater for WordPress class.
 *
 * With the Theater for WordPress plugin it is possible to manage and publish _events_ that have one of more _dates_.
 *
 * So if you run a theater then 'The Sound Of Music' is an event and the show this weekend is a date.
 *
 * ## Getting started
 *
 * ### Events
 * <code>
 * // Retrieve a list of all events.
 * $events = new Theater_Event_List;
 * foreach ( $events() as $event ) {
 *		// $event is a Theater_Event object.	 
 *		echo $event->title();
 * }
 * </code>
 *
 * <code>
 * // Output a formatted list of all events.
 * $events = new Theater_Event_List;
 * echo $events;
 * </code>
 *
 * See [Theater_Event_List()](class-Theater_Event_List.html) for more examples.
 *
 * ### Event dates
 * <code>
 * // Retrieve a list of all event dates.
 * $dates = new Theater_Event_Date_List;
 * foreach ( $dates() as $date ) {
 *		// $date is a Theater_Event_Date object.	 
 *		echo $date->title();
 * }
 * </code>
 *
 * <code>
 * // Output a formatted list of all dates.
 * $dates = new Theater_Event_Date_List;
 * echo $dates;
 * </code>
 *
 * See [Theater_Event_Date_List()](class-Theater_Event_Date_List.html) for more examples.
 *
 * ## Extending Theater for WordPress
 * You can safely add extra functionality by using the `theater/loaded` action hook:
 * <code>
 * function theater_example_loader() {
 *		// Add your custom code below...
 * }
 * add_action( 'theater/loaded', 'theater_example_loader' );
 * </code>
 * See this [Example Extension](https://github.com/slimndap/wp-theatre-example-extension) for a full example.
 *
 * @package		Theater
 * @version		0.16
 * @author		Jeroen Schmit <jeroen@slimndap.com>
 * @copyright	2016 [Slim & Dapper](http://slimndap.com)
 * @license		https://opensource.org/licenses/GPL-3.0 GNU General Public License
 *
 */
class Theater {

	/**
	 * The single instance of the class.
	 *
	 * @var Theater for WordPress
	 * @since 0.16
	 * @internal
	 */
	protected static $_instance = null;

	function __construct() {
		
	}

	/**
	 * Returns the main Theater for WordPress Instance.
	 *
	 * Ensures only one instance of Theater for WordPress is loaded or can be loaded.
	 *
	 * @since 0.16
	 * @static
	 * @internal
	 * @return 	Theater	Main Theater instance.
	 */
	static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;		
	}

	/**
	 * init function.
	 * 
	 * @since	0.16
	 * @return 	void
	 * @internal
	 */
	function init() {
		// Set version
		global $wpt_version;
		$this->wpt_version = $wpt_version;

		$this->define_constants();

		// Includes
		$this->includes();

		// Setup
		Theater_Setup::init();

		$this->admin = new WPT_Admin();
		Theater_Admin_Plugins::init();
		
		$this->order = new WPT_Order();
		$this->status = new WPT_Status();
		$this->feeds = new WPT_Feeds();
		$this->transient = new WPT_Transient();
		$this->listing_page = new WPT_Listing_Page();
		$this->calendar = new WPT_Calendar();
		$this->context = new WPT_Context();
		$this->filter = new WPT_Filter();

		$this->event_admin = new WPT_Event_Admin();
		$this->event_editor = new WPT_Event_Editor();

		$this->production_permalink = new WPT_Production_Permalink();
		Theater_Event_Date_Link::init();

		Theater_Widgets::init();

		$this->productions_admin = new WPT_Productions_Admin();

		$this->cart = new WPT_Cart();
		$this->tags = new WPT_Tags();
		$this->extensions_updater= new WPT_Extensions_Updater();
		$this->extensions_promo= new WPT_Extensions_Promo();
		if (is_admin()) {
		} else {
			$this->frontend = new WPT_Frontend();
		}
		
		$this->deprecated_properties();

		// Options
		$this->wpt_language_options = get_option( 'wpt_language' );
		$this->wpt_Listing_page_options = get_option( 'wpt_Listing_page' );
		$this->wpt_style_options = get_option( 'wpt_style' );
		$this->wpt_tickets_options = get_option( 'wpt_tickets' );
		$this->deprecated_options();

		// Plugin (de)activation hooks
		register_activation_hook( __FILE__, array($this, 'activate' ));
		register_deactivation_hook( __FILE__, array($this, 'deactivate' ));

		// Plugin update hooks
		if ($wpt_version!=get_option('wpt_version')) {
			update_option('wpt_version', $wpt_version);
			add_action('admin_init',array($this,'update'));
		}

		// Hook wpt_loaded action.
		add_action ('plugins_loaded', array($this,'do_theater_loaded_action') );
		
	}

	/**
	 * define_constants function.
	 * 
	 * @access protected
	 * @return void
	 * @internal
	 */
	protected function define_constants() {
		if ( ! defined( 'THEATER_PLUGIN_BASENAME' ) ) {
			define( 'THEATER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );		
		}
		if ( ! defined( 'THEATER_VERSION' ) ) {
			define( 'THEATER_VERSION', $this->wpt_version );	
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @access public
	 * @return void
	 * @internal
	 */
	function includes() {
		require_once(dirname(__FILE__) . '/functions/abstract/class-theater-item.php');
		require_once(dirname(__FILE__) . '/functions/abstract/class-theater-list.php');

		require_once(dirname(__FILE__) . '/functions/template/wpt_template.php');
		require_once(dirname(__FILE__) . '/functions/template/wpt_template_placeholder.php');
		require_once(dirname(__FILE__) . '/functions/template/wpt_template_placeholder_filter.php');

		// All event classes.
		require_once(dirname(__FILE__) . '/functions/event/class-theater-event.php');
		require_once(dirname(__FILE__) . '/functions/event/class-theater-event-field.php');
		require_once(dirname(__FILE__) . '/functions/event/class-theater-event-date.php');
		require_once(dirname(__FILE__) . '/functions/event/class-theater-event-date-link.php');
		require_once(dirname(__FILE__) . '/functions/event/class-theater-event-list.php');
		require_once(dirname(__FILE__) . '/functions/event/class-theater-event-date-list.php');


		require_once(dirname(__FILE__) . '/functions/wpt_production_permalink.php');
		require_once(dirname(__FILE__) . '/functions/wpt_production_template.php');
		require_once(dirname(__FILE__) . '/functions/wpt_production_widget.php');

		require_once(dirname(__FILE__) . '/functions/wpt_productions_admin.php');
		require_once(dirname(__FILE__) . '/functions/wpt_productions_list_table.php');

		require_once(dirname(__FILE__) . '/functions/deprecated/class-wp-theatre.php');
		require_once(dirname(__FILE__) . '/functions/deprecated/class-wpt-production.php');
		require_once(dirname(__FILE__) . '/functions/deprecated/class-wpt-event.php');
		require_once(dirname(__FILE__) . '/functions/wpt_event_admin.php');
		require_once(dirname(__FILE__) . '/functions/wpt_event_editor.php');
		require_once(dirname(__FILE__) . '/functions/wpt_event_template.php');

		require_once(dirname(__FILE__) . '/functions/wpt_events_widget.php');

		require_once(dirname(__FILE__) . '/functions/setup/class-theater-setup.php');
		require_once(dirname(__FILE__) . '/functions/wpt_season.php');

		require_once(dirname(__FILE__) . '/functions/wpt_widget.php');
		require_once(dirname(__FILE__) . '/functions/widgets/class-theater-widgets.php');

		require_once(dirname(__FILE__) . '/functions/wpt_admin.php');
		require_once(dirname(__FILE__) . '/functions/admin/class-theater-admin-plugins.php');

		require_once(dirname(__FILE__) . '/functions/wpt_order.php');
		require_once(dirname(__FILE__) . '/functions/wpt_status.php');
		require_once(dirname(__FILE__) . '/functions/wpt_feeds.php');
		require_once(dirname(__FILE__) . '/functions/wpt_transient.php');
		require_once(dirname(__FILE__) . '/functions/wpt_listing_page.php');
		require_once(dirname(__FILE__) . '/functions/wpt_calendar.php');
		require_once(dirname(__FILE__) . '/functions/wpt_context.php');
		require_once(dirname(__FILE__) . '/functions/wpt_filter.php');
		require_once(dirname(__FILE__) . '/functions/wpt_cart.php');
		require_once(dirname(__FILE__) . '/functions/wpt_tags.php');

		require_once(dirname(__FILE__) . '/functions/extensions/wpt_extensions_updater.php');
		require_once(dirname(__FILE__) . '/functions/extensions/wpt_extensions_promo.php');

		require_once(dirname(__FILE__) . '/functions/wpt_importer.php');


		if (is_admin()) {
		} else {
			require_once(dirname(__FILE__) . '/functions/wpt_frontend.php');
		}
		require_once(dirname(__FILE__) . '/integrations/wordpress-seo.php');
		require_once(dirname(__FILE__) . '/integrations/jetpack-featured-content.php');

	}

	/**
	 * @deprecated	0.16
	 * @internal
	 */
	public function seasons($PostClass = false) {
		return $this->get_seasons($PostClass);
	}

	/**
	 * activate function.
	 * 
	 * @access public
	 * @internal
	 * @return void
	 */
	function activate() {
		wp_schedule_event( time(), 'wpt_schedule', 'wpt_cron');

		//defines the post types so the rules can be flushed.
		$this->setup->init();

		//and flush the rules.
		flush_rewrite_rules();
	}

	/**
	 * deactivate function.
	 * 
	 * @access public
	 * @return void
	 * @internal
	 */
	function deactivate() {
		wp_clear_scheduled_hook('wpt_cron');
		delete_post_meta_by_key($this->order->meta_key);
		flush_rewrite_rules();
	}

	/**
	 * update function.
	 * 
	 * @access public
	 * @return void
	 * @internal
	 */
	function update() {
		$this->activate();
	}



 	/**
 	 * Fires the `theater/loaded` action.
 	 *
 	 * Use this action to safely load plugins that depend on Theater for WordPress.
 	 *
 	 * @since	0.x
 	 * @return 	void
 	 */
 	function do_theater_loaded_action() {
 		/**
	 	 * Fires after Theater for WordPress is fully loaded.
	 	 * @since	0.16
	 	 */
		do_action('theater/loaded');
		
		/**
		 * @deprecated	0.16
		 */
		do_action('wpt_loaded');
	}

	/*
	 * Private functions.
	 */

	/**
	 * @internal
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
	 * @deprecated 0.4
	 * @internal
	 */
	function compile_events($args=array()) {
		return $this->events->html($args);
	}

	/**
	 * @deprecated 0.4
	 * @internal
	 */
	private function get_events($PostClass = false) {
		return $this->events();
	}

	/**
	 * @deprecated 0.4
	 * @internal
	 */
	function render_events($args=array()) {
		echo $this->compile_events($args);
	}

	/**
	 * @deprecated 0.4
	 * @internal
	 */
	private function get_productions($PostClass = false) {
		return $this->productions();
	}

	/**
	 * @deprecated 0.4
	 * @internal
	 */
	function render_productions($args=array()) {
		return $this->productions->html_Listing();
	}
	
	/*
	 * For backward compatibility purposes
	 * Use old theatre options for style options and tickets options.
	 * As of v0.8 style options and tickets options are stored seperately.
	 */
	
	/**
	 * @deprecated 0.8
	 * @internal
	 */
	function deprecated_options() {
		if (empty($this->wpt_style_options)) {
			$this->wpt_style_options = get_option( 'theatre' );
		}
		if (empty($this->wpt_tickets_options)) {
			$this->wpt_tickets_options = get_option( 'theatre' );
		}
	}
	
	/**
	 * @deprecated 0.8
	 * @internal
	 */
	protected function deprecated_properties() {
		$this->productions = new Theater_Event_List; 	
		$this->events = new Theater_Event_Date_List; 	
 	}
}

/**
 * Main instance of Theater for WordPress.
 *
 * @since  0.16
 * @package	Theater
 * @return 	Theater	The main instance of Theater for WordPress to prevent the need to use globals.
 */
function Theater() {
	return Theater::instance();
}

Theater()->init();
?>
