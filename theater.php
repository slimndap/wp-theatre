<?php
/*
	Plugin Name: Theater
	Plugin URI: https://wp.theater
	Description: Turn your Wordpress website into a theater website.
	Author: Jeroen Schmit
	Version: 0.15.8
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

$wpt_version = '0.15.8';


/**
 * Main Theater for WordPress class.
 *
 * ## Events and dates
 * Every event can have one or more dates.
 *
 * So if you run a theatre then 'The Sound Of Music' is an event and the show this weekend is a date.
 *
 * ## Getting started
 *
 * ### Dates
 * <code>
 * // Retrieve a list of upcoming dates:
 * $dates = new Theater_Dates;
 * $list = $dates();
 * </code>
 *
 * <code>
 * // Output a list of upcoming dates:
 * $dates = new Theater_Dates;
 * echo $dates;
 * </code>
 *
 * Retrieve a list of upcoming dates for a single event:
 * <code>
 * $dates = new Theater_Dates( array( 'event' => 123 ) );
 * $list = $dates();
 * </code>
 *
 * See [Theater_Dates()](class-Theater_Dates.html) for more examples.
 *
 * ### Events
 * Retrieve a list of all productions:
 * <code>
 * $productions = $wp_theatre->productions->get();
 * </code>
 *
 * Output a list of all productions:
 * <code>
 * echo $wp_theatre->productions->get_html();
 * </code>
 *
 * Output a list of all productions with upcoming events:
 * <code>
 * $args = array( 'start' => 'now' );
 * echo $wp_theatre->productions->get_html( $args );
 * </code>
 *
 * ## Extending Theater for WordPress
 * You can safely add extra functionality by using the `wpt_loaded` action hook:
 * <code>
 * function wpt_example_loader() {
 *		global $wp_theatre;
 *
 *		// Add your custom code below...
 *
 * }
 * add_action( 'wpt_loaded', 'wpt_example_loader' );
 * </code>
 * See this [Example Extension](https://github.com/slimndap/wp-theatre-example-extension) for a full example.
 *
 * @package		Theater
 * @version		0.15.8
 * @author		Jeroen Schmit <jeroen@slimndap.com>
 * @copyright	2016 [Slim & Dapper](http://slimndap.com)
 * @license		https://opensource.org/licenses/GPL-3.0 GNU General Public License
 *
 * @example 	[Example of a WordPress for Theater extension](https://github.com/slimndap/wp-theatre-example-extension).
 */
class WP_Theatre {

	/**
	 * The single instance of the class.
	 *
	 * @var Theater for WordPress
	 * @since 0.16
	 */
	protected static $_instance = null;

	/**
	 * Main Theater for WordPress Instance.
	 *
	 * Ensures only one instance of Theater for WordPress is loaded or can be loaded.
	 *
	 * @since 0.16
	 * @static
	 * @see 	Theater()
	 * @return 	WP_Theatre	Main instance.
	 */
	static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;		
	}

	function __construct() {

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

		Theater_Dates::init();

		$this->production_permalink = new WPT_Production_Permalink();
		Theater_Event_Dates::init();

		Theater_Widgets::init();

		$this->productions = new WPT_Productions();
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
		$this->wpt_listing_page_options = get_option( 'wpt_listing_page' );
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
		add_action ('plugins_loaded', array($this,'wpt_loaded') );
	}

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
	 */
	function includes() {
		require_once(dirname(__FILE__) . '/functions/wpt_listing.php');
		require_once(dirname(__FILE__) . '/functions/abstract/class-theater-lists.php');
		require_once(dirname(__FILE__) . '/functions/abstract/class-theater-field.php');

		require_once(dirname(__FILE__) . '/functions/template/wpt_template.php');
		require_once(dirname(__FILE__) . '/functions/template/wpt_template_placeholder.php');
		require_once(dirname(__FILE__) . '/functions/template/wpt_template_placeholder_filter.php');

		require_once(dirname(__FILE__) . '/functions/wpt_production.php');
		require_once(dirname(__FILE__) . '/functions/wpt_production_permalink.php');
		require_once(dirname(__FILE__) . '/functions/wpt_production_template.php');
		require_once(dirname(__FILE__) . '/functions/wpt_production_widget.php');
		require_once(dirname(__FILE__) . '/functions/event/class-theater-event-dates.php');

		require_once(dirname(__FILE__) . '/functions/wpt_productions.php');
		require_once(dirname(__FILE__) . '/functions/wpt_productions_admin.php');
		require_once(dirname(__FILE__) . '/functions/wpt_productions_list_table.php');

		require_once(dirname(__FILE__) . '/functions/date/class-theater-date.php');
		require_once(dirname(__FILE__) . '/functions/date/class-theater-date-field.php');
		require_once(dirname(__FILE__) . '/functions/deprecated/class-wpt-event.php');
		require_once(dirname(__FILE__) . '/functions/wpt_event_admin.php');
		require_once(dirname(__FILE__) . '/functions/wpt_event_editor.php');
		require_once(dirname(__FILE__) . '/functions/wpt_event_template.php');

		require_once(dirname(__FILE__) . '/functions/dates/class-theater-dates.php');
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
	
	protected function deprecated_properties() {
		$this->events = new Theater_Dates; 	
 	}
}

/**
 * Main instance of Theater for WordPress.
 *
 * Returns the main instance of Theater for WordPress to prevent the need to use globals.
 *
 * @since  0.19
 * @return WP_Theatre
 */
function Theater() {
	return WP_Theatre::instance();
}

/**
 * @var	WP_Theatre	
 */
$wp_theatre = Theater();


?>
