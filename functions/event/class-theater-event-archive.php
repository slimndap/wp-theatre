<?php

/**
 * Event Archive class.
 *
 * Customizes the archive pages of events.
 *
 * @since	0.15.10
 * @since	0.15.12	'Pre_get_posts' filters no longer run on admin pages.
 *					Fixes: https://wordpress.org/support/topic/incomplete-backend-listing-after-update/
 *					See: https://core.trac.wordpress.org/ticket/18993
 * @package	Theater/Events
 */
class Theater_Event_Archive {

	static function init() {

		if ( ! is_admin() ) {
			add_action( 'pre_get_posts', array( __CLASS__, 'set_is_post_type_archive' ), 5 );
			add_action( 'pre_get_posts', array( __CLASS__, 'set_events_order' ), 10 );
			add_action( 'pre_get_posts', array( __CLASS__, 'hide_past_events' ), 10 );
			add_action( 'pre_get_posts', array( __CLASS__, 'remove_past_events' ), 99 );

			add_filter( 'the_content', array( __CLASS__, 'set_event_date_content' ), 10, 1 );
			add_filter( 'the_title', array( __CLASS__, 'set_event_date_title' ), 10, 2 );
			add_filter( 'post_type_link', array( __CLASS__, 'set_event_date_permalink' ), 10, 2 );
			add_filter( 'get_post_metadata', array( __CLASS__, 'set_event_date_post_thumbnail' ), 10, 4 );

			add_filter( 'the_title', array( __CLASS__, 'add_title_actions' ), 10, 2 );
			add_filter( 'the_content', array( __CLASS__, 'add_content_actions' ), 10, 2 );

			add_action( 'theater/event/archive/content/before', array( __CLASS__, 'event_details' ) );

			add_filter( 'get_the_archive_title', array( __CLASS__, 'set_archive_title' ), 10, 1 );
			add_filter( 'get_the_archive_description', array( __CLASS__, 'set_archive_description' ), 10, 1 );

		}

	}

	static function is_event_archive_page() {
		global $wp_query;

		$event_archive_page = self::get_event_archive_page();

		if ( ! $event_archive_page ) {
			return false;
		}

			$queried_object_id = $wp_query->queried_object_id;

			return $event_archive_page->ID == $queried_object_id;
	}

	static function add_content_actions( $content ) {

		if ( ! in_the_loop() ) {
			return $content;
		}

		if ( ! is_post_type_archive( self::get_event_archive_post_type() ) ) {
			return $content;
		}

		ob_start();

		do_action( 'theater/event/archive/content/before', $content );

		echo $content;

		do_action( 'theater/event/archive/content/after', $content );

		return ob_get_clean();
	}

	static function add_title_actions( $title, $post_id ) {

		if ( ! in_the_loop() ) {
			return $title;
		}

		if ( ! is_post_type_archive( self::get_event_archive_post_type() ) ) {
			return $title;
		}

		ob_start();

		do_action( 'theater/event/archive/title/before', $title, $post_id );

		echo $title;

		do_action( 'theater/event/archive/title/after', $title, $post_id );

		return ob_get_clean();
	}

	static function event_details( $content ) {

		if ( WPT_Event::post_type_name == self::get_event_archive_post_type() ) {
			$event_date = new WPT_Event( get_the_id() );
			$template = '{{remark}}{{datetime}}{{location}}{{tickets}}';
			echo $event_date->html( $template );
		} else {
			$event = new WPT_Production( get_the_id() );
			$template = '{{dates}}{{cities}}';
			echo $event->html( $template );
		}

	}

	static function get_event_archive_page() {
		global $wp_theatre;
		return $wp_theatre->listing_page->page();
	}

	static function get_event_archive_post_type() {

		$post_type = WPT_Event::post_type_name;

		return $post_type;

	}

	static function hide_past_events( $query ) {

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( ! $query->is_post_type_archive( self::get_event_archive_post_type() ) ) {
			return;
		}

		$meta_query = $query->get( 'meta_query' );

		$meta_query[] = array(
			'key' => THEATER_ORDER_INDEX_KEY,
			'value' => time(),
			'compare' => '>=',
			'type' => 'NUMERIC',
		);

		$query->set( 'meta_query', $meta_query );

	}

