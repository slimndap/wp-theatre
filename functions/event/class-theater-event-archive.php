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
			add_action( 'pre_get_posts', array( __CLASS__, 'set_events_order' ), 10 );
			add_action( 'pre_get_posts', array( __CLASS__, 'remove_past_events' ), 10 );
		}

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

		if ( ! $query->is_post_type_archive( WPT_Production::post_type_name ) ) {
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
}
