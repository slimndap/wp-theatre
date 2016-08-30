<?php

/**
 * Event Archive class.
 *
 * Customizes the archive pages of events.
 *
 * @since	0.15.10
 * @package	Theater/Events
 */
class Theater_Event_Archive {

	static function init() {
		add_action( 'pre_get_posts', array( __CLASS__, 'set_events_order' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'remove_past_events' ) );
	}

	/**
	 * Orders all events by start date on event archive pages.
	 * 
	 * @since	0.15.10
	 * @uses	WPT_Order::meta_key to order events on the event archive page by start date.
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

		$query->set( 'meta_key', $wp_theatre->order->meta_key );
		$query->set( 'orderby', 'meta_value' );
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
			'key' => $wp_theatre->order->meta_key,
			'value' => current_time( 'timestamp' ) - get_option( 'gmt_offset' ) * 3600,
			'compare' => '>',
		);

		$query->set( 'meta_query', $meta_query );

	}
}