	static function set_archive_description( $archive_description ) {

		if ( ! self::is_event_archive_page() ) {
			return $archive_description;
		}

		$event_archive_page = self::get_event_archive_page();

		return apply_filters( 'the_content', $event_archive_page->post_content );

	}

	static function set_archive_title( $title ) {

		if ( ! self::is_event_archive_page() ) {
			return $title;
		}

		return get_the_title( self::get_event_archive_page() );

	}

	static function set_event_date_content( $content ) {

		if ( ! in_the_loop() ) {
			return $content;
		}

		if ( ! is_post_type_archive( self::get_event_archive_post_type() ) ) {
			return $content;
		}

		if ( WPT_Event::post_type_name == self::get_event_archive_post_type() ) {
			$event_date = new WPT_Event( get_the_id() );
			$content = $event_date->production()->content();
		}

		return $content;
	}

	static function set_event_date_title( $title, $post_id ) {

		if ( ! in_the_loop() ) {
			return $title;
		}

		if ( ! is_post_type_archive( self::get_event_archive_post_type() ) ) {
			return $title;
		}

		if ( WPT_Event::post_type_name == self::get_event_archive_post_type() ) {
			$event_date = new WPT_Event( $post_id );
			$title  = $event_date->title();
		}

		return $title;
	}

	static function set_event_date_permalink( $permalink, $post ) {

		if ( ! in_the_loop() ) {
			return $permalink;
		}

		if ( ! is_post_type_archive( self::get_event_archive_post_type() ) ) {
			return $permalink;
		}

		if ( WPT_Event::post_type_name != get_post_type( $post->ID ) ) {
			return $permalink;
		}

		if ( WPT_Event::post_type_name != self::get_event_archive_post_type() ) {
			return $permalink;
		}

		$event_date = new WPT_Event( $post->ID );
		$permalink = $event_date->permalink();

		return $permalink;
	}

	static function set_event_date_post_thumbnail( $value, $post_id, $meta_key, $single ) {

		if ( '_thumbnail_id' != $meta_key ) {
			return $value;
		}

		if ( ! in_the_loop() ) {
			return $value;
		}

		if ( ! is_post_type_archive( self::get_event_archive_post_type() ) ) {
			return $value;
		}

		if ( WPT_Event::post_type_name != get_post_type( $post_id ) ) {
			return $value;
		}

		if ( WPT_Event::post_type_name != self::get_event_archive_post_type() ) {
			return $value;
		}

		$event_date = new WPT_Event( $post_id );
		return $event_date->production()->thumbnail();

	}

	/**
	 * Orders events in ascending order on event archive pages.
	 *
	 * @since	0.15.10
	 * @since	0.15.13     Only set the order to ascending.
	 *						Leave all other ordering to the Theater_Event_Order class.
	 * @param 	WP_Query	$query
	 * @return 	void
	 */
	static function set_events_order( $query ) {

		global $wp_theatre;

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( ! $query->is_post_type_archive( self::get_event_archive_post_type() ) ) {
			return;
		}

		$query->set( 'order', 'ASC' );

	}

	/**
	 * Removes past events from event archive pages.
	 *
	 * @since	0.15.10
	 * @uses	WPT_Order::meta_key to remove past events from the event archive page.
	 * @param 	WP_Query	$query
	 * @return 	void
	 */
	static function remove_past_events( $query ) {

		global $wp_theatre;

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( ! $query->is_post_type_archive( WPT_Production::post_type_name ) ) {
			return;
		}

		$meta_query = $query->get( 'meta_query' );

		if ( empty( $meta_query ) ) {
			$meta_query = array();
		}

		$meta_query[] = array(
			'key' => THEATER_ORDER_INDEX_KEY,
			'value' => current_time( 'timestamp' ) - get_option( 'gmt_offset' ) * 3600,
			'compare' => '>',
		);

		$query->set( 'meta_query', $meta_query );

	}

	static function set_is_post_type_archive( $query ) {
			global $wp_theatre;

		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( ! self::is_event_archive_page() ) {
			return;
		}

		$query->set( 'post_type', self::get_event_archive_post_type() );
		$query->set( 'pagename', '' );

		$query->is_page = 0;
		$query->is_singular = 0;
		$query->is_archive = 1;
		$query->is_post_type_archive = 1;

	}
}

