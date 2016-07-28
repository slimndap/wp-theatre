<?php
/**
 * Main Setup class.
 *
 * Initializes the following sub classes:
 *
 * - Theater_Setup_Post_Types
 * - Theater_Setup_Meta
 *
 * @package	Theater/Setup
 * @since	0.?
 * @internal
 */
class Theater_Setup {

	protected static function add_hooks() {

		add_action( 'init', 'Theater_Setup::wpt_rewrite_rules' );

		add_filter( 'cron_schedules', 'Theater_Setup::cron_schedules' );

		add_filter( 'query_vars', 'Theater_Setup::add_tickets_url_iframe_query_vars' );
		add_action( 'init', 'Theater_Setup::add_tickets_url_iframe_rewrites' );

	}

	/**
	 * Initializes the setup.
	 *
	 * @since	0.16
	 * @uses	Theater_Setup::init_subclasses() to initialize the setup sub-classes.
	 * @uses	Theater_Setup::add_hooks to add all setup hooks.
	 * @return 	void
	 */
	static function init() {
		self::init_subclasses();
		self::add_hooks();
	}

	/**
	 * Initializes all setup sub-classes.
	 *
	 * @access protected
	 * @static
	 * @since	0.16
	 * @uses	Theater_Setup_Meta::init() to register all event date meta fields.
	 * @uses	Theater_Setup_Post_Types::init() to register custom event, date and season post types.
	 * @return 	void
	 */
	protected static function init_subclasses() {

		require_once( dirname( __FILE__ ) . '/class-theater-setup-language.php' );
		Theater_Setup_Language::init();

		require_once( dirname( __FILE__ ) . '/class-theater-setup-post-types.php' );
		Theater_Setup_Post_Types::init();

		require_once( dirname( __FILE__ ) . '/class-theater-setup-meta.php' );
		Theater_Setup_Meta::init();

		require_once( dirname( __FILE__ ) . '/class-theater-setup-lists.php' );
		Theater_Setup_Lists::init();

	}

	/**
	 * Adds event tickets URL iframe rewrites.
	 *
	 * Rewrite pretty iframed ticket screens URLs from:
	 *
	 * <code>
	 * http://example.com/tickets/my-event/123
	 * </code>
	 *
	 * to:
	 *
	 * <code>
	 * http://example.com/?pagename=tickets&wpt_event_tickets=123
	 * </code>
	 *
	 * @see		WPT_Setup::add_tickets_url_iframe_query_vars()
	 * @since	0.12
	 * @return	void
	 */
	static function add_tickets_url_iframe_rewrites() {
		global $wp_theatre;

		if ( empty( $wp_theatre->wpt_tickets_options['iframepage'] ) ) {
			return;
		}

		$iframe_page = get_post( $wp_theatre->wpt_tickets_options['iframepage'] );

		if ( is_null( $iframe_page ) ) {
			return;
		}

		add_rewrite_tag( '%wpt_event_tickets%', '.*' );

		add_rewrite_rule(
			$iframe_page->post_name.'/([a-z0-9-]+)/([0-9]+)$',
			'index.php?pagename='.$iframe_page->post_name.'&wpt_event_tickets=$matches[2]',
			'top'
		);

	}

	/**
	 * Adds 'wpt_event_tickets' to the query vars.
	 *
	 * Makes it possible to access iframed ticket screens like:
	 * http://example.com/?pagename=tickets&wpt_event_tickets=123
	 *
	 * @see WPT_Setup::add_tickets_url_iframe_rewrites()
	 * @access public
	 * @param mixed $query_vars
	 * @return void
	 */
	static function add_tickets_url_iframe_query_vars( $query_vars ) {
		$query_vars[] = 'wpt_event_tickets';
		return $query_vars;
	}

	/**
	 * Why is this here?.
	 */
	static function wpt_rewrite_rules() {
		do_action( 'wpt_rewrite_rules' );
	}




	static function cron_schedules( $schedules ) {
		// Adds once weekly to the existing schedules.
		$schedules['wpt_schedule'] = array(
			'interval' => 5 * 60,
			'display' => __( 'Every 5 minutes', 'theatre' ),
		);
		return $schedules;
	}
}



?>
